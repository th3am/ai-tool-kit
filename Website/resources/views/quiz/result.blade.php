<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Result - {{ $quiz->title }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: { extend: { fontFamily: { sans: ['Tajawal', 'sans-serif'] } } }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="min-h-screen bg-[#060b21] font-sans text-white">
    <main class="max-w-5xl mx-auto px-4 py-10">
        <section class="rounded-3xl border border-white/10 bg-white/5 backdrop-blur-3xl p-8 shadow-2xl text-center mb-6">
            <div class="w-36 h-36 rounded-full mx-auto mb-6 p-2" style="background: conic-gradient({{ $percentage >= 70 ? '#34d399' : ($percentage >= 40 ? '#fbbf24' : '#f87171') }} {{ $percentage }}%, rgba(255,255,255,.1) 0)">
                <div class="w-full h-full rounded-full bg-[#060b21] flex flex-col items-center justify-center">
                    <span class="text-4xl font-extrabold">{{ $percentage }}%</span>
                    <span class="text-sm text-gray-400">Score</span>
                </div>
            </div>

            <h1 class="text-3xl font-extrabold mb-2">
                @if($percentage >= 80) Excellent Work!
                @elseif($percentage >= 60) Good Job!
                @elseif($percentage >= 40) Not Bad!
                @else Keep Practicing!
                @endif
            </h1>
            <p class="text-gray-400">You completed <span class="font-semibold text-white">{{ $quiz->title }}</span>.</p>

            <div class="grid grid-cols-3 gap-3 mt-8">
                <div class="rounded-2xl border border-emerald-500/25 bg-emerald-500/10 p-4">
                    <div class="text-3xl font-extrabold text-emerald-300">{{ $score }}</div>
                    <div class="text-sm text-gray-400">Correct</div>
                </div>
                <div class="rounded-2xl border border-red-500/25 bg-red-500/10 p-4">
                    <div class="text-3xl font-extrabold text-red-300">{{ $attempt->total - $score }}</div>
                    <div class="text-sm text-gray-400">Wrong</div>
                </div>
                <div class="rounded-2xl border border-indigo-500/25 bg-indigo-500/10 p-4">
                    <div class="text-3xl font-extrabold text-indigo-300">{{ $attempt->total }}</div>
                    <div class="text-sm text-gray-400">Total</div>
                </div>
            </div>
        </section>

        <section class="rounded-3xl border border-white/10 bg-white/5 backdrop-blur-3xl p-6 shadow-lg">
            <h2 class="text-2xl font-bold mb-5">Detailed Results</h2>
            <div class="space-y-4">
                @foreach($results as $result)
                    <article class="rounded-2xl border {{ $result['is_correct'] ? 'border-emerald-500/25 bg-emerald-500/10' : 'border-red-500/25 bg-red-500/10' }} p-5">
                        <h3 class="font-semibold leading-relaxed mb-3">{{ $result['question'] }}</h3>
                        <p class="text-sm {{ $result['is_correct'] ? 'text-emerald-200' : 'text-red-200' }}">
                            <i class="fa-solid {{ $result['is_correct'] ? 'fa-circle-check' : 'fa-circle-xmark' }} mr-2"></i>
                            Your answer: {{ ucfirst($result['your_answer']) }}
                        </p>
                        @if(!$result['is_correct'])
                            <p class="text-sm text-emerald-200 mt-2">
                                <i class="fa-solid fa-circle-check mr-2"></i>
                                Correct answer: {{ ucfirst($result['correct_answer']) }}
                            </p>
                        @endif
                    </article>
                @endforeach
            </div>

            <div class="grid sm:grid-cols-2 gap-3 mt-6">
                <a href="{{ route('quiz.public.show', $quiz->share_uuid) }}" class="rounded-2xl border border-white/10 px-6 py-4 text-center font-bold text-gray-200 hover:bg-white/5 transition">
                    Try Again
                </a>
                <button onclick="shareResult()" class="rounded-2xl bg-gradient-to-r from-pink-500 to-indigo-500 px-6 py-4 font-bold text-white hover:opacity-90 transition">
                    Share Result
                </button>
            </div>
        </section>
    </main>

    <script>
        function shareResult() {
            const text = `I scored {{ $percentage }}% on "{{ addslashes($quiz->title) }}"! Try it: {{ $quiz->share_url }}`;
            if (navigator.share) {
                navigator.share({ title: 'Quiz Result', text, url: '{{ $quiz->share_url }}' });
            } else {
                navigator.clipboard.writeText(text).then(() => alert('Result copied.'));
            }
        }
    </script>
</body>
</html>
