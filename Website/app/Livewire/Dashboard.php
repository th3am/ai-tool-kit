<?php

namespace App\Livewire;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\ToolJob;
use App\Services\CreditService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithFileUploads;

class Dashboard extends Component
{
    use WithFileUploads;

    // ─── Stepper ────────────────────────────────────────────────────────────
    public int $step = 1;

    // ─── Tool Selection ──────────────────────────────────────────────────────
    public ?string $selectedTool = null;

    // ─── Shared Inputs ───────────────────────────────────────────────────────
    public ?string $topic = null;

    public ?string $instructions = null;

    public string $style = 'Modern';

    public int $slideCount = 5;

    public $uploadedFile = null;

    // ─── Video Explainer Inputs ──────────────────────────────────────────────
    public string $videoLanguage = 'ar';

    public bool $videoEnableCaptions = true;

    // ─── WhatsApp Sharing ────────────────────────────────────────────────────
    public string $whatsappNumber = '';

    public ?string $whatsappResult = null;

    // ─── Result State ─────────────────────────────────────────────────────────
    public ?string $generatedMindMap = null;

    public ?string $generatedMindMapJobId = null;

    public ?string $generatedAnimation = null;

    public ?string $generatedAnimationPath = null;

    public ?string $generatedAnimationJobId = null;

    public ?string $generatedAudio = null;

    public array $generatedSlides = [];

    public ?int $generatedPresentationId = null;

    public ?string $generatedVideoPath = null;

    public ?int $generatedVideoJobId = null;

    public ?int $generatedQuizId = null;

    public ?int $lastResultJobId = null;

    // ─── Processing State ────────────────────────────────────────────────────
    public bool $isProcessing = false;

    public ?string $processingStage = null;   // e.g. "Generating slides…"

    public ?int $pollingJobId = null;   // ToolJob id to poll

    public ?string $pollingTool = null;   // which tool we're waiting for

    public ?string $jobNotice = null;

    // ─── Credits (refreshed on mount/after generate) ──────────────────────────
    public int $userCredits = 0;

    // ─── Lifecycle ───────────────────────────────────────────────────────────
    public function mount(): void
    {
        $this->userCredits = (int) Auth::user()->credits;
        ToolJob::where('user_id', Auth::id())
            ->whereIn('status', ['queued', 'running'])
            ->where('updated_at', '<', now()->subMinutes(
                (int) config('queue.stale_after_minutes', 40)
            ))
            ->update([
                'status' => 'failed',
                'error_message' => 'The background worker stopped responding before this job completed.',
            ]);

        // restore ongoing job if the user refreshes mid-process
        $ongoing = ToolJob::where('user_id', Auth::id())
            ->whereIn('status', ['queued', 'running'])
            ->whereIn('tool_type', [
                'video-explainer',
                'lecture',
                'video-animation',
                'audio',
                'presentation',
                'mindmap',
                'quiz',
            ])
            ->latest()
            ->first();

        if ($ongoing) {
            $this->pollingJobId = $ongoing->id;
            $this->pollingTool = $ongoing->tool_type;
            $this->selectedTool = $this->dashboardToolFor($ongoing->tool_type);
            $this->isProcessing = true;
            $this->processingStage = $ongoing->status === 'running'
                ? $this->getRunningStage($ongoing->tool_type)
                : 'Waiting for a queue worker…';
            $this->step = 2;
        }
    }

    // ─── Step Navigation ──────────────────────────────────────────────────────
    public function setStep(int $step): void
    {
        if ($step === 1) {
            if ($this->isProcessing) {
                $this->cancelActiveJob();
            }

            // Reset everything when going back to step 1
            $this->reset([
                'selectedTool', 'topic', 'instructions', 'style',
                'slideCount', 'uploadedFile', 'videoLanguage', 'videoEnableCaptions',
                'isProcessing', 'processingStage', 'pollingJobId', 'pollingTool',
                'generatedMindMap', 'generatedMindMapJobId',
                'generatedAnimation', 'generatedAnimationPath', 'generatedAnimationJobId',
                'generatedAudio', 'generatedSlides', 'generatedPresentationId',
                'generatedVideoPath', 'generatedVideoJobId', 'generatedQuizId',
                'lastResultJobId',
            ]);
            $this->slideCount = 5;
        }
        $this->step = $step;
    }

