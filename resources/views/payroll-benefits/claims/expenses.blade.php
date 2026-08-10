@extends('layouts.app')

@php
    $pageTitle = 'Driver Work Expenses';
    $currentPage = 'claims.expenses';
@endphp

@section('content')

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-extrabold font-outfit text-gray-900">Driver Work Expense Claims</h1>
            <p class="text-xs text-gray-500 mt-0.5">Reimbursable expenses from Fleet Operations. Approved non-taxable claims feed directly into Net Pay.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="flex items-center gap-1.5 text-xs font-semibold text-blue-600 bg-blue-50 border border-blue-200 px-3 py-1.5 rounded-full">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                Payroll Engine Sync Active
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

        <!-- Search & New Claim Toolbar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
            <form action="{{ route('claims.expenses') }}" method="GET" class="flex flex-1 items-center gap-3 max-w-md">
                <div class="relative flex-1">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search driver or claim ID..." class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl pl-9 pr-3.5 py-2 text-gray-800 focus:outline-none focus:border-[#F44336]">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <button type="submit" class="bg-gray-900 text-white text-xs font-bold px-3.5 py-2 rounded-xl">Search</button>
            </form>

            <button @click="showModal = true" class="bg-[#F44336] hover:bg-[#D32F2F] text-white font-bold text-xs px-4 py-2.5 rounded-xl transition-all shadow-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                File Expense Claim
            </button>
        </div>

        <!-- Claims Table -->
        <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-100 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                            <th class="py-3 px-4">Receipt Ref</th>
                            <th class="py-3 px-4">Driver / Employee</th>
                            <th class="py-3 px-4">Expense Description</th>
                            <th class="py-3 px-4">Amount (Non-Taxable)</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 text-xs">
                        @forelse($claims as $claim)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="py-3.5 px-4 font-mono font-bold text-gray-900">{{ $claim->receipt_number }}</td>
                                <td class="py-3.5 px-4 font-bold text-gray-900">
                                    <div>{{ $claim->employee?->first_name }} {{ $claim->employee?->last_name }}</div>
                                    <span class="text-[10px] text-gray-400 font-normal">{{ $claim->employee?->employee_code }} ({{ $claim->employee?->position }})</span>
                                </td>
                                <td class="py-3.5 px-4 font-semibold text-gray-700 max-w-xs truncate" title="{{ $claim->description }}">
                                    {{ $claim->description }}
                                </td>
                                <td class="py-3.5 px-4 font-extrabold text-emerald-600">₱{{ number_format((float)$claim->amount, 2) }}</td>
                                <td class="py-3.5 px-4">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase 
                                        {{ $claim->status === 'approved' ? 'bg-emerald-100 text-emerald-800' : ($claim->status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800') }}">
                                        {{ $claim->status }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    @if($claim->status === 'pending')
                                        <div class="flex items-center justify-end gap-2">
                                            <form action="{{ route('claims.approve', $claim->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] px-3 py-1.5 rounded-xl transition-all shadow-sm">
                                                    Approve & Sync to Payroll
                                                </button>
                                            </form>
                                            <form action="{{ route('claims.reject', $claim->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-[11px] px-3 py-1.5 rounded-xl transition-all">
                                                    Reject
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-[10px] text-gray-400 font-semibold">Synced to Net Pay</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-gray-400 text-xs">No expense claims found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $claims->links() }}
            </div>
        </div>

        <!-- File Expense Claim Modal -->
        <div x-show="showModal" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div @click.away="showModal = false" class="bg-white rounded-2xl border border-gray-100 p-6 max-w-lg w-full shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="text-sm font-bold font-outfit text-gray-900">File Work Expense Reimbursement</h3>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>

                <form action="{{ route('claims.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="type" value="expense">

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Driver / Employee</label>
                        <select name="employee_id" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-[#F44336]">
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->first_name }} {{ $emp->last_name }} ({{ $emp->position }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Amount (₱)</label>
                            <input type="number" step="0.01" name="amount" required placeholder="e.g. 1250.00" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-[#F44336]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Receipt Number</label>
                            <input type="text" name="receipt_number" placeholder="e.g. RCP-98124" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-[#F44336]">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Expense Details / Description</label>
                        <textarea name="description" rows="3" required placeholder="Fuel top-up at Shell EDSA, vehicle toll fees..." class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl p-3 text-gray-800 focus:outline-none focus:border-[#F44336]"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                        <button type="button" @click="showModal = false" class="text-xs font-bold text-gray-500 px-4 py-2">Cancel</button>
                        <button type="submit" class="bg-[#F44336] hover:bg-[#D32F2F] text-white font-bold text-xs px-5 py-2 rounded-xl">Submit Claim</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

@endsection
