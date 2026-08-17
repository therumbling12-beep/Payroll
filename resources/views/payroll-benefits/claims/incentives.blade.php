@extends('layouts.app')

@php
    $pageTitle = 'Driver Ride Milestone Incentives';
    $currentPage = 'claims.incentives';
@endphp

@section('content')

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-black font-outfit text-gray-900 tracking-tight">Driver Ride-Based Incentives</h1>
            <p class="text-xs text-gray-500 mt-1">Automated 5-Tier ride milestone qualification and consistency bonus engine.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('claims.export', ['type' => 'incentive']) }}" 
               class="text-xs font-black text-gray-800 hover:text-black bg-white border border-gray-200 px-3.5 py-1.5 rounded-xl shadow-2xs hover:bg-gray-50 flex items-center gap-1.5 transition-all">
                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export CSV
            </a>
            <span class="flex items-center gap-2 text-xs font-bold text-gray-800 bg-white border border-gray-200 px-3.5 py-1.5 rounded-full shadow-2xs">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                5-Tier Milestone Engine Active
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
        activeTab: 'roster', {{-- 'roster', 'queue', 'matrix', 'policy' --}}
        selectedCutoff: '{{ $currentCutoff }}',
        showBatchModal: false,
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

        driverRoster: {{ json_encode($driverRoster) }},
        
        get qualifiedDrivers() {
            return this.driverRoster.filter(d => d.is_qualified);
        },

        get totalProjectedPayout() {
            return this.qualifiedDrivers.reduce((acc, d) => acc + d.total_incentive_amount, 0);
        },

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

        <!-- Top Navigation Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-200 pb-3">
            <div class="flex flex-wrap items-center gap-1.5 bg-gray-100 p-1 rounded-2xl">
                
                <!-- Tab 1: Driver Roster Qualification -->
                <button type="button" @click="activeTab = 'roster'" 
                        :class="activeTab === 'roster' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold'" 
                        class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4" :class="activeTab === 'roster' ? 'text-[#F44336]' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Live Driver Milestone Roster
                    <span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-gray-200 text-gray-800">{{ $driverRoster->count() }}</span>
                </button>

                <!-- Tab 2: Historical Claims Queue -->
                <button type="button" @click="activeTab = 'queue'" 
                        :class="activeTab === 'queue' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold'" 
                        class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4" :class="activeTab === 'queue' ? 'text-[#F44336]' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    Committed Incentive Queue
                    <span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-gray-200 text-gray-800">{{ $claims->total() }}</span>
                </button>

                <!-- Tab 3: Tier Matrix -->
                <button type="button" @click="activeTab = 'matrix'" 
                        :class="activeTab === 'matrix' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold'" 
                        class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4" :class="activeTab === 'matrix' ? 'text-[#F44336]' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    5-Tier Milestone Matrix
                </button>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-3">
                <button @click="showBatchModal = true" type="button" 
                        class="bg-gray-900 hover:bg-black text-white font-black text-xs px-4 py-2 rounded-xl transition-all shadow-sm flex items-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    Commit Qualified Incentives
                </button>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 1: LIVE DRIVER MILESTONE ROSTER -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'roster'" x-transition class="space-y-6">

            <!-- 4 Summary Stat Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-extrabold text-gray-400 uppercase tracking-wider">Active TNVS Drivers</span>
                        <div class="w-8 h-8 rounded-xl bg-gray-100 text-gray-700 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-2xl font-black font-outfit text-gray-900">{{ $driverRoster->count() }}</p>
                    <p class="text-xs text-gray-500 font-medium">Fleet Operations Roster</p>
                </div>

                <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-extrabold text-gray-400 uppercase tracking-wider">Qualified for Bonus</span>
                        <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-2xl font-black font-outfit text-emerald-600">{{ $stats['qualified_drivers_count'] }}</p>
                    <p class="text-xs text-emerald-700 font-bold">Achieved 20+ rides in cutoff</p>
                </div>

                <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-extrabold text-gray-400 uppercase tracking-wider">Total Projected Payout</span>
                        <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-2xl font-black font-outfit text-purple-700 font-mono">PHP {{ number_format($stats['total_projected_incentives'], 2) }}</p>
                    <p class="text-xs text-gray-500 font-medium">Standard Tiered Milestone Payout</p>
                </div>

                <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-extrabold text-gray-400 uppercase tracking-wider">Payroll Cutoff</span>
                        <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-lg font-black font-mono text-gray-900">{{ $currentCutoff }}</p>
                    <p class="text-xs text-gray-500 font-medium">Tax Classification: Taxable</p>
                </div>
            </div>

            <!-- Driver Milestone Roster Table -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-4">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200 text-xs font-extrabold text-gray-400 uppercase tracking-wider">
                                <th class="py-3.5 px-4">Driver Name & Code</th>
                                <th class="py-3.5 px-4">Completed Rides</th>
                                <th class="py-3.5 px-4">Next Tier Target</th>
                                <th class="py-3.5 px-4">Qualified Milestone</th>
                                <th class="py-3.5 px-4">Total Incentive Reward</th>
                                <th class="py-3.5 px-4">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-xs">
                            @foreach($driverRoster as $driver)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="py-3.5 px-4 font-bold text-gray-900">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-8 h-8 rounded-full bg-gray-900 text-white flex items-center justify-center font-bold font-outfit text-xs">
                                                {{ substr($driver['driver_name'], 0, 1) }}
                                            </div>
                                            <div>
                                                <div class="font-bold text-gray-900 font-outfit">{{ $driver['driver_name'] }}</div>
                                                <span class="text-[10px] text-gray-400 font-mono font-normal">{{ $driver['employee_code'] }} • {{ $driver['position'] }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="font-mono font-extrabold text-sm text-gray-900">{{ $driver['completed_rides'] }}</span>
                                        <span class="text-[10px] text-gray-500 block font-normal">Completed Trips</span>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        @if($driver['next_tier'] && $driver['next_tier']['target_remaining'] > 0)
                                            <div class="w-36 space-y-1">
                                                <div class="flex items-center justify-between text-[10px] text-gray-500 font-mono">
                                                    <span>{{ $driver['next_tier']['target_remaining'] }} to Tier {{ $driver['next_tier']['tier'] }}</span>
                                                    <span>{{ $driver['next_tier']['progress_pct'] }}%</span>
                                                </div>
                                                <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                                    <div class="h-full bg-purple-600 rounded-full" style="width: {{ $driver['next_tier']['progress_pct'] }}%"></div>
                                                </div>
                                            </div>
                                        @else
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-purple-50 text-purple-800 border border-purple-200">Max Milestone Reached</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4">
                                        @if($driver['is_qualified'])
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-800 border border-emerald-200">
                                                {{ $driver['tier_label'] }}
                                            </span>
                                        @else
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-gray-100 text-gray-500 border border-gray-200">
                                                Below Quota (<20 Rides)
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 font-mono font-bold text-sm">
                                        <span class="{{ $driver['total_incentive_amount'] > 0 ? 'text-purple-700' : 'text-gray-400' }}">
                                            PHP {{ number_format((float)$driver['total_incentive_amount'], 2) }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        @if($driver['is_already_committed'])
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-purple-50 text-purple-800 border border-purple-200">
                                                Committed
                                            </span>
                                        @elseif($driver['is_qualified'])
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-800 border border-emerald-200">
                                                Qualified
                                            </span>
                                        @else
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-gray-100 text-gray-500 border border-gray-200">
                                                Ineligible
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
        <!-- TAB 2: HISTORICAL COMMITTED QUEUE -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'queue'" x-transition class="space-y-6">

            <!-- Search Bar -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex flex-1 items-center gap-3 flex-wrap">
                    <form action="{{ route('claims.incentives') }}" method="GET" class="flex flex-1 items-center gap-3 max-w-md">
                        <input type="hidden" name="cutoff" value="{{ $currentCutoff }}">
                        <div class="relative flex-1">
                            <input type="text" name="search" value="{{ $search }}" placeholder="Search driver name, reference, or reason..." 
                                   class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl pl-9 pr-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-gray-900">
                            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <button type="submit" class="bg-gray-900 hover:bg-black text-white text-xs font-black px-4 py-2.5 rounded-xl transition-all shadow-sm cursor-pointer">
                            Filter
                        </button>
                    </form>

                    <!-- Filter: Overdue / Aging -->
                    <a href="{{ route('claims.incentives', ['aging' => 'overdue', 'cutoff' => $currentCutoff]) }}" 
                       class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2 {{ ($aging ?? '') === 'overdue' ? 'bg-white text-rose-900 font-black shadow-sm' : 'text-rose-700 hover:bg-rose-100/50 font-bold' }}">
                        <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Waiting > 3 Days
                        @if(!empty($stats['overdue_count']) && $stats['overdue_count'] > 0)
                            <span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-rose-100 text-rose-900 animate-pulse">
                                {{ $stats['overdue_count'] }}
                            </span>
                        @endif
                    </a>
                </div>

                <div class="text-xs text-gray-500 font-bold">
                    Showing {{ $claims->count() }} of {{ $claims->total() }} committed driver incentives
                </div>
            </div>

            <!-- Table Container -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-4">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200 text-xs font-extrabold text-gray-400 uppercase tracking-wider">
                                <th class="py-3.5 px-4">Ref Code</th>
                                <th class="py-3.5 px-4">Driver Partner</th>
                                <th class="py-3.5 px-4">Description</th>
                                <th class="py-3.5 px-4 text-right">Incentive Reward</th>
                                <th class="py-3.5 px-4">Workflow Status</th>
                                <th class="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-xs">
                            @forelse($claims as $claim)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="py-3.5 px-4 font-mono font-bold text-gray-900">
                                        {{ $claim->receipt_number }}
                                        <div class="text-[10px] text-gray-400 font-normal">{{ $claim->created_at->format('M d, Y') }}</div>
                                    </td>
                                    <td class="py-3.5 px-4 font-bold text-gray-900">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-8 h-8 rounded-full bg-gray-900 text-white flex items-center justify-center font-bold font-outfit text-xs">
                                                {{ substr($claim->employee?->first_name ?? 'D', 0, 1) }}
                                            </div>
                                            <div>
                                                <div class="font-bold text-gray-900 font-outfit">{{ $claim->employee?->first_name }} {{ $claim->employee?->last_name }}</div>
                                                <span class="text-[10px] text-gray-400 font-mono font-normal">{{ $claim->employee?->employee_code }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-semibold text-gray-700 max-w-xs truncate" title="{{ $claim->description }}">
                                            {{ $claim->description }}
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 font-mono font-bold text-purple-700 text-sm text-right">
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
                                        <button type="button" @click="openTimeline({{ json_encode($claim) }})" class="p-1.5 text-gray-500 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors cursor-pointer" title="View Approval Timeline">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-gray-400 text-xs">No committed driver incentives found in this view.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $claims->links() }}
                </div>
            </div>

        </div>

        <!-- ========================================================================= -->
        <!-- TAB 3: 5-TIER MILESTONE MATRIX -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'matrix'" x-transition class="space-y-6">
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-6">
                <div class="flex items-center gap-2 border-b border-gray-100 pb-3">
                    <span class="w-2.5 h-2.5 rounded-full bg-gray-900"></span>
                    <h3 class="text-base font-extrabold font-outfit text-gray-900">TNVS Ride Milestone Quota & Bonus Matrix</h3>
                </div>

                <p class="text-xs text-gray-600">Drivers achieving verified passenger ride thresholds within the semi-monthly cutoff period qualify for automated bonus compensation.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    @foreach($tiers as $tier)
                        <div class="bg-gray-50 border border-gray-200 rounded-2xl p-5 text-center space-y-2">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-purple-100 text-purple-800">{{ $tier['label'] }}</span>
                            <p class="text-2xl font-black font-outfit text-gray-900">{{ $tier['min_rides'] }}+ Rides</p>
                            <p class="text-xl font-black text-purple-700 font-mono">PHP {{ number_format($tier['amount'], 2) }}</p>
                            <p class="text-[11px] text-gray-500">Threshold Payout</p>
                        </div>
                    @endforeach
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-gray-100">
                    <div class="bg-purple-50/50 border border-purple-200 rounded-xl p-4 space-y-1">
                        <h4 class="text-xs font-black text-purple-900 uppercase">Monthly Consistency Bonus (+PHP 500.00)</h4>
                        <p class="text-xs text-purple-800 leading-relaxed">Awarded to drivers who achieve Tier 2 or above ($\ge 40$ completed rides) across consecutive payroll periods.</p>
                    </div>

                    <div class="bg-emerald-50/50 border border-emerald-200 rounded-xl p-4 space-y-1">
                        <h4 class="text-xs font-black text-emerald-900 uppercase">Perfect Attendance Bonus (+PHP 500.00)</h4>
                        <p class="text-xs text-emerald-800 leading-relaxed">Awarded to drivers achieving Tier 2 or above with zero tardiness and 100% active shift attendance.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Batch Commit Confirmation Modal -->
        <div x-show="showBatchModal" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 backdrop-blur-xs">
            <div @click.away="showBatchModal = false" class="bg-white rounded-2xl border border-gray-100 p-6 max-w-md w-full shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-base font-black font-outfit text-gray-900">Commit Milestone Incentives</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Queue all qualified driver bonuses directly into Active Claims</p>
                    </div>
                    <button @click="showBatchModal = false" class="text-gray-400 hover:text-gray-600 text-lg cursor-pointer">&times;</button>
                </div>

                <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 space-y-2 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Cutoff Period:</span>
                        <span class="font-mono font-bold text-gray-900" x-text="selectedCutoff"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Qualification Policy:</span>
                        <span class="font-bold text-purple-700">Standard Tiered Milestone</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Qualified Drivers:</span>
                        <span class="font-bold text-emerald-700" x-text="qualifiedDrivers.length + ' Drivers'"></span>
                    </div>
                    <div class="flex items-center justify-between pt-2 border-t border-gray-200 text-sm font-black">
                        <span class="text-gray-900">Total Incentive Amount:</span>
                        <span class="text-purple-700 font-mono" x-text="'PHP ' + totalProjectedPayout.toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                    </div>
                </div>

                <form action="{{ route('claims.incentives.batch-qualify') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="cutoff_period" :value="selectedCutoff">
                    <input type="hidden" name="plans_json" :value="JSON.stringify(qualifiedDrivers)">

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                        <button type="button" @click="showBatchModal = false" class="text-xs font-bold text-gray-500 px-4 py-2 hover:text-gray-700 cursor-pointer">Cancel</button>
                        <button type="submit" class="bg-gray-900 hover:bg-black text-white font-black text-xs px-5 py-2.5 rounded-xl transition-all shadow-sm cursor-pointer">Confirm & Commit Batch</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Slide-out Timeline Drawer -->
        <div x-show="showDrawer" x-cloak class="fixed inset-0 z-50 overflow-hidden" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-xs transition-opacity" @click="showDrawer = false"></div>

            <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
                <div class="w-screen max-w-md bg-white shadow-2xl p-6 flex flex-col justify-between overflow-y-auto">
                    <div>
                        <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                            <div>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-gray-900 text-white">
                                    Milestone Timeline
                                </span>
                                <h2 class="text-base font-black font-outfit text-gray-900 mt-1" x-text="selectedClaim ? selectedClaim.receipt_number : 'Incentive Details'"></h2>
                            </div>
                            <button @click="showDrawer = false" class="text-gray-400 hover:text-gray-600 text-2xl font-bold cursor-pointer">&times;</button>
                        </div>

                        <template x-if="selectedClaim">
                            <div class="mt-5 space-y-5">
                                <div class="bg-purple-50/40 border border-purple-200 rounded-xl p-4 text-xs space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-500 font-medium">Incentive Reward:</span>
                                        <span class="font-bold text-purple-700 font-mono text-sm" x-text="'PHP ' + Number(selectedClaim.amount).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-500 font-medium">Recipient:</span>
                                        <span class="font-bold text-gray-800" x-text="selectedClaim.employee ? (selectedClaim.employee.first_name + ' ' + selectedClaim.employee.last_name) : 'Driver'"></span>
                                    </div>
                                    <div class="pt-2 border-t border-purple-200 text-gray-700">
                                        <p class="font-medium text-gray-500 mb-0.5">Verification Notes:</p>
                                        <p class="font-normal" x-text="selectedClaim.description"></p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="pt-4 border-t border-gray-100">
                        <button type="button" @click="showDrawer = false" class="w-full bg-gray-900 hover:bg-black text-white font-bold text-xs py-2.5 rounded-xl transition-all cursor-pointer">
                            Close Timeline
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>

@endsection
