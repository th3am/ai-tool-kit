@extends('layouts.admin')

@section('title', 'Users')
@section('breadcrumb', 'Users')

@section('content')

{{-- Header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-white">Users</h1>
        <p class="text-sm text-gray-500 mt-1">Manage all registered users and their plans</p>
    </div>
</div>

{{-- Filters --}}
<div class="admin-card p-4 mb-5">
    <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[180px]">
            <label class="block text-xs text-gray-500 mb-1.5 font-medium">Search</label>
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-600 text-xs"></i>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Name, email, whatsapp..."
                       class="w-full pl-9 pr-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-sm text-white placeholder-gray-600 focus:outline-none focus:border-indigo-500 transition">
            </div>
        </div>
        <div class="min-w-[150px]">
            <label class="block text-xs text-gray-500 mb-1.5 font-medium">Plan</label>
            <select name="plan_id" class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-sm text-white focus:outline-none focus:border-indigo-500 transition">
                <option value="">All Plans</option>
                @foreach($plans as $plan)
                <option value="{{ $plan->id }}" {{ request('plan_id') == $plan->id ? 'selected' : '' }}>{{ $plan->name }}</option>
                @endforeach
                <option value="null" {{ request('plan_id') === 'null' ? 'selected' : '' }}>No Plan</option>
            </select>
        </div>
        <div class="min-w-[130px]">
            <label class="block text-xs text-gray-500 mb-1.5 font-medium">Role</label>
            <select name="role" class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-sm text-white focus:outline-none focus:border-indigo-500 transition">
                <option value="">All Roles</option>
                <option value="student" {{ request('role') === 'student' ? 'selected' : '' }}>Student</option>
                <option value="lecturer" {{ request('role') === 'lecturer' ? 'selected' : '' }}>Lecturer</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-lg transition">
                <i class="fas fa-search mr-1.5"></i> Filter
            </button>
            @if(request()->hasAny(['search','plan_id','role']))
            <a href="{{ route('admin.users.index') }}" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-300 text-sm font-medium rounded-lg transition">
                <i class="fas fa-times mr-1.5"></i> Clear
            </a>
            @endif
        </div>
    </form>
</div>

{{-- Table --}}
<div class="admin-card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full admin-table text-sm">
            <thead>
                <tr class="border-b border-gray-800 bg-gray-900/50">
                    <th class="text-left px-5 py-3 text-xs text-gray-500 font-semibold uppercase tracking-wider">User</th>
                    <th class="text-left px-4 py-3 text-xs text-gray-500 font-semibold uppercase tracking-wider hidden md:table-cell">Contact</th>
                    <th class="text-left px-4 py-3 text-xs text-gray-500 font-semibold uppercase tracking-wider">Plan</th>
                    <th class="text-left px-4 py-3 text-xs text-gray-500 font-semibold uppercase tracking-wider">Credits</th>
                    <th class="text-left px-4 py-3 text-xs text-gray-500 font-semibold uppercase tracking-wider hidden lg:table-cell">Status</th>
                    <th class="text-left px-4 py-3 text-xs text-gray-500 font-semibold uppercase tracking-wider hidden lg:table-cell">Joined</th>
                    <th class="text-right px-5 py-3 text-xs text-gray-500 font-semibold uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-800/50">
                @forelse($users as $user)
                <tr class="transition-colors duration-100">
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-indigo-600 to-purple-700 flex items-center justify-center text-sm font-bold text-white flex-shrink-0">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-white font-medium truncate">{{ $user->name }}</p>
                                <p class="text-xs text-gray-600 capitalize">{{ $user->role }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-4 hidden md:table-cell">
                        <p class="text-gray-400 text-xs truncate max-w-[180px]">{{ $user->email ?? '—' }}</p>
                        <p class="text-gray-600 text-xs">{{ $user->whatsapp_number }}</p>
                    </td>
                    <td class="px-4 py-4">
                        @if($user->plan)
                            <span class="badge badge-indigo">{{ $user->plan->name }}</span>
                        @else
                            <span class="badge badge-slate">Free</span>
                        @endif
                    </td>
                    <td class="px-4 py-4">
                        <div class="flex items-center gap-1.5">
                            <i class="fas fa-coins text-yellow-500/70 text-xs"></i>
                            <span class="text-white font-medium text-sm">{{ number_format($user->credits ?? 0) }}</span>
                        </div>
                        <p class="text-xs text-gray-600 mt-0.5">{{ number_format($user->credits_used ?? 0) }} used</p>
                    </td>
                    <td class="px-4 py-4 hidden lg:table-cell">
                        @if($user->is_verified)
                            <span class="badge badge-green"><i class="fas fa-check mr-1"></i> Verified</span>
                        @else
                            <span class="badge badge-yellow"><i class="fas fa-clock mr-1"></i> Pending</span>
                        @endif
                    </td>
                    <td class="px-4 py-4 hidden lg:table-cell">
                        <p class="text-gray-400 text-xs">{{ $user->created_at->format('M d, Y') }}</p>
                        <p class="text-gray-600 text-xs">{{ $user->created_at->diffForHumans() }}</p>
                    </td>
                    <td class="px-5 py-4 text-right">
                        <a href="{{ route('admin.users.show', $user) }}"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600/20 hover:bg-indigo-600/40 text-indigo-400 hover:text-indigo-300 border border-indigo-500/30 rounded-lg text-xs font-medium transition">
                            <i class="fas fa-eye text-xs"></i> View
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-5 py-12 text-center text-gray-600">
                        <i class="fas fa-users text-3xl mb-3 block opacity-30"></i>
                        No users found
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($users->hasPages())
    <div class="px-5 py-4 border-t border-gray-800 flex items-center justify-between">
        <p class="text-xs text-gray-600">
            Showing {{ $users->firstItem() }}–{{ $users->lastItem() }} of {{ $users->total() }} users
        </p>
        <div class="flex gap-1">
            @if($users->onFirstPage())
                <span class="px-3 py-1.5 bg-gray-800 text-gray-600 rounded-lg text-xs cursor-not-allowed"><i class="fas fa-chevron-left"></i></span>
            @else
                <a href="{{ $users->previousPageUrl() }}" class="px-3 py-1.5 bg-gray-800 hover:bg-gray-700 text-gray-400 rounded-lg text-xs transition"><i class="fas fa-chevron-left"></i></a>
            @endif

            @foreach($users->getUrlRange(max(1, $users->currentPage()-2), min($users->lastPage(), $users->currentPage()+2)) as $page => $url)
                <a href="{{ $url }}" class="px-3 py-1.5 rounded-lg text-xs transition {{ $page == $users->currentPage() ? 'bg-indigo-600 text-white' : 'bg-gray-800 hover:bg-gray-700 text-gray-400' }}">{{ $page }}</a>
            @endforeach

            @if($users->hasMorePages())
                <a href="{{ $users->nextPageUrl() }}" class="px-3 py-1.5 bg-gray-800 hover:bg-gray-700 text-gray-400 rounded-lg text-xs transition"><i class="fas fa-chevron-right"></i></a>
            @else
                <span class="px-3 py-1.5 bg-gray-800 text-gray-600 rounded-lg text-xs cursor-not-allowed"><i class="fas fa-chevron-right"></i></span>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection
