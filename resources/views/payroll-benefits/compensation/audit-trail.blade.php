@extends('layouts.app')

@php
    $pageTitle = 'Compensation Audit Trail & Compliance Log';
    $currentPage = 'compensation.audit-trail';
@endphp

@section('content')

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-black font-outfit text-gray-900 tracking-tight">Compensation Audit Trail & Compliance Log</h1>
            <p class="text-xs text-gray-500 mt-1">Read-only statutory compliance record tracking all compensation approvals, pay scale benchmark updates, and payroll syncs.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('compensation.audit-trail.export', request()->query()) }}" 
               class="bg-[#1c1c1e] hover:bg-black text-white text-xs font-black px-4 py-2 rounded-xl transition-all shadow-sm flex items-center gap-2">
                <svg class="w-3.5 h-3.5 text-[#F44336]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Export CSV for Audit
            </a>
        </div>
    </div>

    <div x-data="{
        detailModalOpen: false,
        activeLog: null,
        
        openDetail(log) {
            this.activeLog = log;
            this.detailModalOpen = true;
        }
    }" class="space-y-6 pb-12">

        <!-- Filters Form Card -->
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm">
            <form action="{{ route('compensation.audit-trail') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
                
                <!-- Search -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Search User / Action</label>
                    <input type="text" name="search" value="{{ $search }}" placeholder="e.g. Administrator..." 
                           class="w-full text-xs font-semibold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:border-[#F44336]">
                </div>

                <!-- Action Type Filter -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Event Action Type</label>
                    <select name="action" 
                            class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:border-[#F44336]">
                        <option value="all">All Action Types</option>
                        @foreach($distinctActions as $act)
                            <option value="{{ $act }}" {{ $action === $act ? 'selected' : '' }}>{{ $act }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Start Date -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Date From</label>
                    <input type="date" name="start_date" value="{{ $startDate }}" 
                           class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:border-[#F44336]">
                </div>

                <!-- End Date -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Date To</label>
                    <input type="date" name="end_date" value="{{ $endDate }}" 
                           class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:border-[#F44336]">
                </div>

                <!-- Filter Actions -->
                <div class="flex items-center gap-2">
                    <button type="submit" class="w-full bg-[#F44336] hover:bg-[#D32F2F] text-white text-xs font-black py-2.5 px-3 rounded-xl transition-all shadow-sm">
                        Filter Log
                    </button>
                    @if($search || ($action && $action !== 'all') || $startDate || $endDate)
                        <a href="{{ route('compensation.audit-trail') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold py-2.5 px-3 rounded-xl transition-all text-center">
                            Reset
                        </a>
                    @endif
                </div>

            </form>
        </div>

        <!-- Read-Only Audit Log Datatable -->
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                <h2 class="text-base font-extrabold font-outfit text-gray-900 flex items-center gap-2">
                    <span>Immutable Statutory Compliance Logs</span>
                    <span class="text-xs font-black bg-gray-100 text-gray-800 px-2.5 py-0.5 rounded-full">{{ $auditLogs->total() }} Logged Entries</span>
                </h2>
                <span class="text-xs text-gray-400 font-semibold font-mono">Cryptographically Timestamped</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-200 text-xs font-extrabold text-gray-400 uppercase tracking-wider">
                            <th class="py-3.5 px-4">Timestamp</th>
                            <th class="py-3.5 px-4">Authorized User</th>
                            <th class="py-3.5 px-4">Action Event</th>
                            <th class="py-3.5 px-4">Entity Type</th>
                            <th class="py-3.5 px-4 text-center">Entity ID</th>
                            <th class="py-3.5 px-4 text-center">IP Address</th>
                            <th class="py-3.5 px-4 text-right">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-xs">
                        @forelse($auditLogs as $log)
                            <tr class="hover:bg-gray-50/75 transition-colors">
                                <td class="py-4 px-4 font-mono text-gray-600 text-xs font-medium">
                                    {{ $log->created_at->format('Y-m-d H:i:s') }}
                                </td>
                                <td class="py-4 px-4 font-black text-sm text-gray-900">
                                    {{ $log->user_name }}
                                </td>
                                <td class="py-4 px-4">
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-black uppercase font-mono
                                          {{ str_contains($log->action, 'APPROVED') ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : (str_contains($log->action, 'REJECTED') ? 'bg-rose-50 text-rose-800 border border-rose-200' : 'bg-gray-100 text-gray-800') }}">
                                        {{ $log->action }}
                                    </span>
                                </td>
                                <td class="py-4 px-4 font-medium text-gray-800">
                                    {{ $log->model_type }}
                                </td>
                                <td class="py-4 px-4 text-center font-mono text-gray-500 font-bold">
                                    #{{ $log->model_id }}
                                </td>
                                <td class="py-4 px-4 text-center font-mono text-gray-400 text-xs">
                                    {{ $log->ip_address }}
                                </td>
                                <td class="py-4 px-4 text-right">
                                    <button type="button" @click="openDetail({{ Js::from($log) }})" 
                                            class="text-xs font-black text-[#F44336] hover:text-[#D32F2F] bg-red-50 hover:bg-red-100 px-3.5 py-1.5 rounded-xl transition-all">
                                        View Diff
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-10 text-center text-gray-400 text-xs font-semibold">
                                    No audit trail records match the specified filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($auditLogs->hasPages())
                <div class="pt-4 border-t border-gray-100">
                    {{ $auditLogs->links() }}
                </div>
            @endif
        </div>

        <!-- Detail Diff Modal -->
        <div x-show="detailModalOpen" style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div @click.away="detailModalOpen = false" class="bg-white rounded-2xl border border-gray-200 max-w-lg w-full p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="text-base font-black font-outfit text-gray-900">Audit Trail Entry Inspection</h3>
                    <button type="button" @click="detailModalOpen = false" class="text-gray-400 hover:text-gray-600 text-sm font-bold">✕</button>
                </div>

                <template x-if="activeLog">
                    <div class="space-y-3 text-xs">
                        <div class="p-4 bg-gray-50 rounded-2xl border border-gray-200 grid grid-cols-2 gap-3">
                            <div>
                                <span class="text-gray-400 text-xs font-bold block">Action</span>
                                <span class="font-black text-gray-900" x-text="activeLog.action"></span>
                            </div>
                            <div>
                                <span class="text-gray-400 text-xs font-bold block">Authorized User</span>
                                <span class="font-black text-gray-900" x-text="activeLog.user_name"></span>
                            </div>
                            <div>
                                <span class="text-gray-400 text-xs font-bold block">Timestamp</span>
                                <span class="font-mono text-gray-700" x-text="activeLog.created_at"></span>
                            </div>
                            <div>
                                <span class="text-gray-400 text-xs font-bold block">Target Entity</span>
                                <span class="font-mono text-gray-700" x-text="activeLog.model_type + ' #' + activeLog.model_id"></span>
                            </div>
                        </div>

                        <!-- Changes Diff Payload -->
                        <div class="space-y-1">
                            <span class="text-xs font-extrabold text-gray-800">Modified Values Payload:</span>
                            <pre class="p-4 bg-[#1c1c1e] text-emerald-400 rounded-2xl text-xs font-mono overflow-x-auto max-h-60" 
                                 x-text="JSON.stringify(activeLog.new_values, null, 2)"></pre>
                        </div>
                    </div>
                </template>

                <div class="pt-2 flex justify-end">
                    <button type="button" @click="detailModalOpen = false" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs px-5 py-2.5 rounded-xl transition-all">Close</button>
                </div>
            </div>
        </div>

    </div>

@endsection
