@extends('layouts.auth')

@section('title', 'Database Health Check')

@section('content')
<div class="min-h-screen flex items-center justify-center px-6">
    <div class="max-w-md w-full text-center">
        @if($status === 'ok')
            <div class="w-16 h-16 mx-auto rounded-full bg-emerald-100 flex items-center justify-center mb-6">
                <span class="material-symbols-outlined text-3xl text-emerald-600">check_circle</span>
            </div>
            <h1 class="text-2xl font-extrabold text-gray-900 mb-2">Database Connected</h1>
        @else
            <div class="w-16 h-16 mx-auto rounded-full bg-red-100 flex items-center justify-center mb-6">
                <span class="material-symbols-outlined text-3xl text-red-600">error</span>
            </div>
            <h1 class="text-2xl font-extrabold text-gray-900 mb-2">Connection Failed</h1>
        @endif

        <p class="text-gray-500 text-sm font-medium">
            Response time: <span class="font-bold text-gray-900">{{ $ms }} ms</span>
        </p>
    </div>
</div>
@endsection
