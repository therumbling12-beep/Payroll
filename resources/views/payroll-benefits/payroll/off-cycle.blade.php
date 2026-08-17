@extends('layouts.app')

@php
    $pageTitle = 'Off-Cycle Payroll & Final Pay';
    $currentPage = 'payroll.off-cycle';
@endphp

@section('content')

    <div x-data="{ 
        showCreateModal: false, 
        modalRunType: 'final_pay'
    }" class="space-y-6">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-extrabold font-outfit text-gray-900">Off-Cycle Payroll & Final Pay Settlements</h1>
                <p class="text-xs text-gray-500 mt-0.5">Manage special compensation runs, retroactive salary differentials, 13th month advances, and DOLE-compliant Final Pay quitclaim settlements.</p>
            </div>
            <div class="flex items-center gap-3">
                <button type="button" @click="showCreateModal = true" 
                        class="bg-[#F44336] hover:bg-[#d32f2f] text-white text-xs font-black px-4 py-2.5 rounded-xl shadow-xs transition-all flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    New Off-Cycle Run
                </button>
            </div>
        </div>

        <!-- KPI Portfolio Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block">Total Off-Cycle Batches</span>
                <div class="text-2xl font-black font-outfit text-gray-900 mt-1">{{ $totalRuns }}</div>
                <span class="text-[11px] text-gray-400 font-medium mt-0.5 block">Separation, bonus & advance runs</span>
            </div>

            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block">Pending Governance Approvals</span>
                <div class="text-2xl font-black font-outfit text-amber-600 mt-1">{{ $pendingApprovals }}</div>
                <span class="text-[11px] text-gray-400 font-medium mt-0.5 block">Draft batches awaiting review</span>
            </div>

            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block">Total Released & Disbursed</span>
                <div class="text-2xl font-black font-outfit text-emerald-600 mt-1">PHP {{ number_format($totalDisbursed, 2) }}</div>
                <span class="text-[11px] text-gray-400 font-medium mt-0.5 block">All-time off-cycle settlements</span>
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-4 shadow-sm">
            <form action="{{ route('payroll.off-cycle') }}" method="GET" class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex flex-1 flex-wrap items-center gap-3">
                    <div class="relative flex-1 min-w-[200px]">
                        <input type="text" name="search" value="{{ $search }}" placeholder="Search by run number, title..." 
                               class="w-full text-xs font-medium bg-gray-50 border border-gray-200 rounded-xl pl-9 pr-3.5 py-2.5 text-gray-800 placeholder-gray-400 focus:outline-none focus:border-[#F44336]">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    <select name="type" onchange="this.form.submit()" 
                            class="text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-[#F44336]">
                        <option value="all">All Run Types</option>
                        <option value="final_pay" {{ $type === 'final_pay' ? 'selected' : '' }}>Final Pay Settlements</option>
                        <option value="special_bonus" {{ $type === 'special_bonus' ? 'selected' : '' }}>Special Bonus Runs</option>
                        <option value="salary_differential" {{ $type === 'salary_differential' ? 'selected' : '' }}>Salary Differentials</option>
                        <option value="thirteenth_month_advance" {{ $type === 'thirteenth_month_advance' ? 'selected' : '' }}>13th Month Advances</option>
                    </select>

                    <select name="status" onchange="this.form.submit()" 
                            class="text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-[#F44336]">
                        <option value="all">All Statuses</option>
                        <option value="draft" {{ $status === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="released" {{ $status === 'released' ? 'selected' : '' }}>Released</option>
                    </select>

                    <button type="submit" class="bg-gray-900 hover:bg-black text-white text-xs font-black px-4 py-2.5 rounded-xl transition-all">
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Off-Cycle Runs Table -->
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-200 text-xs font-black text-gray-400 uppercase tracking-wider bg-gray-50/50">
                            <th class="py-3.5 px-4">Run Number & Title</th>
                            <th class="py-3.5 px-4">Run Type</th>
                            <th class="py-3.5 px-4 text-center">Headcount</th>
                            <th class="py-3.5 px-4 text-right">Gross Amount</th>
                            <th class="py-3.5 px-4 text-right">Total Deductions</th>
                            <th class="py-3.5 px-4 text-right">Net Payout</th>
                            <th class="py-3.5 px-4">Payout Date</th>
                            <th class="py-3.5 px-4 text-center">Status</th>
                            <th class="py-3.5 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-xs">
                        @forelse($runs as $run)
                            <tr class="hover:bg-gray-50/75 transition-colors">
                                <td class="py-3.5 px-4 font-black text-gray-900">
                                    <div class="font-outfit text-sm">{{ $run->title }}</div>
                                    <span class="text-xs text-gray-400 font-mono">{{ $run->run_number }}</span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold 
                                        @if($run->run_type->value === 'final_pay') bg-purple-50 text-purple-800 border border-purple-200
                                        @elseif($run->run_type->value === 'special_bonus') bg-emerald-50 text-emerald-800 border border-emerald-200
                                        @elseif($run->run_type->value === 'salary_differential') bg-blue-50 text-blue-800 border border-blue-200
                                        @else bg-amber-50 text-amber-800 border border-amber-200 @endif">
                                        {{ $run->run_type->label() }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center font-bold text-gray-700">
                                    {{ $run->items->count() }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-black font-outfit text-gray-900">
                                    PHP {{ number_format((float)$run->total_gross, 2) }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-rose-600">
                                    -PHP {{ number_format((float)$run->total_deductions, 2) }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-black font-outfit text-emerald-700">
                                    PHP {{ number_format((float)$run->total_net_pay, 2) }}
                                </td>
                                <td class="py-3.5 px-4 text-gray-600 font-mono">
                                    {{ $run->payout_date->format('M d, Y') }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    @if($run->status === 'released')
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-emerald-50 text-emerald-800 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                            Released
                                        </span>
                                    @elseif($run->status === 'approved')
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-blue-50 text-blue-800 border border-blue-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                                            Approved
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-amber-50 text-amber-800 border border-amber-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                                            Draft
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <a href="{{ route('payroll.off-cycle.show', $run->id) }}" 
                                       class="inline-block bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold px-3 py-1.5 rounded-lg text-xs transition-colors">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-8 text-center text-gray-400 font-medium">
                                    No off-cycle payroll runs found matching the criteria. Click "New Off-Cycle Run" to initiate one.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($runs->hasPages())
                <div class="p-4 border-t border-gray-100">
                    {{ $runs->links() }}
                </div>
            @endif
        </div>

        <!-- ========================================================================= -->
        <!-- CREATE OFF-CYCLE RUN MODAL -->
        <!-- ========================================================================= -->
        <div x-show="showCreateModal" x-transition 
             class="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div @click.away="showCreateModal = false" 
                 class="bg-white rounded-3xl max-w-xl w-full p-6 shadow-2xl space-y-5 max-h-[90vh] overflow-y-auto">
                
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h3 class="text-base font-black font-outfit text-gray-900">Initiate New Off-Cycle Run</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Select run type and specify compensation parameters.</p>
                    </div>
                    <button @click="showCreateModal = false" class="text-gray-400 hover:text-gray-600 text-sm font-bold">✕</button>
                </div>

                <!-- Run Type Tabs -->
                <div class="grid grid-cols-2 gap-2 bg-gray-100/80 p-1 rounded-2xl">
                    <button type="button" @click="modalRunType = 'final_pay'" 
                            :class="modalRunType === 'final_pay' ? 'bg-white text-gray-900 font-black shadow-xs' : 'text-gray-500 font-bold'"
                            class="py-2 text-xs rounded-xl transition-all">
                        Final Pay / Separation
                    </button>
                    <button type="button" @click="modalRunType = 'special_bonus'" 
                            :class="modalRunType !== 'final_pay' ? 'bg-white text-gray-900 font-black shadow-xs' : 'text-gray-500 font-bold'"
                            class="py-2 text-xs rounded-xl transition-all">
                        Special Bonus / Differential
                    </button>
                </div>

                <!-- FORM A: FINAL PAY WIZARD -->
                <form x-show="modalRunType === 'final_pay'" action="{{ route('payroll.off-cycle.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="run_type" value="final_pay">

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Separating Employee</label>
                        <select name="employee_id" required 
                                class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-[#F44336]">
                            <option value="">-- Select Employee --</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->last_name }}, {{ $emp->first_name }} ({{ $emp->employee_code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Last Working / Separation Date</label>
                            <input type="date" name="separation_date" required value="{{ date('Y-m-d') }}" 
                                   class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-[#F44336]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Payout Release Date</label>
                            <input type="date" name="payout_date" required value="{{ date('Y-m-d') }}" 
                                   class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-[#F44336]">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Unpaid Days in Final Period</label>
                            <input type="number" step="0.5" name="unpaid_days" value="0" min="0" max="31" 
                                   class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-[#F44336]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Unused Leave Credits (SIL)</label>
                            <input type="number" step="0.5" name="unused_leaves" value="0" min="0" max="60" 
                                   class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-[#F44336]">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Other Deductions (Clearances)</label>
                            <input type="number" step="0.01" name="other_deductions" value="0.00" min="0" 
                                   class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-[#F44336]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Reimbursements</label>
                            <input type="number" step="0.01" name="reimbursements" value="0.00" min="0" 
                                   class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-[#F44336]">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Settlement Notes</label>
                        <input type="text" name="notes" placeholder="e.g. Voluntary resignation, cleared of company liabilities" 
                               class="w-full text-xs font-medium bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-[#F44336]">
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                        <button type="button" @click="showCreateModal = false" class="text-xs font-bold text-gray-500 hover:text-gray-700 px-4 py-2.5">
                            Cancel
                        </button>
                        <button type="submit" class="bg-[#F44336] hover:bg-[#d32f2f] text-white text-xs font-black px-5 py-2.5 rounded-xl shadow-xs transition-all">
                            Compute & Generate Final Pay Batch
                        </button>
                    </div>
                </form>

                <!-- FORM B: SPECIAL BONUS / DIFFERENTIAL RUN -->
                <form x-show="modalRunType !== 'final_pay'" action="{{ route('payroll.off-cycle.store') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Run Type</label>
                        <select name="run_type" required 
                                class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-[#F44336]">
                            <option value="special_bonus">Special Performance Bonus</option>
                            <option value="salary_differential">Salary Differential / Retroactive Pay</option>
                            <option value="thirteenth_month_advance">13th Month Pay Advance</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Batch Title</label>
                        <input type="text" name="title" required placeholder="e.g. Mid-Year Performance Bonus 2026" 
                               class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-[#F44336]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Disbursement Date</label>
                        <input type="date" name="payout_date" required value="{{ date('Y-m-d') }}" 
                               class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-[#F44336]">
                    </div>

                    <div class="space-y-2 border border-gray-100 p-3 rounded-2xl bg-gray-50/50">
                        <label class="block text-xs font-bold text-gray-700 uppercase">Beneficiary Employee & Amount</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <select name="employee_ids[]" required 
                                    class="w-full text-xs font-bold bg-white border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-[#F44336]">
                                <option value="">-- Select Employee --</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->last_name }}, {{ $emp->first_name }}</option>
                                @endforeach
                            </select>
                            <input type="number" step="0.01" name="amounts[]" required placeholder="Payout Amount (PHP)" 
                                   class="w-full text-xs font-bold bg-white border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-[#F44336]">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Notes</label>
                        <input type="text" name="notes" placeholder="e.g. Approved by management board" 
                               class="w-full text-xs font-medium bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-[#F44336]">
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                        <button type="button" @click="showCreateModal = false" class="text-xs font-bold text-gray-500 hover:text-gray-700 px-4 py-2.5">
                            Cancel
                        </button>
                        <button type="submit" class="bg-[#F44336] hover:bg-[#d32f2f] text-white text-xs font-black px-5 py-2.5 rounded-xl shadow-xs transition-all">
                            Create Off-Cycle Run
                        </button>
                    </div>
                </form>

            </div>
        </div>

    </div>

@endsection
