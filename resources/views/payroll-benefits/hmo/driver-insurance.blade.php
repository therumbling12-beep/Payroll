@extends('layouts.app')

@php
    $pageTitle = 'Driver Insurance Config';
    $currentPage = 'hmo.driver-insurance';
@endphp

@section('content')

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-extrabold font-outfit text-gray-900">Driver Accident Insurance & Benefit Deductions</h1>
            <p class="text-xs text-gray-500 mt-0.5">Configure driver salary contribution percentages (3%) and accident assistance policies for medical bill payouts.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="flex items-center gap-1.5 text-xs font-semibold text-purple-600 bg-purple-50 border border-purple-200 px-3 py-1.5 rounded-full">
                <span class="w-1.5 h-1.5 rounded-full bg-purple-500 animate-pulse"></span>
                Driver Protection Policy Active
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

    <div x-data="{ showClaimModal: false }" class="space-y-6">

        <!-- Main Grid: Config Form Left, Accident Claims & Accumulated Fund Right -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Contribution & Policy Config Panel (Left 1 col) -->
            <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                <h2 class="text-sm font-bold font-outfit text-gray-900 mb-1">Accident Policy Configuration</h2>
                <p class="text-[10px] text-gray-400 mb-6">Set contribution percentage deducted from driver payroll</p>

                <div class="space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1.5">Driver Salary Contribution Rate</label>
                        <div class="relative">
                            <input type="number" readonly value="3" class="w-full text-sm font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none">
                            <span class="absolute right-4 top-2.5 text-xs font-bold text-gray-400">% per payout</span>
                        </div>
                        <p class="text-[10px] text-gray-400 mt-1">Status: Active & Deducted automatically during Payroll runs</p>
                    </div>

                    <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-200/60 space-y-2">
                        <div class="flex items-center gap-2 text-xs font-bold text-gray-800">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            50% Company Matching Assistance
                        </div>
                        <p class="text-[10px] text-gray-500">Company pays 50% of driver emergency hospital bills if an accident occurs during active trip delivery.</p>
                    </div>

                    <button @click="showClaimModal = true" type="button" class="w-full bg-[#F44336] hover:bg-[#D32F2F] text-white font-bold text-xs py-3 px-4 rounded-xl transition-all shadow-md flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Process Emergency Accident Claim
                    </button>
                </div>
            </div>

            <!-- Driver Pool & Accident Claims Table (Right 2 cols) -->
            <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-gray-100 pb-4 mb-6 gap-4">
                    <div>
                        <h2 class="text-sm font-bold font-outfit text-gray-900">Driver Accident Assistance & Benefit Pool</h2>
                        <p class="text-[10px] text-gray-400">Accumulated 3% driver salary deductions in active payroll runs</p>
                    </div>

                    <div class="sm:text-right bg-emerald-50 border border-emerald-100 p-3 rounded-xl">
                        <span class="text-[10px] font-bold text-emerald-800 uppercase tracking-widest block">Total Reserve Pool</span>
                        <span class="text-lg font-extrabold font-outfit text-emerald-600">₱{{ number_format($accumulatedFund, 2) }}</span>
                    </div>
                </div>

                <!-- Accident Assistance Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-100 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                                <th class="py-3 px-4">Driver</th>
                                <th class="py-3 px-4">Incident Ref</th>
                                <th class="py-3 px-4">Description</th>
                                <th class="py-3 px-4">Bill Coverage</th>
                                <th class="py-3 px-4 text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-xs">
                            @forelse($accidentClaims as $claim)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="py-3.5 px-4 font-bold text-gray-900">
                                        <div>{{ $claim->employee?->first_name }} {{ $claim->employee?->last_name }}</div>
                                        <span class="text-[10px] text-gray-400 font-normal">{{ $claim->employee?->employee_code }}</span>
                                    </td>
                                    <td class="py-3.5 px-4 font-mono font-bold text-gray-700">{{ $claim->incident_number }}</td>
                                    <td class="py-3.5 px-4 text-gray-600 max-w-xs truncate" title="{{ $claim->description }}">{{ $claim->description }}</td>
                                    <td class="py-3.5 px-4 font-extrabold text-red-600">₱{{ number_format((float)$claim->bill_amount, 2) }}</td>
                                    <td class="py-3.5 px-4 text-right">
                                        <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 font-bold rounded-md text-[10px]">Paid by Assistance</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-gray-400 text-xs">No accident emergency claims recorded.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $accidentClaims->links() }}
                </div>
            </div>

        </div>

        <!-- Emergency Claim Modal -->
        <div x-show="showClaimModal" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div @click.away="showClaimModal = false" class="bg-white rounded-2xl border border-gray-100 p-6 max-w-lg w-full shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="text-sm font-bold font-outfit text-gray-900">File Driver Emergency Accident Claim</h3>
                    <button @click="showClaimModal = false" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>

                <form action="{{ route('hmo.file-claim') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Driver</label>
                        <select name="employee_id" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-[#F44336]">
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->first_name }} {{ $emp->last_name }} ({{ $emp->position }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Hospital / Emergency Bill Amount (₱)</label>
                        <input type="number" step="0.01" name="bill_amount" required placeholder="e.g. 18500.00" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-[#F44336]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Accident Incident Details</label>
                        <textarea name="description" rows="3" required placeholder="Emergency medical bill assistance for collision on C5 ramp..." class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl p-3 text-gray-800 focus:outline-none focus:border-[#F44336]"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                        <button type="button" @click="showClaimModal = false" class="text-xs font-bold text-gray-500 px-4 py-2">Cancel</button>
                        <button type="submit" class="bg-[#F44336] hover:bg-[#D32F2F] text-white font-bold text-xs px-5 py-2 rounded-xl">Disburse Emergency Assistance</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

@endsection
