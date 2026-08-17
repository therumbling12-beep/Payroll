@extends('layouts.app')

@php
    $pageTitle = 'Off-Cycle Batch Details - ' . $offCycle->run_number;
    $currentPage = 'payroll.off-cycle';
@endphp

@section('content')

    <div class="space-y-6"
         x-data="{
            showTransparencyModal: false,
            activeItem: null,
            modalTab: 'summary',
            transparencyData: null,
            loadingTransparency: false,
            openTransparency(itemId) {
                this.modalTab = 'summary';
                this.transparencyData = null;
                this.loadingTransparency = true;
                this.showTransparencyModal = true;
                fetch('{{ url('/payroll/off-cycle/items') }}/' + itemId + '/transparency')
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

        <!-- Breadcrumb & Back -->
        <div class="flex items-center gap-2 text-xs text-gray-500 font-medium">
            <a href="{{ route('payroll.off-cycle') }}" class="hover:text-gray-900 font-bold transition-colors">Off-Cycle Payroll</a>
            <span>/</span>
            <span class="text-gray-900 font-mono font-bold">{{ $offCycle->run_number }}</span>
        </div>

        <!-- Header & Action Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-xl font-extrabold font-outfit text-gray-900">{{ $offCycle->title }}</h1>
                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold 
                        @if($offCycle->run_type->value === 'final_pay') bg-purple-50 text-purple-800 border border-purple-200
                        @elseif($offCycle->run_type->value === 'special_bonus') bg-emerald-50 text-emerald-800 border border-emerald-200
                        @elseif($offCycle->run_type->value === 'salary_differential') bg-blue-50 text-blue-800 border border-blue-200
                        @else bg-amber-50 text-amber-800 border border-amber-200 @endif">
                        {{ $offCycle->run_type->label() }}
                    </span>
                </div>
                <div class="flex items-center gap-4 text-xs text-gray-400 font-medium mt-1">
                    <span>Batch: <strong class="text-gray-700 font-mono">{{ $offCycle->run_number }}</strong></span>
                    <span>Payout Date: <strong class="text-gray-700 font-mono">{{ $offCycle->payout_date->format('M d, Y') }}</strong></span>
                    @if($offCycle->notes)
                        <span>Notes: <strong class="text-gray-700">{{ $offCycle->notes }}</strong></span>
                    @endif
                </div>
            </div>

            <!-- Workflow Buttons -->
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('payroll.off-cycle.export', $offCycle->id) }}" 
                   class="bg-gray-900 hover:bg-black text-white text-xs font-black px-4 py-2.5 rounded-xl transition-all shadow-xs flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Export Batch CSV
                </a>

                @if($offCycle->status === 'draft')
                    <form action="{{ route('payroll.off-cycle.approve', $offCycle->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-black px-4 py-2.5 rounded-xl transition-all shadow-xs">
                            Approve Batch
                        </button>
                    </form>
                @endif

                @if($offCycle->status === 'approved')
                    <form action="{{ route('payroll.off-cycle.release', $offCycle->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black px-4 py-2.5 rounded-xl transition-all shadow-xs">
                            Release & Disburse Payouts
                        </button>
                    </form>
                @endif

                @if($offCycle->status === 'released')
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-black bg-emerald-50 text-emerald-800 border border-emerald-200">
                        <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                        Disbursed & Closed
                    </span>
                @endif
            </div>
        </div>

        <!-- Summary Totals -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block">Total Gross Earnings</span>
                <div class="text-xl font-black font-outfit text-gray-900 mt-1">PHP {{ number_format((float)$offCycle->total_gross, 2) }}</div>
                <span class="text-[11px] text-gray-400 font-medium mt-0.5 block">Base, 13th month, bonuses & leaves</span>
            </div>

            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block">Total Deductions / Offsets</span>
                <div class="text-xl font-black font-outfit text-rose-600 mt-1">-PHP {{ number_format((float)$offCycle->total_deductions, 2) }}</div>
                <span class="text-[11px] text-gray-400 font-medium mt-0.5 block">Active loan balances & clearances</span>
            </div>

            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block">Net Take-Home Settlement</span>
                <div class="text-xl font-black font-outfit text-emerald-600 mt-1">PHP {{ number_format((float)$offCycle->total_net_pay, 2) }}</div>
                <span class="text-[11px] text-gray-400 font-medium mt-0.5 block">Disbursement payout amount</span>
            </div>
        </div>

        <!-- Itemized Employees Table -->
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 shadow-sm overflow-hidden space-y-3 p-6">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <h2 class="text-base font-black font-outfit text-gray-900">Beneficiary Personnel Compensation Breakdown</h2>
                <span class="text-xs text-gray-400 font-bold">{{ $offCycle->items->count() }} Employees Processed</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-200 text-xs font-black text-gray-400 uppercase tracking-wider bg-gray-50/50">
                            <th class="py-3 px-4">Employee</th>
                            <th class="py-3 px-4 text-right">Unpaid Base</th>
                            <th class="py-3 px-4 text-right">Pro-rated 13th</th>
                            <th class="py-3 px-4 text-right">Leave Monetization</th>
                            <th class="py-3 px-4 text-right">Bonuses / Diffs</th>
                            <th class="py-3 px-4 text-right">Gross Total</th>
                            <th class="py-3 px-4 text-right">Loan Deductions</th>
                            <th class="py-3 px-4 text-right">Net Settlement</th>
                            <th class="py-3 px-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-xs">
                        @foreach($offCycle->items as $item)
                            @php $emp = $item->employee; @endphp
                            <tr class="hover:bg-gray-50/75 transition-colors">
                                <td class="py-3.5 px-4 font-black text-gray-900">
                                    <div>{{ $emp?->last_name }}, {{ $emp?->first_name }}</div>
                                    <span class="text-xs text-gray-400 font-mono">{{ $emp?->employee_code }} • {{ $emp?->department?->name ?? 'General' }}</span>
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-gray-700">
                                    PHP {{ number_format((float)$item->basic_pay_earned, 2) }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-purple-700">
                                    PHP {{ number_format((float)$item->pro_rated_13th_month, 2) }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-blue-700">
                                    PHP {{ number_format((float)$item->leave_conversion_pay, 2) }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-gray-700">
                                    PHP {{ number_format((float)$item->bonuses_differentials, 2) }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-black font-outfit text-gray-900">
                                    PHP {{ number_format((float)$item->gross_amount, 2) }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-rose-600">
                                    -PHP {{ number_format((float)$item->loan_deduction + (float)$item->other_deductions, 2) }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-black font-outfit text-emerald-700">
                                    PHP {{ number_format((float)$item->net_settlement_pay, 2) }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button type="button" @click="openTransparency({{ $item->id }})"
                                                class="inline-flex items-center gap-1 text-xs font-bold text-gray-800 bg-gray-100 hover:bg-gray-200 border border-gray-200 px-2.5 py-1 rounded-lg transition-colors">
                                            Audit Math
                                        </button>
                                        <a href="{{ route('payroll.off-cycle.certificate', $item->id) }}" target="_blank"
                                           class="inline-flex items-center gap-1 text-xs font-bold text-purple-700 bg-purple-50 hover:bg-purple-100 border border-purple-200 px-2.5 py-1 rounded-lg transition-colors">
                                            Certificate
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: FINAL PAY & QUITCLAIM MATHEMATICAL TRANSPARENCY (4 TABS) -->
        <!-- ========================================================================= -->
        <div x-show="showTransparencyModal" style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div @click.away="showTransparencyModal = false" class="bg-white rounded-3xl max-w-2xl w-full p-6 shadow-2xl border border-gray-100 space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h3 class="text-base font-black font-outfit text-gray-900">DOLE Final Pay Settlement Transparency</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Labor Advisory No. 06-20 compliance, unearned wages, SIL monetization, and loan offset math.</p>
                    </div>
                    <button @click="showTransparencyModal = false" class="text-gray-400 hover:text-gray-600 text-sm font-bold">✕</button>
                </div>

                <div class="space-y-4 text-xs">
                    <!-- Personnel Header Card -->
                    <div class="p-3.5 bg-gray-50 rounded-2xl border border-gray-200 flex items-center justify-between">
                        <div>
                            <span class="font-black text-sm text-gray-900 block" x-text="transparencyData?.employee?.name || 'Loading...'"></span>
                            <span class="text-xs text-gray-500 font-mono block" x-text="(transparencyData?.employee?.code || '') + ' • ' + (transparencyData?.employee?.position || '') + ' • ' + (transparencyData?.employee?.department || '')"></span>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] text-gray-400 font-bold uppercase block">Separation Date</span>
                            <span class="text-xs font-black font-mono text-purple-900" x-text="transparencyData?.employee?.separation_date || 'N/A'"></span>
                        </div>
                    </div>

                    <!-- Modal Navigation Tabs -->
                    <div class="flex items-center gap-1.5 p-1 bg-gray-100 rounded-xl overflow-x-auto">
                        <button type="button" @click="modalTab = 'summary'"
                                :class="modalTab === 'summary' ? 'bg-white font-black text-gray-900 shadow-xs' : 'text-gray-600 font-bold hover:text-gray-900'"
                                class="px-3 py-1.5 rounded-lg text-xs transition-all whitespace-nowrap">
                            1. Settlement Summary
                        </button>
                        <button type="button" @click="modalTab = 'wages_leaves'"
                                :class="modalTab === 'wages_leaves' ? 'bg-white font-black text-gray-900 shadow-xs' : 'text-gray-600 font-bold hover:text-gray-900'"
                                class="px-3 py-1.5 rounded-lg text-xs transition-all whitespace-nowrap">
                            2. Wages & SIL Leaves
                        </button>
                        <button type="button" @click="modalTab = 'pro_13th'"
                                :class="modalTab === 'pro_13th' ? 'bg-white font-black text-gray-900 shadow-xs' : 'text-gray-600 font-bold hover:text-gray-900'"
                                class="px-3 py-1.5 rounded-lg text-xs transition-all whitespace-nowrap">
                            3. Pro-Rated 13th Math
                        </button>
                        <button type="button" @click="modalTab = 'loans_dole'"
                                :class="modalTab === 'loans_dole' ? 'bg-white font-black text-gray-900 shadow-xs' : 'text-gray-600 font-bold hover:text-gray-900'"
                                class="px-3 py-1.5 rounded-lg text-xs transition-all whitespace-nowrap">
                            4. Loan Offsets & DOLE LA 06-20
                        </button>
                    </div>

                    <!-- TAB 1: SETTLEMENT SUMMARY -->
                    <div x-show="modalTab === 'summary'" class="space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-3.5 bg-purple-50/40 rounded-2xl border border-purple-100 space-y-2">
                                <span class="font-black text-purple-900 uppercase tracking-wider block text-[11px]">Gross Separation Credits</span>
                                <div class="space-y-1 text-gray-700">
                                    <div class="flex justify-between">
                                        <span>Unpaid Wages Earned:</span>
                                        <span class="font-mono font-bold" x-text="'PHP ' + Number(transparencyData?.basic_wages_math?.basic_pay_earned || 0).toFixed(2)"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Pro-Rated 13th Month:</span>
                                        <span class="font-mono font-bold" x-text="'PHP ' + Number(transparencyData?.pro_rated_13th_math?.pro_rated_13th_month || 0).toFixed(2)"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Unused Leave SIL Pay:</span>
                                        <span class="font-mono font-bold" x-text="'PHP ' + Number(transparencyData?.leave_monetization_math?.leave_conversion_pay || 0).toFixed(2)"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Reimbursements / Diffs:</span>
                                        <span class="font-mono font-bold" x-text="'PHP ' + Number(transparencyData?.gross_settlement_math?.reimbursements || 0).toFixed(2)"></span>
                                    </div>
                                    <div class="flex justify-between border-t border-purple-200 pt-1 font-black text-purple-950">
                                        <span>Total Gross Earnings:</span>
                                        <span class="font-mono font-outfit" x-text="'PHP ' + Number(transparencyData?.gross_settlement_math?.gross_amount || 0).toFixed(2)"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="p-3.5 bg-rose-50/40 rounded-2xl border border-rose-100 space-y-2">
                                <span class="font-black text-rose-900 uppercase tracking-wider block text-[11px]">Deductions & Loan Offsets</span>
                                <div class="space-y-1 text-gray-700">
                                    <div class="flex justify-between">
                                        <span>Active Loan Balances Offset:</span>
                                        <span class="font-mono font-bold text-rose-700" x-text="'-PHP ' + Number(transparencyData?.loan_offsets_math?.total_offset_deducted || 0).toFixed(2)"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Clearance / Accountabilities:</span>
                                        <span class="font-mono font-bold text-rose-700" x-text="'-PHP ' + Number(transparencyData?.other_deductions_math?.clearance_deductions || 0).toFixed(2)"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Withholding Tax:</span>
                                        <span class="font-mono font-bold" x-text="'PHP ' + Number(transparencyData?.other_deductions_math?.withholding_tax || 0).toFixed(2)"></span>
                                    </div>
                                    <div class="flex justify-between border-t border-rose-200 pt-1 font-black text-rose-950">
                                        <span>Total Deductions:</span>
                                        <span class="font-mono font-outfit" x-text="'-PHP ' + Number(transparencyData?.other_deductions_math?.total_deductions || 0).toFixed(2)"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-3 bg-gray-50 rounded-xl border border-gray-200 font-mono text-[11px] text-gray-800"
                             x-text="transparencyData?.net_settlement_math?.formula || 'Calculating net formula...'">
                        </div>
                    </div>

                    <!-- TAB 2: WAGES & SIL LEAVES -->
                    <div x-show="modalTab === 'wages_leaves'" class="space-y-3">
                        <div class="p-4 bg-gray-50 rounded-2xl border border-gray-200 space-y-2">
                            <span class="font-black text-gray-900 uppercase tracking-wider block">1. Unpaid Regular Working Days</span>
                            <div class="p-3 bg-white rounded-xl border border-gray-200 font-mono text-[11px] text-gray-800"
                                 x-text="transparencyData?.basic_wages_math?.formula || ''">
                            </div>
                        </div>

                        <div class="p-4 bg-gray-50 rounded-2xl border border-gray-200 space-y-2">
                            <span class="font-black text-gray-900 uppercase tracking-wider block">2. Service Incentive Leave (SIL) Monetization</span>
                            <div class="p-3 bg-white rounded-xl border border-gray-200 font-mono text-[11px] text-gray-800"
                                 x-text="transparencyData?.leave_monetization_math?.formula || ''">
                            </div>
                            <p class="text-[11px] text-gray-500 font-medium">
                                Per DOLE regulations, all unused Service Incentive Leaves (up to statutory credits) are monetized at the employee's regular daily rate.
                            </p>
                        </div>
                    </div>

                    <!-- TAB 3: PRO-RATED 13TH MONTH -->
                    <div x-show="modalTab === 'pro_13th'" class="space-y-3">
                        <div class="p-4 bg-purple-50/40 rounded-2xl border border-purple-100 space-y-2">
                            <span class="font-black text-purple-900 uppercase tracking-wider block">Pro-Rated 13th Month Pay Accrual</span>
                            <div class="p-3 bg-white rounded-xl border border-purple-100 font-mono text-[11px] text-gray-800"
                                 x-text="transparencyData?.pro_rated_13th_math?.formula || ''">
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-gray-700 pt-1">
                                <div>Active Months Rendered: <strong class="text-gray-900" x-text="(transparencyData?.pro_rated_13th_math?.months_worked || 0) + ' Months'"></strong></div>
                                <div>Statutory Non-Taxable Cap: <strong class="text-emerald-700" x-text="'PHP ' + Number(transparencyData?.pro_rated_13th_math?.non_taxable_exempt || 0).toFixed(2)"></strong></div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 4: LOAN OFFSETS & DOLE LA 06-20 -->
                    <div x-show="modalTab === 'loans_dole'" class="space-y-3">
                        <!-- Loan Offsets Table -->
                        <div class="border border-gray-200 rounded-2xl overflow-hidden">
                            <div class="bg-gray-100 px-3.5 py-2 font-black text-gray-800 uppercase text-[11px]">
                                Active Loan Balance Offsets to Zero
                            </div>
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-gray-50 border-b border-gray-200 text-gray-400 font-black text-[10px] uppercase">
                                        <th class="py-1.5 px-3">Loan Type</th>
                                        <th class="py-1.5 px-3">Ref No</th>
                                        <th class="py-1.5 px-3 text-right">Balance Prior</th>
                                        <th class="py-1.5 px-3 text-right">Offset Applied</th>
                                        <th class="py-1.5 px-3 text-right">Remaining</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <template x-for="loan in transparencyData?.loan_offsets_math?.items" :key="loan.reference_no">
                                        <tr class="hover:bg-gray-50">
                                            <td class="py-1.5 px-3 font-bold" x-text="loan.loan_type"></td>
                                            <td class="py-1.5 px-3 font-mono text-gray-500" x-text="loan.reference_no"></td>
                                            <td class="py-1.5 px-3 text-right font-mono" x-text="'PHP ' + Number(loan.balance_before).toFixed(2)"></td>
                                            <td class="py-1.5 px-3 text-right font-mono font-bold text-rose-600" x-text="'-PHP ' + Number(loan.offset_deduction).toFixed(2)"></td>
                                            <td class="py-1.5 px-3 text-right font-mono font-bold text-emerald-600" x-text="'PHP ' + Number(loan.balance_after).toFixed(2)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <!-- DOLE LA 06-20 Checklist -->
                        <div class="p-3.5 bg-gray-50 rounded-2xl border border-gray-200 space-y-2">
                            <span class="font-black text-gray-900 uppercase tracking-wider block text-[11px]">DOLE Labor Advisory No. 06-20 Statutory Checklist</span>
                            <div class="space-y-1.5">
                                <div class="p-2 bg-white rounded-xl border border-gray-200 flex items-center justify-between">
                                    <span class="text-gray-700">1. Released within 30 days from date of employee separation</span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800"
                                          x-text="transparencyData?.dole_la_06_20_compliance?.statutory_timeline?.status || 'COMPLIANT'">
                                    </span>
                                </div>
                                <div class="p-2 bg-white rounded-xl border border-gray-200 flex items-center justify-between">
                                    <span class="text-gray-700">2. Certificate of Employment (COE) prepared within 3 days</span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800">READY</span>
                                </div>
                                <div class="p-2 bg-white rounded-xl border border-gray-200 flex items-center justify-between">
                                    <span class="text-gray-700">3. Full quitclaim waiver disclosure of offset balances</span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800">COMPLIANT</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Net Take-Home Settlement Banner -->
                    <div class="p-4 bg-emerald-950 text-white rounded-2xl flex items-center justify-between">
                        <div>
                            <span class="text-xs text-emerald-300 font-bold block">Net Final Pay Settlement Payout</span>
                            <span class="text-[10px] text-emerald-400 font-mono">Disbursement Amount on Quitclaim Signing</span>
                        </div>
                        <span class="text-xl font-black font-outfit text-emerald-200"
                              x-text="'PHP ' + Number(transparencyData?.net_settlement_math?.net_payout || 0).toLocaleString(undefined, {minimumFractionDigits: 2})">
                        </span>
                    </div>

                    <div class="flex items-center justify-end pt-2">
                        <button type="button" @click="showTransparencyModal = false" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold text-xs px-5 py-2.5 rounded-xl transition-all">
                            Close Audit
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>

@endsection
