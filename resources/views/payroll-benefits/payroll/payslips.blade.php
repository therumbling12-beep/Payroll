@extends('layouts.app')

@php
    $pageTitle = 'Payslip Generation & Distribution';
    $currentPage = 'payroll.payslips';
@endphp

@section('content')

    <div class="space-y-6">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-extrabold font-outfit text-gray-900">Personnel Payslip Generation & Distribution</h1>
                <p class="text-xs text-gray-500 mt-0.5">Generate, inspect, and distribute DOLE-compliant digital payslips with Security Bank bulk disbursement and Cash Voucher exports.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('payroll.payslips.batch', $cutoff) }}" target="_blank"
                   class="bg-gray-900 hover:bg-black text-white text-xs font-black px-4 py-2.5 rounded-xl shadow-xs transition-all flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Print Batch Payslips
                </a>

                <form action="{{ route('payroll.payslips.push-ess', $cutoff) }}" method="POST">
                    @csrf
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-black px-4 py-2.5 rounded-xl shadow-xs transition-all flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        Publish to ESS Portal
                    </button>
                </form>
            </div>
        </div>

        <!-- KPI Portfolio Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block">Total Net Disbursement</span>
                <div class="text-2xl font-black font-outfit text-emerald-600 mt-1">PHP {{ number_format($totalNet, 2) }}</div>
                <span class="text-[11px] text-gray-400 font-medium mt-0.5 block">Cutoff: {{ $cutoff }}</span>
            </div>

            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block">Security Bank (SBC) Payees</span>
                <div class="text-2xl font-black font-outfit text-blue-700 mt-1">{{ $bankCount }} Accounts</div>
                <span class="text-[11px] text-gray-400 font-medium mt-0.5 block">Direct bank credit beneficiaries</span>
            </div>

            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block">Cash Voucher Payees</span>
                <div class="text-2xl font-black font-outfit text-amber-700 mt-1">{{ $cashCount }} Personnel</div>
                <span class="text-[11px] text-gray-400 font-medium mt-0.5 block">Cashier counter disbursements</span>
            </div>
        </div>

        <!-- Filter & Export Controls -->
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-4 shadow-sm">
            <form action="{{ route('payroll.payslips') }}" method="GET" class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div class="flex flex-1 flex-wrap items-center gap-3">
                    <select name="period" onchange="this.form.submit()" 
                            class="text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-[#F44336]">
                        @foreach($cutoffs as $c)
                            <option value="{{ $c->cutoff_period }}" {{ $cutoff === $c->cutoff_period ? 'selected' : '' }}>
                                Cutoff: {{ $c->cutoff_period }}
                            </option>
                        @endforeach
                    </select>

                    <div class="relative min-w-[200px] flex-1">
                        <input type="text" name="search" value="{{ $search }}" placeholder="Search by name, code..." 
                               class="w-full text-xs font-medium bg-gray-50 border border-gray-200 rounded-xl pl-9 pr-3.5 py-2.5 text-gray-800 placeholder-gray-400 focus:outline-none focus:border-[#F44336]">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    <select name="department" onchange="this.form.submit()" 
                            class="text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-[#F44336]">
                        <option value="all">All Departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ (string)$deptId === (string)$dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>

                    <select name="mode" onchange="this.form.submit()" 
                            class="text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-[#F44336]">
                        <option value="all">All Payment Modes</option>
                        <option value="bank" {{ $mode === 'bank' ? 'selected' : '' }}>Security Bank Transfer</option>
                        <option value="cash" {{ $mode === 'cash' ? 'selected' : '' }}>Cash Payment</option>
                    </select>

                    <button type="submit" class="bg-gray-900 hover:bg-black text-white text-xs font-black px-4 py-2.5 rounded-xl transition-all">
                        Filter
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('payroll.export.security-bank', $cutoff) }}" 
                       class="bg-blue-50 hover:bg-blue-100 text-blue-800 font-bold text-xs px-3.5 py-2.5 rounded-xl border border-blue-200 transition-all flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Export SBC File
                    </a>

                    <a href="{{ route('payroll.export.cash-voucher', $cutoff) }}" 
                       class="bg-amber-50 hover:bg-amber-100 text-amber-800 font-bold text-xs px-3.5 py-2.5 rounded-xl border border-amber-200 transition-all flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Export Cash Vouchers
                    </a>
                </div>
            </form>
        </div>

        <!-- Payslips Table -->
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-200 text-xs font-black text-gray-400 uppercase tracking-wider bg-gray-50/50">
                            <th class="py-3.5 px-4">Employee</th>
                            <th class="py-3.5 px-4">Department & Position</th>
                            <th class="py-3.5 px-4">Disbursement Mode</th>
                            <th class="py-3.5 px-4 text-right">Gross Pay</th>
                            <th class="py-3.5 px-4 text-right">Total Deductions</th>
                            <th class="py-3.5 px-4 text-right">Net Take-Home Pay</th>
                            <th class="py-3.5 px-4 text-center">Status</th>
                            <th class="py-3.5 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-xs">
                        @forelse($computations as $comp)
                            @php $emp = $comp->employee; @endphp
                            <tr class="hover:bg-gray-50/75 transition-colors">
                                <td class="py-3.5 px-4 font-black text-gray-900">
                                    <div>{{ $emp?->first_name }} {{ $emp?->last_name }}</div>
                                    <span class="text-xs text-gray-400 font-mono">{{ $emp?->employee_code }}</span>
                                </td>
                                <td class="py-3.5 px-4 text-gray-700 font-bold">
                                    <div>{{ $emp?->position }}</div>
                                    <span class="text-xs text-gray-400">{{ $emp?->department?->name ?? 'General' }}</span>
                                </td>
                                <td class="py-3.5 px-4">
                                    @if($emp?->payment_mode === 'bank')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-blue-50 text-blue-800 border border-blue-200">
                                            SBC: {{ $emp->bank_account_number ?: '0012345678' }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                            Cash Voucher
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-right font-black font-outfit text-gray-900">
                                    PHP {{ number_format((float)$comp->gross_pay, 2) }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-rose-600">
                                    -PHP {{ number_format((float)$comp->total_deductions, 2) }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-black font-outfit text-emerald-700">
                                    PHP {{ number_format((float)$comp->net_pay, 2) }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-black bg-emerald-50 text-emerald-800 border border-emerald-200">
                                        Generated
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <a href="{{ route('payroll.payslips.show', $comp->id) }}" target="_blank"
                                       class="inline-block bg-gray-900 hover:bg-black text-white font-bold px-3 py-1.5 rounded-lg text-xs transition-colors shadow-xs">
                                        View / Print
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8 text-center text-gray-400 font-medium">
                                    No salary computations found for period [{{ $cutoff }}]. Run automated computation first.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($computations->hasPages())
                <div class="p-4 border-t border-gray-100">
                    {{ $computations->links() }}
                </div>
            @endif
        </div>

    </div>

@endsection
