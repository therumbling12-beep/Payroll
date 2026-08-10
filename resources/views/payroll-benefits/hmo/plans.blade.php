@extends('layouts.app')

@php
    $pageTitle = 'Employee HMO Plans';
    $currentPage = 'hmo.plans';
@endphp

@section('content')

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-extrabold font-outfit text-gray-900">Employee HMO Coverage & Benefits</h1>
            <p class="text-xs text-gray-500 mt-0.5">Manage employee medical coverage tiers. Synchronized with Employee Self-Service (ESS) Portal records.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="flex items-center gap-1.5 text-xs font-semibold text-emerald-600 bg-emerald-50 border border-emerald-200 px-3 py-1.5 rounded-full">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                ESS Portal Record Sync Live
            </span>
            <span class="text-xs text-gray-400">{{ now()->format('D, M j Y') }}</span>
        </div>
    </div>

    @if(session('status'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-2xl font-bold flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session('status') }}
        </div>
    @endif

    <div x-data="{ showModal: false }" class="space-y-6">

        <!-- Active HMO Providers Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Provider 1 -->
            <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-xs">
                <div class="flex items-center justify-between mb-3">
                    <span class="px-2.5 py-1 bg-red-50 text-[#F44336] font-bold rounded-lg text-[10px]">Executive Tier</span>
                    <span class="text-xs font-bold text-emerald-600">Maxicard Gold</span>
                </div>
                <h3 class="text-base font-extrabold font-outfit text-gray-900">₱250,000.00 MBL</h3>
                <p class="text-xs text-gray-500 mt-1 mb-4">Coverage for Senior Management & Key Leads. Includes 2 free dependents.</p>
                <div class="flex items-center justify-between pt-3 border-t border-gray-100 text-xs">
                    <span class="text-gray-400">Enrolled Staff:</span>
                    <span class="font-bold text-gray-900">{{ $enrollments->where('provider_plan', 'Maxicard Gold')->count() }} Employees</span>
                </div>
            </div>

            <!-- Provider 2 -->
            <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-xs">
                <div class="flex items-center justify-between mb-3">
                    <span class="px-2.5 py-1 bg-blue-50 text-blue-700 font-bold rounded-lg text-[10px]">Standard Staff Tier</span>
                    <span class="text-xs font-bold text-blue-600">Intellicare Silver</span>
                </div>
                <h3 class="text-base font-extrabold font-outfit text-gray-900">₱150,000.00 MBL</h3>
                <p class="text-xs text-gray-500 mt-1 mb-4">Standard medical coverage for office staff & dispatchers. Pre-existing conditions covered.</p>
                <div class="flex items-center justify-between pt-3 border-t border-gray-100 text-xs">
                    <span class="text-gray-400">Enrolled Staff:</span>
                    <span class="font-bold text-gray-900">{{ $enrollments->where('provider_plan', 'Intellicare Silver')->count() }} Employees</span>
                </div>
            </div>

            <!-- Provider 3 -->
            <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-xs">
                <div class="flex items-center justify-between mb-3">
                    <span class="px-2.5 py-1 bg-purple-50 text-purple-700 font-bold rounded-lg text-[10px]">Driver Fleet Care</span>
                    <span class="text-xs font-bold text-purple-600">InLife Fleet Protect</span>
                </div>
                <h3 class="text-base font-extrabold font-outfit text-gray-900">₱100,000.00 Emergency</h3>
                <p class="text-xs text-gray-500 mt-1 mb-4">Custom accident & emergency hospitalization for active drivers.</p>
                <div class="flex items-center justify-between pt-3 border-t border-gray-100 text-xs">
                    <span class="text-gray-400">Enrolled Drivers:</span>
                    <span class="font-bold text-gray-900">{{ $enrollments->where('provider_plan', 'InLife Fleet Protect')->count() }} Drivers</span>
                </div>
            </div>
        </div>

        <!-- HMO Employee Record List -->
        <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <form action="{{ route('hmo.plans') }}" method="GET" class="flex flex-1 items-center gap-3 max-w-md">
                    <div class="relative flex-1">
                        <input type="text" name="search" value="{{ $search }}" placeholder="Search employee name..." class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl pl-9 pr-3.5 py-2 text-gray-800 focus:outline-none focus:border-[#F44336]">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <button type="submit" class="bg-gray-900 text-white text-xs font-bold px-3.5 py-2 rounded-xl">Search</button>
                </form>
                
                <button @click="showModal = true" class="bg-[#F44336] hover:bg-[#D32F2F] text-white text-xs font-bold px-4 py-2.5 rounded-xl transition-colors shadow-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Enroll Employee into HMO
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-100 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                            <th class="py-3 px-4">Employee</th>
                            <th class="py-3 px-4">HMO Card Number</th>
                            <th class="py-3 px-4">Coverage Plan</th>
                            <th class="py-3 px-4">Maximum Benefit Limit (MBL)</th>
                            <th class="py-3 px-4">ESS Sync Status</th>
                            <th class="py-3 px-4 text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 text-xs">
                        @forelse($enrollments as $item)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="py-3.5 px-4 font-bold text-gray-900">
                                    <div>{{ $item->employee?->first_name }} {{ $item->employee?->last_name }}</div>
                                    <span class="text-[10px] text-gray-400 font-normal">{{ $item->employee?->employee_code }} • {{ $item->employee?->position }}</span>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-gray-700">{{ $item->hmo_card_number }}</td>
                                <td class="py-3.5 px-4">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold 
                                        {{ $item->provider_plan === 'Maxicard Gold' ? 'bg-red-50 text-[#F44336]' : ($item->provider_plan === 'Intellicare Silver' ? 'bg-blue-50 text-blue-700' : 'bg-purple-50 text-purple-700') }}">
                                        {{ $item->provider_plan }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-extrabold text-gray-900">₱{{ number_format((float)$item->mbl_amount, 2) }}</td>
                                <td class="py-3.5 px-4">
                                    <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 font-bold rounded-md text-[10px]">Synced to ESS Portal</span>
                                </td>
                                <td class="py-3.5 px-4 text-right font-bold text-emerald-600 uppercase text-[10px]">
                                    {{ $item->status }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-gray-400 text-xs">No employee HMO enrollments found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $enrollments->links() }}
            </div>
        </div>

        <!-- Enroll HMO Modal -->
        <div x-show="showModal" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div @click.away="showModal = false" class="bg-white rounded-2xl border border-gray-100 p-6 max-w-lg w-full shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="text-sm font-bold font-outfit text-gray-900">Enroll Employee into HMO Plan</h3>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>

                <form action="{{ route('hmo.enroll') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Select Employee</label>
                        <select name="employee_id" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-[#F44336]">
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->first_name }} {{ $emp->last_name }} ({{ $emp->position }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Coverage Plan Tier</label>
                        <select name="provider_plan" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-[#F44336]">
                            <option value="Intellicare Silver">Intellicare Silver (₱150,000.00 MBL)</option>
                            <option value="Maxicard Gold">Maxicard Gold (₱250,000.00 MBL)</option>
                            <option value="InLife Fleet Protect">InLife Fleet Protect (₱100,000.00 MBL)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Maximum Benefit Limit (MBL ₱)</label>
                        <input type="number" step="0.01" name="mbl_amount" required value="150000.00" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-[#F44336]">
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                        <button type="button" @click="showModal = false" class="text-xs font-bold text-gray-500 px-4 py-2">Cancel</button>
                        <button type="submit" class="bg-[#F44336] hover:bg-[#D32F2F] text-white font-bold text-xs px-5 py-2 rounded-xl">Complete Enrollment</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

@endsection
