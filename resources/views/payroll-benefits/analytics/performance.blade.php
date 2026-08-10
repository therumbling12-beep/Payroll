@extends('layouts.app')

@php
    $pageTitle = 'Employee Performance Analytics';
    $currentPage = 'analytics.performance';
@endphp

@section('content')

    <!-- Load Chart.js CDN for Analytics Charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-extrabold font-outfit text-gray-900">Employee Performance Reporting</h1>
            <p class="text-xs text-gray-500 mt-0.5">Macro overview highlighting top trip earners, rating distributions, and workforce efficiency issues.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="flex items-center gap-1.5 text-xs font-semibold text-emerald-600 bg-emerald-50 border border-emerald-200 px-3 py-1.5 rounded-full">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                Performance Stream Live
            </span>
            <span class="text-xs text-gray-400">{{ now()->format('D, M j Y') }}</span>
        </div>
    </div>

    <!-- Summary Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-xs">
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Total Workforce</span>
            <div class="text-2xl font-extrabold font-outfit text-gray-900">{{ $totalEmployees }} Staff</div>
            <span class="text-[10px] font-semibold text-gray-500 mt-1 block">{{ $totalDrivers }} Drivers • {{ $totalStaff }} Office Staff</span>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-xs">
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Total Trip Earnings</span>
            <div class="text-2xl font-extrabold font-outfit text-emerald-600">₱{{ number_format($totalTripEarnings, 2) }}</div>
            <span class="text-[10px] font-semibold text-emerald-600 mt-1 block">Active Cutoff Deliveries</span>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-xs">
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Avg Driver Earnings</span>
            <div class="text-2xl font-extrabold font-outfit text-blue-600">₱{{ number_format($avgTripEarningsPerDriver, 2) }}</div>
            <span class="text-[10px] font-semibold text-blue-600 mt-1 block">Per Active Driver</span>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-xs">
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Performance Bonuses</span>
            <div class="text-2xl font-extrabold font-outfit text-purple-600">₱{{ number_format($totalPerformanceBonuses, 2) }}</div>
            <span class="text-[10px] font-semibold text-purple-600 mt-1 block">Qualified Merit Pools</span>
        </div>
    </div>

    <!-- Chart & Summary Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        
        <!-- Performance Distribution Chart (Left 2 cols) -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-4">
                <div>
                    <h2 class="text-sm font-bold font-outfit text-gray-900">Workforce Category Breakdown</h2>
                    <p class="text-[10px] text-gray-400">Distribution of drivers vs administrative staff</p>
                </div>
                <span class="text-xs font-bold text-gray-500 bg-gray-100 px-3 py-1 rounded-full">{{ now()->format('M Y') }}</span>
            </div>

            <!-- Canvas for Chart.js -->
            <div class="relative h-64 w-full">
                <canvas id="performanceChart"></canvas>
            </div>
        </div>

        <!-- Top Drivers Summary Card (Right 1 col) -->
        <div class="space-y-4">
            <!-- Top Performers Box -->
            <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm space-y-3">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold font-outfit uppercase tracking-wider text-emerald-600">Top Fleet Earners</span>
                    <span class="text-[10px] font-bold bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-md">Live Stream</span>
                </div>
                
                <div class="p-3 bg-gray-50 rounded-xl space-y-1">
                    <div class="text-xs font-bold text-gray-900">Juan Dela Cruz (Senior Driver)</div>
                    <div class="text-[10px] text-emerald-600 font-bold">₱18,450.00 Trip Income • 4.9 ★ Rating</div>
                </div>

                <div class="p-3 bg-gray-50 rounded-xl space-y-1">
                    <div class="text-xs font-bold text-gray-900">Elena Rostova (Dispatcher Lead)</div>
                    <div class="text-[10px] text-blue-600 font-bold">99.2% On-Time Dispatch Rate</div>
                </div>
            </div>
        </div>

    </div>

    <!-- Chart.js Script Initialization -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const ctx = document.getElementById('performanceChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Drivers (Active Delivery)', 'Support & Operations Staff'],
                    datasets: [{
                        label: 'Employee Count',
                        data: [{{ $totalDrivers }}, {{ $totalStaff }}],
                        backgroundColor: [
                            '#10B981', // Emerald
                            '#3B82F6'  // Blue
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
