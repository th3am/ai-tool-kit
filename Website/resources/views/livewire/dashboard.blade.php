<div>
    @if($jobNotice)
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-300">
            {{ $jobNotice }}
        </div>
    @endif

    {{-- ── Credits Banner ─────────────────────────────────────────────── --}}
    <div class="hidden mb-6 items-center justify-between px-4 py-3 rounded-xl bg-white/50 dark:bg-white/5 border border-gray-200 dark:border-white/10 backdrop-blur-sm">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-yellow-500/20 flex items-center justify-center">
                <svg class="w-4 h-4 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                    <span class="text-yellow-500">{{ number_format($userCredits) }}</span> Credits Remaining
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Used to generate AI content</p>
            </div>
        </div>
        @if(auth()->user()->isAdmin())
        <span class="text-xs bg-indigo-600/20 text-indigo-400 border border-indigo-500/30 px-2.5 py-1 rounded-full font-medium">Admin — Unlimited</span>
        @else
        <div class="flex items-center gap-2">
            <div class="hidden sm:flex items-center gap-1.5">
                @php $pct = min(100, max(0, round(($userCredits / max(1, ($userCredits + auth()->user()->credits_used))) * 100))); @endphp
                <div class="w-24 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all {{ $pct < 20 ? 'bg-red-500' : ($pct < 50 ? 'bg-yellow-500' : 'bg-emerald-500') }}"
                         style="width: {{ $pct }}%"></div>
                </div>
                <span class="text-xs text-gray-500">{{ $pct }}%</span>
            </div>
            @if(auth()->user()->plan)
                <span class="text-xs bg-indigo-600/20 text-indigo-400 border border-indigo-500/30 px-2.5 py-1 rounded-full font-medium">{{ auth()->user()->plan->name }}</span>
            @else
                <span class="text-xs bg-slate-600/20 text-slate-400 border border-slate-500/30 px-2.5 py-1 rounded-full font-medium">Free Plan</span>
            @endif
        </div>
        @endif
    </div>

    <!-- Stepper -->
    <div class="upper h-auto items-center flex">
        <div class="w-full flex justify-center items-center pt-2 pb-2 md:pt-2 md:pb-2 lg:py-6 px-3">
          <div class="flex items-center justify-center gap-4 sm:gap-6 flex-wrap">

            <div wire:click="setStep(1)" class="flex items-center gap-2 cursor-pointer">
              <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm sm:text-md font-semibold {{ $step >= 1 ? 'bg-[#6366F1] text-white' : 'bg-[#DBDDE9] dark:bg-white/5 text-gray-600 dark:text-gray-300' }}">
                1
              </div>
              <span class="text-sm sm:text-md font-medium {{ $step >= 1 ? 'text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400' }}">
                Choose Tool &amp; Submit
              </span>
            </div>

            <div class="hidden sm:block w-[50px] h-[2px] bg-gray-300 dark:bg-white/10"></div>

            <div class="flex items-center gap-2 {{ $step >= 2 ? '' : 'opacity-50' }}">
              <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm sm:text-md font-semibold {{ $step >= 2 ? 'bg-[#6366F1] text-white' : 'bg-[#DBDDE9] dark:bg-white/5 text-gray-600 dark:text-gray-300' }}">
                2
              </div>
              <span class="text-sm sm:text-md font-medium {{ $step >= 2 ? 'text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400' }}">
                Configure &amp; Process
              </span>
            </div>

          </div>
        </div>
    </div>

    <!-- Step 1: Choose Tool — GRAD-PROJECT 70/30 Layout -->
    @if($step === 1)
        @php
            $tools = [
                ['id' => 'mindmap-generator',    'name' => 'Mind Map Generator',     'desc' => 'Transform text into visual mind maps',                     'credits' => 15, 'time' => '2-3 min',  'fa' => 'fa-solid fa-book-open',       'color' => 'fuchsia'],
                ['id' => 'audio',                'name' => 'Audio Narration',         'desc' => 'Convert text to natural audio',                            'credits' => 20, 'time' => '3-4 min',  'fa' => 'fa-solid fa-microphone',      'color' => 'sky'],
                ['id' => 'video-animation',      'name' => '2D Animation Video',      'desc' => 'Create engaging animated videos',                          'credits' => 30, 'time' => '5-7 min',  'fa' => 'fa-solid fa-video',           'color' => 'orange'],
                ['id' => 'quiz-generator',       'name' => 'Quiz Generator',          'desc' => 'Generate AI-powered MCQ quizzes from text or files',       'credits' => 10, 'time' => '2-3 min',  'fa' => 'fa-solid fa-circle-question', 'color' => 'pink'],
                ['id' => 'powerpoint-generator', 'name' => 'PowerPoint Generator',   'desc' => 'Generate professional presentations',                      'credits' => 25, 'time' => '4-6 min',  'fa' => 'fa-solid fa-file-powerpoint', 'color' => 'emerald'],
                ['id' => 'video-explainer',      'name' => 'Video Explainer',         'desc' => 'Narrated MP4 explainer videos with AI slides & TTS audio', 'credits' => 40, 'time' => '8-15 min', 'fa' => 'fa-solid fa-film',            'color' => 'teal'],
                ['id' => 'lecture',              'name' => 'Lecture Explainer Video', 'desc' => 'Create educational explainer videos',                      'credits' => 35, 'time' => '6-8 min',  'fa' => 'fa-solid fa-book',            'color' => 'violet'],
            ];
            $colorMap = [
                'fuchsia' => ['bg' => 'bg-fuchsia-500/30', 'icon' => 'text-fuchsia-400 dark:text-fuchsia-300', 'credit' => 'text-fuchsia-300'],
                'sky'     => ['bg' => 'bg-sky-500/30',     'icon' => 'text-sky-400 dark:text-sky-300',         'credit' => 'text-sky-300'],
                'orange'  => ['bg' => 'bg-orange-500/30',  'icon' => 'text-orange-400 dark:text-orange-300',   'credit' => 'text-orange-300'],
                'pink'    => ['bg' => 'bg-pink-500/30',    'icon' => 'text-pink-400 dark:text-pink-300',       'credit' => 'text-pink-300'],
                'emerald' => ['bg' => 'bg-emerald-500/30', 'icon' => 'text-emerald-400 dark:text-emerald-300', 'credit' => 'text-emerald-300'],
                'teal'    => ['bg' => 'bg-teal-500/30',    'icon' => 'text-teal-400 dark:text-teal-300',       'credit' => 'text-teal-300'],
                'violet'  => ['bg' => 'bg-violet-500/30',  'icon' => 'text-violet-400 dark:text-violet-300',   'credit' => 'text-violet-300'],
            ];
            $selectedToolData = collect($tools)->firstWhere('id', $selectedTool);
        @endphp

        @error('tool')
            <div class="mb-4 mx-3 px-4 py-3 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-sm text-red-600 dark:text-red-400">
                {{ $message }}
            </div>
        @enderror

        <div class="lower relative w-full h-auto mx-auto overflow-visible flex">
        <div class="lower2 w-full h-full inset-0 mx-auto gap-4 lg:gap-[2%] flex flex-col lg:flex-row pr-3 lg:pr-[2%] pl-3 lg:pl-[2%] pt-0 pb-8">

            {{-- LEFT: Tools Grid (70%) --}}
            <div class="leftTools w-full lg:w-[70%] h-auto dark:bg-[#060b21] rounded-xl px-4 lg:pl-[4%]">
                <div class="toolsUpper grid grid-cols-1">
                <div class="tools-upper grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 xl:grid-cols-2 gap-4 lg:gap-5 w-full h-auto lg:h-[50%]">
                    @foreach($tools as $tool)
                        @php $c = $colorMap[$tool['color']] ?? $colorMap['violet']; @endphp
                        <div class="w-full lg:w-[90%] h-auto backdrop-blur-3xl bg-white/50 dark:bg-white/5 rounded-xl transition-all duration-300
                            {{ $selectedTool === $tool['id'] ? 'ring-2 ring-[#6366f1] shadow-[0_0_0_3px_rgba(99,102,241,0.12)] -translate-y-[1px]' : '' }}">
                            <button
                                wire:click="selectTool('{{ $tool['id'] }}')"
                                class="toolCard w-full h-full rounded-2xl px-6 pt-6 pb-0 bg-white/5 border border-white/10 hover:bg-white/10 transition text-left shadow-lg relative"
                            >
                                @if($selectedTool === $tool['id'])
                                    <div class="absolute top-4 right-4 text-[#6366f1]">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                @endif
                                <div class="w-12 h-12 rounded-2xl {{ $c['bg'] }} flex items-center justify-center mb-3">
                                    <i class="{{ $tool['fa'] }} {{ $c['icon'] }} text-xl"></i>
                                </div>
                                <h3 class="text-2xl font-bold leading-tight dark:text-white text-gray-900">{{ $tool['name'] }}</h3>
                                <p class="text-lg text-gray-500 dark:text-gray-400 mt-3">{{ $tool['desc'] }}</p>
                                <div class="mt-2 flex items-center justify-between text-md mb-[30px]">
                                    <span class="{{ $c['credit'] }} font-semibold">{{ $tool['credits'] }} Credits</span>
                                    <span class="text-gray-400 flex items-center gap-2">
                                        <i class="fa-regular fa-clock"></i> {{ $tool['time'] }}
                                    </span>
                                </div>
                            </button>
                        </div>
                    @endforeach
                </div>
                </div>
            </div>

            {{-- RIGHT: Summary + Inputs + Next (30%) --}}
            <div class="w-full lg:w-[30%] h-auto lg:h-auto flex flex-col dark:bg-[#060b21] gap-5">

                {{-- Job Summary --}}
                <div class="rounded-2xl mx-4 lg:mx-0 p-6 dark:bg-white/5 backdrop-blur-3xl bg-white/50 border border-white/10 shadow-lg">
                    <h3 class="flex items-center gap-2 font-bold text-xl mb-4 text-gray-900 dark:text-white">
                        <span class="inline-flex items-center justify-center w-4 h-4 rounded-md bg-violet-500/20">
                            <span class="w-2 h-2 rounded-sm bg-violet-400"></span>
                        </span>
                        Job Summary
                    </h3>
                    <div class="space-y-3 text-md text-gray-600 dark:text-gray-400">
                        <p><span class="font-medium text-gray-700 dark:text-gray-300">Tool: </span>{{ $selectedToolData ? $selectedToolData['name'] : '—' }}</p>
                        <p><span class="font-medium text-gray-700 dark:text-gray-300">Credits: </span>{{ $selectedToolData ? $selectedToolData['credits'] : '—' }}</p>
                        <p><span class="font-medium text-gray-700 dark:text-gray-300">Est. Time: </span>{{ $selectedToolData ? $selectedToolData['time'] : '—' }}</p>
                    </div>
                </div>

                {{-- Topic Title --}}
                <div class="w-[91%] md:w-[95%] m-auto lg:w-[100%] lg:max-w-2xl space-y-2">
                    <label class="block font-semibold text-gray-700 dark:text-gray-300 text-xl">
                        Topic Title <span class="text-gray-400 font-normal text-sm">(optional)</span>
                    </label>
                    <input
                        wire:model="topic"
                        type="text"
                        placeholder="Enter a topic or subject..."
                        class="w-full px-4 sm:px-5 lg:py-3 py-4 sm:py-5 rounded-xl bg-white/5 backdrop-blur-xl border dark:border-white/10 border-gray-400 text-md sm:text-base md:text-lg text-gray-800 dark:text-gray-200 placeholder-gray-500 outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition"
                    />
                </div>

                {{-- Additional Instructions --}}
                <div class="rounded-2xl mx-4 lg:mx-0 backdrop-blur-3xl bg-white/50 p-6 dark:bg-white/5 border border-white/10 shadow-lg flex-1">
                    <h3 class="font-bold text-xl text-gray-900 dark:text-white mb-4">
                        Additional Instructions <span class="text-gray-400 font-normal text-sm">(optional)</span>
                    </h3>
                    <textarea
                        wire:model="instructions"
                        rows="4"
                        class="w-full h-[75%] rounded-2xl bg-transparent dark:border dark:border-white/10 p-4 text-md dark:text-gray-200 text-gray-700 border border-gray-300 outline-none focus:ring-1 focus:ring-indigo-500 resize-none"
                        placeholder="Add any specific requirements or preferences..."
                    ></textarea>
                </div>

                {{-- Next Button --}}
                <div class="flex justify-end gap-3">
                <button
                    wire:click="goToStep2"
                    wire:loading.attr="disabled"
                    class="lg:w-1/2 py-3 mx-4 w-1/4 lg:mx-0 mb-2 lg:mb-0 rounded-xl text-xl text-white bg-gradient-to-r from-[#3b82f6] to-[#6366f1] hover:from-[#2563eb] hover:to-[#4f46e5] transition font-semibold flex items-center justify-center disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="goToStep2">
                        Next <i class="fa-solid fa-arrow-right text-sm ml-1 mt-1"></i>
                    </span>
                    <span wire:loading wire:target="goToStep2" class="flex items-center gap-2">
                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Loading…
                    </span>
                </button>
                </div>

            </div>{{-- /right --}}
        </div>{{-- /flex row --}}
        </div>
    @endif

    @if($step === 1)
        <div class="mx-3 lg:mx-[2%] mb-8 rounded-2xl border border-white/10 bg-white/50 dark:bg-white/5 backdrop-blur-3xl shadow-lg overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-white/10">
                <div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">Recent Jobs</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Your latest generated content and processing status.</p>
                </div>
                <a href="{{ route('quiz.index') }}" class="hidden sm:inline-flex items-center gap-2 text-sm font-semibold text-indigo-500 hover:text-indigo-400">
                    My quizzes <i class="fa-solid fa-arrow-right text-xs"></i>
                </a>
            </div>

            <div class="divide-y divide-gray-200 dark:divide-white/10">
                @forelse($recentJobs as $job)
                    @php
                        $statusClasses = match($job->status) {
                            'succeeded' => 'bg-emerald-500/15 text-emerald-500 border-emerald-500/25',
                            'failed' => 'bg-red-500/15 text-red-500 border-red-500/25',
                            'queued' => 'bg-yellow-500/15 text-yellow-500 border-yellow-500/25',
                            'running' => 'bg-sky-500/15 text-sky-500 border-sky-500/25',
                            'cancelled' => 'bg-slate-500/15 text-slate-400 border-slate-500/25',
                            default => 'bg-slate-500/15 text-slate-400 border-slate-500/25',
                        };
                    @endphp
                    <div class="grid md:grid-cols-[1fr_auto_auto] gap-3 px-5 py-4 items-center">
                        <div class="min-w-0">
                            <p class="font-semibold text-gray-900 dark:text-white truncate">{{ ucwords(str_replace('-', ' ', $job->tool_type)) }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ $job->params['topic'] ?? $job->params['prompt'] ?? 'No topic provided' }}</p>
                        </div>
                        <span class="inline-flex justify-center rounded-full border px-3 py-1 text-xs font-bold capitalize {{ $statusClasses }}">{{ $job->status }}</span>
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $job->created_at->diffForHumans() }}</span>
                    </div>
                @empty
                    <div class="px-5 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No recent jobs yet.</div>
                @endforelse
            </div>
        </div>
    @endif

    <!-- Step 2: Upload or Inputs -->
    @if($step === 2)
        <div class="grid lg:grid-cols-3 gap-6 mb-8 transition-all duration-300">
            <!-- Input Area -->
            <div class="lg:col-span-2">
                <div class="backdrop-blur-3xl bg-white/50 dark:bg-white/5 rounded-2xl shadow-lg p-6 md:p-8 border border-gray-200 dark:border-white/10">
                    
                    @if($selectedTool === 'powerpoint-generator')
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Presentation Details</h3>
                        <div class="space-y-6">
                            <!-- Topic -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Topic / Title</label>
                                <input wire:model="topic" type="text" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('topic') border-red-500 @enderror" placeholder="e.g. Artificial Intelligence in Healthcare">
                                @error('topic') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Style & Slide Count Grid -->
                            <div class="grid md:grid-cols-2 gap-6">
                                <!-- Style -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Visual Style</label>
                                    <div class="grid grid-cols-2 gap-3">
                                        @foreach(['Modern', 'Professional', 'Creative', 'Minimalist'] as $s)
                                            <div wire:click="$set('style', '{{ $s }}')" class="cursor-pointer px-3 py-2 rounded-lg text-center text-sm border {{ $style === $s ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300' : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-indigo-300' }}">
                                                <span class="block font-semibold">{{ $s }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Slide Count -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Number of Slides: <span class="text-indigo-600 font-bold">{{ $slideCount }}</span></label>
                                    <input wire:model.live="slideCount" type="range" min="3" max="10" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700">
                                    <div class="flex justify-between text-xs text-gray-500 mt-1">
                                        <span>3</span>
                                        <span>10</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Instructions -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Instructions / Outline</label>
                                <textarea wire:model="instructions" rows="4" placeholder="Specific points to cover, audience details..." class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
                            </div>
                        </div>



                    @elseif($selectedTool === 'audio')
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Audio Narration Setup</h3>
                        <div class="space-y-6">
                            <!-- Topic/Text -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Narrative Text (Optional)</label>
                                <textarea wire:model="topic" rows="5" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none @error('topic') border-red-500 @enderror" placeholder="Enter the text you want to narrate, or describe what the audio should be about..."></textarea>
                                @error('topic') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            
                           <!-- File Upload (Copy MindMap style) -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Document to Narrate (Optional)</label>
                                <div 
                                    x-data="{ dragging: false }"
                                    x-on:dragover.prevent="dragging = true"
                                    x-on:dragleave.prevent="dragging = false"
                                    x-on:drop.prevent="dragging = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change', { bubbles: true }))"
                                    :class="{ 'bg-white/10 border-[#6366f1]': dragging }"
                                    class="backdrop-blur-3xl bg-white/50 dark:bg-white/5 border-2 border-dashed dark:border-white/30 border-gray-400 rounded-xl px-4 sm:px-6 py-8 text-center cursor-pointer hover:border-[#6366f1] transition-all relative">
                                    
                                    <input x-ref="fileInput" wire:model="uploadedFile" type="file" class="hidden" accept=".pdf,.png,.jpg,.jpeg,.txt,.doc,.docx">
                                    
                                    @if(!$uploadedFile)
                                        <div @click="$refs.fileInput.click()">
                                            <svg class="w-10 h-10 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                                            </svg>
                                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Click to upload or drag & drop</p>
                                            <p class="text-xs text-gray-500 mt-1">PDF, Docs, Images, or Text files (Max 1MB)</p>
                                        </div>
                                    @else
                                        <!-- File Preview (Same as MindMap) -->
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center">
                                                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                    </svg>
                                                </div>
                                                <div class="text-left">
                                                    <p class="font-medium text-gray-900 dark:text-white text-sm">{{ $uploadedFile->getClientOriginalName() }}</p>
                                                    <span class="text-xs text-gray-500">{{ number_format($uploadedFile->getSize() / 1024, 2) }} KB</span>
                                                </div>
                                            </div>
                                            <button wire:click="removeFile" class="p-1 text-red-500 hover:bg-red-50 rounded transition">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                    @elseif($selectedTool === 'mindmap-generator')
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Mind Map Configuration</h3>
                        <div class="space-y-6">
                            <!-- Topic -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Topic / Description</label>
                                <textarea wire:model="topic" rows="3" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500 resize-none @error('topic') border-red-500 @enderror" placeholder="Enter a topic or describe what you want the mind map to cover..."></textarea>
                                @error('topic') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            
                            <!-- File Upload for OCR/Context -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Document Reference (Optional)</label>
                                <div 
                                    x-data="{ dragging: false }"
                                    x-on:dragover.prevent="dragging = true"
                                    x-on:dragleave.prevent="dragging = false"
                                    x-on:drop.prevent="dragging = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change', { bubbles: true }))"
                                    :class="{ 'bg-white/10 border-[#6366f1]': dragging }"
                                    class="backdrop-blur-3xl bg-white/50 dark:bg-white/5 border-2 border-dashed dark:border-white/30 border-gray-400 rounded-xl px-4 sm:px-6 py-8 text-center cursor-pointer hover:border-[#6366f1] transition-all relative">
                                    
                                    <input x-ref="fileInput" wire:model="uploadedFile" type="file" class="hidden" accept=".pdf,.png,.jpg,.jpeg,.txt">
                                    
                                    @if(!$uploadedFile)
                                        <div @click="$refs.fileInput.click()">
                                            <svg class="w-10 h-10 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Click to upload or drag & drop</p>
                                            <p class="text-xs text-gray-500 mt-1">PDF, Images, or Text files (Max 10MB)</p>
                                        </div>
                                    @else
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900 flex items-center justify-center">
                                                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                    </svg>
                                                </div>
                                                <div class="text-left">
                                                    <p class="font-medium text-gray-900 dark:text-white text-sm">{{ $uploadedFile->getClientOriginalName() }}</p>
                                                    <span class="text-xs text-gray-500">{{ number_format($uploadedFile->getSize() / 1024, 2) }} KB</span>
                                                </div>
                                            </div>
                                            <button wire:click="removeFile" class="p-1 text-red-500 hover:bg-red-50 rounded transition">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                    @elseif($selectedTool === 'video-animation')
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">2D Animation Configuration</h3>
                        <div class="space-y-6">
                            <!-- Prompt -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Animation Description</label>
                                <textarea wire:model="topic" rows="4" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500 resize-none @error('topic') border-red-500 @enderror" placeholder="Describe the animation you want (e.g., 'A bouncing red ball on a blue background', 'A rocket launching into space')..."></textarea>
                                @error('topic') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                    @elseif($selectedTool === 'video-explainer')
                        {{-- ═══════════════════════════════════════════ --}}
                        {{-- 🎬  Video Explainer Configuration           --}}
                        {{-- ═══════════════════════════════════════════ --}}
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 rounded-xl bg-teal-500/20 flex items-center justify-center">
                                <svg class="w-5 h-5 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Video Explainer Setup</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">AI generates slides + narration → assembled into MP4</p>
                            </div>
                        </div>

                        <div class="space-y-6">

                            {{-- Topic --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Topic / Subject <span class="text-red-500">*</span>
                                </label>
                                <textarea wire:model="topic" rows="3"
                                    class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500 resize-none @error('topic') border-red-500 @enderror"
                                    placeholder="e.g. How does photosynthesis work? / ما هو الذكاء الاصطناعي؟"></textarea>
                                @error('topic') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            {{-- Language + Style --}}
                            <div class="grid md:grid-cols-2 gap-6">

                                {{-- Target Language --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        🌐 Target Language (Narration + Voice)
                                    </label>
                                    <select wire:model="videoLanguage"
                                        class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                                        <option value="ar">🇪🇬 Arabic (Egypt) — Salma</option>
                                        <option value="ar-sa">🇸🇦 Arabic (Saudi) — Zariyah</option>
                                        <option value="en">🇺🇸 English (US) — Jenny</option>
                                        <option value="en-gb">🇬🇧 English (UK) — Sonia</option>
                                        <option value="fr">🇫🇷 French — Denise</option>
                                        <option value="es">🇪🇸 Spanish — Elvira</option>
                                        <option value="de">🇩🇪 German — Katja</option>
                                        <option value="it">🇮🇹 Italian — Elsa</option>
                                        <option value="pt">🇧🇷 Portuguese (BR) — Francisca</option>
                                        <option value="tr">🇹🇷 Turkish — Emel</option>
                                        <option value="zh">🇨🇳 Chinese — Xiaoxiao</option>
                                        <option value="ja">🇯🇵 Japanese — Nanami</option>
                                        <option value="ko">🇰🇷 Korean — SunHi</option>
                                        <option value="ru">🇷🇺 Russian — Svetlana</option>
                                    </select>
                                    <p class="text-xs text-gray-400 mt-1">{{ \App\Models\AppSetting::getValue('tts_provider', 'edge') === 'gemini' ? 'Gemini TTS selected by admin' : 'Microsoft Edge Neural TTS voices' }}</p>
                                </div>

                                {{-- Visual Style --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Visual Style</label>
                                    <div class="grid grid-cols-2 gap-2">
                                        @foreach(['Modern', 'Professional', 'Creative', 'Minimalist'] as $s)
                                            <div wire:click="$set('style', '{{ $s }}')"
                                                class="cursor-pointer px-3 py-2 rounded-lg text-center text-sm border transition {{ $style === $s ? 'border-teal-500 bg-teal-50 dark:bg-teal-900/20 text-teal-700 dark:text-teal-300' : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-teal-300' }}">
                                                <span class="block font-semibold">{{ $s }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            {{-- Scene Count + Captions --}}
                            <div class="grid md:grid-cols-2 gap-6">

                                {{-- Number of Scenes --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Number of Scenes: <span class="text-teal-600 dark:text-teal-400 font-bold">{{ $slideCount }}</span>
                                    </label>
                                    <input wire:model.live="slideCount" type="range" min="3" max="30"
                                        class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700 accent-teal-500">
                                    <div class="flex justify-between text-xs text-gray-500 mt-1">
                                        <span>3 (~30s)</span>
                                        <span>30 (long video)</span>
                                    </div>
                                </div>

                                {{-- Captions Toggle --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        🎬 Captions / Subtitles
                                    </label>
                                    <div class="flex items-center gap-3 mt-2">
                                        <button type="button" wire:click="$set('videoEnableCaptions', true)"
                                            class="flex-1 py-3 rounded-lg text-sm font-semibold border transition-all {{ $videoEnableCaptions ? 'bg-teal-500 border-teal-500 text-white shadow-md' : 'border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:border-teal-400' }}">
                                            ✅ On
                                        </button>
                                        <button type="button" wire:click="$set('videoEnableCaptions', false)"
                                            class="flex-1 py-3 rounded-lg text-sm font-semibold border transition-all {{ !$videoEnableCaptions ? 'bg-gray-500 border-gray-500 text-white shadow-md' : 'border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:border-gray-400' }}">
                                            ❌ Off
                                        </button>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-2">Subtitles are burnt directly into the video</p>
                                </div>
                            </div>

                            {{-- Additional Instructions --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Additional Instructions <span class="text-gray-400 font-normal">(optional)</span>
                                </label>
                                <textarea wire:model="instructions" rows="2"
                                    placeholder="e.g. Target beginner level, use simple language, include real-world examples..."
                                    class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500 resize-none"></textarea>
                            </div>

                            {{-- Info Banner --}}
                            <div class="flex items-start gap-3 p-4 bg-teal-50 dark:bg-teal-900/20 border border-teal-200 dark:border-teal-800 rounded-xl">
                                <svg class="w-5 h-5 text-teal-600 dark:text-teal-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="text-sm text-teal-700 dark:text-teal-300">
                                    <strong>Pipeline:</strong> {{ config('services.ai.provider') === 'chatgpt' ? 'LLM' : 'Gemini AI' }} → {{ $slideCount }} distinct visual scenes + narrations → Browsershot screenshots → {{ \App\Models\AppSetting::getValue('tts_provider', 'edge') === 'gemini' ? 'Gemini TTS' : 'Edge TTS' }} audio → FFmpeg assembles MP4{{ $videoEnableCaptions ? ' with burnt-in subtitles' : '' }}. Larger videos can take <strong>up to 30 minutes</strong>.
                                </p>
                            </div>

                        </div>

                    @elseif($selectedTool === 'quiz-generator')
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">🧠 Quiz Generator Setup</h3>
                        <div class="space-y-6">
                            
                            <!-- Topic/Instructions -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Subject / Content (Optional if file uploaded)</label>
                                <textarea wire:model="topic" rows="5" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-pink-500 resize-none @error('topic') border-red-500 @enderror" placeholder="Paste the text or subject you want to generate questions from..."></textarea>
                                @error('topic') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Question Count Slider -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Number of Questions: <span class="text-pink-600 font-bold">{{ $slideCount }}</span></label>
                                <input wire:model.live="slideCount" type="range" min="3" max="20" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700">
                                <div class="flex justify-between text-xs text-gray-500 mt-1">
                                    <span>3 Max</span>
                                    <span>20 Max</span>
                                </div>
                            </div>
                            
                            <!-- File Upload -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Source Document (Optional if text provided)</label>
                                <div 
                                    x-data="{ dragging: false }"
                                    x-on:dragover.prevent="dragging = true"
                                    x-on:dragleave.prevent="dragging = false"
                                    x-on:drop.prevent="dragging = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change', { bubbles: true }))"
                                    :class="{ 'bg-white/10 border-[#6366f1]': dragging }"
                                    class="backdrop-blur-3xl bg-white/50 dark:bg-white/5 border-2 border-dashed dark:border-white/30 border-gray-400 rounded-xl px-4 sm:px-6 py-8 text-center cursor-pointer hover:border-[#6366f1] transition-all relative">
                                    
                                    <input x-ref="fileInput" wire:model="uploadedFile" type="file" class="hidden" accept=".pdf,.doc,.docx,.pptx,.txt">
                                    
                                    @if(!$uploadedFile)
                                        <div @click="$refs.fileInput.click()">
                                            <svg class="w-10 h-10 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Click to upload or drag & drop</p>
                                            <p class="text-xs text-gray-500 mt-1">PDF, DOCX, PPTX, or Text (Max 20MB)</p>
                                        </div>
                                    @else
                                        <!-- File Preview -->
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-lg bg-pink-100 dark:bg-pink-900 flex items-center justify-center">
                                                    <svg class="w-5 h-5 text-pink-600 dark:text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                    </svg>
                                                </div>
                                                <div class="text-left">
                                                    <p class="font-medium text-gray-900 dark:text-white text-sm">{{ $uploadedFile->getClientOriginalName() }}</p>
                                                    <span class="text-xs text-gray-500">{{ number_format($uploadedFile->getSize() / 1024, 2) }} KB</span>
                                                </div>
                                            </div>
                                            <button wire:click="removeFile" class="p-1 text-red-500 hover:bg-red-50 rounded transition">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                    @else
                        <!-- Upload Zone (Default) -->
                        <div 
                            x-data="{ dragging: false }"
                            x-on:dragover.prevent="dragging = true"
                            x-on:dragleave.prevent="dragging = false"
                            x-on:drop.prevent="dragging = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change', { bubbles: true }))"
                            :class="{ 'bg-white/10 border-[#6366f1]': dragging }"
                            class="backdrop-blur-3xl bg-white/50 dark:bg-white/5 border-2 border-dashed dark:border-white/30 border-gray-400 rounded-xl px-4 sm:px-6 py-8 text-center cursor-pointer hover:border-[#6366f1] transition-all relative">
                            
                            <input x-ref="fileInput" wire:model="uploadedFile" type="file" class="hidden">

                            @if(!$uploadedFile)
                                <div @click="$refs.fileInput.click()">
                                    <svg class="w-16 h-16 mx-auto text-indigo-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                    </svg>
                                    <p class="text-lg font-medium text-gray-700 dark:text-gray-300 mb-2">Drag and drop your file here</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">or</p>
                                    <label class="px-6 py-3 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 transition-all cursor-pointer inline-block">Browse Files</label>
                                </div>
                            @else
                                <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-lg bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                        </div>
                                        <div class="text-left">
                                            <p class="font-medium text-gray-900 dark:text-white">{{ $uploadedFile->getClientOriginalName() }}</p>
                                            <div class="flex items-center gap-3 mt-1">
                                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ number_format($uploadedFile->getSize() / 1024, 2) }} KB</span>
                                            </div>
                                        </div>
                                    </div>
                            </div>
                            <input type="file" id="file-upload" wire:model="uploadedFile" x-ref="fileInput" class="hidden" accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png">
                        </div>
                    @endif
                </div>


            @if(false)
            <!-- Legacy result modals kept disabled. The active result modals are rendered at the component root. -->
            <!-- Animation Result Modal -->
            @if($generatedAnimation)
            <div 
                x-data="{ 
                    close() {
                        $wire.continueToChat();
                    }
                }"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4"
            >
                <div class="bg-white dark:bg-gray-900 w-full max-w-4xl rounded-2xl shadow-2xl flex flex-col relative overflow-hidden h-[80vh]">
                     <!-- Header -->
                     <div class="flex justify-between items-center p-4 border-b border-gray-100 dark:border-gray-800">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Animation Preview</h3>
                        <div class="flex gap-2">
                             <a href="{{ route('animation.download', ['job' => $generatedAnimationJobId, 'format' => 'svg']) }}" target="_blank" class="px-3 py-1 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded text-sm font-medium text-gray-700 dark:text-gray-300">Download SVG</a>
                             <a href="{{ route('animation.download', ['job' => $generatedAnimationJobId, 'format' => 'mp4']) }}" target="_blank" class="px-3 py-1 bg-orange-500 hover:bg-orange-600 text-white rounded text-sm font-medium flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                MP4
                             </a>
                             <a href="{{ route('animation.download', ['job' => $generatedAnimationJobId, 'format' => 'gif']) }}" target="_blank" class="px-3 py-1 bg-pink-500 hover:bg-pink-600 text-white rounded text-sm font-medium">GIF</a>
                             
                             <button @click="close()" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-full text-gray-500">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                             </button>
                        </div>
                    </div>
                    
                    <!-- Canvas -->
                    <div class="flex-1 bg-gray-50 dark:bg-black/50 flex items-center justify-center p-8 overflow-hidden">
                         <div class="w-full h-full flex items-center justify-center bg-white dark:bg-gray-800 rounded-xl shadow-inner overflow-hidden relative">
                            {!! $generatedAnimation !!}
                         </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- ════════════════════════════════════════════════ --}}
            {{-- 🎬  Video Explainer Result Modal                 --}}
            {{-- ════════════════════════════════════════════════ --}}
            @if($generatedVideoPath)
            <div
                x-data="{
                    open: true,
                    close() {
                        this.open = false;
                        $wire.continueToChat();
                    }
                }"
                x-show="open"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/85 backdrop-blur-sm p-4"
            >
                <div
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    class="bg-white dark:bg-gray-950 w-full max-w-5xl rounded-3xl shadow-2xl flex flex-col overflow-hidden relative border border-teal-200/30 dark:border-teal-800/30"
                    style="max-height: 90vh;"
                >
                    {{-- Header --}}
                    <div class="flex justify-between items-center px-6 py-4 border-b border-gray-100 dark:border-gray-800 bg-gradient-to-r from-teal-600 to-cyan-600">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-white">Video Explainer Ready!</h3>
                                <p class="text-teal-100 text-xs">Your narrated MP4 video has been generated</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            {{-- Download Button --}}
                            <a href="{{ route('video.explainer.download', $generatedVideoJobId ?? 0) }}"
                                class="flex items-center gap-2 px-4 py-2 bg-white text-teal-700 rounded-xl text-sm font-bold hover:bg-teal-50 transition-all shadow">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                Download MP4
                            </a>
                            <button @click="close()" class="p-2 hover:bg-white/10 rounded-xl text-white transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Video Player --}}
                    <div class="flex-1 bg-black flex items-center justify-center overflow-hidden" style="min-height: 400px;">
                        <video
                            controls
                            autoplay
                            class="w-full h-full object-contain"
                            style="max-height: 60vh;"
                            src="{{ asset('storage/' . $generatedVideoPath) }}"
                        >
                            Your browser does not support the video tag.
                        </video>
                    </div>

                    {{-- Footer --}}
                    <div class="flex items-center justify-between px-6 py-4 bg-gray-50 dark:bg-gray-900/60 border-t border-gray-100 dark:border-gray-800">
                        <div class="flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-teal-100 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 rounded-full text-xs font-medium">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg>
                                MP4 Video
                            </span>
                            @if($videoEnableCaptions)
                                <span class="inline-flex items-center gap-1 px-3 py-1 bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-300 rounded-full text-xs font-medium">
                                    🎬 Subtitles Included
                                </span>
                            @endif
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-full text-xs font-medium">
                                🌐 {{ \App\Services\Ai\VideoExplainerGenerator::$languageNames[$videoLanguage] ?? $videoLanguage }}
                            </span>
                        </div>
                        <button @click="close()" class="px-5 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition">
                            Close & Continue
                        </button>
                    </div>
                </div>
            </div>
            @endif

            <!-- Mind Map Result Modal -->
            @if(false && $generatedMindMap)
            <div 
                x-data="{ 
                    rawMarkdown: @js($generatedMindMap),
                    mm: null,
                    init() {
                        this.$nextTick(() => {
                            this.waitForMarkmap();
                        });
                    },
                    waitForMarkmap(attempts = 0) {
                        if (window.markmap && window.markmap.Markmap && window.markmap.Transformer) {
                            console.log('Markmap loaded:', Object.keys(window.markmap));
                            this.renderMap();
                        } else {
                            if (attempts > 50) { 
                                console.error('Markmap missing. Window keys:', Object.keys(window));
                                alert('Failed to load Mind Map libraries (Timeout). Check console.');
                                return;
                            }
                            setTimeout(() => this.waitForMarkmap(attempts + 1), 100);
                        }
                    },
                    renderMap() {
                        try {
                            const { Markmap, Transformer } = window.markmap;
                            const svg = this.$refs.mmSvg;
                            
                            if (!this.rawMarkdown) {
                                console.error('No markdown content found');
                                alert('Error: Mind Map content is empty.');
                                return;
                            }

                            // Clean previous
                            svg.innerHTML = '';
                            
                            // Transform
                            const transformer = new Transformer();
                            const { root, features } = transformer.transform(this.rawMarkdown);
                            
                            // Render
                            this.mm = Markmap.create(svg, {
                                height: '100%',
                                duration: 250,
                                autoFit: false,
                            }, root);
                            
                            // Fit
                            setTimeout(() => {
                                this.mm.fit();
                                console.log('Markmap fitted');
                            }, 500);

                        } catch (e) {
                            console.error('Markmap render error:', e);
                            alert('Error rendering Mind Map: ' + e.message);
                        }
                    },
                    downloadSVG() {
                        const svg = this.$refs.mmSvg;
                        const svgData = new XMLSerializer().serializeToString(svg);
                        const blob = new Blob([svgData], { type: 'image/svg+xml;charset=utf-8' });
                        const url = URL.createObjectURL(blob);
                        const link = document.createElement('a');
                        link.href = url;
                        link.download = 'mindmap.svg';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    },
                    downloadPNG() {
                        try {
                             const svg = this.$refs.mmSvg;
                             // Get bounding box of the actual content
                             const bbox = svg.getBBox();
                             
                             if (!bbox || bbox.width === 0 || bbox.height === 0) {
                                 console.warn('SVG bounding box is empty. Content might not be rendered yet.');
                                 // Fallback to clientWidth if bbox fails
                                 bbox.width = svg.clientWidth || 800;
                                 bbox.height = svg.clientHeight || 600;
                                 bbox.x = 0;
                                 bbox.y = 0;
                             }

                             const canvas = document.createElement('canvas');
                             const ctx = canvas.getContext('2d');
                             
                             // Setup high-res export
                             const padding = 40; // More padding
                             const scale = 2; 
                             const width = bbox.width + padding * 2;
                             const height = bbox.height + padding * 2;
                             
                             canvas.width = width * scale;
                             canvas.height = height * scale;
                             
                             // Clone SVG to modify for export
                             const clonedSvg = svg.cloneNode(true);
                             
                             // CRITICAL: Explicitly set size and viewBox so the image loads correctly
                             clonedSvg.setAttribute('width', canvas.width);
                             clonedSvg.setAttribute('height', canvas.height);
                             clonedSvg.setAttribute('viewBox', `${bbox.x - padding} ${bbox.y - padding} ${width} ${height}`);
                             
                             // Create style element
                             const style = document.createElement('style');
                             style.textContent = `
                                .markmap-container { display: flex; justify-content: center; align-items: center; }
                                .markmap text { font-family: 'Tajawal', sans-serif; font-size: 14px; }
                                .markmap link { fill: none; }
                             `;
                             
                             // Check for Dark Mode and append styles
                             if (document.documentElement.classList.contains('dark')) {
                                 style.textContent += `
                                    .markmap text { fill: #f9fafb !important; }
                                    .markmap div { color: #f9fafb !important; }
                                    .markmap path { stroke: #9ca3af !important; }
                                    .markmap circle { stroke: #9ca3af !important; fill: #1f2937 !important; }
                                 `;
                             } else {
                                 style.textContent += `
                                    .markmap text { fill: #1f2937; }
                                    .markmap path { stroke: #9ca3af; }
                                    .markmap circle { stroke: #9ca3af; fill: #fff; }
                                 `;
                             }
                             
                             clonedSvg.prepend(style);
    
                             // Get SVG data
                             const serializer = new XMLSerializer();
                             const svgData = serializer.serializeToString(clonedSvg);
                             
                             // Create Image
                             const img = new Image();
                             const svgBlob = new Blob([svgData], {type: 'image/svg+xml;charset=utf-8'});
                             const url = URL.createObjectURL(svgBlob);
                             
                             img.onload = () => {
                                 // Draw Background
                                 ctx.fillStyle = document.documentElement.classList.contains('dark') ? '#111827' : '#ffffff';
                                 ctx.fillRect(0, 0, canvas.width, canvas.height);
                                 
                                 // Draw Image
                                 ctx.drawImage(img, 0, 0);
                                 
                                 // Download
                                 try {
                                     const pngUrl = canvas.toDataURL('image/png');
                                     const link = document.createElement('a');
                                     link.href = pngUrl;
                                     link.download = 'mindmap.png';
                                     document.body.appendChild(link);
                                     link.click();
                                     document.body.removeChild(link);
                                 } catch (err) {
                                     console.error('Data URL Error:', err);
                                     alert('Error creating PNG file.');
                                 }
                                 
                                 URL.revokeObjectURL(url);
                             };
                             
                             img.onerror = (e) => {
                                 console.error('Image Load Error', e);
                                 alert('Error loading SVG for conversion. Check console.');
                                 URL.revokeObjectURL(url);
                             };

                             img.src = url;

                        } catch (e) {
                            console.error('Export Error:', e);
                            alert('An unexpected error occurred during export.');
                        }
                    },
                    close() {
                        $wire.continueToChat();
                    }
                }"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4"
            >
                <!-- Style for Dark Mode support and Layout -->
                <style>
                    svg.markmap {
                        width: 100% !important;
                        height: 100% !important;
                    }
                    /* Dark Mode Text Fix - SVG Text */
                    .dark .markmap text {
                        fill: #f9fafb !important; /* gray-50 */
                    }
                    /* Dark Mode Text Fix - HTML Content (foreignObject) */
                    .dark .markmap div {
                        color: #f9fafb !important;
                    }
                    .dark .markmap path {
                        stroke: #9ca3af !important; /* gray-400 */
                    }
                    .dark .markmap circle {
                        stroke: #9ca3af !important;
                        fill: #1f2937 !important; /* gray-800 background for circles */
                    }
                </style>

                <div class="bg-white dark:bg-gray-900 w-full max-w-7xl h-[90vh] rounded-2xl shadow-2xl flex flex-col relative overflow-hidden">
                     <!-- Header -->
                     <div class="flex justify-between items-center p-4 border-b border-gray-100 dark:border-gray-800">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Mind Map Result</h3>
                        <div class="flex gap-2">
                             <button @click="downloadSVG()" class="px-3 py-1 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded text-sm font-medium text-gray-700 dark:text-gray-300">Download SVG</button>
                             <button @click="downloadPNG()" class="px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-sm font-medium">Download PNG</button>
                             <button @click="close()" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-full text-gray-500">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                             </button>
                        </div>
                    </div>
                    
                    <!-- Canvas -->
                    <div class="flex-1 bg-gray-50 dark:bg-black overflow-hidden relative">
                         <svg x-ref="mmSvg" class="w-full h-full block"></svg>
                    </div>
                </div>
            </div>
            @endif

            <!-- Audio Result Modal -->
            @if($generatedAudio)
            <div 
                x-data="{
                    close() {
                        $wire.continueToChat();
                    }
                }"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4"
            >
                <div class="bg-white dark:bg-gray-900 w-full max-w-2xl rounded-2xl shadow-2xl flex flex-col relative overflow-hidden">
                     <!-- Header -->
                     <div class="flex justify-between items-center p-6 border-b border-gray-100 dark:border-gray-800">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span class="p-2 rounded-lg bg-indigo-100 text-indigo-600 dark:bg-indigo-900/50 dark:text-indigo-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path></svg>
                            </span>
                            Audio Generated Successfully!
                        </h3>
                        <button @click="close()" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-full text-gray-500 transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    
                    <!-- Content -->
                    <div class="p-8 flex flex-col items-center justify-center bg-gray-50 dark:bg-black/50">
                         <div class="w-full max-w-md bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                            <div class="mb-4 text-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Your audio narration is ready to play.</p>
                            </div>
                            
                            <audio controls class="w-full h-12 rounded-lg accent-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                @php
                                    $mime = Str::endsWith($generatedAudio, '.wav') ? 'audio/wav' : 'audio/mpeg';
                                @endphp
                                <source src="{{ asset('storage/' . $generatedAudio) }}" type="{{ $mime }}">
                                Your browser does not support the audio element.
                            </audio>

                            <div class="mt-4 flex justify-between items-center text-xs text-gray-400">
                                <span>Generated by {{ \App\Models\AppSetting::getValue('tts_provider', 'edge') === 'gemini' ? 'Gemini TTS' : 'Edge TTS' }}</span>
                                <a href="{{ asset('storage/' . $generatedAudio) }}" download class="text-indigo-600 hover:text-indigo-500 hover:underline">Download File</a>
                            </div>
                         </div>

                         <div class="mt-8 text-center">
                             <button @click="close()" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold shadow-lg hover:shadow-xl transition-all flex items-center gap-2 mx-auto">
                                <span>Continue to Chat Session</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                             </button>
                             <p class="mt-2 text-xs text-gray-500">The audio has been saved to your chat history.</p>
                         </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Quiz Result Modal -->
            @if($generatedQuizId)
            <div 
                x-data="{
                    close() {
                        $wire.continueToChat();
                    }
                }"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4"
            >
                <div class="bg-white dark:bg-gray-900 w-full max-w-2xl rounded-2xl shadow-2xl flex flex-col relative overflow-hidden">
                     <!-- Header -->
                     <div class="flex justify-between items-center p-6 border-b border-gray-100 dark:border-gray-800">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span class="p-2 rounded-lg bg-pink-100 text-pink-600 dark:bg-pink-900/50 dark:text-pink-400">
                                📝
                            </span>
                            Quiz Generated Successfully!
                        </h3>
                        <button @click="close()" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-full text-gray-500 transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    
                    <!-- Content -->
                    <div class="p-8 flex flex-col items-center justify-center bg-gray-50 dark:bg-black/50">
                         <div class="w-full max-w-md bg-white dark:bg-gray-800 p-8 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 text-center">
                            
                            <div class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            
                            <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Ready to Play</h4>
                            <p class="text-gray-500 dark:text-gray-400 mb-8">Your AI-generated quiz is ready. You can take it now, edit it, or share its public link with your friends.</p>
                            
                            <div class="flex flex-col gap-3">
                                <a href="{{ route('quiz.show', $generatedQuizId) }}" class="w-full px-6 py-3 bg-gradient-to-r from-pink-500 to-rose-500 hover:from-pink-600 hover:to-rose-600 text-white rounded-xl font-bold shadow-lg hover:shadow-xl transition-all flex items-center justify-center gap-2">
                                    <span>View & Play Quiz</span>
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                </a>
                                
                                <button @click="close()" class="w-full px-6 py-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-bold shadow hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                    Return to Chat
                                </button>
                            </div>

                         </div>
                    </div>
                </div>
            </div>
            @endif

            @if (!empty($generatedSlides))
                    <div 
                        x-data="{ 
                            localSlides: @js($generatedSlides),
                            currentSlideIndex: 0,
                            showModal: true,
                            typedInstance: null,
                            autoAdvanceTimer: null,
                            scale: 0.5,

                            init() {
                                // Start automatically when modal opens
                                setTimeout(() => {
                                    this.updateScale();
                                    if (this.localSlides.length > 0) {
                                        this.typeSlide();
                                    }
                                }, 500);

                                window.addEventListener('resize', () => this.updateScale());

                                // Watch for any navigation (manual or auto)
                                this.$watch('currentSlideIndex', (value) => {
                                    this.typeSlide();
                                });
                            },

                            updateScale() {
                                const wrapper = this.$refs.previewWrapper;
                                if (wrapper) {
                                    const width = wrapper.clientWidth;
                                    const height = wrapper.clientHeight;
                                    // Calculate scale to fit 1920x1080 into current wrapper
                                    const scaleX = width / 1920;
                                    const scaleY = height / 1080;
                                    // Use the smaller scale to fit entirely, with a little padding (5%)
                                    this.scale = Math.min(scaleX, scaleY) * 0.95;
                                }
                            },

                            typeSlide() {
                                // 1. Cleanup previous state
                                clearTimeout(this.autoAdvanceTimer);
                                
                                if (this.typedInstance) {
                                    this.typedInstance.destroy();
                                    this.typedInstance = null;
                                }
                                
                                let container = this.$refs.slideContainer;
                                if (!container) return;
                                
                                container.innerHTML = '';
                                
                                // 2. Get content and validate index
                                if (this.currentSlideIndex < 0 || this.currentSlideIndex >= this.localSlides.length) {
                                    return;
                                }
                                
                                let content = this.localSlides[this.currentSlideIndex];
                                
                                // 3. Start Typing
                                this.typedInstance = new Typed(container, {
                                    strings: [content],
                                    typeSpeed: 5, // Fast but visible
                                    showCursor: false,
                                    contentType: 'html',
                                    loop: false,
                                    onComplete: (self) => {
                                        // 4. Auto-advance after delay
                                        if (this.currentSlideIndex < this.localSlides.length - 1) {
                                            this.autoAdvanceTimer = setTimeout(() => {
                                                this.currentSlideIndex++;
                                            }, 3000); // 3s read time
                                        }
                                    }
                                });
                            },

                            close() {
                                $wire.continueToChat();
                            }
                        }" 
                        x-show="showModal"
                        class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4 md:p-8"
                    >
                         <div class="bg-white dark:bg-gray-900 w-full max-w-7xl h-full max-h-[90vh] rounded-3xl shadow-2xl flex flex-col overflow-hidden relative">
                            <!-- Header -->
                            <div class="flex justify-between items-center p-6 border-b border-gray-100 dark:border-gray-800">
                                <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Presentation Preview</h3>
                                <button @click="close()" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-full transition">
                                    <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>

                            <!-- Content Area -->
                            <div class="flex-1 flex overflow-hidden">
                                <!-- Main Preview -->
                                <div class="flex-1 bg-gray-100 dark:bg-black p-4 md:p-8 flex items-center justify-center overflow-hidden relative">
                                    <div x-ref="previewWrapper" class="relative w-full h-full flex items-center justify-center">
                                        <!-- Scaled Container -->
                                        <div 
                                            class="bg-white shadow-2xl origin-center flex-shrink-0"
                                            :style="`width: 1920px; height: 1080px; transform: scale(${scale});`"
                                        >
                                            <!-- Typed.js Target Container -->
                                            <div x-ref="slideContainer" class="w-full h-full overflow-hidden"></div>
                                        </div>
                                    </div>
                                    
                                    <!-- Floating Nav Controls -->
                                    <div class="absolute bottom-6 left-1/2 transform -translate-x-1/2 flex gap-4 bg-white/10 backdrop-blur-md p-2 rounded-full border border-white/20 shadow-lg">
                                        <button @click="currentSlideIndex = Math.max(0, currentSlideIndex - 1)" :disabled="currentSlideIndex === 0" class="p-3 rounded-full hover:bg-white/20 text-white disabled:opacity-30 transition">
                                            <i class="fas fa-chevron-left"></i>
                                        </button>
                                        <span class="px-4 py-2 font-mono text-white flex items-center">
                                            <span x-text="currentSlideIndex + 1"></span> / <span x-text="localSlides.length"></span>
                                        </span>
                                        <button @click="currentSlideIndex = Math.min(localSlides.length - 1, currentSlideIndex + 1)" :disabled="currentSlideIndex === localSlides.length - 1" class="p-3 rounded-full hover:bg-white/20 text-white disabled:opacity-30 transition">
                                            <i class="fas fa-chevron-right"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Sidebar -->
                                <div class="w-80 border-l border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 flex flex-col">
                                    <div class="p-4 border-b border-gray-100 dark:border-gray-800">
                                        <h4 class="font-bold text-gray-900 dark:text-gray-100">Slide Sequence</h4>
                                    </div>
                                    <div class="flex-1 overflow-y-auto p-4 space-y-4 custom-scrollbar">
                                        <template x-for="(slide, index) in localSlides" :key="index">
                                            <div 
                                                @click="currentSlideIndex = index"
                                                class="cursor-pointer group relative transition-all duration-200"
                                                :class="{'ring-2 ring-indigo-500 rounded-lg bg-indigo-50 dark:bg-indigo-900/10': currentSlideIndex === index}"
                                            >
                                                <div class="aspect-video bg-white rounded border border-gray-200 dark:border-gray-700 overflow-hidden relative pointer-events-none">
                                                    <div class="absolute inset-0 w-[400%] h-[400%] scale-[0.25] origin-top-left p-4" x-html="slide"></div>
                                                </div>
                                                <div class="mt-2 flex justify-between items-center px-1">
                                                    <span class="text-xs font-medium text-gray-500">Slide <span x-text="index + 1"></span></span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Footer / Actions -->
                            <div class="p-6 border-t border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/50 flex flex-col md:flex-row justify-between items-center gap-4">
                                <div class="flex items-center gap-4">
                                    <button @click="close()" class="text-gray-500 hover:text-gray-700 font-medium whitespace-nowrap">Close / Cancel</button>
                                    
                                    <!-- WhatsApp Share -->
                                    <div class="flex items-center gap-2 border-l border-gray-300 dark:border-gray-700 pl-4">
                                        <div class="flex flex-col">
                                            <div class="flex items-center gap-2">
                                                <input type="text" wire:model.defer="whatsappNumber" placeholder="Ex: 62888xxxx" class="w-32 sm:w-40 border border-gray-300 rounded-lg px-3 py-2 text-sm dark:bg-gray-800 dark:text-white dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-green-500" title="Enter WhatsApp Number">
                                                <button wire:click="sendToWhatsApp" wire:loading.attr="disabled" class="px-4 py-2 bg-green-500 text-white rounded-lg text-sm font-medium hover:bg-green-600 transition flex items-center gap-2 disabled:opacity-50">
                                                    <span wire:loading.remove wire:target="sendToWhatsApp">
                                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                                                    </span>
                                                    <span wire:loading wire:target="sendToWhatsApp">...</span>
                                                </button>
                                            </div>
                                            @if($whatsappResult === 'success') <span class="text-green-600 text-xs font-bold mt-1">Sent! Check WhatsApp.</span> @endif
                                            @error('whatsapp') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </div>
                                
                                @if($generatedPresentationId)
                                    <a 
                                        href="{{ route('presentation.download', $generatedPresentationId) }}" 
                                        class="px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl shadow-lg hover:shadow-xl hover:from-indigo-500 hover:to-purple-500 transition-all font-bold flex items-center gap-2"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                        Download PDF Presentation
                                    </a>
                                @endif
                            </div>
                         </div>
                    </div>
                @endif
                    @endif
            @endif
                </div>
            </div>

            <!-- Sidebar Info -->
            <div class="space-y-6">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700 sticky top-24">
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">Job Summary</h4>
                    <div class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
                        <div>
                            <span class="font-medium text-gray-900 dark:text-white">Tool:</span>
                            <span class="capitalize">{{ str_replace('-', ' ', $selectedTool ?? 'None Selected') }}</span>
                        </div>
                        @if($uploadedFile)
                        <div>
                            <span class="font-medium text-gray-900 dark:text-white">File:</span>
                            <span class="truncate block w-full">{{ $uploadedFile->getClientOriginalName() }}</span>
                        </div>
                        @endif
                    </div>
                    
                    @if($errors->any())
                        <div class="mt-4 p-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-sm text-red-600 dark:text-red-400">
                            <ul class="list-disc pl-4 space-y-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- ── Generate Button ──────────────────────────────── --}}
                    @if(!$isProcessing)
                        <button
                            wire:click="generate"
                            wire:loading.attr="disabled"
                            class="mt-6 w-full py-4 px-6 rounded-xl font-bold text-white shadow-lg transition-all duration-200
                                   bg-gradient-to-r from-indigo-600 to-purple-600
                                   hover:from-indigo-500 hover:to-purple-500 hover:shadow-xl hover:-translate-y-0.5
                                   disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                        >
                            <span wire:loading.remove wire:target="generate" class="flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                Generate
                            </span>
                            <span wire:loading wire:target="generate" class="flex items-center gap-2">
                                <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor"
                                          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                Starting…
                            </span>
                        </button>
                    @endif

                    {{-- ── Processing State ─────────────────────────────── --}}
                    @if($isProcessing)
                        <div wire:poll.3000ms="pollJobStatus" class="mt-6 space-y-4">
                            <div class="flex flex-col items-center gap-4 p-5 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl border border-indigo-200 dark:border-indigo-800">
                                <div class="relative w-16 h-16">
                                    <svg class="animate-spin w-16 h-16 text-indigo-500" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor"
                                              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                    </svg>
                                </div>
                                <div class="text-center">
                                    <p class="font-bold text-indigo-700 dark:text-indigo-300 text-sm">
                                        {{ $processingStage ?? 'Processing…' }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        This page updates automatically every 3 seconds.
                                    </p>
                                </div>
                            </div>

                            {{-- Progress Steps --}}
                            @php
                                $steps = match($selectedTool) {
                                    'video-explainer', 'lecture' => ['AI Slides & Scripts', 'Screenshots', 'Voice Narration', 'FFmpeg Encoding', 'Final MP4'],
                                    'powerpoint-generator'       => ['AI Content', 'Rendering Slides', 'PDF Export'],
                                    'mindmap-generator'          => ['AI Analysis', 'Building Graph'],
                                    'audio'                      => ['AI Script', 'Voice Synthesis'],
                                    'quiz-generator'             => ['AI Questions', 'Saving Quiz'],
                                    'video-animation'            => ['AI SVG Code', 'Rendering', 'MP4 Export'],
                                    default                      => ['Processing'],
                                };
                            @endphp
                            <div class="flex flex-col gap-2">
                                @foreach($steps as $i => $stepName)
                                    <div class="flex items-center gap-2 text-xs">
                                        <div class="w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0
                                            {{ $i === 0 ? 'bg-indigo-500 text-white animate-pulse' : 'bg-gray-200 dark:bg-gray-700 text-gray-500' }}">
                                            {{ $i + 1 }}
                                        </div>
                                        <span class="{{ $i === 0 ? 'text-indigo-700 dark:text-indigo-300 font-medium' : 'text-gray-400' }}">
                                            {{ $stepName }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>

                            <button
                                wire:click="cancelJob"
                                wire:loading.attr="disabled"
                                wire:target="cancelJob"
                                class="w-full py-2 text-sm text-gray-500 hover:text-red-500 transition text-center disabled:opacity-50"
                            >
                                <span wire:loading.remove wire:target="cancelJob">Cancel job</span>
                                <span wire:loading wire:target="cancelJob">Cancelling…</span>
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    @include('livewire.dashboard-result-modals')
</div>
