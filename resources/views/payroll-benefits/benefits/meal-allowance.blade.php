@extends('layouts.app')

@php
    $pageTitle = 'Meal Allowance Subsidy';
    $currentPage = 'benefits.meal-allowance';
@endphp

@section('content')

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-black font-outfit text-gray-900 tracking-tight">Meal Allowance Subsidy Management</h1>
            <p class="text-xs text-gray-500 mt-1">Configurable daily meal assistance for drivers and operations crew with statutory BIR RR 11-2018 De Minimis exemption rules.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('benefits.meal-allowance.export', ['cutoff' => $activeCutoff, 'department_id' => $departmentId]) }}" 
               class="inline-flex items-center gap-2 text-xs font-bold text-gray-700 bg-white border border-gray-200 px-3.5 py-2 rounded-xl shadow-2xs hover:bg-gray-50 transition-all">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export Roster CSV
            </a>
            <span class="text-xs text-gray-400 font-semibold font-mono">{{ now()->format('M j, Y') }}</span>
        </div>
    </div>

    @if(session('status'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-2xl font-bold flex items-center gap-2 shadow-2xs">
            <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session('status') }}
        </div>
    @endif

    <!-- Main Container with Alpine.js State -->
    <div x-data="{
        showBatchModal: false,
        showConfigModal: false,
        batchCutoff: '{{ $activeCutoff }}',
        batchDailyRate: '{{ $stats['meal_daily_rate'] }}',
        batchDeptId: '{{ $departmentId ?: '' }}',
    }" class="space-y-6 pb-12">

        <!-- 4 Summary Stat Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Standard Daily Subsidy</p>
                <p class="text-2xl font-black font-outfit text-emerald-600 mt-1">PHP {{ number_format($stats['meal_daily_rate'], 2) }}</p>
                <p class="text-[11px] text-gray-500 mt-1">Cap: ₱{{ number_format($stats['meal_de_minimis_weekly_cap'], 2) }}/week De Minimis</p>
            </div>

            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Eligible Active Drivers</p>
                <p class="text-2xl font-black font-outfit text-indigo-600 mt-1">{{ $stats['total_drivers'] }} Drivers</p>
                <p class="text-[11px] text-gray-500 mt-1">Auto-included in weekly payroll</p>
            </div>

            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Est. Weekly Outlay</p>
                <p class="text-2xl font-black font-outfit text-amber-600 mt-1">PHP {{ number_format($stats['est_weekly_outlay'], 2) }}</p>
                <p class="text-[11px] text-gray-500 mt-1">Based on 6 shifts/week benchmark</p>
            </div>

            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Cutoff Outlay ({{ $activeCutoff }})</p>
                <p class="text-2xl font-black font-outfit text-emerald-700 mt-1">PHP {{ number_format($stats['total_gross_outlay'], 2) }}</p>
                <p class="text-[11px] text-gray-500 mt-1">{{ $stats['total_disbursements_count'] }} employee records computed</p>
            </div>
        </div>

        <!-- Filter Controls & Batch Workflow Bar -->
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-4 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-3">
            <form action="{{ route('benefits.meal-allowance') }}" method="GET" class="flex flex-wrap items-center gap-3 flex-1">
                <div class="relative flex-1 min-w-[180px]">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search employee..." 
                           class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl pl-9 pr-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-gray-900">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                <select name="department_id" class="text-xs bg-gray-50 border border-gray-200 rounded-xl px-3 py-2.5 text-gray-800 font-bold focus:outline-none focus:border-gray-900">
                    <option value="all">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ $departmentId == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>

                <div class="flex items-center gap-1.5 bg-gray-50 border border-gray-200 rounded-xl px-3 py-1">
                    <span class="text-[10px] font-bold text-gray-400 uppercase">Cutoff:</span>
                    <input type="text" name="cutoff" value="{{ $activeCutoff }}" placeholder="YYYY-MM-DD" class="text-xs font-mono font-bold bg-transparent border-none text-gray-900 focus:outline-none w-28">
                </div>

                <button type="submit" class="bg-gray-900 hover:bg-black text-white text-xs font-black px-4 py-2.5 rounded-xl transition-all shadow-sm">
                    Filter Roster
                </button>
            </form>

            <div class="flex flex-wrap items-center gap-2 flex-shrink-0">
                <button @click="showBatchModal = true" type="button" 
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-black text-xs px-3.5 py-2.5 rounded-xl transition-all shadow-sm flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Batch Compute
                </button>

                @if($stats['pending_count'] > 0)
                    <form action="{{ route('benefits.meal-allowance.approve') }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="cutoff_period" value="{{ $activeCutoff }}">
                        <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white font-black text-xs px-3.5 py-2.5 rounded-xl transition-all shadow-sm flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Approve Batch ({{ $stats['pending_count'] }})
                        </button>
                    </form>
                @endif

                @if($stats['approved_count'] > 0)
                    <form action="{{ route('benefits.meal-allowance.release') }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="cutoff_period" value="{{ $activeCutoff }}">
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs px-3.5 py-2.5 rounded-xl transition-all shadow-sm flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Push to Payroll ({{ $stats['approved_count'] }})
                        </button>
                    </form>
                @endif

                <button @click="showConfigModal = true" type="button" 
                        class="bg-gray-900 hover:bg-black text-white font-black text-xs px-3.5 py-2.5 rounded-xl transition-all shadow-sm flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    </svg>
                    Policy
                </button>
            </div>
        </div>

        <!-- Meal Allowance Table -->
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-gray-50/80 border-b border-gray-100 text-[10px] font-black text-gray-400 uppercase tracking-wider">
                            <th class="py-3.5 px-4">Employee</th>
                            <th class="py-3.5 px-4">Role / Department</th>
                            <th class="py-3.5 px-4 text-center">Daily Rate</th>
                            <th class="py-3.5 px-4 text-center">Shifts Rendered</th>
                            <th class="py-3.5 px-4 text-right">Gross Allowance</th>
                            <th class="py-3.5 px-4 text-right">Tax-Exempt De Minimis</th>
                            <th class="py-3.5 px-4 text-right">Taxable Excess</th>
                            <th class="py-3.5 px-4 text-center">Disbursement Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($roster as $row)
                            <tr class="hover:bg-gray-50/60 transition-colors">
                                <!-- Employee -->
                                <td class="py-3.5 px-4 font-mono">
                                    <div class="font-bold text-gray-900">{{ $row['employee']->first_name }} {{ $row['employee']->last_name }}</div>
                                    <div class="text-[10px] text-gray-400">{{ $row['employee']->employee_code }}</div>
                                </td>

                                <!-- Department / Position -->
                                <td class="py-3.5 px-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-700">
                                        {{ $row['employee']->department?->name ?? 'General Fleet' }}
                                    </span>
                                    <div class="text-[11px] text-gray-500 mt-0.5">{{ $row['employee']->position }}</div>
                                </td>

                                <!-- Daily Rate -->
                                <td class="py-3.5 px-4 text-center font-mono font-bold text-emerald-700">
                                    PHP {{ number_format($row['daily_rate'], 2) }}
                                </td>

                                <!-- Shifts Rendered -->
                                <td class="py-3.5 px-4 text-center font-mono font-bold text-gray-800">
                                    <span class="px-2.5 py-0.5 rounded-full bg-gray-100 text-gray-800 text-[10px]">
                                        {{ $row['days_rendered'] }} Shifts
                                    </span>
                                </td>

                                <!-- Gross Allowance -->
                                <td class="py-3.5 px-4 text-right font-mono font-black font-outfit text-sm text-gray-900">
                                    PHP {{ number_format($row['gross_amount'], 2) }}
                                </td>

                                <!-- Tax-Exempt De Minimis -->
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-emerald-600">
                                    PHP {{ number_format($row['tax_exempt_amount'], 2) }}
                                </td>

                                <!-- Taxable Excess -->
                                <td class="py-3.5 px-4 text-right font-mono font-bold {{ $row['taxable_excess_amount'] > 0 ? 'text-rose-600' : 'text-gray-400' }}">
                                    PHP {{ number_format($row['taxable_excess_amount'], 2) }}
                                </td>

                                <!-- Disbursement Status -->
                                <td class="py-3.5 px-4 text-center">
                                    @if($row['status'] === 'released_to_payroll')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Disbursed to Payroll
                                        </span>
                                    @elseif($row['status'] === 'approved')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-indigo-50 text-indigo-700 border border-indigo-200">
                                            HR Approved
                                        </span>
                                    @elseif($row['status'] === 'pending')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                            Pending Review
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-500 border border-gray-200">
                                            Unprocessed
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-12 text-gray-400 text-xs">No employee records found for Cutoff {{ $activeCutoff }}.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($employees->hasPages())
                <div class="p-4 border-t border-gray-100">
                    {{ $employees->links() }}
                </div>
            @endif
        </div>

        <!-- De Minimis Tax Compliance Card -->
        <div class="p-4 bg-amber-50 rounded-2xl border border-amber-200 text-amber-900 text-xs flex items-start gap-3">
            <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <p class="font-bold">De Minimis Tax Exemption Standard (BIR Revenue Regulations No. 11-2018)</p>
                <p class="text-[11px] text-amber-800/80 mt-0.5">
                    Daily meal allowances for overtime work and night duty shifts not exceeding twenty-five percent (25%) of the basic minimum wage or up to PHP {{ number_format($stats['meal_de_minimis_weekly_cap'], 2) }}/week are considered non-taxable de minimis benefits. Any excess above the statutory threshold is accounted under taxable compensation.
                </p>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: BATCH COMPUTE MEAL ALLOWANCE -->
        <!-- ========================================================================= -->
        <div x-show="showBatchModal" x-cloak class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div @click.away="showBatchModal = false" class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-base font-black font-outfit text-gray-900">Batch Compute Meal Allowances</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Generate disbursements based on attendance/trip logs</p>
                    </div>
                    <button @click="showBatchModal = false" type="button" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
                </div>

                <form action="{{ route('benefits.meal-allowance.generate') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Payroll Cutoff Period *</label>
                        <input type="text" name="cutoff_period" x-model="batchCutoff" required class="w-full text-xs font-mono font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:border-gray-900">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Daily Meal Subsidy Rate (PHP) *</label>
                        <input type="number" step="0.5" min="0" max="2000" name="daily_rate" x-model="batchDailyRate" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:border-gray-900">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Department Filter (Optional)</label>
                        <select name="department_id" x-model="batchDeptId" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-gray-800">
                            <option value="">All Departments (Entire Fleet)</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                        <button @click="showBatchModal = false" type="button" class="text-xs font-bold text-gray-500 px-4 py-2">Cancel</button>
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-black text-xs px-5 py-2.5 rounded-xl transition-all shadow-sm">Compute Batch</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: CONFIGURE MEAL ALLOWANCE POLICY -->
        <!-- ========================================================================= -->
        <div x-show="showConfigModal" x-cloak class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div @click.away="showConfigModal = false" class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-base font-black font-outfit text-gray-900">Configure Meal Allowance Policy</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Dynamic rates and BIR De Minimis threshold</p>
                    </div>
                    <button @click="showConfigModal = false" type="button" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
                </div>

                <form action="{{ route('benefits.meal-allowance.settings') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Daily Meal Subsidy (PHP) *</label>
                        <input type="number" step="0.5" min="0" max="2000" name="meal_allowance_daily" value="{{ $stats['meal_daily_rate'] }}" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:border-gray-900">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Weekly De Minimis Ceiling (PHP) *</label>
                        <input type="number" step="10" min="0" max="10000" name="meal_de_minimis_weekly_cap" value="{{ $stats['meal_de_minimis_weekly_cap'] }}" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:border-gray-900">
                        <p class="text-[10px] text-gray-400 mt-1">Statutory non-taxable ceiling under BIR RR 11-2018.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Disbursement Schedule</label>
                        <input type="text" name="meal_allowance_schedule" value="{{ $stats['meal_schedule'] ?? 'Weekly per Cutoff' }}" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Eligibility Description</label>
                        <input type="text" name="meal_allowance_eligibility" value="{{ $stats['meal_eligibility'] ?? 'All Active Drivers & Operations Crew' }}" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="meal_allowance_driver_auto" value="1" id="meal_driver_auto" {{ ($stats['meal_driver_auto'] ?? true) ? 'checked' : '' }} class="rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                        <label for="meal_driver_auto" class="text-xs font-bold text-gray-700">Auto-apply subsidy for all active drivers</label>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                        <button @click="showConfigModal = false" type="button" class="text-xs font-bold text-gray-500 px-4 py-2">Cancel</button>
                        <button type="submit" class="bg-gray-900 hover:bg-black text-white font-black text-xs px-5 py-2.5 rounded-xl transition-all shadow-sm">Save Policy Settings</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

@endsection