    public function selectTool(string $toolId): void
    {
        $this->selectedTool = $toolId;
        // Stay on step 1 — user must click "Next" to proceed
        $this->slideCount = match ($toolId) {
            'quiz-generator' => 10,
            'video-explainer' => 5,
            'lecture' => 5,
            'powerpoint-generator' => 5,
            default => 5,
        };
    }

    public function goToStep2(): void
    {
        if (! $this->selectedTool) {
            $this->addError('tool', 'Please select a tool before continuing.');
            return;
        }
        $this->step = 2;
    }

    // ─── File Handling ────────────────────────────────────────────────────────
    public function removeFile(): void
    {
        $this->uploadedFile = null;
    }

    // ─── Main Generate Action ────────────────────────────────────────────────
    public function generate(CreditService $creditService): void
    {
        // Basic validation
        $rules = [
            'topic' => 'nullable|string|max:3000',
        ];

        if (in_array($this->selectedTool, ['video-explainer', 'lecture'], true)) {
            $rules['slideCount'] = 'required|integer|min:3|max:30';
        }

        $this->validate($rules);

        if (empty($this->topic) && ! $this->uploadedFile) {
            $this->addError('topic', 'Please enter a topic or upload a file.');

            return;
        }

        // Map dashboard tool slug → CreditService tool type
        $toolTypeMap = [
            'mindmap-generator'   => 'mindmap',
            'audio'               => 'audio',
            'video-animation'     => 'video-animation',
            'powerpoint-generator'=> 'presentation',
            'video-explainer'     => 'video-explainer',
            'lecture'             => 'lecture',
            'quiz-generator'      => 'quiz',
        ];
        $toolType = $toolTypeMap[$this->selectedTool] ?? $this->selectedTool;

        // ── Credit check ──────────────────────────────────────────────────────
        $user = Auth::user();
        if (! $creditService->check($user, $toolType)) {
            $cost = $creditService->costFor($toolType);
            $this->addError('topic', "Insufficient credits. This tool requires {$cost} credits. You have {$user->credits} credits remaining.");
            return;
        }

        // Deduct credits before dispatching
        $creditService->deduct($user, $toolType);
        $this->userCredits = (int) $user->fresh()->credits;

        $this->isProcessing = true;
        $this->processingStage = 'Initialising…';

        try {
            match ($this->selectedTool) {
                'mindmap-generator' => $this->runMindMap(),
                'audio' => $this->runAudio(),
                'video-animation' => $this->runVideoAnimation(),
                'powerpoint-generator' => $this->runPresentation(),
                'video-explainer' => $this->runVideoExplainer(),
                'lecture' => $this->runVideoExplainer(),   // reuse pipeline
                'quiz-generator' => $this->runQuiz(),
                default => null,
            };
        } catch (\Exception $e) {
            Log::error("Dashboard::generate – {$e->getMessage()}");
            // Refund credits on error
            $creditService->refund($user, $toolType);
            $this->userCredits = (int) $user->fresh()->credits;
            $this->isProcessing = false;
            $this->processingStage = null;
            $this->addError('topic', 'Something went wrong: '.$e->getMessage());
        }
    }

    // ─── Tool Runners ────────────────────────────────────────────────────────

    private function runMindMap(): void
    {
        $this->processingStage = 'Generating mind map…';

        /** @var \App\Services\Ai\MindMapGenerator $svc */
        $svc = app(\App\Services\Ai\MindMapGenerator::class);

        $filePath = null;
        if ($this->uploadedFile) {
            $filePath = $this->uploadedFile->getRealPath();
        }

        $markdown = $svc->generate($this->topic ?? '', $filePath);

        // Create session + tool job for history
        $session = $this->ensureSession('Mind Map: '.($this->topic ?? 'Upload'));
        $job = ToolJob::create([
            'user_id' => Auth::id(),
            'chat_session_id' => $session->id,
            'tool_type' => 'mindmap',
            'status' => 'succeeded',
            'params' => ['topic' => $this->topic],
            'results' => ['raw_markdown' => $markdown],
        ]);
        ChatMessage::create([
            'session_id' => $session->id,
            'role' => 'assistant',
            'content' => "Here is your mind map for: **{$this->topic}**",
            'tool_job_id' => $job->id,
        ]);

        $this->generatedMindMap = $markdown;
        $this->generatedMindMapJobId = $job->id;
        $this->lastResultJobId = $job->id;
        $this->isProcessing = false;
        $this->processingStage = null;
    }

