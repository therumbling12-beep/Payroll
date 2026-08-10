@extends('layouts.app')

@php
    $pageTitle = 'Salary Computation & Audit';
    $currentPage = 'payroll.salary-computation';
@endphp

@section('content')

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('payroll.salary-computation') }}" class="text-xs font-bold text-gray-400 hover:text-[#F44336] transition-colors flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Back to Cutoff Runs
                </a>
            </div>
            <h1 class="text-xl font-extrabold font-outfit text-gray-900 mt-1">Batch Salary Computation</h1>
            <p class="text-xs text-gray-500 mt-0.5">Step 2 of 2: Detailed employee payroll breakdown, AI compliance analysis, and workflow approval stream.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-xs font-semibold text-emerald-600 bg-emerald-50 border border-emerald-200 px-3 py-1.5 rounded-full">
                DOLE 2026 Compliant Engine
            </span>
        </div>
    </div>

    <!-- Main Table Container with Alpine.js State -->
    <div class="bg-white rounded-2xl border border-gray-100 p-6 mb-8" 
         x-data="{ 
            selected: [], 
            selectAll: false,
            expandedRow: null,
            showManualModal: false,
            showAiInsightModal: false,
            activeLog: null,
            activeComp: null,
            activeOverride: {
                employee_id: '',
                cutoff_period: '{{ $cutoff }}',
                base_pay: '',
                trip_earnings: '',
                performance_bonus: '',
                sss_deduction: '',
                philhealth_deduction: '',
                pagibig_deduction: ''
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
                        performance_bonus: comp.performance_bonus || '',
                        sss_deduction: comp.sss_deduction || '',
                        philhealth_deduction: comp.philhealth_deduction || '',
                        pagibig_deduction: comp.pagibig_deduction || ''
                    };
                } else {
                    this.activeOverride = {
                        employee_id: '',
                        cutoff_period: '{{ $cutoff }}',
                        base_pay: '',
                        trip_earnings: '',
                        performance_bonus: '',
                        sss_deduction: '',
                        philhealth_deduction: '',
                        pagibig_deduction: ''
                    };
                }
                this.showManualModal = true;
            },
            toggleSelectAll(ids) {
                this.selectAll = !this.selectAll;
                this.selected = this.selectAll ? ids : [];
            }
         }">

        <!-- Payroll Cutoff & Workflow Header -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-gray-100 pb-6 mb-6">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <h2 class="text-sm font-bold font-outfit text-gray-900">Active Period: {{ $cutoff }}</h2>
                    <span class="text-[10px] font-extrabold px-2.5 py-0.5 rounded-full border {{ $batch->status->badgeClasses() }}">
                        {{ $batch->status->label() }}
                    </span>
                </div>
                <p class="text-[10px] text-gray-400">
                    @if(in_array($batch->status->value, ['budget_requested', 'budget_received', 'released']))
                        Total Net Budget Requested: <strong class="text-gray-900 font-extrabold">₱{{ number_format($batch->total_net_pay, 2) }}</strong>
                    @else
                        Manage batch computation, request budget funding from Finance, and release employee payouts.
                    @endif
                </p>
            </div>
            
            <div class="flex flex-wrap items-center gap-3">
                <!-- Re-Run Payroll Run Button -->
                <form action="{{ route('payroll.batch-compute') }}" method="POST">
                    @csrf
                    <input type="hidden" name="period" value="{{ $cutoff }}">
                    <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs px-3.5 py-2 rounded-xl transition-all shadow-xs flex items-center gap-1.5 whitespace-nowrap shrink-0 border border-gray-200">
                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Re-Calculate Batch
                    </button>
                </form>

                <!-- DYNAMIC WORKFLOW STEP BUTTON -->
                @if($batch->status->value === 'draft')
                    <!-- Step 1: Submit for Admin -->
                    <form action="{{ route('payroll.workflow.submit-admin', $cutoff) }}" method="POST">
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
                    <form action="{{ route('payroll.workflow.approve-admin', $cutoff) }}" method="POST">
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
                    <form action="{{ route('payroll.workflow.request-budget', $cutoff) }}" method="POST">
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
                    <form action="{{ route('payroll.workflow.receive-budget', $cutoff) }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs px-5 py-2.5 rounded-xl transition-all shadow-md flex items-center gap-2 whitespace-nowrap shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Mark Budget Received (₱{{ number_format($batch->total_net_pay, 2) }})
                        </button>
                    </form>

                @elseif($batch->status->value === 'budget_received')
                    <!-- Step 5: Release Payroll -->
                    <form action="{{ route('payroll.workflow.release', $cutoff) }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-5 py-2.5 rounded-xl transition-all shadow-md flex items-center gap-2 whitespace-nowrap shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Release Payroll
                        </button>
                    </form>

                @elseif($batch->status->value === 'released')
                    <!-- Step 6: Released State -->
                    <div class="bg-emerald-50 text-emerald-800 border border-emerald-200 text-xs font-bold px-4 py-2 rounded-xl flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Payroll Released & Paid
                    </div>
                @endif
            </div>
        </div>

        <!-- Interactive Search & Filter Form (GET Request) -->
        <form action="{{ route('payroll.salary-computation') }}" method="GET" class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <input type="hidden" name="period" value="{{ $cutoff }}">
            <div class="flex flex-1 items-center gap-3">
                <!-- Search Input -->
                <div class="relative flex-1 max-w-xs">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search employee name or ID..." class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl pl-9 pr-3.5 py-2 text-gray-800 focus:outline-none focus:border-[#F44336]">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                <!-- Department Filter Dropdown -->
                <select name="department" onchange="this.form.submit()" class="text-xs font-semibold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-[#F44336]">
                    <option value="all">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ (string)$deptId === (string)$dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>

                <button type="submit" class="bg-gray-900 text-white text-xs font-bold px-3.5 py-2 rounded-xl">Filter</button>
            </div>

            <!-- Sort Indicator -->
            <div class="text-xs text-gray-400 flex items-center gap-2">
                <span>Total Loaded:</span>
                <span class="font-bold text-gray-700">{{ $computations->total() }} Database Records</span>
            </div>
        </form>

        <!-- Payroll Items Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-100 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                        <th class="py-3 px-4">Employee</th>
                        <th class="py-3 px-4">Position</th>
                        <th class="py-3 px-4">Department</th>
                        <th class="py-3 px-4">Gross Pay</th>
                        <th class="py-3 px-4">Deductions / Commission</th>
                        <th class="py-3 px-4">Net Pay</th>
                        <th class="py-3 px-4 text-center">Formula Transparency</th>
                        <th class="py-3 px-4 text-center">AI Compliance</th>
                        <th class="py-3 px-4 text-right">Status / Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 text-xs">
                    
                    @forelse($computations as $comp)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="py-3.5 px-4 font-bold text-gray-900">
                                <div>{{ $comp->employee->first_name }} {{ $comp->employee->last_name }}</div>
                                <span class="text-[10px] text-gray-400 font-normal">{{ $comp->employee->employee_code }}</span>
                            </td>
                            <td class="py-3.5 px-4 text-gray-600 font-medium">{{ $comp->employee->position }}</td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 bg-blue-50 text-blue-700 font-bold rounded-md text-[10px]">{{ $comp->employee->department?->name }}</span>
                            </td>
                            <td class="py-3.5 px-4 font-bold text-gray-900">₱{{ number_format((float)$comp->gross_pay, 2) }}</td>
                            <td class="py-3.5 px-4 font-semibold text-red-600">-₱{{ number_format((float)$comp->total_deductions, 2) }}</td>
                            <td class="py-3.5 px-4 font-extrabold text-emerald-600 text-sm">₱{{ number_format((float)$comp->net_pay, 2) }}</td>
                            <td class="py-3.5 px-4 text-center">
                                <button @click="expandedRow = expandedRow === {{ $comp->id }} ? null : {{ $comp->id }}" class="px-2.5 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-lg text-[10px] transition-colors flex items-center gap-1 mx-auto">
                                    <svg class="w-3.5 h-3.5 text-[#F44336]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span x-text="expandedRow === {{ $comp->id }} ? 'Hide Formula' : 'View Formula'"></span>
                                </button>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if($comp->aiComplianceLog)
                                    <button @click="openAiInsight({{ json_encode($comp->aiComplianceLog) }}, {{ \Illuminate\Support\Js::from($comp) }})" 
                                            type="button"
                                            class="px-2 py-1 rounded-lg text-[10px] font-bold transition-all hover:scale-105 shadow-2xs flex items-center gap-1 mx-auto {{ $comp->aiComplianceLog->status === 'PASSED' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100' : ($comp->aiComplianceLog->status === 'WARNING' ? 'bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100' : 'bg-red-50 text-red-700 border border-red-200 hover:bg-red-100') }}">
                                        🤖 Groq {{ $comp->aiComplianceLog->compliance_score }}%
                                        @if($comp->aiComplianceLog->status !== 'PASSED')
                                            <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                                        @endif
                                    </button>
                                @else
                                    <span class="text-[10px] text-gray-400">Pending Run</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right flex items-center justify-end gap-2">
                                <span class="px-2 py-0.5 bg-amber-50 text-amber-700 font-bold rounded-md text-[10px] uppercase">{{ str_replace('_', ' ', $comp->status) }}</span>
                                
                                <!-- DYNAMIC MANUAL OVERRIDE: ONLY VISIBLE IF AI COMPLIANCE DETECTED AN ANOMALY -->
                                @if($comp->aiComplianceLog && $comp->aiComplianceLog->status !== 'PASSED')
                                    <button @click="openOverrideModal({{ \Illuminate\Support\Js::from($comp) }})" type="button" class="px-2 py-1 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 font-bold rounded-lg text-[10px] transition-all flex items-center gap-1" title="AI Anomaly Detected: Manual Adjustment Unlocked">
                                        <svg class="w-3 h-3 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        Override
                                    </button>
                                @endif
                            </td>
                        </tr>

                        <!-- Expandable Transparent Computation Panel Row -->
                        <tr x-show="expandedRow === {{ $comp->id }}" x-cloak class="bg-gray-50/80">
                            <td colspan="10" class="p-4 border-l-4 border-[#F44336]">
                                <div class="bg-white rounded-xl p-4 border border-gray-200/80 space-y-3">
                                    <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                                        <span class="text-xs font-bold text-gray-900 font-outfit">Transparent Salary Computation Breakdown — {{ $comp->employee->first_name }} {{ $comp->employee->last_name }}</span>
                                        <span class="text-[10px] text-gray-400 font-mono">Cutoff: {{ $comp->cutoff_period }}</span>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                                        @php
                                            $isDriverRow = str_contains($comp->employee->position, 'Driver');
                                        @endphp
                                        <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                                            <span class="text-[10px] font-bold text-gray-400 uppercase block mb-1">1. Base & Earnings Formula</span>
                                            @if($isDriverRow)
                                                <div class="font-mono text-gray-800 font-semibold">Variable Trip Income (Team 9): ₱{{ number_format((float)$comp->trip_earnings, 2) }} | Bonus: ₱{{ number_format((float)$comp->performance_bonus, 2) }}</div>
                                                <div class="text-emerald-600 font-extrabold mt-1">= ₱{{ number_format((float)$comp->gross_pay, 2) }} Gross Trips Income</div>
                                            @else
                                                <div class="font-mono text-gray-800 font-semibold">Base: ₱{{ number_format((float)$comp->base_pay, 2) }} | Trips: ₱{{ number_format((float)$comp->trip_earnings, 2) }} | Bonus: ₱{{ number_format((float)$comp->performance_bonus, 2) }}</div>
                                                <div class="text-emerald-600 font-extrabold mt-1">= ₱{{ number_format((float)$comp->gross_pay, 2) }} Gross</div>
                                            @endif
                                        </div>
                                        <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                                            @if($isDriverRow)
                                                <span class="text-[10px] font-bold text-gray-400 uppercase block mb-1">2. Platform Commission Fee</span>
                                                <div class="text-[10px] text-gray-600">TNVS Platform Fee (Commission 20%): ₱{{ number_format((float)($comp->platform_fee_deduction ?? 0), 2) }} | Mandatory Statutory Deductions: ₱0.00 (Exempt)</div>
                                                <div class="text-red-600 font-extrabold mt-1">= -₱{{ number_format((float)$comp->total_deductions, 2) }} Total Platform Fee</div>
                                            @else
                                                <span class="text-[10px] font-bold text-gray-400 uppercase block mb-1">2. Deductions Breakdown</span>
                                                <div class="text-[10px] text-gray-600">SSS: ₱{{ number_format((float)$comp->sss_deduction, 2) }} | PhilHealth: ₱{{ number_format((float)$comp->philhealth_deduction, 2) }} | PagIBIG: ₱{{ number_format((float)$comp->pagibig_deduction, 2) }}</div>
                                                <div class="text-red-600 font-extrabold mt-1">= -₱{{ number_format((float)$comp->total_deductions, 2) }} Total</div>
                                            @endif
                                        </div>
                                        <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                                            <span class="text-[10px] font-bold text-gray-400 uppercase block mb-1">3. Net Payout Result</span>
                                            @if($isDriverRow)
                                                <div class="text-[10px] text-gray-600">Gross Trips (₱{{ number_format((float)$comp->gross_pay, 2) }}) - Platform Fee (₱{{ number_format((float)$comp->total_deductions, 2) }})</div>
                                            @else
                                                <div class="text-[10px] text-gray-600">Gross (₱{{ number_format((float)$comp->gross_pay, 2) }}) - Deductions (₱{{ number_format((float)$comp->total_deductions, 2) }})</div>
                                            @endif
                                            <div class="text-emerald-600 font-extrabold text-sm mt-1">= ₱{{ number_format((float)$comp->net_pay, 2) }} Net Payout</div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-6 text-center text-gray-400 text-xs">No salary records found matching filter.</td>
                        </tr>
                    @endforelse

                </tbody>
            </table>
        </div>

        <!-- Real Laravel Pagination Links -->
        <div class="mt-6">
            {{ $computations->links() }}
        </div>



        <!-- Alpine.js AI Audit & Resolution Insights Modal -->
        <div x-show="showAiInsightModal" 
             x-transition 
             class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50" 
             style="display: none;">
            
            <div @click.away="showAiInsightModal = false" class="bg-white rounded-2xl max-w-xl w-full p-6 shadow-2xl border border-gray-100 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-4">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-purple-50 border border-purple-200 flex items-center justify-center text-purple-600 font-bold text-lg">
                            🤖
                        </div>
                        <div>
                            <h3 class="text-sm font-extrabold font-outfit text-gray-900">AI Risk & Anomaly Compliance Insights</h3>
                            <p class="text-[10px] text-gray-400">Groq Llama 3 Real-time Pattern & DOLE Advisory Audit</p>
                        </div>
                    </div>
                    <button @click="showAiInsightModal = false" class="text-gray-400 hover:text-gray-600 text-lg font-bold">&times;</button>
                </div>

                <template x-if="activeLog">
                    <div class="space-y-4">
                        <!-- Compliance Score Banner -->
                        <div class="p-3.5 rounded-xl border flex items-center justify-between"
                             :class="{
                                'bg-emerald-50 border-emerald-200 text-emerald-900': activeLog.status === 'PASSED',
                                'bg-amber-50 border-amber-200 text-amber-900': activeLog.status === 'WARNING',
                                'bg-red-50 border-red-200 text-red-900': activeLog.status === 'FAILED'
                             }">
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider block opacity-70">Audit Result Status</span>
                                <span class="text-sm font-extrabold" x-text="activeLog.status"></span>
                            </div>
                            <div class="text-right">
                                <span class="text-xl font-extrabold font-outfit" x-text="activeLog.compliance_score + '%'"></span>
                                <span class="text-[10px] block opacity-70">Compliance Score</span>
                            </div>
                        </div>

                        <!-- AI Executive Summary -->
                        <div class="bg-gray-50 p-3.5 rounded-xl border border-gray-100">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-1">AI Audit Summary</span>
                            <p class="text-xs text-gray-700 font-medium" x-text="activeLog.ai_summary"></p>
                        </div>

                        <!-- Why It Failed (Flagged Issues) -->
                        <div>
                            <span class="text-[11px] font-extrabold text-red-600 uppercase tracking-wider block mb-2 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                Why It Failed (Flagged Anomalies)
                            </span>
                            <template x-if="activeLog.flagged_issues && activeLog.flagged_issues.length > 0">
                                <ul class="space-y-1.5">
                                    <template x-for="(issue, idx) in activeLog.flagged_issues" :key="idx">
                                        <li class="text-xs bg-red-50 text-red-800 p-2.5 rounded-lg border border-red-100 font-medium flex items-start gap-2">
                                            <span class="text-red-500 font-bold">•</span>
                                            <span x-text="issue"></span>
                                        </li>
                                    </template>
                                </ul>
                            </template>
                            <template x-if="!activeLog.flagged_issues || activeLog.flagged_issues.length === 0">
                                <p class="text-xs text-gray-500 italic">No anomalies flagged. All statutory patterns are clear.</p>
                            </template>
                        </div>

                        <!-- How To Fix It (Resolution Suggestions) -->
                        <div>
                            <span class="text-[11px] font-extrabold text-emerald-600 uppercase tracking-wider block mb-2 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                How To Fix It (AI Recommended Resolutions)
                            </span>
                            <template x-if="activeLog.resolution_suggestions && activeLog.resolution_suggestions.length > 0">
                                <ul class="space-y-1.5">
                                    <template x-for="(suggestion, idx) in activeLog.resolution_suggestions" :key="idx">
                                        <li class="text-xs bg-emerald-50 text-emerald-800 p-2.5 rounded-lg border border-emerald-100 font-medium flex items-start gap-2">
                                            <span class="text-emerald-500 font-bold">✔</span>
                                            <span x-text="suggestion"></span>
                                        </li>
                                    </template>
                                </ul>
                            </template>
                            <template x-if="!activeLog.resolution_suggestions || activeLog.resolution_suggestions.length === 0">
                                <p class="text-xs text-gray-500 italic">No manual corrections required.</p>
                            </template>
                        </div>

                        <!-- Modal Action Footer -->
                        <div class="border-t border-gray-100 pt-4 flex items-center justify-end gap-3">
                            <button @click="showAiInsightModal = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition-all">
                                Close Insights
                            </button>
                            <template x-if="activeLog.status !== 'PASSED'">
                                <button @click="showAiInsightModal = false; openOverrideModal(activeComp)" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl transition-all shadow-sm">
                                    Open Manual Override
                                </button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Alpine.js Manual Payroll Computation Override Modal -->
        <div x-show="showManualModal" 
             x-transition 
             class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50" 
             style="display: none;">
            
            <div @click.away="showManualModal = false" class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl border border-gray-100 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-5">
                    <div>
                        <h3 class="text-base font-extrabold font-outfit text-gray-900">Manual Payroll Override Entry</h3>
                        <p class="text-xs text-gray-400">Manually compute or adjust an employee's salary and statutory deductions for a cutoff period.</p>
                    </div>
                    <button @click="showManualModal = false" class="text-gray-400 hover:text-gray-600 text-lg font-bold">&times;</button>
                </div>

                <form action="{{ route('payroll.manual-compute') }}" method="POST" class="space-y-4">
                    @csrf
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Select Employee *</label>
                            <select name="employee_id" x-model="activeOverride.employee_id" required class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl p-2.5 font-semibold text-gray-800 focus:outline-none focus:border-[#F44336]">
                                <option value="">-- Choose Employee --</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->first_name }} {{ $emp->last_name }} ({{ $emp->position }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Cutoff Period *</label>
                            <select name="cutoff_period" x-model="activeOverride.cutoff_period" required class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl p-2.5 font-semibold text-gray-800 focus:outline-none focus:border-[#F44336]">
                                <option value="2026-07-01_15">July 1 – July 15, 2026 (1st Cutoff)</option>
                                <option value="2026-07-16_31">July 16 – July 31, 2026 (2nd Cutoff)</option>
                            </select>
                        </div>
                    </div>

                    <div class="border-t border-gray-100 pt-3">
                        <span class="text-[11px] font-extrabold uppercase tracking-wider text-emerald-600 block mb-2">1. Earnings & Base Income</span>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">Base Pay (₱) *</label>
                                <input type="number" step="0.01" name="base_pay" x-model="activeOverride.base_pay" required placeholder="12500.00" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl p-2.5 font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">Trip Earnings (₱)</label>
                                <input type="number" step="0.01" name="trip_earnings" x-model="activeOverride.trip_earnings" placeholder="0.00" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl p-2.5 font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">Performance Bonus (₱)</label>
                                <input type="number" step="0.01" name="performance_bonus" x-model="activeOverride.performance_bonus" placeholder="0.00" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl p-2.5 font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-100 pt-3">
                        <span class="text-[11px] font-extrabold uppercase tracking-wider text-red-600 block mb-2">2. Statutory Deductions (Leave Blank for Auto-Calculate)</span>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">SSS Deduction (₱)</label>
                                <input type="number" step="0.01" name="sss_deduction" x-model="activeOverride.sss_deduction" placeholder="Auto" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl p-2.5 font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">PhilHealth Deduction (₱)</label>
                                <input type="number" step="0.01" name="philhealth_deduction" x-model="activeOverride.philhealth_deduction" placeholder="Auto" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl p-2.5 font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">Pag-IBIG Deduction (₱)</label>
                                <input type="number" step="0.01" name="pagibig_deduction" x-model="activeOverride.pagibig_deduction" placeholder="Auto" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl p-2.5 font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">TNVS Platform Commission (₱)</label>
                                <input type="number" step="0.01" name="platform_fee_deduction" x-model="activeOverride.platform_fee_deduction" placeholder="Auto (20%)" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl p-2.5 font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-100 pt-4 flex items-center justify-end gap-3">
                        <button type="button" @click="showManualModal = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs rounded-xl transition-all">Cancel</button>
                        <button type="submit" class="px-5 py-2 bg-[#F44336] hover:bg-[#D32F2F] text-white font-bold text-xs rounded-xl shadow-md transition-all">Save & Calculate Override</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

@endsection
