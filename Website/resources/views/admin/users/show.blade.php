@extends('layouts.admin')

@section('title', $user->name)
@section('breadcrumb', 'Users / ' . $user->name)

@section('content')

<div class="mb-4">
    <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-300 transition">
        <i class="fas fa-arrow-left text-xs"></i> Back to Users
    </a>
</div>

<div class="grid lg:grid-cols-3 gap-5">

    {{-- Left: Profile + Add Credits --}}
    <div class="space-y-4">

        {{-- Profile Card --}}
        <div class="admin-card p-6">
            <div class="flex flex-col items-center text-center mb-6">
                <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-700 flex items-center justify-center text-3xl font-bold text-white mb-4 shadow-lg">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <h2 class="text-xl font-bold text-white">{{ $user->name }}</h2>
                <p class="text-sm text-gray-500 mt-1">{{ $user->email ?? $user->whatsapp_number }}</p>
                <div class="flex items-center gap-2 mt-3">
                    @if($user->is_verified)
                        <span class="badge badge-green"><i class="fas fa-check mr-1"></i> Verified</span>
                    @else
                        <span class="badge badge-yellow"><i class="fas fa-clock mr-1"></i> Pending</span>
                    @endif
                    <span class="badge badge-slate capitalize">{{ $user->role }}</span>
                </div>
            </div>

            <div class="space-y-3 text-sm">
                <div class="flex items-center justify-between py-2 border-t border-gray-800">
                    <span class="text-gray-500">Credits Remaining</span>
                    <span class="text-white font-bold">{{ number_format($user->credits ?? 0) }}</span>
                </div>
                <div class="flex items-center justify-between py-2 border-t border-gray-800">
                    <span class="text-gray-500">Credits Used</span>
                    <span class="text-yellow-400 font-medium">{{ number_format($user->credits_used ?? 0) }}</span>
                </div>
                <div class="flex items-center justify-between py-2 border-t border-gray-800">
                    <span class="text-gray-500">Plan</span>
                    @if($user->plan)
                        <span class="badge badge-indigo">{{ $user->plan->name }}</span>
                    @else
                        <span class="badge badge-slate">No Plan</span>
                    @endif
                </div>
                @if($user->plan_expires_at)
                <div class="flex items-center justify-between py-2 border-t border-gray-800">
                    <span class="text-gray-500">Expires</span>
                    <span class="text-sm text-gray-300">{{ $user->plan_expires_at->format('M d, Y') }}</span>
                </div>
                @endif
                <div class="flex items-center justify-between py-2 border-t border-gray-800">
                    <span class="text-gray-500">Joined</span>
                    <span class="text-gray-300">{{ $user->created_at->format('M d, Y') }}</span>
                </div>
                <div class="flex items-center justify-between py-2 border-t border-gray-800">
                    <span class="text-gray-500">WhatsApp</span>
                    <span class="text-gray-300 text-xs">{{ $user->whatsapp_number }}</span>
                </div>
                @if($user->country_name)
                <div class="flex items-center justify-between py-2 border-t border-gray-800">
                    <span class="text-gray-500">Country</span>
                    <span class="text-gray-300">{{ $user->country_name }}</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Add Credits --}}
        <div class="admin-card p-5">
            <h3 class="text-sm font-semibold text-white mb-4 flex items-center gap-2">
                <i class="fas fa-coins text-yellow-500"></i> Add Credits
            </h3>
            <form method="POST" action="{{ route('admin.users.add-credits', $user) }}">
                @csrf
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1.5">Amount</label>
                        <input type="number" name="amount" min="1" max="10000" placeholder="e.g. 100"
                               class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-sm text-white focus:outline-none focus:border-indigo-500 transition">
                        @error('amount') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1.5">Reason (optional)</label>
                        <input type="text" name="reason" placeholder="e.g. Compensation, promo"
                               class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-sm text-white focus:outline-none focus:border-indigo-500 transition">
                    </div>
                    <button type="submit" class="w-full py-2 bg-yellow-600/80 hover:bg-yellow-600 text-white font-medium text-sm rounded-lg transition">
                        <i class="fas fa-plus mr-1.5"></i> Add Credits
                    </button>
                </div>
            </form>
        </div>

        {{-- Danger Zone --}}
        @if(!$user->isAdmin())
        <div class="admin-card p-5 border-red-500/20">
            <h3 class="text-sm font-semibold text-red-400 mb-3 flex items-center gap-2">
                <i class="fas fa-triangle-exclamation"></i> Danger Zone
            </h3>
            <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                  onsubmit="return confirm('Are you sure you want to delete {{ $user->name }}? This cannot be undone.')">
                @csrf @method('DELETE')
                <button type="submit" class="w-full py-2 bg-red-600/20 hover:bg-red-600/40 text-red-400 border border-red-500/30 font-medium text-sm rounded-lg transition">
                    <i class="fas fa-trash mr-1.5"></i> Delete User
                </button>
            </form>
        </div>
        @endif
    </div>

    {{-- Right: Edit + Jobs --}}
    <div class="lg:col-span-2 space-y-5">

        {{-- Edit Form --}}
        <div class="admin-card p-6">
            <h3 class="text-base font-semibold text-white mb-5 flex items-center gap-2">
                <i class="fas fa-pen text-indigo-400 text-sm"></i> Edit User
            </h3>
            <form method="POST" action="{{ route('admin.users.update', $user) }}">
                @csrf @method('PUT')
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1.5 font-medium">Name</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                               class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-sm text-white focus:outline-none focus:border-indigo-500 transition">
                        @error('name') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1.5 font-medium">Email</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}"
                               class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-sm text-white focus:outline-none focus:border-indigo-500 transition">
                        @error('email') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1.5 font-medium">Role</label>
                        <select name="role" class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-sm text-white focus:outline-none focus:border-indigo-500 transition">
                            <option value="student"  {{ old('role', $user->role) === 'student'  ? 'selected' : '' }}>Student</option>
                            <option value="lecturer" {{ old('role', $user->role) === 'lecturer' ? 'selected' : '' }}>Lecturer</option>
                            <option value="admin"    {{ old('role', $user->role) === 'admin'    ? 'selected' : '' }}>Admin</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1.5 font-medium">Subscription Plan</label>
                        <select name="plan_id" class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-sm text-white focus:outline-none focus:border-indigo-500 transition">
                            <option value="">No Plan (Free)</option>
                            @foreach($plans as $plan)
                            <option value="{{ $plan->id }}" {{ old('plan_id', $user->plan_id) == $plan->id ? 'selected' : '' }}>
                                {{ $plan->name }} — {{ $plan->credits }} credits
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1.5 font-medium">Credits Remaining</label>
                        <input type="number" name="credits" value="{{ old('credits', $user->credits ?? 0) }}" min="0"
                               class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-sm text-white focus:outline-none focus:border-indigo-500 transition">
                        @error('credits') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1.5 font-medium">Plan Expires At</label>
                        <input type="date" name="plan_expires_at"
                               value="{{ old('plan_expires_at', optional($user->plan_expires_at)->format('Y-m-d')) }}"
                               class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-sm text-white focus:outline-none focus:border-indigo-500 transition">
                        @error('plan_expires_at') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="mt-5">
                    <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-medium text-sm rounded-lg transition">
                        <i class="fas fa-save mr-1.5"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>

        {{-- Tool Jobs History --}}
        <div class="admin-card overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-800">
                <h3 class="text-base font-semibold text-white flex items-center gap-2">
                    <i class="fas fa-history text-indigo-400 text-sm"></i> Tool Job History
                    <span class="ml-auto badge badge-slate">{{ $user->toolJobs->count() }} shown</span>
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full admin-table text-sm">
                    <thead>
                        <tr class="border-b border-gray-800 bg-gray-900/40">
                            <th class="text-left px-5 py-3 text-xs text-gray-500 font-semibold uppercase">Tool</th>
                            <th class="text-left px-4 py-3 text-xs text-gray-500 font-semibold uppercase">Status</th>
                            <th class="text-left px-4 py-3 text-xs text-gray-500 font-semibold uppercase hidden md:table-cell">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800/50">
                        @forelse($user->toolJobs as $job)
                        <tr>
                            <td class="px-5 py-3">
                                <span class="text-white font-medium capitalize">{{ str_replace('-', ' ', $job->tool_type) }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $badgeClass = match($job->status) {
                                        'succeeded' => 'badge-green',
                                        'failed'    => 'badge-red',
                                        'queued'    => 'badge-yellow',
                                        'running'   => 'badge-blue',
                                        default     => 'badge-slate',
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ $job->status }}</span>
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell">
                                <p class="text-gray-400 text-xs">{{ $job->created_at->format('M d, Y H:i') }}</p>
                                <p class="text-gray-600 text-xs">{{ $job->created_at->diffForHumans() }}</p>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-5 py-8 text-center text-gray-600 text-sm">No jobs yet</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection
