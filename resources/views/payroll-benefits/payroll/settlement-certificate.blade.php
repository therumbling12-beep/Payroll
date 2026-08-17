<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Final Pay Settlement & Release Quitclaim - {{ $item->employee?->last_name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; font-size: 12pt; }
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-900 font-sans p-6">

    <div class="max-w-3xl mx-auto space-y-6">

        <!-- Print Action Header -->
        <div class="no-print flex items-center justify-between bg-white p-4 rounded-2xl border border-gray-200 shadow-sm">
            <span class="text-xs font-bold text-gray-500">DOLE-Compliant Final Pay & Quitclaim Certificate</span>
            <div class="flex items-center gap-2">
                <button onclick="window.print()" class="bg-gray-900 hover:bg-black text-white text-xs font-black px-4 py-2 rounded-xl transition-all">
                    Print / Save as PDF
                </button>
                <button onclick="window.close()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-bold px-4 py-2 rounded-xl transition-all">
                    Close
                </button>
            </div>
        </div>

        <!-- Official Certificate Sheet -->
        <div class="bg-white p-10 rounded-3xl border border-gray-200 shadow-sm space-y-6">
            
            <!-- Company Letterhead -->
            <div class="border-b-2 border-gray-900 pb-4 text-center">
                <h1 class="text-xl font-black tracking-wider uppercase text-gray-900">TRIPWISE TRANSPORT & LOGISTICS INC.</h1>
                <p class="text-xs text-gray-500 mt-0.5">National Capital Region, Philippines • DOLE Compliance Reference: LA-06-20</p>
                <h2 class="text-sm font-black uppercase text-gray-800 mt-3 tracking-wide">FINAL PAY SETTLEMENT STATEMENT & RELEASE OF CLAIMS</h2>
            </div>

            <!-- Employee Info -->
            @php $emp = $item->employee; @endphp
            <div class="grid grid-cols-2 gap-4 text-xs border border-gray-200 p-4 rounded-xl bg-gray-50/50">
                <div>
                    <span class="text-gray-400 font-medium block">Employee Name:</span>
                    <span class="font-black text-gray-900 text-sm uppercase">{{ $emp?->last_name }}, {{ $emp?->first_name }}</span>
                </div>
                <div>
                    <span class="text-gray-400 font-medium block">Employee Code / TIN:</span>
                    <span class="font-mono font-bold text-gray-800">{{ $emp?->employee_code }} • TIN: 000-000-000-000</span>
                </div>
                <div>
                    <span class="text-gray-400 font-medium block">Department & Position:</span>
                    <span class="font-bold text-gray-800">{{ $emp?->department?->name ?? 'General' }} • {{ $emp?->position }}</span>
                </div>
                <div>
                    <span class="text-gray-400 font-medium block">Settlement Batch & Date:</span>
                    <span class="font-mono font-bold text-gray-800">{{ $item->offCyclePayroll?->run_number }} • {{ $item->offCyclePayroll?->payout_date?->format('F d, Y') }}</span>
                </div>
            </div>

            <!-- Itemized Earnings & Deductions Breakdown -->
            <div class="space-y-2">
                <h3 class="text-xs font-black uppercase tracking-wider text-gray-700">1. Statement of Final Earnings</h3>
                <table class="w-full text-xs border-collapse border border-gray-200">
                    <thead class="bg-gray-100 font-bold text-gray-700">
                        <tr>
                            <th class="border border-gray-200 py-2 px-3 text-left">Compensation Item</th>
                            <th class="border border-gray-200 py-2 px-3 text-right">Amount (PHP)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr>
                            <td class="border border-gray-200 py-2 px-3">Unpaid Regular Basic Wages (Final Cutoff Days)</td>
                            <td class="border border-gray-200 py-2 px-3 text-right font-mono font-bold">{{ number_format((float)$item->basic_pay_earned, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="border border-gray-200 py-2 px-3">Pro-rated 13th Month Pay (Jan 1 to Separation Date)</td>
                            <td class="border border-gray-200 py-2 px-3 text-right font-mono font-bold">{{ number_format((float)$item->pro_rated_13th_month, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="border border-gray-200 py-2 px-3">Unused Service Incentive Leave (SIL) Monetization</td>
                            <td class="border border-gray-200 py-2 px-3 text-right font-mono font-bold">{{ number_format((float)$item->leave_conversion_pay, 2) }}</td>
                        </tr>
                        @if((float)$item->reimbursements > 0)
                            <tr>
                                <td class="border border-gray-200 py-2 px-3">Approved Non-Taxable Reimbursements</td>
                                <td class="border border-gray-200 py-2 px-3 text-right font-mono font-bold">{{ number_format((float)$item->reimbursements, 2) }}</td>
                            </tr>
                        @endif
                        <tr class="bg-gray-50 font-black">
                            <td class="border border-gray-200 py-2 px-3 text-gray-900">Total Gross Final Settlement</td>
                            <td class="border border-gray-200 py-2 px-3 text-right font-mono text-gray-900">PHP {{ number_format((float)$item->gross_amount, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="space-y-2">
                <h3 class="text-xs font-black uppercase tracking-wider text-gray-700">2. Statement of Deductions & Loan Offsets</h3>
                <table class="w-full text-xs border-collapse border border-gray-200">
                    <thead class="bg-gray-100 font-bold text-gray-700">
                        <tr>
                            <th class="border border-gray-200 py-2 px-3 text-left">Deduction Item</th>
                            <th class="border border-gray-200 py-2 px-3 text-right">Amount (PHP)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr>
                            <td class="border border-gray-200 py-2 px-3">Active SSS/HDMF/Company Loan Balance Offsets</td>
                            <td class="border border-gray-200 py-2 px-3 text-right font-mono font-bold text-rose-600">-{{ number_format((float)$item->loan_deduction, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="border border-gray-200 py-2 px-3">Company Asset / Liability Clearance Deductions</td>
                            <td class="border border-gray-200 py-2 px-3 text-right font-mono font-bold text-rose-600">-{{ number_format((float)$item->other_deductions, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="border border-gray-200 py-2 px-3">BIR Withholding Tax Reconciliation</td>
                            <td class="border border-gray-200 py-2 px-3 text-right font-mono font-bold text-gray-700">-{{ number_format((float)$item->withholding_tax, 2) }}</td>
                        </tr>
                        <tr class="bg-gray-50 font-black">
                            <td class="border border-gray-200 py-2 px-3 text-rose-700">Total Settlement Deductions</td>
                            <td class="border border-gray-200 py-2 px-3 text-right font-mono text-rose-700">-PHP {{ number_format((float)$item->total_deductions, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Net Final Payout Highlight -->
            <div class="border-2 border-gray-900 p-4 rounded-2xl flex items-center justify-between bg-gray-50">
                <div>
                    <span class="text-xs font-bold text-gray-500 uppercase block">Net Payout Check / Bank Credit:</span>
                    <span class="text-xs text-gray-400 font-medium">Final settlement amount in full satisfaction of all labor claims</span>
                </div>
                <div class="text-xl font-black font-mono text-emerald-700">
                    PHP {{ number_format((float)$item->net_settlement_pay, 2) }}
                </div>
            </div>

            <!-- Release, Waiver and Quitclaim Statement -->
            <div class="text-[11px] text-gray-600 text-justify leading-relaxed border border-gray-200 p-4 rounded-xl space-y-2">
                <p>
                    <strong>RELEASE, WAIVER AND QUITCLAIM:</strong> I acknowledge receipt of the sum of 
                    <strong class="font-mono text-gray-900">PHP {{ number_format((float)$item->net_settlement_pay, 2) }}</strong> 
                    representing full and complete settlement of all salaries, 13th month pay, leave conversions, benefits, and claims due to me from TripWise Transport & Logistics Inc. by reason of my separation from employment.
                </p>
                <p>
                    I hereby release and forever discharge TripWise Transport & Logistics Inc., its officers, and agents from any and all claims, actions, or liabilities under the Philippine Labor Code.
                </p>
            </div>

            <!-- Signature Blocks -->
            <div class="grid grid-cols-3 gap-6 pt-8 text-center text-xs">
                <div class="space-y-1">
                    <div class="border-b border-gray-900 pb-8"></div>
                    <span class="font-bold text-gray-900 block">{{ $emp?->first_name }} {{ $emp?->last_name }}</span>
                    <span class="text-[10px] text-gray-400">Employee Signature & Date</span>
                </div>
                <div class="space-y-1">
                    <div class="border-b border-gray-900 pb-8"></div>
                    <span class="font-bold text-gray-900 block">HR & Legal Officer</span>
                    <span class="text-[10px] text-gray-400">Verified & Approved</span>
                </div>
                <div class="space-y-1">
                    <div class="border-b border-gray-900 pb-8"></div>
                    <span class="font-bold text-gray-900 block">Finance / Cashier</span>
                    <span class="text-[10px] text-gray-400">Disbursed & Recorded</span>
                </div>
            </div>

        </div>

    </div>

</body>
</html>