    private function runAudio(): void
    {
        $this->processingStage = 'Queuing audio generation…';

        $inputContent = $this->topic ?? '';
        $inputType = 'text';

        if ($this->uploadedFile) {
            $inputContent = $this->uploadedFile->getRealPath();
            $inputType = 'file';
        }

        $session = $this->ensureSession('Audio: '.($this->topic ?? 'Upload'));
        $job = ToolJob::create([
            'user_id' => Auth::id(),
            'chat_session_id' => $session->id,
            'tool_type' => 'audio',
            'status' => 'queued',
            'params' => ['topic' => $this->topic, 'inputType' => $inputType],
        ]);

        ChatMessage::create([
            'session_id' => $session->id,
            'role' => 'assistant',
            'content' => "I'm generating audio narration for: **{$this->topic}**",
            'tool_job_id' => $job->id,
        ]);

        \App\Jobs\GenerateAudioJob::dispatch(
            Auth::id(),
            $session->id,
            $job->id,
            $inputContent,
            $inputType
        );

        $this->pollingJobId = $job->id;
        $this->pollingTool = 'audio';
        $this->processingStage = 'Generating audio narration… (may take ~1 min)';
    }

    private function runVideoAnimation(): void
    {
        $this->processingStage = 'Queuing 2D animation…';

        $session = $this->ensureSession('Animation: '.($this->topic ?? ''));
        $job = ToolJob::create([
            'user_id' => Auth::id(),
            'chat_session_id' => $session->id,
            'tool_type' => 'video-animation',
            'status' => 'queued',
            'params' => [
                'prompt' => $this->topic,
                'credits_charged' => true,
            ],
        ]);

        ChatMessage::create([
            'session_id' => $session->id,
            'role' => 'assistant',
            'content' => "I'm generating a 2D animation for: **{$this->topic}**",
            'tool_job_id' => $job->id,
        ]);

        \App\Jobs\GenerateAnimationJob::dispatch(
            Auth::id(),
            $session->id,
            $job->id,
            $this->topic
        );

        $this->pollingJobId = $job->id;
        $this->pollingTool = 'video-animation';
        $this->processingStage = 'Generating 2D animation… (may take ~2 min)';
    }

    private function runPresentation(): void
    {
        $this->processingStage = 'Queuing presentation generation…';

        $session = $this->ensureSession('Presentation: '.($this->topic ?? ''));
        $job = ToolJob::create([
            'user_id' => Auth::id(),
            'chat_session_id' => $session->id,
            'tool_type' => 'presentation',
            'status' => 'queued',
            'params' => [
                'topic' => $this->topic,
                'style' => $this->style,
                'slideCount' => $this->slideCount,
                'instructions' => $this->instructions,
            ],
        ]);

        ChatMessage::create([
            'session_id' => $session->id,
            'role' => 'assistant',
            'content' => "I've started generating your presentation on **{$this->topic}**.",
            'tool_job_id' => $job->id,
        ]);

        \App\Jobs\GeneratePresentationJob::dispatch(
            Auth::id(),
            $session->id,
            $job->id,
            $this->topic,
            $this->style,
            $this->slideCount,
            $this->instructions ?? ''
        );

        $this->pollingJobId = $job->id;
        $this->pollingTool = 'presentation';
        $this->processingStage = 'Generating presentation… (may take ~1–2 min)';
    }

    private function runVideoExplainer(): void
    {
        $this->processingStage = 'Queuing video explainer…';

        $toolType = $this->selectedTool === 'lecture' ? 'lecture' : 'video-explainer';
        $session = $this->ensureSession('Video Explainer: '.($this->topic ?? ''));

        $job = ToolJob::create([
            'user_id' => Auth::id(),
            'chat_session_id' => $session->id,
            'tool_type' => $toolType,
            'status' => 'queued',
            'params' => [
                'topic' => $this->topic,
                'style' => $this->style,
                'slideCount' => $this->slideCount,
                'instructions' => $this->instructions,
                'language' => $this->videoLanguage,
                'enableCaptions' => $this->videoEnableCaptions,
            ],
        ]);

        ChatMessage::create([
            'session_id' => $session->id,
            'role' => 'assistant',
            'content' => "I'm generating your video explainer on **{$this->topic}**.",
            'tool_job_id' => $job->id,
        ]);

        \App\Jobs\GenerateVideoExplainerJob::dispatch(
            Auth::id(),
            $session->id,
            $job->id,
            $this->topic,
            $this->style,
            $this->slideCount,
            $this->instructions ?? '',
            $this->videoLanguage,
            $this->videoEnableCaptions
        );

        $this->pollingJobId = $job->id;
        $this->pollingTool = $toolType;
        $this->processingStage = 'Generating video explainer… (2–5 min)';
    }

