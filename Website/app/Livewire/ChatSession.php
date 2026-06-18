<?php

namespace App\Livewire;

use Livewire\Component;

use App\Models\ChatSession as SessionModel;
use App\Models\ChatMessage;
use App\Models\Quiz;
use App\Models\ToolJob;
use Illuminate\Support\Facades\Auth;

class ChatSession extends Component
{
    public $sessionId;
    public $session;
    public $chatMessages = [];
    public $newMessage = '';
    public $isTyping = false;
    
    // Tool Viewing State
    public $viewingMindMap = null;
    public $viewingMindMapJobId = null;
    public $pendingTool = null;
    
    // WhatsApp Sharing
    public $activeShareJobId = null;
    public $whatsappNumber = '';

    public function toggleShare($jobId)
    {
        $this->activeShareJobId = $this->activeShareJobId === $jobId ? null : $jobId;
        $this->whatsappNumber = auth()->user()->whatsapp_number ?? ''; 
    }

    public function sendToWhatsApp($jobId, \App\Services\WhatsAppService $whatsappService, \App\Services\PdfGeneratorService $pdfService)
    {
        $this->validate([
            'whatsappNumber' => 'required|string|min:10|max:20',
        ]);

        $job = \App\Models\ToolJob::find($jobId);
        if (!$job || $job->tool_type !== 'presentation') return;

        // Get Presentation ID from results
        $presentationId = $job->results['presentation_id'] ?? null;
        if (!$presentationId) return;

        $presentation = \App\Models\Presentation::find($presentationId);
        if (!$presentation) return;

        // Ensure PDF exists
        $path = $presentation->pdf_path;
        if (!$path || !\Illuminate\Support\Facades\Storage::exists('public/' . $path)) {
            try {
                $path = $pdfService->generate($presentation);
            } catch (\Exception $e) {
                $this->addError('whatsapp_' . $jobId, 'Failed to generate PDF.');
                return;
            }
        }

        $url = asset('storage/' . $path);
        $number = preg_replace('/[^0-9]/', '', $this->whatsappNumber);

        $success = $whatsappService->sendMedia(
            $number, 
            'document', 
            $url, 
            "Here is the presentation: {$presentation->topic}"
        );

        if ($success) {
            session()->flash('whatsapp_success_' . $jobId, 'Sent!');
            $this->activeShareJobId = null;
            $this->whatsappNumber = '';
        } else {
             $this->addError('whatsapp_' . $jobId, 'Failed to send. Verify number.');
        }
    }

    public function mount($sessionId)
    {
        $this->sessionId = $sessionId;
        $this->loadSession();
    }
    
    public function viewToolResult($jobId)
    {
        $job = \App\Models\ToolJob::find($jobId);
        if ($job && $job->tool_type === 'mindmap' && isset($job->results['raw_markdown'])) {
            $this->viewingMindMap = $job->results['raw_markdown'];
            $this->viewingMindMapJobId = $job->id;
        }
    }

    public function closeToolResult()
    {
        $this->viewingMindMap = null;
        $this->viewingMindMapJobId = null;
    }
    
    public function startTool($tool)
    {
        $this->pendingTool = $tool;
        // Optionally add a temporary UI indicator or just let the placeholder change
        $this->dispatch('focus-input'); // If we had JS to focus
    }

    public function sendMessage(
        \App\Services\Ai\MindMapGenerator $mindMapService, 
        \App\Services\Ai\PresentationGenerator $presentationService, 
        \App\Services\PdfGeneratorService $pdfService,
        \App\Services\Ai\ChatService $chatService
    )
    {
        $this->validate([
            'newMessage' => 'required|string|max:2000',
        ]);

        // Save User Message
        $userMsg = ChatMessage::create([
            'session_id' => $this->sessionId,
            'role' => 'user',
            'content' => $this->newMessage,
        ]);

        // Set Title if new
        if ($this->session && ($this->session->title === 'New Chat' || empty($this->session->title))) {
            $this->session->update(['title' => \Illuminate\Support\Str::limit($this->newMessage, 30)]);
            $this->dispatch('chat-session-updated'); 
        }

        $prompt = $this->newMessage;
        $toolToRun = $this->pendingTool;
        
        $this->newMessage = '';
        $this->pendingTool = null; // Reset
        
        $this->loadSession(); // Refresh UI immediately

        // If a tool was pending, execute it
        if ($toolToRun) {
            $this->executeTool($toolToRun, $prompt, $mindMapService, $presentationService, $pdfService);
        } else {
            // Standard Chat with RAG Context
            try {
                $response = $chatService->chat($prompt, $this->sessionId);
                
                ChatMessage::create([
                    'session_id' => $this->sessionId,
                    'role' => 'assistant',
                    'content' => $response,
                ]);
            } catch (\Exception $e) {
                // Fallback
                 ChatMessage::create([
                    'session_id' => $this->sessionId,
                    'role' => 'system',
                    'content' => "Error: " . $e->getMessage(),
                ]);
            }
            
            $this->loadSession();
        }
        
        // Notify Sidebar to Update
        $this->dispatch('chat-session-updated');
    }
    
