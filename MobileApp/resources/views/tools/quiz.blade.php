@extends('layouts.mobile', ['title' => 'Quiz Generator'])

@section('content')
<div x-data="{
        topic: '', count: 10, submitting: false, error: '',
        quizId: null, polling: false, status: '',
        ready: false, quiz: null, isPublic: false, shareUrl: '',
        sharing: false, copied: false,

        async submit() {
            this.error = '';
            if (this.topic.trim().length < 10) {
                this.error = 'Please enter at least 10 characters.'; return;
            }
            this.submitting = true;
            try {
                const res = await Api.post('/quiz', {
                    text: this.topic,
                    max_questions: parseInt(this.count) || 10,
                    title: this.topic.slice(0, 80) + ' Quiz',
                });
                this.quizId = res.quiz_id;
                this.status = res.status || 'pending';
                this.submitting = false;
                this.polling = true;
                this.ready = false;
                this.pollStatus();
            } catch(e) {
                this.error = e.message || 'Failed to generate quiz.';
                this.submitting = false;
            }
        },

        async pollStatus() {
            if (!this.quizId) return;
            try {
                const res = await Api.get('/quiz/' + this.quizId + '/status');
                this.status = res.status;
                if (res.status === 'done') {
                    this.polling = false;
                    this.ready = true;
                    await this.loadQuiz();
                } else if (res.status === 'failed') {
                    this.error = res.error_message || 'Quiz generation failed.';
                    this.polling = false;
                } else {
                    setTimeout(() => this.pollStatus(), 3000);
                }
            } catch(e) {
                this.error = e.message || 'Polling error.';
                this.polling = false;
            }
        },

        async loadQuiz() {
            if (!this.quizId) return;
            const res = await Api.get('/quiz/' + this.quizId);
            this.quiz = res.data || res;
            this.isPublic = !!this.quiz.is_public;
            this.shareUrl = this.quiz.share_url || '';
        },

        async toggleShare() {
            if (!this.quizId || this.sharing) return;
            this.error = '';
            this.sharing = true;
            try {
                const res = await Api.post('/quiz/' + this.quizId + '/toggle-share');
                this.isPublic = !!res.is_public;
                this.shareUrl = res.share_url || '';
                if (this.quiz) {
                    this.quiz.is_public = this.isPublic;
                    this.quiz.share_url = this.shareUrl;
                }
            } catch(e) {
                this.error = e.message || 'Could not update share setting.';
            } finally {
                this.sharing = false;
            }
        },

        async copyShare() {
            if (!this.shareUrl) return;
            try {
                await navigator.clipboard.writeText(this.shareUrl);
            } catch(e) {
                const input = document.createElement('textarea');
                input.value = this.shareUrl;
                input.style.position = 'fixed';
                input.style.opacity = '0';
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                input.remove();
            }
            this.copied = true;
            setTimeout(() => this.copied = false, 1800);
        },

        async nativeShare() {
            if (!this.shareUrl) return;
            if (navigator.share) {
                await navigator.share({ title: this.quiz?.title || 'EduAI Quiz', url: this.shareUrl });
            } else {
                await this.copyShare();
            }
        }
     }">

    <div class="page-header">
        <a href="/tools" class="back-btn">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1>🧠 Quiz Generator</h1>
    </div>

    <div class="p-4 md:p-8 flex flex-col gap-5 max-w-2xl mx-auto w-full">

        <div class="error-message" x-show="error" x-text="error" style="display:none;"></div>

        {{-- Polling State --}}
        <div x-show="polling" class="card text-center py-10" style="display:none;">
            <div class="spinner spinner-lg spinner-accent mx-auto mb-4"></div>
            <p class="font-semibold text-white text-base">Generating your quiz…</p>
            <p class="text-sm text-white/40 mt-2">This takes about 30–60 seconds</p>
            <div class="mt-6 progress-track mx-auto w-48">
                <div class="progress-fill" style="width:65%;"></div>
            </div>
        </div>

        {{-- Ready State --}}
        <div x-show="ready" class="card card-accent text-center py-9 flex flex-col gap-4" style="display:none;">
            <div>
                <p class="font-bold text-2xl text-white">Quiz Ready</p>
                <p class="text-sm text-brand-300 mt-2" x-text="(quiz?.questions?.length || quiz?.max_questions || count) + ' questions generated'"></p>
            </div>

            <a :href="'/quiz/' + quizId + '/play'" class="btn btn-primary btn-full">Take Quiz in App</a>

            <div class="grid grid-cols-2 gap-3">
                <button class="btn btn-ghost text-[13px]" @click="toggleShare()" :disabled="sharing">
                    <span x-text="sharing ? 'Updating...' : (isPublic ? 'Make Private' : 'Make Public')"></span>
                </button>
                <button class="btn btn-ghost text-[13px]" @click="nativeShare()" :disabled="!shareUrl">
                    Share Link
                </button>
            </div>

            <div x-show="shareUrl" class="rounded-xl bg-dark-50 border border-white/[0.07] p-3 text-left" style="display:none;">
                <p class="text-[10px] uppercase tracking-widest text-white/35 mb-1">Public link</p>
                <button class="w-full text-left text-xs text-white/70 break-all" @click="copyShare()" x-text="copied ? 'Copied!' : shareUrl"></button>
            </div>

            <button class="btn btn-ghost btn-full" @click="ready=false; quiz=null; quizId=null; topic=''; status=''">Create Another Quiz</button>
        </div>

        {{-- Form --}}
        <div x-show="!polling && !ready" class="flex flex-col gap-4">

            <div class="flex flex-col gap-1.5">
                <label>Topic or Text *</label>
                <textarea placeholder="e.g. The French Revolution, or paste a paragraph of text you want quizzed on…"
                          x-model="topic" rows="5" style="min-height:130px;"></textarea>
                <p class="text-xs text-white/25 mt-0.5" x-text="topic.length + ' characters (min 10)'"></p>
            </div>

            <div class="flex flex-col gap-1.5">
                <label>Number of Questions</label>
                <select x-model="count">
                    <option value="5">5 questions</option>
                    <option value="10" selected>10 questions</option>
                    <option value="15">15 questions</option>
                    <option value="20">20 questions</option>
                </select>
            </div>

            <button class="btn btn-primary btn-full" @click="submit()" :disabled="submitting || topic.trim().length < 10">
                <div class="spinner spinner-sm" x-show="submitting" style="display:none;"></div>
                <span x-text="submitting ? 'Starting…' : '✨ Generate Quiz'">✨ Generate Quiz</span>
            </button>

            <div class="flex items-start gap-3 px-4 py-3 rounded-2xl"
                 style="background:rgba(124,58,237,0.08);border:1px solid rgba(124,58,237,0.2);">
                <span class="text-lg mt-0.5">💡</span>
                <p class="text-sm text-white/50 leading-relaxed">When it is ready, you can take it inside the app or make a public share link.</p>
            </div>

        </div>

    </div>
</div>
@endsection
