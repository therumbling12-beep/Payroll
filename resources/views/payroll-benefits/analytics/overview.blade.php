@extends('layouts.app')

@php
    $pageTitle = 'Executive Analytics Overview';
    $currentPage = 'analytics.overview';
@endphp

@section('content')

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-extrabold font-outfit text-gray-900">Executive Analytics Overview</h1>
            <p class="text-xs text-gray-500 mt-0.5">Unified dashboard summarizing company-wide headcount, payroll expenses, healthcare coverage, and claims disbursements.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="flex items-center gap-1.5 text-xs font-semibold text-emerald-600 bg-emerald-50 border border-emerald-200 px-3 py-1.5 rounded-full">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                Executive Feed Live
            </span>
            <span class="text-xs text-gray-400 font-medium">{{ now()->format('D, M j, Y') }}</span>
        </div>
    </div>

    <!-- Executive Stat Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        
        <!-- Total Workforce -->
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-xs transition-all hover:shadow-md">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Active Workforce</span>
                <span class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </span>
            </div>
            <div class="text-2xl font-black font-outfit text-gray-900">{{ $totalEmployees }} Personnel</div>
            <p class="text-[11px] text-gray-500 mt-1 font-medium">{{ $totalDrivers }} Drivers • {{ $totalStaff }} Office Staff</p>
        </div>

        <!-- Gross Payroll -->
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-xs transition-all hover:shadow-md">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Cumulative Gross Payroll</span>
                <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>
            <div class="text-2xl font-black font-outfit text-emerald-600">PHP {{ number_format($totalGrossPayroll, 2) }}</div>
            <p class="text-[11px] text-gray-500 mt-1 font-medium">Net Take-Home: PHP {{ number_format($totalNetPayroll, 2) }}</p>
        </div>

        <!-- Driver Insurance Pool Enrolled -->
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-xs transition-all hover:shadow-md">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Driver Insurance Pool</span>
                <span class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold text-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </span>
            </div>
            <div class="text-2xl font-black font-outfit text-purple-600">{{ $driverPoolEnrolled }} Active Drivers</div>
            <p class="text-[11px] text-gray-500 mt-1 font-medium">Accident & emergency relief pool active</p>
        </div>

        <!-- Total Claims Disbursed -->
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-xs transition-all hover:shadow-md">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Claims Disbursed</span>
                <span class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </span>
            </div>
            <div class="text-2xl font-black font-outfit text-amber-600">PHP {{ number_format($totalClaimsDisbursed, 2) }}</div>
            <p class="text-[11px] text-gray-500 mt-1 font-medium">{{ $pendingClaimsCount }} Claims Awaiting Verification</p>
        </div>

    </div>

    <!-- Sub-Module Executive Deep-Dive Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Performance Analytics Card -->
        <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-xs flex flex-col justify-between space-y-4">
            <div class="space-y-2">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
                <h3 class="text-base font-extrabold font-outfit text-gray-900">Performance Analytics</h3>
                <p class="text-xs text-gray-500">Track driver trip earnings, milestone rewards, and attendance compliance rankings.</p>
            </div>
            <a href="{{ route('analytics.performance') }}" 
               class="inline-flex items-center justify-center gap-2 w-full py-2.5 px-4 bg-gray-900 hover:bg-black text-white text-xs font-bold rounded-xl transition-all shadow-xs">
                Inspect Performance Roster
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

        <!-- Payroll Cost Analytics Card -->
        <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-xs flex flex-col justify-between space-y-4">
            <div class="space-y-2">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <h3 class="text-base font-extrabold font-outfit text-gray-900">Payroll Cost & Statutory</h3>
                <p class="text-xs text-gray-500">Monitor SSS, PhilHealth, Pag-IBIG contributions, and BIR withholding tax disbursements.</p>
            </div>
            <a href="{{ route('analytics.payroll') }}" 
               class="inline-flex items-center justify-center gap-2 w-full py-2.5 px-4 bg-gray-900 hover:bg-black text-white text-xs font-bold rounded-xl transition-all shadow-xs">
                View Payroll Breakdown
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

        <!-- Budget & Benefits Card -->
        <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-xs flex flex-col justify-between space-y-4">
            <div class="space-y-2">
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <h3 class="text-base font-extrabold font-outfit text-gray-900">Budget & Benefit Requests</h3>
                <p class="text-xs text-gray-500">Audit company medical allocations, accident fund balances, and fund requisitions.</p>
            </div>
            <a href="{{ route('analytics.budget') }}" 
               class="inline-flex items-center justify-center gap-2 w-full py-2.5 px-4 bg-gray-900 hover:bg-black text-white text-xs font-bold rounded-xl transition-all shadow-xs">
                Audit Budget Requests
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

    </div>

@endsection
