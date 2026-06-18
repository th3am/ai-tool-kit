<x-layouts.app :title="$quiz->title">
    <div class="px-4 lg:px-[2%] py-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
            <div>
                <a href="{{ route('quiz.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-indigo-400 hover:text-indigo-300 mb-3">
                    <i class="fa-solid fa-arrow-left"></i> My Quizzes
                </a>
                <h1 class="text-3xl font-bold text-white">{{ $quiz->title }}</h1>
                <div class="flex flex-wrap gap-3 mt-3 text-sm text-gray-400">
                    <span class="rounded-full border px-3 py-1 {{ $quiz->status === 'done' ? 'border-emerald-500/25 bg-emerald-500/15 text-emerald-300' : ($quiz->status === 'failed' ? 'border-red-500/25 bg-red-500/15 text-red-300' : 'border-sky-500/25 bg-sky-500/15 text-sky-300') }}">{{ ucfirst($quiz->status) }}</span>
                    <span><i class="fa-regular fa-file-lines mr-1"></i>{{ ucfirst($quiz->source_type) }}</span>
                    <span><i class="fa-regular fa-circle-question mr-1"></i>{{ $quiz->questions->count() }} Questions</span>
                    <span><i class="fa-regular fa-clock mr-1"></i>{{ $quiz->created_at->diffForHumans() }}</span>
                </div>
            </div>
            <a href="{{ route('quiz.create') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3 font-bold text-white hover:bg-indigo-500 transition">
                <i class="fa-solid fa-plus"></i> New Quiz
            </a>
        </div>

        @if(session('success'))
            <div class="mb-5 rounded-xl border border-emerald-500/25 bg-emerald-500/10 px-4 py-3 text-emerald-300">
                {{ session('success') }}
            </div>
        @endif

        @if($quiz->status === 'pending' || $quiz->status === 'processing')
            <div class="rounded-3xl border border-white/10 bg-white/5 p-10 text-center shadow-lg" id="processingCard">
                <div class="w-16 h-16 rounded-full border-4 border-indigo-500/30 border-t-indigo-400 animate-spin mx-auto mb-6"></div>
                <h2 class="text-2xl font-bold text-white mb-2">Generating your quiz...</h2>
                <p class="text-gray-400">The AI is analyzing your content and creating questions. This page updates automatically.</p>
            </div>

            <script>
                setInterval(function() {
                    fetch('{{ route('quiz.status', $quiz) }}')
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'done' || data.status === 'failed') {
                                window.location.reload();
                            }
                        });
                }, 5000);
            </script>
        @elseif($quiz->status === 'failed')
            <div class="rounded-3xl border border-red-500/25 bg-red-500/10 p-10 text-center shadow-lg">
                <i class="fa-solid fa-triangle-exclamation text-5xl text-red-300 mb-5"></i>
                <h2 class="text-2xl font-bold text-white mb-2">Generation Failed</h2>
                <p class="text-red-200">{{ $quiz->error_message }}</p>
            </div>
        @else
            <div class="grid lg:grid-cols-[minmax(0,1fr)_420px] gap-6">
                <section class="rounded-3xl border border-white/10 bg-white/5 backdrop-blur-3xl p-6 shadow-lg">
                    <div class="flex items-center justify-between gap-3 mb-6">
                        <div>
                            <h2 class="text-2xl font-bold text-white">Quiz Questions</h2>
                            <p class="text-gray-400">Review the generated quiz before sharing.</p>
                        </div>
                        @if($quiz->is_public)
                            <a href="{{ $quiz->share_url }}" target="_blank" class="rounded-xl bg-pink-500/20 px-4 py-2 text-sm font-bold text-pink-200 hover:bg-pink-500/30 transition">
                                Preview public
                            </a>
                        @endif
                    </div>

                    <div class="space-y-4">
                        @foreach($quiz->questions as $question)
                            <article class="rounded-2xl border border-white/10 bg-black/20 p-5">
                                <div class="text-xs font-bold uppercase tracking-wide text-pink-300 mb-2">Question {{ $question->order }}</div>
                                <h3 class="text-lg font-semibold text-white mb-4">{{ $question->question_text }}</h3>
                                <div class="grid sm:grid-cols-2 gap-3">
                                    @foreach($question->getAllOptionsShuffled() as $option)
                                        <div class="rounded-xl border px-4 py-3 text-sm {{ strtolower(trim($option)) === strtolower(trim($question->correct_answer)) ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-200' : 'border-white/10 bg-white/5 text-gray-300' }}">
                                            {{ ucfirst($option) }}
                                        </div>
                                    @endforeach
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>

                <aside class="space-y-5">
                    <div class="rounded-3xl border border-white/10 bg-white/5 backdrop-blur-3xl p-6 shadow-lg">
                        <h3 class="flex items-center gap-2 font-bold text-xl text-white mb-4">
                            <span class="w-3 h-3 rounded bg-violet-400"></span>
                            Share Quiz
                        </h3>
                        @if($quiz->is_public)
                            <p class="text-gray-400 mb-4">Public sharing is enabled. Send this link to anyone.</p>
                            <div class="flex gap-2">
                                <input id="shareUrl" value="{{ $quiz->share_url }}" readonly class="min-w-0 flex-1 rounded-xl bg-black/25 border border-white/10 px-3 py-3 text-sm text-indigo-200">
                                <button type="button" onclick="copyQuizLink()" class="rounded-xl bg-indigo-600 px-4 py-3 font-bold text-white hover:bg-indigo-500 transition">
                                    Copy
                                </button>
                            </div>
                            <form method="POST" action="{{ route('quiz.toggle-share', $quiz) }}" class="mt-4">
                                @csrf
                                <button class="w-full rounded-xl border border-red-500/25 bg-red-500/10 px-4 py-3 font-bold text-red-200 hover:bg-red-500/20 transition">Make Private</button>
                            </form>
                        @else
                            <p class="text-gray-400 mb-4">Enable sharing to create a public quiz link.</p>
                            <form method="POST" action="{{ route('quiz.toggle-share', $quiz) }}">
                                @csrf
                                <button class="w-full rounded-xl bg-emerald-600 px-4 py-3 font-bold text-white hover:bg-emerald-500 transition">Enable Public Link</button>
                            </form>
                        @endif
                    </div>

                    <div class="rounded-3xl border border-white/10 bg-white/5 backdrop-blur-3xl p-6 shadow-lg">
                        <h3 class="font-bold text-xl text-white mb-4">Recent Attempts</h3>
                        <div class="space-y-3">
                            @forelse($quiz->attempts as $attempt)
                                <div class="rounded-2xl border border-white/10 bg-black/20 p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="font-semibold text-white truncate">{{ $attempt->participant_name ?? $attempt->user?->name ?? 'Anonymous' }}</span>
                                        <span class="rounded-full bg-indigo-500/15 px-3 py-1 text-xs font-bold text-indigo-200">{{ $attempt->score }}/{{ $attempt->total }}</span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-2">{{ $attempt->completed_at?->diffForHumans() ?? 'Just now' }}</p>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500">No attempts yet.</p>
                            @endforelse
                        </div>
                    </div>

                    <form method="POST" action="{{ route('quiz.destroy', $quiz) }}" onsubmit="return confirm('Delete this quiz permanently?')">
                        @csrf
                        @method('DELETE')
                        <button class="w-full rounded-2xl border border-red-500/25 bg-red-500/10 px-5 py-4 font-bold text-red-200 hover:bg-red-500/20 transition">
                            Delete Quiz
                        </button>
                    </form>
                </aside>
            </div>
        @endif
    </div>

    <script>
        function copyQuizLink() {
            const input = document.getElementById('shareUrl');
            input.select();
            navigator.clipboard?.writeText(input.value).catch(() => document.execCommand('copy'));
        }
    </script>
</x-layouts.app>
