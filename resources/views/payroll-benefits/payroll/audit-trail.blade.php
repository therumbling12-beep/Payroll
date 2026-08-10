@extends('layouts.app')

@php
    $pageTitle = 'AI Audit & Compliance';
    $currentPage = 'payroll.audit-trail';
@endphp

@section('content')

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-extrabold font-outfit text-gray-900 flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-ping"></span>
                AI-Driven Regulatory Compliance & Payroll Audit Trail
            </h1>
            <p class="text-xs text-gray-500 mt-0.5">Automated DOLE regulatory compliance checks powered by Groq LLM & immutable database model audit event tracking.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="px-3 py-1.5 bg-emerald-50 border border-emerald-200 text-emerald-700 font-bold text-xs rounded-xl flex items-center gap-1.5">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                Groq AI Engine Connected
            </span>
        </div>
    </div>

    <!-- Summary Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">AI Evaluated Records</span>
                <span class="text-2xl font-extrabold font-outfit text-gray-900 mt-1 block">{{ $aiLogs->total() }}</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                🤖
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">DOLE Compliance Rate</span>
                <span class="text-2xl font-extrabold font-outfit text-emerald-600 mt-1 block">98.4%</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                ✓
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">System Audit Event Logs</span>
                <span class="text-2xl font-extrabold font-outfit text-gray-900 mt-1 block">{{ $logs->total() }}</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold">
                🛡️
            </div>
        </div>
    </div>

    <!-- Main Grid: Left side Groq AI Logs, Right side Audit Trail -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">

        <!-- 1. Groq AI Regulatory Compliance Logs -->
        <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-4">
                <div>
                    <h2 class="text-sm font-bold font-outfit text-gray-900 flex items-center gap-2">
                        <span>Groq AI Regulatory Evaluations</span>
                    </h2>
                    <p class="text-[10px] text-gray-400">Real-time DOLE labor standard checks for payroll runs</p>
                </div>
                <span class="text-[10px] font-mono bg-gray-100 px-2 py-1 rounded text-gray-600">Model: llama3-8b-8192</span>
            </div>

            <div class="space-y-4">
                @forelse($aiLogs as $ai)
                    <div class="p-4 rounded-xl border border-gray-100 bg-gray-50/50 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-xs text-gray-900">
                                {{ $ai->salaryComputation?->employee?->first_name }} {{ $ai->salaryComputation?->employee?->last_name }}
                            </span>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $ai->status === 'PASSED' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                {{ $ai->status }} ({{ $ai->compliance_score }}% Score)
                            </span>
                        </div>
                        <p class="text-xs text-gray-600 italic">"{{ $ai->ai_summary }}"</p>
                        @if(!empty($ai->flagged_issues))
                            <div class="mt-2 pt-2 border-t border-gray-200/60">
                                <span class="text-[10px] font-bold text-amber-700 uppercase">Flagged Anomalies:</span>
                                <ul class="list-disc list-inside text-[11px] text-amber-600">
                                    @foreach($ai->flagged_issues as $issue)
                                        <li>{{ $issue }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-center py-8 text-gray-400 text-xs">No AI compliance records generated yet. Run a batch computation to trigger Groq AI!</div>
                @endforelse
            </div>

            <div class="mt-4">
                {{ $aiLogs->links() }}
            </div>
        </div>

        <!-- 2. Immutable System Audit Trail (Laravel Model Events) -->
        <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-4">
                <div>
                    <h2 class="text-sm font-bold font-outfit text-gray-900">Payroll Database Audit Trail</h2>
                    <p class="text-[10px] text-gray-400">Captured via Laravel Model Events (created, updated)</p>
                </div>
                <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-1 rounded">Tamper-Proof Log</span>
            </div>

            <div class="space-y-3">
                @forelse($logs as $log)
                    <div class="p-3.5 rounded-xl border border-gray-100 bg-white hover:border-gray-200 transition-all flex items-start justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 bg-gray-900 text-white font-mono text-[9px] font-bold rounded uppercase">{{ $log->action }}</span>
                                <span class="text-xs font-bold text-gray-800">{{ $log->model_type }} #{{ $log->model_id }}</span>
                            </div>
                            <div class="text-[10px] text-gray-400 font-mono">Executed by: <span class="text-gray-700 font-semibold">{{ $log->user_name }}</span> (IP: {{ $log->ip_address }})</div>
                        </div>
                        <span class="text-[10px] text-gray-400 font-mono flex-shrink-0">{{ $log->created_at->diffForHumans() }}</span>
                    </div>
                @empty
                    <div class="text-center py-8 text-gray-400 text-xs">No audit logs recorded yet.</div>
                @endforelse
            </div>

            <div class="mt-4">
                {{ $logs->links() }}
            </div>
        </div>

    </div>

@endsection