    private function runQuiz(): void
    {
        $this->processingStage = 'Queuing quiz generation…';

        $inputContent = $this->topic ?? '';
        $inputType = 'text';

        if ($this->uploadedFile) {
            $inputContent = $this->topic ?: 'Uploaded file quiz';
            $inputType = 'file';
        }

        $session = $this->ensureSession('Quiz: '.($this->topic ?? 'Upload'));
        $quiz = \App\Models\Quiz::create([
            'user_id' => Auth::id(),
            'title' => \Illuminate\Support\Str::limit($this->topic ?: 'Generated Quiz', 255),
            'source_type' => 'text',
            'source_text' => $inputContent,
            'max_questions' => $this->slideCount,
            'status' => 'pending',
        ]);

        $job = ToolJob::create([
            'user_id' => Auth::id(),
            'chat_session_id' => $session->id,
            'tool_type' => 'quiz',
            'status' => 'queued',
            'params' => [
                'topic' => $this->topic,
                'count' => $this->slideCount,
                'inputType' => $inputType,
            ],
            'results' => ['quiz_id' => $quiz->id],
        ]);

        ChatMessage::create([
            'session_id' => $session->id,
            'role' => 'assistant',
            'content' => "I'm generating your quiz on **{$this->topic}**.",
            'tool_job_id' => $job->id,
        ]);

        \App\Jobs\GenerateQuizJob::dispatch($quiz, $job->id, $session->id);

        $this->pollingJobId = $job->id;
        $this->pollingTool = 'quiz';
        $this->processingStage = 'Generating quiz… (may take ~1 min)';
    }

    // ─── Polling ────────────────────────────────────────────────────────────
    public function pollJobStatus(): void
    {
        if (! $this->pollingJobId) {
            return;
        }

        $job = ToolJob::find($this->pollingJobId);
        if (! $job) {
            $this->clearProcessingState();
            $this->addError('topic', 'The background job no longer exists.');

            return;
        }

        if ($job->status === 'running') {
            $this->processingStage = $this->getRunningStage($job->tool_type);
        }

        if ($job->status === 'succeeded') {
            $this->handleSuccess($job);
        }

        if ($job->status === 'failed') {
            $this->clearProcessingState();
            $this->addError('topic', 'Generation failed: '.($job->error_message ?? 'Unknown error'));
        }

        if ($job->isCancelled()) {
            $this->clearProcessingState();
            $this->jobNotice = 'The job was cancelled.';
        }
    }

    public function cancelJob(): void
    {
        $cancelled = $this->cancelActiveJob();
        $this->step = 1;
        $this->selectedTool = null;
        $this->jobNotice = $cancelled
            ? 'The job was cancelled successfully.'
            : 'There was no active job to cancel.';
    }

    private function getRunningStage(string $type): string
    {
        return match ($type) {
            'audio' => 'Generating audio narration…',
            'presentation' => 'Rendering slides…',
            'mindmap' => 'Building mind map…',
            'video-explainer','lecture' => 'Assembling MP4 video…',
            'video-animation' => 'Rendering animation…',
            'quiz' => 'Generating quiz questions…',
            default => 'Processing…',
        };
    }

    private function handleSuccess(ToolJob $job): void
    {
        $this->clearProcessingState();
        $this->lastResultJobId = $job->id;

        match ($job->tool_type) {
            'audio' => $this->generatedAudio = $job->results['audio_path'] ?? null,

            'presentation' => $this->handlePresentationSuccess($job),

            'mindmap' => $this->handleMindMapSuccess($job),

            'video-animation' => $this->handleAnimationSuccess($job),

            'video-explainer', 'lecture' => $this->handleVideoExplainerSuccess($job),

            'quiz' => $this->generatedQuizId = $job->results['quiz_id'] ?? null,

            default => null,
        };
    }

    private function handlePresentationSuccess(ToolJob $job): void
    {
        $presentationId = $job->results['presentation_id'] ?? null;
        if ($presentationId) {
            $presentation = \App\Models\Presentation::find($presentationId);
            if ($presentation) {
                $this->generatedSlides = $presentation->content ?? [];
                $this->generatedPresentationId = $presentation->id;
            }
        }
    }

