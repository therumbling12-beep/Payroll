@extends('layouts.app')

@php
    $pageTitle = 'Maternity Leave Request';
    $currentPage = 'claims.maternity-leave';
@endphp

@section('content')

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-black font-outfit text-gray-900 tracking-tight">Maternity Leave & Benefit Claims</h1>
            <p class="text-xs text-gray-500 mt-1">105-day statutory maternity allowance with SSS advance and mandatory company salary differential under RA 11210.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('claims.export', ['type' => 'maternity']) }}" 
               class="text-xs font-black text-gray-800 hover:text-black bg-white border border-gray-200 px-3.5 py-1.5 rounded-xl shadow-2xs hover:bg-gray-50 flex items-center gap-1.5 transition-all">
                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export CSV
            </a>
            <span class="flex items-center gap-2 text-xs font-bold text-gray-800 bg-white border border-gray-200 px-3.5 py-1.5 rounded-full shadow-2xs">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                RA 11210 Expanded Engine Active
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
        showDrawer: false, 
        showSssStatusModal: false,
        selectedClaim: null,
        sssClaimId: '',
        sssClaimRef: '',
        sssCurrentStatus: 'advanced_to_employee',
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
        openSssModal(claim) {
            this.selectedClaim = claim;
            this.sssClaimId = claim.id;
            this.sssClaimRef = claim.receipt_number;
            this.sssCurrentStatus = claim.sss_reimbursement_status || 'advanced_to_employee';
            this.showSssStatusModal = true;
        }
    }" class="space-y-6 pb-12">

        <!-- Top Navigation Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-200 pb-3">
            <div class="flex flex-wrap items-center gap-1.5 bg-gray-100 p-1 rounded-2xl">
                
                <!-- Filter: All -->
                <a href="{{ route('claims.maternity-leave') }}" 
                   class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2 {{ empty($sssStatus) ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold' }}">
                    All Maternity Records
                </a>

                <!-- Filter: Advanced to Employee -->
                <a href="{{ route('claims.maternity-leave', ['sss_status' => 'advanced_to_employee']) }}" 
                   class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2 {{ $sssStatus === 'advanced_to_employee' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold' }}">
                    Advanced to Employee
                </a>

                <!-- Filter: Submitted to SSS -->
                <a href="{{ route('claims.maternity-leave', ['sss_status' => 'submitted_to_sss']) }}" 
                   class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2 {{ $sssStatus === 'submitted_to_sss' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold' }}">
                    Submitted to SSS
                </a>

                <!-- Filter: Reimbursed by SSS -->
                <a href="{{ route('claims.maternity-leave', ['sss_status' => 'reimbursed_by_sss']) }}" 
                   class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2 {{ $sssStatus === 'reimbursed_by_sss' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold' }}">
                    Reimbursed by SSS
                    <span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-emerald-100 text-emerald-800">{{ $stats['reimbursed_count'] }}</span>
                </a>
            </div>

            <!-- Review Queue Badge -->
            <div class="flex items-center gap-2">
                <span class="px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-bold rounded-xl border border-gray-200 shadow-2xs flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Maternity & Medical Review Queue
                </span>
            </div>
        </div>

        <!-- 4 Summary Stat Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm space-y-1">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-extrabold text-gray-400 uppercase tracking-wider">Total Disbursed</span>
                    <div class="w-8 h-8 rounded-xl bg-gray-100 text-gray-700 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-2xl font-black font-outfit text-gray-900">PHP {{ number_format($stats['total_disbursed'], 2) }}</p>
                <p class="text-xs text-gray-500 font-medium">{{ $stats['claims_count'] }} Total Beneficiaries</p>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm space-y-1">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-extrabold text-gray-400 uppercase tracking-wider">SSS Advance Share</span>
                    <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-2xl font-black font-outfit text-indigo-600">PHP {{ number_format($stats['sss_share_total'], 2) }}</p>
                <p class="text-xs text-gray-500 font-medium">100% Advanced to Employees</p>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm space-y-1">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-extrabold text-gray-400 uppercase tracking-wider">Salary Differential</span>
                    <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                </div>
                <p class="text-2xl font-black font-outfit text-emerald-600">PHP {{ number_format($stats['company_differential_total'], 2) }}</p>
                <p class="text-xs text-gray-500 font-medium">Mandated Top-up (RA 11210)</p>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm space-y-1">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-extrabold text-gray-400 uppercase tracking-wider">Pending SSS Recovery</span>
                    <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-2xl font-black font-outfit text-amber-600">PHP {{ number_format($stats['pending_sss_reimbursement_total'], 2) }}</p>
                <p class="text-xs text-gray-500 font-medium">Awaiting SSS Recovery Settlement</p>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex flex-1 items-center gap-3 flex-wrap">
                <form action="{{ route('claims.maternity-leave') }}" method="GET" class="flex flex-1 items-center gap-3 max-w-md">
                    @if($sssStatus)
                        <input type="hidden" name="sss_status" value="{{ $sssStatus }}">
                    @endif
                    <div class="relative flex-1">
                        <input type="text" name="search" value="{{ $search }}" placeholder="Search employee, reference, or OB-GYN..." 
                               class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl pl-9 pr-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-gray-900">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <button type="submit" class="bg-gray-900 hover:bg-black text-white text-xs font-black px-4 py-2.5 rounded-xl transition-all shadow-sm cursor-pointer">
                        Filter
                    </button>
                </form>

                <!-- Filter: Overdue / Aging -->
                <a href="{{ route('claims.maternity-leave', ['aging' => 'overdue', 'sss_status' => $sssStatus]) }}" 
                   class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2 {{ ($aging ?? '') === 'overdue' ? 'bg-rose-600 text-white font-black shadow-sm' : 'text-rose-600 hover:bg-rose-50 font-bold border border-rose-200' }}">
                    Needs Attention (> 3 Days)
                    @if(!empty($stats['overdue_count']) && $stats['overdue_count'] > 0)
                        <span class="px-1.5 py-0.5 text-[9px] font-black rounded-full {{ ($aging ?? '') === 'overdue' ? 'bg-white text-rose-700' : 'bg-rose-100 text-rose-800' }} animate-pulse">
                            {{ $stats['overdue_count'] }}
                        </span>
                    @endif
                </a>
            </div>

            <div class="text-xs text-gray-500 font-bold">
                Showing {{ $claims->count() }} of {{ $claims->total() }} records
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
                            <th class="py-3.5 px-4">Claim Ref & Filing Date</th>
                            <th class="py-3.5 px-4">Female Beneficiary</th>
                            <th class="py-3.5 px-4">Leave Duration & Type</th>
                            <th class="py-3.5 px-4">SSS Share</th>
                            <th class="py-3.5 px-4">Company Differential</th>
                            <th class="py-3.5 px-4">Total Advance</th>
                            <th class="py-3.5 px-4">SSS Recovery Status</th>
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
                                <td class="py-3.5 px-4 font-bold text-gray-900 font-outfit">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-full bg-gray-900 text-white flex items-center justify-center font-bold text-xs font-outfit shadow-2xs">
                                            {{ substr($claim->employee?->first_name ?? 'E', 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="text-gray-900">{{ $claim->employee?->first_name }} {{ $claim->employee?->last_name }}</div>
                                            <span class="text-[10px] text-gray-400 font-mono font-normal">{{ $claim->employee?->employee_code }} • {{ $claim->employee?->position }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-pink-50 text-pink-800 border border-pink-200">
                                        {{ $claim->maternity_leave_days }} Days ({{ str_replace('_', ' ', $claim->maternity_type ?? '105-Day Live Birth') }})
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-mono font-bold text-indigo-700">
                                    PHP {{ number_format((float)$claim->sss_maternity_share, 2) }}
                                </td>
                                <td class="py-3.5 px-4 font-mono font-bold text-emerald-700">
                                    PHP {{ number_format((float)$claim->company_maternity_topup, 2) }}
                                </td>
                                <td class="py-3.5 px-4 font-extrabold text-gray-900 font-mono text-sm">
                                    PHP {{ number_format((float)$claim->amount, 2) }}
                                </td>
                                <td class="py-3.5 px-4">
                                    @if($claim->sss_reimbursement_status === 'reimbursed_by_sss')
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-800 border border-emerald-200 inline-block">
                                            Reimbursed by SSS
                                        </span>
                                    @elseif($claim->sss_reimbursement_status === 'submitted_to_sss')
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-indigo-50 text-indigo-800 border border-indigo-200 inline-block">
                                            Submitted to SSS
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-50 text-amber-800 border border-amber-200 inline-block">
                                            Advanced to Employee
                                        </span>
                                    @endif
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
                                        <button type="button" @click="openTimeline({{ json_encode($claim) }})" class="p-1.5 text-gray-500 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors cursor-pointer" title="View Breakdown">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </button>

                                        <button type="button" @click="openSssModal({{ json_encode($claim) }})" class="bg-white hover:bg-gray-100 text-gray-800 font-bold text-[11px] px-3 py-1.5 rounded-xl transition-all border border-gray-200 shadow-2xs cursor-pointer">
                                            SSS Status
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-8 text-center text-gray-400 text-xs">No maternity leave claims found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                     <div class="mt-4">
                {{ $claims->links() }}
            </div>
        </div>

        <!-- Update SSS Recovery Status Modal -->
        <div x-show="showSssStatusModal" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 backdrop-blur-xs">
            <div @click.away="showSssStatusModal = false" class="bg-white rounded-2xl border border-gray-100 p-6 max-w-md w-full shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-base font-black font-outfit text-gray-900">Update SSS Reimbursement Status</h2>
                        <p class="text-xs text-gray-400 mt-0.5" x-text="'Claim Reference: ' + sssClaimRef"></p>
                    </div>
                    <button @click="showSssStatusModal = false" class="text-gray-400 hover:text-gray-600 text-lg cursor-pointer">&times;</button>
                </div>

                <form :action="'{{ route('claims.maternity.sss-status', ['claim' => '__ID__']) }}'.replace('__ID__', sssClaimId)" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">SSS Recovery Milestone</label>
                        <select name="sss_reimbursement_status" x-model="sssCurrentStatus" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                            <option value="advanced_to_employee">1. Advanced to Employee</option>
                            <option value="submitted_to_sss">2. Submitted to SSS for Reimbursement</option>
                            <option value="reimbursed_by_sss">3. Reimbursed by SSS (Recovered)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">SSS Transaction / Reference No.</label>
                        <input type="text" name="sss_reference_number" placeholder="e.g. SSS-MAT-2026-0941" class="w-full text-xs font-mono font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Reimbursement Settlement Date</label>
                        <input type="date" name="sss_reimbursement_date" class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                        <button type="button" @click="showSssStatusModal = false" class="text-xs font-bold text-gray-500 px-4 py-2 hover:text-gray-700 cursor-pointer">Cancel</button>
                        <button type="submit" class="bg-gray-900 hover:bg-black text-white font-black text-xs px-5 py-2 rounded-xl transition-all shadow-sm cursor-pointer">Save SSS Status</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Slide-out Timeline Drawer -->
        <div x-show="showDrawer" x-cloak class="fixed inset-0 z-50 overflow-hidden" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-xs transition-opacity" @click="showDrawer = false"></div>

            <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
                <div class="w-screen max-w-md bg-white shadow-2xl p-6 flex flex-col justify-between overflow-y-auto">
                    <div>
                        <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                            <div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-pink-50 text-pink-800">
                                    RA 11210 Record
                                </span>
                                <h2 class="text-base font-black font-outfit text-gray-900 mt-1" x-text="selectedClaim ? selectedClaim.receipt_number : 'Claim Details'"></h2>
                            </div>
                            <button @click="showDrawer = false" class="text-gray-400 hover:text-gray-600 text-lg cursor-pointer">&times;</button>
                        </div>

                        <template x-if="selectedClaim">
                            <div class="mt-5 space-y-4">
                                <div class="bg-gray-50 rounded-2xl p-4 text-xs space-y-2 border border-gray-200">
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-500 font-bold">Total Advance:</span>
                                        <span class="font-black text-gray-900 font-mono text-sm" x-text="'PHP ' + Number(selectedClaim.amount).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-500 font-bold">SSS Share:</span>
                                        <span class="font-mono font-bold text-indigo-700" x-text="'PHP ' + Number(selectedClaim.sss_maternity_share || 0).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-500 font-bold">Company Differential:</span>
                                        <span class="font-mono font-bold text-emerald-700" x-text="'PHP ' + Number(selectedClaim.company_maternity_topup || 0).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-500 font-bold">Taxability:</span>
                                        <span class="font-black text-emerald-700">100% Tax-Exempt Statutory Benefit</span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="pt-4 border-t border-gray-100">
                        <button type="button" @click="showDrawer = false" class="w-full bg-gray-900 hover:bg-black text-white font-black text-xs py-2.5 rounded-xl transition-all shadow-sm cursor-pointer">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>

@endsection
