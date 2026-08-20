@extends('layouts.app')

@php
    $pageTitle = 'Driver Accident Insurance Pool';
    $currentPage = 'driver-insurance.index';
@endphp

@section('content')

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-black font-outfit text-gray-900 tracking-tight">Driver Accident Insurance Pool & Claims Governance</h1>
            <p class="text-xs text-gray-500 mt-1">Multi-level claims governance, on-the-road hospitalization payouts, 3% salary deduction pool, and corporate matching subsidy.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="flex items-center gap-2 text-xs font-bold text-gray-800 bg-white border border-gray-200 px-3.5 py-1.5 rounded-full shadow-2xs">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                Driver Protection Pool Active
            </span>
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

    @if(session('error'))
        <div class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-800 text-xs rounded-2xl font-bold flex items-center gap-2 shadow-2xs">
            <svg class="w-4 h-4 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            {{ session('error') }}
        </div>
    @endif

    <!-- Main Container with Alpine.js State -->
    <div x-data="{ 
        activeTab: 'claims', {{-- 'claims', 'ledger' --}}
        showActionModal: false,
        showReturnModal: false,
        showPolicyModal: false,
        showEvidenceModal: false,
        showClaimModal: false,
        showDriverHistoryModal: false,
        driverHistoryLoading: false,
        driverHistoryData: null,
        
        selectedClaim: null,
        actionType: '',
        actionUrl: '',
        actionIncidentRef: '',
        actionAmount: '',
        actionRemarks: '',

        openAction(type, url, ref, currentAmount, defaultRemarks = '') {
            this.actionType = type;
            this.actionUrl = url;
            this.actionIncidentRef = ref;
            this.actionAmount = currentAmount;
            this.actionRemarks = defaultRemarks;
            this.showActionModal = true;
        },

        openReturn(claim) {
            this.selectedClaim = claim;
            this.showReturnModal = true;
        },

        openEvidence(claim) {
            this.selectedClaim = claim;
            this.showEvidenceModal = true;
        },

        inspectDriver(empId) {
            this.driverHistoryLoading = true;
            this.showDriverHistoryModal = true;
            fetch('/driver-insurance/driver/' + empId + '/history')
                .then(r => r.json())
                .then(data => {
                    this.driverHistoryData = data;
                    this.driverHistoryLoading = false;
                })
                .catch(() => {
                    this.driverHistoryLoading = false;
                });
        }
    }" class="space-y-6 pb-12">

        <!-- Top Navigation Bar & Actions -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 border-b border-gray-200 pb-3">
            <div class="flex flex-wrap items-center gap-1.5 bg-gray-100 p-1 rounded-2xl">
                
                <!-- Tab 1: Claims Queue -->
                <button type="button" @click="activeTab = 'claims'" 
                        :class="activeTab === 'claims' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold'" 
                        class="px-3.5 py-2 text-xs rounded-xl transition-all flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#F44336]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Accident Claims Queue
                    <span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-gray-200 text-gray-800">{{ $accidentClaims->total() }}</span>
                </button>

                <!-- Tab 2: Driver Pool Fund Accounting & Ledger -->
                <button type="button" @click="activeTab = 'ledger'" 
                        :class="activeTab === 'ledger' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold'" 
                        class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#F44336]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Pool Fund Accounting & Ledger
                    <span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-gray-200 text-gray-800">{{ $ledger->total() }}</span>
                </button>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Export Financial Statement CSV -->
                <a href="{{ route('driver-insurance.export-statement') }}" 
                   class="bg-white hover:bg-gray-100 text-gray-800 font-bold text-xs px-3.5 py-2 rounded-xl transition-all border border-gray-200 flex items-center gap-1.5 shadow-2xs">
                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Financial Statement CSV
                </a>

                <!-- Export Ledger CSV -->
                <a href="{{ route('driver-insurance.export-ledger') }}" 
                   class="bg-white hover:bg-gray-100 text-gray-800 font-bold text-xs px-3.5 py-2 rounded-xl transition-all border border-gray-200 flex items-center gap-1.5 shadow-2xs">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Export Ledger CSV
                </a>

                <!-- Policy Config Button -->
                <button @click="showPolicyModal = true" type="button" 
                        class="bg-white hover:bg-gray-100 text-gray-800 font-bold text-xs px-3.5 py-2 rounded-xl transition-all border border-gray-200 flex items-center gap-1.5 shadow-2xs">
                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    </svg>
                    Pool Settings
                </button>

                <!-- File Accident Claim Button -->
                <button @click="showClaimModal = true" type="button" 
                        class="bg-gray-900 hover:bg-black text-white font-black text-xs px-3.5 py-2 rounded-xl transition-all shadow-sm flex items-center gap-1.5 cursor-pointer">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    File Accident Claim
                </button>
            </div>
        </div>

        <!-- 4 Summary Stat Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Driver 3% Deductions</p>
                <p class="text-2xl font-black font-outfit text-emerald-600 mt-1">PHP {{ number_format($stats['total_driver_contributions'], 2) }}</p>
                <p class="text-[11px] text-gray-500 mt-1">Rate: {{ $stats['contribution_rate_pct'] }}% of Gross Earnings ({{ $stats['active_drivers_count'] }} Drivers)</p>
            </div>

            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">TripWise Corporate Match</p>
                <p class="text-2xl font-black font-outfit text-blue-600 mt-1">PHP {{ number_format($stats['total_company_subsidies'], 2) }}</p>
                <p class="text-[11px] text-gray-500 mt-1">Subsidy: {{ $stats['company_match_pct'] }}% Matching Subsidy</p>
            </div>

            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Claims Disbursed</p>
                <p class="text-2xl font-black font-outfit text-rose-600 mt-1">PHP {{ number_format($stats['total_disbursed'], 2) }}</p>
                <p class="text-[11px] text-gray-500 mt-1">{{ $stats['approved_count'] }} Approved Accident Claims</p>
            </div>

            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Net Liquid Fund Balance</p>
                <p class="text-2xl font-black font-outfit text-indigo-600 mt-1">PHP {{ number_format($stats['net_liquid_balance'], 2) }}</p>
                <p class="text-[11px] text-gray-500 mt-1">Pending Liabilities: PHP {{ number_format($stats['pending_pipeline'], 2) }}</p>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 1: CLAIMS QUEUE & 3-STAGE WORKFLOW STEPPER -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'claims'" x-transition class="space-y-6">

            <!-- Filter Controls -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-4 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <form action="{{ route('driver-insurance.index') }}" method="GET" class="flex flex-wrap items-center gap-3 flex-1">
                    <div class="relative flex-1 min-w-[220px]">
                        <input type="text" name="search" value="{{ $search }}" placeholder="Search incident reference, driver name, vehicle plate, or description..." 
                               class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl pl-9 pr-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-gray-900">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    <select name="status" class="text-xs bg-gray-50 border border-gray-200 rounded-xl px-3 py-2.5 text-gray-800 font-bold focus:outline-none focus:border-gray-900">
                        <option value="all">All Workflow Stages</option>
                        <option value="pending_hr" {{ $status === 'pending_hr' ? 'selected' : '' }}>Stage 1: Awaiting HR Review</option>
                        <option value="pending_admin" {{ $status === 'pending_admin' ? 'selected' : '' }}>Stage 2: Awaiting Admin Approval</option>
                        <option value="pending_finance" {{ $status === 'pending_finance' ? 'selected' : '' }}>Stage 3: Awaiting Finance Release</option>
                        <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Approved & Disbursed</option>
                        <option value="returned" {{ $status === 'returned' ? 'selected' : '' }}>Returned for Revision</option>
                    </select>

                    <button type="submit" class="bg-gray-900 hover:bg-black text-white text-xs font-black px-4 py-2.5 rounded-xl transition-all shadow-sm">
                        Filter Queue
                    </button>
                </form>
            </div>

            <!-- Claims Table -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/80 border-b border-gray-100 text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                <th class="py-3.5 px-4">Claim Ref / Date</th>
                                <th class="py-3.5 px-4">Driver / Plate</th>
                                <th class="py-3.5 px-4">Incident Type & Description</th>
                                <th class="py-3.5 px-4">Claim Amount</th>
                                <th class="py-3.5 px-4">3-Stage Workflow Status</th>
                                <th class="py-3.5 px-4 text-center">Evidence</th>
                                <th class="py-3.5 px-4 text-right">Governance Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-xs">
                            @forelse($accidentClaims as $claim)
                                <tr class="hover:bg-gray-50/60 transition-colors">
                                    <!-- Ref & Date -->
                                    <td class="py-3.5 px-4 font-mono">
                                        <div class="font-bold text-gray-900">{{ $claim->incident_number }}</div>
                                        <div class="text-[10px] text-gray-400">{{ $claim->incident_date ? $claim->incident_date->format('M j, Y') : $claim->created_at->format('M j, Y') }}</div>
                                    </td>

                                    <!-- Driver / Plate -->
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-gray-900">{{ $claim->employee?->first_name }} {{ $claim->employee?->last_name }}</div>
                                        <div class="text-[10px] text-gray-500 font-mono">
                                            {{ $claim->employee?->employee_code }} 
                                            @if($claim->vehicle_plate_number)
                                                • Plate: <span class="font-bold text-gray-700">{{ $claim->vehicle_plate_number }}</span>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Type & Description -->
                                    <td class="py-3.5 px-4">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-gray-100 text-gray-800">
                                            {{ $claim->incident_type }}
                                        </span>
                                        <p class="text-[11px] text-gray-600 mt-1 line-clamp-1">{{ $claim->description }}</p>
                                        @if($claim->diagnosis)
                                            <div class="text-[10px] text-blue-700 font-semibold mt-0.5">Dx: {{ $claim->diagnosis }}</div>
                                        @endif
                                    </td>

                                    <!-- Amounts -->
                                    <td class="py-3.5 px-4 font-outfit">
                                        <div class="font-extrabold text-gray-900 text-sm">PHP {{ number_format((float)$claim->bill_amount, 2) }}</div>
                                        @if($claim->approved_amount && $claim->workflow_status === 'approved')
                                            <div class="text-[10px] text-emerald-700 font-bold">Paid: PHP {{ number_format((float)$claim->approved_amount, 2) }}</div>
                                        @elseif($claim->approved_amount)
                                            <div class="text-[10px] text-blue-700 font-bold">Approved: PHP {{ number_format((float)$claim->approved_amount, 2) }}</div>
                                        @endif
                                    </td>

                                    <!-- 3-Stage Workflow Visual Stepper -->
                                    <td class="py-3.5 px-4">
                                        <div class="space-y-1.5 min-w-[160px]">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black border {{ $claim->statusBadgeClasses() }}">
                                                {{ $claim->statusLabel() }}
                                            </span>

                                            <!-- Visual 3-Stage Progress Indicator -->
                                            <div class="flex items-center gap-1">
                                                <!-- Step 1: HR -->
                                                <div class="flex-1 h-1.5 rounded-full {{ $claim->hr_status === 'approved' ? 'bg-emerald-500' : ($claim->workflow_status === 'pending_hr' ? 'bg-amber-500 animate-pulse' : 'bg-gray-200') }}" title="Step 1: HR Review"></div>
                                                <!-- Step 2: Admin -->
                                                <div class="flex-1 h-1.5 rounded-full {{ $claim->admin_status === 'approved' ? 'bg-emerald-500' : ($claim->workflow_status === 'pending_admin' ? 'bg-blue-500 animate-pulse' : 'bg-gray-200') }}" title="Step 2: Admin Approval"></div>
                                                <!-- Step 3: Finance -->
                                                <div class="flex-1 h-1.5 rounded-full {{ $claim->finance_status === 'approved' ? 'bg-emerald-500' : ($claim->workflow_status === 'pending_finance' ? 'bg-purple-500 animate-pulse' : 'bg-gray-200') }}" title="Step 3: Finance Disbursement"></div>
                                            </div>

                                            <div class="text-[9px] text-gray-400 flex items-center justify-between font-mono">
                                                <span class="{{ $claim->hr_status === 'approved' ? 'text-emerald-600 font-bold' : '' }}">HR</span>
                                                <span class="{{ $claim->admin_status === 'approved' ? 'text-emerald-600 font-bold' : '' }}">Admin</span>
                                                <span class="{{ $claim->finance_status === 'approved' ? 'text-emerald-600 font-bold' : '' }}">Finance</span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Evidence & Photos -->
                                    <td class="py-3.5 px-4 text-center">
                                        <button @click="openEvidence({{ json_encode($claim) }})" 
                                                class="px-2.5 py-1 text-[10px] font-bold rounded-lg border border-gray-200 hover:bg-gray-100 text-gray-700 transition-all flex items-center gap-1 mx-auto">
                                            <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                            </svg>
                                            Evidence
                                        </button>
                                    </td>

                                    <!-- Governance Actions -->
                                    <td class="py-3.5 px-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            
                                            <!-- Step 1: HR Approve -->
                                            @if($claim->workflow_status === 'pending_hr')
                                                <button @click="openAction('hr', '{{ route('driver-insurance.claim.approve-hr', $claim) }}', '{{ $claim->incident_number }}', '{{ $claim->bill_amount }}', 'Verified active trip logs and driver injury receipts.')" 
                                                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-[10px] px-2.5 py-1 rounded-lg transition-all shadow-2xs">
                                                    HR Validate
                                                </button>
                                                <button @click="openReturn({{ json_encode($claim) }})" class="bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-[10px] px-2 py-1 rounded-lg border border-rose-200 transition-all">
                                                    Return
                                                </button>
                                            @endif

                                            <!-- Step 2: Admin Approve -->
                                            @if($claim->workflow_status === 'pending_admin')
                                                <button @click="openAction('admin', '{{ route('driver-insurance.claim.approve-admin', $claim) }}', '{{ $claim->incident_number }}', '{{ $claim->approved_amount ?: $claim->bill_amount }}', 'Vehicle damage report and incident assessment cleared.')" 
                                                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-[10px] px-2.5 py-1 rounded-lg transition-all shadow-2xs">
                                                    Admin Clear
                                                </button>
                                                <button @click="openReturn({{ json_encode($claim) }})" class="bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-[10px] px-2 py-1 rounded-lg border border-rose-200 transition-all">
                                                    Return
                                                </button>
                                            @endif

                                            <!-- Step 3: Finance Release -->
                                            @if($claim->workflow_status === 'pending_finance')
                                                <button @click="openAction('finance', '{{ route('driver-insurance.claim.approve-finance', $claim) }}', '{{ $claim->incident_number }}', '{{ $claim->approved_amount ?: $claim->bill_amount }}', 'Disbursement approved and released from Driver Insurance Pool.')" 
                                                        class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[10px] px-2.5 py-1 rounded-lg transition-all shadow-2xs">
                                                    Release Payout
                                                </button>
                                                <button @click="openReturn({{ json_encode($claim) }})" class="bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-[10px] px-2 py-1 rounded-lg border border-rose-200 transition-all">
                                                    Return
                                                </button>
                                            @endif

                                            <!-- Approved Badge -->
                                            @if($claim->workflow_status === 'approved')
                                                <span class="text-emerald-700 font-black text-[10px] bg-emerald-50 px-2 py-1 rounded-md border border-emerald-200">
                                                    Disbursed
                                                </span>
                                            @endif

                                            <!-- Returned Badge -->
                                            @if($claim->workflow_status === 'returned')
                                                <button @click="openAction('hr', '{{ route('driver-insurance.claim.approve-hr', $claim) }}', '{{ $claim->incident_number }}', '{{ $claim->bill_amount }}', 'Re-evaluated following driver document submission.')" 
                                                        class="bg-amber-600 hover:bg-amber-700 text-white font-bold text-[10px] px-2.5 py-1 rounded-lg transition-all shadow-2xs">
                                                    Re-evaluate
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-12 text-gray-400 text-xs">No driver accident claims found in queue.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($accidentClaims->hasPages())
                    <div class="p-4 border-t border-gray-100">
                        {{ $accidentClaims->links() }}
                    </div>
                @endif
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 2: DRIVER POOL FUND ACCOUNTING & LEDGER -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'ledger'" x-transition class="space-y-6">
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-black font-outfit text-gray-900">Driver Accident Insurance Pool Fund Accounting Ledger</h2>
                        <p class="text-[11px] text-gray-500 mt-0.5">Real-time audit trail of driver 3% deductions, corporate subsidies, claim disbursements, and running liquid balance.</p>
                    </div>
                    <a href="{{ route('driver-insurance.export-ledger') }}" class="bg-gray-900 hover:bg-black text-white text-xs font-bold px-3.5 py-1.5 rounded-xl transition-all shadow-2xs">
                        Download CSV
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-gray-50/80 border-b border-gray-100 text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                <th class="py-3 px-4">Transaction Date</th>
                                <th class="py-3 px-4">Reference Code</th>
                                <th class="py-3 px-4">Entry Type</th>
                                <th class="py-3 px-4">Driver / Entity</th>
                                <th class="py-3 px-4">Description</th>
                                <th class="py-3 px-4 text-right">Amount (PHP)</th>
                                <th class="py-3 px-4 text-right">Running Fund Balance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($ledger as $item)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="py-3 px-4 font-mono text-[11px] text-gray-600">
                                        {{ $item->created_at->format('M j, Y H:i') }}
                                    </td>
                                    <td class="py-3 px-4 font-mono font-bold text-gray-900">
                                        {{ $item->reference_code }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase
                                            @if($item->entry_type === 'driver_contribution') bg-emerald-50 text-emerald-700
                                            @elseif($item->entry_type === 'company_subsidy_match') bg-blue-50 text-blue-700
                                            @elseif($item->entry_type === 'claim_disbursement') bg-rose-50 text-rose-700
                                            @else bg-gray-100 text-gray-700 @endif">
                                            {{ $item->entry_type_label }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        @if($item->employee)
                                            <div class="font-bold text-gray-900">{{ $item->employee->first_name }} {{ $item->employee->last_name }}</div>
                                            <div class="text-[10px] text-gray-400 font-mono">{{ $item->employee->employee_code }}</div>
                                        @else
                                            <span class="text-gray-500 font-semibold">TripWise Corporate</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-gray-600 font-medium">
                                        {{ $item->description }}
                                    </td>
                                    <td class="py-3 px-4 text-right font-black font-outfit
                                        {{ $item->amount < 0 ? 'text-rose-600' : 'text-emerald-700' }}">
                                        {{ $item->amount < 0 ? '-' : '+' }}PHP {{ number_format(abs((float)$item->amount), 2) }}
                                    </td>
                                    <td class="py-3 px-4 text-right font-black font-outfit text-indigo-700">
                                        PHP {{ number_format((float)$item->running_balance, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-10 text-gray-400 text-xs">
                                        No ledger transactions recorded yet. (Transactions are created automatically during payroll runs and claim releases).
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($ledger->hasPages())
                    <div class="p-4 border-t border-gray-100">
                        {{ $ledger->links() }}
                    </div>
                @endif
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: FILE DRIVER ACCIDENT CLAIM (Multipart Uploads) -->
        <!-- ========================================================================= -->
        <div x-show="showClaimModal" x-cloak class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div @click.away="showClaimModal = false" class="bg-white rounded-2xl shadow-2xl w-full max-w-xl p-6 space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-base font-black font-outfit text-gray-900">File Driver Accident Assistance Claim</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Submit medical bills, incident blotter, and photos for 3-stage review</p>
                    </div>
                    <button @click="showClaimModal = false" type="button" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
                </div>

                <form action="{{ route('driver-insurance.file-claim') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Select Driver *</label>
                        <select name="employee_id" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-gray-900">
                            <option value="">-- Choose Driver --</option>
                            @foreach($employees as $driver)
                                <option value="{{ $driver->id }}">{{ $driver->first_name }} {{ $driver->last_name }} ({{ $driver->employee_code }} - {{ $driver->position }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Incident Category *</label>
                            <select name="incident_type" required class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-800 font-bold focus:outline-none focus:border-gray-900">
                                <option value="Work Injury">Work Injury (On Duty)</option>
                                <option value="Accident - Hospitalization">Accident - Hospitalization</option>
                                <option value="Accident - Medical Bills">Accident - Medical Bills & ORs</option>
                                <option value="Emergency Assistance">Emergency Road Assistance</option>
                                <option value="Death Benefit">Accidental Death & Dismemberment (AD&D)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Incident Date *</label>
                            <input type="date" name="incident_date" value="{{ now()->format('Y-m-d') }}" required max="{{ now()->format('Y-m-d') }}" class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-gray-900">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Vehicle Plate Number</label>
                            <input type="text" name="vehicle_plate_number" placeholder="e.g. NBD-8821" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 font-bold focus:outline-none focus:border-gray-900">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Trip / Booking ID</label>
                            <input type="text" name="trip_id" placeholder="e.g. TRIP-2026-99124" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 font-bold focus:outline-none focus:border-gray-900">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Claim Amount (PHP) *</label>
                            <input type="number" step="0.01" name="bill_amount" required placeholder="0.00" class="w-full text-xs font-black bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:border-gray-900">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Medical Diagnosis / Injury</label>
                            <input type="text" name="diagnosis" placeholder="e.g. Right arm fracture, laceration" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 font-bold focus:outline-none focus:border-gray-900">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Incident Description & Location *</label>
                        <textarea name="description" rows="2" required placeholder="Detailed description of collision or emergency circumstances..." class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl p-3 text-gray-800 focus:outline-none focus:border-gray-900"></textarea>
                    </div>

                    <!-- Documentary Evidence Uploads -->
                    <div class="p-3 bg-gray-50 rounded-xl border border-gray-200 space-y-3">
                        <p class="text-[11px] font-black uppercase text-gray-700">Documentary Evidence Attachments (PDF / JPG / PNG)</p>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 mb-1">Police Report / Blotter</label>
                                <input type="file" name="police_report" accept=".pdf,.jpg,.jpeg,.png" class="text-[10px] w-full text-gray-600">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 mb-1">Medical Receipts / ORs</label>
                                <input type="file" name="medical_receipt" accept=".pdf,.jpg,.jpeg,.png" class="text-[10px] w-full text-gray-600">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 mb-1">Vehicle Damage Photo</label>
                                <input type="file" name="incident_photo" accept=".pdf,.jpg,.jpeg,.png" class="text-[10px] w-full text-gray-600">
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                        <button @click="showClaimModal = false" type="button" class="text-xs font-bold text-gray-500 px-4 py-2">Cancel</button>
                        <button type="submit" class="bg-gray-900 hover:bg-black text-white font-black text-xs px-5 py-2.5 rounded-xl transition-all shadow-sm">Submit Claim</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: 3-STAGE WORKFLOW ACTION APPROVAL -->
        <!-- ========================================================================= -->
        <div x-show="showActionModal" x-cloak class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div @click.away="showActionModal = false" class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-base font-black font-outfit text-gray-900" x-text="actionType === 'hr' ? 'HR Stage 1 Validation' : (actionType === 'admin' ? 'Admin Stage 2 Clearance' : 'Finance Stage 3 Disbursement')"></h2>
                        <p class="text-xs text-gray-400 mt-0.5" x-text="'Claim: ' + actionIncidentRef"></p>
                    </div>
                    <button @click="showActionModal = false" type="button" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
                </div>

                <form :action="actionUrl" method="POST" class="space-y-4">
                    @csrf

                    <template x-if="actionType === 'hr'">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Approved Claim Amount (PHP) *</label>
                            <input type="number" step="0.01" name="approved_amount" :value="actionAmount" required class="w-full text-xs font-black bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-900 focus:outline-none focus:border-gray-900">
                        </div>
                    </template>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Review Remarks</label>
                        <textarea name="remarks" rows="3" x-model="actionRemarks" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl p-3 text-gray-800 focus:outline-none focus:border-gray-900"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                        <button @click="showActionModal = false" type="button" class="text-xs font-bold text-gray-500 px-4 py-2">Cancel</button>
                        <button type="submit" class="bg-gray-900 hover:bg-black text-white font-black text-xs px-5 py-2 rounded-xl transition-all shadow-sm">Confirm Approval</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: RETURN CLAIM FOR REVISION -->
        <!-- ========================================================================= -->
        <div x-show="showReturnModal" x-cloak class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div @click.away="showReturnModal = false" class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-base font-black font-outfit text-rose-700">Return Claim for Revision</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Send back with documented explanation</p>
                    </div>
                    <button @click="showReturnModal = false" type="button" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
                </div>

                <template x-if="selectedClaim">
                    <form :action="'/driver-insurance/claim/' + selectedClaim.id + '/return'" method="POST" class="space-y-4">
                        @csrf

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Return Reason / Required Documents *</label>
                            <textarea name="remarks" required rows="3" placeholder="e.g. Official medical receipts missing doctor signature or police blotter unclear..." class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl p-3 text-gray-800 focus:outline-none focus:border-gray-900"></textarea>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                            <button @click="showReturnModal = false" type="button" class="text-xs font-bold text-gray-500 px-4 py-2">Cancel</button>
                            <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white font-black text-xs px-5 py-2 rounded-xl">Confirm Return</button>
                        </div>
                    </form>
                </template>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: EVIDENCE & DOCUMENT INSPECTION DRAWER -->
        <!-- ========================================================================= -->
        <div x-show="showEvidenceModal" x-cloak class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div @click.away="showEvidenceModal = false" class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-base font-black font-outfit text-gray-900">Documentary Evidence Files</h2>
                        <p class="text-xs text-gray-400 mt-0.5" x-text="selectedClaim ? ('Incident ' + selectedClaim.incident_number) : ''"></p>
                    </div>
                    <button @click="showEvidenceModal = false" type="button" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
                </div>

                <template x-if="selectedClaim">
                    <div class="space-y-3 text-xs">
                        <div class="p-3 bg-gray-50 rounded-xl flex items-center justify-between">
                            <div>
                                <p class="font-bold text-gray-800">Police Report / Blotter</p>
                                <p class="text-[10px] text-gray-400" x-text="selectedClaim.police_report_path ? 'File attached' : 'No document uploaded'"></p>
                            </div>
                            <template x-if="selectedClaim.police_report_path">
                                <a :href="'/storage/' + selectedClaim.police_report_path" target="_blank" class="px-3 py-1 bg-gray-900 text-white rounded-lg font-bold text-[10px]">View</a>
                            </template>
                        </div>

                        <div class="p-3 bg-gray-50 rounded-xl flex items-center justify-between">
                            <div>
                                <p class="font-bold text-gray-800">Hospital Bills & Medical ORs</p>
                                <p class="text-[10px] text-gray-400" x-text="selectedClaim.medical_receipt_path ? 'File attached' : 'No document uploaded'"></p>
                            </div>
                            <template x-if="selectedClaim.medical_receipt_path">
                                <a :href="'/storage/' + selectedClaim.medical_receipt_path" target="_blank" class="px-3 py-1 bg-gray-900 text-white rounded-lg font-bold text-[10px]">View</a>
                            </template>
                        </div>

                        <div class="p-3 bg-gray-50 rounded-xl flex items-center justify-between">
                            <div>
                                <p class="font-bold text-gray-800">Vehicle Incident / Damage Photos</p>
                                <p class="text-[10px] text-gray-400" x-text="selectedClaim.incident_photo_path ? 'File attached' : 'No document uploaded'"></p>
                            </div>
                            <template x-if="selectedClaim.incident_photo_path">
                                <a :href="'/storage/' + selectedClaim.incident_photo_path" target="_blank" class="px-3 py-1 bg-gray-900 text-white rounded-lg font-bold text-[10px]">View</a>
                            </template>
                        </div>
                    </div>
                </template>

                <div class="pt-2 text-center">
                    <button @click="showEvidenceModal = false" type="button" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold text-xs py-2 rounded-xl">Close</button>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: UPDATE DRIVER POOL POLICY & MATCHING RULES -->
        <!-- ========================================================================= -->
        <div x-show="showPolicyModal" x-cloak class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div @click.away="showPolicyModal = false" class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-base font-black font-outfit text-gray-900">Driver Insurance Pool Configuration</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Contribution rate and corporate matching subsidy</p>
                    </div>
                    <button @click="showPolicyModal = false" type="button" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
                </div>

                <form action="{{ route('driver-insurance.update-contribution-rate') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Driver Payroll Deduction Rate (%) *</label>
                        <input type="number" step="0.1" min="0" max="20" name="contribution_rate" value="{{ $stats['contribution_rate_pct'] }}" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                        <p class="text-[10px] text-gray-400 mt-1">Deducted from driver trip earnings (default: 3.0%)</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Company Matching Subsidy (%) *</label>
                        <input type="number" step="0.1" min="0" max="200" name="company_match_pct" value="{{ $stats['company_match_pct'] }}" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                        <p class="text-[10px] text-gray-400 mt-1">Corporate subsidy matched to driver deductions (default: 50.0%)</p>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                        <button @click="showPolicyModal = false" type="button" class="text-xs font-bold text-gray-500 px-4 py-2">Cancel</button>
                        <button type="submit" class="bg-gray-900 hover:bg-black text-white font-black text-xs px-5 py-2 rounded-xl">Save Settings</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: INDIVIDUAL DRIVER CONTRIBUTION & CLAIM DRILLDOWN -->
        <!-- ========================================================================= -->
        <div x-show="showDriverHistoryModal" x-cloak class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div @click.away="showDriverHistoryModal = false" class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl p-6 space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-base font-black font-outfit text-gray-900">Driver Insurance Pool Timeline & Coverage</h2>
                        <p class="text-xs text-gray-400 mt-0.5" x-text="driverHistoryData ? (driverHistoryData.employee.name + ' (' + driverHistoryData.employee.code + ')') : 'Loading driver timeline...'"></p>
                    </div>
                    <button @click="showDriverHistoryModal = false" type="button" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
                </div>

                <template x-if="driverHistoryLoading">
                    <div class="py-12 text-center text-xs text-gray-400 flex flex-col items-center justify-center gap-2">
                        <svg class="w-6 h-6 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Loading driver ledger history...
                    </div>
                </template>

                <template x-if="!driverHistoryLoading && driverHistoryData">
                    <div class="space-y-4">
                        <!-- Stat Summary Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="bg-gray-50 rounded-xl p-3 border border-gray-100">
                                <span class="text-[10px] font-bold text-gray-400 uppercase block">Driver Paid Contributions</span>
                                <span class="text-base font-black font-outfit text-gray-900" x-text="'PHP ' + Number(driverHistoryData.total_contributed).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                            </div>
                            <div class="bg-amber-50/70 rounded-xl p-3 border border-amber-100">
                                <span class="text-[10px] font-bold text-amber-700 uppercase block">Company Match Subsidy</span>
                                <span class="text-base font-black font-outfit text-amber-900" x-text="'PHP ' + Number(driverHistoryData.company_match_total).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                            </div>
                            <div class="bg-emerald-50 rounded-xl p-3 border border-emerald-100">
                                <span class="text-[10px] font-bold text-emerald-700 uppercase block">Total Pool Credit</span>
                                <span class="text-base font-black font-outfit text-emerald-900" x-text="'PHP ' + Number(driverHistoryData.total_pool_credit).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                            </div>
                        </div>

                        <!-- Contribution Transactions List -->
                        <div>
                            <h4 class="text-xs font-black uppercase text-gray-700 tracking-wider mb-2">Payroll Contribution History</h4>
                            <div class="border border-gray-100 rounded-xl overflow-hidden max-h-48 overflow-y-auto">
                                <table class="w-full text-left border-collapse text-xs">
                                    <thead class="bg-gray-50 text-[10px] text-gray-400 font-bold uppercase sticky top-0">
                                        <tr>
                                            <th class="py-2 px-3">Date</th>
                                            <th class="py-2 px-3">Reference</th>
                                            <th class="py-2 px-3">Description</th>
                                            <th class="py-2 px-3 text-right">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <template x-for="item in driverHistoryData.contributions" :key="item.ref">
                                            <tr class="hover:bg-gray-50/60 font-mono">
                                                <td class="py-2 px-3" x-text="item.date"></td>
                                                <td class="py-2 px-3 font-bold text-gray-800" x-text="item.ref"></td>
                                                <td class="py-2 px-3 text-gray-500 font-sans" x-text="item.desc"></td>
                                                <td class="py-2 px-3 text-right font-black text-gray-900" x-text="'₱' + item.amount"></td>
                                            </tr>
                                        </template>
                                        <template x-if="driverHistoryData.contributions.length === 0">
                                            <tr>
                                                <td colspan="4" class="py-4 text-center text-gray-400 text-xs">No payroll contributions recorded yet.</td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Claims History List -->
                        <div>
                            <h4 class="text-xs font-black uppercase text-gray-700 tracking-wider mb-2">Accident Claims Filed</h4>
                            <div class="border border-gray-100 rounded-xl overflow-hidden max-h-40 overflow-y-auto">
                                <table class="w-full text-left border-collapse text-xs">
                                    <thead class="bg-gray-50 text-[10px] text-gray-400 font-bold uppercase sticky top-0">
                                        <tr>
                                            <th class="py-2 px-3">Incident #</th>
                                            <th class="py-2 px-3">Date</th>
                                            <th class="py-2 px-3">Status</th>
                                            <th class="py-2 px-3 text-right">Billed</th>
                                            <th class="py-2 px-3 text-right">Disbursed</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <template x-for="cl in driverHistoryData.claims" :key="cl.incident_number">
                                            <tr class="hover:bg-gray-50/60 font-mono">
                                                <td class="py-2 px-3 font-bold text-gray-900" x-text="cl.incident_number"></td>
                                                <td class="py-2 px-3" x-text="cl.incident_date"></td>
                                                <td class="py-2 px-3 font-sans">
                                                    <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase"
                                                          :class="cl.status === 'approved' ? 'bg-emerald-100 text-emerald-800' : (cl.status === 'returned' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800')"
                                                          x-text="cl.status.replace('_', ' ')"></span>
                                                </td>
                                                <td class="py-2 px-3 text-right" x-text="'₱' + cl.bill_amount"></td>
                                                <td class="py-2 px-3 text-right font-bold text-emerald-700" x-text="'₱' + cl.approved_amount"></td>
                                            </tr>
                                        </template>
                                        <template x-if="driverHistoryData.claims.length === 0">
                                            <tr>
                                                <td colspan="5" class="py-4 text-center text-gray-400 text-xs">No accident claims filed.</td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="pt-2 flex justify-end">
                            <button @click="showDriverHistoryModal = false" type="button" class="bg-gray-900 text-white font-bold text-xs px-4 py-2 rounded-xl">Close</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

    </div>

@endsection