    private function handleMindMapSuccess(ToolJob $job): void
    {
        $this->generatedMindMap = $job->results['raw_markdown'] ?? null;
        $this->generatedMindMapJobId = $job->id;
    }

    private function handleAnimationSuccess(ToolJob $job): void
    {
        $this->generatedAnimation = $job->results['svg_content'] ?? null;
        $this->generatedAnimationPath = $job->results['svg_path'] ?? null;
        $this->generatedAnimationJobId = $job->id;
    }

    private function handleVideoExplainerSuccess(ToolJob $job): void
    {
        $this->generatedVideoPath = $job->results['video_path'] ?? null;
        $this->generatedVideoJobId = $job->id;
    }

    // ─── After Result Actions ────────────────────────────────────────────────
    public function continueToChat(): void
    {
        if ($this->lastResultJobId) {
            $job = ToolJob::where('user_id', Auth::id())
                ->whereKey($this->lastResultJobId)
                ->first();

            if ($job && $job->chat_session_id) {
                $this->redirect(route('chat.session', $job->chat_session_id));

                return;
            }
        }

        // Find the session linked to the last successful job
        $lastJob = ToolJob::where('user_id', Auth::id())
            ->whereIn('tool_type', ['mindmap', 'audio', 'video-animation', 'presentation', 'video-explainer', 'lecture', 'quiz'])
            ->where('status', 'succeeded')
            ->latest()
            ->first();

        if ($lastJob && $lastJob->chat_session_id) {
            $this->redirect(route('chat.session', $lastJob->chat_session_id));

            return;
        }

        $this->setStep(1);
    }

    // ─── WhatsApp Sharing ────────────────────────────────────────────────────
    public function sendToWhatsApp(\App\Services\WhatsAppService $whatsappService, \App\Services\PdfGeneratorService $pdfService): void
    {
        $this->validate(['whatsappNumber' => 'required|string|min:10|max:20']);

        if (! $this->generatedPresentationId) {
            return;
        }

        $presentation = \App\Models\Presentation::find($this->generatedPresentationId);
        if (! $presentation) {
            return;
        }

        $path = $presentation->pdf_path;
        if (! $path || ! \Illuminate\Support\Facades\Storage::exists('public/'.$path)) {
            try {
                $path = $pdfService->generate($presentation);
            } catch (\Exception $e) {
                $this->addError('whatsapp', 'Failed to generate PDF.');

                return;
            }
        }

        $url = asset('storage/'.$path);
        $number = preg_replace('/[^0-9]/', '', $this->whatsappNumber);

        $success = $whatsappService->sendMedia(
            $number,
            'document',
            $url,
            "Here is the presentation: {$presentation->topic}"
        );

        $this->whatsappResult = $success ? 'success' : null;

        if (! $success) {
            $this->addError('whatsapp', 'Failed to send. Verify number.');
        }
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────
    private function ensureSession(string $title): ChatSession
    {
        return ChatSession::create([
            'user_id' => Auth::id(),
            'title' => \Illuminate\Support\Str::limit($title, 60),
        ]);
    }

    private function cancelActiveJob(): bool
    {
        if (! $this->pollingJobId) {
            $this->clearProcessingState();

            return false;
        }

        $updated = ToolJob::whereKey($this->pollingJobId)
            ->where('user_id', Auth::id())
            ->whereIn('status', ['queued', 'running'])
            ->update([
                'status' => ToolJob::STATUS_CANCELLED,
                'error_message' => 'Cancelled by the user.',
            ]);

        $this->clearProcessingState();

        return $updated > 0;
    }

    private function clearProcessingState(): void
    {
        $this->isProcessing = false;
        $this->processingStage = null;
        $this->pollingJobId = null;
        $this->pollingTool = null;
    }

    private function dashboardToolFor(string $toolType): string
    {
        return match ($toolType) {
            'presentation' => 'powerpoint-generator',
            'mindmap' => 'mindmap-generator',
            'quiz' => 'quiz-generator',
            default => $toolType,
        };
    }

    // ─── Render ──────────────────────────────────────────────────────────────
    public function render()
    {
        $recentJobs = ToolJob::where('user_id', Auth::id())
            ->latest()
            ->take(6)
            ->get();

        return view('livewire.dashboard', compact('recentJobs'))->layout('components.layouts.app');
    }
}
