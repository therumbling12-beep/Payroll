@extends('layouts.app')

@php
    $pageTitle = 'Benefits Cost Tracking & Corporate Budget Hub';
    $currentPage = 'hmo.cost-tracking';
@endphp

@section('content')

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-black font-outfit text-gray-900 tracking-tight">Benefits Cost & Corporate Budget Hub</h1>
            <p class="text-xs text-gray-500 mt-1">Consolidated financial command center combining workforce Total Cost of Employment (TCE), statutory employer burdens, and formal budget requisitions with Team 5 Finance.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="flex items-center gap-2 text-xs font-bold text-gray-800 bg-white border border-gray-200 px-3.5 py-1.5 rounded-full shadow-2xs">
                <span class="w-2.5 h-2.5 rounded-full bg-blue-500 animate-pulse"></span>
                Team 5 Financial Stream Connected
            </span>
            <span class="text-xs text-gray-400 font-semibold font-mono">{{ now()->format('M j, Y') }}</span>
        </div>
    </div>

    @if(session('status'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs rounded-2xl font-bold flex items-center gap-2.5 shadow-2xs">
            <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session('status') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-900 text-xs rounded-2xl font-bold flex items-center gap-2.5 shadow-2xs">
            <svg class="w-4 h-4 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            {{ session('error') }}
        </div>
    @endif

    <!-- Alpine.js Financial Container -->
    <div x-data="{ 
        activeTab: '{{ $tab ?? 'tce' }}', {{-- 'tce', 'budget' --}}
        showRequisitionModal: false
    }" class="space-y-6 pb-12">

        <!-- Top Navigation Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-200 pb-3">
            <div class="flex items-center gap-1.5 bg-gray-100 p-1 rounded-2xl">
                
                <!-- Tab 1: Total Cost of Employment (TCE) -->
                <button type="button" @click="activeTab = 'tce'" 
                        :class="activeTab === 'tce' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold'" 
                        class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#F44336]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Total Cost of Employment (TCE)
                    <span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-gray-200 text-gray-800">{{ $stats['headcount'] }} Staff</span>
                </button>

                <!-- Tab 2: Budget Requisitions -->
                <button type="button" @click="activeTab = 'budget'" 
                        :class="activeTab === 'budget' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold'" 
                        class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#F44336]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    Corporate Budget Requisitions
                    <span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-gray-200 text-gray-800">{{ $requisitions->total() }}</span>
                </button>
            </div>

            <!-- Action Button -->
            <button @click="showRequisitionModal = true" type="button" 
                    class="bg-gray-900 hover:bg-black text-white font-black text-xs px-4 py-2 rounded-xl transition-all shadow-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                New Budget Requisition
            </button>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 1: TOTAL COST OF EMPLOYMENT (TCE) & DEPARTMENT AUDIT -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'tce'" x-transition class="space-y-6">

            <!-- Department Cost Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Monthly TCE</p>
                    <p class="text-xl font-black font-outfit text-gray-900 mt-1">PHP {{ number_format($stats['total_tce'], 2) }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">Combined salary & benefits expense</p>
                </div>

                <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">HMO Corporate Share</p>
                    <p class="text-xl font-black font-outfit text-purple-600 mt-1">PHP {{ number_format($stats['total_hmo_cost'], 2) }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">Employer monthly healthcare subsidy</p>
                </div>

                <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Gov't Statutory Burden</p>
                    <p class="text-xl font-black font-outfit text-emerald-600 mt-1">PHP {{ number_format($stats['total_govt_cost'], 2) }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">SSS, PhilHealth, Pag-IBIG employer portions</p>
                </div>

                <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Active Headcount</p>
                    <p class="text-xl font-black font-outfit text-blue-600 mt-1">{{ $stats['headcount'] }} Staff</p>
                    <p class="text-[11px] text-gray-500 mt-1">Enrolled employees & driver partners</p>
                </div>
            </div>

            <!-- Department Cost Summary Cards -->
            @if(isset($departmentSummaries) && $departmentSummaries->isNotEmpty())
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach($departmentSummaries as $dept)
                        <div class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm space-y-3">
                            <div class="flex items-center justify-between border-b border-gray-100 pb-2.5">
                                <div>
                                    <h3 class="font-bold font-outfit text-gray-900 text-sm">{{ $dept['department_name'] }}</h3>
                                    <p class="text-[10px] text-gray-400 font-medium">{{ $dept['headcount'] }} Employees</p>
                                </div>
                                <span class="px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 text-[10px] font-black">
                                    Dept Total
                                </span>
                            </div>
                            <div class="space-y-1.5 text-xs">
                                <div class="flex justify-between text-gray-600">
                                    <span>Basic Salary:</span>
                                    <span class="font-mono font-semibold">PHP {{ number_format($dept['total_basic'], 2) }}</span>
                                </div>
                                <div class="flex justify-between text-gray-600">
                                    <span>Allowances:</span>
                                    <span class="font-mono font-semibold">PHP {{ number_format($dept['total_allowances'], 2) }}</span>
                                </div>
                                <div class="flex justify-between text-purple-700">
                                    <span>Company Health Share:</span>
                                    <span class="font-mono font-bold">PHP {{ number_format($dept['total_hmo'], 2) }}</span>
                                </div>
                                <div class="flex justify-between text-emerald-700">
                                    <span>Gov't Contributions:</span>
                                    <span class="font-mono font-bold">PHP {{ number_format($dept['total_govt'], 2) }}</span>
                                </div>
                                <div class="flex justify-between font-black text-gray-900 border-t border-gray-100 pt-2 text-sm font-outfit">
                                    <span>Total Monthly Cost:</span>
                                    <span class="text-[#F44336]">PHP {{ number_format($dept['total_tce'], 2) }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- Filter & Search Bar -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-4 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <form action="{{ route('hmo.cost-tracking') }}" method="GET" class="flex flex-wrap items-center gap-3 flex-1">
                    <input type="hidden" name="tab" value="tce">

                    <div class="relative flex-1 min-w-[220px]">
                        <input type="text" name="search" value="{{ $search }}" placeholder="Search by employee name or code..." 
                               class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl pl-9 pr-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-gray-900">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    <select name="department_id" class="text-xs bg-gray-50 border border-gray-200 rounded-xl px-3 py-2.5 text-gray-800 font-bold focus:outline-none focus:border-gray-900">
                        <option value="all">All Departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ (string)$departmentId === (string)$dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>

                    <button type="submit" class="bg-gray-900 hover:bg-black text-white text-xs font-black px-4 py-2.5 rounded-xl transition-all shadow-sm">
                        Filter TCE
                    </button>
                </form>

                <!-- Download CSV Button -->
                <a href="{{ route('hmo.cost-tracking.export-tce') }}" 
                   class="bg-gray-900 hover:bg-black text-white text-xs font-black px-4 py-2.5 rounded-xl transition-all shadow-sm flex items-center gap-2 flex-shrink-0">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Download Cost Report (CSV)
                </a>
            </div>

            <!-- Employee TCE Master Table -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-gray-50/80 border-b border-gray-100 text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                <th class="py-3.5 px-4">Employee</th>
                                <th class="py-3.5 px-4 text-right">Basic Salary</th>
                                <th class="py-3.5 px-4 text-right">Allowances</th>
                                <th class="py-3.5 px-4 text-right">HMO Employer</th>
                                <th class="py-3.5 px-4 text-right">SSS Employer</th>
                                <th class="py-3.5 px-4 text-right">PhilHealth ER</th>
                                <th class="py-3.5 px-4 text-right">Pag-IBIG ER</th>
                                <th class="py-3.5 px-4 text-right bg-gray-100/70 font-black text-gray-900">Total TCE / Mo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($tceData as $item)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-gray-900 font-outfit">{{ $item['employee']->first_name }} {{ $item['employee']->last_name }}</div>
                                        <div class="text-[10px] text-gray-400 font-mono">
                                            {{ $item['employee']->employee_code }} • {{ $item['employee']->department?->name ?? 'General' }}
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-mono text-gray-700">
                                        PHP {{ number_format($item['basic_salary'], 2) }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-mono text-gray-700">
                                        PHP {{ number_format($item['allowances'], 2) }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-mono text-purple-700 font-bold">
                                        PHP {{ number_format($item['hmo_premium'], 2) }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-mono text-emerald-700">
                                        PHP {{ number_format($item['sss_employer'], 2) }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-mono text-emerald-700">
                                        PHP {{ number_format($item['philhealth_employer'], 2) }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-mono text-emerald-700">
                                        PHP {{ number_format($item['pagibig_employer'], 2) }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-mono font-black text-gray-900 bg-gray-50/70">
                                        PHP {{ number_format($item['total_tce'], 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-12 text-gray-400 text-xs">
                                        No employee records match your search criteria.
                                    </td>
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

        </div>

        <!-- ========================================================================= -->
        <!-- TAB 2: CORPORATE BUDGET REQUISITIONS & FINANCE ALLOCATION STEPPER -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'budget'" x-transition class="space-y-6">

            <!-- 4 Budget Stat Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Funds Requested</p>
                    <p class="text-xl font-black font-outfit text-gray-900 mt-1">PHP {{ number_format($budgetStats['total_requested'], 2) }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">Cumulative fund requests submitted</p>
                </div>

                <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Awaiting Finance Review</p>
                    <p class="text-xl font-black font-outfit text-amber-600 mt-1">{{ $budgetStats['pending_count'] }} Requisitions</p>
                    <p class="text-[11px] text-gray-500 mt-1">Under review by Team 5 Financial</p>
                </div>

                <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Approved by Finance</p>
                    <p class="text-xl font-black font-outfit text-emerald-600 mt-1">PHP {{ number_format($budgetStats['total_approved'], 2) }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">Approved budget ready for disbursement</p>
                </div>

                <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Funds Released</p>
                    <p class="text-xl font-black font-outfit text-blue-600 mt-1">PHP {{ number_format($budgetStats['total_released'], 2) }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">Disbursed to healthcare providers/pools</p>
                </div>
            </div>

            <!-- Budget Tracker Progress Card -->
            <div class="bg-gradient-to-br from-gray-900 via-gray-800 to-gray-950 rounded-2xl p-6 text-white shadow-xl space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-white/10 pb-3">
                    <div>
                        <span class="px-2.5 py-0.5 bg-blue-500/20 text-blue-300 border border-blue-500/30 text-[10px] font-black uppercase rounded-full tracking-wider">
                            Finance Health Budget Tracker
                        </span>
                        <h2 class="text-base font-black font-outfit text-white mt-1">Total Health Budget Given vs. Money Spent</h2>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] text-gray-400 uppercase font-mono">Budget Used</p>
                        <p class="text-lg font-black font-outfit text-emerald-400">{{ $budgetStats['percent_used'] }}%</p>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="space-y-1.5">
                    <div class="w-full bg-white/10 rounded-full h-3 overflow-hidden p-0.5 border border-white/10">
                        <div class="h-2 rounded-full bg-gradient-to-r from-emerald-400 to-blue-500 transition-all duration-500" 
                             style="width: {{ max(2, min(100, $budgetStats['percent_used'])) }}%"></div>
                    </div>
                    <div class="flex justify-between text-[11px] text-gray-400 font-mono">
                        <span>Money Spent: PHP {{ number_format($budgetStats['total_disbursed_spend'], 2) }}</span>
                        <span>Approved Budget: PHP {{ number_format($budgetStats['total_approved_budget'], 2) }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2 text-xs">
                    <div class="bg-white/5 p-3 rounded-xl border border-white/5">
                        <p class="text-[10px] text-gray-400">Total Money Approved</p>
                        <p class="font-bold font-outfit text-white mt-0.5">PHP {{ number_format($budgetStats['total_approved_budget'], 2) }}</p>
                    </div>
                    <div class="bg-white/5 p-3 rounded-xl border border-white/5">
                        <p class="text-[10px] text-gray-400">Total Money Released</p>
                        <p class="font-bold font-outfit text-blue-400 mt-0.5">PHP {{ number_format($budgetStats['total_disbursed_spend'], 2) }}</p>
                    </div>
                    <div class="bg-white/5 p-3 rounded-xl border border-white/5">
                        <p class="text-[10px] text-gray-400">Remaining Budget Balance</p>
                        <p class="font-bold font-outfit text-emerald-400 mt-0.5">PHP {{ number_format($budgetStats['remaining_balance'], 2) }}</p>
                    </div>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-4 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <form action="{{ route('hmo.cost-tracking') }}" method="GET" class="flex flex-wrap items-center gap-3 flex-1">
                    <input type="hidden" name="tab" value="budget">

                    <select name="budget_status" class="text-xs bg-gray-50 border border-gray-200 rounded-xl px-3 py-2.5 text-gray-800 font-bold focus:outline-none focus:border-gray-900">
                        <option value="all">All Request Statuses</option>
                        <option value="awaiting_approval" {{ $budgetStatus === 'awaiting_approval' ? 'selected' : '' }}>Awaiting Approval</option>
                        <option value="approved" {{ $budgetStatus === 'approved' ? 'selected' : '' }}>Approved by Finance</option>
                        <option value="released" {{ $budgetStatus === 'released' ? 'selected' : '' }}>Funds Released</option>
                        <option value="rejected" {{ $budgetStatus === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>

                    <select name="budget_category" class="text-xs bg-gray-50 border border-gray-200 rounded-xl px-3 py-2.5 text-gray-800 font-bold focus:outline-none focus:border-gray-900">
                        <option value="all">All Benefit Categories</option>
                        <option value="HMO Healthcare Coverage" {{ $budgetCategory === 'HMO Healthcare Coverage' ? 'selected' : '' }}>HMO Healthcare Coverage</option>
                        <option value="Driver Accident Pool Subsidies" {{ $budgetCategory === 'Driver Accident Pool Subsidies' ? 'selected' : '' }}>Driver Accident Pool Subsidies</option>
                        <option value="Corporate Wellness Programs" {{ $budgetCategory === 'Corporate Wellness Programs' ? 'selected' : '' }}>Corporate Wellness Programs</option>
                        <option value="Group Life Policies" {{ $budgetCategory === 'Group Life Policies' ? 'selected' : '' }}>Group Life Policies</option>
                    </select>

                    <button type="submit" class="bg-gray-900 hover:bg-black text-white text-xs font-black px-4 py-2.5 rounded-xl transition-all shadow-sm">
                        Filter Requisitions
                    </button>
                </form>
            </div>

            <!-- Requisitions Table Card -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-gray-50/80 border-b border-gray-100 text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                <th class="py-3.5 px-4">Requisition Code</th>
                                <th class="py-3.5 px-4">Benefit Category</th>
                                <th class="py-3.5 px-4 text-right">Requested Amount</th>
                                <th class="py-3.5 px-4">Purpose & Justification</th>
                                <th class="py-3.5 px-4 text-center">Lifecycle Status</th>
                                <th class="py-3.5 px-4">Date Transmitted</th>
                                <th class="py-3.5 px-4 text-right">Finance Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($requisitions as $req)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="py-3.5 px-4 font-mono font-bold text-gray-900">
                                        {{ $req->requisition_code }}
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="px-2 py-0.5 rounded bg-gray-100 text-gray-800 text-[10px] font-bold">
                                            {{ $req->category }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-mono font-black text-gray-900 font-outfit">
                                        PHP {{ number_format((float)$req->amount, 2) }}
                                    </td>
                                    <td class="py-3.5 px-4 max-w-[280px]">
                                        <p class="truncate text-gray-700 font-medium">{{ $req->justification }}</p>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase
                                            @if($req->status === 'approved') bg-emerald-50 text-emerald-700 border border-emerald-200
                                            @elseif($req->status === 'released') bg-blue-50 text-blue-700 border border-blue-200
                                            @elseif($req->status === 'rejected') bg-rose-50 text-rose-700 border border-rose-200
                                            @else bg-amber-50 text-amber-700 border border-amber-200 @endif">
                                            {{ str_replace('_', ' ', $req->status) }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-gray-500 font-mono text-[11px]">
                                        {{ $req->created_at->format('M j, Y') }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            @if($req->status === 'awaiting_approval')
                                                <form action="{{ route('hmo.update-budget-status', $req) }}" method="POST" class="inline">
                                                    @csrf
                                                    <input type="hidden" name="status" value="approved">
                                                    <button type="submit" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[10px] font-bold transition-all shadow-2xs">
                                                        Approve
                                                    </button>
                                                </form>
                                                <form action="{{ route('hmo.update-budget-status', $req) }}" method="POST" class="inline">
                                                    @csrf
                                                    <input type="hidden" name="status" value="rejected">
                                                    <button type="submit" class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-[10px] font-bold transition-all">
                                                        Reject
                                                    </button>
                                                </form>
                                            @elseif($req->status === 'approved')
                                                <form action="{{ route('hmo.update-budget-status', $req) }}" method="POST" class="inline">
                                                    @csrf
                                                    <input type="hidden" name="status" value="released">
                                                    <button type="submit" class="px-2.5 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-[10px] font-bold transition-all shadow-2xs">
                                                        Disburse Funds
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-gray-400 text-[10px] font-bold">Processed</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-12 text-gray-400 text-xs">
                                        No budget requisitions match the selected filters.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($requisitions->hasPages())
                    <div class="p-4 border-t border-gray-100">
                        {{ $requisitions->links() }}
                    </div>
                @endif
            </div>

        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: SUBMIT BUDGET REQUISITION TO FINANCE (TEAM 5) -->
        <!-- ========================================================================= -->
        <div x-show="showRequisitionModal" x-cloak class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div @click.away="showRequisitionModal = false" class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-base font-black font-outfit text-gray-900">New Benefits Budget Requisition</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Transmit formal funding request to Team 5 Financial Management</p>
                    </div>
                    <button @click="showRequisitionModal = false" type="button" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
                </div>

                <form action="{{ route('hmo.submit-request') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Benefit Allocation Category *</label>
                        <select name="category" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                            <option value="HMO Healthcare Coverage">HMO Healthcare Coverage (Maxicare / Intellicare Corporate Billing)</option>
                            <option value="Driver Accident Pool Subsidies">Driver Accident Pool Subsidies (50% TNVS Match)</option>
                            <option value="Corporate Wellness Programs">Corporate Wellness Programs & Annual Physical Exams (APE)</option>
                            <option value="Group Life Policies">Corporate Group Life & Accident Insurance</option>
                            <option value="Government Mandated Benefits">Statutory Benefits Adjustments (SSS / PhilHealth / Pag-IBIG)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Required Fund Amount (PHP) *</label>
                        <input type="number" name="amount" step="100" min="1" required placeholder="e.g. 150000" class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Justification & Expenditure Purpose *</label>
                        <textarea name="justification" rows="3" required placeholder="Detail the business justification and headcount covered..." class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-gray-800 focus:outline-none focus:border-gray-900"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                        <button @click="showRequisitionModal = false" type="button" class="text-xs font-bold text-gray-500 px-4 py-2">Cancel</button>
                        <button type="submit" class="bg-gray-900 hover:bg-black text-white font-black text-xs px-5 py-2 rounded-xl transition-all shadow-sm">Transmit to Finance</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

@endsection
