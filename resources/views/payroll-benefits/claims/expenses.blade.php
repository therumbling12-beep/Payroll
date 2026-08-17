@extends('layouts.app')

@php
    $pageTitle = 'Expense Reimbursements & Fuel Validation';
    $currentPage = 'claims.expenses';
@endphp

@section('content')

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-black font-outfit text-gray-900 tracking-tight">Driver Work Expense Claims</h1>
            <p class="text-xs text-gray-500 mt-1">Non-taxable business reimbursements with automated fuel consumption validation (15% tolerance rule) and multi-team governance.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('claims.export', ['type' => 'expense']) }}" 
               class="text-xs font-black text-gray-800 hover:text-black bg-white border border-gray-200 px-3.5 py-1.5 rounded-xl shadow-2xs hover:bg-gray-50 flex items-center gap-1.5 transition-all">
                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export CSV
            </a>
            <span class="flex items-center gap-2 text-xs font-bold text-gray-800 bg-white border border-gray-200 px-3.5 py-1.5 rounded-full shadow-2xs">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                {{ $fuelTolerancePct }}% Fuel Tolerance Active
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

    <div x-data="{ 
        activeSubtype: '{{ $subtype ?? '' }}',
        showDrawer: false,
        showActionModal: false,
        selectedClaim: null,
        actionType: '',
        actionUrl: '',
        actionClaimRef: '',
        actionAmount: '',
        actionRemarks: '',
        selected: [],
        selectAll: false,

        toggleSelectAll(ids) {
            this.selectAll = !this.selectAll;
            this.selected = this.selectAll ? ids : [];
        },

        openTimeline(claim) {
            this.selectedClaim = claim;
            this.showDrawer = true;
        },

        openAction(type, url, ref, currentAmount, defaultRemarks = '') {
            this.actionType = type;
            this.actionUrl = url;
            this.actionClaimRef = ref;
            this.actionAmount = currentAmount;
            this.actionRemarks = defaultRemarks;
            this.showActionModal = true;
        }
    }" class="space-y-6 pb-12">

        <!-- Top Navigation Filter Bar & Action Buttons -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-200 pb-3">
            <div class="flex flex-wrap items-center gap-1.5 bg-gray-100 p-1 rounded-2xl">
                
                <!-- Filter: All Expenses -->
                <a href="{{ route('claims.expenses') }}" 
                   class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2 {{ empty($subtype) && empty($status) && empty($aging) ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold' }}">
                    <svg class="w-4 h-4 {{ empty($subtype) && empty($status) && empty($aging) ? 'text-[#F44336]' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    All Expenses
                    <span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-gray-200 text-gray-800">{{ $filterCounts['all'] ?? 0 }}</span>
                </a>

                <!-- Filter: Needs My Action -->
                <a href="{{ route('claims.expenses', ['status' => 'needs_action']) }}" 
                   class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2 {{ ($status ?? '') === 'needs_action' ? 'bg-white text-amber-900 font-black shadow-sm' : 'text-amber-800 hover:bg-amber-100/50 font-bold' }}">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    Needs My Action
                    <span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-amber-100 text-amber-900">
                        {{ $filterCounts['needs_action'] ?? 0 }}
                    </span>
                </a>

                <!-- Filter: Overdue (> 3 Days) -->
                <a href="{{ route('claims.expenses', ['aging' => 'overdue']) }}" 
                   class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2 {{ ($aging ?? '') === 'overdue' ? 'bg-white text-rose-900 font-black shadow-sm' : 'text-rose-700 hover:bg-rose-100/50 font-bold' }}">
                    <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Waiting > 3 Days
                    @if(!empty($filterCounts['overdue']) && $filterCounts['overdue'] > 0)
                        <span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-rose-100 text-rose-900 animate-pulse">
                            {{ $filterCounts['overdue'] }}
                        </span>
                    @endif
                </a>

                <!-- Filter: Ready for Next Payroll -->
                <a href="{{ route('claims.expenses', ['status' => 'ready_payroll']) }}" 
                   class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2 {{ ($status ?? '') === 'ready_payroll' ? 'bg-white text-emerald-900 font-black shadow-sm' : 'text-emerald-800 hover:bg-emerald-100/50 font-bold' }}">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Ready for Next Payroll
                    <span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-emerald-100 text-emerald-900">
                        {{ $filterCounts['ready_payroll'] ?? 0 }}
                    </span>
                </a>

                <!-- Filter: Fuel Subtype -->
                <a href="{{ route('claims.expenses', ['subtype' => 'fuel']) }}" 
                   class="px-3.5 py-2 text-xs rounded-xl transition-all flex items-center gap-1.5 {{ $subtype === 'fuel' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold' }}">
                    Fuel
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-orange-100 text-orange-800">{{ $stats['fuel_claims_count'] }}</span>
                </a>

                <!-- Filter: Toll (RFID) Subtype -->
                <a href="{{ route('claims.expenses', ['subtype' => 'toll']) }}" 
                   class="px-3.5 py-2 text-xs rounded-xl transition-all {{ $subtype === 'toll' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold' }}">
                    Toll
                </a>

                <!-- Filter: Maintenance Subtype -->
                <a href="{{ route('claims.expenses', ['subtype' => 'maintenance']) }}" 
                   class="px-3.5 py-2 text-xs rounded-xl transition-all {{ $subtype === 'maintenance' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold' }}">
                    Repairs
                </a>
            </div>

            <!-- Review Queue Badge -->
            <div class="flex items-center gap-2">
                <span class="px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-bold rounded-xl border border-gray-200 shadow-2xs flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Employee Self-Service Review Queue
                </span>
            </div>
        </div>

        <!-- Executive KPI Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Card 1: Total Disbursed -->
            <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm space-y-1">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Total Disbursed</span>
                    <div class="w-8 h-8 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="text-2xl font-black font-outfit text-gray-900">
                    PHP {{ number_format($stats['total_disbursed'], 2) }}
                </div>
                <p class="text-xs text-gray-500 font-medium">{{ $stats['approved_count'] }} Approved Claims</p>
            </div>

            <!-- Card 2: Fuel Auto-Validation Rate -->
            <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm space-y-1">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Fuel Auto-Validation</span>
                    <div class="w-8 h-8 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                </div>
                <div class="text-2xl font-black font-outfit text-emerald-700">
                    {{ $stats['fuel_auto_validation_rate'] }}%
                </div>
                <p class="text-xs text-emerald-700 font-bold">{{ $stats['fuel_auto_validated_count'] }} Passed 15% Tolerance</p>
            </div>

            <!-- Card 3: Variance Review Queue -->
            <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm space-y-1">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Variance Review Queue</span>
                    <div class="w-8 h-8 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                </div>
                <div class="text-2xl font-black font-outfit text-amber-900">
                    {{ $stats['flagged_variance_count'] }} Flagged
                </div>
                <p class="text-xs text-gray-500 font-medium">Exceeds 15% distance variance</p>
            </div>

            <!-- Card 4: Pending Multi-Team -->
            <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm space-y-1">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Pending Multi-Team</span>
                    <div class="w-8 h-8 rounded-xl bg-purple-50 flex items-center justify-center text-purple-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="text-2xl font-black font-outfit text-purple-900">
                    {{ $stats['pending_count'] }} Pending
                </div>
                <p class="text-xs text-gray-500 font-medium">Awaiting Supervisor / HR / Admin</p>
            </div>
        </div>

        <!-- Floating Batch Actions Toolbar -->
        <div x-show="selected.length > 0" x-cloak class="fixed bottom-6 inset-x-4 sm:inset-x-auto sm:left-1/2 sm:-translate-x-1/2 z-40 bg-gray-900/95 backdrop-blur-md text-white rounded-3xl p-3.5 sm:px-6 shadow-2xl flex flex-wrap items-center justify-between gap-3 sm:gap-6 border border-gray-700 transition-all">
            <div class="flex items-center gap-2.5">
                <span class="w-7 h-7 rounded-xl bg-emerald-500 text-gray-950 flex items-center justify-center font-mono font-black text-xs" x-text="selected.length"></span>
                <span class="text-xs font-bold text-white">Selected Claims</span>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <form action="{{ route('claims.batch-workflow') }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="action" value="batch_approve_hr">
                    <template x-for="id in selected" :key="id">
                        <input type="hidden" name="selected_ids[]" :value="id">
                    </template>
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs px-3.5 py-2 rounded-xl transition-all shadow-sm cursor-pointer flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        1-Click Batch Validate
                    </button>
                </form>

                <form action="{{ route('claims.batch-workflow') }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="action" value="batch_approve_finance">
                    <template x-for="id in selected" :key="id">
                        <input type="hidden" name="selected_ids[]" :value="id">
                    </template>
                    <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs px-3 py-2 rounded-xl transition-all shadow-sm cursor-pointer">
                        Batch Finance
                    </button>
                </form>

                <form action="{{ route('claims.batch-workflow') }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="action" value="batch_queue_payroll">
                    <template x-for="id in selected" :key="id">
                        <input type="hidden" name="selected_ids[]" :value="id">
                    </template>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs px-3 py-2 rounded-xl transition-all shadow-sm cursor-pointer">
                        Batch to Payroll
                    </button>
                </form>

                <button type="button" @click="selected = []; selectAll = false;" class="text-gray-400 hover:text-white text-xs font-bold px-2 py-1 cursor-pointer">
                    Clear
                </button>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <form action="{{ route('claims.expenses') }}" method="GET" class="flex flex-1 items-center gap-3 max-w-md">
                @if($subtype)
                    <input type="hidden" name="subtype" value="{{ $subtype }}">
                @endif
                <div class="relative flex-1">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search claimant, OR receipt, station, or description..." 
                           class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl pl-9 pr-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-gray-900">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <button type="submit" class="bg-gray-900 hover:bg-black text-white text-xs font-black px-4 py-2.5 rounded-xl transition-all shadow-sm cursor-pointer">
                    Filter
                </button>
            </form>

            <div class="text-xs text-gray-500 font-bold">
                Showing {{ $claims->count() }} of {{ $claims->total() }} expense claims
            </div>
        </div>

        <!-- Claims Table Container -->
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-4">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-200 text-xs font-extrabold text-gray-400 uppercase tracking-wider">
                            <th class="py-3.5 px-3 w-10 text-center">
                                <input type="checkbox" @click="toggleSelectAll({{ json_encode($claims->pluck('id')) }})" :checked="selectAll" class="rounded text-gray-900 focus:ring-gray-900">
                            </th>
                            <th class="py-3.5 px-4">Receipt Ref & Date</th>
                            <th class="py-3.5 px-4">Employee Claimant</th>
                            <th class="py-3.5 px-4">Expense Type & Details</th>
                            <th class="py-3.5 px-4">Fuel Validation Status</th>
                            <th class="py-3.5 px-4 text-right">Claim Amount</th>
                            <th class="py-3.5 px-4">Workflow Status</th>
                            <th class="py-3.5 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 text-xs">
                        @forelse($claims as $claim)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="py-3.5 px-3 text-center">
                                    <input type="checkbox" :value="{{ $claim->id }}" x-model="selected" class="rounded text-gray-900 focus:ring-gray-900">
                                </td>
                                <td class="py-3.5 px-4 font-mono font-bold text-gray-900">
                                    {{ $claim->receipt_number }}
                                    <div class="text-[10px] text-gray-400 font-normal">{{ $claim->expense_date?->format('M d, Y') ?? $claim->created_at->format('M d, Y') }}</div>
                                </td>
                                <td class="py-3.5 px-4 font-bold text-gray-900">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-full bg-gray-900 text-white flex items-center justify-center font-bold font-outfit text-xs">
                                            {{ substr($claim->employee?->first_name ?? 'E', 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-gray-900 font-outfit">{{ $claim->employee?->first_name }} {{ $claim->employee?->last_name }}</div>
                                            <span class="text-[10px] text-gray-400 font-mono font-normal">{{ $claim->employee?->employee_code }} • {{ $claim->employee?->position }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-semibold text-gray-800">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-gray-100 text-gray-700">
                                            {{ $claim->expense_subtype ? strtoupper($claim->expense_subtype) : ($claim->categoryModel?->name ?? 'REIMBURSEMENT') }}
                                        </span>
                                        @if($claim->merchant_name)
                                            <span class="text-xs text-gray-600 font-medium ml-1">{{ $claim->merchant_name }}</span>
                                        @endif
                                    </div>
                                    <p class="text-[10px] text-gray-400 mt-0.5 truncate max-w-xs" title="{{ $claim->description }}">{{ $claim->description }}</p>
                                </td>
                                <td class="py-3.5 px-4">
                                    @if($claim->expense_subtype === 'fuel')
                                        @if($claim->auto_validated)
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-800 border border-emerald-200">
                                                Auto-Verified ({{ $claim->fuel_variance_pct >= 0 ? '+' : '' }}{{ $claim->fuel_variance_pct }}%)
                                            </span>
                                        @else
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-50 text-amber-800 border border-amber-200">
                                                Flagged Variance ({{ $claim->fuel_variance_pct >= 0 ? '+' : '' }}{{ $claim->fuel_variance_pct }}%)
                                            </span>
                                        @endif
                                        <p class="text-[10px] font-mono text-gray-400 mt-0.5">{{ $claim->distance_traveled_km }} km @ {{ $claim->vehicle_fuel_efficiency_kpl }} km/L</p>
                                    @else
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-gray-50 text-gray-600 border border-gray-200">
                                            Receipt Verified
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 font-mono font-bold text-gray-900 text-sm text-right">
                                    PHP {{ number_format((float)$claim->amount, 2) }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black border uppercase tracking-wider inline-block {{ $claim->status_badge_class }}">
                                        {{ $claim->status_label }}
                                    </span>
                                    @if($claim->isOverdue())
                                        <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase bg-rose-100 text-rose-800 border border-rose-200 block mt-1 animate-pulse">
                                            Waiting {{ $claim->waitingDays() }} Days (Overdue)
                                        </span>
                                    @endif
                                    <div class="flex items-center gap-1 mt-1.5" title="Step 1: Submitted • Step 2: HR Check • Step 3: Finance Budget • Step 4: In Payslip">
                                        @php
                                            $cStatus = $claim->approval_status ?? $claim->status;
                                             $isPaid = in_array($cStatus, ['paid', 'payroll_queued'], true);
                                            $isFinance = (bool) $claim->finance_approved_at || $isPaid;
                                            $isHr = (bool) $claim->hr_approved_at || $isFinance;
                                        @endphp
                                        <span class="px-1.5 py-0.5 text-[8px] font-black rounded-sm bg-emerald-100 text-emerald-800">1. Sub</span>
                                        <span class="px-1.5 py-0.5 text-[8px] font-black rounded-sm {{ $isHr ? 'bg-emerald-100 text-emerald-800' : ($cStatus === 'pending_hr' || $cStatus === 'pending' ? 'bg-amber-100 text-amber-800 animate-pulse' : 'bg-gray-100 text-gray-400') }}">2. HR</span>
                                        <span class="px-1.5 py-0.5 text-[8px] font-black rounded-sm {{ $isFinance ? 'bg-emerald-100 text-emerald-800' : ($cStatus === 'pending_finance' || $cStatus === 'pending_admin' ? 'bg-purple-100 text-purple-800 animate-pulse' : 'bg-gray-100 text-gray-400') }}">3. Fin</span>
                                        <span class="px-1.5 py-0.5 text-[8px] font-black rounded-sm {{ $isPaid ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-400' }}">4. Pay</span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button" @click="openTimeline({{ json_encode($claim) }})" class="p-1.5 text-gray-500 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors cursor-pointer" title="View Details">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </button>

                                        @if($claim->approval_status === 'pending_hr' || $claim->approval_status === 'pending')
                                            <button type="button" @click="openAction('HR Validation', '{{ route('claims.workflow-action', $claim->id) }}', '{{ $claim->receipt_number }}', '{{ $claim->amount }}', 'Validated official receipt and distance log.')" class="bg-amber-600 hover:bg-amber-700 text-white font-black text-[11px] px-3 py-1.5 rounded-xl transition-all shadow-xs cursor-pointer">
                                                HR Validate
                                            </button>
                                        @elseif($claim->approval_status === 'pending_admin')
                                            <button type="button" @click="openAction('Admin Authorization', '{{ route('claims.workflow-action', $claim->id) }}', '{{ $claim->receipt_number }}', '{{ $claim->amount }}', 'Expense disbursement authorized.')" class="bg-indigo-600 hover:bg-indigo-700 text-white font-black text-[11px] px-3 py-1.5 rounded-xl transition-all shadow-xs cursor-pointer">
                                                Admin Approve
                                            </button>
                                        @elseif($claim->approval_status === 'pending_finance')
                                            <button type="button" @click="openAction('Finance Budget Approval', '{{ route('claims.workflow-action', $claim->id) }}', '{{ $claim->receipt_number }}', '{{ $claim->amount }}', 'Budget allocated under Team 5.')" class="bg-purple-600 hover:bg-purple-700 text-white font-black text-[11px] px-3 py-1.5 rounded-xl transition-all shadow-xs cursor-pointer">
                                                Finance Approve
                                            </button>
                                        @elseif($claim->approval_status === 'approved')
                                            <form action="{{ route('claims.workflow-action', $claim->id) }}" method="POST" class="inline">
                                                @csrf
                                                <input type="hidden" name="action" value="queue_payroll">
                                                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-black text-[11px] px-3 py-1.5 rounded-xl transition-all shadow-xs cursor-pointer">
                                                    Queue to Payroll
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8 text-center text-gray-400 text-xs">No expense claims found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $claims->links() }}
            </div>
        </div>

        <!-- Side-by-Side Receipt & Workflow Inspection Drawer -->
        <div x-show="showDrawer" x-cloak class="fixed inset-0 z-50 overflow-hidden" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-xs transition-opacity" @click="showDrawer = false"></div>

            <div class="fixed inset-y-0 right-0 max-w-full flex pl-4 sm:pl-10">
                <div class="w-screen max-w-4xl bg-white shadow-2xl flex flex-col justify-between overflow-y-auto">
                    <!-- Drawer Header -->
                    <div class="p-6 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-gray-900 text-white">
                                    Claim Inspection
                                </span>
                                <span class="text-xs font-mono font-bold text-gray-500" x-text="selectedClaim ? selectedClaim.receipt_number : ''"></span>
                            </div>
                            <h3 class="text-base font-extrabold font-outfit text-gray-900 mt-1" x-text="selectedClaim ? (selectedClaim.merchant_name || selectedClaim.category || 'Expense Details') : 'Claim Details'"></h3>
                        </div>
                        <button @click="showDrawer = false" class="text-gray-400 hover:text-gray-700 text-2xl font-bold p-2 cursor-pointer">&times;</button>
                    </div>

                    <!-- Drawer Body: 2-Column Side-by-Side Layout -->
                    <div class="p-6 flex-1 overflow-y-auto" x-show="selectedClaim">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                            
                            <!-- Left Column: Receipt Document View (5 Cols) -->
                            <div class="md:col-span-5 space-y-3">
                                <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider">Proof / Official Receipt</h4>
                                
                                <template x-if="selectedClaim && selectedClaim.attachment_path">
                                    <div class="space-y-2">
                                        <div class="rounded-2xl border border-gray-200 overflow-hidden bg-gray-900/5 p-2 text-center">
                                            <template x-if="selectedClaim.attachment_path.endsWith('.pdf')">
                                                <div class="py-12 px-4 space-y-3">
                                                    <div class="w-12 h-12 mx-auto rounded-xl bg-gray-900 text-white flex items-center justify-center font-mono font-bold text-xs">
                                                        PDF
                                                    </div>
                                                    <p class="text-xs font-bold text-gray-800">PDF Document Attached</p>
                                                    <a :href="'/storage/' + selectedClaim.attachment_path" target="_blank" class="inline-block bg-gray-900 hover:bg-black text-white text-[11px] font-bold px-3 py-1.5 rounded-lg transition-all">
                                                        Open PDF in New Tab
                                                    </a>
                                                </div>
                                            </template>
                                            <template x-if="!selectedClaim.attachment_path.endsWith('.pdf')">
                                                <div class="space-y-2">
                                                    <img :src="'/storage/' + selectedClaim.attachment_path" alt="Official Receipt" class="w-full max-h-80 object-contain rounded-xl bg-white shadow-xs border border-gray-100">
                                                    <a :href="'/storage/' + selectedClaim.attachment_path" target="_blank" class="text-[11px] font-bold text-gray-600 hover:text-gray-900 flex items-center justify-center gap-1">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                                        </svg>
                                                        View High-Res Photo
                                                    </a>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="!selectedClaim || !selectedClaim.attachment_path">
                                    <div class="p-8 rounded-2xl border-2 border-dashed border-gray-200 text-center space-y-2 bg-gray-50/50">
                                        <div class="w-10 h-10 mx-auto rounded-full bg-gray-200 text-gray-500 flex items-center justify-center">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                        </div>
                                        <p class="text-xs font-bold text-gray-700">No Physical File Uploaded</p>
                                        <p class="text-[10px] text-gray-400">Claim filed directly via system workflow</p>
                                    </div>
                                </template>
                            </div>

                            <!-- Right Column: Details, Tax Status, Gas Checker & 1-Click Actions (7 Cols) -->
                            <div class="md:col-span-7 space-y-4">
                                <!-- Claimant Info -->
                                <div class="bg-gray-50 rounded-2xl p-4 border border-gray-200 space-y-2">
                                    <h4 class="text-xs font-bold text-gray-500 uppercase">Employee Details</h4>
                                    <div class="flex items-center gap-3" x-show="selectedClaim && selectedClaim.employee">
                                        <div class="w-10 h-10 rounded-full bg-gray-900 text-white font-black flex items-center justify-center text-sm" x-text="selectedClaim && selectedClaim.employee ? selectedClaim.employee.first_name.charAt(0) : 'E'"></div>
                                        <div>
                                            <p class="text-sm font-extrabold text-gray-900" x-text="selectedClaim && selectedClaim.employee ? (selectedClaim.employee.first_name + ' ' + selectedClaim.employee.last_name) : 'Employee'"></p>
                                            <p class="text-xs text-gray-500" x-text="selectedClaim && selectedClaim.employee ? (selectedClaim.employee.employee_code + ' • ' + selectedClaim.employee.position) : ''"></p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Expense & Tax Breakdown -->
                                <div class="bg-white rounded-2xl p-4 border border-gray-200 shadow-xs space-y-2.5 text-xs">
                                    <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                                        <span class="text-gray-500">Claimed Amount:</span>
                                        <span class="font-black text-gray-900 font-mono text-base" x-text="'PHP ' + Number(selectedClaim ? selectedClaim.amount : 0).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                                    </div>
                                    <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                                        <span class="text-gray-500">Tax Classification:</span>
                                        <span class="font-bold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full text-[10px]">100% Tax-Exempt Reimbursement</span>
                                    </div>
                                    <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                                        <span class="text-gray-500">Merchant / Station:</span>
                                        <span class="font-bold text-gray-800" x-text="selectedClaim ? (selectedClaim.merchant_name || 'Official Receipt') : '—'"></span>
                                    </div>
                                    <div class="pt-1 text-gray-700">
                                        <span class="text-gray-500 block mb-0.5">Business Purpose:</span>
                                        <p class="italic text-gray-800 bg-gray-50 p-2.5 rounded-xl border border-gray-100" x-text="selectedClaim ? selectedClaim.description : '—'"></p>
                                    </div>
                                </div>

                                <!-- Gas Cost Checker Details if Fuel -->
                                <template x-if="selectedClaim && selectedClaim.expense_subtype === 'fuel'">
                                    <div class="bg-emerald-50/60 border border-emerald-200 rounded-2xl p-4 text-xs space-y-2">
                                        <div class="flex items-center justify-between border-b border-emerald-200 pb-1.5">
                                            <span class="font-black text-emerald-900 uppercase text-[10px]">Gas Cost Checker Breakdown</span>
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-black" :class="selectedClaim.auto_validated ? 'bg-emerald-200 text-emerald-900' : 'bg-amber-200 text-amber-900'" x-text="selectedClaim.auto_validated ? 'Within Tolerance' : 'Needs Review'"></span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2 text-[11px]">
                                            <div>
                                                <span class="text-gray-500">Distance:</span>
                                                <span class="font-mono font-bold text-gray-900 ml-1" x-text="selectedClaim.distance_traveled_km + ' km'"></span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500">Efficiency:</span>
                                                <span class="font-mono font-bold text-gray-900 ml-1" x-text="selectedClaim.vehicle_fuel_efficiency_kpl + ' km/L'"></span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500">Expected Cost:</span>
                                                <span class="font-mono font-bold text-gray-900 ml-1" x-text="'PHP ' + Number(selectedClaim.expected_fuel_cost || 0).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500">Variance:</span>
                                                <span class="font-mono font-bold ml-1" :class="selectedClaim.auto_validated ? 'text-emerald-700' : 'text-amber-700'" x-text="(selectedClaim.fuel_variance_pct >= 0 ? '+' : '') + selectedClaim.fuel_variance_pct + '%'"></span>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <!-- 1-Click Inline Action Form -->
                                <div class="pt-2 border-t border-gray-200 space-y-2">
                                    <h4 class="text-xs font-bold text-gray-700 uppercase">1-Click Reviewer Action</h4>
                                    
                                    <template x-if="selectedClaim && (selectedClaim.approval_status === 'pending_hr' || selectedClaim.approval_status === 'pending')">
                                        <div class="space-y-2">
                                            <form :action="'{{ route('claims.workflow-action', ['claim' => '__ID__']) }}'.replace('__ID__', selectedClaim.id)" method="POST">
                                                @csrf
                                                <input type="hidden" name="action" value="approve_hr">
                                                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs py-3 rounded-xl shadow-sm transition-all flex items-center justify-center gap-2 cursor-pointer">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                    1-Click Validate & Forward to Finance
                                                </button>
                                            </form>
                                            
                                            <div class="flex items-center gap-2">
                                                <button type="button" @click="openAction('Return for Revision', '{{ route('claims.workflow-action', ['claim' => '__ID__']) }}'.replace('__ID__', selectedClaim.id), selectedClaim.receipt_number, selectedClaim.amount, 'Please re-upload a clearer receipt photo.')" class="flex-1 bg-gray-100 hover:bg-amber-50 hover:text-amber-700 text-gray-700 font-bold text-xs py-2 rounded-xl border border-gray-200 transition-all cursor-pointer">
                                                    Return for Revision
                                                </button>
                                                <button type="button" @click="openAction('Reject Claim', '{{ route('claims.workflow-action', ['claim' => '__ID__']) }}'.replace('__ID__', selectedClaim.id), selectedClaim.receipt_number, selectedClaim.amount, 'Non-compliant or unauthorized expense.')" class="flex-1 bg-gray-100 hover:bg-rose-50 hover:text-rose-700 text-gray-700 font-bold text-xs py-2 rounded-xl border border-gray-200 transition-all cursor-pointer">
                                                    Reject
                                                </button>
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="selectedClaim && selectedClaim.approval_status === 'pending_finance'">
                                        <form :action="'{{ route('claims.workflow-action', ['claim' => '__ID__']) }}'.replace('__ID__', selectedClaim.id)" method="POST">
                                            @csrf
                                            <input type="hidden" name="action" value="approve_finance">
                                            <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-black text-xs py-3 rounded-xl shadow-sm transition-all flex items-center justify-center gap-2 cursor-pointer">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                                </svg>
                                                Approve Budget & Forward to Admin
                                            </button>
                                        </form>
                                    </template>

                                    <template x-if="selectedClaim && selectedClaim.approval_status === 'pending_admin'">
                                        <form :action="'{{ route('claims.workflow-action', ['claim' => '__ID__']) }}'.replace('__ID__', selectedClaim.id)" method="POST">
                                            @csrf
                                            <input type="hidden" name="action" value="approve_admin">
                                            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-black text-xs py-3 rounded-xl shadow-sm transition-all flex items-center justify-center gap-2 cursor-pointer">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                                </svg>
                                                Authorize Disbursement
                                            </button>
                                        </form>
                                    </template>

                                    <template x-if="selectedClaim && selectedClaim.approval_status === 'approved'">
                                        <form :action="'{{ route('claims.workflow-action', ['claim' => '__ID__']) }}'.replace('__ID__', selectedClaim.id)" method="POST">
                                            @csrf
                                            <input type="hidden" name="action" value="queue_payroll">
                                            <button type="submit" class="w-full bg-emerald-700 hover:bg-emerald-800 text-white font-black text-xs py-3 rounded-xl shadow-sm transition-all flex items-center justify-center gap-2 cursor-pointer">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                Queue to Next Cutoff Payslip
                                            </button>
                                        </form>
                                    </template>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Drawer Footer -->
                    <div class="p-4 border-t border-gray-100 bg-gray-50 flex items-center justify-between">
                        <span class="text-xs text-gray-500">Side-by-side inspection</span>
                        <button type="button" @click="showDrawer = false" class="bg-gray-900 hover:bg-black text-white font-bold text-xs px-5 py-2 rounded-xl transition-all cursor-pointer">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Remarks Modal -->
        <div x-show="showActionModal" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 backdrop-blur-xs">
            <div @click.away="showActionModal = false" class="bg-white rounded-2xl border border-gray-100 p-6 max-w-md w-full shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-base font-black font-outfit text-gray-900" x-text="actionType"></h2>
                        <p class="text-xs text-gray-400 mt-0.5" x-text="'Claim Reference: ' + actionClaimRef"></p>
                    </div>
                    <button @click="showActionModal = false" class="text-gray-400 hover:text-gray-600 text-lg cursor-pointer">&times;</button>
                </div>

                <form :action="actionUrl" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="action" :value="actionType === 'Reject Claim' || actionType === 'Return for Revision' ? 'reject' : 'approve_hr'">
                    <input type="hidden" name="rejection_reason" :value="actionRemarks">

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Approved Amount (PHP)</label>
                        <input type="number" step="0.01" name="approved_amount" x-model="actionAmount" required class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 font-mono font-bold focus:outline-none focus:border-gray-900">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Approval Remarks</label>
                        <textarea name="remarks" x-model="actionRemarks" rows="3" required class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl p-3 text-gray-800 focus:outline-none focus:border-gray-900"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                        <button type="button" @click="showActionModal = false" class="text-xs font-bold text-gray-500 px-4 py-2 hover:text-gray-700 cursor-pointer">Cancel</button>
                        <button type="submit" class="bg-gray-900 hover:bg-black text-white font-black text-xs px-5 py-2.5 rounded-xl transition-all shadow-sm cursor-pointer" x-text="'Confirm ' + actionType"></button>
                    </div>
                </form>
            </div>
        </div>

    </div>

@endsection
