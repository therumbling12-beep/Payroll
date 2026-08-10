@extends('layouts.app')

@php
    $pageTitle = 'Counter Offers Calculator';
    $currentPage = 'compensation.counter-offers';
@endphp

@section('content')

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-extrabold font-outfit text-gray-900">Counter Offers Calculator</h1>
            <p class="text-xs text-gray-500 mt-0.5">Formulate competitive salary offers based on candidate credentials & Financial budget limits.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="flex items-center gap-1.5 text-xs font-semibold text-emerald-600 bg-emerald-50 border border-emerald-200 px-3 py-1.5 rounded-full">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                Financial Budget API Live
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

    @if(session('error'))
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 text-xs rounded-2xl font-bold flex items-center gap-2">
            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            {{ session('error') }}
        </div>
    @endif

    <!-- Main Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8" 
         x-data="{ 
            mode: 'auto', 
            experience: 3, 
            certs: 1, 
            proposedBase: 35500, 
            budgetApproved: true,
            budgetReason: 'Budget approved by Team 5 Financial Management',
            calculateCounterOffer() {
                if (this.mode !== 'auto') return;
                fetch('{{ route('compensation.simulate') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        position: 'Payroll & HR Specialist',
                        years_experience: this.experience,
                        certifications_count: this.certs
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.proposedBase = data.data.computed_counter_offer;
                        this.budgetApproved = data.data.financial_budget_check.approved;
                        this.budgetReason = data.data.financial_budget_check.reason;
                    }
                });
            }
         }"
         x-init="calculateCounterOffer()">
        
        <!-- Counter Offer Form Panel (Left 2 cols) -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
            
            <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-6">
                <div>
                    <h2 class="text-sm font-bold font-outfit text-gray-900">Applicant / Employee Retention Counter Offer</h2>
                    <p class="text-[10px] text-gray-400">Calculates credential-based compensation packages</p>
                </div>
                
                <!-- Computation Mode Toggle -->
                <div class="flex items-center gap-2 bg-gray-100 p-1 rounded-xl">
                    <button @click="mode = 'auto'; calculateCounterOffer()" :class="mode === 'auto' ? 'bg-white text-gray-900 font-bold shadow-xs' : 'text-gray-500 font-medium'" class="px-3 py-1 text-xs rounded-lg transition-all">
                        Automated Credentials Engine
                    </button>
                    <button @click="mode = 'manual'" :class="mode === 'manual' ? 'bg-white text-gray-900 font-bold shadow-xs' : 'text-gray-500 font-medium'" class="px-3 py-1 text-xs rounded-lg transition-all">
                        Manual Override
                    </button>
                </div>
            </div>

            <form action="{{ route('compensation.adjustments.store') }}" method="POST" class="space-y-5">
                @csrf
                <input type="hidden" name="type" value="counter_offer">

                <!-- Target Employee -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Target Employee / Applicant</label>
                    <select name="employee_id" class="w-full text-xs font-semibold bg-gray-50/50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-[#F44336]">
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->first_name }} {{ $emp->last_name }} — {{ $emp->position }} (Current Base: ₱{{ number_format((float)($emp->monthly_rate > 0 ? $emp->monthly_rate : $emp->daily_rate * 26), 2) }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" x-show="mode === 'auto'">
                    <!-- Experience -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1.5">Years of Relevant Experience</label>
                        <input type="number" name="years_experience" x-model="experience" @input="calculateCounterOffer()" class="w-full text-xs font-semibold bg-gray-50/50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-[#F44336]" min="0" max="30">
                    </div>

                    <!-- Certifications Count -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1.5">Professional Certifications Count</label>
                        <input type="number" name="certifications_count" x-model="certs" @input="calculateCounterOffer()" class="w-full text-xs font-semibold bg-gray-50/50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-[#F44336]" min="0" max="10">
                    </div>
                </div>

                <!-- Proposed Counter Offer Field -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-bold text-gray-700">Counter Offer Salary (₱)</label>
                        <span x-show="mode === 'auto'" class="text-[10px] text-emerald-600 font-bold bg-emerald-50 px-2 py-0.5 rounded-md">Automated Computation Active</span>
                        <span x-show="mode === 'manual'" class="text-[10px] text-amber-600 font-bold bg-amber-50 px-2 py-0.5 rounded-md">Manual Input Active</span>
                    </div>
                    <input type="number" step="0.01" name="new_rate" x-model="proposedBase" :readonly="mode === 'auto'" class="w-full text-sm font-extrabold font-outfit bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-gray-900 focus:outline-none focus:border-[#F44336]">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Offer Justification / Competitive Match Reason</label>
                    <textarea name="reason" rows="2" required placeholder="Counter-offer to retain candidate against competitor offer..." class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl p-3 text-gray-800 focus:outline-none focus:border-[#F44336]"></textarea>
                </div>

                <!-- Primary Action Button -->
                <div class="pt-2">
                    <button type="submit" class="w-full bg-[#F44336] hover:bg-[#D32F2F] text-white font-bold text-xs py-3 px-4 rounded-xl transition-all shadow-md flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Submit Counter Offer Proposal
                    </button>
                </div>

            </form>

        </div>

        <!-- Budget & Financial Status Panel (Right 1 col) -->
        <div class="space-y-4">
            
            <!-- Financial Integration Budget Check Card -->
            <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xs font-bold font-outfit uppercase tracking-wider text-gray-400">Team 5 Financial Integration</h3>
                    <div class="w-2.5 h-2.5 rounded-full" :class="budgetApproved ? 'bg-emerald-500 animate-pulse' : 'bg-red-500'"></div>
                </div>

                <div class="space-y-4">
                    <div>
                        <p class="text-xs text-gray-500">Live Proposed Offer</p>
                        <p class="text-xl font-extrabold font-outfit text-gray-900">₱<span x-text="Number(proposedBase).toLocaleString()"></span></p>
                    </div>

                    <!-- Dynamic Budget Status Badge -->
                    <div class="pt-2 border-t border-gray-100">
                        <div x-show="budgetApproved" class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center gap-2 text-xs font-bold text-emerald-700">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Budget Approved by Financial
                        </div>
                        <div x-show="!budgetApproved" style="display:none;" class="p-3 bg-red-50 border border-red-200 rounded-xl flex items-center gap-2 text-xs font-bold text-red-700">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Exceeds Financial Budget Cap
                        </div>
                        <p class="text-[10px] text-gray-400 mt-1" x-text="budgetReason"></p>
                    </div>
                </div>
            </div>

            <!-- Helpful Integration Context -->
            <div class="bg-gray-50 rounded-2xl border border-gray-100 p-4 text-xs text-gray-500">
                <p class="font-bold text-gray-700 mb-1">Applicant Management Hook:</p>
                <p class="leading-relaxed">Team 1 can also trigger this logic automatically via standard REST webhook: <code class="font-mono text-indigo-600">POST /api/payroll/webhooks/counter-offer</code></p>
            </div>

        </div>

    </div>

    <!-- Active Counter Offers Queue -->
    <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
        <h2 class="text-sm font-bold font-outfit text-gray-900 mb-4">Submitted Counter Offers</h2>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-100 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                        <th class="py-3 px-4">Employee</th>
                        <th class="py-3 px-4">Old Rate</th>
                        <th class="py-3 px-4">Counter Offer Rate</th>
                        <th class="py-3 px-4">Reason</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 text-xs">
                    @forelse($adjustments as $adj)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="py-4 px-4 font-bold text-gray-900">
                                <div>{{ $adj->employee?->first_name }} {{ $adj->employee?->last_name }}</div>
                                <span class="text-[10px] text-gray-400 font-normal">{{ $adj->employee?->employee_code }}</span>
                            </td>
                            <td class="py-4 px-4 text-gray-500">₱{{ number_format((float)$adj->old_rate, 2) }}</td>
                            <td class="py-4 px-4 font-extrabold text-emerald-600">₱{{ number_format((float)$adj->new_rate, 2) }}</td>
                            <td class="py-4 px-4 text-gray-600">{{ $adj->reason }}</td>
                            <td class="py-4 px-4">
                                @if($adj->status === 'approved')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-emerald-100 text-emerald-800">Approved</span>
                                @elseif($adj->status === 'rejected_financial_budget')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-red-100 text-red-800">Rejected (Financial Budget)</span>
                                @elseif($adj->status === 'rejected')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-gray-100 text-gray-800">Rejected</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-amber-100 text-amber-800">Pending Approval</span>
                                @endif
                            </td>
                            <td class="py-4 px-4 text-right">
                                @if($adj->status === 'pending')
                                    <form action="{{ route('compensation.adjustments.approve', $adj->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] px-3 py-1.5 rounded-xl transition-all shadow-sm">
                                            Accept & Sync to Payroll
                                        </button>
                                    </form>
                                @else
                                    <span class="text-[10px] text-gray-400 font-semibold">Processed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-gray-400 text-xs">No active counter offer proposals.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection
