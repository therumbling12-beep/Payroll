@extends('layouts.app')

@php
    $pageTitle = 'Cost & Budget Overview';
    $currentPage = 'analytics.budget';
@endphp

@section('content')

    <!-- Load Chart.js CDN for Analytics Charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-extrabold font-outfit text-gray-900">Company Benefits & Cost Overview</h1>
            <p class="text-xs text-gray-500 mt-0.5">High-level financial overview of company funds allocated across HMO, Driver Claims, and Requisitions.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="flex items-center gap-1.5 text-xs font-semibold text-blue-600 bg-blue-50 border border-blue-200 px-3 py-1.5 rounded-full">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                Financial Team 5 Stream Synced
            </span>
            <span class="text-xs text-gray-400">{{ now()->format('D, M j Y') }}</span>
        </div>
    </div>

    <!-- Summary Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-xs">
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Approved Requisition Budget</span>
            <div class="flex items-center justify-between">
                <span class="text-2xl font-extrabold font-outfit text-emerald-600">₱{{ number_format($totalApprovedBudget, 2) }}</span>
                <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md">Released</span>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-xs">
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Pending Budget Requisitions</span>
            <div class="flex items-center justify-between">
                <span class="text-2xl font-extrabold font-outfit text-amber-600">₱{{ number_format($totalPendingBudget, 2) }}</span>
                <span class="text-xs font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md">Awaiting Team 5</span>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-xs">
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Enrolled HMO Members</span>
            <div class="flex items-center justify-between">
                <span class="text-2xl font-extrabold font-outfit text-blue-600">{{ $totalHmoEnrolled }} Employees</span>
                <span class="text-xs font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-md">Active Coverage</span>
            </div>
        </div>
    </div>

    <!-- Main Grid: Doughnut Chart Left, Breakdown Cards Right -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        
        <!-- Budget Allocation Doughnut Chart (Left 2 cols) -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-4">
                <div>
                    <h2 class="text-sm font-bold font-outfit text-gray-900">Total Benefits & Assistance Fund Distribution</h2>
                    <p class="text-[10px] text-gray-400">Proportional budget utilization across company benefit categories</p>
                </div>
            </div>

            <!-- Canvas for Chart.js Doughnut -->
            <div class="relative h-64 w-full flex items-center justify-center">
                <canvas id="budgetsDoughnutChart"></canvas>
            </div>
        </div>

        <!-- Budget Breakdown Summary (Right 1 col) -->
        <div class="space-y-4">
            <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Approved Requisitions</span>
                <span class="text-xl font-extrabold font-outfit text-gray-900">₱{{ number_format($totalApprovedBudget, 2) }}</span>
                <span class="text-xs text-gray-400 block mt-1">Transmitted to Team 5</span>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Accumulated Driver Reserve Fund</span>
                <span class="text-xl font-extrabold font-outfit text-emerald-600">₱{{ number_format($totalDriverAccidentFund, 2) }}</span>
                <span class="text-xs text-gray-400 block mt-1">From 3% payroll contributions</span>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Accident Emergency Payouts</span>
                <span class="text-xl font-extrabold font-outfit text-purple-600">₱{{ number_format($totalAccidentPayouts, 2) }}</span>
                <span class="text-xs text-gray-400 block mt-1">Hospital bill coverage disbursements</span>
            </div>
        </div>

    </div>

    <!-- Chart.js Script Initialization -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const ctx = document.getElementById('budgetsDoughnutChart').getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Approved Requisition Budget', 'Driver Fleet Reserve Fund', 'Emergency Accident Payouts'],
                    datasets: [{
                        data: [
                            {{ $totalApprovedBudget > 0 ? $totalApprovedBudget : 100000 }},
                            {{ $totalDriverAccidentFund > 0 ? $totalDriverAccidentFund : 50000 }},
                            {{ $totalAccidentPayouts > 0 ? $totalAccidentPayouts : 25000 }}
                        ],
                        backgroundColor: [
                            '#3B82F6', // Blue
                            '#10B981', // Emerald
                            '#8B5CF6'  // Purple
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        });
    </script>

@endsection
