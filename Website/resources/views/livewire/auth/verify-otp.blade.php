<div class="contents">
    <div class="flex-1 text-center lg:text-left mb-10 lg:mb-0 px-4 sm:px-6 lg:mt-[95px] md:px-8 lg:pr-12">
        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold mb-4 lg:-mt-[105px] bg-gradient-to-r from-[#B58BF6] via-[#6366f1] to-[#2ACDF0] dark:from-[#B58BF6] dark:to-[#2ACDF0] bg-clip-text text-transparent transition-colors duration-500">
            EduTech Platform
        </h1>
        <h2 class="text-xl sm:text-2xl font-semibold mb-4">Start your learning journey</h2>
    </div>

    <div class="right-form w-full md:w-[60%] lg:w-[45%] backdrop-blur-3xl bg-white/50 mx-auto lg:mx-0 mt-[50px] lg:mt-[40px] rounded-2xl border border-gray-200 dark:border-white/5 dark:bg-white/10 transition-all duration-700 ease-in-out" 
         x-data="otpForm()"
         @otp-resent.window="startTimer()">
        <div class="icon-check rounded-full mx-auto text-center ">
            <i class="fa-solid fa-circle-check text-[60px] -mt-[30px] bg-gradient-to-r from-[#B58BF6] via-[#6366f1] to-[#2ACDF0] bg-clip-text text-transparent"></i>
        </div>
        <p class="capitalize text-center mt-[45px] block font-semibold text-3xl">Enter OTP code</p>
        <p class="text-center mt-[20px] text-gray-500 dark:text-gray-300 text-md leading-tight">Please enter the verification code we sent to your </p>
        <p class="text-center mt-[5px] text-gray-700 dark:text-gray-400 mb-[20px] md:mb-0">WhatsApp number: <span class="font-bold">{{ $whatsapp_number }}</span></p>
        
        <form wire:submit="verify" class="text-center">
            <div class="input-field flex justify-center gap-2 lg:gap-2 mt-[5px] md:mt-[45px] lg:mt-[25px]">
                @for($i = 0; $i < 6; $i++)
                <input 
                    type="number" 
                    id="otp-{{ $i }}"
                    x-model="digits[{{ $i }}]"
                    @input="handleInput({{ $i }}, $event)"
                    @keydown.backspace="handleBackspace({{ $i }}, $event)"
                    @paste="handlePaste($event)"
                    class="text-[25px] w-[14%] lg:w-[65px] h-[65px] bg-[#DBDDE9] dark:bg-white/10 dark:text-white text-gray-800 rounded-lg no-spinner text-center outline-none border border-[1px] border-[#ddd] dark:border-none focus:ring-2 focus:ring-blue-500 transition"
                    maxlength="1"
                >
                @endfor
            </div>
            
            @error('otp') <p class="text-red-500 text-sm mt-3">{{ $message }}</p> @enderror

            <button type="button" @click="submit" class="verify capitalize mt-[20px] mb-0 mb-[25px] font-semibold md:mb-[35px] lg:mt-[20px] w-[95%] lg:w-[85%] bg-gradient-to-r from-[#3b82f6] to-[#6366f1] hover:from-[#2563eb] hover:to-[#4f46e5] text-white text-lg rounded-lg py-3 font-semibold transition-all duration-[1s] shadow-lg disabled:opacity-50 relative">
                 <span wire:loading.remove>Confirm</span>
                 <span wire:loading><i class="fas fa-spinner fa-spin"></i> Verifying...</span>
            </button>
            
            <div class="mt-4">
                <button type="button" wire:click="resend" :disabled="timer > 0" 
                    class="text-sm transition-colors duration-200"
                    :class="timer > 0 ? 'text-gray-400 cursor-not-allowed' : 'text-blue-500 hover:text-blue-600 underline'">
                    <span x-show="timer > 0">Resend OTP in <span x-text="formatTime(timer)"></span></span>
                    <span x-show="timer === 0">Resend OTP</span>
                </button>
            </div>
        </form>
    </div>

    <script>
        function otpForm() {
            return {
                digits: ['', '', '', '', '', ''],
                otpFull: '',
                timer: 120, 
                interval: null,

                init() {
                    this.startTimer();
                },

                startTimer() {
                    this.timer = 120;
                    if(this.interval) clearInterval(this.interval);
                    this.interval = setInterval(() => {
                        if (this.timer > 0) {
                            this.timer--;
                        } else {
                            clearInterval(this.interval);
                        }
                    }, 1000);
                },

                formatTime(seconds) {
                    const minutes = Math.floor(seconds / 60);
                    const partInSeconds = seconds % 60;
                    return `${minutes}:${partInSeconds.toString().padStart(2, '0')}`;
                },
                
                handleInput(index, event) {
                    const value = event.target.value;
                    if (!/^\d*$/.test(value)) {
                        this.digits[index] = '';
                        return;
                    }
                    if (value.length > 1) {
                        this.digits[index] = value.substring(0, 1);
                    }
                    if (this.digits[index] !== '' && index < 5) {
                        this.$nextTick(() => {
                            document.getElementById('otp-' + (index + 1)).focus();
                        });
                    }
                    this.syncOtp();
                },
                
                handleBackspace(index, event) {
                    if (this.digits[index] === '' && index > 0) {
                        this.$nextTick(() => {
                            document.getElementById('otp-' + (index - 1)).focus();
                        });
                    }
                    this.syncOtp();
                },

                handlePaste(event) {
                    event.preventDefault();
                    const pasteData = event.clipboardData.getData('text').trim();
                    if (!/^\d+$/.test(pasteData)) return;

                    const values = pasteData.split('').slice(0, 6);
                    values.forEach((val, i) => {
                        this.digits[i] = val;
                    });
                    
                    const nextIndex = Math.min(values.length, 5);
                    this.$nextTick(() => {
                         document.getElementById('otp-' + (nextIndex === 6 ? 5 : nextIndex)).focus();
                    });
                    this.syncOtp();
                },

                syncOtp() {
                    this.otpFull = this.digits.join('');
                    // Sync with Livewire property 'otp'
                    @this.set('otp', this.otpFull);
                },

                submit() {
                    this.syncOtp();
                    if(this.otpFull.length !== 6) {
                        window.showToast('Please enter a complete 6-digit OTP', 'error');
                        return;
                    }
                    @this.verify(); // Call Livewire method
                }
            }
        }
    </script>
</div>
