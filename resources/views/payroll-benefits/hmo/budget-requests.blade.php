@extends('layouts.app')

@php
    $pageTitle = 'HMO & Benefits Budget Requests';
    $currentPage = 'hmo.budget-requests';
@endphp

@section('content')

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-extrabold font-outfit text-gray-900">Financial Budget Requisition for HMO & Benefits</h1>
            <p class="text-xs text-gray-500 mt-0.5">Submit formal fund requisitions to Financial Management (Team 5) for company benefit allocations.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="flex items-center gap-1.5 text-xs font-semibold text-blue-600 bg-blue-50 border border-blue-200 px-3 py-1.5 rounded-full">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                Financial Integration Stream Connected
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

    <!-- Main Grid: New Requisition Form Left, Requests History Right -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        
        <!-- Budget Requisition Form (Left 1 col) -->
        <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
            <h2 class="text-sm font-bold font-outfit text-gray-900 mb-1">Create Budget Requisition</h2>
            <p class="text-[10px] text-gray-400 mb-6">Transmitted to Team 5 (Financial) for fund release</p>

            <form action="{{ route('hmo.submit-request') }}" method="POST" class="space-y-4">
                @csrf
                
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Benefit Category</label>
                    <select name="category" required class="w-full text-xs font-semibold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-[#F44336]">
                        <option value="Annual HMO Corporate Premiums">Annual HMO Corporate Premiums</option>
                        <option value="Driver Accident Emergency Pool Top-Up">Driver Accident Emergency Pool Top-Up</option>
                        <option value="13th Month Pay Total Fund Allocation">13th Month Pay Total Fund Allocation</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Requested Budget Amount (₱)</label>
                    <div class="relative">
                        <input type="number" step="0.01" name="amount" required placeholder="e.g. 450000.00" class="w-full text-sm font-extrabold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:border-[#F44336]">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Justification & Notes</label>
                    <textarea name="justification" rows="3" required class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl p-3 text-gray-800 focus:outline-none focus:border-[#F44336]" placeholder="Explain why funds are needed for Team 5 review..."></textarea>
                </div>

                <button type="submit" class="w-full bg-[#F44336] hover:bg-[#D32F2F] text-white font-bold text-xs py-3 px-4 rounded-xl transition-all shadow-md flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                    Transmit Request to Financial
                </button>

            </form>
        </div>

        <!-- Budget Requests Tracking Table (Right 2 cols) -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-6">
                <div>
                    <h2 class="text-sm font-bold font-outfit text-gray-900">Financial Requisition Stream</h2>
                    <p class="text-[10px] text-gray-400">Status tracking of budget requests submitted to Financial Management</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-100 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                            <th class="py-3 px-4">Requisition ID</th>
                            <th class="py-3 px-4">Category</th>
                            <th class="py-3 px-4">Amount Requested</th>
                            <th class="py-3 px-4">Financial Status</th>
                            <th class="py-3 px-4 text-right">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 text-xs">
                        @forelse($requisitions as $req)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="py-3.5 px-4 font-mono font-bold text-gray-900">{{ $req->requisition_code }}</td>
                                <td class="py-3.5 px-4 font-semibold text-gray-700">{{ $req->category }}</td>
                                <td class="py-3.5 px-4 font-extrabold text-gray-900 text-sm">₱{{ number_format((float)$req->amount, 2) }}</td>
                                <td class="py-3.5 px-4">
                                    @if($req->status === 'approved')
                                        <span class="px-2.5 py-1 bg-blue-50 border border-blue-200 text-blue-700 font-bold rounded-md text-[10px]">Budget Released (Team 5)</span>
                                    @else
                                        <span class="px-2.5 py-1 bg-amber-50 border border-amber-200 text-amber-700 font-bold rounded-md text-[10px]">Awaiting Financial Approval</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-right text-gray-400 text-[11px]">
                                    {{ $req->created_at?->format('M j, Y') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-gray-400 text-xs">No budget requisitions found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $requisitions->links() }}
            </div>

        </div>

    </div>

@endsection
