@extends('layouts.mobile', ['title' => 'Profile'])

@section('content')
<div x-data="profilePage()" x-init="init()">

    <div class="page-header">
        <h1>Profile</h1>
    </div>

    {{-- Skeleton --}}
    <div class="p-4 flex flex-col gap-4" x-show="loading" style="display:none;">
        <div class="flex items-center gap-4">
            <div class="skeleton w-16 h-16 rounded-full flex-shrink-0"></div>
            <div class="flex flex-col gap-2 flex-1">
                <div class="skeleton h-5 w-[60%] rounded-lg"></div>
                <div class="skeleton h-4 w-[80%] rounded-lg"></div>
            </div>
        </div>
        <div class="skeleton h-24 rounded-2xl"></div>
        <div class="skeleton h-32 rounded-2xl"></div>
    </div>

    {{-- Error --}}
    <div class="error-state" x-show="error && !loading" style="display:none;">
        <span class="text-5xl">⚠️</span>
        <h3>Failed to load</h3>
        <p x-text="error"></p>
        <button class="btn btn-ghost btn-sm mt-2" @click="init()">Retry</button>
    </div>

    {{-- Content --}}
    <div x-show="!loading && !error" style="display:none;" class="fade-in pb-8">

        {{-- User Card --}}
        <div class="p-4">
            <div class="card flex items-center gap-4 p-5">
                <div class="w-16 h-16 rounded-full bg-accent-gradient flex items-center justify-center font-extrabold text-2xl text-white flex-shrink-0 shadow-glow-sm"
                     x-text="user ? user.name?.[0]?.toUpperCase() : 'U'">U</div>
                <div class="flex flex-col flex-1 min-w-0">
                    <p class="font-bold text-[17px] text-white truncate" x-text="user?.name"></p>
                    <p class="text-sm text-white/50 truncate" x-text="user?.email"></p>
                    <div class="mt-2.5">
                        <span class="badge badge-gray" x-text="user?.role || 'user'"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Credits & Plan --}}
        <div class="px-4 pb-4">
            <div class="credits-card flex items-center">
                <div class="flex-1">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-brand-400/80 mb-1.5">Credits Balance</p>
                    <div class="text-[36px] font-extrabold leading-none text-white" x-text="user?.credits || 0">0</div>
                    <p class="text-xs text-white/50 mt-1.5" x-text="(user?.credits_used || 0) + ' total used'"></p>
                </div>
                <div class="text-right flex flex-col items-end">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-brand-400/80 mb-1.5">Current Plan</p>
                    <div x-show="user?.current_plan" style="display:none;">
                        <span class="badge badge-purple" x-text="user?.current_plan?.name || 'Free'"></span>
                    </div>
                    <div x-show="!user?.current_plan">
                        <span class="badge badge-gray">Free Plan</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Upgrade Plans --}}
        <div x-show="upgradePlans.length > 0" style="display:none;">
            <p class="section-label">Upgrade Plan</p>
            <div class="px-4 flex flex-col gap-3">
                <template x-for="plan in upgradePlans" :key="plan.id">
                    <div class="card flex items-center gap-3 p-5">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <p class="font-bold text-[15px] text-white" x-text="plan.name"></p>
                                <div class="badge" x-show="plan.color" :style="'background:' + plan.color + '22;color:' + plan.color">Premium</div>
                            </div>
                            <p class="text-xs text-white/50" x-text="plan.credits + ' credits per month'"></p>
                            <p class="text-xs text-white/30 mt-1 line-clamp-2" x-text="plan.description || ''"></p>
                        </div>
                        <div class="text-right flex-shrink-0 pl-2">
                            <p class="font-bold text-lg text-brand-400" x-text="'$' + plan.price"></p>
                            <p class="text-[10px] uppercase font-bold text-white/30 tracking-wider mt-0.5">/ mo</p>
                        </div>
                    </div>
                </template>
                <p class="text-xs text-white/30 text-center mt-2">Contact support to upgrade your plan.</p>
            </div>
        </div>

        {{-- Logout --}}
        <div class="px-4 mt-8">
            <button class="btn btn-danger btn-full" @click="$store.app.logout()">
                🚪 Sign Out
            </button>
        </div>

    </div>

</div>

@push('scripts')
<script>
function profilePage() {
    return {
        loading: true, error: '',
        user: null, upgradePlans: [],

        async init() {
            this.loading = true; this.error = '';
            try {
                const res = await Api.get('/user');
                this.user = res;
                this.upgradePlans = res.upgrade_plans || [];
            } catch(e) {
                if (e.status !== 401) this.error = e.message || 'Failed to load profile.';
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>
@endpush
@endsection
