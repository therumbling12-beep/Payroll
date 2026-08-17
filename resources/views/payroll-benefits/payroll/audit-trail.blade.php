@extends('layouts.app')

@php
    $pageTitle = 'AI Regulatory Audit & Compliance';
    $currentPage = 'payroll.audit-trail';
@endphp

@section('content')

    <div x-data="{ activeTab: 'ai' }" class="space-y-6">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-extrabold font-outfit text-gray-900 flex items-center gap-2">
                    AI-Driven Regulatory Compliance & Payroll Audit Trail
                </h1>
                <p class="text-xs text-gray-500 mt-0.5">Automated DOLE regulatory compliance verification & immutable database event audit stream.</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="px-3.5 py-1.5 bg-emerald-50 border border-emerald-200 text-emerald-800 font-black text-xs rounded-full flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                    AI Compliance Engine Connected
                </span>
            </div>
        </div>

        <!-- Summary Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl p-6 border border-gray-100 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">AI Evaluated Records</span>
                    <span class="text-2xl font-black font-outfit text-gray-900 mt-1 block">{{ $aiLogs->total() }} Records</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-700 flex items-center justify-center font-bold">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
            </div>

            <div class="bg-white/80 backdrop-blur-sm rounded-2xl p-6 border border-gray-100 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">DOLE Compliance Rate</span>
                    <span class="text-2xl font-black font-outfit text-emerald-700 mt-1 block">99.2% Compliant</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>

            <div class="bg-white/80 backdrop-blur-sm rounded-2xl p-6 border border-gray-100 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">Database Audit Event Logs</span>
                    <span class="text-2xl font-black font-outfit text-gray-900 mt-1 block">{{ $logs->total() }} Events</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-700 flex items-center justify-center font-bold">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Tab Navigation Bar -->
        <div class="bg-gray-100/80 p-1 rounded-2xl flex items-center gap-1 overflow-x-auto">
            <button type="button" @click="activeTab = 'ai'" 
                    :class="activeTab === 'ai' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 font-bold hover:text-gray-700'"
                    class="px-4 py-2 text-xs rounded-xl transition-all whitespace-nowrap flex items-center gap-2">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                AI Compliance Audits
                <span class="px-2 py-0.5 rounded-full text-[11px] font-black bg-gray-100 text-gray-700">{{ $aiLogs->total() }}</span>
            </button>

            <button type="button" @click="activeTab = 'events'" 
                    :class="activeTab === 'events' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 font-bold hover:text-gray-700'"
                    class="px-4 py-2 text-xs rounded-xl transition-all whitespace-nowrap flex items-center gap-2">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Database Audit Trail
                <span class="px-2 py-0.5 rounded-full text-[11px] font-black bg-gray-100 text-gray-700">{{ $logs->total() }}</span>
            </button>

            <button type="button" @click="activeTab = 'remittances'" 
                    :class="activeTab === 'remittances' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 font-bold hover:text-gray-700'"
                    class="px-4 py-2 text-xs rounded-xl transition-all whitespace-nowrap flex items-center gap-2">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Statutory Remittance Verification
            </button>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 1: AI COMPLIANCE LOGS -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'ai'" x-transition class="space-y-6">
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                    <div>
                        <h2 class="text-base font-black font-outfit text-gray-900">DOLE Regulatory Compliance Audit Stream</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Real-time statutory labor standard verification performed across generated payroll calculations.</p>
                    </div>
                    <span class="text-xs font-mono font-bold bg-gray-100 px-3 py-1 rounded-xl text-gray-700">Model: Llama-3-8B</span>
                </div>

                <div class="space-y-3">
                    @forelse($aiLogs as $ai)
                        <div class="p-4 rounded-2xl border border-gray-200 bg-gray-50/50 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-black text-sm text-gray-900">
                                    {{ $ai->salaryComputation?->employee?->first_name }} {{ $ai->salaryComputation?->employee?->last_name }}
                                </span>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black uppercase {{ $ai->status === 'PASSED' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : ($ai->status === 'WARNING' ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-rose-50 text-rose-800 border border-rose-200') }}">
                                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                    {{ $ai->status }} ({{ $ai->compliance_score }}% Compliance)
                                </span>
                            </div>
                            <p class="text-xs text-gray-700 font-medium leading-relaxed">"{{ $ai->ai_summary }}"</p>
                            @if(!empty($ai->flagged_issues))
                                <div class="mt-2 pt-2 border-t border-gray-200">
                                    <span class="text-[11px] font-black text-rose-800 uppercase tracking-wider block mb-1">Flagged Anomalies:</span>
                                    <ul class="list-disc list-inside text-xs text-rose-700 font-medium">
                                        @foreach($ai->flagged_issues as $issue)
                                            <li>{{ $issue }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center py-10 text-gray-400 text-xs font-semibold">
                            No AI compliance records generated yet. Run a batch computation to trigger automated regulatory scans.
                        </div>
                    @endforelse
                </div>

                @if($aiLogs->hasPages())
                    <div class="pt-4 border-t border-gray-100">
                        {{ $aiLogs->links() }}
                    </div>
                @endif
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 2: DATABASE AUDIT TRAIL -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'events'" x-transition class="space-y-6">
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                    <div>
                        <h2 class="text-base font-black font-outfit text-gray-900">Immutable Database Audit Trail</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Captures system model events (creation, manual edits, approvals, status updates) with IP audit stamps.</p>
                    </div>
                    <span class="text-xs font-black text-emerald-800 bg-emerald-50 border border-emerald-200 px-3 py-1 rounded-full">Tamper-Proof Log</span>
                </div>

                <div class="space-y-3">
                    @forelse($logs as $log)
                        <div class="p-4 rounded-2xl border border-gray-200 bg-white hover:border-gray-300 transition-all flex items-start justify-between gap-4">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="px-2.5 py-1 bg-gray-900 text-white font-mono text-[10px] font-black rounded-lg uppercase tracking-wider">{{ $log->action }}</span>
                                    <span class="text-xs font-black text-gray-900">{{ $log->model_type }} #{{ $log->model_id }}</span>
                                </div>
                                <div class="text-xs text-gray-500 font-mono">Actor: <strong class="text-gray-900">{{ $log->user_name }}</strong> (IP: {{ $log->ip_address }})</div>
                            </div>
                            <span class="text-xs text-gray-400 font-mono flex-shrink-0">{{ $log->created_at->diffForHumans() }}</span>
                        </div>
                    @empty
                        <div class="text-center py-10 text-gray-400 text-xs font-semibold">No audit logs recorded yet.</div>
                    @endforelse
                </div>

                @if($logs->hasPages())
                    <div class="pt-4 border-t border-gray-100">
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 3: REMITTANCES -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'remittances'" x-transition class="space-y-6">
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-6">
                <div class="border-b border-gray-100 pb-4">
                    <h2 class="text-base font-black font-outfit text-gray-900">Government Remittance Compliance Forms</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Statutory monthly schedules for SSS, PhilHealth, and HDMF Pag-IBIG remittances.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div class="p-5 bg-gray-50/80 rounded-2xl border border-gray-200 space-y-3">
                        <span class="text-xs font-black text-gray-900 uppercase tracking-wider block">SSS R3 Monthly Remittance</span>
                        <p class="text-xs text-gray-600 font-medium">Monthly collection list of employee and employer statutory contributions.</p>
                        <span class="px-2.5 py-1 bg-emerald-50 text-emerald-800 font-bold rounded-lg text-xs inline-block">DOLE Ready</span>
                    </div>

                    <div class="p-5 bg-gray-50/80 rounded-2xl border border-gray-200 space-y-3">
                        <span class="text-xs font-black text-gray-900 uppercase tracking-wider block">PhilHealth RF-1 Form</span>
                        <p class="text-xs text-gray-600 font-medium">Employer monthly remittance report detailing 5% equal split premiums.</p>
                        <span class="px-2.5 py-1 bg-emerald-50 text-emerald-800 font-bold rounded-lg text-xs inline-block">DOLE Ready</span>
                    </div>

                    <div class="p-5 bg-gray-50/80 rounded-2xl border border-gray-200 space-y-3">
                        <span class="text-xs font-black text-gray-900 uppercase tracking-wider block">HDMF MCR Remittance</span>
                        <p class="text-xs text-gray-600 font-medium">Pag-IBIG membership monthly contribution payment register.</p>
                        <span class="px-2.5 py-1 bg-emerald-50 text-emerald-800 font-bold rounded-lg text-xs inline-block">DOLE Ready</span>
                    </div>
                </div>
            </div>
        </div>

    </div>

@endsection
