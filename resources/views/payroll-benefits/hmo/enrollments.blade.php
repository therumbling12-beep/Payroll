@extends('layouts.app')

@php
    $pageTitle = 'Employee HMO Enrollments & Workforce Roster';
    $currentPage = 'hmo.enrollments';
@endphp

@section('content')

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-black font-outfit text-gray-900 tracking-tight">Employee HMO Enrollments & Roster</h1>
            <p class="text-xs text-gray-500 mt-1">Manage active workforce healthcare policies, remaining MBL balances, eligibility verification, and payroll deduction synchronization.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="flex items-center gap-2 text-xs font-bold text-gray-800 bg-white border border-gray-200 px-3.5 py-1.5 rounded-full shadow-2xs">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                Enrollment Master Active
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

    <!-- Main Container with Alpine.js State -->
    <div x-data="{ 
        activeTab: '{{ $tab ?? 'roster' }}', {{-- 'roster', 'approvals' --}}
        showEnrollModal: false,
        showEditModal: false,
        showUtilizeModal: false,
        showDeactivateModal: false,
        showActivateModal: false,
        showRejectModal: false,
        selectedEnrollment: null,

        openEdit(enrollment) {
            this.selectedEnrollment = enrollment;
            this.showEditModal = true;
        },
        openUtilize(enrollment) {
            this.selectedEnrollment = enrollment;
            this.showUtilizeModal = true;
        },
        openDeactivate(enrollment) {
            this.selectedEnrollment = enrollment;
            this.showDeactivateModal = true;
        },
        openActivate(enrollment) {
            this.selectedEnrollment = enrollment;
            this.showActivateModal = true;
        },
        openReject(enrollment) {
            this.selectedEnrollment = enrollment;
            this.showRejectModal = true;
        }
    }" class="space-y-6 pb-12">

        <!-- Top Navigation Bar & Action Modals -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-200 pb-3">
            <div class="flex items-center gap-1.5 bg-gray-100 p-1 rounded-2xl">
                
                <!-- Tab 1: Active Workforce Roster -->
                <button type="button" @click="activeTab = 'roster'" 
                        :class="activeTab === 'roster' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold'" 
                        class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#F44336]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Workforce Healthcare Roster
                    <span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-gray-200 text-gray-800">{{ $stats['total_active'] }}</span>
                </button>

                <!-- Tab 2: Eligibility & Approvals Queue -->
                <button type="button" @click="activeTab = 'approvals'" 
                        :class="activeTab === 'approvals' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold'" 
                        class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#F44336]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Eligibility & Applications Queue
                    @if($stats['pending_applications'] > 0)
                        <span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-amber-500 text-white animate-pulse">{{ $stats['pending_applications'] }}</span>
                    @endif
                </button>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- 1-Click Sync to Payroll Deductions -->
                <form action="{{ route('hmo.enrollments.sync-payroll') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" 
                            class="bg-white hover:bg-gray-100 text-gray-800 font-bold text-xs px-3.5 py-2 rounded-xl transition-all border border-gray-200 flex items-center gap-1.5 shadow-2xs">
                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Sync to Payroll Deductions
                    </button>
                </form>

                <!-- Export CSV Master Roster -->
                <a href="{{ route('hmo.plans.export-roster') }}" 
                   class="bg-white hover:bg-gray-100 text-gray-800 font-bold text-xs px-3.5 py-2 rounded-xl transition-all border border-gray-200 flex items-center gap-1.5 shadow-2xs">
                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Export Roster CSV
                </a>

                <!-- Enroll Employee Button -->
                <button @click="showEnrollModal = true" type="button" 
                        class="bg-gray-900 hover:bg-black text-white font-black text-xs px-4 py-2 rounded-xl transition-all shadow-sm flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    Enroll Employee
                </button>
            </div>
        </div>

        <!-- 4 Summary Stat Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Active Policyholders</p>
                <p class="text-xl font-black font-outfit text-gray-900 mt-1">{{ $stats['total_active'] }} Employees</p>
                <p class="text-[11px] text-emerald-600 font-bold mt-1">100% Policy Roster Verified</p>
            </div>

            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Annual Corporate Premiums</p>
                <p class="text-xl font-black font-outfit text-purple-600 mt-1">PHP {{ number_format($stats['total_annual_premiums'], 2) }}</p>
                <p class="text-[11px] text-gray-500 mt-1">Includes Company + Employee Co-pay</p>
            </div>

            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Renewals Due (30 Days)</p>
                <p class="text-xl font-black font-outfit {{ $stats['expiring_soon'] > 0 ? 'text-amber-600' : 'text-gray-900' }} mt-1">
                    {{ $stats['expiring_soon'] }} Policies
                </p>
                <p class="text-[11px] text-gray-500 mt-1">Annual 30-Day Renewal Window</p>
            </div>

            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Medical Claims Utilized</p>
                <p class="text-xl font-black font-outfit text-rose-600 mt-1">PHP {{ number_format($stats['total_utilized'], 2) }}</p>
                <p class="text-[11px] text-gray-500 mt-1">Deducted from Employee MBLs</p>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 1: ACTIVE WORKFORCE HEALTHCARE COVERAGE ROSTER -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'roster'" x-transition class="space-y-6">

            <!-- Quick Filter Buttons -->
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('hmo.enrollments', ['tab' => 'roster', 'filter' => 'all']) }}" 
                       class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all {{ ($filter ?? 'all') === 'all' ? 'bg-gray-900 text-white shadow-sm' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200' }}">
                        All Employees
                    </a>
                    <a href="{{ route('hmo.enrollments', ['tab' => 'roster', 'filter' => 'drivers']) }}" 
                       class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all {{ ($filter ?? '') === 'drivers' ? 'bg-gray-900 text-white shadow-sm' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200' }}">
                        Drivers
                    </a>
                    <a href="{{ route('hmo.enrollments', ['tab' => 'roster', 'filter' => 'office']) }}" 
                       class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all {{ ($filter ?? '') === 'office' ? 'bg-gray-900 text-white shadow-sm' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200' }}">
                        Office Staff
                    </a>
                    <a href="{{ route('hmo.enrollments', ['tab' => 'roster', 'filter' => 'expiring']) }}" 
                       class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all {{ ($filter ?? '') === 'expiring' ? 'bg-amber-600 text-white shadow-sm' : 'bg-white text-amber-700 hover:bg-amber-50 border border-amber-200' }}">
                        Expiring Soon
                    </a>
                </div>
            </div>

            <!-- Filter & Search Bar -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-4 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <form action="{{ route('hmo.enrollments') }}" method="GET" class="flex flex-wrap items-center gap-3 flex-1">
                    <input type="hidden" name="tab" value="roster">
                    <input type="hidden" name="filter" value="{{ $filter ?? 'all' }}">

                    <div class="relative flex-1 min-w-[220px]">
                        <input type="text" name="search" value="{{ $search }}" placeholder="Search by name, employee code, card number, or provider..." 
                               class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl pl-9 pr-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-gray-900">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    <select name="tier" class="text-xs bg-gray-50 border border-gray-200 rounded-xl px-3 py-2.5 text-gray-800 font-bold focus:outline-none focus:border-gray-900">
                        <option value="all">All Coverage Tiers</option>
                        <option value="Basic" {{ $tier === 'Basic' ? 'selected' : '' }}>Basic</option>
                        <option value="Plus" {{ $tier === 'Plus' ? 'selected' : '' }}>Plus</option>
                        <option value="Premium" {{ $tier === 'Premium' ? 'selected' : '' }}>Premium</option>
                        <option value="Driver Fleet Care" {{ $tier === 'Driver Fleet Care' ? 'selected' : '' }}>Driver Fleet Care</option>
                    </select>

                    <select name="status" class="text-xs bg-gray-50 border border-gray-200 rounded-xl px-3 py-2.5 text-gray-800 font-bold focus:outline-none focus:border-gray-900">
                        <option value="all">All Statuses</option>
                        <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive / Terminated</option>
                        <option value="expired" {{ $status === 'expired' ? 'selected' : '' }}>Expired</option>
                    </select>

                    <button type="submit" class="bg-gray-900 hover:bg-black text-white text-xs font-black px-4 py-2.5 rounded-xl transition-all shadow-sm">
                        Filter Roster
                    </button>
                </form>
            </div>

            <!-- Enrollment Table -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-gray-50/80 border-b border-gray-100 text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                <th class="py-3.5 px-4">Employee Member</th>
                                <th class="py-3.5 px-4">Provider & Card Number</th>
                                <th class="py-3.5 px-4">Coverage Tier</th>
                                <th class="py-3.5 px-4 text-right">Annual MBL & Remaining</th>
                                <th class="py-3.5 px-4 text-right">Monthly Premium Split</th>
                                <th class="py-3.5 px-4">Coverage Period</th>
                                <th class="py-3.5 px-4 text-center">Status</th>
                                <th class="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($enrollments as $enrollment)
                                @php
                                    $mblLimit = (float) ($enrollment->annual_limit ?: $enrollment->mbl_amount);
                                    $utilized = (float) $enrollment->totalUtilized();
                                    $remaining = max(0.00, $mblLimit - $utilized);
                                    $percentUsed = $mblLimit > 0 ? min(100, round(($utilized / $mblLimit) * 100)) : 0;
                                @endphp
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    
                                    <!-- Employee Member -->
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-gray-900 font-outfit">
                                            {{ $enrollment->employee?->first_name }} {{ $enrollment->employee?->last_name }}
                                        </div>
                                        <div class="text-[10px] text-gray-400 font-mono">
                                            {{ $enrollment->employee?->employee_code }} • {{ $enrollment->employee?->department?->name ?? 'General' }}
                                        </div>
                                        @if($enrollment->dependent_count > 0)
                                            <span class="inline-block mt-1 px-2 py-0.5 rounded bg-blue-50 text-blue-700 text-[9px] font-bold">
                                                {{ $enrollment->dependent_count }} Registered {{ Str::plural('Dependent', $enrollment->dependent_count) }}
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Provider & Card Number -->
                                    <td class="py-3.5 px-4">
                                        <div class="font-mono font-bold text-gray-900">{{ $enrollment->hmo_card_number }}</div>
                                        <div class="text-[10px] text-gray-500">{{ $enrollment->hmo_provider }} ({{ $enrollment->provider_plan }})</div>
                                    </td>

                                    <!-- Coverage Tier -->
                                    <td class="py-3.5 px-4">
                                        <span class="px-2.5 py-1 rounded-md text-[10px] font-black uppercase tracking-wider
                                            @if(str_contains(strtolower($enrollment->coverage_tier), 'basic')) bg-gray-100 text-gray-700
                                             @elseif(str_contains(strtolower($enrollment->coverage_tier), 'plus')) bg-blue-50 text-blue-700 border border-blue-200
                                             @elseif(str_contains(strtolower($enrollment->coverage_tier), 'premium')) bg-purple-50 text-purple-700 border border-purple-200
                                             @else bg-emerald-50 text-emerald-700 border border-emerald-200 @endif">
                                            {{ $enrollment->coverage_tier }}
                                        </span>
                                    </td>

                                    <!-- MBL & Remaining Progress Bar -->
                                    <td class="py-3.5 px-4 text-right">
                                        <div class="font-black font-outfit text-gray-900">PHP {{ number_format($remaining, 2) }}</div>
                                        <div class="text-[10px] text-gray-400">Limit: PHP {{ number_format($mblLimit, 2) }}</div>
                                        <div class="w-full bg-gray-100 rounded-full h-1.5 mt-1 overflow-hidden">
                                            <div class="h-1.5 rounded-full {{ $percentUsed > 80 ? 'bg-rose-500' : ($percentUsed > 50 ? 'bg-amber-500' : 'bg-emerald-500') }}" 
                                                 style="width: {{ $percentUsed }}%"></div>
                                        </div>
                                        @if($enrollment->isLowBalance())
                                            <span class="inline-block mt-1 px-2 py-0.5 rounded bg-rose-50 border border-rose-200 text-rose-700 text-[9px] font-black tracking-wide">
                                                Low Balance (&lt; 20%)
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Monthly Premium Split -->
                                    <td class="py-3.5 px-4 text-right">
                                        @php
                                            $coShare = $enrollment->calculateCoShare();
                                        @endphp
                                        <div class="font-mono font-bold text-gray-900">PHP {{ number_format((float)$enrollment->monthly_premium, 2) }}/mo</div>
                                        <div class="text-[10px] text-gray-500 font-mono">
                                            Company: ₱{{ number_format($coShare['company_share'], 0) }} • Employee: ₱{{ number_format($coShare['employee_share'], 0) }}
                                        </div>
                                    </td>

                                    <!-- Coverage Period -->
                                    <td class="py-3.5 px-4 font-mono text-[11px] text-gray-600">
                                        <div>{{ $enrollment->coverage_start_date?->format('M j, Y') ?? 'N/A' }}</div>
                                        <div class="text-gray-400">to {{ $enrollment->coverage_end_date?->format('M j, Y') ?? 'N/A' }}</div>
                                        @if($enrollment->isExpiringSoon())
                                            <span class="inline-block mt-0.5 px-2 py-0.5 bg-amber-50 border border-amber-200 text-amber-800 rounded text-[9px] font-black animate-pulse">
                                                Renews in {{ $enrollment->daysUntilExpiry() }}d
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Status -->
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase
                                            @if($enrollment->status === 'active') bg-emerald-50 text-emerald-700 border border-emerald-200
                                            @elseif($enrollment->status === 'inactive') bg-rose-50 text-rose-700 border border-rose-200
                                            @else bg-gray-100 text-gray-600 @endif">
                                            {{ $enrollment->status }}
                                        </span>
                                    </td>

                                    <!-- Actions -->
                                    <td class="py-3.5 px-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <!-- Log Utilization Modal Trigger -->
                                            <button @click="openUtilize({{ $enrollment->toJson() }})" type="button" 
                                                    class="px-2.5 py-1 bg-white hover:bg-gray-100 text-gray-700 border border-gray-200 rounded-lg text-[10px] font-bold transition-all shadow-2xs">
                                                Log Use
                                            </button>

                                            <!-- Edit Modal Trigger -->
                                            <button @click="openEdit({{ $enrollment->toJson() }})" type="button" 
                                                    class="px-2.5 py-1 bg-white hover:bg-gray-100 text-blue-700 border border-blue-200 rounded-lg text-[10px] font-bold transition-all shadow-2xs">
                                                Edit
                                            </button>

                                            <!-- 1-Click Renew Action -->
                                            @if($enrollment->isExpiringSoon() || $enrollment->status === 'expired')
                                                <form action="{{ route('hmo.enrollments.renew', $enrollment) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="px-2.5 py-1 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-[10px] font-black transition-all shadow-2xs">
                                                        Renew +1Y
                                                    </button>
                                                </form>
                                            @endif

                                            <!-- Deactivate on Separation Trigger -->
                                            @if($enrollment->status === 'active')
                                                <button @click="openDeactivate({{ $enrollment->toJson() }})" type="button" 
                                                        class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-[10px] font-bold transition-all">
                                                    Deactivate
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-12 text-gray-400 text-xs">
                                        No employee enrollment policies match the selected filters.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($enrollments->hasPages())
                    <div class="p-4 border-t border-gray-100">
                        {{ $enrollments->links() }}
                    </div>
                @endif
            </div>

            <!-- Recent Medical Availment Utilization Ledger -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-black font-outfit text-gray-900">Recent Healthcare Utilization Audit Ledger</h2>
                        <p class="text-[11px] text-gray-500 mt-0.5">Real-time medical service claims deducted against active policy Maximum Benefit Limits (MBL).</p>
                    </div>
                    <span class="text-xs text-gray-400 font-mono">Live Sync</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-gray-50/80 border-b border-gray-100 text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                <th class="py-3 px-4">Date</th>
                                <th class="py-3 px-4">Employee</th>
                                <th class="py-3 px-4">Service Type</th>
                                <th class="py-3 px-4">Medical Facility</th>
                                <th class="py-3 px-4 text-right">Amount Claimed</th>
                                <th class="py-3 px-4 text-right">Remaining MBL</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($utilizationLogs as $log)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="py-3 px-4 text-gray-600 font-mono text-[11px]">
                                        {{ $log->service_date ? $log->service_date->format('M j, Y') : ($log->utilized_at ? $log->utilized_at->format('M j, Y') : 'N/A') }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-gray-900">{{ $log->employee?->first_name }} {{ $log->employee?->last_name }}</div>
                                        <div class="text-[10px] text-gray-400 font-mono">{{ $log->employee?->employee_code }}</div>
                                    </td>
                                    <td class="py-3 px-4 font-semibold text-gray-700">
                                        {{ $log->service_type ?: $log->benefit_type }}
                                    </td>
                                    <td class="py-3 px-4 text-gray-600">
                                        {{ $log->hospital_clinic_name ?: $log->service_provider }}
                                    </td>
                                    <td class="py-3 px-4 text-right font-black font-outfit text-rose-600">
                                        PHP {{ number_format((float)$log->utilized_amount, 2) }}
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono font-bold text-gray-900">
                                        PHP {{ number_format((float)$log->remaining_mbl ?: (float)$log->remaining_balance, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-8 text-gray-400 text-xs">No medical utilization claims logged yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- ========================================================================= -->
        <!-- TAB 2: ELIGIBILITY ENGINE & APPROVALS QUEUE -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'approvals'" x-transition class="space-y-6">

            <!-- Pending Self-Service Application Approvals Stepper -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-black font-outfit text-gray-900">Employee Self-Service (ESS) Benefits Approval Pipeline</h2>
                        <p class="text-[11px] text-gray-500 mt-0.5">Review, verify PSA documents, request budget from Finance, and activate healthcare policies.</p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-black bg-amber-50 border border-amber-200 text-amber-800">
                        {{ $pendingApplications->count() }} In-Pipeline
                    </span>
                </div>

                <div class="divide-y divide-gray-100">
                    @forelse($pendingApplications as $app)
                        <div class="p-5 space-y-4 hover:bg-gray-50/40 transition-colors">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-black font-outfit text-gray-900 text-sm">
                                            {{ $app->employee?->first_name }} {{ $app->employee?->last_name }}
                                        </span>
                                        <span class="text-xs text-gray-400 font-mono">({{ $app->employee?->employee_code }})</span>
                                        <span class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-[10px] font-bold">
                                            {{ $app->employee?->department?->name ?? 'General' }} • {{ $app->employee?->position }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Requested Plan: <span class="font-bold text-gray-800">{{ $app->provider_plan }}</span> (Tier: {{ $app->coverage_tier }}) • MBL: PHP {{ number_format((float)($app->annual_limit ?: $app->mbl_amount), 2) }}
                                    </p>
                                </div>

                                <!-- Current Stepper Status Badge -->
                                <div>
                                    @if($app->enrollment_status === 'submitted')
                                        <span class="px-3 py-1 bg-amber-50 text-amber-800 border border-amber-200 rounded-xl text-xs font-bold">Step 1: HR Review Pending</span>
                                    @elseif($app->enrollment_status === 'hr_approved')
                                        <span class="px-3 py-1 bg-blue-50 text-blue-800 border border-blue-200 rounded-xl text-xs font-bold">Step 2: Budget Request to Finance</span>
                                    @elseif($app->enrollment_status === 'budget_requested')
                                        <span class="px-3 py-1 bg-purple-50 text-purple-800 border border-purple-200 rounded-xl text-xs font-bold">Step 3: Awaiting Provider Card</span>
                                    @endif
                                </div>
                            </div>

                            <!-- Visual 4-Step Approval Pipeline Stepper -->
                            <div class="grid grid-cols-1 sm:grid-cols-4 gap-2 pt-2 border-t border-gray-100">
                                
                                <!-- Step 1: ESS Application -->
                                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-2.5 text-xs text-emerald-900">
                                    <div class="font-bold flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        1. Applied via ESS
                                    </div>
                                    <div class="text-[10px] text-emerald-700 mt-0.5">{{ $app->created_at->format('M j, Y') }}</div>
                                </div>

                                <!-- Step 2: HR Verification -->
                                <div class="{{ in_array($app->enrollment_status, ['hr_approved', 'budget_requested', 'active']) ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-amber-50 border-amber-200 text-amber-900' }} border rounded-xl p-2.5 text-xs">
                                    <div class="font-bold flex items-center gap-1.5">
                                        @if(in_array($app->enrollment_status, ['hr_approved', 'budget_requested', 'active']))
                                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            2. HR Verified
                                        @else
                                            <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                                            2. HR Review
                                        @endif
                                    </div>
                                    <div class="text-[10px] {{ in_array($app->enrollment_status, ['hr_approved', 'budget_requested', 'active']) ? 'text-emerald-700' : 'text-amber-700' }} mt-0.5">
                                        {{ $app->hr_reviewed_at ? $app->hr_reviewed_at->format('M j, Y') : 'Pending Verification' }}
                                    </div>
                                </div>

                                <!-- Step 3: Budget Requisition -->
                                <div class="{{ in_array($app->enrollment_status, ['budget_requested', 'active']) ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : ($app->enrollment_status === 'hr_approved' ? 'bg-blue-50 border-blue-200 text-blue-900' : 'bg-gray-50 border-gray-200 text-gray-400') }} border rounded-xl p-2.5 text-xs">
                                    <div class="font-bold flex items-center gap-1.5">
                                        @if(in_array($app->enrollment_status, ['budget_requested', 'active']))
                                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            3. Budget Transmitted
                                        @else
                                            <span class="w-2 h-2 rounded-full {{ $app->enrollment_status === 'hr_approved' ? 'bg-blue-500 animate-pulse' : 'bg-gray-300' }}"></span>
                                            3. Finance Budget
                                        @endif
                                    </div>
                                    <div class="text-[10px] mt-0.5">
                                        {{ $app->budgetRequisition ? $app->budgetRequisition->requisition_code : 'Awaiting Transmission' }}
                                    </div>
                                </div>

                                <!-- Step 4: Card Issuance & Active -->
                                <div class="{{ $app->enrollment_status === 'active' ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : ($app->enrollment_status === 'budget_requested' ? 'bg-purple-50 border-purple-200 text-purple-900' : 'bg-gray-50 border-gray-200 text-gray-400') }} border rounded-xl p-2.5 text-xs">
                                    <div class="font-bold flex items-center gap-1.5">
                                        @if($app->enrollment_status === 'active')
                                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            4. Card Issued
                                        @else
                                            <span class="w-2 h-2 rounded-full {{ $app->enrollment_status === 'budget_requested' ? 'bg-purple-500 animate-pulse' : 'bg-gray-300' }}"></span>
                                            4. Card Issuance
                                        @endif
                                    </div>
                                    <div class="text-[10px] mt-0.5">
                                        {{ $app->hmo_card_number ? '#' . $app->hmo_card_number : 'Pending Card #' }}
                                    </div>
                                </div>

                            </div>

                            <!-- Stepper Actions Bar -->
                            <div class="flex items-center justify-end gap-2 pt-2">
                                @if($app->enrollment_status === 'submitted')
                                    <form action="{{ route('hmo.enrollments.hr-validate', $app) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-all shadow-2xs">
                                            Verify & Approve Eligibility
                                        </button>
                                    </form>
                                @elseif($app->enrollment_status === 'hr_approved')
                                    <form action="{{ route('hmo.enrollments.request-budget', $app) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-all shadow-2xs">
                                            Transmit Budget Request (Team 5)
                                        </button>
                                    </form>
                                @elseif($app->enrollment_status === 'budget_requested')
                                    <button @click="openActivate({{ $app->toJson() }})" type="button" 
                                            class="px-3.5 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-bold transition-all shadow-2xs">
                                        Assign Card # & Finalize Activation
                                    </button>
                                @endif

                                <button @click="openReject({{ $app->toJson() }})" type="button" 
                                        class="px-3.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl text-xs font-bold transition-all">
                                    Reject
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="p-12 text-center text-gray-400 text-xs">
                            No pending self-service applications awaiting review.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Automated Workforce Eligibility Rules Status Engine -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-black font-outfit text-gray-900">Workforce Benefits Eligibility Status Matrix</h2>
                        <p class="text-[11px] text-gray-500 mt-0.5">Automated policy checks evaluating tenure, regularization status, and position grade.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-gray-50/80 border-b border-gray-100 text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                <th class="py-3 px-4">Employee</th>
                                <th class="py-3 px-4">Department & Position</th>
                                <th class="py-3 px-4">Employment Status</th>
                                <th class="py-3 px-4">Tenure Duration</th>
                                <th class="py-3 px-4">Eligibility Status</th>
                                <th class="py-3 px-4">Qualified Healthcare Tier</th>
                                <th class="py-3 px-4">Active Roster Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($employeeEligibility as $item)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-gray-900 font-outfit">{{ $item['employee']->first_name }} {{ $item['employee']->last_name }}</div>
                                        <div class="text-[10px] text-gray-400 font-mono">{{ $item['employee']->employee_code }}</div>
                                    </td>
                                    <td class="py-3 px-4 text-gray-700">
                                        {{ $item['employee']->department?->name ?? 'General' }} • {{ $item['employee']->position }}
                                    </td>
                                    <td class="py-3 px-4 font-bold text-gray-800">
                                        {{ ucfirst($item['employee']->employment_status ?? 'regular') }}
                                    </td>
                                    <td class="py-3 px-4 font-mono text-[11px] text-gray-600">
                                        {{ $item['eligibility']['tenure_months'] }} mos
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $item['eligibility']['badge_class'] }}">
                                            {{ $item['eligibility']['label'] }}
                                        </span>
                                        <div class="text-[10px] text-gray-500 mt-0.5">{{ $item['eligibility']['reason'] }}</div>
                                    </td>
                                    <td class="py-3 px-4 font-black font-outfit text-gray-900">
                                        {{ $item['eligibility']['eligible_plan'] }}
                                    </td>
                                    <td class="py-3 px-4">
                                        @if($item['active_enrollment'])
                                            <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded text-[9px] font-black">
                                                Enrolled (#{{ $item['active_enrollment']->hmo_card_number }})
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 bg-gray-100 text-gray-500 rounded text-[9px] font-bold">
                                                Not Enrolled
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
        <!-- MODAL: ENROLL EMPLOYEE -->
        <!-- ========================================================================= -->
        <div x-show="showEnrollModal" x-cloak class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div @click.away="showEnrollModal = false" class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-base font-black font-outfit text-gray-900">Enroll Employee into HMO Policy</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Issue new healthcare coverage policy</p>
                    </div>
                    <button @click="showEnrollModal = false" type="button" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
                </div>

                <form action="{{ route('hmo.enroll') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Select Employee *</label>
                        <select name="employee_id" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->first_name }} {{ $emp->last_name }} ({{ $emp->employee_code }} - {{ $emp->position }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">HMO Provider *</label>
                            <input type="text" name="hmo_provider" value="{{ $hmoConfig['hmo_provider_name'] ?? 'Maxicare' }}" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Provider Plan Name *</label>
                            <input type="text" name="provider_plan" value="Corporate Comprehensive Care" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Coverage Tier *</label>
                            <select name="coverage_tier" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                                <option value="Basic">Basic (PG 1-2)</option>
                                <option value="Plus">Plus (PG 3-4)</option>
                                <option value="Premium">Premium (PG 5-6)</option>
                                <option value="Driver Fleet Care">Driver Fleet Care</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Annual MBL Limit (PHP) *</label>
                            <input type="number" name="annual_limit" value="150000" step="1000" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Monthly Premium (PHP) *</label>
                            <input type="number" name="monthly_premium" value="1800" step="50" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Dependent Count</label>
                            <input type="number" name="dependent_count" value="0" min="0" max="10" class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Coverage Start Date *</label>
                            <input type="date" name="coverage_start_date" value="{{ now()->format('Y-m-d') }}" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Coverage End Date *</label>
                            <input type="date" name="coverage_end_date" value="{{ now()->addYear()->format('Y-m-d') }}" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Policy Notes</label>
                        <textarea name="notes" rows="2" placeholder="Enrollment justification and remarks..." class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-gray-800 focus:outline-none focus:border-gray-900"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                        <button @click="showEnrollModal = false" type="button" class="text-xs font-bold text-gray-500 px-4 py-2">Cancel</button>
                        <button type="submit" class="bg-gray-900 hover:bg-black text-white font-black text-xs px-5 py-2 rounded-xl transition-all shadow-sm">Complete Enrollment</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: LOG MEDICAL UTILIZATION / CLAIM -->
        <!-- ========================================================================= -->
        <div x-show="showUtilizeModal" x-cloak class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div @click.away="showUtilizeModal = false" class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-base font-black font-outfit text-gray-900">Record Medical Utilization</h2>
                        <p class="text-xs text-gray-400 mt-0.5" x-text="'Deduct claim from ' + (selectedEnrollment ? selectedEnrollment.hmo_card_number : '')"></p>
                    </div>
                    <button @click="showUtilizeModal = false" type="button" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
                </div>

                <form action="{{ route('hmo.log-utilization') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="hmo_enrollment_id" :value="selectedEnrollment ? selectedEnrollment.id : ''">

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Service Type *</label>
                        <select name="service_type" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                            <option value="Outpatient Consultation">Outpatient Consultation</option>
                            <option value="Emergency Room (ER)">Emergency Room (ER)</option>
                            <option value="Hospitalization / Inpatient">Hospitalization / Inpatient</option>
                            <option value="Laboratory / Diagnostic">Laboratory / Diagnostic</option>
                            <option value="Dental Treatment">Dental Treatment</option>
                            <option value="Optical Examination">Optical Examination</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Hospital / Clinic Facility Name *</label>
                        <input type="text" name="hospital_clinic_name" required placeholder="e.g. St. Luke's Medical Center" class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Claim Amount (PHP) *</label>
                            <input type="number" name="utilized_amount" step="10" min="1" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Service Date *</label>
                            <input type="date" name="service_date" value="{{ now()->format('Y-m-d') }}" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Diagnosis & Remarks</label>
                        <textarea name="diagnosis" rows="2" placeholder="Brief diagnostic description..." class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-gray-800 focus:outline-none focus:border-gray-900"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                        <button @click="showUtilizeModal = false" type="button" class="text-xs font-bold text-gray-500 px-4 py-2">Cancel</button>
                        <button type="submit" class="bg-gray-900 hover:bg-black text-white font-black text-xs px-5 py-2 rounded-xl transition-all shadow-sm">Record & Deduct MBL</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: DEACTIVATE ON SEPARATION -->
        <!-- ========================================================================= -->
        <div x-show="showDeactivateModal" x-cloak class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div @click.away="showDeactivateModal = false" class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-base font-black font-outfit text-rose-700">Deactivate Policy on Separation</h2>
                        <p class="text-xs text-gray-400 mt-0.5" x-text="'Terminate coverage for ' + (selectedEnrollment && selectedEnrollment.employee ? selectedEnrollment.employee.first_name + ' ' + selectedEnrollment.employee.last_name : '')"></p>
                    </div>
                    <button @click="showDeactivateModal = false" type="button" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
                </div>

                <form :action="'{{ url('/hmo-benefits/enrollments') }}/' + (selectedEnrollment ? selectedEnrollment.id : '') + '/deactivate'" method="POST" class="space-y-4">
                    @csrf

                    <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs space-y-1">
                        <p class="font-bold">Important Notice:</p>
                        <p>Deactivating will cancel the employee's active HMO coverage, invalidate all registered dependent e-cards, and halt all future payroll deductions for this policy.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Reason for Separation / Termination *</label>
                        <select name="separation_reason" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                            <option value="Voluntary Resignation">Voluntary Resignation</option>
                            <option value="End of Employment Contract">End of Employment Contract</option>
                            <option value="Separation / Offboarding">Separation / Offboarding</option>
                            <option value="Policy Non-Renewal">Policy Non-Renewal</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                        <button @click="showDeactivateModal = false" type="button" class="text-xs font-bold text-gray-500 px-4 py-2">Cancel</button>
                        <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white font-black text-xs px-5 py-2 rounded-xl transition-all shadow-sm">Confirm Termination</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: ISSUE OFFICIAL MEMBER CARD -->
        <!-- ========================================================================= -->
        <div x-show="showActivateModal" x-cloak class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div @click.away="showActivateModal = false" class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-base font-black font-outfit text-gray-900">Issue Official HMO Member Card</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Finalize coverage activation</p>
                    </div>
                    <button @click="showActivateModal = false" type="button" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
                </div>

                <form :action="'{{ url('/hmo-benefits/enrollments') }}/' + (selectedEnrollment ? selectedEnrollment.id : '') + '/activate'" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Official Provider Card Number *</label>
                        <input type="text" name="hmo_card_number" required placeholder="e.g. MAX-2026-9901" class="w-full text-xs font-mono font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Provider Plan Name</label>
                        <input type="text" name="provider_plan" placeholder="e.g. Maxicare Comprehensive Gold" class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                        <button @click="showActivateModal = false" type="button" class="text-xs font-bold text-gray-500 px-4 py-2">Cancel</button>
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs px-5 py-2 rounded-xl transition-all shadow-sm">Issue Card & Activate</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: REJECT APPLICATION -->
        <!-- ========================================================================= -->
        <div x-show="showRejectModal" x-cloak class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div @click.away="showRejectModal = false" class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-base font-black font-outfit text-rose-700">Reject HMO Application</h2>
                        <p class="text-xs text-gray-400 mt-0.5">State justification for rejection</p>
                    </div>
                    <button @click="showRejectModal = false" type="button" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
                </div>

                <form :action="'{{ url('/hmo-benefits/enrollments') }}/' + (selectedEnrollment ? selectedEnrollment.id : '') + '/reject'" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Rejection Reason *</label>
                        <textarea name="rejection_reason" rows="3" required placeholder="State exact deficiency or reason for rejection..." class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-gray-800 focus:outline-none focus:border-gray-900"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                        <button @click="showRejectModal = false" type="button" class="text-xs font-bold text-gray-500 px-4 py-2">Cancel</button>
                        <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white font-black text-xs px-5 py-2 rounded-xl transition-all shadow-sm">Reject Application</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: EDIT HMO ENROLLMENT POLICY -->
        <!-- ========================================================================= -->
        <div x-show="showEditModal" x-cloak class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div @click.away="showEditModal = false" class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-base font-black font-outfit text-gray-900">Edit HMO Policy Enrollment</h2>
                        <p class="text-xs text-gray-400 mt-0.5" x-text="'Update policy record for ' + (selectedEnrollment && selectedEnrollment.employee ? selectedEnrollment.employee.first_name + ' ' + selectedEnrollment.employee.last_name : (selectedEnrollment ? selectedEnrollment.hmo_card_number : ''))"></p>
                    </div>
                    <button @click="showEditModal = false" type="button" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
                </div>

                <form :action="'{{ url('/hmo-benefits/plans') }}/' + (selectedEnrollment ? selectedEnrollment.id : '') + '/update'" method="POST" class="space-y-4">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">HMO Provider *</label>
                            <input type="text" name="hmo_provider" :value="selectedEnrollment ? selectedEnrollment.hmo_provider : 'Maxicare'" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Provider Plan Name *</label>
                            <input type="text" name="provider_plan" :value="selectedEnrollment ? selectedEnrollment.provider_plan : 'Standard Plus'" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Coverage Tier *</label>
                            <select name="coverage_tier" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                                <option value="Basic" :selected="selectedEnrollment && selectedEnrollment.coverage_tier === 'Basic'">Basic</option>
                                <option value="Plus" :selected="selectedEnrollment && selectedEnrollment.coverage_tier === 'Plus'">Plus</option>
                                <option value="Premium" :selected="selectedEnrollment && selectedEnrollment.coverage_tier === 'Premium'">Premium</option>
                                <option value="Driver Fleet Care" :selected="selectedEnrollment && selectedEnrollment.coverage_tier === 'Driver Fleet Care'">Driver Fleet Care</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Enrollment Status *</label>
                            <select name="status" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                                <option value="active" :selected="selectedEnrollment && selectedEnrollment.status === 'active'">Active</option>
                                <option value="inactive" :selected="selectedEnrollment && selectedEnrollment.status === 'inactive'">Inactive</option>
                                <option value="expired" :selected="selectedEnrollment && selectedEnrollment.status === 'expired'">Expired</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Annual MBL Limit (PHP) *</label>
                            <input type="number" step="100" name="annual_limit" :value="selectedEnrollment ? (selectedEnrollment.annual_limit || selectedEnrollment.mbl_amount) : ''" required class="w-full text-xs font-bold font-mono bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Monthly Premium (PHP) *</label>
                            <input type="number" step="10" name="monthly_premium" :value="selectedEnrollment ? selectedEnrollment.monthly_premium : ''" required class="w-full text-xs font-bold font-mono bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Coverage Start Date *</label>
                            <input type="date" name="coverage_start_date" :value="selectedEnrollment && selectedEnrollment.coverage_start_date ? selectedEnrollment.coverage_start_date.substring(0, 10) : ''" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Coverage End Date *</label>
                            <input type="date" name="coverage_end_date" :value="selectedEnrollment && selectedEnrollment.coverage_end_date ? selectedEnrollment.coverage_end_date.substring(0, 10) : ''" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Number of Dependents</label>
                        <input type="number" name="dependent_count" min="0" max="10" :value="selectedEnrollment ? (selectedEnrollment.dependent_count || (selectedEnrollment.dependents ? selectedEnrollment.dependents.length : 0)) : 0" class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Policy Modification Notes</label>
                        <textarea name="notes" rows="2" placeholder="Reason for modification, endorsement ref, or special rider terms..." :value="selectedEnrollment ? selectedEnrollment.notes : ''" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-gray-800 focus:outline-none focus:border-gray-900"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                        <button @click="showEditModal = false" type="button" class="text-xs font-bold text-gray-500 px-4 py-2">Cancel</button>
                        <button type="submit" class="bg-gray-900 hover:bg-black text-white font-black text-xs px-5 py-2 rounded-xl transition-all shadow-sm">Save Policy Changes</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

@endsection
