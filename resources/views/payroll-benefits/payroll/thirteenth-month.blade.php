@extends('layouts.app')

@php
    $pageTitle = '13th Month Pay';
    $currentPage = 'payroll.thirteenth-month';
@endphp

@section('content')

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-extrabold font-outfit text-gray-900">13th Month Pay Computation & Approval</h1>
            <p class="text-xs text-gray-500 mt-0.5">Automated pro-rated bonus calculation based on active months worked. Sent to Admin for budget release.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-xs font-semibold text-purple-600 bg-purple-50 border border-purple-200 px-3 py-1.5 rounded-full">
                Annual Benefit Allocation Stream
            </span>
        </div>
    </div>

    <!-- Main Container with Alpine.js State -->
    <div class="bg-white rounded-2xl border border-gray-100 p-6 mb-8" 
         x-data="{ 
            selected: [], 
            selectAll: false,
            toggleSelectAll(ids) {
                this.selectAll = !this.selectAll;
                this.selected = this.selectAll ? ids : [];
            }
         }">

        <!-- 13th Month Batch & Workflow Header Toolbar -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-gray-100 pb-6 mb-6">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <h2 class="text-sm font-bold font-outfit text-gray-900">Active Year: {{ $year }}</h2>
                    <span class="text-[10px] font-extrabold px-2.5 py-0.5 rounded-full border {{ $batch->status->badgeClasses() }}">
                        {{ $batch->status->label() }}
                    </span>
                </div>
                <p class="text-[10px] text-gray-400">
                    @if(in_array($batch->status->value, ['budget_requested', 'budget_received', 'released']))
                        Total 13th Month Budget Requested: <strong class="text-gray-900 font-extrabold">₱{{ number_format($batch->total_amount, 2) }}</strong>
                    @else
                        Calculate annual pro-rated 13th-month bonuses, request budget funding from Finance, and release employee payouts.
                    @endif
                </p>
            </div>
            
            <div class="flex flex-wrap items-center gap-3">
                <!-- Re-Run Computation Button -->
                <form action="{{ route('payroll.thirteenth-month.compute') }}" method="POST">
                    @csrf
                    <input type="hidden" name="year" value="{{ $year }}">
                    <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs px-3.5 py-2 rounded-xl transition-all shadow-xs flex items-center gap-1.5 whitespace-nowrap shrink-0 border border-gray-200">
                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Compute 13th Month Pay
                    </button>
                </form>

                <!-- DYNAMIC WORKFLOW STEP BUTTON -->
                @if($batch->status->value === 'draft')
                    <!-- Step 1: Submit for Admin -->
                    <form action="{{ route('payroll.thirteenth-month.workflow.submit-admin', $year) }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-[#F44336] hover:bg-[#D32F2F] text-white font-bold text-xs px-5 py-2.5 rounded-xl transition-all shadow-md flex items-center gap-2 whitespace-nowrap shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                            Submit for Admin
                        </button>
                    </form>

                @elseif($batch->status->value === 'pending_admin')
                    <!-- Step 2: Admin Approves -->
                    <form action="{{ route('payroll.thirteenth-month.workflow.approve-admin', $year) }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs px-5 py-2.5 rounded-xl transition-all shadow-md flex items-center gap-2 whitespace-nowrap shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Approve Batch (Admin)
                        </button>
                    </form>

                @elseif($batch->status->value === 'approved')
                    <!-- Step 3: Request Budget from Financial -->
                    <form action="{{ route('payroll.thirteenth-month.workflow.request-budget', $year) }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs px-5 py-2.5 rounded-xl transition-all shadow-md flex items-center gap-2 whitespace-nowrap shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            Request Budget
                        </button>
                    </form>

                @elseif($batch->status->value === 'budget_requested')
                    <!-- Step 4: Money Received from Financial -->
                    <form action="{{ route('payroll.thirteenth-month.workflow.receive-budget', $year) }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs px-5 py-2.5 rounded-xl transition-all shadow-md flex items-center gap-2 whitespace-nowrap shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Mark Budget Received (₱{{ number_format($batch->total_amount, 2) }})
                        </button>
                    </form>

                @elseif($batch->status->value === 'budget_received')
                    <!-- Step 5: Release Payroll -->
                    <form action="{{ route('payroll.thirteenth-month.workflow.release', $year) }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-5 py-2.5 rounded-xl transition-all shadow-md flex items-center gap-2 whitespace-nowrap shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Release 13th Month
                        </button>
                    </form>

                @elseif($batch->status->value === 'released')
                    <!-- Step 6: Released State -->
                    <div class="bg-emerald-50 text-emerald-800 border border-emerald-200 text-xs font-bold px-4 py-2 rounded-xl flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        13th Month Released & Paid
                    </div>
                @endif
            </div>
        </div>

        <!-- Search & Filter Toolbar -->
        <form action="{{ route('payroll.thirteenth-month') }}" method="GET" class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <input type="hidden" name="year" value="{{ $year }}">
            <div class="flex flex-1 items-center gap-3">
                <div class="relative flex-1 max-w-xs">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search employee for 13th month..." class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl pl-9 pr-3.5 py-2 text-gray-800 focus:outline-none focus:border-[#F44336]">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <select name="department" onchange="this.form.submit()" class="text-xs font-semibold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-[#F44336]">
                    <option value="all" {{ $deptId == 'all' ? 'selected' : '' }}>All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ $deptId == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
                @if($search || ($deptId && $deptId !== 'all'))
                    <a href="{{ route('payroll.thirteenth-month', ['year' => $year]) }}" class="text-xs text-gray-500 hover:text-red-500 underline font-medium">Clear</a>
                @endif
            </div>
            <span class="text-xs text-gray-400">Showing {{ $computations->count() }} of {{ $computations->total() }} Records</span>
        </form>

        <!-- 13th Month Computation Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-100 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                        <th class="py-3 px-4 w-10">
                            <input type="checkbox" @click="toggleSelectAll({{ json_encode($computations->pluck('id')) }})" :checked="selectAll" class="rounded text-[#F44336] focus:ring-0">
                        </th>
                        <th class="py-3 px-4">Employee</th>
                        <th class="py-3 px-4">Monthly Base Salary</th>
                        <th class="py-3 px-4">Months Worked</th>
                        <th class="py-3 px-4">Calculation Formula</th>
                        <th class="py-3 px-4">Computed 13th Month Pay</th>
                        <th class="py-3 px-4 text-right">Approval Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 text-xs">
                    
                    @forelse($computations as $comp)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="py-3.5 px-4">
                                <input type="checkbox" value="{{ $comp->id }}" x-model="selected" class="rounded text-[#F44336] focus:ring-0">
                            </td>
                            <td class="py-3.5 px-4 font-bold text-gray-900">
                                <div>{{ $comp->employee->first_name ?? '' }} {{ $comp->employee->last_name ?? '' }}</div>
                                <span class="text-[10px] text-gray-400 font-normal">{{ $comp->employee->department->name ?? 'General' }} • {{ $comp->months_worked == 12 ? 'Full Year' : 'Pro-Rated' }}</span>
                            </td>
                            <td class="py-3.5 px-4 font-semibold text-gray-700">₱{{ number_format($comp->monthly_salary, 2) }} / mo</td>
                            <td class="py-3.5 px-4 font-bold {{ $comp->months_worked == 12 ? 'text-emerald-600' : 'text-blue-600' }}">
                                {{ $comp->months_worked }} Months ({{ round(($comp->months_worked / 12) * 100) }}%)
                            </td>
                            <td class="py-3.5 px-4 font-mono text-gray-500">₱{{ number_format($comp->monthly_salary, 0) }} × {{ $comp->months_worked }} / 12</td>
                            <td class="py-3.5 px-4 font-extrabold text-emerald-600 text-sm">₱{{ number_format($comp->amount, 2) }}</td>
                            <td class="py-3.5 px-4 text-right">
                                <span class="px-2.5 py-1 font-bold rounded-lg text-[10px] {{ $batch->status->badgeClasses() }}">
                                    {{ $batch->status->label() }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-gray-400">
                                <p class="mb-3 text-xs">No 13th Month Pay records calculated yet for Year {{ $year }}.</p>
                                <form action="{{ route('payroll.thirteenth-month.compute') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="year" value="{{ $year }}">
                                    <button type="submit" class="bg-[#F44336] hover:bg-[#D32F2F] text-white font-bold text-xs px-4 py-2 rounded-xl transition-all shadow-sm">
                                        Calculate 13th Month Pay for {{ $year }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforelse

                </tbody>
            </table>
        </div>

        <!-- Pagination Controls Footer -->
        <div class="mt-6">
            {{ $computations->links() }}
        </div>

    </div>

@endsection
