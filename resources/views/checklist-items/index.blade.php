@extends('layouts.app')

@section('title', 'Question Bank')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-800 transition">Dashboard</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('settings.index') }}" class="hover:text-gray-800 transition">Settings</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <span class="text-gray-900 font-bold">Question Bank</span>
@endsection

@section('content')
<div class="max-w-5xl mx-auto">

    <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-4 mb-6 flex flex-wrap gap-2">
        @foreach($components as $code => $comp)
            <a href="{{ route('checklist-items.index', ['component' => $code]) }}"
               class="px-4 py-2 rounded-xl text-xs font-bold transition {{ $code === $activeCode ? 'bg-eids-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                {{ $code }}
            </a>
        @endforeach
    </div>

    <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
            <h2 class="font-extrabold text-gray-900 text-sm">{{ $components[$activeCode]->name ?? $activeCode }} — {{ $items->count() }} question(s)</h2>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200 text-xs text-gray-500 uppercase tracking-wider font-bold">
                <tr>
                    <th class="px-6 py-3 text-left w-12">#</th>
                    <th class="px-4 py-3 text-left">Question</th>
                    <th class="px-4 py-3 text-left">Group</th>
                    <th class="px-4 py-3 text-left">Type</th>
                    <th class="px-4 py-3 text-center">Guide</th>
                    <th class="px-6 py-3 text-right">Edit</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($items as $item)
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-6 py-3 text-gray-400 font-mono">{{ $item->sort_order }}</td>
                        <td class="px-4 py-3 text-gray-900 font-medium">{{ $item->question_text }}</td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ $item->defect_group }}</td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ $item->input_type === 'numeric_with_tolerance' ? 'Numeric (' . $item->tolerance_text . ')' : 'Pass/Fail' }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($item->has_guide)
                                <span class="text-emerald-700 font-bold text-xs">Yes</span>
                            @else
                                <span class="text-gray-300 text-xs">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-right">
                            <a href="{{ route('checklist-items.edit', $item) }}" class="text-xs font-bold text-eids-accent hover:underline">Edit</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
