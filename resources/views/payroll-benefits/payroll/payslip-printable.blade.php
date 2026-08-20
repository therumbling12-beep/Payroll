<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Payslip - {{ $payslip['full_name'] }} ({{ $payslip['cutoff_period'] }})</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; font-size: 11pt; }
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-900 font-sans p-6">

    <div class="max-w-4xl mx-auto space-y-6">

        <!-- Print Action Header -->
        <div class="no-print flex items-center justify-between bg-white p-4 rounded-2xl border border-gray-200 shadow-sm">
            <div class="flex items-center gap-2 text-xs text-gray-500 font-bold">
                <span>DOLE-Compliant Electronic Payslip</span>
                <span>•</span>
                <span class="text-gray-900 font-mono">{{ $payslip['cutoff_period'] }}</span>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="window.print()" class="bg-gray-900 hover:bg-black text-white text-xs font-black px-4 py-2 rounded-xl transition-all">
                    Print / Save as PDF
                </button>
                <button onclick="window.close()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-bold px-4 py-2 rounded-xl transition-all">
                    Close
                </button>
            </div>
        </div>

        <!-- Official Payslip Sheet -->
        <div class="bg-white p-8 rounded-3xl border border-gray-200 shadow-sm space-y-5">
            
            <!-- Company Letterhead -->
            <div class="border-b-2 border-gray-900 pb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h1 class="text-lg font-black tracking-wider uppercase text-gray-900">TRIPWISE TRANSPORT & LOGISTICS INC.</h1>
                    <p class="text-xs text-gray-500">TripWise TNVS Payroll & Benefits System • DOLE Advisory No. 06-20</p>
                </div>
                <div class="text-left sm:text-right">
                    <span class="text-xs font-black uppercase text-gray-900 tracking-wide block">OFFICIAL EMPLOYEE PAYSLIP (WEEKLY)</span>
                    <span class="text-xs text-gray-500 font-mono font-bold">Pay Period: {{ $payslip['cutoff_period'] }}</span>
                </div>
            </div>

            <!-- Employee Info Header -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs border border-gray-200 p-3.5 rounded-2xl bg-gray-50/50">
                <div>
                    <span class="text-gray-400 font-medium block">Employee Name:</span>
                    <span class="font-black text-gray-900 uppercase">{{ $payslip['full_name'] }}</span>
                </div>
                <div>
                    <span class="text-gray-400 font-medium block">Employee Code:</span>
                    <span class="font-mono font-bold text-gray-800">{{ $payslip['employee_code'] }}</span>
                </div>
                <div>
                    <span class="text-gray-400 font-medium block">Position & Department:</span>
                    <span class="font-bold text-gray-800">{{ $payslip['position'] }} • {{ $payslip['department'] }}</span>
                </div>
                <div>
                    <span class="text-gray-400 font-medium block">Payment Channel:</span>
                    <span class="font-bold text-gray-800">
                        {{ $payslip['payment_mode'] === 'bank' ? 'SBC Direct (' . $payslip['bank_account_number'] . ')' : 'Cash Disbursement' }}
                    </span>
                </div>
            </div>

            <!-- Side-by-Side Compensation Breakdown -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                
                <!-- EARNINGS TABLE -->
                <div class="border border-gray-200 rounded-2xl overflow-hidden">
                    <div class="bg-gray-100 px-3.5 py-2 border-b border-gray-200 flex justify-between items-center">
                        <span class="text-xs font-black uppercase tracking-wider text-gray-800">Gross Earnings</span>
                        <span class="text-[11px] text-gray-500 font-bold">Taxable & Non-Taxable</span>
                    </div>
                    <table class="w-full text-xs border-collapse">
                        <tbody class="divide-y divide-gray-100">
                            @if($payslip['earnings']['base_pay'] > 0)
                                <tr>
                                    <td class="py-1.5 px-3 text-gray-700">Basic Salary (Weekly)</td>
                                    <td class="py-1.5 px-3 text-right font-mono font-bold text-gray-900">PHP {{ number_format($payslip['earnings']['base_pay'], 2) }}</td>
                                </tr>
                            @endif
                            @if($payslip['earnings']['trip_earnings'] > 0)
                                <tr>
                                    <td class="py-1.5 px-3 text-gray-700">TNVS Trip Fares & Passenger Bookings</td>
                                    <td class="py-1.5 px-3 text-right font-mono font-bold text-blue-700">PHP {{ number_format($payslip['earnings']['trip_earnings'], 2) }}</td>
                                </tr>
                            @endif
                            @if($payslip['earnings']['holiday_pay'] > 0)
                                <tr>
                                    <td class="py-1.5 px-3 text-gray-700">Holiday Pay (Regular 200% / Special 130%)</td>
                                    <td class="py-1.5 px-3 text-right font-mono font-bold text-purple-700">PHP {{ number_format($payslip['earnings']['holiday_pay'], 2) }}</td>
                                </tr>
                            @endif
                            @if($payslip['earnings']['overtime_pay'] > 0)
                                <tr>
                                    <td class="py-1.5 px-3 text-gray-700">Overtime & Rest Day Premium (125% / 130%)</td>
                                    <td class="py-1.5 px-3 text-right font-mono font-bold text-blue-800">PHP {{ number_format($payslip['earnings']['overtime_pay'], 2) }}</td>
                                </tr>
                            @endif
                            @if($payslip['earnings']['night_diff_pay'] > 0)
                                <tr>
                                    <td class="py-1.5 px-3 text-gray-700">Night Shift Differential (10%)</td>
                                    <td class="py-1.5 px-3 text-right font-mono font-bold text-indigo-700">PHP {{ number_format($payslip['earnings']['night_diff_pay'], 2) }}</td>
                                </tr>
                            @endif
                            @if($payslip['earnings']['performance_bonus'] > 0)
                                <tr>
                                    <td class="py-1.5 px-3 text-gray-700">Performance Bonus / Allowances</td>
                                    <td class="py-1.5 px-3 text-right font-mono font-bold text-amber-700">PHP {{ number_format($payslip['earnings']['performance_bonus'], 2) }}</td>
                                </tr>
                            @endif
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray-50 border-t border-gray-200 font-black">
                                <td class="py-2 px-3 text-gray-900">Total Gross Pay</td>
                                <td class="py-2 px-3 text-right font-mono text-gray-900 font-outfit text-sm">PHP {{ number_format($payslip['earnings']['gross_pay'], 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- DEDUCTIONS TABLE -->
                <div class="border border-gray-200 rounded-2xl overflow-hidden">
                    <div class="bg-gray-100 px-3.5 py-2 border-b border-gray-200 flex justify-between items-center">
                        <span class="text-xs font-black uppercase tracking-wider text-gray-800">Deductions</span>
                        <span class="text-[11px] text-gray-500 font-bold">Gov't, Loans & Fees</span>
                    </div>
                    <table class="w-full text-xs border-collapse">
                        <tbody class="divide-y divide-gray-100">
                            @if($payslip['deductions']['sss_deduction'] > 0)
                                <tr>
                                    <td class="py-1.5 px-3 text-gray-700">SSS Contribution (EE Share)</td>
                                    <td class="py-1.5 px-3 text-right font-mono font-bold text-rose-600">-PHP {{ number_format($payslip['deductions']['sss_deduction'], 2) }}</td>
                                </tr>
                            @endif
                            @if($payslip['deductions']['philhealth_deduction'] > 0)
                                <tr>
                                    <td class="py-1.5 px-3 text-gray-700">PhilHealth Premium (EE Share 2.5%)</td>
                                    <td class="py-1.5 px-3 text-right font-mono font-bold text-rose-600">-PHP {{ number_format($payslip['deductions']['philhealth_deduction'], 2) }}</td>
                                </tr>
                            @endif
                            @if($payslip['deductions']['pagibig_deduction'] > 0)
                                <tr>
                                    <td class="py-1.5 px-3 text-gray-700">Pag-IBIG HDMF (EE Share)</td>
                                    <td class="py-1.5 px-3 text-right font-mono font-bold text-rose-600">-PHP {{ number_format($payslip['deductions']['pagibig_deduction'], 2) }}</td>
                                </tr>
                            @endif
                            @if($payslip['deductions']['withholding_tax'] > 0)
                                <tr>
                                    <td class="py-1.5 px-3 text-gray-700">BIR Withholding Tax (TRAIN Law)</td>
                                    <td class="py-1.5 px-3 text-right font-mono font-bold text-rose-600">-PHP {{ number_format($payslip['deductions']['withholding_tax'], 2) }}</td>
                                </tr>
                            @endif
                            @if($payslip['deductions']['platform_fee_deduction'] > 0)
                                <tr>
                                    <td class="py-1.5 px-3 text-gray-700">TNC Platform Commission Fee (20%)</td>
                                    <td class="py-1.5 px-3 text-right font-mono font-bold text-rose-600">-PHP {{ number_format($payslip['deductions']['platform_fee_deduction'], 2) }}</td>
                                </tr>
                            @endif
                            @if($payslip['deductions']['loan_deduction'] > 0)
                                <tr>
                                    <td class="py-1.5 px-3 text-gray-700">Loan Amortization Deductions</td>
                                    <td class="py-1.5 px-3 text-right font-mono font-bold text-rose-600">-PHP {{ number_format($payslip['deductions']['loan_deduction'], 2) }}</td>
                                </tr>
                            @endif
                            @if($payslip['deductions']['tardiness_deduction'] > 0)
                                <tr>
                                    <td class="py-1.5 px-3 text-gray-700">Tardiness / Late Minutes Deduction</td>
                                    <td class="py-1.5 px-3 text-right font-mono font-bold text-rose-600">-PHP {{ number_format($payslip['deductions']['tardiness_deduction'], 2) }}</td>
                                </tr>
                            @endif
                            @if($payslip['deductions']['undertime_deduction'] > 0)
                                <tr>
                                    <td class="py-1.5 px-3 text-gray-700">Undertime Minutes Deduction</td>
                                    <td class="py-1.5 px-3 text-right font-mono font-bold text-rose-600">-PHP {{ number_format($payslip['deductions']['undertime_deduction'], 2) }}</td>
                                </tr>
                            @endif
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray-50 border-t border-gray-200 font-black">
                                <td class="py-2 px-3 text-rose-700">Total Deductions</td>
                                <td class="py-2 px-3 text-right font-mono text-rose-700 font-outfit text-sm">-PHP {{ number_format($payslip['deductions']['total_deductions'], 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

            </div>

            <!-- Net Take-Home Payout Highlight -->
            <div class="border-2 border-gray-900 p-4 rounded-2xl flex items-center justify-between bg-gray-50/80">
                <div>
                    <span class="text-xs font-bold text-gray-500 uppercase block">Net Take-Home Pay (Bank Settlement):</span>
                    <span class="text-[11px] text-gray-400 font-medium">Direct credit to employee payroll account / bank transfer</span>
                </div>
                <div class="text-2xl font-black font-outfit text-emerald-700">
                    PHP {{ number_format($payslip['net_pay'], 2) }}
                </div>
            </div>

            <!-- Over-the-Counter Cash Reimbursement Voucher (If applicable) -->
            @if(($payslip['earnings']['reimbursements'] ?? 0) > 0)
                <div class="p-3.5 bg-amber-50/70 border border-amber-200 rounded-2xl flex items-center justify-between text-xs">
                    <div>
                        <span class="font-black text-amber-950 uppercase tracking-wider block text-[11px]">Over-the-Counter Cash Reimbursement Voucher</span>
                        <span class="text-gray-600 text-[11px]">Approved work expense refund — Claim in Physical Cash via Cashier Counter</span>
                    </div>
                    <div class="text-right">
                        <span class="font-black font-outfit text-amber-900 text-sm">
                            PHP {{ number_format($payslip['earnings']['reimbursements'], 2) }}
                        </span>
                        <span class="text-[10px] font-bold text-amber-700 block uppercase">Cash Settlement</span>
                    </div>
                </div>
            @endif

            <!-- Itemized Active Loans Reference (if any) -->
            @if(count($payslip['itemized_loans']) > 0)
                <div class="border border-gray-200 rounded-xl p-3 bg-gray-50/30 text-xs space-y-1.5">
                    <span class="font-black text-gray-800 uppercase tracking-wider block text-[11px]">Active Loan Amortization Status & Remaining Balances</span>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        @foreach($payslip['itemized_loans'] as $loan)
                            <div class="border border-gray-200 p-2 rounded-lg bg-white">
                                <span class="font-bold text-gray-900 block">{{ $loan['loan_type'] }}</span>
                                <span class="text-[10px] text-gray-400 font-mono block">Ref: {{ $loan['reference_no'] }}</span>
                                <span class="text-xs font-bold text-rose-600 block mt-0.5">Remaining: PHP {{ number_format($loan['remaining_balance'], 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Employer Statutory Contributions (Company Gov't Burden) -->
            <div class="border border-blue-200 rounded-2xl p-4 bg-blue-50/40 text-xs space-y-2.5">
                <div class="flex justify-between items-center border-b border-blue-200 pb-2">
                    <span class="font-black text-blue-950 uppercase tracking-wider text-[11px]">Employer Statutory Contributions (Company Gov't Burden)</span>
                    <div class="flex items-center gap-2">
                        <span class="text-[11px] font-bold text-blue-800">Total Employer Burden: PHP {{ number_format($payslip['employer_contributions']['total_employer_burden'], 2) }}</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-blue-100 text-blue-900">Total CTC: PHP {{ number_format($payslip['earnings']['gross_pay'] + $payslip['employer_contributions']['total_employer_burden'], 2) }}</span>
                    </div>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-gray-700">
                    <div>
                        <span class="text-gray-500 block text-[10px]">SSS Employer:</span>
                        <span class="font-mono font-bold text-gray-900">PHP {{ number_format($payslip['employer_contributions']['sss_employer'], 2) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 block text-[10px]">PhilHealth Employer:</span>
                        <span class="font-mono font-bold text-gray-900">PHP {{ number_format($payslip['employer_contributions']['philhealth_employer'], 2) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 block text-[10px]">Pag-IBIG Employer:</span>
                        <span class="font-mono font-bold text-gray-900">PHP {{ number_format($payslip['employer_contributions']['pagibig_employer'], 2) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 block text-[10px]">EC Contribution:</span>
                        <span class="font-mono font-bold text-gray-900">PHP {{ number_format($payslip['employer_contributions']['ec_contribution'], 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Signature Blocks -->
            <div class="grid grid-cols-2 gap-8 pt-6 text-center text-xs">
                <div class="space-y-1">
                    <div class="border-b border-gray-900 pb-6"></div>
                    <span class="font-bold text-gray-900 block">{{ $payslip['full_name'] }}</span>
                    <span class="text-[10px] text-gray-400">Employee Acknowledgment</span>
                </div>
                <div class="space-y-1">
                    <div class="border-b border-gray-900 pb-6"></div>
                    <span class="font-bold text-gray-900 block">HR & Payroll Officer</span>
                    <span class="text-[10px] text-gray-400">Certified Correct</span>
                </div>
            </div>

        </div>

    </div>

</body>
</html>
