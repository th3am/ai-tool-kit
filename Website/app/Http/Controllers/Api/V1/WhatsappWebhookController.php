<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateAnimationJob;
use App\Jobs\GenerateAudioJob;
use App\Jobs\GenerateMindMapJob;
use App\Jobs\GeneratePresentationJob;
use App\Jobs\GenerateQuizJob;
use App\Jobs\GenerateVideoExplainerJob;
use App\Jobs\SendWhatsappToolResultJob;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Quiz;
use App\Models\ToolJob;
use App\Models\User;
use App\Services\CreditService;
use App\Services\Whatsapp\MetaphiliaClient;
use App\Services\Whatsapp\WhatsappIntentDetector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookController extends Controller
{
    public function __construct(
        protected MetaphiliaClient $metaphilia,
        protected WhatsappIntentDetector $intentDetector,
        protected CreditService $credits
    ) {}

    public function handle(Request $request): JsonResponse
    {
        if (! $this->validWebhookSecret($request)) {
            return response()->json(['message' => 'Invalid webhook secret.'], 403);
        }

        $payload = $request->all();
        Log::info('Metaphilia WhatsApp webhook received.', [
            'from' => $payload['from'] ?? null,
            'participant' => $payload['participant'] ?? null,
            'has_message' => trim((string) ($payload['message'] ?? '')) !== '',
            'has_image' => ! empty($payload['bufferImage']),
        ]);

        $message = trim((string) ($payload['message'] ?? ''));
        $from = $this->metaphilia->normalizeNumber((string) ($payload['from'] ?? ''));
        $participant = (string) ($payload['participant'] ?? '');

        if ($this->isGroupOrEmpty($from, $participant, $message)) {
            return response()->json(['ok' => true, 'ignored' => true]);
        }

        $user = $this->findUserByWhatsapp($from);

        if (! $user) {
            $this->metaphilia->sendMessage(
                $from,
                "I couldn't find an EduAI account for this WhatsApp number.\nPlease register using this number first, then message me again."
            );

            return response()->json(['ok' => true, 'registered' => false]);
        }

        $intent = $this->intentDetector->detect($message);

        if ($intent['intent'] !== 'tool_request' || ! $intent['tool']) {
            $this->metaphilia->sendMessage($from, $this->infoReply($user, $intent['reply']));
            return response()->json(['ok' => true, 'intent' => $intent['intent']]);
        }

        $tool = $intent['tool'];
        $topic = $this->cleanTopic($intent['topic'] ?: $message);
        $cost = $this->credits->costFor($tool);

        if (! $this->credits->check($user, $tool)) {
            $this->metaphilia->sendMessage(
                $from,
                "You do not have enough credits for {$tool}.\nRequired: {$cost}\nYour credits: {$user->fresh()->credits}"
            );

            return response()->json(['ok' => true, 'insufficient_credits' => true]);
        }

        try {
            $session = ChatSession::create([
                'user_id' => $user->id,
                'title' => 'WhatsApp: '.$this->shortTitle($topic),
                'context_type' => 'whatsapp',
                'context_id' => $from,
            ]);

            ChatMessage::create([
                'session_id' => $session->id,
                'role' => 'user',
                'content' => $message,
                'meta_data' => [
                    'source' => 'whatsapp',
                    'from' => $from,
                    'name' => $payload['name'] ?? null,
                ],
            ]);

            $job = $this->startTool($user, $session, $from, $tool, $topic, $intent);

            $this->metaphilia->sendMessage(
                $from,
                "Started your {$this->toolLabel($tool)} job.\nTopic: {$topic}\nCost: {$cost} credits\nI will send the result here when it is ready."
            );

            SendWhatsappToolResultJob::dispatch($job->id, $from)->delay(now()->addSeconds(20));

            return response()->json([
                'ok' => true,
                'job_id' => $job->id,
                'tool' => $tool,
            ]);
        } catch (\Throwable $e) {
            Log::error('WhatsApp webhook tool start failed.', [
                'from' => $from,
                'tool' => $tool,
                'message' => $e->getMessage(),
            ]);

            $this->metaphilia->sendMessage($from, 'Sorry, I could not start this request: '.$e->getMessage());

            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    private function startTool(User $user, ChatSession $session, string $from, string $tool, string $topic, array $intent): ToolJob
    {
        if (! $this->credits->deduct($user, $tool)) {
            throw new \RuntimeException('Insufficient credits.');
        }

        return match ($tool) {
            'presentation' => $this->startPresentation($user, $session, $from, $topic, $intent),
            'mindmap' => $this->startMindmap($user, $session, $from, $topic),
            'audio' => $this->startAudio($user, $session, $from, $topic),
            'video-animation' => $this->startAnimation($user, $session, $from, $topic),
            'video-explainer' => $this->startVideoExplainer($user, $session, $from, $topic, $intent),
            'quiz' => $this->startQuiz($user, $session, $from, $topic, $intent),
            default => throw new \InvalidArgumentException('Unsupported tool.'),
        };
    }

    private function startPresentation(User $user, ChatSession $session, string $from, string $topic, array $intent): ToolJob
    {
        $slideCount = max(3, min(5, (int) ($intent['slide_count'] ?? 5)));
        $instructions = '[WHATSAPP_FAST] Generated from WhatsApp request. Keep the deck concise and easy to send back as PDF and PowerPoint.';
        $job = $this->createToolJob($user, $session, $from, 'presentation', [
            'topic' => $topic,
            'style' => 'Modern',
            'slide_count' => $slideCount,
            'instructions' => $instructions,
        ]);

        GeneratePresentationJob::dispatch($user->id, $session->id, $job->id, $topic, 'Modern', $slideCount, $instructions);

        return $job;
    }

    private function startMindmap(User $user, ChatSession $session, string $from, string $topic): ToolJob
    {
        $job = $this->createToolJob($user, $session, $from, 'mindmap', ['topic' => $topic]);
        GenerateMindMapJob::dispatch($session->id, $job->id, $topic);

        return $job;
    }

    private function startAudio(User $user, ChatSession $session, string $from, string $topic): ToolJob
    {
        $job = $this->createToolJob($user, $session, $from, 'audio', [
            'inputType' => 'text',
            'text' => $topic,
        ]);

        GenerateAudioJob::dispatch($user->id, $session->id, $job->id, $topic, 'text');

        return $job;
    }

    private function startAnimation(User $user, ChatSession $session, string $from, string $topic): ToolJob
    {
        $job = $this->createToolJob($user, $session, $from, 'video-animation', [
            'prompt' => $topic,
            'credits_charged' => true,
        ]);
        GenerateAnimationJob::dispatch($user->id, $session->id, $job->id, $topic);

        return $job;
    }

    private function startVideoExplainer(User $user, ChatSession $session, string $from, string $topic, array $intent): ToolJob
    {
        $slideCount = max(3, min(10, (int) ($intent['slide_count'] ?? 5)));
        $language = in_array($intent['language'] ?? null, ['ar', 'en'], true) ? $intent['language'] : 'ar';
        $job = $this->createToolJob($user, $session, $from, 'video-explainer', [
            'topic' => $topic,
            'style' => 'Modern',
            'slide_count' => $slideCount,
            'instructions' => 'Generated from WhatsApp request.',
            'language' => $language,
            'enable_captions' => true,
        ]);

        GenerateVideoExplainerJob::dispatch($user->id, $session->id, $job->id, $topic, 'Modern', $slideCount, 'Generated from WhatsApp request.', $language, true);

        return $job;
    }

    private function startQuiz(User $user, ChatSession $session, string $from, string $topic, array $intent): ToolJob
    {
        $questionCount = max(3, min(20, (int) ($intent['question_count'] ?? 5)));

        $quiz = Quiz::create([
            'user_id' => $user->id,
            'title' => $this->shortTitle($topic).' Quiz',
            'source_type' => 'text',
            'source_text' => $this->quizSourceText($topic),
            'max_questions' => $questionCount,
            'status' => 'pending',
            'is_public' => true,
        ]);

        $job = $this->createToolJob($user, $session, $from, 'quiz', [
            'topic' => $topic,
            'max_questions' => $questionCount,
        ]);

        GenerateQuizJob::dispatch($quiz, $job->id, $session->id);

        return $job;
    }

    private function createToolJob(User $user, ChatSession $session, string $from, string $tool, array $params): ToolJob
    {
        return ToolJob::create([
            'user_id' => $user->id,
            'chat_session_id' => $session->id,
            'tool_type' => $tool,
            'status' => 'queued',
            'params' => [
                ...$params,
                'whatsapp' => [
                    'number' => $from,
                    'source' => 'metaphilia',
                ],
            ],
        ]);
    }

    private function findUserByWhatsapp(string $from): ?User
    {
        if ($from === '') {
            return null;
        }

        $direct = User::where('whatsapp_number', $from)
            ->orWhere('whatsapp_number', '+'.$from)
            ->first();

        if ($direct) {
            return $direct;
        }

        return User::whereNotNull('whatsapp_number')
            ->get()
            ->first(fn (User $user) => $this->numbersMatch($this->metaphilia->normalizeNumber($user->whatsapp_number), $from));
    }

    private function validWebhookSecret(Request $request): bool
    {
        $secret = trim((string) config('services.metaphilia.webhook_secret'));

        if ($secret === '') {
            return true;
        }

        return hash_equals($secret, (string) $request->query('secret', $request->header('X-Metaphilia-Secret', '')));
    }

    private function numbersMatch(string $registered, string $incoming): bool
    {
        if ($registered === '' || $incoming === '') {
            return false;
        }

        if ($registered === $incoming) {
            return true;
        }

        return strlen($registered) >= 10
            && strlen($incoming) >= 10
            && substr($registered, -10) === substr($incoming, -10);
    }

    private function isGroupOrEmpty(string $from, string $participant, string $message): bool
    {
        return $message === ''
            || $from === ''
            || str_ends_with($participant, '@g.us')
            || str_contains($participant, '@g.us');
    }

    private function infoReply(User $user, string $reply): string
    {
        $costs = collect($this->credits->allCosts())
            ->map(fn ($cost, $tool) => "{$tool}: {$cost}")
            ->implode("\n");

        return trim($reply)."\n\nYour credits: {$user->fresh()->credits}\nAvailable tools:\n{$costs}";
    }

    private function cleanTopic(string $topic): string
    {
        $topic = trim(preg_replace('/\s+/', ' ', $topic));

        if (mb_strlen($topic) < 5) {
            throw new \InvalidArgumentException('Please send more details about what you want to generate.');
        }

        return mb_substr($topic, 0, 2000);
    }

    private function shortTitle(string $topic): string
    {
        return mb_substr($topic, 0, 60);
    }

    private function toolLabel(string $tool): string
    {
        return [
            'presentation' => 'presentation',
            'mindmap' => 'mind map',
            'audio' => 'audio narration',
            'video-animation' => '2D animation',
            'video-explainer' => 'video explainer',
            'quiz' => 'quiz',
        ][$tool] ?? $tool;
    }

    private function quizSourceText(string $topic): string
    {
        if (mb_strlen($topic) >= 120) {
            return $topic;
        }

        return "Create an educational quiz about {$topic}. Include core definitions, how it works, examples, advantages, limitations, and practical use cases.";
    }
}
