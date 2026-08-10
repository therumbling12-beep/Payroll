@extends('layouts.app')

@php
    $pageTitle = 'Employee Self Service (ESS)';
    $currentPage = 'ess.dashboard';
@endphp

@section('content')

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-extrabold font-outfit text-gray-900">Employee Self-Service (ESS) Portal</h1>
            <p class="text-xs text-gray-500 mt-0.5">View your transparent compensation breakdown, HMO benefit coverage, claims, and update bank deposit details.</p>
        </div>
        
        <!-- Employee Selector Dropdown -->
        <form action="{{ route('ess.dashboard') }}" method="GET" class="flex items-center gap-3">
            <label class="text-xs font-semibold text-gray-500">Select Employee:</label>
            <select name="employee_id" onchange="this.form.submit()" class="text-xs bg-white border border-gray-200 rounded-xl px-3 py-2 text-gray-800 focus:outline-none focus:border-[#F44336] shadow-sm font-medium">
                @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" {{ $selectedEmployee && $selectedEmployee->id == $emp->id ? 'selected' : '' }}>
                        {{ $emp->first_name }} {{ $emp->last_name }} ({{ $emp->position }})
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    @if(session('status'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-2xl font-bold flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session('status') }}
        </div>
    @endif

    @if($selectedEmployee)
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left Column: Profile & Bank Setup -->
        <div class="space-y-6">

            <!-- Profile Summary Card -->
            <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-[#F44336] to-orange-400 text-white flex items-center justify-center text-lg font-bold shadow-md shadow-red-200">
                        {{ strtoupper(substr($selectedEmployee->first_name, 0, 1) . substr($selectedEmployee->last_name, 0, 1)) }}
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-gray-900 font-outfit">{{ $selectedEmployee->first_name }} {{ $selectedEmployee->last_name }}</h2>
                        <span class="inline-block px-2.5 py-0.5 bg-gray-100 text-gray-600 text-[10px] font-semibold rounded-full mt-0.5">{{ $selectedEmployee->position }}</span>
                    </div>
                </div>
                <div class="border-t border-gray-50 pt-4 space-y-2.5 text-xs">
                    <div class="flex justify-between">
                        <span class="text-gray-400">Employee Code:</span>
                        <span class="font-mono font-semibold text-gray-700">{{ $selectedEmployee->employee_code }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Department:</span>
                        <span class="font-medium text-gray-800">{{ $selectedEmployee->department->name ?? 'General' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Email:</span>
                        <span class="font-medium text-gray-800">{{ $selectedEmployee->email }}</span>
                    </div>
                </div>
            </div>

            <!-- Bank Deposit Setup Card -->
            <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                <h3 class="text-sm font-bold text-gray-900 font-outfit mb-1">Bank Deposit Information</h3>
                <p class="text-[11px] text-gray-400 mb-4">Configure your preferred mode of payment for salary releases.</p>

                <form action="{{ route('ess.bank-details') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="employee_id" value="{{ $selectedEmployee->id }}">

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Payment Mode</label>
                        <select name="payment_method" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-gray-800 focus:outline-none focus:border-[#F44336]">
                            <option value="bank" {{ strtolower($selectedEmployee->payment_method ?? 'bank') === 'bank' ? 'selected' : '' }}>Direct Bank Deposit</option>
                            <option value="cash" {{ strtolower($selectedEmployee->payment_method ?? '') === 'cash' ? 'selected' : '' }}>Cash Payroll Disbursement</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Bank Provider Name</label>
                        <input type="text" name="bank_name" value="{{ $selectedEmployee->bank_name ?? 'BDO Unibank' }}" placeholder="e.g. BDO, BPI, UnionBank" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-gray-800 focus:outline-none focus:border-[#F44336]">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Bank Account / Reference No.</label>
                        <input type="text" name="bank_account_number" value="{{ $selectedEmployee->bank_account_number ?? $selectedEmployee->bank_account_no ?? '1092-3849-2849' }}" placeholder="Account Number" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-gray-800 focus:outline-none focus:border-[#F44336]">
                    </div>

                    <button type="submit" class="w-full bg-[#F44336] text-white text-xs font-bold py-2.5 px-4 rounded-xl hover:bg-red-600 transition shadow-md shadow-red-200">
                        Update Bank Account Info
                    </button>
                </form>
            </div>

            <!-- HMO Coverage Card -->
            <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden">
                <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-white/5 rounded-full blur-xl"></div>
                <div class="flex items-center justify-between mb-4">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-red-400 bg-red-950/60 border border-red-800/50 px-2.5 py-1 rounded-full">HMO Healthcare Card</span>
                    <svg class="w-6 h-6 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <h4 class="text-lg font-extrabold font-outfit">{{ $hmo->provider_plan ?? 'Standard HMO Plan' }}</h4>
                <p class="text-xs text-slate-300 font-mono tracking-widest mt-2 mb-4">{{ $hmo->hmo_card_number ?? '4000-1092-9384-2819' }}</p>
                <div class="border-t border-slate-700/60 pt-3 flex justify-between items-center text-xs">
                    <span class="text-slate-400">Maximum Benefit Limit:</span>
                    <span class="font-bold text-emerald-400">₱{{ number_format($hmo->mbl_amount ?? 150000, 2) }}</span>
                </div>
            </div>

        </div>

        <!-- Right Column: Payslip Breakdown & Claims History -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Latest Payslip Breakdown -->
            <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 font-outfit">Latest Computation Payslip</h3>
                        <p class="text-[11px] text-gray-400">Transparent statutory deductions & gross breakdown.</p>
                    </div>
                    @if($latestComputation)
                        <span class="px-3 py-1 bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-bold rounded-full uppercase">
                            {{ $latestComputation->status }}
                        </span>
                    @endif
                </div>

                @if($latestComputation)
                <div class="bg-gray-50 rounded-2xl p-5 border border-gray-200/60 space-y-3 text-xs">
                    <div class="flex justify-between items-center pb-2 border-b border-gray-200 font-bold text-gray-800">
                        <span>Cutoff Period:</span>
                        <span class="font-mono text-indigo-600">{{ $latestComputation->cutoff_period }}</span>
                    </div>

                    <!-- Earnings -->
                    <div class="space-y-1.5 pt-1">
                        <div class="flex justify-between text-gray-600">
                            <span>Base Pay:</span>
                            <span class="font-semibold text-gray-800">₱{{ number_format($latestComputation->base_pay, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Driver Trip Earnings:</span>
                            <span class="font-semibold text-gray-800">₱{{ number_format($latestComputation->trip_earnings, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Performance Bonus:</span>
                            <span class="font-semibold text-gray-800">₱{{ number_format($latestComputation->performance_bonus, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Approved Claims & Reimbursements:</span>
                            <span class="font-semibold text-emerald-600">₱{{ number_format($latestComputation->reimbursements ?? 0, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-900 font-bold pt-2 border-t border-gray-200/60">
                            <span>Gross Earnings:</span>
                            <span class="text-sm">₱{{ number_format($latestComputation->gross_pay + ($latestComputation->reimbursements ?? 0), 2) }}</span>
                        </div>
                    </div>

                    <!-- Deductions -->
                    <div class="space-y-1.5 pt-2 border-t border-gray-200">
                        @if(str_contains(auth()->user()->employee?->position ?? '', 'Driver'))
                            <span class="font-bold text-amber-600 text-[11px] uppercase tracking-wider block mb-1">TNVS Platform Deductions</span>
                            <div class="flex justify-between text-gray-600">
                                <span>Platform Commission Fee (20%):</span>
                                <span class="font-mono text-red-500">-₱{{ number_format($latestComputation->platform_fee_deduction ?? 0, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>Statutory Government Contributions:</span>
                                <span class="font-mono text-gray-400">₱0.00 (Independent Partner)</span>
                            </div>
                        @else
                            <span class="font-bold text-red-600 text-[11px] uppercase tracking-wider block mb-1">Statutory & Benefit Deductions</span>
                            <div class="flex justify-between text-gray-600">
                                <span>SSS Contribution:</span>
                                <span class="font-mono text-red-500">-₱{{ number_format($latestComputation->sss_deduction, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>PhilHealth Contribution:</span>
                                <span class="font-mono text-red-500">-₱{{ number_format($latestComputation->philhealth_deduction, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>Pag-IBIG Contribution:</span>
                                <span class="font-mono text-red-500">-₱{{ number_format($latestComputation->pagibig_deduction, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>Withholding Tax (BIR):</span>
                                <span class="font-mono text-red-500">-₱{{ number_format($latestComputation->withholding_tax ?? 0, 2) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between text-gray-900 font-bold pt-2 border-t border-gray-200/60">
                            <span>Total Deductions:</span>
                            <span class="text-red-600">₱{{ number_format($latestComputation->total_deductions, 2) }}</span>
                        </div>
                    </div>

                    <!-- Net Pay -->
                    <div class="flex justify-between items-center bg-white p-4 rounded-xl border border-gray-200 shadow-sm mt-3">
                        <span class="font-extrabold text-gray-900 text-sm font-outfit">TAKE HOME NET PAY:</span>
                        <span class="text-lg font-black text-emerald-600 font-mono">₱{{ number_format($latestComputation->net_pay, 2) }}</span>
                    </div>
                </div>
                @else
                    <div class="p-6 bg-gray-50 rounded-2xl text-center text-xs text-gray-400">
                        No salary computations generated for this employee yet.
                    </div>
                @endif
            </div>

            <!-- Incentives & Claims History Table -->
            <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                <h3 class="text-sm font-bold text-gray-900 font-outfit mb-4">My Claims & Driver Incentives</h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="text-gray-400 font-semibold border-b border-gray-100 pb-2">
                                <th class="pb-2">Type</th>
                                <th class="pb-2">Description</th>
                                <th class="pb-2">Amount</th>
                                <th class="pb-2">Status</th>
                                <th class="pb-2 text-right">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($claims as $claim)
                                <tr>
                                    <td class="py-3 capitalize font-bold text-gray-800">
                                        @if($claim->type === 'incentive')
                                            <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 rounded-md">Incentive</span>
                                        @elseif($claim->type === 'expense')
                                            <span class="px-2 py-0.5 bg-blue-50 text-blue-700 rounded-md">Expense</span>
                                        @else
                                            <span class="px-2 py-0.5 bg-purple-50 text-purple-700 rounded-md">Maternity</span>
                                        @endif
                                    </td>
                                    <td class="py-3 text-gray-600">{{ $claim->description }}</td>
                                    <td class="py-3 font-bold text-gray-900">₱{{ number_format($claim->amount, 2) }}</td>
                                    <td class="py-3">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $claim->status === 'approved' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                            {{ $claim->status }}
                                        </span>
                                    </td>
                                    <td class="py-3 text-right text-gray-400">{{ $claim->created_at->format('M d, Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-4 text-center text-gray-400">No claims or incentives recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>
    @endif

@endsection
