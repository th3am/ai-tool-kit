<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $quiz->title }} - EduAI Quiz</title>
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
    <div class="border-b border-white/10 bg-white/5 backdrop-blur-xl">
        <div class="max-w-7xl mx-auto px-4 py-5 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-pink-500 to-indigo-500 flex items-center justify-center">
                    <i class="fa-solid fa-circle-question"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold">EduAI Quiz</h1>
                    <p class="text-sm text-gray-400">Shared by {{ $quiz->user->name }}</p>
                </div>
            </div>
            <div class="hidden sm:flex items-center gap-4 text-sm text-gray-400">
                <span><i class="fa-regular fa-circle-question mr-1"></i>{{ $quiz->questions->count() }} Questions</span>
                <span><i class="fa-regular fa-user mr-1"></i>{{ $quiz->attempts()->count() }} Attempts</span>
            </div>
        </div>
    </div>

    <main class="max-w-7xl mx-auto px-4 py-8">
        <div class="grid lg:grid-cols-[minmax(0,1fr)_380px] gap-6">
            <form method="POST" action="{{ route('quiz.public.attempt', $quiz->share_uuid) }}" id="quizForm" class="rounded-3xl border border-white/10 bg-white/5 backdrop-blur-3xl p-6 shadow-2xl">
                @csrf
                <div class="mb-6">
                    <span class="inline-flex rounded-full border border-pink-500/25 bg-pink-500/15 px-3 py-1 text-xs font-bold text-pink-200 mb-3">AI-Generated Quiz</span>
                    <h2 class="text-3xl font-extrabold">{{ $quiz->title }}</h2>
                    <p class="text-gray-400 mt-2">Answer the questions below, then submit your score.</p>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-300 mb-2">Your Name</label>
                    <input type="text" name="participant_name" placeholder="Optional" class="w-full rounded-2xl bg-black/25 border border-white/10 px-4 py-3 text-white placeholder-gray-500 outline-none focus:border-indigo-500">
                </div>

                <div class="h-2 rounded-full bg-white/10 overflow-hidden mb-6">
                    <div id="progressFill" class="h-full w-0 rounded-full bg-gradient-to-r from-pink-500 to-indigo-500 transition-all"></div>
                </div>

                <div class="space-y-5">
                    @foreach($quiz->questions as $index => $question)
                        <article class="rounded-2xl border border-white/10 bg-black/20 p-5">
                            <div class="text-xs font-bold uppercase tracking-wide text-pink-300 mb-2">Question {{ $index + 1 }} of {{ $quiz->questions->count() }}</div>
                            <h3 class="text-lg font-semibold leading-relaxed mb-4">{{ $question->question_text }}</h3>
                            <div class="grid gap-3">
                                @foreach($question->getAllOptionsShuffled() as $option)
                                    <label class="group flex items-center gap-3 rounded-xl border border-white/10 bg-white/5 px-4 py-3 cursor-pointer hover:border-indigo-400/50 hover:bg-indigo-500/10 transition">
                                        <input type="radio" name="answers[{{ $question->id }}]" value="{{ $option }}" class="peer hidden" onchange="updateProgress()">
                                        <span class="w-5 h-5 rounded-full border-2 border-gray-500 peer-checked:border-indigo-400 peer-checked:bg-indigo-500 flex-shrink-0"></span>
                                        <span class="text-gray-200 peer-checked:text-white">{{ ucfirst($option) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="mt-8 text-center">
                    <p id="progressText" class="text-sm text-gray-400 mb-4">Answer all questions before submitting.</p>
                    <button id="submitBtn" class="rounded-2xl bg-gradient-to-r from-pink-500 to-indigo-500 px-10 py-4 font-extrabold text-white shadow-lg hover:shadow-xl transition">
                        <i class="fa-solid fa-bullseye mr-2"></i>Submit Quiz
                    </button>
                </div>
            </form>

            <aside class="space-y-5">
                <div class="rounded-3xl border border-white/10 bg-white/5 backdrop-blur-3xl p-6 shadow-lg sticky top-6">
                    <h3 class="flex items-center gap-2 font-bold text-xl mb-4">
                        <span class="w-3 h-3 rounded bg-violet-400"></span>
                        Quiz Summary
                    </h3>
                    <div class="space-y-3 text-gray-400">
                        <p><span class="font-semibold text-gray-300">Title:</span> {{ $quiz->title }}</p>
                        <p><span class="font-semibold text-gray-300">Questions:</span> {{ $quiz->questions->count() }}</p>
                        <p><span class="font-semibold text-gray-300">Attempts:</span> {{ $quiz->attempts()->count() }}</p>
                    </div>
                </div>
            </aside>
        </div>
    </main>

    <script>
        const total = {{ $quiz->questions->count() }};
        function updateProgress() {
            const answered = new Set([...document.querySelectorAll('input[type=radio]:checked')].map(input => input.name)).size;
            const pct = total > 0 ? Math.round((answered / total) * 100) : 0;
            document.getElementById('progressFill').style.width = pct + '%';
            document.getElementById('progressText').textContent = answered === total
                ? 'All questions answered. Ready to submit.'
                : `${answered} of ${total} questions answered.`;
        }

        document.getElementById('quizForm').addEventListener('submit', function() {
            document.getElementById('submitBtn').disabled = true;
            document.getElementById('submitBtn').innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Submitting...';
        });
    </script>
</body>
</html>
