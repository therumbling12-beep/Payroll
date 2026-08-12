{{-- Extends the app layout which includes sidebar & headbar --}}
@extends('layouts.app')

@php
    $pageTitle = 'Operations Dashboard';
    $currentPage = 'dashboard';
@endphp

@section('content')

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-extrabold font-outfit text-gray-900">Operations Command Center</h1>
            <p class="text-xs text-gray-500 mt-0.5">Welcome back, Super Admin — here is your TripWise cluster overview for today.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="flex items-center gap-1.5 text-xs font-semibold text-emerald-600 bg-emerald-50 border border-emerald-200 px-3 py-1.5 rounded-full">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                All Cluster Systems Live
            </span>
            <span class="text-xs text-gray-400">{{ now()->format('D, M j Y') }}</span>
        </div>
    </div>

    <!-- Cluster Executive KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">

        <!-- Active Trips (Ops) -->
        <div class="bg-white rounded-2xl border border-gray-100 p-4 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between mb-3">
                <div class="w-9 h-9 rounded-xl bg-red-50 flex items-center justify-center text-[#F44336]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                    </svg>
                </div>
                <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded-full">+12%</span>
            </div>
            <p class="text-2xl font-extrabold font-outfit text-gray-900">1,482</p>
            <p class="text-xs text-gray-500 mt-0.5">Active Trips (Ops)</p>
        </div>

        <!-- Fleet Availability -->
        <div class="bg-white rounded-2xl border border-gray-100 p-4 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between mb-3">
                <div class="w-9 h-9 rounded-xl bg-red-50 flex items-center justify-center text-[#F44336]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <span class="text-[10px] font-bold text-red-600 bg-red-50 px-1.5 py-0.5 rounded-full">94.8%</span>
            </div>
            <p class="text-2xl font-extrabold font-outfit text-gray-900">247</p>
            <p class="text-xs text-gray-500 mt-0.5">Available Fleet</p>
        </div>

        <!-- Active Employees -->
        <div class="bg-white rounded-2xl border border-gray-100 p-4 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between mb-3">
                <div class="w-9 h-9 rounded-xl bg-red-50 flex items-center justify-center text-[#F44336]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <span class="text-[10px] font-bold text-red-600 bg-red-50 px-1.5 py-0.5 rounded-full">+5</span>
            </div>
            <p class="text-2xl font-extrabold font-outfit text-gray-900">318</p>
            <p class="text-xs text-gray-500 mt-0.5">Active Workforce</p>
        </div>

        <!-- Today's Revenue -->
        <div class="bg-white rounded-2xl border border-gray-100 p-4 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between mb-3">
                <div class="w-9 h-9 rounded-xl bg-red-50 flex items-center justify-center text-[#F44336]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded-full">+8%</span>
            </div>
            <p class="text-2xl font-extrabold font-outfit text-gray-900">₱84.2K</p>
            <p class="text-xs text-gray-500 mt-0.5">Consolidated Revenue</p>
        </div>

    </div>

    <!-- Cluster Subsystem Integration Status Checks -->
    <div class="bg-white rounded-2xl border border-gray-100 p-6 mb-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-sm font-bold font-outfit text-gray-900">TNVS Subsystem Integration Panel</h2>
                <p class="text-[10px] text-gray-400">Monitoring connection status to all 10 independent Capstone repositories</p>
            </div>
            <span class="text-[10px] font-bold text-[#F44336] bg-red-50 border border-brand/20 px-2.5 py-0.5 rounded-full">10 Linked Domains</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            
            <!-- System 1 -->
            <div class="p-3.5 bg-gray-50/50 rounded-xl border border-gray-100 flex items-center justify-between">
                <div class="min-w-0">
                    <span class="text-[9px] font-bold text-gray-400 uppercase">Team 1</span>
                    <h4 class="text-xs font-bold text-gray-800 truncate">Recruitment & Core HR</h4>
                </div>
                <span class="flex items-center gap-1.5 text-[9px] font-bold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-lg">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Operational
                </span>
            </div>

            <!-- System 2 -->
            <div class="p-3.5 bg-gray-50/50 rounded-xl border border-gray-100 flex items-center justify-between">
                <div class="min-w-0">
                    <span class="text-[9px] font-bold text-gray-400 uppercase">Team 2</span>
                    <h4 class="text-xs font-bold text-gray-800 truncate">Workforce Management</h4>
                </div>
                <span class="flex items-center gap-1.5 text-[9px] font-bold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-lg">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Operational
                </span>
            </div>

            <!-- System 3 -->
            <div class="p-3.5 bg-gray-50/50 rounded-xl border border-gray-100 flex items-center justify-between">
                <div class="min-w-0">
                    <span class="text-[9px] font-bold text-gray-400 uppercase">Team 3</span>
                    <h4 class="text-xs font-bold text-gray-800 truncate">Performance & Dev</h4>
                </div>
                <span class="flex items-center gap-1.5 text-[9px] font-bold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-lg">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Operational
                </span>
            </div>

            <!-- System 4 -->
            <div class="p-3.5 bg-gray-50/50 rounded-xl border border-gray-100 flex items-center justify-between">
                <div class="min-w-0">
                    <span class="text-[9px] font-bold text-gray-400 uppercase">Team 4</span>
                    <h4 class="text-xs font-bold text-gray-800 truncate">Payroll & Benefits</h4>
                </div>
                <span class="flex items-center gap-1.5 text-[9px] font-bold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-lg">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Operational
                </span>
            </div>

            <!-- System 5 -->
            <div class="p-3.5 bg-gray-50/50 rounded-xl border border-gray-100 flex items-center justify-between">
                <div class="min-w-0">
                    <span class="text-[9px] font-bold text-gray-400 uppercase">Team 5</span>
                    <h4 class="text-xs font-bold text-gray-800 truncate">Financial Management</h4>
                </div>
                <span class="flex items-center gap-1.5 text-[9px] font-bold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-lg">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Operational
                </span>
            </div>

            <!-- System 6 -->
            <div class="p-3.5 bg-gray-50/50 rounded-xl border border-gray-100 flex items-center justify-between">
                <div class="min-w-0">
                    <span class="text-[9px] font-bold text-gray-400 uppercase">Team 6</span>
                    <h4 class="text-xs font-bold text-gray-800 truncate">Supply Chain & Inventory</h4>
                </div>
                <span class="flex items-center gap-1.5 text-[9px] font-bold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-lg">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Operational
                </span>
            </div>

            <!-- System 7 -->
            <div class="p-3.5 bg-gray-50/50 rounded-xl border border-gray-100 flex items-center justify-between">
                <div class="min-w-0">
                    <span class="text-[9px] font-bold text-gray-400 uppercase">Team 7</span>
                    <h4 class="text-xs font-bold text-gray-800 truncate">Fleet & Transport</h4>
                </div>
                <span class="flex items-center gap-1.5 text-[9px] font-bold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-lg">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Operational
                </span>
            </div>

            <!-- System 8 -->
            <div class="p-3.5 bg-gray-50/50 rounded-xl border border-gray-100 flex items-center justify-between">
                <div class="min-w-0">
                    <span class="text-[9px] font-bold text-gray-400 uppercase">Team 8</span>
                    <h4 class="text-xs font-bold text-gray-800 truncate">Facilities & Admin</h4>
                </div>
                <span class="flex items-center gap-1.5 text-[9px] font-bold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-lg">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Operational
                </span>
            </div>

            <!-- System 9 -->
            <div class="p-3.5 bg-gray-50/50 rounded-xl border border-gray-100 flex items-center justify-between">
                <div class="min-w-0">
                    <span class="text-[9px] font-bold text-gray-400 uppercase">Team 9</span>
                    <h4 class="text-xs font-bold text-gray-800 truncate">TNVS Operations & Driver</h4>
                </div>
                <span class="flex items-center gap-1.5 text-[9px] font-bold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-lg">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Operational
                </span>
            </div>

        </div>
    </div>

    <!-- Main Grid: Live Dispatch Feed + Quick Access Links -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        <!-- Live Dispatch Feed -->
        <div class="xl:col-span-2 bg-white rounded-2xl border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-sm font-bold font-outfit text-gray-900">Cluster Dispatch Feed</h2>
                    <p class="text-[10px] text-gray-400">Real-time trip & driver activity log</p>
                </div>
                <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded-full">Live</span>
            </div>

            <div class="space-y-2">
                @foreach([
                    ['id' => '#29384', 'driver' => 'Juan D.', 'status' => 'Active', 'route' => 'Makati → BGC', 'color' => 'emerald', 'initial' => 'JD'],
                    ['id' => '#29385', 'driver' => 'Maria R.', 'status' => 'Pending', 'route' => 'Pasig → Ortigas', 'color' => 'amber', 'initial' => 'MR'],
                    ['id' => '#29386', 'driver' => 'Pedro L.', 'status' => 'Active', 'route' => 'Quezon City → Cubao', 'color' => 'emerald', 'initial' => 'PL'],
                    ['id' => '#29387', 'driver' => 'Ana C.', 'status' => 'Completed', 'route' => 'Alabang → Muntinlupa', 'color' => 'gray', 'initial' => 'AC'],
                ] as $trip)
                <div class="flex items-center gap-3 p-3 bg-gray-50/70 rounded-xl hover:bg-gray-50 transition-colors">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0" style="background-color:#F44336;">
                        {{ $trip['initial'] }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-gray-900">{{ $trip['id'] }}</span>
                            <span class="text-[10px] text-gray-500">— {{ $trip['driver'] }}</span>
                        </div>
                        <p class="text-[10px] text-gray-500 truncate">{{ $trip['route'] }}</p>
                    </div>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full flex-shrink-0
                        @if($trip['color'] === 'emerald') text-emerald-700 bg-emerald-50
                        @elseif($trip['color'] === 'amber') text-amber-700 bg-amber-50
                        @else text-gray-500 bg-gray-100 @endif">
                        {{ $trip['status'] }}
                    </span>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Gateway Redirection Panel -->
        <div class="bg-white rounded-2xl border border-gray-100 p-5">
            <h2 class="text-sm font-bold font-outfit text-gray-900 mb-1">Direct Login Transfer</h2>
            <p class="text-[10px] text-gray-400 mb-4">Open direct system console logins</p>

            <div class="space-y-2">
                @foreach([
                    ['label' => 'Team 1 - Recruitment', 'url' => env('TEAM1_URL', '#')],
                    ['label' => 'Team 2 - Workforce', 'url' => env('TEAM2_URL', '#')],
                    ['label' => 'Team 3 - Talent & Dev', 'url' => env('TEAM3_URL', '#')],
                    ['label' => 'Team 4 - Payroll', 'url' => env('TEAM4_URL', '#')],
                    ['label' => 'Team 5 - Finance Ledger', 'url' => env('TEAM5_URL', '#')],
                    ['label' => 'Team 6 - Inventory', 'url' => env('TEAM6_URL', '#')],
                    ['label' => 'Team 7 - Fleet Planning', 'url' => env('TEAM7_URL', '#')],
                    ['label' => 'Team 8 - Admin/Facilities', 'url' => env('TEAM8_URL', '#')],
                    ['label' => 'Team 9 - Driver Ops', 'url' => env('TEAM9_URL', '#')],
                    ['label' => 'Team 10 - Passenger Booking', 'url' => env('TEAM10_URL', '#')],
                ] as $item)
                <a href="{{ $item['url'] }}" target="_blank" class="flex items-center justify-between p-2.5 rounded-xl hover:bg-red-50/40 border border-gray-100 hover:border-brand/20 transition-all group">
                    <span class="text-xs font-bold text-gray-800 group-hover:text-[#F44336] transition-colors">{{ $item['label'] }}</span>
                    <svg class="w-3.5 h-3.5 text-gray-300 group-hover:text-[#F44336] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                </a>
                @endforeach
            </div>
        </div>

    </div>

@endsection
