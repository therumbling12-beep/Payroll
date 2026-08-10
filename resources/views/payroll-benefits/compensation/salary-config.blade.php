@extends('layouts.app')

@php
    $pageTitle = 'Salary Configuration';
    $currentPage = 'compensation.salary-config';
@endphp

@section('content')

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-extrabold font-outfit text-gray-900">Salary Ranges & Configuration</h1>
            <p class="text-xs text-gray-500 mt-0.5">Determine compensation brackets by position hierarchy and tenure growth multipliers.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="flex items-center gap-1.5 text-xs font-semibold text-emerald-600 bg-emerald-50 border border-emerald-200 px-3 py-1.5 rounded-full">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                Payroll Integration Active
            </span>
            <span class="text-xs text-gray-400">{{ now()->format('D, M j Y') }}</span>
        </div>
    </div>

    @if(session('status'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-2xl font-bold flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session('status') }}
        </div>
    @endif

    <!-- Official Company Salary Grade Brackets Matrix (Janitor to Executive) -->
    <div class="bg-white rounded-2xl border border-gray-100 p-6 mb-8 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-base font-extrabold font-outfit text-gray-900">Official Company Salary Grade Matrix</h2>
                <p class="text-xs text-gray-400">Position brackets ranging from lowest entry-level (Janitor) to executive management.</p>
            </div>
            <span class="px-3 py-1 bg-red-50 text-[#F44336] text-xs font-bold rounded-full">Compensation Planning Standard</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($salaryGrades as $grade)
                <div class="bg-gray-50 rounded-2xl p-4 border border-gray-200/60 hover:border-red-300 transition-colors">
                    <div class="flex justify-between items-start mb-2">
                        <span class="font-bold text-xs text-gray-900 font-outfit">{{ $grade->position_name }}</span>
                        <span class="px-2 py-0.5 text-[10px] font-extrabold bg-emerald-100 text-emerald-800 rounded-full">+{{ $grade->annual_growth_rate }}%/yr</span>
                    </div>
                    <div class="space-y-1 text-xs">
                        <div class="flex justify-between text-gray-500">
                            <span>Minimum Floor:</span>
                            <span class="font-semibold text-gray-800">₱{{ number_format($grade->min_salary, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-500">
                            <span>Maximum Ceiling:</span>
                            <span class="font-semibold text-gray-800">₱{{ number_format($grade->max_salary, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-500 pt-1 border-t border-gray-200">
                            <span>Band Midpoint:</span>
                            <span class="font-bold text-indigo-600">₱{{ number_format(($grade->min_salary + $grade->max_salary) / 2, 2) }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Interactive Search & Filter Form -->
    <form action="{{ route('compensation.salary-config') }}" method="GET" class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div class="flex flex-1 items-center gap-3">
            <div class="relative flex-1 max-w-xs">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search employee name..." class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl pl-9 pr-3.5 py-2 text-gray-800 focus:outline-none focus:border-[#F44336]">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            <select name="department" onchange="this.form.submit()" class="text-xs font-semibold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-[#F44336]">
                <option value="all">All Departments</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ (string)$deptId === (string)$dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                @endforeach
            </select>

            <button type="submit" class="bg-gray-900 text-white text-xs font-bold px-3.5 py-2 rounded-xl">Filter</button>
        </div>
    </form>

    <!-- Active Employee Base Rates Table -->
    <div class="bg-white rounded-2xl border border-gray-100 p-6 mb-8 shadow-sm">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-sm font-bold text-gray-900 font-outfit">Employee Compensation Roster</h3>
                <p class="text-xs text-gray-400">Current configured rates and assigned payment modes.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="text-gray-400 font-semibold border-b border-gray-100 pb-2">
                        <th class="pb-2">Employee</th>
                        <th class="pb-2">Department</th>
                        <th class="pb-2">Position</th>
                        <th class="pb-2">Daily Rate</th>
                        <th class="pb-2">Monthly Base</th>
                        <th class="pb-2">Payment Mode</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($employees as $emp)
                        <tr>
                            <td class="py-3 font-bold text-gray-900">
                                {{ $emp->first_name }} {{ $emp->last_name }}
                                <span class="block text-[10px] text-gray-400 font-normal font-mono">{{ $emp->employee_code }}</span>
                            </td>
                            <td class="py-3 text-gray-600">{{ $emp->department->name ?? 'General' }}</td>
                            <td class="py-3 font-medium text-gray-800">{{ $emp->position }}</td>
                            <td class="py-3 font-mono">₱{{ number_format($emp->daily_rate ?? 0, 2) }}</td>
                            <td class="py-3 font-mono font-bold text-gray-900">₱{{ number_format($emp->monthly_rate ?? 0, 2) }}</td>
                            <td class="py-3 capitalize">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ strtolower($emp->payment_method ?? 'bank') === 'bank' ? 'bg-blue-50 text-blue-700' : 'bg-amber-50 text-amber-700' }}">
                                    {{ $emp->payment_method ?? 'bank' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-4 text-center text-gray-400">No employee compensation records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $employees->links() }}
        </div>
    </div>

@endsection
