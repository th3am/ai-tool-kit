<?php

namespace App\Services\Ai;

use App\Models\ToolJob;
use App\Models\Presentation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatService
{
    protected $apiKey;
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
    }

    public function chat(string $userMessage, $sessionId)
    {
        if (!$this->apiKey) {
            return "Error: Missing API Key.";
        }

        $context = $this->buildContext($sessionId);

        $systemPrompt = "You are a helpful AI assistant. The user is asking questions in a session where they might have generated content using tools (Mind Maps, Presentations, Audio). \n\n";
        
        if (!empty($context)) {
            $systemPrompt .= "Here is the context of the content generated in this session:\n\n" . $context . "\n\n";
            $systemPrompt .= "INSTRUCTION: Use the above context to answer the user's question if relevant. If the user asks about specific details in the mind map, presentation, or audio, refer to the data above.";
        } else {
            $systemPrompt .= "No specific content has been generated yet. Answer the user's question normally.";
        }

        $systemPrompt .= "\n\nFORMATTING: Use clear structure. Use bolding for key terms, bullet points for lists, and headers for sections to make the response very readable.";

        if (env('AI_PROVIDER', 'gemini') === 'chatgpt') {
            $endpoint = env('CHATGPT_API_ENDPOINT', 'https://gpt-api.metaphilia.com/chat');
            $proxyApiKey = env('CHATGPT_API_KEY');

            $response = Http::withToken($proxyApiKey)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->withoutVerifying()
                ->post($endpoint, [
                    'message' => $systemPrompt . "\n\nUser Question: " . $userMessage
                ]);

            if ($response->failed()) {
                Log::error('ChatGPT API Error (Chat)', ['body' => $response->body()]);
                return "I'm sorry, I'm having trouble connecting to the AI right now.";
            }

            $data = $response->json();
            return $data['response'] ?? "I didn't understand that.";
        }

        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->withoutVerifying()
            ->post("{$this->baseUrl}?key={$this->apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $systemPrompt . "\n\nUser Question: " . $userMessage]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 1000,
                ]
            ]);

        if ($response->failed()) {
            Log::error('Gemini Chat API Error', ['body' => $response->body()]);
            return "I'm sorry, I'm having trouble connecting to the AI right now.";
        }

        $data = $response->json();
        return $data['candidates'][0]['content']['parts'][0]['text'] ?? "I didn't understand that.";
    }

    protected function buildContext($sessionId)
    {
        $jobs = ToolJob::where('chat_session_id', $sessionId)
            ->where('status', 'succeeded')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $contextParts = [];

        foreach ($jobs as $job) {
            $topic = $job->params['topic'] ?? 'Unknown';

            if ($job->tool_type === 'mindmap') {
                $markdown = $job->results['raw_markdown'] ?? '';
                if ($markdown) {
                    $contextParts[] = "[Mind Map on topic '$topic':\n$markdown\n]";
                }
            }
            elseif ($job->tool_type === 'audio') {
                $script = $job->results['script'] ?? '';
                if ($script) {
                    $contextParts[] = "[Audio Script for '$topic':\n$script\n]";
                }
            }
            elseif ($job->tool_type === 'presentation') {
                $presentationId = $job->results['presentation_id'] ?? null;
                if ($presentationId) {
                    $presentation = Presentation::find($presentationId);
                    if ($presentation) {
                        $content = $presentation->content;
                        if (is_string($content)) {
                            $content = json_decode($content, true);
                        }

                        if (is_array($content)) {
                            $slidesText = "";
                            foreach ($content as $index => $slide) {
                                $title = $slide['title'] ?? '';
                                $bullets = implode(', ', $slide['bullets'] ?? []);
                                $slidesText .= "Slide " . ($index + 1) . ": $title ($bullets)\n";
                            }
                            $presentationTopic = $presentation->topic ?? $topic;
                            $contextParts[] = "[Presentation Slides for '$presentationTopic':\n$slidesText\n]";
                        }
                    }
                }
            }
        }

        return implode("\n\n", $contextParts);
    }
}
