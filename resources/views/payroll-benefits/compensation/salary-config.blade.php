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
            <p class="text-xs text-gray-500 mt-0.5">Determine compensation brackets by position hierarchy and tenure multipliers.</p>
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

    <!-- Overview KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        <div class="bg-white rounded-2xl border border-gray-100 p-4 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between mb-3">
                <div class="w-9 h-9 rounded-xl bg-red-50 flex items-center justify-center text-[#F44336]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
                <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded-full">Active</span>
            </div>
            <p class="text-2xl font-extrabold font-outfit text-gray-900">{{ $employees->total() }} Employees</p>
            <p class="text-xs text-gray-500 mt-0.5">Configured Base Salary Records</p>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 p-4 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between mb-3">
                <div class="w-9 h-9 rounded-xl bg-red-50 flex items-center justify-center text-[#F44336]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
                <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded-full">+3.5% / Year</span>
            </div>
            <p class="text-2xl font-extrabold font-outfit text-gray-900">Tenure Multiplier</p>
            <p class="text-xs text-gray-500 mt-0.5">Annual Experience Growth Factor</p>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 p-4 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between mb-3">
                <div class="w-9 h-9 rounded-xl bg-red-50 flex items-center justify-center text-[#F44336]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-[10px] font-bold text-gray-600 bg-gray-100 px-1.5 py-0.5 rounded-full">Observer Active</span>
            </div>
            <p class="text-2xl font-extrabold font-outfit text-gray-900">100% Synced</p>
            <p class="text-xs text-gray-500 mt-0.5">Feeds directly to Payroll Module</p>
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

    <!-- Salary Brackets & Active Employee Base Rates Table -->
    <div class="bg-white rounded-2xl border border-gray-100 p-6 mb-8 shadow-sm">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-sm font-bold font-outfit text-gray-900">Active Base Salary Configurations</h2>
                <p class="text-[10px] text-gray-400">Current employee rates used by the automated payroll engine</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-100 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                        <th class="py-3 px-4">Employee</th>
                        <th class="py-3 px-4">Position Title</th>
                        <th class="py-3 px-4">Department</th>
                        <th class="py-3 px-4">Daily Rate</th>
                        <th class="py-3 px-4">Monthly Rate</th>
                        <th class="py-3 px-4 text-right">Quick Rate Adjustment</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 text-xs">
                    @forelse($employees as $emp)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="py-3.5 px-4 font-bold text-gray-900">
                                <div>{{ $emp->first_name }} {{ $emp->last_name }}</div>
                                <span class="text-[10px] text-gray-400 font-normal">{{ $emp->employee_code }}</span>
                            </td>
                            <td class="py-3.5 px-4 font-semibold text-gray-700">{{ $emp->position }}</td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 bg-blue-50 text-blue-700 font-bold rounded-md text-[10px]">{{ $emp->department?->name }}</span>
                            </td>
                            <td class="py-3.5 px-4 font-extrabold text-emerald-600">₱{{ number_format((float)$emp->daily_rate, 2) }} / day</td>
                            <td class="py-3.5 px-4 font-extrabold text-emerald-600">₱{{ number_format((float)$emp->monthly_rate, 2) }} / mo</td>
                            <td class="py-3.5 px-4 text-right">
                                <form action="{{ route('compensation.adjustments.store') }}" method="POST" class="flex items-center justify-end gap-2">
                                    @csrf
                                    <input type="hidden" name="employee_id" value="{{ $emp->id }}">
                                    <input type="hidden" name="type" value="salary_config">
                                    <input type="hidden" name="reason" value="Manual Base Rate Re-Configuration">
                                    
                                    <input type="number" step="0.01" name="new_rate" placeholder="New Rate" required class="w-24 text-xs bg-gray-50 border border-gray-200 rounded-lg px-2 py-1 text-gray-800 focus:outline-none focus:border-[#F44336]">
                                    <button type="submit" class="bg-gray-900 hover:bg-gray-800 text-white font-bold text-[10px] px-2.5 py-1 rounded-lg transition-colors">
                                        Propose Rate
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-gray-400 text-xs">No employee salary configurations found.</td>
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
