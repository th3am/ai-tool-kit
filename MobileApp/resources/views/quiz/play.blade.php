@extends('layouts.mobile', ['title' => 'Take Quiz'])

@section('content')
<div x-data="quizPlayer(@js($quizId))" x-init="init()">

    <div class="page-header">
        <a href="/jobs" class="back-btn">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 class="truncate" x-text="quiz ? quiz.title || 'Quiz' : 'Quiz'">Quiz</h1>
        <span x-show="quiz && !done" class="text-xs font-semibold text-brand-400 bg-brand-600/10 px-2.5 py-1 rounded-full"
              x-text="(currentIndex + 1) + ' / ' + questions.length"></span>
    </div>

    <div class="p-4 flex flex-col gap-4" x-show="loading" style="display:none;">
        <div class="skeleton h-2 rounded-full"></div>
        <div class="skeleton h-24 rounded-2xl"></div>
        <div class="skeleton h-14 rounded-xl"></div>
        <div class="skeleton h-14 rounded-xl"></div>
        <div class="skeleton h-14 rounded-xl"></div>
    </div>

    <div class="error-state" x-show="error && !loading" style="display:none;">
        <span class="text-5xl">!</span>
        <h3>Failed to load quiz</h3>
        <p x-text="error"></p>
        <button class="btn btn-ghost btn-sm mt-2" @click="init()">Retry</button>
    </div>

    <div x-show="!loading && !error && !done && questions.length > 0" style="display:none;" class="p-4 flex flex-col gap-5">
        <div class="card flex flex-col gap-3" x-show="quiz">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-white" x-text="quiz?.is_public ? 'Public quiz' : 'Private quiz'"></p>
                    <p class="text-xs text-white/40" x-text="quiz?.is_public ? 'Anyone with the link can take it.' : 'Only you can access it.'"></p>
                </div>
                <button class="btn btn-ghost btn-sm" @click="toggleShare()" :disabled="sharing"
                        x-text="sharing ? 'Updating...' : (quiz?.is_public ? 'Make Private' : 'Make Public')"></button>
            </div>
            <div class="grid grid-cols-2 gap-3" x-show="quiz?.share_url" style="display:none;">
                <button class="btn btn-ghost btn-sm" @click="copyShare()" x-text="copied ? 'Copied!' : 'Copy Link'"></button>
                <button class="btn btn-ghost btn-sm" @click="nativeShare()">Share</button>
            </div>
        </div>

        <div class="progress-track w-full bg-dark-50 rounded-full h-2">
            <div class="progress-fill h-full rounded-full transition-all duration-300 bg-accent-gradient"
                 :style="'width:' + ((currentIndex / Math.max(questions.length, 1)) * 100) + '%'"></div>
        </div>

        <div class="card p-5">
            <p class="text-[10px] text-brand-400/80 font-bold uppercase tracking-widest mb-3" x-text="'Question ' + (currentIndex + 1)"></p>
            <p class="font-bold text-[17px] text-white/90 leading-relaxed" x-text="currentQuestion?.question || ''"></p>
        </div>

        <div class="flex flex-col gap-3">
            <template x-for="(opt, idx) in currentOptions" :key="idx">
                <button class="quiz-option"
                        :class="{ 'selected': selected === idx }"
                        @click="selectOption(idx, opt)"
                        :disabled="submitting">
                    <div class="flex items-center gap-3">
                        <div class="w-6 h-6 rounded-full border border-white/20 flex items-center justify-center flex-shrink-0 transition-colors"
                             :class="{ 'bg-brand-600 border-brand-600': selected === idx }">
                             <svg x-show="selected === idx" class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <span class="flex-1 text-left leading-snug" x-text="opt"></span>
                    </div>
                </button>
            </template>
        </div>

        <button class="btn btn-primary btn-full fade-in" x-show="selected !== null" style="display:none;" @click="nextQuestion()" :disabled="submitting">
            <div class="spinner spinner-sm" x-show="submitting" style="display:none;"></div>
            <span x-text="submitting ? 'Submitting...' : (currentIndex < questions.length - 1 ? 'Next Question' : 'Submit Quiz')"></span>
        </button>
    </div>

    <div x-show="done" style="display:none;" class="p-4 flex flex-col gap-4 fade-in pb-10">
        <div class="card text-center py-10 card-accent">
            <p class="font-extrabold text-[40px] text-white leading-none" x-text="score + '%'"></p>
            <p class="text-brand-300 font-medium text-sm mt-2" x-text="correct + ' out of ' + questions.length + ' correct'"></p>
            <p class="mt-4 font-bold text-lg text-white" x-text="score >= 80 ? 'Excellent work!' : score >= 60 ? 'Good job!' : 'Keep practicing!'"></p>
        </div>

        <p class="section-label mt-2 px-1">Review Answers</p>
        <template x-for="(q, qi) in questions" :key="q.id || qi">
            <div class="card" :class="answers[qi]?.correct ? 'border-emerald-500/30 bg-emerald-500/5' : 'border-red-500/30 bg-red-500/5'">
                <p class="text-sm font-semibold text-white/90 leading-relaxed mb-3" x-text="(qi + 1) + '. ' + q.question"></p>
                <div class="flex items-start gap-2">
                    <span class="mt-0.5" x-text="answers[qi]?.correct ? 'OK' : 'X'"></span>
                    <div>
                        <p class="text-[13px] font-medium" :class="answers[qi]?.correct ? 'text-emerald-400' : 'text-red-400'">
                            Your answer: <span x-text="answers[qi]?.selected || 'Skipped'"></span>
                        </p>
                        <p x-show="!answers[qi]?.correct" class="text-[12px] text-white/50 mt-1">
                            Correct: <span class="text-white/80 font-medium" x-text="answers[qi]?.correctAnswer || ''"></span>
                        </p>
                    </div>
                </div>
            </div>
        </template>

        <div class="flex flex-col gap-3 mt-4">
            <a href="/tools/quiz" class="btn btn-primary btn-full">New Quiz</a>
            <a href="/tools" class="btn btn-ghost btn-full">Back to Tools</a>
        </div>
    </div>

    <div class="empty-state" x-show="!loading && !error && questions.length === 0" style="display:none;">
        <h3>No questions found</h3>
        <p>This quiz is not ready or it does not contain questions yet.</p>
        <a href="/tools/quiz" class="btn btn-primary btn-sm mt-2">Create Quiz</a>
    </div>

