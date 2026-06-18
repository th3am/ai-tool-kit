@extends('layouts.admin')

@section('title', 'Edit Plan')
@section('breadcrumb', 'Plans / Edit')

@section('content')

<div class="mb-4">
    <a href="{{ route('admin.plans.index') }}" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-300 transition">
        <i class="fas fa-arrow-left text-xs"></i> Back to Plans
    </a>
</div>

<div class="max-w-2xl">
    <div class="admin-card p-6">
        <h1 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
            <i class="fas fa-pen text-indigo-400"></i> Edit Plan: {{ $plan->name }}
        </h1>

        <form method="POST" action="{{ route('admin.plans.update', $plan) }}" class="space-y-5">
            @csrf @method('PUT')
            @include('admin.plans._form', ['plan' => $plan])
            <div class="pt-2 flex gap-3">
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-medium text-sm rounded-xl transition">
                    <i class="fas fa-save mr-1.5"></i> Save Changes
                </button>
                <a href="{{ route('admin.plans.index') }}" class="px-6 py-2.5 bg-gray-700 hover:bg-gray-600 text-gray-300 font-medium text-sm rounded-xl transition">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
