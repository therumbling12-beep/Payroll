@extends('layouts.app')

@php
    $pageTitle = 'Payroll Summary & Statutory Reports';
    $currentPage = 'payroll.reports';
@endphp

@section('content')

    <div x-data="{ 
        activeTab: 'summary'
    }" class="space-y-6">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-extrabold font-outfit text-gray-900">Company Payroll Summary & Statutory Remittances</h1>
                <p class="text-xs text-gray-500 mt-0.5">Aggregate payroll registers, statutory government collection schedules (SSS R-3, PhilHealth RF-1, Pag-IBIG MCRF), BIR Form 1604-C Alphalist, and Minimum Wage Compliance.</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-black bg-blue-50 text-blue-800 border border-blue-200">
                    <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                    Statutory Reporting Engine
                </span>
            </div>
        </div>

        <!-- Tab Navigation Bar -->
        <div class="bg-gray-100/80 p-1 rounded-2xl flex items-center gap-1 overflow-x-auto">
            <button type="button" @click="activeTab = 'summary'" 
                    :class="activeTab === 'summary' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 font-bold hover:text-gray-700'"
                    class="px-4 py-2 text-xs rounded-xl transition-all whitespace-nowrap flex items-center gap-2">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Period Summary
            </button>

            <button type="button" @click="activeTab = 'sss'" 
                    :class="activeTab === 'sss' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 font-bold hover:text-gray-700'"
                    class="px-4 py-2 text-xs rounded-xl transition-all whitespace-nowrap flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                SSS R-3 Schedule
            </button>

            <button type="button" @click="activeTab = 'philhealth'" 
                    :class="activeTab === 'philhealth' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 font-bold hover:text-gray-700'"
                    class="px-4 py-2 text-xs rounded-xl transition-all whitespace-nowrap flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                PhilHealth RF-1 Schedule
            </button>

            <button type="button" @click="activeTab = 'pagibig'" 
                    :class="activeTab === 'pagibig' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 font-bold hover:text-gray-700'"
                    class="px-4 py-2 text-xs rounded-xl transition-all whitespace-nowrap flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-amber-600"></span>
                Pag-IBIG MCRF Schedule
            </button>

            <button type="button" @click="activeTab = 'alphalist'" 
                    :class="activeTab === 'alphalist' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 font-bold hover:text-gray-700'"
                    class="px-4 py-2 text-xs rounded-xl transition-all whitespace-nowrap flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-purple-600"></span>
                BIR 1604-C Alphalist
            </button>

            <button type="button" @click="activeTab = 'minwage'" 
                    :class="activeTab === 'minwage' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 font-bold hover:text-gray-700'"
                    class="px-4 py-2 text-xs rounded-xl transition-all whitespace-nowrap flex items-center gap-2">
                <span class="w-2 h-2 rounded-full {{ $wageCompliance['is_fully_compliant'] ? 'bg-emerald-500' : 'bg-rose-500 animate-pulse' }}"></span>
                Minimum Wage Monitor
            </button>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 1: PERIOD SUMMARY & COSTING -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'summary'" x-transition class="space-y-6">

            <!-- Filter Controls -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-4 shadow-sm">
                <form action="{{ route('payroll.reports') }}" method="GET" class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex flex-1 items-center gap-3">
                        <select name="period" onchange="this.form.submit()" 
                                class="text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-[#F44336]">
                            @foreach($cutoffs as $c)
                                <option value="{{ $c->cutoff_period }}" {{ $cutoff === $c->cutoff_period ? 'selected' : '' }}>
                                    Cutoff: {{ $c->cutoff_period }}
                                </option>
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
                            Refresh Report
                        </button>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('payroll.export.register', $cutoff) }}" 
                           class="bg-gray-900 hover:bg-black text-white font-bold text-xs px-4 py-2.5 rounded-xl transition-all shadow-xs flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Export Master Register CSV
                        </a>
                    </div>
                </form>
            </div>

            <!-- Aggregated Metric Cards -->
            @php
                $totalGross = (float)$computations->sum('gross_pay');
                $totalDeductions = (float)$computations->sum('total_deductions');
                $totalNet = (float)$computations->sum('net_pay');
                $sumErSss = (float)$computations->sum('sss_employer');
                $sumErPhil = (float)$computations->sum('philhealth_employer');
                $sumErPagibig = (float)$computations->sum('pagibig_employer');
                $totalEmployerCost = $totalGross + $sumErSss + $sumErPhil + $sumErPagibig;
            @endphp
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block">Gross Payroll</span>
                    <div class="text-xl font-black font-outfit text-gray-900 mt-1">PHP {{ number_format($totalGross, 2) }}</div>
                    <span class="text-[11px] text-gray-400 font-medium mt-0.5 block">{{ $computations->count() }} Processed personnel</span>
                </div>

                <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block">Total Deductions</span>
                    <div class="text-xl font-black font-outfit text-rose-600 mt-1">-PHP {{ number_format($totalDeductions, 2) }}</div>
                    <span class="text-[11px] text-gray-400 font-medium mt-0.5 block">Gov't taxes, loans & statutory deductions</span>
                </div>

                <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block">Net Take-Home Pay</span>
                    <div class="text-xl font-black font-outfit text-emerald-600 mt-1">PHP {{ number_format($totalNet, 2) }}</div>
                    <span class="text-[11px] text-gray-400 font-medium mt-0.5 block">Approved cash disbursement</span>
                </div>

                <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block">Total Employer Labor Cost</span>
                    <div class="text-xl font-black font-outfit text-purple-600 mt-1">PHP {{ number_format($totalEmployerCost, 2) }}</div>
                    <span class="text-[11px] text-gray-400 font-medium mt-0.5 block">Gross + Statutory ER Matches</span>
                </div>
            </div>

            <!-- Departmental Costing Table -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h2 class="text-base font-black font-outfit text-gray-900">Departmental Payroll Costing Breakdown</h2>
                    <span class="text-xs text-gray-500 font-bold">Cutoff: {{ $cutoff }}</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200 text-xs font-black text-gray-400 uppercase tracking-wider">
                                <th class="py-3 px-4">Department</th>
                                <th class="py-3 px-4 text-center">Headcount</th>
                                <th class="py-3 px-4 text-right">Base Salaries</th>
                                <th class="py-3 px-4 text-right">Trip & Overtime Pay</th>
                                <th class="py-3 px-4 text-right">Gross Pay</th>
                                <th class="py-3 px-4 text-right">Employer Taxes</th>
                                <th class="py-3 px-4 text-right">Net Payout</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-xs">
                            @foreach($departments as $dept)
                                @php
                                    $deptComps = $computations->filter(fn($c) => $c->employee?->department_id === $dept->id);
                                    if ($deptComps->isEmpty()) continue;
                                    $dGross = (float)$deptComps->sum('gross_pay');
                                    $dNet = (float)$deptComps->sum('net_pay');
                                    $dBase = (float)$deptComps->sum('base_pay');
                                    $dTrips = (float)$deptComps->sum('trip_earnings') + (float)$deptComps->sum('overtime_pay') + (float)$deptComps->sum('holiday_pay');
                                    $dErTaxes = (float)$deptComps->sum('sss_employer') + (float)$deptComps->sum('philhealth_employer') + (float)$deptComps->sum('pagibig_employer');
                                @endphp
                                <tr class="hover:bg-gray-50/75 transition-colors">
                                    <td class="py-3.5 px-4 font-black text-gray-900">
                                        {{ $dept->name }}
                                    </td>
                                    <td class="py-3.5 px-4 text-center font-bold text-gray-700">
                                        {{ $deptComps->count() }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-mono font-bold text-gray-700">
                                        PHP {{ number_format($dBase, 2) }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-mono font-bold text-blue-700">
                                        PHP {{ number_format($dTrips, 2) }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-black font-outfit text-gray-900">
                                        PHP {{ number_format($dGross, 2) }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-mono font-bold text-purple-700">
                                        PHP {{ number_format($dErTaxes, 2) }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-black font-outfit text-emerald-700">
                                        PHP {{ number_format($dNet, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- ========================================================================= -->
        <!-- TAB 2: SSS R-3 COLLECTION SCHEDULE -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'sss'" x-transition class="space-y-6">
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-100 pb-4">
                    <div>
                        <h2 class="text-base font-black font-outfit text-gray-900">Social Security System (SSS) Form R-3 Contribution Collection Schedule</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Official SSS remittance schedule with Monthly Salary Credit (MSC) and Employee/Employer shares (RA 11199).</p>
                    </div>
                    <a href="{{ route('payroll.export.sss', $cutoff) }}" 
                       class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition-all shadow-xs flex items-center gap-1.5 shrink-0">
                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Export SSS Form R-3 CSV
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 bg-blue-50/50 p-4 rounded-2xl border border-blue-100 text-xs">
                    <div>
                        <span class="text-gray-500 font-bold block">Total Monthly Salary Credit (MSC)</span>
                        <span class="text-base font-black font-outfit text-gray-900 mt-0.5 block">PHP {{ number_format($sssSummary['total_msc'], 2) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 font-bold block">Total Employee Share (4.5%)</span>
                        <span class="text-base font-black font-outfit text-blue-700 mt-0.5 block">PHP {{ number_format($sssSummary['total_ee'], 2) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 font-bold block">Total Employer Share (9.5%)</span>
                        <span class="text-base font-black font-outfit text-blue-900 mt-0.5 block">PHP {{ number_format($sssSummary['total_er'], 2) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 font-bold block">Grand SSS Remittance (+EC)</span>
                        <span class="text-base font-black font-outfit text-emerald-700 mt-0.5 block">PHP {{ number_format($sssSummary['grand_total'], 2) }}</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200 text-xs font-black text-gray-400 uppercase tracking-wider">
                                <th class="py-3 px-4">SS Number</th>
                                <th class="py-3 px-4">Employee Name</th>
                                <th class="py-3 px-4 text-right">MSC Basis</th>
                                <th class="py-3 px-4 text-right">EE Share (4.5%)</th>
                                <th class="py-3 px-4 text-right">ER Share (9.5%)</th>
                                <th class="py-3 px-4 text-right">EC Share</th>
                                <th class="py-3 px-4 text-right">Total SSS Due</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-xs">
                            @foreach($computations as $c)
                                @php
                                    $emp = $c->employee;
                                    $msc = max(5000.00, round((float)$c->gross_pay * 2 / 500) * 500);
                                    $ee = (float)$c->sss_deduction;
                                    $er = (float)($c->sss_employer ?? $ee * 2);
                                    $ec = (float)($c->ec_contribution ?? 10.00);
                                @endphp
                                <tr class="hover:bg-gray-50/75">
                                    <td class="py-3 px-4 font-mono font-bold text-gray-700">00-0000000-0</td>
                                    <td class="py-3 px-4 font-black text-gray-900">{{ $emp?->last_name }}, {{ $emp?->first_name }}</td>
                                    <td class="py-3 px-4 text-right font-mono font-bold text-gray-700">PHP {{ number_format($msc, 2) }}</td>
                                    <td class="py-3 px-4 text-right font-mono font-bold text-blue-700">PHP {{ number_format($ee, 2) }}</td>
                                    <td class="py-3 px-4 text-right font-mono font-bold text-blue-900">PHP {{ number_format($er, 2) }}</td>
                                    <td class="py-3 px-4 text-right font-mono font-bold text-gray-600">PHP {{ number_format($ec, 2) }}</td>
                                    <td class="py-3 px-4 text-right font-black font-outfit text-emerald-700">PHP {{ number_format($ee + $er + $ec, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 3: PHILHEALTH RF-1 SCHEDULE -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'philhealth'" x-transition class="space-y-6">
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-100 pb-4">
                    <div>
                        <h2 class="text-base font-black font-outfit text-gray-900">Philippine Health Insurance Corporation (PhilHealth) Form RF-1</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Quarterly/Monthly remittance return at statutory 5.0% premium rate (2.5% EE / 2.5% ER).</p>
                    </div>
                    <a href="{{ route('payroll.export.philhealth', $cutoff) }}" 
                       class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition-all shadow-xs flex items-center gap-1.5 shrink-0">
                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Export PhilHealth Form RF-1 CSV
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 bg-emerald-50/50 p-4 rounded-2xl border border-emerald-100 text-xs">
                    <div>
                        <span class="text-gray-500 font-bold block">Total Monthly Basic Salary (MBS)</span>
                        <span class="text-base font-black font-outfit text-gray-900 mt-0.5 block">PHP {{ number_format($philhealthSummary['total_mbs'], 2) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 font-bold block">Total Employee Premium (2.5%)</span>
                        <span class="text-base font-black font-outfit text-emerald-700 mt-0.5 block">PHP {{ number_format($philhealthSummary['total_ee'], 2) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 font-bold block">Total Employer Premium (2.5%)</span>
                        <span class="text-base font-black font-outfit text-emerald-900 mt-0.5 block">PHP {{ number_format($philhealthSummary['total_er'], 2) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 font-bold block">Grand PhilHealth Remittance</span>
                        <span class="text-base font-black font-outfit text-emerald-800 mt-0.5 block">PHP {{ number_format($philhealthSummary['grand_total'], 2) }}</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200 text-xs font-black text-gray-400 uppercase tracking-wider">
                                <th class="py-3 px-4">PhilHealth PIN</th>
                                <th class="py-3 px-4">Employee Name</th>
                                <th class="py-3 px-4 text-right">Monthly Basic Salary</th>
                                <th class="py-3 px-4 text-right">Employee Share (2.5%)</th>
                                <th class="py-3 px-4 text-right">Employer Share (2.5%)</th>
                                <th class="py-3 px-4 text-right">Total Premium Due</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-xs">
                            @foreach($computations as $c)
                                @php
                                    $emp = $c->employee;
                                    $ee = (float)$c->philhealth_deduction;
                                    $er = (float)($c->philhealth_employer ?? $ee);
                                @endphp
                                <tr class="hover:bg-gray-50/75">
                                    <td class="py-3 px-4 font-mono font-bold text-gray-700">00-000000000-0</td>
                                    <td class="py-3 px-4 font-black text-gray-900">{{ $emp?->last_name }}, {{ $emp?->first_name }}</td>
                                    <td class="py-3 px-4 text-right font-mono font-bold text-gray-700">PHP {{ number_format((float)$c->base_pay * 2, 2) }}</td>
                                    <td class="py-3 px-4 text-right font-mono font-bold text-emerald-700">PHP {{ number_format($ee, 2) }}</td>
                                    <td class="py-3 px-4 text-right font-mono font-bold text-emerald-900">PHP {{ number_format($er, 2) }}</td>
                                    <td class="py-3 px-4 text-right font-black font-outfit text-emerald-800">PHP {{ number_format($ee + $er, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 4: PAG-IBIG HDMF MCRF SCHEDULE -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'pagibig'" x-transition class="space-y-6">
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-100 pb-4">
                    <div>
                        <h2 class="text-base font-black font-outfit text-gray-900">Home Development Mutual Fund (Pag-IBIG) Form MCRF</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Monthly Member Contribution Remittance Form (Mandatory statutory contribution schedule).</p>
                    </div>
                    <a href="{{ route('payroll.export.pagibig', $cutoff) }}" 
                       class="bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition-all shadow-xs flex items-center gap-1.5 shrink-0">
                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Export Pag-IBIG Form MCRF CSV
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 bg-amber-50/50 p-4 rounded-2xl border border-amber-100 text-xs">
                    <div>
                        <span class="text-gray-500 font-bold block">Total Monthly Compensation</span>
                        <span class="text-base font-black font-outfit text-gray-900 mt-0.5 block">PHP {{ number_format($pagibigSummary['total_compensation'], 2) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 font-bold block">Total Employee Share</span>
                        <span class="text-base font-black font-outfit text-amber-700 mt-0.5 block">PHP {{ number_format($pagibigSummary['total_ee'], 2) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 font-bold block">Total Employer Share</span>
                        <span class="text-base font-black font-outfit text-amber-900 mt-0.5 block">PHP {{ number_format($pagibigSummary['total_er'], 2) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 font-bold block">Grand Pag-IBIG Remittance</span>
                        <span class="text-base font-black font-outfit text-emerald-700 mt-0.5 block">PHP {{ number_format($pagibigSummary['grand_total'], 2) }}</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200 text-xs font-black text-gray-400 uppercase tracking-wider">
                                <th class="py-3 px-4">Pag-IBIG MID</th>
                                <th class="py-3 px-4">Employee Name</th>
                                <th class="py-3 px-4 text-right">Monthly Compensation</th>
                                <th class="py-3 px-4 text-right">Employee Share (EE)</th>
                                <th class="py-3 px-4 text-right">Employer Share (ER)</th>
                                <th class="py-3 px-4 text-right">Total Remittance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-xs">
                            @foreach($computations as $c)
                                @php
                                    $emp = $c->employee;
                                    $ee = (float)$c->pagibig_deduction;
                                    $er = (float)($c->pagibig_employer ?? $ee);
                                @endphp
                                <tr class="hover:bg-gray-50/75">
                                    <td class="py-3 px-4 font-mono font-bold text-gray-700">0000-0000-0000</td>
                                    <td class="py-3 px-4 font-black text-gray-900">{{ $emp?->last_name }}, {{ $emp?->first_name }}</td>
                                    <td class="py-3 px-4 text-right font-mono font-bold text-gray-700">PHP {{ number_format((float)$c->gross_pay * 2, 2) }}</td>
                                    <td class="py-3 px-4 text-right font-mono font-bold text-amber-700">PHP {{ number_format($ee, 2) }}</td>
                                    <td class="py-3 px-4 text-right font-mono font-bold text-amber-900">PHP {{ number_format($er, 2) }}</td>
                                    <td class="py-3 px-4 text-right font-black font-outfit text-emerald-700">PHP {{ number_format($ee + $er, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 5: BIR 1604-C & ALPHALIST SCHEDULE 7.1 -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'alphalist'" x-transition class="space-y-6">
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-100 pb-4">
                    <div>
                        <h2 class="text-base font-black font-outfit text-gray-900">BIR Form 1604-C Annual Information Return & Alphalist (Schedule 7.1)</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Annualized TRAIN tax computation, non-taxable 13th month exemption, and Year-End Tax Adjustments (Refund/Payable).</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <form action="{{ route('payroll.reports') }}" method="GET" class="flex items-center gap-2">
                            <input type="hidden" name="period" value="{{ $cutoff }}">
                            <select name="year" onchange="this.form.submit()" class="text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-gray-800">
                                @for($y = (int)date('Y'); $y >= (int)date('Y') - 2; $y--)
                                    <option value="{{ $y }}" {{ $year === $y ? 'selected' : '' }}>Tax Year: {{ $y }}</option>
                                @endfor
                            </select>
                        </form>

                        <a href="{{ route('payroll.export.alphalist', $year) }}" 
                           class="bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition-all shadow-xs flex items-center gap-1.5 shrink-0">
                            <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Export BIR 1604-C CSV
                        </a>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 bg-purple-50/50 p-4 rounded-2xl border border-purple-100 text-xs">
                    <div>
                        <span class="text-gray-500 font-bold block">Total Annual Gross Compensation</span>
                        <span class="text-base font-black font-outfit text-gray-900 mt-0.5 block">PHP {{ number_format($alphalist['total_gross_compensation'], 2) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 font-bold block">Non-Taxable Gov't Deductions</span>
                        <span class="text-base font-black font-outfit text-purple-700 mt-0.5 block">PHP {{ number_format($alphalist['total_non_taxable_statutory'], 2) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 font-bold block">Taxable Compensation Income</span>
                        <span class="text-base font-black font-outfit text-gray-900 mt-0.5 block">PHP {{ number_format($alphalist['total_taxable_compensation'], 2) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 font-bold block">Total Annual Tax Due (TRAIN)</span>
                        <span class="text-base font-black font-outfit text-rose-600 mt-0.5 block">PHP {{ number_format($alphalist['total_tax_due'], 2) }}</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200 text-xs font-black text-gray-400 uppercase tracking-wider">
                                <th class="py-3 px-4">TIN / Employee</th>
                                <th class="py-3 px-4">Department & Position</th>
                                <th class="py-3 px-4 text-right">Gross Compensation</th>
                                <th class="py-3 px-4 text-right">Non-Taxable Deductions</th>
                                <th class="py-3 px-4 text-right">13th Mo. Exempt (PHP 90k)</th>
                                <th class="py-3 px-4 text-right">Taxable Income</th>
                                <th class="py-3 px-4 text-right">Annual Tax Due</th>
                                <th class="py-3 px-4 text-right">Tax Withheld</th>
                                <th class="py-3 px-4 text-center">Year-End Adjustment</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-xs">
                            @foreach($alphalist['employees'] as $row)
                                <tr class="hover:bg-gray-50/75">
                                    <td class="py-3 px-4 font-black text-gray-900">
                                        <div>{{ $row['full_name'] }}</div>
                                        <span class="text-xs text-gray-400 font-mono">TIN: {{ $row['tin'] }}</span>
                                    </td>
                                    <td class="py-3 px-4 text-gray-700 font-bold">
                                        <div>{{ $row['position'] }}</div>
                                        <span class="text-xs text-gray-400">{{ $row['department'] }}</span>
                                    </td>
                                    <td class="py-3 px-4 text-right font-black font-outfit text-gray-900">PHP {{ number_format((float)$row['gross_compensation'], 2) }}</td>
                                    <td class="py-3 px-4 text-right font-mono font-bold text-gray-600">-PHP {{ number_format((float)$row['non_taxable_statutory'], 2) }}</td>
                                    <td class="py-3 px-4 text-right font-mono font-bold text-purple-700">PHP {{ number_format((float)$row['exempt_thirteenth_month'], 2) }}</td>
                                    <td class="py-3 px-4 text-right font-mono font-bold text-gray-900">PHP {{ number_format((float)$row['taxable_compensation'], 2) }}</td>
                                    <td class="py-3 px-4 text-right font-black font-outfit text-rose-600">PHP {{ number_format((float)$row['annual_tax_due'], 2) }}</td>
                                    <td class="py-3 px-4 text-right font-mono font-bold text-gray-700">PHP {{ number_format((float)$row['tax_withheld'], 2) }}</td>
                                    <td class="py-3 px-4 text-center">
                                        @if($row['adjustment_type'] === 'refund')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-black bg-emerald-50 text-emerald-800 border border-emerald-200">
                                                Refund: PHP {{ number_format(abs((float)$row['tax_adjustment']), 2) }}
                                            </span>
                                        @elseif($row['adjustment_type'] === 'payable')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-black bg-rose-50 text-rose-800 border border-rose-200">
                                                Payable: PHP {{ number_format(abs((float)$row['tax_adjustment']), 2) }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-black bg-gray-100 text-gray-700">
                                                Balanced
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 6: DOLE MINIMUM WAGE COMPLIANCE MONITOR -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'minwage'" x-transition class="space-y-6">
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-100 pb-4">
                    <div>
                        <h2 class="text-base font-black font-outfit text-gray-900">DOLE Minimum Wage Compliance Monitor (Wage Order No. {{ $wageCompliance['wage_order_no'] }})</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Automated statutory wage floor compliance verification against regional daily wage standards (NCR Statutory Floor: PHP {{ number_format($wageCompliance['statutory_daily_rate'], 2) }}/day).</p>
                    </div>
                    <span class="px-3.5 py-1.5 rounded-full text-xs font-black border 
                        {{ $wageCompliance['is_fully_compliant'] ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' }}">
                        {{ $wageCompliance['is_fully_compliant'] ? '100% Fully Compliant' : $wageCompliance['non_compliant_count'] . ' Compliance Violations Detected' }}
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100 text-xs">
                        <span class="text-gray-500 font-bold block">Statutory Minimum Floor (NCR-24)</span>
                        <span class="text-base font-black font-outfit text-gray-900 mt-0.5 block">PHP {{ number_format($wageCompliance['statutory_daily_rate'], 2) }} / day</span>
                    </div>
                    <div class="bg-emerald-50/50 p-4 rounded-2xl border border-emerald-100 text-xs">
                        <span class="text-gray-500 font-bold block">Compliant Personnel</span>
                        <span class="text-base font-black font-outfit text-emerald-700 mt-0.5 block">{{ $wageCompliance['compliant_count'] }} Employees</span>
                    </div>
                    <div class="bg-rose-50/50 p-4 rounded-2xl border border-rose-100 text-xs">
                        <span class="text-gray-500 font-bold block">Sub-Minimum Exceptions</span>
                        <span class="text-base font-black font-outfit text-rose-600 mt-0.5 block">{{ $wageCompliance['non_compliant_count'] }} Exceptions</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200 text-xs font-black text-gray-400 uppercase tracking-wider">
                                <th class="py-3 px-4">Employee</th>
                                <th class="py-3 px-4">Department & Position</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4 text-right">Monthly Rate</th>
                                <th class="py-3 px-4 text-right">Effective Daily Rate</th>
                                <th class="py-3 px-4 text-right">Statutory Minimum</th>
                                <th class="py-3 px-4 text-right">Variance / Margin</th>
                                <th class="py-3 px-4 text-center">Compliance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-xs">
                            @foreach($wageCompliance['evaluations'] as $eval)
                                <tr class="hover:bg-gray-50/75">
                                    <td class="py-3 px-4 font-black text-gray-900">
                                        <div>{{ $eval['full_name'] }}</div>
                                        <span class="text-xs text-gray-400 font-mono">{{ $eval['employee_code'] }}</span>
                                    </td>
                                    <td class="py-3 px-4 text-gray-700 font-bold">
                                        <div>{{ $eval['position'] }}</div>
                                        <span class="text-xs text-gray-400">{{ $eval['department'] }}</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-2.5 py-0.5 rounded-lg text-xs font-bold {{ $eval['is_driver'] ? 'bg-purple-50 text-purple-800' : 'bg-blue-50 text-blue-800' }}">
                                            {{ $eval['is_driver'] ? 'Contractor' : ucfirst($eval['employment_status']) }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono font-bold text-gray-700">
                                        {{ $eval['monthly_rate'] > 0 ? 'PHP ' . number_format($eval['monthly_rate'], 2) : 'Variable/Trips' }}
                                    </td>
                                    <td class="py-3 px-4 text-right font-black font-outfit text-gray-900">
                                        PHP {{ number_format($eval['effective_daily_rate'], 2) }}
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono font-bold text-gray-500">
                                        PHP {{ number_format($eval['statutory_daily_rate'], 2) }}
                                    </td>
                                    <td class="py-3 px-4 text-right font-black font-outfit {{ $eval['variance'] >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">
                                        {{ $eval['variance'] >= 0 ? '+PHP ' . number_format($eval['variance'], 2) : '-PHP ' . number_format(abs($eval['variance']), 2) }}
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        @if($eval['is_compliant'])
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-emerald-50 text-emerald-800 border border-emerald-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                                Compliant
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-rose-50 text-rose-800 border border-rose-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-600"></span>
                                                Below Wage Floor
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

@endsection
