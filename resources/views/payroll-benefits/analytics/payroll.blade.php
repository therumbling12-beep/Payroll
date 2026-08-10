@extends('layouts.app')

@php
    $pageTitle = 'Payroll & Salary Reports';
    $currentPage = 'analytics.payroll';
@endphp

@section('content')

    <!-- Load Chart.js CDN for Analytics Charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-extrabold font-outfit text-gray-900">Company Salary Disbursement & Payroll Reports</h1>
            <p class="text-xs text-gray-500 mt-0.5">Historical breakdown of monthly payroll expenses, statutory contributions, and net payouts.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="flex items-center gap-1.5 text-xs font-semibold text-emerald-600 bg-emerald-50 border border-emerald-200 px-3 py-1.5 rounded-full">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                Payroll Stream Connected
            </span>
            <span class="text-xs text-gray-400">{{ now()->format('D, M j Y') }}</span>
        </div>
    </div>

    <!-- Monthly Summary Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-xs">
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Total Gross Salary Paid</span>
            <div class="flex items-center justify-between">
                <span class="text-2xl font-extrabold font-outfit text-gray-900">₱{{ number_format($totalGrossPay, 2) }}</span>
                <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md">Live Data</span>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-xs">
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Total Statutory & Tax Deductions</span>
            <div class="flex items-center justify-between">
                <span class="text-2xl font-extrabold font-outfit text-red-600">₱{{ number_format($totalDeductions, 2) }}</span>
                <span class="text-xs font-bold text-gray-500 bg-gray-100 px-2 py-0.5 rounded-md">Government Breakdown</span>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-xs">
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Net Disbursement</span>
            <div class="flex items-center justify-between">
                <span class="text-2xl font-extrabold font-outfit text-emerald-600">₱{{ number_format($totalNetPay, 2) }}</span>
                <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md">Active Payouts</span>
            </div>
        </div>
    </div>

    <!-- Statutory Government Contributions Breakdown -->
    <div class="bg-white rounded-2xl border border-gray-100 p-6 mb-8 shadow-sm">
        <h2 class="text-sm font-bold font-outfit text-gray-900 mb-4">Statutory Government Remittance Breakdown</h2>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <div class="p-4 bg-gray-50 rounded-xl border border-gray-100 space-y-1">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">SSS Contributions</span>
                <div class="text-lg font-extrabold text-blue-700">₱{{ number_format($totalSss, 2) }}</div>
                <span class="text-[10px] text-gray-500">Capped at MSC ₱30k Limit</span>
            </div>

            <div class="p-4 bg-gray-50 rounded-xl border border-gray-100 space-y-1">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">PhilHealth Premium</span>
                <div class="text-lg font-extrabold text-emerald-700">₱{{ number_format($totalPhilhealth, 2) }}</div>
                <span class="text-[10px] text-gray-500">2.5% Employee Share</span>
            </div>

            <div class="p-4 bg-gray-50 rounded-xl border border-gray-100 space-y-1">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Pag-IBIG HDMF</span>
                <div class="text-lg font-extrabold text-purple-700">₱{{ number_format($totalPagibig, 2) }}</div>
                <span class="text-[10px] text-gray-500">₱200 Flat Standard</span>
            </div>

            <div class="p-4 bg-gray-50 rounded-xl border border-gray-100 space-y-1">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">BIR Withholding Tax</span>
                <div class="text-lg font-extrabold text-[#F44336]">₱{{ number_format($totalWithholdingTax, 2) }}</div>
                <span class="text-[10px] text-gray-500">20% Taxable Above ₱20,833 Limit</span>
            </div>
        </div>
    </div>

    <!-- Salary Component Breakdown Bar Chart -->
    <div class="bg-white rounded-2xl border border-gray-100 p-6 mb-8 shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-4">
            <div>
                <h2 class="text-sm font-bold font-outfit text-gray-900">Government Remittance vs Net Payout Breakdown</h2>
                <p class="text-[10px] text-gray-400">Proportional comparison of statutory deductions vs employee net pay</p>
            </div>
        </div>

        <div class="relative h-64 w-full">
            <canvas id="payrollTrendChart"></canvas>
        </div>
    </div>

    <!-- Chart.js Script Initialization -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const ctx = document.getElementById('payrollTrendChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['SSS Remittance', 'PhilHealth Remittance', 'Pag-IBIG HDMF', 'BIR Withholding Tax', 'Total Net Pay Released'],
                    datasets: [{
                        label: 'Amount (₱)',
                        data: [
                            {{ $totalSss }},
                            {{ $totalPhilhealth }},
                            {{ $totalPagibig }},
                            {{ $totalWithholdingTax }},
                            {{ $totalNetPay }}
                        ],
                        backgroundColor: [
                            '#3B82F6', // SSS Blue
                            '#10B981', // PhilHealth Green
                            '#8B5CF6', // PagIBIG Purple
                            '#F44336', // BIR Tax Red
                            '#059669'  // Net Pay Emerald
                        ],
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        });
    </script>

@endsection
