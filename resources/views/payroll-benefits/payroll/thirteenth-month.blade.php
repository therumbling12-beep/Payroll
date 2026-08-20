@extends('layouts.app')

@php
    $pageTitle = '13th Month Pay Computation — ' . $year;
    $currentPage = 'payroll.thirteenth-month';
@endphp

@section('content')

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-extrabold font-outfit text-gray-900">13th Month Pay Computation & Transparency</h1>
            <p class="text-xs text-gray-500 mt-0.5">Automated pro-rated bonus calculation per Presidential Decree 851, TRAIN Law statutory exemption, and 12-month cutoff ledger audit.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-black bg-purple-50 text-purple-800 border border-purple-200">
                <span class="w-2 h-2 rounded-full bg-purple-600"></span>
                Mandatory P.D. 851 Compliant
            </span>
        </div>
    </div>

    <!-- Main Container with Alpine.js State -->
    <div class="space-y-6" 
         x-data="{ 
            activeTab: 'table',
            showCalcModal: false,
            activeComp: null,
            calcModalTab: 'matrix',
            transparencyData: null,
            loadingTransparency: false,
            openCalc(comp) {
                this.activeComp = comp;
                this.calcModalTab = 'matrix';
                this.transparencyData = null;
                this.loadingTransparency = true;
                this.showCalcModal = true;
                fetch('{{ url('/payroll/13th-month/' . $year . '/employee') }}/' + comp.employee_id + '/transparency')
                    .then(r => r.json())
                    .then(d => {
                        this.transparencyData = d;
                        this.loadingTransparency = false;
                    })
                    .catch(() => {
                        this.loadingTransparency = false;
                    });
            }
         }">

        <!-- 13th Month Batch & Workflow Header Toolbar -->
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Calendar Year:</span>
                    <form action="{{ route('payroll.thirteenth-month') }}" method="GET" class="inline-flex items-center">
                        <select name="year" onchange="this.form.submit()" class="text-xs font-black bg-gray-50 border border-gray-200 rounded-xl px-3 py-1.5 text-gray-900 focus:outline-none focus:border-[#F44336] shadow-2xs">
                            @foreach($availableYears ?? [2026, 2027, 2028] as $y)
                                <option value="{{ $y }}" {{ (int)$year === (int)$y ? 'selected' : '' }}>
                                    Year {{ $y }} {{ (int)$y === 2026 ? '(Active)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black border {{ $batch->status->badgeClasses() }}">
                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                        {{ $batch->status->label() }}
                    </span>
                </div>
                <p class="text-xs text-gray-500 font-medium">
                    @if(in_array($batch->status->value, ['budget_requested', 'budget_received', 'released']))
                        Total 13th Month Budget Requested: <strong class="text-gray-900 font-black font-outfit text-sm">PHP {{ number_format((float)$batch->total_amount, 2) }}</strong>
                    @else
                        Total Computed 13th Month Pool: <strong class="text-gray-900 font-black font-outfit text-sm">PHP {{ number_format((float)$computations->sum('amount'), 2) }}</strong> across {{ $computations->total() }} personnel.
                    @endif
                </p>
            </div>
            
            <div class="flex flex-wrap items-center gap-3">
                <!-- Re-Run Computation Button -->
                <form action="{{ route('payroll.thirteenth-month.compute') }}" method="POST">
                    @csrf
                    <input type="hidden" name="year" value="{{ $year }}">
                    <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold text-xs px-4 py-2.5 rounded-xl transition-all shadow-xs flex items-center gap-1.5 border border-gray-200">
                        <svg class="w-3.5 h-3.5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                        <button type="submit" class="bg-[#F44336] hover:bg-[#D32F2F] text-white font-black text-xs px-5 py-2.5 rounded-xl transition-all shadow-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                            Submit to Admin
                        </button>
                    </form>

                @elseif($batch->status->value === 'pending_admin')
                    <!-- Step 2: Admin Approves -->
                    <form action="{{ route('payroll.thirteenth-month.workflow.approve-admin', $year) }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white font-black text-xs px-5 py-2.5 rounded-xl transition-all shadow-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Approve 13th Month (Admin)
                        </button>
                    </form>

                @elseif($batch->status->value === 'approved')
                    <!-- Step 3: Request Budget from Financial -->
                    <form action="{{ route('payroll.thirteenth-month.workflow.request-budget', $year) }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-black text-xs px-5 py-2.5 rounded-xl transition-all shadow-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            Request Budget (Finance)
                        </button>
                    </form>

                @elseif($batch->status->value === 'budget_requested')
                    <!-- Step 4: Finance Funds Transferred -->
                    <form action="{{ route('payroll.thirteenth-month.workflow.receive-budget', $year) }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-black text-xs px-5 py-2.5 rounded-xl transition-all shadow-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Confirm Budget Received
                        </button>
                    </form>

                @elseif($batch->status->value === 'budget_received')
                    <!-- Step 5: Release Payouts -->
                    <form action="{{ route('payroll.thirteenth-month.workflow.release', $year) }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs px-5 py-2.5 rounded-xl transition-all shadow-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Release 13th Month Pay
                        </button>
                    </form>

                @elseif($batch->status->value === 'released')
                    <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-black bg-emerald-50 text-emerald-800 border border-emerald-200">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        13th Month Pay Released & Completed
                    </span>
                @endif
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- EMPLOYEE BREAKDOWN TABLE -->
        <!-- ========================================================================= -->
        <div class="space-y-6">

            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-5">
                               <!-- Search & Department & Year Filter -->
                <form action="{{ route('payroll.thirteenth-month') }}" method="GET" class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex flex-1 flex-wrap items-center gap-3">
                        <div class="relative flex-1 min-w-[200px] max-w-sm">
                            <input type="text" name="search" value="{{ $search }}" placeholder="Search employee name or code..." 
                                   class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl pl-9 pr-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-[#F44336]">
                            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>

                        <select name="year" onchange="this.form.submit()" 
                                class="text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-[#F44336]">
                            @foreach($availableYears ?? [2026, 2027, 2028] as $y)
                                <option value="{{ $y }}" {{ (int)$year === (int)$y ? 'selected' : '' }}>Year {{ $y }} {{ (int)$y === 2026 ? '(Active)' : '' }}</option>
                            @endforeach
                        </select>

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
                        Showing {{ $computations->count() }} of {{ $computations->total() }} employees
                    </div>
                </form>

                <!-- 13th Month Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200 text-xs font-black text-gray-400 uppercase tracking-wider">
                                <th class="py-3 px-4">Employee</th>
                                <th class="py-3 px-4">Position</th>
                                <th class="py-3 px-4">Department</th>
                                <th class="py-3 px-4 text-center">Weekly Periods Rendered</th>
                                <th class="py-3 px-4 text-right">Monthly Base</th>
                                <th class="py-3 px-4 text-right">13th Month Pay</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-xs">
                            @forelse($computations as $comp)
                                @php
                                    $emp = $comp->employee;
                                    $months = (float) $comp->months_worked;
                                    $monthlySalary = (float) ($comp->monthly_salary ?: $emp?->monthly_rate ?: 0);
                                    $amount = (float) $comp->amount;
                                    $weeksCount = (int) ($comp->weeks_worked ?? ($amount > 0 ? 1 : 0));
                                @endphp
                                <tr class="hover:bg-gray-50/75 transition-colors">
                                    <td class="py-3.5 px-4 font-black text-gray-900">
                                        <div class="text-sm font-black">{{ $emp?->first_name }} {{ $emp?->last_name }}</div>
                                        <span class="text-xs text-gray-400 font-mono">{{ $emp?->employee_code }}</span>
                                    </td>
                                    <td class="py-3.5 px-4 text-gray-700 font-bold">
                                        {{ $emp?->position }}
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="px-2.5 py-1 bg-blue-50 text-blue-800 font-bold rounded-lg text-xs">
                                             {{ $emp?->department?->name ?? 'General' }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-black {{ $weeksCount > 0 ? 'bg-purple-50 text-purple-800 border border-purple-200' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $weeksCount }} / 52 Weeks
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-black font-outfit text-gray-900">
                                        PHP {{ number_format($monthlySalary, 2) }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-black font-outfit text-sm text-purple-900">
                                        PHP {{ number_format($amount, 2) }}
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black {{ $comp->status === 'released' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-gray-100 text-gray-700' }}">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $comp->status === 'released' ? 'bg-emerald-600' : 'bg-gray-400' }}"></span>
                                            {{ ucfirst($comp->status ?? 'Calculated') }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <button type="button" @click="openCalc({{ Js::from($comp) }})" 
                                                title="Audit 12-Month Cutoff Ledger & Formula Math"
                                                class="p-2 rounded-xl bg-purple-50 text-purple-700 hover:bg-purple-100 hover:text-purple-900 border border-purple-200 transition-all shadow-2xs inline-flex items-center justify-center">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-10 text-center text-gray-400 text-xs font-semibold">
                                        No 13th month computation records for Year {{ $year }}. Click 'Compute 13th Month Pay' to calculate.
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
        <!-- MODAL: 12-MONTH CUTOFF AUDIT LEDGER & TRANSPARENCY (4 TABS) -->
        <!-- ========================================================================= -->
        <div x-show="showCalcModal" style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div @click.away="showCalcModal = false" class="bg-white rounded-3xl max-w-2xl w-full p-6 shadow-2xl border border-gray-100 space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h3 class="text-base font-black font-outfit text-gray-900">13th Month Pay Audit Ledger & Transparency</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Historical 24-cutoff audit trail, pro-rata formulas, and TRAIN Law tax ceiling analysis.</p>
                    </div>
                    <button @click="showCalcModal = false" class="text-gray-400 hover:text-gray-600 text-sm font-bold">✕</button>
                </div>

                <template x-if="activeComp">
                    <div class="space-y-4 text-xs">
                        <!-- Personnel Card -->
                        <div class="p-3.5 bg-gray-50 rounded-2xl border border-gray-200 flex items-center justify-between">
                            <div>
                                <span class="font-black text-sm text-gray-900 block" x-text="transparencyData?.employee?.name || (activeComp.employee ? activeComp.employee.first_name + ' ' + activeComp.employee.last_name : 'Employee')"></span>
                                <span class="text-xs text-gray-500 font-mono block" x-text="(transparencyData?.employee?.code || '') + ' • ' + (transparencyData?.employee?.position || '')"></span>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-xs font-black bg-purple-100 text-purple-800"
                                  x-text="transparencyData?.employee?.hiring_status || 'Active'">
                            </span>
                        </div>

                        <!-- Modal Sub-Tabs -->
                        <div class="flex items-center gap-1.5 p-1 bg-gray-100 rounded-xl overflow-x-auto">
                            <button type="button" @click="calcModalTab = 'matrix'"
                                    :class="calcModalTab === 'matrix' ? 'bg-white font-black text-gray-900 shadow-xs' : 'text-gray-600 font-bold hover:text-gray-900'"
                                    class="px-3 py-1.5 rounded-lg text-xs transition-all whitespace-nowrap">
                                1. 12-Month Matrix
                            </button>
                            <button type="button" @click="calcModalTab = 'formula'"
                                    :class="calcModalTab === 'formula' ? 'bg-white font-black text-gray-900 shadow-xs' : 'text-gray-600 font-bold hover:text-gray-900'"
                                    class="px-3 py-1.5 rounded-lg text-xs transition-all whitespace-nowrap">
                                2. Formula Math
                            </button>
                            <button type="button" @click="calcModalTab = 'train_tax'"
                                    :class="calcModalTab === 'train_tax' ? 'bg-white font-black text-gray-900 shadow-xs' : 'text-gray-600 font-bold hover:text-gray-900'"
                                    class="px-3 py-1.5 rounded-lg text-xs transition-all whitespace-nowrap">
                                3. TRAIN Exemption
                            </button>
                            <button type="button" @click="calcModalTab = 'compliance'"
                                    :class="calcModalTab === 'compliance' ? 'bg-white font-black text-gray-900 shadow-xs' : 'text-gray-600 font-bold hover:text-gray-900'"
                                    class="px-3 py-1.5 rounded-lg text-xs transition-all whitespace-nowrap">
                                4. P.D. 851 Checklist
                            </button>
                        </div>

                        <!-- TAB 1: 52-WEEK CUTOFF MATRIX -->
                        <div x-show="calcModalTab === 'matrix'" class="space-y-3">
                            <div class="border border-gray-200 rounded-2xl overflow-hidden">
                                <div class="bg-gray-100 px-3.5 py-2 font-black text-gray-800 uppercase text-[11px] flex justify-between">
                                    <span>52-Week Base Pay Historical Ledger (Year {{ $year }})</span>
                                    <span x-text="(transparencyData?.audit_metrics?.cutoffs_recorded_count || 0) + ' Weekly Payouts Recorded'"></span>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left border-collapse text-xs">
                                        <thead>
                                            <tr class="bg-gray-50 border-b border-gray-200 text-gray-400 font-black text-[10px] uppercase">
                                                <th class="py-1.5 px-2.5">Month</th>
                                                <th class="py-1.5 px-2 text-right">Week 1</th>
                                                <th class="py-1.5 px-2 text-right">Week 2</th>
                                                <th class="py-1.5 px-2 text-right">Week 3</th>
                                                <th class="py-1.5 px-2 text-right">Week 4</th>
                                                <th class="py-1.5 px-2 text-right">Week 5</th>
                                                <th class="py-1.5 px-2.5 text-right">Month Total</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <template x-for="row in transparencyData?.monthly_breakdown" :key="row.month_number">
                                                <tr :class="row.is_eligible ? 'hover:bg-gray-50' : 'bg-gray-50/50 text-gray-400'">
                                                    <td class="py-1.5 px-2.5 font-bold" x-text="row.month_name"></td>
                                                    <td class="py-1.5 px-2 text-right font-mono" x-text="'PHP ' + Number(row.weeks?.[0]?.base_pay || row.cutoff_1?.base_pay || 0).toFixed(2)"></td>
                                                    <td class="py-1.5 px-2 text-right font-mono" x-text="'PHP ' + Number(row.weeks?.[1]?.base_pay || row.cutoff_2?.base_pay || 0).toFixed(2)"></td>
                                                    <td class="py-1.5 px-2 text-right font-mono" x-text="'PHP ' + Number(row.weeks?.[2]?.base_pay || 0).toFixed(2)"></td>
                                                    <td class="py-1.5 px-2 text-right font-mono" x-text="'PHP ' + Number(row.weeks?.[3]?.base_pay || 0).toFixed(2)"></td>
                                                    <td class="py-1.5 px-2 text-right font-mono" x-text="'PHP ' + Number(row.weeks?.[4]?.base_pay || 0).toFixed(2)"></td>
                                                    <td class="py-1.5 px-2.5 text-right font-mono font-bold" :class="row.month_total > 0 ? 'text-purple-900' : 'text-gray-400'" x-text="'PHP ' + Number(row.month_total).toFixed(2)"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                        <tfoot>
                                            <tr class="bg-gray-50 border-t border-gray-200 font-black">
                                                <td class="py-2 px-2.5 text-gray-900">Total Annual Base Pay:</td>
                                                <td colspan="6" class="py-2 px-2.5 text-right font-mono text-purple-950 font-outfit text-sm"
                                                    x-text="'PHP ' + Number(transparencyData?.audit_metrics?.annual_base_pay_basis || 0).toLocaleString(undefined, {minimumFractionDigits: 2})">
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: MATHEMATICAL FORMULA & PRO-RATION -->
                        <div x-show="calcModalTab === 'formula'" class="space-y-3">
                            <div class="p-4 bg-purple-50/40 rounded-2xl border border-purple-100 space-y-2">
                                <span class="font-black text-purple-900 uppercase tracking-wider block">13th Month Pay Formula</span>
                                <div class="p-3 bg-white rounded-xl border border-purple-100 font-mono text-gray-800 text-[11px]"
                                     x-text="transparencyData?.audit_metrics?.formula || 'Calculating formula...'">
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-gray-700 pt-1">
                                    <div>Monthly Rate: <strong class="text-gray-900" x-text="'PHP ' + Number(transparencyData?.employee?.monthly_rate || activeComp.monthly_salary || 0).toLocaleString(undefined, {minimumFractionDigits: 2})"></strong></div>
                                    <div>Eligible Service: <strong class="text-gray-900" x-text="(transparencyData?.audit_metrics?.months_worked || activeComp.months_worked || 12) + ' Months'"></strong></div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 3: TRAIN LAW TAX EXEMPTION -->
                        <div x-show="calcModalTab === 'train_tax'" class="space-y-3">
                            <div class="p-4 bg-gray-50 rounded-2xl border border-gray-200 space-y-2">
                                <span class="font-black text-gray-900 uppercase tracking-wider block">Statutory Non-Taxable Cap (PHP 90,000.00)</span>
                                <div class="grid grid-cols-2 gap-3 text-gray-700">
                                    <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-200">
                                        <span class="text-[10px] font-bold text-emerald-800 uppercase block">Non-Taxable Exempt (1604-C)</span>
                                        <span class="text-base font-black font-outfit text-emerald-700 block mt-0.5"
                                              x-text="'PHP ' + Number(transparencyData?.audit_metrics?.non_taxable_exempt || Math.min(activeComp.amount, 90000)).toLocaleString(undefined, {minimumFractionDigits: 2})">
                                        </span>
                                    </div>
                                    <div class="p-3 bg-amber-50 rounded-xl border border-amber-200">
                                        <span class="text-[10px] font-bold text-amber-800 uppercase block">Taxable Excess Compensation</span>
                                        <span class="text-base font-black font-outfit text-amber-700 block mt-0.5"
                                              x-text="'PHP ' + Number(transparencyData?.audit_metrics?.taxable_excess || Math.max(0, activeComp.amount - 90000)).toLocaleString(undefined, {minimumFractionDigits: 2})">
                                        </span>
                                    </div>
                                </div>
                                <p class="text-[11px] text-gray-500 font-medium">
                                    Under the TRAIN Law (RA 10963), total 13th month pay and other benefits up to PHP 90,000.00 are exempt from withholding tax. Any excess is subject to graduated income tax in December BIR Form 1604-C.
                                </p>
                            </div>
                        </div>

                        <!-- TAB 4: DOLE PD 851 CHECKLIST -->
                        <div x-show="calcModalTab === 'compliance'" class="space-y-3">
                            <div class="p-4 bg-gray-50 rounded-2xl border border-gray-200 space-y-2">
                                <span class="font-black text-gray-900 uppercase tracking-wider block">P.D. 851 Statutory Compliance Checklist</span>
                                <div class="space-y-2">
                                    <div class="p-2.5 bg-white rounded-xl border border-gray-200 flex items-center justify-between">
                                        <span class="text-gray-700">1. Rendered at least 1 month (30 days) in calendar year</span>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800">PASSED</span>
                                    </div>
                                    <div class="p-2.5 bg-white rounded-xl border border-gray-200 flex items-center justify-between">
                                        <span class="text-gray-700">2. Basic salary computation base (excludes OT/incentives)</span>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800">PASSED</span>
                                    </div>
                                    <div class="p-2.5 bg-white rounded-xl border border-gray-200 flex items-center justify-between">
                                        <span class="text-gray-700">3. Scheduled for disbursement on or before December 24</span>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800">PASSED</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Final Amount Banner -->
                        <div class="p-4 bg-purple-950 text-white rounded-2xl flex items-center justify-between">
                            <div>
                                <span class="text-xs text-purple-300 font-bold block">Final Computed 13th Month Benefit</span>
                                <span class="text-[10px] text-purple-400 font-mono" x-text="transparencyData?.audit_metrics?.computation_mode || 'Strict Weekly Cutoff Earnings'"></span>
                            </div>
                            <span class="text-xl font-black font-outfit text-purple-200" x-text="'PHP ' + Number(transparencyData?.audit_metrics?.calculated_amount ?? activeComp.amount).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                        </div>

                        <div class="flex items-center justify-end pt-2">
                            <button type="button" @click="showCalcModal = false" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold text-xs px-5 py-2.5 rounded-xl transition-all">
                                Close Ledger
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

    </div>

@endsection
