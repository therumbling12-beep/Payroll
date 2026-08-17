@extends('layouts.app')

@php
    $pageTitle = 'Annual Physical Examination & Statutory Compliance';
    $currentPage = 'hmo.corporate-wellness';
@endphp

@section('content')

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-black font-outfit text-gray-900 tracking-tight">Annual Physical Exam & Statutory Compliance</h1>
            <p class="text-xs text-gray-500 mt-1">Annual Physical Exam (APE) campaigns, Fit to Work occupational health clearances, and statutory remittance compliance tracking.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="flex items-center gap-2 text-xs font-bold text-gray-800 bg-white border border-gray-200 px-3.5 py-1.5 rounded-full shadow-2xs">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                Occupational Health Active
            </span>
            <span class="text-xs text-gray-400 font-semibold font-mono">{{ now()->format('M j, Y') }}</span>
        </div>
    </div>

    @if(session('status'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs rounded-2xl font-bold flex items-center gap-2.5 shadow-2xs">
            <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session('status') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-900 text-xs rounded-2xl font-bold flex items-center gap-2.5 shadow-2xs">
            <svg class="w-4 h-4 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            {{ session('error') }}
        </div>
    @endif

    <!-- Main Container with Alpine.js State -->
    <div x-data="{ 
        activeTab: '{{ $tab ?? 'ape' }}', {{-- 'ape', 'statutory' --}}
        showScheduleModal: false,
        showBatchModal: false,
        showResultsModal: false,
        selectedExam: null,

        openResults(exam) {
            this.selectedExam = exam;
            this.showResultsModal = true;
        }
    }" class="space-y-6 pb-12">

        <!-- Top Navigation Bar & Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-200 pb-3">
            <div class="flex items-center gap-1.5 bg-gray-100 p-1 rounded-2xl">
                
                <!-- Tab 1: Annual Physical Exam (APE) -->
                <button type="button" @click="activeTab = 'ape'" 
                        :class="activeTab === 'ape' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold'" 
                        class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#F44336]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                    Annual Physical Exam (APE)
                    <span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-gray-200 text-gray-800">{{ $exams->total() }}</span>
                </button>

                <!-- Tab 2: Statutory Remittances -->
                <button type="button" @click="activeTab = 'statutory'" 
                        :class="activeTab === 'statutory' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold'" 
                        class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#F44336]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Statutory Remittance Calendar
                </button>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Batch APE Campaign Button -->
                <button @click="showBatchModal = true" type="button" 
                        class="bg-white hover:bg-gray-100 text-gray-800 font-bold text-xs px-3.5 py-2 rounded-xl transition-all border border-gray-200 flex items-center gap-1.5 shadow-2xs">
                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    Batch APE Campaign
                </button>

                <!-- Schedule Individual APE -->
                <button @click="showScheduleModal = true" type="button" 
                        class="bg-gray-900 hover:bg-black text-white font-black text-xs px-4 py-2 rounded-xl transition-all shadow-sm flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    Schedule APE
                </button>
            </div>
        </div>

        <!-- 3 Summary Stat Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">APE Compliance Rate</p>
                <p class="text-2xl font-black font-outfit text-emerald-600 mt-1">{{ $apeSummary['compliance_rate_pct'] }}%</p>
                <p class="text-[11px] text-gray-500 mt-1">{{ $apeSummary['total_attended'] }} of {{ $apeSummary['total_scheduled'] }} Scheduled Attended</p>
            </div>

            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Fit to Work Cleared</p>
                <p class="text-2xl font-black font-outfit text-blue-600 mt-1">{{ $apeSummary['fit_to_work_count'] }} Staff</p>
                <p class="text-[11px] text-gray-500 mt-1">{{ $apeSummary['fit_with_restrictions_count'] }} with Work Restrictions</p>
            </div>

            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Monthly Statutory Burden</p>
                <p class="text-2xl font-black font-outfit text-gray-900 mt-1">PHP {{ number_format($remittanceCalendar['total_remittance'], 2) }}</p>
                <p class="text-[11px] text-gray-500 mt-1">SSS, PhilHealth, Pag-IBIG, BIR Remittance Inflow</p>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 1: ANNUAL PHYSICAL EXAM (APE) SCHEDULING & ATTENDANCE -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'ape'" x-transition class="space-y-6">

            <!-- Filter Controls -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-4 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <form action="{{ route('hmo.corporate-wellness') }}" method="GET" class="flex flex-wrap items-center gap-3 flex-1">
                    <div class="relative flex-1 min-w-[220px]">
                        <input type="text" name="search" value="{{ $search }}" placeholder="Search employee name, code, or clinic..." 
                               class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl pl-9 pr-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-gray-900">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    <select name="attendance_status" class="text-xs bg-gray-50 border border-gray-200 rounded-xl px-3 py-2.5 text-gray-800 font-bold focus:outline-none focus:border-gray-900">
                        <option value="all">All Attendance</option>
                        <option value="scheduled" {{ $attendanceStatus === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                        <option value="attended" {{ $attendanceStatus === 'attended' ? 'selected' : '' }}>Attended</option>
                        <option value="no_show" {{ $attendanceStatus === 'no_show' ? 'selected' : '' }}>No Show</option>
                        <option value="rescheduled" {{ $attendanceStatus === 'rescheduled' ? 'selected' : '' }}>Rescheduled</option>
                    </select>

                    <button type="submit" class="bg-gray-900 hover:bg-black text-white text-xs font-black px-4 py-2.5 rounded-xl transition-all shadow-sm">
                        Filter APE Roster
                    </button>
                </form>
            </div>

            <!-- APE Schedule Table -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-gray-50/80 border-b border-gray-100 text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                <th class="py-3.5 px-4">Employee</th>
                                <th class="py-3.5 px-4">Schedule Date & Slot</th>
                                <th class="py-3.5 px-4">Accredited Clinic & Package</th>
                                <th class="py-3.5 px-4 text-center">Attendance</th>
                                <th class="py-3.5 px-4 text-center">Medical Clearance</th>
                                <th class="py-3.5 px-4">Findings Summary</th>
                                <th class="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($exams as $exam)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-gray-900 font-outfit">{{ $exam->employee?->first_name }} {{ $exam->employee?->last_name }}</div>
                                        <div class="text-[10px] text-gray-400 font-mono">{{ $exam->employee?->employee_code }} • {{ $exam->employee?->department?->name ?? 'General' }}</div>
                                    </td>
                                    <td class="py-3.5 px-4 font-mono text-[11px] text-gray-700">
                                        <div class="font-bold">{{ $exam->schedule_date ? $exam->schedule_date->format('M j, Y') : 'Pending' }}</div>
                                        <div class="text-gray-400">{{ $exam->time_slot ?? '08:00 AM - 12:00 PM' }}</div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-semibold text-gray-900">{{ $exam->facility_name }}</div>
                                        <span class="inline-block mt-0.5 px-2 py-0.5 rounded bg-purple-50 text-purple-700 text-[10px] font-bold">
                                            {{ $exam->package_type }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase
                                            @if($exam->attendance_status === 'attended') bg-emerald-50 text-emerald-700 border border-emerald-200
                                            @elseif($exam->attendance_status === 'scheduled') bg-blue-50 text-blue-700 border border-blue-200
                                            @elseif($exam->attendance_status === 'no_show') bg-rose-50 text-rose-700 border border-rose-200
                                            @else bg-gray-100 text-gray-600 @endif">
                                            {{ $exam->attendance_status }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase
                                            @if($exam->medical_clearance_status === 'fit_to_work') bg-emerald-50 text-emerald-700 border border-emerald-200
                                            @elseif($exam->medical_clearance_status === 'fit_with_restrictions') bg-amber-50 text-amber-700 border border-amber-200
                                            @elseif($exam->medical_clearance_status === 'temporarily_unfit') bg-rose-50 text-rose-700 border border-rose-200
                                            @else bg-gray-100 text-gray-500 @endif">
                                            {{ str_replace('_', ' ', $exam->medical_clearance_status) }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 max-w-[200px]">
                                        <p class="truncate text-gray-600 text-[11px]">{{ $exam->findings_summary ?: 'No diagnostic findings entered' }}</p>
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <button @click="openResults({{ $exam->toJson() }})" type="button" 
                                                class="px-3 py-1.5 bg-white hover:bg-gray-100 text-gray-800 border border-gray-200 rounded-lg text-xs font-bold transition-all shadow-2xs">
                                            Record Results
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-12 text-gray-400 text-xs">
                                        No APE appointments scheduled for this selection.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($exams->hasPages())
                    <div class="p-4 border-t border-gray-100">
                        {{ $exams->links() }}
                    </div>
                @endif
            </div>

        </div>

        <!-- ========================================================================= -->
        <!-- TAB 2: STATUTORY REMITTANCE COMPLIANCE CALENDAR -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'statutory'" x-transition class="space-y-6">

            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-black font-outfit text-gray-900">Statutory Benefits Remittance Calendar & Schedules</h2>
                        <p class="text-[11px] text-gray-500 mt-0.5">Mandatory government remittance deadlines and electronic filing schedule.</p>
                    </div>
                    <span class="text-xs text-gray-400 font-mono">Period: {{ $remittanceCalendar['period_label'] }}</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-gray-50/80 border-b border-gray-100 text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                <th class="py-3.5 px-4">Government Agency</th>
                                <th class="py-3.5 px-4">Report Form / E-File Code</th>
                                <th class="py-3.5 px-4">Contribution Formula & Rates</th>
                                <th class="py-3.5 px-4">Remittance Due Date</th>
                                <th class="py-3.5 px-4 text-right">Computed Remittance</th>
                                <th class="py-3.5 px-4 text-center">Filing Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($remittanceCalendar['items'] as $item)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-gray-900 font-outfit">{{ $item['agency'] }}</div>
                                    </td>
                                    <td class="py-3.5 px-4 font-mono font-bold text-gray-700">
                                        {{ $item['form_report'] }}
                                    </td>
                                    <td class="py-3.5 px-4 text-gray-600 text-[11px]">
                                        {{ $item['rate_formula'] }}
                                    </td>
                                    <td class="py-3.5 px-4 font-mono font-bold text-gray-900">
                                        {{ $item['due_date'] }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-black font-outfit text-sm text-gray-900">
                                        PHP {{ number_format((float)$item['amount'], 2) }}
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black border
                                            @if($item['status'] === 'remitted' || $item['status'] === 'filed') bg-emerald-50 text-emerald-700 border-emerald-200
                                            @else bg-amber-50 text-amber-700 border-amber-200 @endif">
                                            {{ $item['status_label'] }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: SCHEDULE INDIVIDUAL APE -->
        <!-- ========================================================================= -->
        <div x-show="showScheduleModal" x-cloak class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div @click.away="showScheduleModal = false" class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-base font-black font-outfit text-gray-900">Schedule Annual Physical Exam (APE)</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Appoint employee for annual corporate medical exam</p>
                    </div>
                    <button @click="showScheduleModal = false" type="button" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
                </div>

                <form action="{{ route('hmo.ape.schedule') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Select Employee *</label>
                        <select name="employee_id" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-gray-900">
                            <option value="">-- Choose Employee --</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->first_name }} {{ $emp->last_name }} ({{ $emp->employee_code }} - {{ $emp->position }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Exam Year *</label>
                            <input type="number" name="exam_year" value="{{ $year }}" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Schedule Date *</label>
                            <input type="date" name="schedule_date" value="{{ now()->addDays(7)->format('Y-m-d') }}" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Time Slot *</label>
                            <select name="time_slot" required class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 font-bold focus:outline-none focus:border-gray-900">
                                <option value="08:00 AM - 10:00 AM">08:00 AM - 10:00 AM</option>
                                <option value="10:00 AM - 12:00 PM">10:00 AM - 12:00 PM</option>
                                <option value="01:00 PM - 03:00 PM">01:00 PM - 03:00 PM</option>
                                <option value="03:00 PM - 05:00 PM">03:00 PM - 05:00 PM</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Package Type *</label>
                            <select name="package_type" required class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 font-bold focus:outline-none focus:border-gray-900">
                                <option value="Standard Occupational">Standard Occupational</option>
                                <option value="Executive Comprehensive">Executive Comprehensive</option>
                                <option value="Driver Road Fit">Driver Road Fit (Vision + ECG)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Accredited Facility Name *</label>
                        <input type="text" name="facility_name" value="St. Luke's Medical Center - BGC" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                        <button @click="showScheduleModal = false" type="button" class="text-xs font-bold text-gray-500 px-4 py-2">Cancel</button>
                        <button type="submit" class="bg-gray-900 hover:bg-black text-white font-black text-xs px-5 py-2 rounded-xl transition-all shadow-sm">Save Appointment</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: BATCH APE CAMPAIGN SCHEDULING -->
        <!-- ========================================================================= -->
        <div x-show="showBatchModal" x-cloak class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div @click.away="showBatchModal = false" class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-base font-black font-outfit text-gray-900">Batch APE Campaign Scheduling</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Bulk appoint staff or driver fleet for APE</p>
                    </div>
                    <button @click="showBatchModal = false" type="button" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
                </div>

                <form action="{{ route('hmo.ape.batch-schedule') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Target Department / Group</label>
                        <select name="department_id" class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-gray-900">
                            <option value="">All Company Workforce (All Departments)</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Exam Year *</label>
                            <input type="number" name="exam_year" value="{{ $year }}" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Campaign Date *</label>
                            <input type="date" name="schedule_date" value="{{ now()->addDays(14)->format('Y-m-d') }}" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Accredited Diagnostic Clinic *</label>
                        <input type="text" name="facility_name" value="Hi-Precision Diagnostics - Makati" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Exam Package *</label>
                        <select name="package_type" required class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 font-bold focus:outline-none focus:border-gray-900">
                            <option value="Standard Occupational">Standard Occupational (CBC, Urinalysis, X-Ray)</option>
                            <option value="Driver Road Fit">Driver Road Fit (Vision, Color Blindness, ECG)</option>
                            <option value="Executive Comprehensive">Executive Comprehensive</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                        <button @click="showBatchModal = false" type="button" class="text-xs font-bold text-gray-500 px-4 py-2">Cancel</button>
                        <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-black text-xs px-5 py-2 rounded-xl transition-all shadow-sm">Generate Batch Campaign</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: RECORD APE RESULTS & MEDICAL CLEARANCE -->
        <!-- ========================================================================= -->
        <div x-show="showResultsModal" x-cloak class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div @click.away="showResultsModal = false" class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-base font-black font-outfit text-gray-900">Record APE Results & Clearance</h2>
                        <p class="text-xs text-gray-400 mt-0.5" x-text="selectedExam ? ('Employee: ' + (selectedExam.employee ? selectedExam.employee.first_name + ' ' + selectedExam.employee.last_name : '')) : ''"></p>
                    </div>
                    <button @click="showResultsModal = false" type="button" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
                </div>

                <template x-if="selectedExam">
                    <form :action="'/hmo-benefits/ape/' + selectedExam.id + '/record-results'" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Attendance Status *</label>
                                <select name="attendance_status" :value="selectedExam.attendance_status" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                                    <option value="attended">Attended (Completed)</option>
                                    <option value="scheduled">Scheduled</option>
                                    <option value="no_show">No Show</option>
                                    <option value="rescheduled">Rescheduled</option>
                                    <option value="waived">Waived</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Medical Clearance *</label>
                                <select name="medical_clearance_status" :value="selectedExam.medical_clearance_status" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                                    <option value="fit_to_work">Fit to Work (Cleared)</option>
                                    <option value="fit_with_restrictions">Fit with Work Restrictions</option>
                                    <option value="temporarily_unfit">Temporarily Unfit</option>
                                    <option value="pending_results">Pending Results</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Doctor Findings & Diagnostic Summary</label>
                            <textarea name="findings_summary" rows="3" :value="selectedExam.findings_summary" placeholder="e.g. Normal chest X-ray, 20/20 visual acuity, mild hypertension recommended for lifestyle monitoring..." class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl p-3 text-gray-800 focus:outline-none focus:border-gray-900"></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Fit to Work Medical Certificate (PDF / JPG)</label>
                            <input type="file" name="medical_certificate" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-xs text-gray-600">
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                            <button @click="showResultsModal = false" type="button" class="text-xs font-bold text-gray-500 px-4 py-2">Cancel</button>
                            <button type="submit" class="bg-gray-900 hover:bg-black text-white font-black text-xs px-5 py-2 rounded-xl transition-all shadow-sm">Save Medical Clearance</button>
                        </div>
                    </form>
                </template>
            </div>
        </div>

    </div>

@endsection
