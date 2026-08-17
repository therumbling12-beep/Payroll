@extends('layouts.app')

@php
    $pageTitle = 'Salary Computation — ' . $cutoff;
    $currentPage = 'payroll.salary-computation';
@endphp

@section('content')

    <!-- Page Header & Breadcrumb -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('payroll.salary-computation') }}" class="text-xs font-bold text-gray-500 hover:text-[#F44336] transition-colors flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Back to Cutoff Runs
                </a>
            </div>
            <h1 class="text-xl font-extrabold font-outfit text-gray-900 mt-1">Batch Salary Computation & Audit</h1>
            <p class="text-xs text-gray-500 mt-0.5">Period: <span class="font-mono font-bold text-gray-800">{{ $cutoff }}</span> • Automated statutory calculation, trip quota incentives, holiday/OT pay, and loan amortizations.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-black bg-emerald-50 text-emerald-800 border border-emerald-200">
                <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                DOLE 2026 Engine Active
            </span>
        </div>
    </div>

    <!-- Main Container with Alpine.js State -->
    <div class="space-y-6" 
         x-data="{ 
            activeTab: 'table',
            selected: [], 
            selectAll: false,
            showManualModal: false,
            showBreakdownModal: false,
            showAiInsightModal: false,
            activeLog: null,
            activeComp: null,
            breakdownTab: 'summary',
            transparencyData: null,
            loadingTransparency: false,
            isDriver(comp) {
                if (!comp || !comp.employee) return false;
                const pos = (comp.employee.position || '').toLowerCase();
                const dept = (comp.employee.department?.name || '').toLowerCase();
                return pos.includes('driver') || dept.includes('fleet');
            },
            openBreakdown(comp) {
                this.activeComp = comp;
                this.breakdownTab = 'summary';
                this.transparencyData = null;
                this.loadingTransparency = true;
                this.showBreakdownModal = true;
                fetch('{{ url('/payroll/computations') }}/' + comp.id + '/transparency')
                    .then(r => r.json())
                    .then(d => {
                        this.transparencyData = d;
                        this.loadingTransparency = false;
                    })
                    .catch(() => {
                        this.loadingTransparency = false;
                    });
            },
            openAiInsight(log, comp = null) {
                this.activeLog = log;
                this.activeComp = comp;
                this.showAiInsightModal = true;
            },
            openOverrideModal(comp = null) {
                if (comp) {
                    this.activeOverride = {
                        employee_id: comp.employee_id || '',
                        cutoff_period: comp.cutoff_period || '{{ $cutoff }}',
                        base_pay: comp.base_pay || '',
                        trip_earnings: comp.trip_earnings || '',
                        driver_trip_incentive: comp.driver_trip_incentive || '',
                        holiday_pay: comp.holiday_pay || '',
                        overtime_pay: comp.overtime_pay || '',
                        night_diff_pay: comp.night_diff_pay || '',
                        performance_bonus: comp.performance_bonus || '',
                        sss_deduction: comp.sss_deduction || '',
                        philhealth_deduction: comp.philhealth_deduction || '',
                        pagibig_deduction: comp.pagibig_deduction || '',
                        loan_deduction: comp.loan_deduction || '',
                        tardiness_deduction: comp.tardiness_deduction || '',
                        undertime_deduction: comp.undertime_deduction || '',
                        platform_fee_deduction: comp.platform_fee_deduction || ''
                    };
                } else {
                    this.activeOverride = {
                        employee_id: '',
                        cutoff_period: '{{ $cutoff }}',
                        base_pay: '',
                        trip_earnings: '',
                        driver_trip_incentive: '',
                        holiday_pay: '',
                        overtime_pay: '',
                        night_diff_pay: '',
                        performance_bonus: '',
                        sss_deduction: '',
                        philhealth_deduction: '',
                        pagibig_deduction: '',
                        loan_deduction: '',
                        tardiness_deduction: '',
                        undertime_deduction: '',
                        platform_fee_deduction: ''
                    };
                }
                this.showManualModal = true;
            },
            toggleSelectAll(ids) {
                this.selectAll = !this.selectAll;
                this.selected = this.selectAll ? ids : [];
            }
         }">

        <!-- Header Workflow & Actions Bar -->
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Payroll Batch Status:</span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black border {{ $batch->status->badgeClasses() }}">
                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                        {{ $batch->status->label() }}
                    </span>
                </div>
                <p class="text-xs text-gray-500 font-medium">
                    @if(in_array($batch->status->value, ['budget_requested', 'budget_received', 'released']))
                        Total Net Payout Requested: <strong class="text-gray-900 font-black font-outfit text-sm">PHP {{ number_format((float)$batch->total_net_pay, 2) }}</strong>
                    @else
                        Total Calculated Net: <strong class="text-gray-900 font-black font-outfit text-sm">PHP {{ number_format((float)$computations->sum('net_pay'), 2) }}</strong> across {{ $computations->total() }} records.
                    @endif
                </p>
            </div>
            
            <div class="flex flex-wrap items-center gap-3">
                <!-- Re-Run Payroll Run Button -->
                <form action="{{ route('payroll.batch-compute') }}" method="POST">
                    @csrf
                    <input type="hidden" name="period" value="{{ $cutoff }}">
                    <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold text-xs px-4 py-2.5 rounded-xl transition-all shadow-xs flex items-center gap-1.5 border border-gray-200">
                        <svg class="w-3.5 h-3.5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Re-Calculate Batch
                    </button>
                </form>

                <!-- Manual Entry Button -->
                <button type="button" @click="openOverrideModal(null)" 
                        class="bg-gray-900 hover:bg-black text-white font-bold text-xs px-4 py-2.5 rounded-xl transition-all shadow-xs flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Manual Salary Override
                </button>

                <!-- DYNAMIC WORKFLOW ACTION BUTTON -->
                @if($batch->status->value === 'draft')
                    <!-- Step 1: Submit to Admin -->
                    <form action="{{ route('payroll.workflow.submit-admin', $cutoff) }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-[#F44336] hover:bg-[#D32F2F] text-white font-black text-xs px-5 py-2.5 rounded-xl transition-all shadow-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                            Submit to Admin for Approval
                        </button>
                    </form>

                @elseif($batch->status->value === 'pending_admin')
                    <!-- Step 2: Admin Approves -->
                    <form action="{{ route('payroll.workflow.approve-admin', $cutoff) }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white font-black text-xs px-5 py-2.5 rounded-xl transition-all shadow-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Approve Batch (Admin Sign-off)
                        </button>
                    </form>

                @elseif($batch->status->value === 'approved')
                    <!-- Step 3: Request Budget from Financial Management -->
                    <form action="{{ route('payroll.workflow.request-budget', $cutoff) }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-black text-xs px-5 py-2.5 rounded-xl transition-all shadow-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            Request Financial Budget
                        </button>
                    </form>

                @elseif($batch->status->value === 'budget_requested')
                    <!-- Step 4: Budget Received / Funded -->
                    <form action="{{ route('payroll.workflow.receive-budget', $cutoff) }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-black text-xs px-5 py-2.5 rounded-xl transition-all shadow-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Confirm Budget Received (PHP {{ number_format((float)$batch->total_net_pay, 2) }})
                        </button>
                    </form>

                @elseif($batch->status->value === 'budget_received')
                    <!-- Step 5: Release Payroll -->
                    <form action="{{ route('payroll.workflow.release', $cutoff) }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs px-5 py-2.5 rounded-xl transition-all shadow-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Release Payroll to Personnel
                        </button>
                    </form>

                @elseif($batch->status->value === 'released')
                    <div class="bg-emerald-50 text-emerald-800 border border-emerald-200 text-xs font-black px-4 py-2.5 rounded-xl flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Payroll Released & Paid
                    </div>
                @endif
            </div>
        </div>

        <!-- Philippine Proclamation Holidays Banner in Current Cutoff -->
        @if(isset($activeHolidays) && $activeHolidays->isNotEmpty())
            <div class="bg-blue-50/60 border border-blue-200/80 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                <div class="flex items-center gap-2.5">
                    <span class="px-2.5 py-1 rounded-lg font-black bg-blue-600 text-white text-[11px] uppercase tracking-wider">Philippine Proclamation Holidays</span>
                    <span class="font-bold text-gray-800">Active Holidays in this Cutoff:</span>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @foreach($activeHolidays as $h)
                        <span class="px-3 py-1 rounded-xl font-bold border {{ $h->holiday_type === 'regular' ? 'bg-indigo-50 border-indigo-200 text-indigo-800' : 'bg-amber-50 border-amber-200 text-amber-800' }}">
                            {{ $h->name }} ({{ $h->holiday_date->format('M d') }} - {{ $h->holiday_type === 'regular' ? 'Regular 200%' : 'Special 130%' }})
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Tab Navigation Bar -->
        <div class="bg-gray-100/80 p-1 rounded-2xl flex items-center gap-1 overflow-x-auto">
            <button type="button" @click="activeTab = 'table'" 
                    :class="activeTab === 'table' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 font-bold hover:text-gray-700'"
                    class="px-4 py-2 text-xs rounded-xl transition-all whitespace-nowrap flex items-center gap-2">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Employee Payroll Records
                <span class="px-2 py-0.5 rounded-full text-[11px] font-black bg-gray-100 text-gray-700">{{ $computations->total() }}</span>
            </button>

            <button type="button" @click="activeTab = 'earnings'" 
                    :class="activeTab === 'earnings' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 font-bold hover:text-gray-700'"
                    class="px-4 py-2 text-xs rounded-xl transition-all whitespace-nowrap flex items-center gap-2">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                Earnings & Deductions Formulas
            </button>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 1: EMPLOYEE TABLE -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'table'" x-transition class="space-y-6">

            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-5">
                
                <!-- Interactive Search & Filter Form -->
                <form action="{{ route('payroll.salary-computation') }}" method="GET" class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <input type="hidden" name="period" value="{{ $cutoff }}">
                    <div class="flex flex-1 items-center gap-3">
                        <div class="relative flex-1 max-w-sm">
                            <input type="text" name="search" value="{{ $search }}" placeholder="Search employee name or code..." 
                                   class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl pl-9 pr-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-[#F44336]">
                            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>

                        <select name="department" onchange="this.form.submit()" 
                                class="text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-[#F44336]">
                            <option value="all">All Departments</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ (string)$deptId === (string)$dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                            @endforeach
                        </select>

                        <button type="submit" class="bg-gray-900 hover:bg-black text-white text-xs font-black px-4 py-2.5 rounded-xl transition-all">
                            Filter
                        </button>
                    </div>

                    <div class="text-xs text-gray-500 font-bold">
                        Showing {{ $computations->count() }} of {{ $computations->total() }} records
                    </div>
                </form>

                <!-- Payroll Items Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200 text-xs font-black text-gray-400 uppercase tracking-wider">
                                <th class="py-3 px-4">Employee</th>
                                <th class="py-3 px-4">Position</th>
                                <th class="py-3 px-4">Department</th>
                                <th class="py-3 px-4 text-right">Gross Pay</th>
                                <th class="py-3 px-4 text-right">Deductions</th>
                                <th class="py-3 px-4 text-right">Net Pay</th>
                                <th class="py-3 px-4 text-center">Compliance</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-xs">
                            @forelse($computations as $comp)
                                <tr class="hover:bg-gray-50/75 transition-colors">
                                    <td class="py-3.5 px-4 font-black text-gray-900">
                                        <div class="text-sm font-black">{{ $comp->employee->first_name }} {{ $comp->employee->last_name }}</div>
                                        <span class="text-xs text-gray-400 font-mono">{{ $comp->employee->employee_code }}</span>
                                    </td>
                                    <td class="py-3.5 px-4 text-gray-700 font-bold">
                                        {{ $comp->employee->position }}
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="px-2.5 py-1 bg-blue-50 text-blue-800 font-bold rounded-lg text-xs">
                                            {{ $comp->employee->department?->name ?? 'General' }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-black font-outfit text-sm text-gray-900">
                                        PHP {{ number_format((float)$comp->gross_pay, 2) }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-black font-outfit text-sm text-rose-600">
                                        -PHP {{ number_format((float)$comp->total_deductions, 2) }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-black font-outfit text-sm text-emerald-700">
                                        PHP {{ number_format((float)$comp->net_pay, 2) }}
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        @if($comp->aiComplianceLog)
                                            <button type="button" @click="openAiInsight({{ Js::from($comp->aiComplianceLog) }}, {{ Js::from($comp) }})"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black transition-all hover:scale-105"
                                                    :class="{
                                                        'bg-emerald-50 text-emerald-800 border border-emerald-200': '{{ $comp->aiComplianceLog->status }}' === 'PASSED',
                                                        'bg-amber-50 text-amber-800 border border-amber-200': '{{ $comp->aiComplianceLog->status }}' === 'WARNING',
                                                        'bg-rose-50 text-rose-800 border border-rose-200': '{{ $comp->aiComplianceLog->status }}' === 'FAILED'
                                                    }">
                                                <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                                {{ $comp->aiComplianceLog->status }}
                                            </button>
                                        @else
                                            <span class="text-xs text-gray-400 font-bold">Standard</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button type="button" @click="openBreakdown({{ Js::from($comp) }})" 
                                                    class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold text-xs px-3 py-1.5 rounded-xl transition-all">
                                                Breakdown
                                            </button>
                                            <button type="button" @click="openOverrideModal({{ Js::from($comp) }})" 
                                                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs px-3 py-1.5 rounded-xl transition-all">
                                                Edit
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-10 text-center text-gray-400 text-xs font-semibold">
                                        No computed salary records found for this period. Click 'Execute Payroll Run' to process.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($computations->hasPages())
                    <div class="pt-4 border-t border-gray-100">
                        {{ $computations->links() }}
                    </div>
                @endif

            </div>

        </div>

        <!-- ========================================================================= -->
        <!-- TAB 2: CALCULATION FORMULAS -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'earnings'" x-transition class="space-y-6">
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-6">
                <div class="border-b border-gray-100 pb-4">
                    <h2 class="text-base font-black font-outfit text-gray-900">DOLE Statutory Pay & Timekeeping Matrix (Articles 87, 90, 93, 94)</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Summary of statutory formulas and rate multipliers for regular payroll, holidays, overtime, trip quotas, and loans.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5 text-xs">
                    <div class="p-5 bg-gray-50 rounded-2xl border border-gray-200 space-y-2">
                        <span class="font-black text-gray-900 uppercase tracking-wider block">1. Driver Trip Quota Incentives</span>
                        <ul class="space-y-1.5 text-gray-600 font-medium">
                            <li><strong class="text-gray-900">Tier 1 (30-49 Trips):</strong> +PHP 500.00 cash bonus</li>
                            <li><strong class="text-gray-900">Tier 2 (50-69 Trips):</strong> +PHP 1,500.00 cash bonus</li>
                            <li><strong class="text-gray-900">Tier 3 (70+ Trips):</strong> +PHP 3,000.00 cash bonus</li>
                        </ul>
                    </div>

                    <div class="p-5 bg-gray-50 rounded-2xl border border-gray-200 space-y-2">
                        <span class="font-black text-gray-900 uppercase tracking-wider block">2. Overtime & Night Differential</span>
                        <ul class="space-y-1.5 text-gray-600 font-medium">
                            <li><strong class="text-gray-900">Regular Overtime:</strong> Hourly Rate x 1.25</li>
                            <li><strong class="text-gray-900">Rest Day Overtime:</strong> Hourly Rate x 1.69</li>
                            <li><strong class="text-gray-900">Night Shift Diff (10PM-6AM):</strong> Hourly Rate x 10%</li>
                        </ul>
                    </div>

                    <div class="p-5 bg-gray-50 rounded-2xl border border-gray-200 space-y-2">
                        <span class="font-black text-gray-900 uppercase tracking-wider block">3. Statutory & Company Loans</span>
                        <ul class="space-y-1.5 text-gray-600 font-medium">
                            <li><strong class="text-gray-900">SSS Salary/Calamity Loan:</strong> Semi-monthly fixed</li>
                            <li><strong class="text-gray-900">Pag-IBIG MPL/Housing:</strong> Semi-monthly fixed</li>
                            <li><strong class="text-gray-900">Balance Cap:</strong> min(amortization, balance)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: ITEM-BY-ITEM TRANSPARENT BREAKDOWN & FORMULA ENGINE -->
        <!-- ========================================================================= -->
        <div x-show="showBreakdownModal" style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div @click.away="showBreakdownModal = false" class="bg-white rounded-3xl max-w-2xl w-full p-6 shadow-2xl border border-gray-100 space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h3 class="text-base font-black font-outfit text-gray-900">Salary Calculation Transparency & Formula Engine</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Real-time mathematical explainability for all base rates, timekeeping, statutory tables, and loan amortizations.</p>
                    </div>
                    <button @click="showBreakdownModal = false" class="text-gray-400 hover:text-gray-600 text-sm font-bold">✕</button>
                </div>

                <template x-if="activeComp">
                    <div class="space-y-4 text-xs">
                        <!-- Employee Info Banner -->
                        <div class="p-3.5 bg-gray-50 rounded-2xl border border-gray-200 flex items-center justify-between">
                            <div>
                                <span class="font-black text-sm text-gray-900 block" x-text="activeComp.employee ? activeComp.employee.first_name + ' ' + activeComp.employee.last_name : 'Employee'"></span>
                                <span class="text-xs text-gray-500 font-mono block" x-text="activeComp.employee ? activeComp.employee.employee_code + ' • ' + activeComp.employee.position : ''"></span>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-xs font-black uppercase tracking-wider"
                                  :class="isDriver(activeComp) ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'"
                                  x-text="isDriver(activeComp) ? 'Transport Driver' : 'Regular Staff'">
                            </span>
                        </div>

                        <!-- Modal Internal Sub-Tabs -->
                        <div class="flex items-center gap-1.5 p-1 bg-gray-100 rounded-xl overflow-x-auto">
                            <button type="button" @click="breakdownTab = 'summary'"
                                    :class="breakdownTab === 'summary' ? 'bg-white font-black text-gray-900 shadow-xs' : 'text-gray-600 font-bold hover:text-gray-900'"
                                    class="px-3 py-1.5 rounded-lg text-xs transition-all whitespace-nowrap">
                                1. Itemized Summary
                            </button>
                            <button type="button" @click="breakdownTab = 'base_timekeeping'"
                                    :class="breakdownTab === 'base_timekeeping' ? 'bg-white font-black text-gray-900 shadow-xs' : 'text-gray-600 font-bold hover:text-gray-900'"
                                    class="px-3 py-1.5 rounded-lg text-xs transition-all whitespace-nowrap">
                                2. Base Pay & Attendance
                            </button>
                            <button type="button" @click="breakdownTab = 'tnvs_holidays'"
                                    :class="breakdownTab === 'tnvs_holidays' ? 'bg-white font-black text-gray-900 shadow-xs' : 'text-gray-600 font-bold hover:text-gray-900'"
                                    class="px-3 py-1.5 rounded-lg text-xs transition-all whitespace-nowrap">
                                3. TNVS & Multipliers
                            </button>
                            <button type="button" @click="breakdownTab = 'statutory_tax'"
                                    :class="breakdownTab === 'statutory_tax' ? 'bg-white font-black text-gray-900 shadow-xs' : 'text-gray-600 font-bold hover:text-gray-900'"
                                    class="px-3 py-1.5 rounded-lg text-xs transition-all whitespace-nowrap">
                                4. Statutory & Tax
                            </button>
                            <button type="button" @click="breakdownTab = 'loans_burden'"
                                    :class="breakdownTab === 'loans_burden' ? 'bg-white font-black text-gray-900 shadow-xs' : 'text-gray-600 font-bold hover:text-gray-900'"
                                    class="px-3 py-1.5 rounded-lg text-xs transition-all whitespace-nowrap">
                                5. Loans & Burden
                            </button>
                        </div>

                        <!-- TAB 1: ITEMIZED SUMMARY -->
                        <div x-show="breakdownTab === 'summary'" class="space-y-3">
                            <div class="p-4 bg-emerald-50/40 rounded-2xl border border-emerald-100 space-y-2">
                                <span class="text-xs font-black text-emerald-900 uppercase tracking-wider block">Gross Earnings Items</span>
                                <div class="space-y-1.5 text-gray-700">
                                    <div class="flex justify-between">
                                        <span>Base Salary:</span>
                                        <span class="font-mono font-bold" x-text="'PHP ' + Number(activeComp.base_pay).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                                    </div>
                                    <template x-if="Number(activeComp.trip_earnings) > 0">
                                        <div class="flex justify-between text-blue-900 font-bold">
                                            <span>Trip Fares:</span>
                                            <span class="font-mono" x-text="'+PHP ' + Number(activeComp.trip_earnings).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                                        </div>
                                    </template>
                                    <template x-if="Number(activeComp.driver_trip_incentive) > 0">
                                        <div class="flex justify-between text-purple-900 font-bold">
                                            <span>Quota Incentive:</span>
                                            <span class="font-mono" x-text="'+PHP ' + Number(activeComp.driver_trip_incentive).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                                        </div>
                                    </template>
                                    <template x-if="Number(activeComp.holiday_pay) > 0">
                                        <div class="flex justify-between text-indigo-900 font-bold">
                                            <span>Holiday Pay:</span>
                                            <span class="font-mono" x-text="'+PHP ' + Number(activeComp.holiday_pay).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                                        </div>
                                    </template>
                                    <template x-if="Number(activeComp.overtime_pay) > 0">
                                        <div class="flex justify-between text-teal-900 font-bold">
                                            <span>Overtime Pay:</span>
                                            <span class="font-mono" x-text="'+PHP ' + Number(activeComp.overtime_pay).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                                        </div>
                                    </template>
                                    <div class="flex justify-between border-t border-emerald-100 pt-1.5 font-black">
                                        <span class="text-emerald-900">Total Gross Pay:</span>
                                        <span class="text-emerald-900 font-outfit text-sm" x-text="'PHP ' + Number(activeComp.gross_pay).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="p-4 bg-rose-50/30 rounded-2xl border border-rose-100 space-y-2">
                                <span class="text-xs font-black text-rose-900 uppercase tracking-wider block">Deductions</span>
                                <div class="space-y-1.5">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 font-bold">SSS (EE):</span>
                                        <span class="font-mono font-bold text-rose-600" x-text="'-PHP ' + Number(activeComp.sss_deduction || 0).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 font-bold">PhilHealth (EE):</span>
                                        <span class="font-mono font-bold text-rose-600" x-text="'-PHP ' + Number(activeComp.philhealth_deduction || 0).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 font-bold">Pag-IBIG (EE):</span>
                                        <span class="font-mono font-bold text-rose-600" x-text="'-PHP ' + Number(activeComp.pagibig_deduction || 0).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                                    </div>
                                    <template x-if="Number(activeComp.loan_deduction) > 0">
                                        <div class="flex justify-between text-purple-900 font-bold">
                                            <span>Loan Amortization:</span>
                                            <span class="font-mono text-rose-600" x-text="'-PHP ' + Number(activeComp.loan_deduction).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                                        </div>
                                    </template>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 font-bold">BIR Tax (TRAIN):</span>
                                        <span class="font-mono font-bold text-rose-600" x-text="'-PHP ' + Number(activeComp.withholding_tax || 0).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                                    </div>
                                    <div class="flex justify-between border-t border-rose-100 pt-1.5 font-black">
                                        <span class="text-rose-900">Total Deductions:</span>
                                        <span class="text-rose-900 font-outfit text-sm" x-text="'-PHP ' + Number(activeComp.total_deductions).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: BASE PAY & ATTENDANCE MATH -->
                        <div x-show="breakdownTab === 'base_timekeeping'" class="space-y-3">
                            <div class="p-4 bg-gray-50 rounded-2xl border border-gray-200 space-y-2">
                                <span class="font-black text-gray-900 uppercase tracking-wider block">Base Pay Formula & Rates</span>
                                <div class="grid grid-cols-2 gap-2 text-gray-700">
                                    <div>Monthly Rate: <strong class="text-gray-900" x-text="'PHP ' + (transparencyData?.base_pay_math?.monthly_rate || 0).toLocaleString(undefined, {minimumFractionDigits: 2})"></strong></div>
                                    <div>Daily Rate (/26): <strong class="text-gray-900" x-text="'PHP ' + (transparencyData?.base_pay_math?.daily_rate || 0).toLocaleString(undefined, {minimumFractionDigits: 2})"></strong></div>
                                    <div>Hourly Rate (/8): <strong class="text-gray-900" x-text="'PHP ' + (transparencyData?.base_pay_math?.hourly_rate || 0).toLocaleString(undefined, {minimumFractionDigits: 2})"></strong></div>
                                    <div>Minute Rate (/480): <strong class="text-gray-900" x-text="'PHP ' + (transparencyData?.base_pay_math?.minute_rate || 0)"></strong></div>
                                </div>
                                <div class="p-2.5 bg-white rounded-xl border border-gray-200 font-mono text-gray-800 text-[11px]"
                                     x-text="transparencyData?.base_pay_math?.formula || 'Calculating formula...'">
                                </div>
                            </div>

                            <div class="p-4 bg-gray-50 rounded-2xl border border-gray-200 space-y-2">
                                <span class="font-black text-gray-900 uppercase tracking-wider block">Timekeeping Penalties (Minute-Level)</span>
                                <div class="space-y-1 text-gray-700">
                                    <div class="p-2 bg-white rounded-xl border border-gray-200 font-mono text-[11px]"
                                         x-text="'Tardiness: ' + (transparencyData?.attendance_math?.tardiness_formula || '0 mins')">
                                    </div>
                                    <div class="p-2 bg-white rounded-xl border border-gray-200 font-mono text-[11px]"
                                         x-text="'Undertime: ' + (transparencyData?.attendance_math?.undertime_formula || '0 mins')">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 3: TNVS & MULTIPLIERS -->
                        <div x-show="breakdownTab === 'tnvs_holidays'" class="space-y-3">
                            <div class="p-4 bg-purple-50/40 rounded-2xl border border-purple-100 space-y-2">
                                <span class="font-black text-purple-900 uppercase tracking-wider block">TNVS Commission & Quota Tiers</span>
                                <div class="p-2.5 bg-white rounded-xl border border-purple-100 font-mono text-[11px] text-gray-800"
                                     x-text="transparencyData?.tnvs_math?.platform_fee_formula || 'N/A'">
                                </div>
                                <div class="text-gray-700">
                                    <span>Quota Tier: </span>
                                    <strong class="text-purple-900" x-text="transparencyData?.tnvs_math?.quota_tier_label || 'None'"></strong>
                                </div>
                            </div>

                            <div class="p-4 bg-gray-50 rounded-2xl border border-gray-200 space-y-2">
                                <span class="font-black text-gray-900 uppercase tracking-wider block">Holiday & Overtime Multipliers</span>
                                <div class="grid grid-cols-2 gap-2 text-gray-700">
                                    <div>Regular Holiday (200%): <strong class="text-gray-900" x-text="'PHP ' + (transparencyData?.holiday_ot_math?.regular_holiday_rate || 0) + '/hr'"></strong></div>
                                    <div>Special Holiday (130%): <strong class="text-gray-900" x-text="'PHP ' + (transparencyData?.holiday_ot_math?.special_holiday_rate || 0) + '/hr'"></strong></div>
                                    <div>Overtime (125%): <strong class="text-gray-900" x-text="'PHP ' + (transparencyData?.holiday_ot_math?.overtime_rate || 0) + '/hr'"></strong></div>
                                    <div>Night Diff (10%): <strong class="text-gray-900" x-text="'PHP ' + (transparencyData?.holiday_ot_math?.night_diff_rate || 0) + '/hr'"></strong></div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 4: STATUTORY & TAX MATRICES -->
                        <div x-show="breakdownTab === 'statutory_tax'" class="space-y-3">
                            <div class="p-4 bg-blue-50/40 rounded-2xl border border-blue-100 space-y-2">
                                <span class="font-black text-blue-900 uppercase tracking-wider block">Statutory Matrices Lookup</span>
                                <div class="space-y-1.5">
                                    <div class="p-2 bg-white rounded-xl border border-blue-100 font-mono text-[11px] text-gray-800"
                                         x-text="'SSS: ' + (transparencyData?.statutory_lookups?.sss?.formula || '')">
                                    </div>
                                    <div class="p-2 bg-white rounded-xl border border-blue-100 font-mono text-[11px] text-gray-800"
                                         x-text="'PhilHealth: ' + (transparencyData?.statutory_lookups?.philhealth?.formula || '')">
                                    </div>
                                    <div class="p-2 bg-white rounded-xl border border-blue-100 font-mono text-[11px] text-gray-800"
                                         x-text="'Pag-IBIG: ' + (transparencyData?.statutory_lookups?.pagibig?.formula || '')">
                                    </div>
                                </div>
                            </div>

                            <div class="p-4 bg-gray-50 rounded-2xl border border-gray-200 space-y-2">
                                <span class="font-black text-gray-900 uppercase tracking-wider block">BIR TRAIN Law Withholding Tax</span>
                                <div class="text-gray-700">
                                    <div>Taxable Income: <strong class="text-gray-900" x-text="'PHP ' + (transparencyData?.tax_math?.taxable_income || 0).toLocaleString(undefined, {minimumFractionDigits: 2})"></strong></div>
                                    <div>Bracket: <strong class="text-indigo-900" x-text="transparencyData?.tax_math?.train_bracket || ''"></strong></div>
                                </div>
                                <div class="p-2.5 bg-white rounded-xl border border-gray-200 font-mono text-[11px] text-gray-800"
                                     x-text="transparencyData?.tax_math?.formula || ''">
                                </div>
                            </div>
                        </div>

                        <!-- TAB 5: LOANS & EMPLOYER BURDEN -->
                        <div x-show="breakdownTab === 'loans_burden'" class="space-y-3">
                            <div class="p-4 bg-gray-50 rounded-2xl border border-gray-200 space-y-2">
                                <span class="font-black text-gray-900 uppercase tracking-wider block">Active Loan Reductions</span>
                                <template x-if="transparencyData?.loan_math?.length > 0">
                                    <div class="space-y-1.5">
                                        <template x-for="loan in transparencyData.loan_math" :key="loan.reference_no">
                                            <div class="p-2.5 bg-white rounded-xl border border-gray-200 text-gray-800 flex justify-between items-center">
                                                <div>
                                                    <span class="font-bold block" x-text="loan.loan_type"></span>
                                                    <span class="text-[10px] text-gray-400 font-mono" x-text="loan.reference_no"></span>
                                                </div>
                                                <div class="text-right">
                                                    <span class="font-mono font-bold text-rose-600" x-text="'-PHP ' + Number(loan.amortization_deduction).toFixed(2)"></span>
                                                    <span class="text-[10px] text-gray-500 block" x-text="'Rem: PHP ' + Number(loan.balance_after).toFixed(2)"></span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="!transparencyData?.loan_math?.length">
                                    <span class="text-gray-400 font-medium block">No active employee loans deducted for this period.</span>
                                </template>
                            </div>

                            <div class="p-4 bg-blue-50/40 rounded-2xl border border-blue-100 space-y-2">
                                <span class="font-black text-blue-900 uppercase tracking-wider block">Employer Cost Burden</span>
                                <div class="p-2.5 bg-white rounded-xl border border-blue-100 font-mono text-[11px] text-gray-800"
                                     x-text="transparencyData?.employer_burden?.formula || ''">
                                </div>
                            </div>
                        </div>

                        <!-- Final Net Pay Formula Banner -->
                        <div class="p-4 bg-gray-900 text-white rounded-2xl flex items-center justify-between">
                            <div>
                                <span class="text-xs text-gray-400 font-bold block">Final Take-Home Net Pay</span>
                                <span class="text-[10px] text-gray-400 font-mono" x-text="transparencyData?.net_pay_math?.formula || ''"></span>
                            </div>
                            <span class="text-lg font-black font-outfit text-emerald-400" x-text="'PHP ' + Number(activeComp.net_pay).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                        </div>

                        <div class="flex items-center justify-end pt-2">
                            <button type="button" @click="showBreakdownModal = false" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold text-xs px-5 py-2.5 rounded-xl transition-all">
                                Close Breakdown
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: MANUAL SALARY CORRECTION -->
        <!-- ========================================================================= -->
        <div x-show="showManualModal" style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div @click.away="showManualModal = false" class="bg-white rounded-2xl max-w-xl w-full p-6 shadow-2xl border border-gray-100 space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h3 class="text-base font-black font-outfit text-gray-900">Manual Salary Correction</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Adjust computed figures prior to final Administrative approval.</p>
                    </div>
                    <button @click="showManualModal = false" class="text-gray-400 hover:text-gray-600 text-sm font-bold">✕</button>
                </div>

                <form action="{{ route('payroll.manual-compute') }}" method="POST" class="space-y-4 text-xs">
                    @csrf
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Target Personnel *</label>
                            <select name="employee_id" x-model="activeOverride.employee_id" required class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                                <option value="">-- Choose Employee --</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->first_name }} {{ $emp->last_name }} ({{ $emp->position }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Cutoff Period *</label>
                            <input type="text" name="cutoff_period" x-model="activeOverride.cutoff_period" readonly class="w-full bg-gray-100 border border-gray-200 rounded-xl p-2.5 font-mono font-bold text-gray-700">
                        </div>
                    </div>

                    <div class="border-t border-gray-100 pt-3 space-y-2">
                        <span class="font-black uppercase tracking-wider text-emerald-700 block">1. Earnings Adjustments</span>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block font-bold text-gray-600 mb-1">Base Pay (PHP) *</label>
                                <input type="number" step="0.01" name="base_pay" x-model="activeOverride.base_pay" required class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                            </div>
                            <div>
                                <label class="block font-bold text-gray-600 mb-1">Trip Earnings (PHP)</label>
                                <input type="number" step="0.01" name="trip_earnings" x-model="activeOverride.trip_earnings" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                            </div>
                            <div>
                                <label class="block font-bold text-gray-600 mb-1">Trip Incentive (PHP)</label>
                                <input type="number" step="0.01" name="driver_trip_incentive" x-model="activeOverride.driver_trip_incentive" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                            </div>
                            <div>
                                <label class="block font-bold text-gray-600 mb-1">Holiday Pay (PHP)</label>
                                <input type="number" step="0.01" name="holiday_pay" x-model="activeOverride.holiday_pay" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                            </div>
                            <div>
                                <label class="block font-bold text-gray-600 mb-1">Overtime Pay (PHP)</label>
                                <input type="number" step="0.01" name="overtime_pay" x-model="activeOverride.overtime_pay" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                            </div>
                            <div>
                                <label class="block font-bold text-gray-600 mb-1">Night Diff Pay (PHP)</label>
                                <input type="number" step="0.01" name="night_diff_pay" x-model="activeOverride.night_diff_pay" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-100 pt-3 space-y-2">
                        <span class="font-black uppercase tracking-wider text-rose-700 block">2. Deductions Override</span>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block font-bold text-gray-600 mb-1">SSS Share (PHP)</label>
                                <input type="number" step="0.01" name="sss_deduction" x-model="activeOverride.sss_deduction" placeholder="Auto" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                            </div>
                            <div>
                                <label class="block font-bold text-gray-600 mb-1">PhilHealth Share (PHP)</label>
                                <input type="number" step="0.01" name="philhealth_deduction" x-model="activeOverride.philhealth_deduction" placeholder="Auto" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                            </div>
                            <div>
                                <label class="block font-bold text-gray-600 mb-1">Pag-IBIG Share (PHP)</label>
                                <input type="number" step="0.01" name="pagibig_deduction" x-model="activeOverride.pagibig_deduction" placeholder="Auto" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                            </div>
                            <div>
                                <label class="block font-bold text-gray-600 mb-1">Loan Amortization (PHP)</label>
                                <input type="number" step="0.01" name="loan_deduction" x-model="activeOverride.loan_deduction" placeholder="0.00" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                            </div>
                            <div>
                                <label class="block font-bold text-gray-600 mb-1">Late / Tardy (PHP)</label>
                                <input type="number" step="0.01" name="tardiness_deduction" x-model="activeOverride.tardiness_deduction" placeholder="0.00" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                            </div>
                            <div>
                                <label class="block font-bold text-gray-600 mb-1">Undertime (PHP)</label>
                                <input type="number" step="0.01" name="undertime_deduction" x-model="activeOverride.undertime_deduction" placeholder="0.00" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-100 pt-4 flex items-center justify-end gap-3">
                        <button type="button" @click="showManualModal = false" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition-all">Cancel</button>
                        <button type="submit" class="px-5 py-2.5 bg-[#F44336] hover:bg-[#D32F2F] text-white font-black rounded-xl shadow-sm transition-all">Save & Apply Correction</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: AI COMPLIANCE AUDIT INSIGHTS -->
        <!-- ========================================================================= -->
        <div x-show="showAiInsightModal" style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div @click.away="showAiInsightModal = false" class="bg-white rounded-2xl max-w-xl w-full p-6 shadow-2xl border border-gray-100 space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h3 class="text-base font-black font-outfit text-gray-900">DOLE Regulatory Compliance Audit</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Automated labor standards compliance scan.</p>
                    </div>
                    <button @click="showAiInsightModal = false" class="text-gray-400 hover:text-gray-600 text-sm font-bold">✕</button>
                </div>

                <template x-if="activeLog">
                    <div class="space-y-4 text-xs">
                        <div class="p-4 rounded-2xl border flex items-center justify-between"
                             :class="{
                                'bg-emerald-50 border-emerald-200 text-emerald-900': activeLog.status === 'PASSED',
                                'bg-amber-50 border-amber-200 text-amber-900': activeLog.status === 'WARNING',
                                'bg-rose-50 border-rose-200 text-rose-900': activeLog.status === 'FAILED'
                             }">
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider block opacity-70">Compliance Status</span>
                                <span class="text-sm font-black" x-text="activeLog.status"></span>
                            </div>
                            <div class="text-right">
                                <span class="text-2xl font-black font-outfit" x-text="activeLog.compliance_score + '%'"></span>
                                <span class="text-xs block opacity-70">Audit Score</span>
                            </div>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-2xl border border-gray-200 space-y-1">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block">Summary Assessment</span>
                            <p class="text-xs text-gray-800 font-medium" x-text="activeLog.ai_summary"></p>
                        </div>

                        <div class="flex items-center justify-end pt-2 gap-2">
                            <button @click="showAiInsightModal = false" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition-all">
                                Close Assessment
                            </button>
                            <template x-if="activeLog.status !== 'PASSED'">
                                <button @click="showAiInsightModal = false; openOverrideModal(activeComp)" class="px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white text-xs font-black rounded-xl transition-all shadow-sm">
                                    Open Manual Override
                                </button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>

    </div>

@endsection
