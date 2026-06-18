<x-layouts.app title="Create Quiz">
    <div class="px-4 lg:px-[2%] py-6" x-data="quizCreator()">
        <div class="flex items-center justify-center gap-4 py-4">
            <div class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-full bg-[#6366F1] text-white flex items-center justify-center text-sm font-bold">1</span>
                <span class="text-sm font-semibold text-white">Quiz Configuration</span>
            </div>
            <div class="hidden sm:block w-14 h-[2px] bg-white/10"></div>
            <div class="flex items-center gap-2 opacity-60">
                <span class="w-8 h-8 rounded-full bg-white/5 text-gray-300 flex items-center justify-center text-sm font-bold">2</span>
                <span class="text-sm font-semibold text-gray-400">Generate & Share</span>
            </div>
        </div>

        <form method="POST" action="{{ route('quiz.store') }}" enctype="multipart/form-data" x-ref="form" @submit.prevent="submit">
            @csrf
            <div class="grid lg:grid-cols-[minmax(0,1fr)_420px] gap-6">
                <section class="rounded-2xl border border-white/10 bg-white/5 backdrop-blur-3xl p-6 shadow-lg">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 rounded-2xl bg-pink-500/25 flex items-center justify-center">
                            <i class="fa-solid fa-circle-question text-pink-300 text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-white">Quiz Generator Setup</h2>
                            <p class="text-gray-400">Generate smart MCQ questions from text or a document.</p>
                        </div>
                    </div>

                    @if($errors->any())
                        <div class="mb-5 rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-300">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <template x-if="error">
                        <div class="mb-5 rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-300" x-text="error"></div>
                    </template>

                    <div class="grid md:grid-cols-2 gap-4 mb-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Quiz Title</label>
                            <input name="title" value="{{ old('title') }}" placeholder="Chapter 3 Review" class="w-full rounded-xl bg-white/5 border border-white/10 px-4 py-3 text-white placeholder-gray-500 outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Questions</label>
                            <select name="max_questions" class="w-full rounded-xl bg-[#12152a] border border-white/10 px-4 py-3 text-white outline-none focus:border-indigo-500">
                                @foreach([3,5,8,10,15,20] as $num)
                                    <option value="{{ $num }}" @selected(old('max_questions', 5) == $num)>{{ $num }} Questions</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex rounded-xl bg-black/25 p-1 mb-5">
                        <button type="button" @click="mode = 'text'" :class="mode === 'text' ? 'bg-indigo-600 text-white' : 'text-gray-400'" class="flex-1 rounded-lg py-3 font-semibold transition">
                            <i class="fa-solid fa-keyboard mr-2"></i>Enter Text
                        </button>
                        <button type="button" @click="mode = 'file'" :class="mode === 'file' ? 'bg-indigo-600 text-white' : 'text-gray-400'" class="flex-1 rounded-lg py-3 font-semibold transition">
                            <i class="fa-solid fa-file-arrow-up mr-2"></i>Upload File
                        </button>
                    </div>

                    <div x-show="mode === 'text'">
                        <label class="block text-sm font-semibold text-gray-300 mb-2">Source Text</label>
                        <textarea name="text" rows="10" placeholder="Paste your lesson, notes, or article here..." class="w-full rounded-2xl bg-white/5 border border-white/10 px-4 py-4 text-white placeholder-gray-500 outline-none focus:border-indigo-500 resize-y">{{ old('text') }}</textarea>
                    </div>

                    <div x-show="mode === 'file'" x-cloak>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">Source File</label>
                        <label class="relative flex flex-col items-center justify-center min-h-72 rounded-2xl border-2 border-dashed border-indigo-500/40 bg-indigo-500/5 hover:bg-indigo-500/10 transition cursor-pointer">
                            <input type="file" name="file" class="absolute inset-0 opacity-0 cursor-pointer" accept=".pdf,.doc,.docx,.pptx" @change="fileName = $event.target.files[0]?.name || ''">
                            <i class="fa-solid fa-cloud-arrow-up text-5xl text-indigo-300 mb-4"></i>
                            <span class="font-bold text-indigo-200">Click to upload or drag and drop</span>
                            <span class="text-sm text-gray-500 mt-2">PDF, DOC, DOCX, PPTX up to 20MB</span>
                            <span class="mt-4 rounded-full bg-indigo-500/20 px-4 py-2 text-sm text-indigo-200" x-show="fileName" x-text="fileName"></span>
                        </label>
                    </div>
                </section>

                <aside class="space-y-5">
                    <div class="rounded-2xl border border-white/10 bg-white/5 backdrop-blur-3xl p-6 shadow-lg">
                        <h3 class="flex items-center gap-2 font-bold text-xl text-white mb-4">
                            <span class="w-3 h-3 rounded bg-violet-400"></span>
                            Job Summary
                        </h3>
                        <div class="space-y-3 text-gray-400">
                            <p><span class="font-semibold text-gray-300">Tool:</span> Quiz Generator</p>
                            <p><span class="font-semibold text-gray-300">Credits:</span> 10</p>
                            <p><span class="font-semibold text-gray-300">Est. Time:</span> 2-3 min</p>
                        </div>
                    </div>

                    <button type="submit" :disabled="loading" class="w-full rounded-2xl bg-gradient-to-r from-pink-500 to-indigo-500 px-6 py-4 font-bold text-white shadow-lg hover:shadow-xl disabled:opacity-60 transition">
                        <span x-show="!loading"><i class="fa-solid fa-wand-magic-sparkles mr-2"></i>Generate Quiz</span>
                        <span x-show="loading"><i class="fa-solid fa-spinner fa-spin mr-2"></i>Generating...</span>
                    </button>

                    <a href="{{ route('quiz.index') }}" class="flex justify-center rounded-2xl border border-white/10 px-6 py-4 font-semibold text-gray-300 hover:bg-white/5 transition">My Quizzes</a>
                </aside>
            </div>
        </form>

        <div x-show="loading" x-cloak class="fixed inset-0 z-[1200] bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="w-full max-w-xl rounded-3xl border border-white/10 bg-[#12152a] p-8 text-center shadow-2xl">
                <div class="w-16 h-16 rounded-full border-4 border-indigo-500/30 border-t-indigo-400 animate-spin mx-auto mb-6"></div>
                <h3 class="text-2xl font-bold text-white mb-2">Generating your quiz...</h3>
                <p class="text-gray-400" x-text="modalMessage"></p>
            </div>
        </div>
    </div>

    <script>
        function quizCreator() {
            return {
                mode: 'text',
                fileName: '',
                loading: false,
                error: '',
                modalMessage: 'The AI is creating questions from your content.',
                submit() {
                    this.error = '';
                    this.loading = true;
                    const data = new FormData(this.$refs.form);
                    if (this.mode === 'text') {
                        data.delete('file');
                    } else {
                        data.set('text', '');
                    }

                    fetch(this.$refs.form.action, {
                        method: 'POST',
                        body: data,
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    })
                    .then(async response => {
                        const payload = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            throw new Error(payload.message || Object.values(payload.errors || {})[0]?.[0] || 'Unable to create quiz.');
                        }
                        return payload;
                    })
                    .then(payload => this.poll(payload.status_url, payload.redirect_url))
                    .catch(error => {
                        this.loading = false;
                        this.error = error.message;
                    });
                },
                poll(statusUrl, redirectUrl) {
                    const timer = setInterval(() => {
                        fetch(statusUrl, { headers: { 'Accept': 'application/json' } })
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'done' || data.status === 'failed') {
                                    clearInterval(timer);
                                    window.location.href = redirectUrl;
                                }
                            });
                    }, 2500);
                }
            }
        }
    </script>
</x-layouts.app>
