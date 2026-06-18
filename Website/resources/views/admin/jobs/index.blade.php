@extends('layouts.admin')

@section('title', 'Tool Jobs')
@section('breadcrumb', 'Tool Jobs')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-white">Tool Jobs</h1>
        <p class="text-sm text-gray-500 mt-1">Monitor all AI generation jobs across users</p>
    </div>
</div>

{{-- Filters --}}
<div class="admin-card p-4 mb-5">
    <form method="GET" action="{{ route('admin.jobs.index') }}" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[160px]">
            <label class="block text-xs text-gray-500 mb-1.5 font-medium">Search User</label>
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-600 text-xs"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Name or email..."
                       class="w-full pl-9 pr-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-sm text-white placeholder-gray-600 focus:outline-none focus:border-indigo-500 transition">
            </div>
        </div>
        <div class="min-w-[150px]">
            <label class="block text-xs text-gray-500 mb-1.5 font-medium">Tool Type</label>
            <select name="tool_type" class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-sm text-white focus:outline-none focus:border-indigo-500 transition">
                <option value="">All Tools</option>
                @foreach($toolTypes as $type)
                <option value="{{ $type }}" {{ request('tool_type') === $type ? 'selected' : '' }}>
                    {{ ucwords(str_replace('-', ' ', $type)) }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="min-w-[130px]">
            <label class="block text-xs text-gray-500 mb-1.5 font-medium">Status</label>
            <select name="status" class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-sm text-white focus:outline-none focus:border-indigo-500 transition">
                <option value="">All Statuses</option>
                @foreach($statuses as $status)
                <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-[140px]">
            <label class="block text-xs text-gray-500 mb-1.5 font-medium">From</label>
            <input type="date" name="from" value="{{ request('from') }}"
                   class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-sm text-white focus:outline-none focus:border-indigo-500 transition">
        </div>
        <div class="min-w-[140px]">
            <label class="block text-xs text-gray-500 mb-1.5 font-medium">To</label>
            <input type="date" name="to" value="{{ request('to') }}"
                   class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-sm text-white focus:outline-none focus:border-indigo-500 transition">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-lg transition">
                <i class="fas fa-filter mr-1.5"></i> Filter
            </button>
            @if(request()->hasAny(['search','tool_type','status','from','to']))
            <a href="{{ route('admin.jobs.index') }}" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-300 text-sm font-medium rounded-lg transition">
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
                    <th class="text-left px-5 py-3 text-xs text-gray-500 font-semibold uppercase tracking-wider">ID</th>
                    <th class="text-left px-4 py-3 text-xs text-gray-500 font-semibold uppercase tracking-wider">User</th>
                    <th class="text-left px-4 py-3 text-xs text-gray-500 font-semibold uppercase tracking-wider">Tool</th>
                    <th class="text-left px-4 py-3 text-xs text-gray-500 font-semibold uppercase tracking-wider">Status</th>
                    <th class="text-left px-4 py-3 text-xs text-gray-500 font-semibold uppercase tracking-wider hidden lg:table-cell">Error</th>
                    <th class="text-left px-4 py-3 text-xs text-gray-500 font-semibold uppercase tracking-wider hidden md:table-cell">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-800/50">
                @forelse($jobs as $job)
                <tr class="transition-colors duration-100">
                    <td class="px-5 py-3">
                        <span class="text-gray-500 font-mono text-xs">#{{ $job->id }}</span>
                    </td>
                    <td class="px-4 py-3">
                        @if($job->user)
                        <a href="{{ route('admin.users.show', $job->user) }}" class="flex items-center gap-2 hover:text-indigo-400 transition group">
                            <div class="w-7 h-7 rounded-full bg-gradient-to-br from-indigo-600 to-purple-700 flex items-center justify-center text-xs font-bold text-white flex-shrink-0">
                                {{ strtoupper(substr($job->user->name, 0, 1)) }}
                            </div>
                            <span class="text-gray-300 group-hover:text-indigo-400 text-sm truncate max-w-[120px]">{{ $job->user->name }}</span>
                        </a>
                        @else
                        <span class="text-gray-600 text-xs">Deleted User</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $toolColors = [
                                'mindmap'        => 'badge-purple',
                                'audio'          => 'badge-blue',
                                'video-animation'=> 'badge-yellow',
                                'presentation'   => 'badge-indigo',
                                'video-explainer'=> 'badge-green',
                                'lecture'        => 'badge-slate',
                                'quiz'           => 'badge-red',
                            ];
                        @endphp
                        <span class="badge {{ $toolColors[$job->tool_type] ?? 'badge-slate' }}">
                            {{ ucwords(str_replace('-', ' ', $job->tool_type)) }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $statusBadge = match($job->status) {
                                'succeeded' => 'badge-green',
                                'failed'    => 'badge-red',
                                'queued'    => 'badge-yellow',
                                'running'   => 'badge-blue',
                                default     => 'badge-slate',
                            };
                        @endphp
                        <span class="badge {{ $statusBadge }}">{{ $job->status }}</span>
                    </td>
                    <td class="px-4 py-3 hidden lg:table-cell">
                        @if($job->error_message)
                        <span class="text-xs text-red-400 truncate max-w-[200px] block" title="{{ $job->error_message }}">
                            {{ Str::limit($job->error_message, 50) }}
                        </span>
                        @else
                        <span class="text-gray-700 text-xs">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 hidden md:table-cell">
                        <p class="text-gray-400 text-xs">{{ $job->created_at->format('M d, Y H:i') }}</p>
                        <p class="text-gray-600 text-xs">{{ $job->created_at->diffForHumans() }}</p>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-12 text-center text-gray-600">
                        <i class="fas fa-bolt text-3xl mb-3 block opacity-20"></i>
                        No jobs found
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($jobs->hasPages())
    <div class="px-5 py-4 border-t border-gray-800 flex items-center justify-between">
        <p class="text-xs text-gray-600">
            Showing {{ $jobs->firstItem() }}–{{ $jobs->lastItem() }} of {{ $jobs->total() }} jobs
        </p>
        <div class="flex gap-1">
            @if($jobs->onFirstPage())
                <span class="px-3 py-1.5 bg-gray-800 text-gray-600 rounded-lg text-xs cursor-not-allowed"><i class="fas fa-chevron-left"></i></span>
            @else
                <a href="{{ $jobs->previousPageUrl() }}" class="px-3 py-1.5 bg-gray-800 hover:bg-gray-700 text-gray-400 rounded-lg text-xs transition"><i class="fas fa-chevron-left"></i></a>
            @endif
            @foreach($jobs->getUrlRange(max(1, $jobs->currentPage()-2), min($jobs->lastPage(), $jobs->currentPage()+2)) as $page => $url)
                <a href="{{ $url }}" class="px-3 py-1.5 rounded-lg text-xs transition {{ $page == $jobs->currentPage() ? 'bg-indigo-600 text-white' : 'bg-gray-800 hover:bg-gray-700 text-gray-400' }}">{{ $page }}</a>
            @endforeach
            @if($jobs->hasMorePages())
                <a href="{{ $jobs->nextPageUrl() }}" class="px-3 py-1.5 bg-gray-800 hover:bg-gray-700 text-gray-400 rounded-lg text-xs transition"><i class="fas fa-chevron-right"></i></a>
            @else
                <span class="px-3 py-1.5 bg-gray-800 text-gray-600 rounded-lg text-xs cursor-not-allowed"><i class="fas fa-chevron-right"></i></span>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection
