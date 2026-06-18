@extends('layouts.auth')

@section('content')
<div class="w-full text-center" x-data="dashboard()">
    <h1 class="text-4xl font-bold mb-4 text-red-500">Admin Dashboard</h1>
    <div class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow-lg inline-block border-2 border-red-500">
        <p class="text-lg">Welcome, <span class="font-bold">{{ auth()->user()->name }}</span>!</p>
        <p class="mb-4">You have full access.</p>
        <button @click="logout" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition">Logout</button>
    </div>
</div>

<script>
    function dashboard() {
        return {
            logout() {
                 window.location.href = "{{ route('login') }}";
            }
        }
    }
</script>
@endsection