</div>

@push('scripts')
<script>
function quizPlayer(quizId) {
    return {
        quizId,
        quiz: null,
        questions: [],
        currentIndex: 0,
        selected: null,
        correct: 0,
        answers: [],
        done: false,
        loading: true,
        error: '',
        submitting: false,
        sharing: false,
        copied: false,

        get currentQuestion() {
            return this.questions[this.currentIndex] || null;
        },

        get currentOptions() {
            const q = this.currentQuestion;
            return q ? (q.options || q.choices || q.all_options || []) : [];
        },

        get score() {
            return this.questions.length ? Math.round((this.correct / this.questions.length) * 100) : 0;
        },

        async init() {
            this.loading = true;
            this.error = '';
            this.done = false;

            try {
                const res = await Api.get('/quiz/' + this.quizId);
                this.quiz = res.data || res;
                const questions = this.quiz.questions || [];
                this.questions = questions.map((q) => ({
                    ...q,
                    question: q.question || q.text || q.question_text || '',
                    options: q.options || q.choices || q.all_options || [],
                }));
                this.answers = new Array(this.questions.length).fill(null);
            } catch (e) {
                if (e.status !== 401) this.error = e.message || 'Failed to load quiz.';
            } finally {
                this.loading = false;
            }
        },

        selectOption(idx, value) {
            if (this.submitting) return;
            this.selected = idx;
            this.answers[this.currentIndex] = {
                selected: value,
                correct: null,
                correctAnswer: null,
            };
        },

        async nextQuestion() {
            if (this.selected === null) return;

            if (this.currentIndex < this.questions.length - 1) {
                this.currentIndex++;
                const existing = this.answers[this.currentIndex]?.selected;
                this.selected = existing ? this.currentOptions.indexOf(existing) : null;
                return;
            }

            await this.submitAttempt();
        },

        async submitAttempt() {
            this.submitting = true;
            this.error = '';

            try {
                const payload = {};
                this.questions.forEach((q, index) => {
                    payload[q.id] = this.answers[index]?.selected || '';
                });

                const result = await Api.post('/quiz/' + this.quizId + '/attempt', { answers: payload });
                const rows = result.results || [];
                this.correct = result.score || 0;
                this.answers = this.questions.map((q, index) => {
                    const row = rows.find((item) => Number(item.question_id) === Number(q.id));
                    return {
                        selected: this.answers[index]?.selected || row?.submitted_answer || '',
                        correct: !!row?.is_correct,
                        correctAnswer: row?.correct_answer || '',
                    };
                });
                this.done = true;
            } catch (e) {
                this.error = e.message || 'Could not submit quiz.';
            } finally {
                this.submitting = false;
            }
        },

        async toggleShare() {
            if (this.sharing || !this.quiz) return;
            this.sharing = true;
            this.error = '';

            try {
                const res = await Api.post('/quiz/' + this.quizId + '/toggle-share');
                this.quiz.is_public = !!res.is_public;
                this.quiz.share_url = res.share_url || null;
            } catch (e) {
                this.error = e.message || 'Could not update share setting.';
            } finally {
                this.sharing = false;
            }
        },

        async copyShare() {
            if (!this.quiz?.share_url) return;

            try {
                await navigator.clipboard.writeText(this.quiz.share_url);
            } catch (e) {
                const input = document.createElement('textarea');
                input.value = this.quiz.share_url;
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
            if (!this.quiz?.share_url) return;
            if (navigator.share) {
                await navigator.share({ title: this.quiz.title || 'EduAI Quiz', url: this.quiz.share_url });
            } else {
                await this.copyShare();
            }
        },
    };
}
</script>
@endpush
@endsection
