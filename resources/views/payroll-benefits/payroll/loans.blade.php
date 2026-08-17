@extends('layouts.app')

@php
    $pageTitle = 'Loan Amortizations — TripWise Payroll';
    $currentPage = 'payroll.loans';
@endphp

@section('content')

    <!-- Page Header & Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('payroll.salary-computation') }}" class="text-xs font-bold text-gray-500 hover:text-[#F44336] transition-colors flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Back to Payroll Runs
                </a>
            </div>
            <h1 class="text-xl font-extrabold font-outfit text-gray-900 mt-1">Employee Loan Amortization Management</h1>
            <p class="text-xs text-gray-500 mt-0.5">Track multi-agency statutory loans (SSS, Pag-IBIG HDMF) and company emergency cash advances with automated semi-monthly payroll deductions.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-black bg-emerald-50 text-emerald-800 border border-emerald-200">
                <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                DOLE & Agency Compliant
            </span>
        </div>
    </div>

    <!-- Main Container with Alpine.js -->
    <div class="space-y-6"
         x-data="{
            showAddModal: false,
            showHistoryModal: false,
            activeLoan: null,
            openHistory(loan) {
                this.activeLoan = loan;
                this.showHistoryModal = true;
            }
         }">

        <!-- KPI Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block">Active Loan Portfolio</span>
                <div class="text-xl font-black font-outfit text-gray-900 mt-1">PHP {{ number_format($totalActivePortfolio, 2) }}</div>
                <span class="text-[11px] text-gray-400 font-medium mt-0.5 block">Total outstanding balances</span>
            </div>

            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block">Active Accounts</span>
                <div class="text-xl font-black font-outfit text-blue-600 mt-1">{{ number_format($totalActiveLoans) }} Active Loans</div>
                <span class="text-[11px] text-gray-400 font-medium mt-0.5 block">Scheduled for payroll deduction</span>
            </div>

            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block">Total Recovered</span>
                <div class="text-xl font-black font-outfit text-emerald-600 mt-1">PHP {{ number_format($totalCollected, 2) }}</div>
                <span class="text-[11px] text-gray-400 font-medium mt-0.5 block">Cumulative amortizations settled</span>
            </div>

            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block">Monthly Recovery Run</span>
                <div class="text-xl font-black font-outfit text-purple-600 mt-1">PHP {{ number_format($monthlyAmortizationRecovery, 2) }}</div>
                <span class="text-[11px] text-gray-400 font-medium mt-0.5 block">Projected monthly deductions</span>
            </div>
        </div>

        <!-- Filter Bar & Action Header -->
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-5">
            
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <!-- Search & Filters -->
                <form action="{{ route('payroll.loans') }}" method="GET" class="flex flex-1 flex-wrap items-center gap-3">
                    <div class="relative flex-1 min-w-[220px] max-w-sm">
                        <input type="text" name="search" value="{{ $search }}" placeholder="Search reference no. or personnel..." 
                               class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl pl-9 pr-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-[#F44336]">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    <select name="type" onchange="this.form.submit()" 
                            class="text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-[#F44336]">
                        <option value="all">All Loan Agencies</option>
                        <option value="sss_salary_loan" {{ $type === 'sss_salary_loan' ? 'selected' : '' }}>SSS Salary Loan</option>
                        <option value="sss_calamity_loan" {{ $type === 'sss_calamity_loan' ? 'selected' : '' }}>SSS Calamity Loan</option>
                        <option value="hdmf_multi_purpose_loan" {{ $type === 'hdmf_multi_purpose_loan' ? 'selected' : '' }}>Pag-IBIG Multi-Purpose Loan</option>
                        <option value="hdmf_housing_loan" {{ $type === 'hdmf_housing_loan' ? 'selected' : '' }}>Pag-IBIG Housing Loan</option>
                        <option value="company_emergency_loan" {{ $type === 'company_emergency_loan' ? 'selected' : '' }}>Company Emergency Advance</option>
                    </select>

                    <select name="status" onchange="this.form.submit()" 
                            class="text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-[#F44336]">
                        <option value="all">All Statuses</option>
                        <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="fully_paid" {{ $status === 'fully_paid' ? 'selected' : '' }}>Fully Paid</option>
                        <option value="paused" {{ $status === 'paused' ? 'selected' : '' }}>Paused</option>
                    </select>

                    <button type="submit" class="bg-gray-900 hover:bg-black text-white text-xs font-black px-4 py-2.5 rounded-xl transition-all">
                        Filter
                    </button>
                </form>

                <!-- Add Loan Button -->
                <button type="button" @click="showAddModal = true" 
                        class="bg-[#F44336] hover:bg-[#D32F2F] text-white font-black text-xs px-5 py-2.5 rounded-xl transition-all shadow-sm flex items-center gap-2 shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Register New Employee Loan
                </button>
            </div>

            <!-- Loans Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-200 text-xs font-black text-gray-400 uppercase tracking-wider">
                            <th class="py-3 px-4">Personnel</th>
                            <th class="py-3 px-4">Agency & Loan Type</th>
                            <th class="py-3 px-4">Reference No.</th>
                            <th class="py-3 px-4 text-right">Principal / Total Due</th>
                            <th class="py-3 px-4 text-right">Cutoff Amortization</th>
                            <th class="py-3 px-4 text-right">Paid / Balance</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-xs">
                        @forelse($loans as $loan)
                            <tr class="hover:bg-gray-50/75 transition-colors">
                                <td class="py-3.5 px-4 font-black text-gray-900">
                                    <div class="text-sm font-black">{{ $loan->employee->first_name ?? 'N/A' }} {{ $loan->employee->last_name ?? '' }}</div>
                                    <span class="text-xs text-gray-400 font-mono">{{ $loan->employee->employee_code ?? '' }} • {{ $loan->employee->position ?? '' }}</span>
                                </td>
                                <td class="py-3.5 px-4 text-gray-800 font-bold">
                                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold
                                        {{ str_starts_with($loan->loan_type, 'sss') ? 'bg-blue-50 text-blue-800 border border-blue-200' : '' }}
                                        {{ str_starts_with($loan->loan_type, 'hdmf') ? 'bg-amber-50 text-amber-800 border border-amber-200' : '' }}
                                        {{ $loan->loan_type === 'company_emergency_loan' ? 'bg-purple-50 text-purple-800 border border-purple-200' : '' }}">
                                        {{ $loan->loan_type_label }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-mono font-bold text-gray-700">
                                    {{ $loan->reference_no }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-black font-outfit text-gray-900">
                                    <div>PHP {{ number_format((float)$loan->total_amount_due, 2) }}</div>
                                    <span class="text-[11px] text-gray-400 font-medium">Principal: PHP {{ number_format((float)$loan->principal_amount, 2) }}</span>
                                </td>
                                <td class="py-3.5 px-4 text-right font-black font-outfit text-rose-600">
                                    -PHP {{ number_format((float)$loan->semi_monthly_amortization, 2) }}
                                    <span class="text-[11px] text-gray-400 block font-sans font-medium">{{ $loan->term_months }} mos. term</span>
                                </td>
                                <td class="py-3.5 px-4 text-right font-black font-outfit">
                                    <div class="text-emerald-700">PHP {{ number_format((float)$loan->total_paid, 2) }} Paid</div>
                                    <span class="text-xs text-gray-500 font-mono font-bold">Bal: PHP {{ number_format((float)$loan->remaining_balance, 2) }}</span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    @if($loan->status === 'active')
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-emerald-50 text-emerald-800 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                            Active
                                        </span>
                                    @elseif($loan->status === 'fully_paid')
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-blue-50 text-blue-800 border border-blue-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                                            Fully Paid
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-amber-50 text-amber-800 border border-amber-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                            Paused
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button" @click="openHistory({{ Js::from($loan) }})" 
                                                class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold text-xs px-3 py-1.5 rounded-xl transition-all">
                                            Ledger
                                        </button>

                                        @if($loan->status === 'active')
                                            <form action="{{ route('payroll.loans.pause', $loan->id) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="bg-amber-50 hover:bg-amber-100 text-amber-800 font-bold text-xs px-2.5 py-1.5 rounded-xl border border-amber-200 transition-all">
                                                    Pause
                                                </button>
                                            </form>
                                        @elseif($loan->status === 'paused')
                                            <form action="{{ route('payroll.loans.resume', $loan->id) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold text-xs px-2.5 py-1.5 rounded-xl border border-emerald-200 transition-all">
                                                    Resume
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-10 text-center text-gray-400 text-xs font-semibold">
                                    No employee loan records found matching the specified criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($loans->hasPages())
                <div class="pt-4 border-t border-gray-100">
                    {{ $loans->links() }}
                </div>
            @endif

        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: REGISTER NEW LOAN -->
        <!-- ========================================================================= -->
        <div x-show="showAddModal" style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div @click.away="showAddModal = false" class="bg-white rounded-2xl max-w-xl w-full p-6 shadow-2xl border border-gray-100 space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h3 class="text-base font-black font-outfit text-gray-900">Register Statutory / Company Loan</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Enrolls employee loan amortization schedule into automated payroll deductions.</p>
                    </div>
                    <button @click="showAddModal = false" class="text-gray-400 hover:text-gray-600 text-sm font-bold">✕</button>
                </div>

                <form action="{{ route('payroll.loans.store') }}" method="POST" class="space-y-4 text-xs">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Target Personnel *</label>
                            <select name="employee_id" required class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                                <option value="">-- Select Personnel --</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->first_name }} {{ $emp->last_name }} ({{ $emp->position }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Agency / Loan Type *</label>
                            <select name="loan_type" required class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                                <option value="sss_salary_loan">SSS Salary Loan</option>
                                <option value="sss_calamity_loan">SSS Calamity Loan</option>
                                <option value="hdmf_multi_purpose_loan">Pag-IBIG Multi-Purpose Loan (MPL)</option>
                                <option value="hdmf_housing_loan">Pag-IBIG Housing Loan</option>
                                <option value="company_emergency_loan">Company Emergency Cash Advance</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Official Reference Number *</label>
                            <input type="text" name="reference_no" placeholder="e.g. SSS-SL-2026-0099" required class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 font-mono font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Term in Months *</label>
                            <input type="number" name="term_months" min="1" max="120" value="12" required class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 border-t border-gray-100 pt-3">
                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Principal Amount (PHP) *</label>
                            <input type="number" step="0.01" name="principal_amount" required placeholder="0.00" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Total Due with Interest (PHP) *</label>
                            <input type="number" step="0.01" name="total_amount_due" required placeholder="0.00" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Cutoff Amortization (PHP) *</label>
                            <input type="number" step="0.01" name="semi_monthly_amortization" required placeholder="0.00" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 border-t border-gray-100 pt-3">
                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Deduction Start Date *</label>
                            <input type="date" name="start_date" required value="{{ date('Y-m-d') }}" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Estimated Completion Date</label>
                            <input type="date" name="end_date" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                        </div>
                    </div>

                    <div class="border-t border-gray-100 pt-4 flex items-center justify-end gap-3">
                        <button type="button" @click="showAddModal = false" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition-all">Cancel</button>
                        <button type="submit" class="px-5 py-2.5 bg-[#F44336] hover:bg-[#D32F2F] text-white font-black rounded-xl shadow-sm transition-all">Save & Schedule Amortization</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: LOAN AMORTIZATION LEDGER HISTORY -->
        <!-- ========================================================================= -->
        <div x-show="showHistoryModal" style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div @click.away="showHistoryModal = false" class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl border border-gray-100 space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h3 class="text-base font-black font-outfit text-gray-900">Loan Deduction Ledger & Amortization History</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Historical cutoff payroll deductions recorded for this account.</p>
                    </div>
                    <button @click="showHistoryModal = false" class="text-gray-400 hover:text-gray-600 text-sm font-bold">✕</button>
                </div>

                <template x-if="activeLoan">
                    <div class="space-y-4 text-xs">
                        <div class="p-4 bg-gray-50 rounded-2xl border border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                                <span class="font-black text-sm text-gray-900 block" x-text="activeLoan.employee ? activeLoan.employee.first_name + ' ' + activeLoan.employee.last_name : 'Employee'"></span>
                                <span class="text-xs text-gray-500 font-mono" x-text="'Ref: ' + activeLoan.reference_no + ' • ' + activeLoan.loan_type_label"></span>
                            </div>
                            <div class="text-right">
                                <span class="text-xs text-gray-500 font-bold block">Remaining Balance</span>
                                <span class="text-base font-black font-outfit text-rose-600" x-text="'PHP ' + Number(activeLoan.remaining_balance).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                            </div>
                        </div>

                        <!-- Historical Deductions List -->
                        <div class="space-y-2">
                            <span class="font-bold text-gray-700 uppercase tracking-wider text-[11px] block">Recorded Payroll Deductions</span>
                            <div class="overflow-x-auto border border-gray-100 rounded-xl">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-gray-50 border-b border-gray-100 text-[11px] font-black text-gray-400 uppercase">
                                            <th class="py-2.5 px-3">Cutoff Period</th>
                                            <th class="py-2.5 px-3 text-right">Amount Deducted</th>
                                            <th class="py-2.5 px-3 text-right">Balance After</th>
                                            <th class="py-2.5 px-3 text-right">Deducted At</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 text-xs">
                                        <template x-for="log in activeLoan.amortization_logs" :key="log.id">
                                            <tr class="hover:bg-gray-50/75">
                                                <td class="py-2.5 px-3 font-mono font-bold text-gray-800" x-text="log.cutoff_period"></td>
                                                <td class="py-2.5 px-3 text-right font-black font-outfit text-rose-600" x-text="'-PHP ' + Number(log.amount_deducted).toLocaleString(undefined, {minimumFractionDigits: 2})"></td>
                                                <td class="py-2.5 px-3 text-right font-mono font-bold text-gray-700" x-text="'PHP ' + Number(log.remaining_balance_after).toLocaleString(undefined, {minimumFractionDigits: 2})"></td>
                                                <td class="py-2.5 px-3 text-right text-gray-500 font-mono" x-text="new Date(log.deducted_at).toLocaleDateString()"></td>
                                            </tr>
                                        </template>
                                        <template x-if="!activeLoan.amortization_logs || activeLoan.amortization_logs.length === 0">
                                            <tr>
                                                <td colspan="4" class="py-6 text-center text-gray-400 font-medium">
                                                    No automated deductions recorded yet. Deductions will apply on payroll release.
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="flex items-center justify-end pt-2">
                            <button type="button" @click="showHistoryModal = false" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold text-xs px-5 py-2.5 rounded-xl transition-all">
                                Close Ledger
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

    </div>

@endsection
