@extends('layouts.admin')

@section('title', 'Plans')
@section('breadcrumb', 'Subscription Plans')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-white">Subscription Plans</h1>
        <p class="text-sm text-gray-500 mt-1">Manage pricing plans and features</p>
    </div>
    <a href="{{ route('admin.plans.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-medium text-sm rounded-xl transition">
        <i class="fas fa-plus text-xs"></i> New Plan
    </a>
</div>

{{-- Plans Grid --}}
<div class="grid md:grid-cols-2 xl:grid-cols-3 gap-5">
    @forelse($plans as $plan)
    @php
        $gradients = [
            'slate'  => 'from-slate-800 to-slate-900',
            'indigo' => 'from-indigo-900/60 to-slate-900',
            'purple' => 'from-purple-900/60 to-slate-900',
            'emerald'=> 'from-emerald-900/60 to-slate-900',
            'rose'   => 'from-rose-900/60 to-slate-900',
        ];
        $borderColors = [
            'slate'  => 'border-slate-700',
            'indigo' => 'border-indigo-500/40',
            'purple' => 'border-purple-500/40',
            'emerald'=> 'border-emerald-500/40',
            'rose'   => 'border-rose-500/40',
        ];
        $iconColors = [
            'slate'  => 'text-slate-400 bg-slate-700/50',
            'indigo' => 'text-indigo-400 bg-indigo-500/20',
            'purple' => 'text-purple-400 bg-purple-500/20',
            'emerald'=> 'text-emerald-400 bg-emerald-500/20',
            'rose'   => 'text-rose-400 bg-rose-500/20',
        ];
        $gradient   = $gradients[$plan->color]   ?? $gradients['slate'];
        $border     = $borderColors[$plan->color] ?? $borderColors['slate'];
        $iconColor  = $iconColors[$plan->color]   ?? $iconColors['slate'];
    @endphp
    <div class="rounded-2xl border {{ $border }} bg-gradient-to-br {{ $gradient }} p-6 flex flex-col relative overflow-hidden">

        {{-- Active Badge --}}
        <div class="absolute top-4 right-4">
            @if($plan->is_active)
                <span class="badge badge-green"><span class="w-1.5 h-1.5 bg-emerald-400 rounded-full mr-1.5 inline-block"></span>Active</span>
            @else
                <span class="badge badge-red">Inactive</span>
            @endif
        </div>

        {{-- Icon & Name --}}
        <div class="w-12 h-12 rounded-xl {{ $iconColor }} flex items-center justify-center mb-4">
            <i class="fas fa-tag text-lg"></i>
        </div>
        <h3 class="text-xl font-bold text-white">{{ $plan->name }}</h3>
        <p class="text-sm text-gray-500 mt-1 mb-4">{{ $plan->description ?? 'No description' }}</p>

        {{-- Price & Credits --}}
        <div class="flex items-end gap-4 mb-4 pb-4 border-b border-white/10">
            <div>
                <p class="text-3xl font-extrabold text-white">${{ number_format($plan->price, 2) }}</p>
                <p class="text-xs text-gray-500">/ month</p>
            </div>
            <div class="mb-1">
                <p class="text-lg font-bold text-yellow-400">{{ number_format($plan->credits) }}</p>
                <p class="text-xs text-gray-500">credits</p>
            </div>
        </div>

        {{-- Features --}}
        @if($plan->features && count($plan->features) > 0)
        <ul class="space-y-2 flex-1 mb-5">
            @foreach(array_slice($plan->features, 0, 5) as $feature)
            <li class="flex items-start gap-2 text-sm text-gray-300">
                <i class="fas fa-check-circle text-emerald-500 text-xs mt-0.5 flex-shrink-0"></i>
                {{ $feature }}
            </li>
            @endforeach
            @if(count($plan->features) > 5)
            <li class="text-xs text-gray-600">+ {{ count($plan->features) - 5 }} more features</li>
            @endif
        </ul>
        @endif

        {{-- Subscribers --}}
        <div class="flex items-center justify-between text-sm mb-5">
            <span class="text-gray-500">Subscribers</span>
            <span class="text-white font-bold">{{ number_format($plan->users_count) }}</span>
        </div>

        {{-- Actions --}}
        <div class="flex gap-2 mt-auto">
            <a href="{{ route('admin.plans.edit', $plan) }}"
               class="flex-1 text-center py-2 bg-white/5 hover:bg-white/10 border border-white/10 text-gray-300 hover:text-white rounded-lg text-sm font-medium transition">
                <i class="fas fa-pen mr-1.5"></i> Edit
            </a>
            @if($plan->users_count === 0)
            <form method="POST" action="{{ route('admin.plans.destroy', $plan) }}"
                  onsubmit="return confirm('Delete the {{ $plan->name }} plan? This cannot be undone.')">
                @csrf @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-red-500/10 hover:bg-red-500/20 border border-red-500/20 text-red-400 hover:text-red-300 rounded-lg text-sm font-medium transition">
                    <i class="fas fa-trash"></i>
                </button>
            </form>
            @endif
        </div>
    </div>
    @empty
    <div class="col-span-3 admin-card py-16 text-center text-gray-600">
        <i class="fas fa-tags text-4xl mb-4 block opacity-30"></i>
        <p>No plans yet. <a href="{{ route('admin.plans.create') }}" class="text-indigo-400 hover:text-indigo-300">Create one</a></p>
    </div>
    @endforelse
</div>
@endsection