    protected function executeTool($tool, $prompt, $mindMapService, $presentationService, $pdfService)
    {
        $session = $this->session;
        
        if ($tool === 'mindmap') {
            // Create Job
            $job = \App\Models\ToolJob::create([
                'user_id' => Auth::id(),
                'chat_session_id' => $session->id,
                'tool_type' => 'mindmap',
                'status' => 'running',
                'params' => ['topic' => $prompt]
            ]);

            try {
                // Determine file context if any (from session)
                $file = null; 
                
                $markdown = $mindMapService->generate($prompt, $file);
                
                $job->update([
                    'status' => 'succeeded',
                    'results' => ['raw_markdown' => $markdown]
                ]);

                ChatMessage::create([
                    'session_id' => $session->id,
                    'role' => 'assistant',
                    'content' => "Here is the mind map for: **$prompt**",
                    'tool_job_id' => $job->id,
                ]);

            } catch (\Exception $e) {
                 $job->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
                 ChatMessage::create([
                    'session_id' => $session->id,
                    'role' => 'system',
                    'content' => "Error generating mind map: " . $e->getMessage(),
                ]);
            }
        }
        elseif ($tool === 'presentation') {
            // Create Tool Job (Queued)
            $job = \App\Models\ToolJob::create([
                'user_id' => Auth::id(),
                'chat_session_id' => $session->id,
                'tool_type' => 'presentation',
                'status' => 'queued',
                'params' => ['topic' => $prompt, 'style' => 'Modern']
            ]);

            // Dispatch Job
            \App\Jobs\GeneratePresentationJob::dispatch(
                Auth::id(),
                $session->id,
                $job->id,
                $prompt,
                'Modern', // Default style
                5,        // Default slides
                ''        // No extra instructions from chat yet
            );

            // Immediate feedback
            ChatMessage::create([
                'session_id' => $session->id,
                'role' => 'assistant',
                'content' => "I've started generating your presentation on **$prompt**. It marks as a background task and will appear here when ready.",
                'tool_job_id' => $job->id,
            ]);
        }
        elseif ($tool === 'audio') {
            // Create Job
            $job = \App\Models\ToolJob::create([
                'user_id' => Auth::id(),
                'chat_session_id' => $session->id,
                'tool_type' => 'audio',
                'status' => 'queued',
                'params' => ['inputType' => 'text'] // Chat is always text for now
            ]);

            \App\Jobs\GenerateAudioJob::dispatch(
                Auth::id(),
                $session->id,
                $job->id,
                $prompt,
                'text'
            );

            ChatMessage::create([
                'session_id' => $session->id,
                'role' => 'assistant',
                'content' => "I'm generating audio narration for: **$prompt**",
                'tool_job_id' => $job->id,
            ]);
        }
        elseif ($tool === 'video-animation') {
             $job = \App\Models\ToolJob::create([
                'user_id' => auth()->id(),
                'chat_session_id' => $this->session->id,
                'tool_type' => 'video-animation',
                'status' => 'queued',
                'params' => ['prompt' => $prompt]
            ]);

            \App\Jobs\GenerateAnimationJob::dispatch(
                auth()->id(),
                $this->session->id,
                $job->id,
                $prompt
            );

            ChatMessage::create([
                'session_id' => $this->session->id,
                'role' => 'assistant',
                'content' => "I'm generating an animation for you based on: \"$prompt\". This might take a moment.",
                'tool_job_id' => $job->id,
            ]);
        }
        elseif ($tool === 'quiz') {
            $quiz = Quiz::create([
                'user_id' => Auth::id(),
                'title' => \Illuminate\Support\Str::limit($prompt ?: 'Generated Quiz', 255),
                'source_type' => 'text',
                'source_text' => $prompt,
                'max_questions' => 5,
                'status' => 'pending',
            ]);

            $job = ToolJob::create([
                'user_id' => Auth::id(),
                'chat_session_id' => $session->id,
                'tool_type' => 'quiz',
                'status' => 'queued',
                'params' => ['topic' => $prompt, 'count' => 5, 'inputType' => 'text'],
                'results' => ['quiz_id' => $quiz->id],
            ]);

            \App\Jobs\GenerateQuizJob::dispatch($quiz, $job->id, $session->id);

            ChatMessage::create([
                'session_id' => $session->id,
                'role' => 'assistant',
                'content' => "I'm generating your quiz on **{$prompt}**.",
                'tool_job_id' => $job->id,
            ]);
        }
        
        $this->loadSession();
    }

    public function loadSession()
    {
        $this->session = SessionModel::with(['messages.toolJob'])->findOrFail($this->sessionId);
        
        // Security check
        if ($this->session->user_id !== Auth::id()) {
            abort(403);
        }

        $this->chatMessages = $this->session->messages;
    }



    public function render()
    {
        return view('livewire.chat-session')->layout('components.layouts.app');
    }
}
