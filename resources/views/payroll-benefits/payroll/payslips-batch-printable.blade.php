<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batch Payslips - Cutoff {{ $cutoff }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; font-size: 10.5pt; }
            .payslip-page {
                page-break-after: always;
                break-after: page;
                margin-bottom: 0 !important;
                border: none !important;
                box-shadow: none !important;
            }
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-900 font-sans p-6">

    <!-- Top Action Header -->
    <div class="no-print max-w-4xl mx-auto mb-6 flex items-center justify-between bg-white p-4 rounded-2xl border border-gray-200 shadow-sm">
        <div class="flex items-center gap-2 text-xs text-gray-500 font-bold">
            <span>Batch Payslip Print Center</span>
            <span>•</span>
            <span class="text-gray-900 font-mono">{{ $cutoff }}</span>
            <span>•</span>
            <span class="text-blue-700">{{ $batchPayslips->count() }} Payslips Ready</span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="bg-gray-900 hover:bg-black text-white text-xs font-black px-4 py-2 rounded-xl transition-all shadow-xs">
                Print All / Save Full Batch PDF
            </button>
            <button onclick="window.close()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-bold px-4 py-2 rounded-xl transition-all">
                Close
            </button>
        </div>
    </div>

    <!-- Batch Payslips Container -->
    <div class="max-w-4xl mx-auto space-y-8">
        @foreach($batchPayslips as $payslip)
            <div class="payslip-page bg-white p-8 rounded-3xl border border-gray-200 shadow-sm space-y-4">
                
                <!-- Header -->
                <div class="border-b-2 border-gray-900 pb-3 flex justify-between items-center">
                    <div>
                        <h1 class="text-base font-black tracking-wider uppercase text-gray-900">TRIPWISE TRANSPORT & LOGISTICS INC.</h1>
                        <p class="text-[11px] text-gray-500">TripWise TNVS Payroll & Benefits System • DOLE Advisory No. 06-20</p>
                    </div>
                    <div class="text-right">
                        <span class="text-xs font-black uppercase text-gray-900 block">EMPLOYEE PAYSLIP</span>
                        <span class="text-[11px] text-gray-500 font-mono font-bold">Cutoff: {{ $payslip['cutoff_period'] }}</span>
                    </div>
                </div>

                <!-- Info -->
                <div class="grid grid-cols-4 gap-2 text-xs border border-gray-200 p-3 rounded-xl bg-gray-50/50">
                    <div>
                        <span class="text-gray-400 block text-[10px]">Employee Name:</span>
                        <span class="font-black text-gray-900 uppercase">{{ $payslip['full_name'] }}</span>
                    </div>
                    <div>
                        <span class="text-gray-400 block text-[10px]">Employee Code:</span>
                        <span class="font-mono font-bold text-gray-800">{{ $payslip['employee_code'] }}</span>
                    </div>
                    <div>
                        <span class="text-gray-400 block text-[10px]">Position & Dept:</span>
                        <span class="font-bold text-gray-800">{{ $payslip['position'] }} • {{ $payslip['department'] }}</span>
                    </div>
                    <div>
                        <span class="text-gray-400 block text-[10px]">Payment Mode:</span>
                        <span class="font-bold text-gray-800">
                            {{ $payslip['payment_mode'] === 'bank' ? 'SBC (' . $payslip['bank_account_number'] . ')' : 'Cash' }}
                        </span>
                    </div>
                </div>

                <!-- Side by Side Tables -->
                <div class="grid grid-cols-2 gap-3">
                    <!-- Earnings -->
                    <div class="border border-gray-200 rounded-xl overflow-hidden text-xs">
                        <div class="bg-gray-100 px-3 py-1 font-black text-gray-800 uppercase text-[11px]">Gross Earnings</div>
                        <table class="w-full">
                            <tbody class="divide-y divide-gray-100">
                                @if($payslip['earnings']['base_pay'] > 0)
                                    <tr>
                                        <td class="py-1 px-3 text-gray-700">Basic Pay</td>
                                        <td class="py-1 px-3 text-right font-mono font-bold">PHP {{ number_format($payslip['earnings']['base_pay'], 2) }}</td>
                                    </tr>
                                @endif
                                @if($payslip['earnings']['trip_earnings'] > 0)
                                    <tr>
                                        <td class="py-1 px-3 text-gray-700">Trip Earnings</td>
                                        <td class="py-1 px-3 text-right font-mono font-bold text-blue-700">PHP {{ number_format($payslip['earnings']['trip_earnings'], 2) }}</td>
                                    </tr>
                                @endif
                                @if($payslip['earnings']['driver_trip_incentive'] > 0)
                                    <tr>
                                        <td class="py-1 px-3 text-gray-700">Trip Quota Incentive</td>
                                        <td class="py-1 px-3 text-right font-mono font-bold text-emerald-700">PHP {{ number_format($payslip['earnings']['driver_trip_incentive'], 2) }}</td>
                                    </tr>
                                @endif
                                @if($payslip['earnings']['holiday_pay'] > 0)
                                    <tr>
                                        <td class="py-1 px-3 text-gray-700">Holiday Pay</td>
                                        <td class="py-1 px-3 text-right font-mono font-bold text-purple-700">PHP {{ number_format($payslip['earnings']['holiday_pay'], 2) }}</td>
                                    </tr>
                                @endif
                                @if($payslip['earnings']['overtime_pay'] > 0)
                                    <tr>
                                        <td class="py-1 px-3 text-gray-700">Overtime Pay</td>
                                        <td class="py-1 px-3 text-right font-mono font-bold text-blue-800">PHP {{ number_format($payslip['earnings']['overtime_pay'], 2) }}</td>
                                    </tr>
                                @endif
                                @if($payslip['earnings']['night_diff_pay'] > 0)
                                    <tr>
                                        <td class="py-1 px-3 text-gray-700">Night Differential</td>
                                        <td class="py-1 px-3 text-right font-mono font-bold text-indigo-700">PHP {{ number_format($payslip['earnings']['night_diff_pay'], 2) }}</td>
                                    </tr>
                                @endif
                                @if($payslip['earnings']['performance_bonus'] > 0)
                                    <tr>
                                        <td class="py-1 px-3 text-gray-700">Bonus / Incentives</td>
                                        <td class="py-1 px-3 text-right font-mono font-bold text-amber-700">PHP {{ number_format($payslip['earnings']['performance_bonus'], 2) }}</td>
                                    </tr>
                                @endif
                                @if($payslip['earnings']['reimbursements'] > 0)
                                    <tr>
                                        <td class="py-1 px-3 text-gray-700">Reimbursements</td>
                                        <td class="py-1 px-3 text-right font-mono font-bold text-emerald-800">PHP {{ number_format($payslip['earnings']['reimbursements'], 2) }}</td>
                                    </tr>
                                @endif
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-50 border-t border-gray-200 font-black">
                                    <td class="py-1.5 px-3">Total Gross</td>
                                    <td class="py-1.5 px-3 text-right font-mono font-outfit">PHP {{ number_format($payslip['earnings']['gross_pay'], 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Deductions -->
                    <div class="border border-gray-200 rounded-xl overflow-hidden text-xs">
                        <div class="bg-gray-100 px-3 py-1 font-black text-gray-800 uppercase text-[11px]">Deductions</div>
                        <table class="w-full">
                            <tbody class="divide-y divide-gray-100">
                                @if($payslip['deductions']['sss_deduction'] > 0)
                                    <tr>
                                        <td class="py-1 px-3 text-gray-700">SSS EE Share</td>
                                        <td class="py-1 px-3 text-right font-mono font-bold text-rose-600">-PHP {{ number_format($payslip['deductions']['sss_deduction'], 2) }}</td>
                                    </tr>
                                @endif
                                @if($payslip['deductions']['philhealth_deduction'] > 0)
                                    <tr>
                                        <td class="py-1 px-3 text-gray-700">PhilHealth EE Share</td>
                                        <td class="py-1 px-3 text-right font-mono font-bold text-rose-600">-PHP {{ number_format($payslip['deductions']['philhealth_deduction'], 2) }}</td>
                                    </tr>
                                @endif
                                @if($payslip['deductions']['pagibig_deduction'] > 0)
                                    <tr>
                                        <td class="py-1 px-3 text-gray-700">Pag-IBIG EE Share</td>
                                        <td class="py-1 px-3 text-right font-mono font-bold text-rose-600">-PHP {{ number_format($payslip['deductions']['pagibig_deduction'], 2) }}</td>
                                    </tr>
                                @endif
                                @if($payslip['deductions']['withholding_tax'] > 0)
                                    <tr>
                                        <td class="py-1 px-3 text-gray-700">BIR Tax (TRAIN)</td>
                                        <td class="py-1 px-3 text-right font-mono font-bold text-rose-600">-PHP {{ number_format($payslip['deductions']['withholding_tax'], 2) }}</td>
                                    </tr>
                                @endif
                                @if($payslip['deductions']['hmo_insurance_deduction'] > 0)
                                    <tr>
                                        <td class="py-1 px-3 text-gray-700">HMO Insurance</td>
                                        <td class="py-1 px-3 text-right font-mono font-bold text-rose-600">-PHP {{ number_format($payslip['deductions']['hmo_insurance_deduction'], 2) }}</td>
                                    </tr>
                                @endif
                                @if($payslip['deductions']['platform_fee_deduction'] > 0)
                                    <tr>
                                        <td class="py-1 px-3 text-gray-700">TNC Commission (20%)</td>
                                        <td class="py-1 px-3 text-right font-mono font-bold text-rose-600">-PHP {{ number_format($payslip['deductions']['platform_fee_deduction'], 2) }}</td>
                                    </tr>
                                @endif
                                @if($payslip['deductions']['loan_deduction'] > 0)
                                    <tr>
                                        <td class="py-1 px-3 text-gray-700">Loan Amortizations</td>
                                        <td class="py-1 px-3 text-right font-mono font-bold text-rose-600">-PHP {{ number_format($payslip['deductions']['loan_deduction'], 2) }}</td>
                                    </tr>
                                @endif
                                @if($payslip['deductions']['tardiness_deduction'] > 0 || $payslip['deductions']['undertime_deduction'] > 0)
                                    <tr>
                                        <td class="py-1 px-3 text-gray-700">Tardiness / Undertime</td>
                                        <td class="py-1 px-3 text-right font-mono font-bold text-rose-600">-PHP {{ number_format($payslip['deductions']['tardiness_deduction'] + $payslip['deductions']['undertime_deduction'], 2) }}</td>
                                    </tr>
                                @endif
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-50 border-t border-gray-200 font-black">
                                    <td class="py-1.5 px-3 text-rose-700">Total Deductions</td>
                                    <td class="py-1.5 px-3 text-right font-mono text-rose-700 font-outfit">-PHP {{ number_format($payslip['deductions']['total_deductions'], 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Net Take-Home Payout -->
                <div class="border-2 border-gray-900 p-3 rounded-xl flex justify-between items-center bg-gray-50">
                    <span class="text-xs font-black uppercase text-gray-800">Net Take-Home Pay</span>
                    <span class="text-xl font-black font-outfit text-emerald-700">PHP {{ number_format($payslip['net_pay'], 2) }}</span>
                </div>

                <!-- Mandatory Employer Contributions Transparency -->
                <div class="border border-gray-200 rounded-lg p-2 bg-blue-50/20 text-[10px] grid grid-cols-4 gap-2 text-gray-600">
                    <div>SSS ER: <strong class="text-gray-900">PHP {{ number_format($payslip['employer_contributions']['sss_employer'], 2) }}</strong></div>
                    <div>PhilHealth ER: <strong class="text-gray-900">PHP {{ number_format($payslip['employer_contributions']['philhealth_employer'], 2) }}</strong></div>
                    <div>Pag-IBIG ER: <strong class="text-gray-900">PHP {{ number_format($payslip['employer_contributions']['pagibig_employer'], 2) }}</strong></div>
                    <div>EC: <strong class="text-gray-900">PHP {{ number_format($payslip['employer_contributions']['ec_contribution'], 2) }}</strong></div>
                </div>

                <!-- Signatures -->
                <div class="grid grid-cols-2 gap-8 pt-3 text-center text-[10px]">
                    <div class="border-t border-gray-400 pt-1 text-gray-500">Employee Signature</div>
                    <div class="border-t border-gray-400 pt-1 text-gray-500">Payroll Officer Verification</div>
                </div>

            </div>
        @endforeach
    </div>

</body>
</html>
