@extends('layouts.app')

@php
    $pageTitle = 'Payroll Cut-offs & Runs';
    $currentPage = 'payroll.salary-computation';
@endphp

@section('content')

    <div x-data="{ 
        activeTab: 'folders',
        showRunModal: false, 
        showTransparencyModal: false,
        searchQuery: '',
        selectedPeriod: '2026-08-06_12',
        matchesSearch(periodCode, periodLabel) {
            if (!this.searchQuery) return true;
            const q = this.searchQuery.toLowerCase();
            return periodCode.toLowerCase().includes(q) || periodLabel.toLowerCase().includes(q);
        }
    }" class="space-y-6">

        <!-- Page Header & Action Controls -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-extrabold font-outfit text-gray-900">Payroll Cut-offs & Period Runs</h1>
                <p class="text-xs text-gray-500 mt-0.5">Manage weekly Thursday-to-Wednesday salary cutoff periods, trigger automated batch calculations, and track financial release workflows.</p>
            </div>
            
            <div class="flex flex-wrap items-center gap-3">
                <!-- Formula Transparency Button -->
                <button @click="showTransparencyModal = true" type="button" 
                        class="bg-white/80 backdrop-blur-sm hover:bg-gray-100 text-gray-800 font-bold text-xs px-4 py-2.5 rounded-xl transition-all border border-gray-200 flex items-center gap-2 shadow-xs">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Calculation Formulas
                </button>

                <!-- New Batch Computation Button -->
                <button @click="showRunModal = true" type="button" 
                        class="bg-[#F44336] hover:bg-[#D32F2F] text-white font-black text-xs px-5 py-2.5 rounded-xl transition-all shadow-sm flex items-center gap-2 whitespace-nowrap shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Execute Payroll Run
                </button>
            </div>
        </div>

        <!-- Tab Navigation Bar -->
        <div class="bg-gray-100/80 p-1 rounded-2xl flex items-center gap-1 overflow-x-auto">
            <button type="button" @click="activeTab = 'folders'" 
                    :class="activeTab === 'folders' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 font-bold hover:text-gray-700'"
                    class="px-4 py-2 text-xs rounded-xl transition-all whitespace-nowrap flex items-center gap-2">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                </svg>
                Active Run Folders
                <span class="px-2 py-0.5 rounded-full text-[11px] font-black bg-gray-100 text-gray-700">{{ $cutoffs->count() }}</span>
            </button>

            <button type="button" @click="activeTab = 'calendar'" 
                    :class="activeTab === 'calendar' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 font-bold hover:text-gray-700'"
                    class="px-4 py-2 text-xs rounded-xl transition-all whitespace-nowrap flex items-center gap-2">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Cutoff Schedule Calendar
            </button>

            <button type="button" @click="activeTab = 'tracker'" 
                    :class="activeTab === 'tracker' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 font-bold hover:text-gray-700'"
                    class="px-4 py-2 text-xs rounded-xl transition-all whitespace-nowrap flex items-center gap-2">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Multi-Stage Approval Tracker
            </button>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 1: RUN FOLDERS -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'folders'" x-transition class="space-y-6">

            <!-- Search Filter Bar -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-4 flex items-center justify-between gap-4 shadow-sm">
                <div class="relative flex-1 max-w-sm">
                    <input type="text" x-model="searchQuery" placeholder="Filter cutoffs by period (e.g. July, 2026-07)..." 
                           class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl pl-9 pr-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-[#F44336]">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <div class="text-xs text-gray-500 font-medium">
                    Showing active payroll period batches
                </div>
            </div>

            <!-- Cutoffs Grid View -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($cutoffs as $cutoff)
                    @php
                        $parts = explode('_', $cutoff->cutoff_period);
                        if (count($parts) === 2) {
                            try {
                                $startDate = \Carbon\Carbon::parse($parts[0]);
                                $endDay = (int) $parts[1];
                                if ($endDay < $startDate->day) {
                                    $endDate = $startDate->copy()->addMonth()->day($endDay);
                                } else {
                                    $endDate = $startDate->copy()->day($endDay);
                                }
                                $formattedPeriod = $startDate->format('M j') . ' – ' . $endDate->format('M j, Y');
                                $cutoffTag = $startDate->format('D') . '–' . $endDate->format('D') . ' Cycle';
                            } catch (\Throwable $e) {
                                $formattedPeriod = $cutoff->cutoff_period;
                                $cutoffTag = 'Weekly Run';
                            }
                        } else {
                            $formattedPeriod = $cutoff->cutoff_period;
                            $cutoffTag = 'Payroll Run';
                        }

                        $batchRecord = $batches->get($cutoff->cutoff_period);
                        $statusLabel = $batchRecord ? $batchRecord->status->label() : 'Draft / Unsubmitted';
                        $statusClasses = $batchRecord ? $batchRecord->status->badgeClasses() : 'bg-gray-100 text-gray-700 border-gray-200';
                    @endphp

                    <div x-show="matchesSearch('{{ $cutoff->cutoff_period }}', '{{ $formattedPeriod }}')" 
                         class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm hover:shadow-md transition-all flex flex-col justify-between group">
                        <div>
                            <!-- Tag & Badge -->
                            <div class="flex items-center justify-between mb-4">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider border {{ $statusClasses }}">
                                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                    {{ $statusLabel }}
                                </span>
                                <span class="text-xs font-bold text-gray-400 bg-gray-100 px-2.5 py-1 rounded-lg">
                                    {{ $cutoffTag }}
                                </span>
                            </div>

                            <!-- Cutoff Title -->
                            <h3 class="text-base font-black font-outfit text-gray-900 mb-1 group-hover:text-[#F44336] transition-colors">
                                {{ $formattedPeriod }}
                            </h3>
                            <p class="text-xs text-gray-400 mb-5">Period Code: <span class="font-mono font-bold text-gray-600">{{ $cutoff->cutoff_period }}</span></p>

                            <!-- Summary Stats Grid -->
                            <div class="grid grid-cols-2 gap-3 bg-gray-50/90 rounded-2xl p-4 mb-5 border border-gray-100">
                                <div>
                                    <span class="text-xs text-gray-400 font-bold block uppercase tracking-wider">Employees</span>
                                    <span class="text-base font-black text-gray-900 font-outfit mt-0.5 block">{{ number_format($cutoff->total_employees) }} Headcount</span>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-400 font-bold block uppercase tracking-wider">Total Net Pay</span>
                                    <span class="text-base font-black text-emerald-600 font-outfit mt-0.5 block">₱{{ number_format($cutoff->total_net, 2) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Action Link -->
                        <a href="{{ route('payroll.salary-computation.show', $cutoff->cutoff_period) }}" 
                           class="w-full bg-gray-900 hover:bg-[#F44336] text-white font-black text-xs py-3 px-4 rounded-xl transition-all shadow-sm flex items-center justify-center gap-2 group-hover:shadow-md">
                            <span>Open Computation Folder</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </a>
                    </div>
                @endforeach
            </div>

        </div>

        <!-- ========================================================================= -->
        <!-- TAB 2: CUTOFF SCHEDULE CALENDAR -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'calendar'" x-transition class="space-y-6">
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-gray-100 pb-4">
                    <div>
                        <h2 class="text-base font-black font-outfit text-gray-900">2026 Weekly Payroll Cutoff Calendar (Thursday – Wednesday Cycles)</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Weekly Thursday-to-Wednesday cutoff cycles per company payroll policy (Payout scheduled every Friday).</p>
                    </div>
                    <span class="px-3 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full text-xs font-black">
                        Weekly Standard Active
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @php
                        $weeklyMonths = [
                            [
                                'name' => 'August 2026',
                                'weeks' => [
                                    ['code' => '2026-08-06_12', 'label' => 'Week 1: Aug 6 – Aug 12 (Thu–Wed)', 'payout' => 'Aug 14 (Fri)'],
                                    ['code' => '2026-08-13_19', 'label' => 'Week 2: Aug 13 – Aug 19 (Thu–Wed)', 'payout' => 'Aug 21 (Fri)'],
                                    ['code' => '2026-08-20_26', 'label' => 'Week 3: Aug 20 – Aug 26 (Thu–Wed)', 'payout' => 'Aug 28 (Fri)'],
                                    ['code' => '2026-08-27_02', 'label' => 'Week 4: Aug 27 – Sep 2 (Thu–Wed)', 'payout' => 'Sep 4 (Fri)'],
                                ],
                            ],
                            [
                                'name' => 'September 2026',
                                'weeks' => [
                                    ['code' => '2026-09-03_09', 'label' => 'Week 1: Sep 3 – Sep 9 (Thu–Wed)', 'payout' => 'Sep 11 (Fri)'],
                                    ['code' => '2026-09-10_16', 'label' => 'Week 2: Sep 10 – Sep 16 (Thu–Wed)', 'payout' => 'Sep 18 (Fri)'],
                                    ['code' => '2026-09-17_23', 'label' => 'Week 3: Sep 17 – Sep 23 (Thu–Wed)', 'payout' => 'Sep 25 (Fri)'],
                                    ['code' => '2026-09-24_30', 'label' => 'Week 4: Sep 24 – Sep 30 (Thu–Wed)', 'payout' => 'Oct 2 (Fri)'],
                                ],
                            ],
                            [
                                'name' => 'October 2026',
                                'weeks' => [
                                    ['code' => '2026-10-01_07', 'label' => 'Week 1: Oct 1 – Oct 7 (Thu–Wed)', 'payout' => 'Oct 9 (Fri)'],
                                    ['code' => '2026-10-08_14', 'label' => 'Week 2: Oct 8 – Oct 14 (Thu–Wed)', 'payout' => 'Oct 16 (Fri)'],
                                    ['code' => '2026-10-15_21', 'label' => 'Week 3: Oct 15 – Oct 21 (Thu–Wed)', 'payout' => 'Oct 23 (Fri)'],
                                    ['code' => '2026-10-22_28', 'label' => 'Week 4: Oct 22 – Oct 28 (Thu–Wed)', 'payout' => 'Oct 30 (Fri)'],
                                    ['code' => '2026-10-29_04', 'label' => 'Week 5: Oct 29 – Nov 4 (Thu–Wed)', 'payout' => 'Nov 6 (Fri)'],
                                ],
                            ],
                        ];
                    @endphp

                    @foreach($weeklyMonths as $m)
                        <div class="p-4 bg-gray-50/80 rounded-2xl border border-gray-200 space-y-3">
                            <div class="font-extrabold text-sm text-gray-900 font-outfit">{{ $m['name'] }}</div>
                            <div class="space-y-2 text-xs">
                                @foreach($m['weeks'] as $w)
                                    @php
                                        $isRan = $cutoffs->contains('cutoff_period', $w['code']);
                                    @endphp
                                    <a href="{{ route('payroll.salary-computation.show', $w['code']) }}" 
                                       class="block p-3 rounded-xl border transition-all duration-150 {{ $isRan ? 'bg-emerald-50/50 border-emerald-300 hover:border-emerald-500 shadow-2xs' : 'bg-white border-rose-200 hover:border-rose-400' }}">
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="space-y-1">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-2 h-2 rounded-full {{ $isRan ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                                    <span class="font-black text-gray-900 text-xs">{{ $w['label'] }}</span>
                                                </div>
                                                <div class="text-[11px] text-gray-500 pl-4">
                                                    Payout: <span class="font-bold text-gray-700">{{ $w['payout'] }}</span>
                                                </div>
                                            </div>
                                            <div>
                                                @if($isRan)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300">
                                                        Ran
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-black bg-rose-50 text-rose-700 border border-rose-200">
                                                        Not Ran
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 3: APPROVAL TRACKER -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'tracker'" x-transition class="space-y-6">
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-4">
                <div class="border-b border-gray-100 pb-4">
                    <h2 class="text-base font-black font-outfit text-gray-900">Multi-Stage Payroll Batch Workflow Tracker</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Trace the status of payroll batches across HR Review, Admin Approval, Financial Budget Confirmation, and Payout Release.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200 text-xs font-black text-gray-400 uppercase tracking-wider">
                                <th class="py-3 px-4">Period Cutoff</th>
                                <th class="py-3 px-4">Step 1: HR Compute</th>
                                <th class="py-3 px-4">Step 2: Admin Approval</th>
                                <th class="py-3 px-4">Step 3: Finance Budget</th>
                                <th class="py-3 px-4">Step 4: Payout Release</th>
                                <th class="py-3 px-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-xs">
                            @foreach($cutoffs as $cutoff)
                                @php
                                    $batchRecord = $batches->get($cutoff->cutoff_period);
                                    $statusVal = $batchRecord ? $batchRecord->status->value : 'draft';
                                @endphp
                                <tr class="hover:bg-gray-50/75 transition-colors">
                                    <td class="py-4 px-4 font-black font-outfit text-sm text-gray-900">
                                        {{ $cutoff->cutoff_period }}
                                    </td>
                                    <td class="py-4 px-4">
                                        <span class="inline-flex items-center gap-1 text-emerald-700 font-bold">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                            Calculated
                                        </span>
                                    </td>
                                    <td class="py-4 px-4">
                                        @if(in_array($statusVal, ['approved', 'budget_requested', 'budget_received', 'released']))
                                            <span class="inline-flex items-center gap-1 text-emerald-700 font-bold">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                                Approved
                                            </span>
                                        @elseif($statusVal === 'pending_admin')
                                            <span class="inline-flex items-center gap-1 text-amber-700 font-bold">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                                Under Review
                                            </span>
                                        @else
                                            <span class="text-gray-400 font-bold">Pending Submission</span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-4">
                                        @if(in_array($statusVal, ['budget_received', 'released']))
                                            <span class="inline-flex items-center gap-1 text-emerald-700 font-bold">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                                Funded
                                            </span>
                                        @elseif($statusVal === 'budget_requested')
                                            <span class="inline-flex items-center gap-1 text-blue-700 font-bold">
                                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                                                Requested
                                            </span>
                                        @else
                                            <span class="text-gray-400 font-bold">Queued</span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-4">
                                        @if($statusVal === 'released')
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-emerald-50 text-emerald-800 border border-emerald-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                                Released & Paid
                                            </span>
                                        @else
                                            <span class="text-gray-400 font-bold">Awaiting Funding</span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-4 text-right">
                                        <a href="{{ route('payroll.salary-computation.show', $cutoff->cutoff_period) }}" 
                                           class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold text-xs px-3.5 py-1.5 rounded-xl transition-all">
                                            View Details
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- EXECUTE BATCH COMPUTATION MODAL -->
        <!-- ========================================================================= -->
        <div x-show="showRunModal" style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div @click.away="showRunModal = false" class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-gray-100 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h3 class="text-base font-black font-outfit text-gray-900">Execute Batch Payroll Calculation</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Automated salary computation pulling attendance & compensation matrices.</p>
                    </div>
                    <button @click="showRunModal = false" class="text-gray-400 hover:text-gray-600 text-sm font-bold">✕</button>
                </div>

                <form action="{{ route('payroll.batch-compute') }}" method="POST" class="space-y-4">
                    @csrf
                    
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Target Cutoff Period *</label>
                        <select name="period" x-model="selectedPeriod" required class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl p-3 font-semibold text-gray-800 focus:outline-none focus:border-[#F44336]">
                            <optgroup label="August 2026 (Weekly Thu–Wed)">
                                <option value="2026-08-06_12">Aug 6 – Aug 12, 2026 (Week 1)</option>
                                <option value="2026-08-13_19">Aug 13 – Aug 19, 2026 (Week 2)</option>
                                <option value="2026-08-20_26">Aug 20 – Aug 26, 2026 (Week 3)</option>
                                <option value="2026-08-27_02">Aug 27 – Sep 2, 2026 (Week 4)</option>
                            </optgroup>
                            <optgroup label="September 2026 (Weekly Thu–Wed)">
                                <option value="2026-09-03_09">Sep 3 – Sep 9, 2026 (Week 1)</option>
                                <option value="2026-09-10_16">Sep 10 – Sep 16, 2026 (Week 2)</option>
                                <option value="2026-09-17_23">Sep 17 – Sep 23, 2026 (Week 3)</option>
                                <option value="2026-09-24_30">Sep 24 – Sep 30, 2026 (Week 4)</option>
                            </optgroup>
                            <optgroup label="October 2026 (Weekly Thu–Wed)">
                                <option value="2026-10-01_07">Oct 1 – Oct 7, 2026 (Week 1)</option>
                                <option value="2026-10-08_14">Oct 8 – Oct 14, 2026 (Week 2)</option>
                                <option value="2026-10-15_21">Oct 15 – Oct 21, 2026 (Week 3)</option>
                                <option value="2026-10-22_28">Oct 22 – Oct 28, 2026 (Week 4)</option>
                                <option value="2026-10-29_04">Oct 29 – Nov 4, 2026 (Week 5)</option>
                            </optgroup>
                            <option value="custom">Custom Date Range...</option>
                        </select>
                    </div>

                    <!-- Custom Date Selection Picker -->
                    <div x-show="selectedPeriod === 'custom'" x-transition class="grid grid-cols-2 gap-3 bg-gray-50 p-3.5 rounded-xl border border-gray-200">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 mb-1">Start Date *</label>
                            <input type="date" name="start_date" class="w-full text-xs bg-white border border-gray-200 rounded-lg p-2 font-semibold text-gray-900 focus:outline-none focus:border-[#F44336]">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 mb-1">End Date *</label>
                            <input type="date" name="end_date" class="w-full text-xs bg-white border border-gray-200 rounded-lg p-2 font-semibold text-gray-900 focus:outline-none focus:border-[#F44336]">
                        </div>
                    </div>

                    <!-- Department Scope Selection -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Department Scope *</label>
                        <select name="department_id" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl p-3 font-semibold text-gray-800 focus:outline-none focus:border-[#F44336]">
                            <option value="all">Entire Company (All Active Staff & Drivers)</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- AI Compliance Audit Toggle Switch -->
                    <div class="flex items-center justify-between bg-blue-50/60 border border-blue-100 rounded-xl p-3.5">
                        <div>
                            <span class="text-xs font-bold text-blue-900 block">
                                Enable Automated DOLE Compliance Scan
                            </span>
                            <span class="text-xs text-blue-700">Audit generated computations against Philippine labor standards.</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="run_ai_audit" value="1" checked class="sr-only peer">
                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-4 mt-6">
                        <button type="button" @click="showRunModal = false" class="text-xs font-bold text-gray-500 hover:text-gray-700 px-4 py-2">
                            Cancel
                        </button>
                        <button type="submit" class="bg-[#F44336] hover:bg-[#D32F2F] text-white font-black text-xs px-5 py-2.5 rounded-xl transition-all shadow-sm">
                            Run Batch Calculation
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- FORMULA TRANSPARENCY MODAL -->
        <!-- ========================================================================= -->
        <div x-show="showTransparencyModal" style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div @click.away="showTransparencyModal = false" class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl border border-gray-100 space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h3 class="text-base font-black font-outfit text-gray-900">Philippine Statutory Payroll Formulas</h3>
                        <p class="text-xs text-gray-500 mt-0.5">DOLE & BIR compliant calculation rules used by the Payroll Engine.</p>
                    </div>
                    <button @click="showTransparencyModal = false" class="text-gray-400 hover:text-gray-600 text-sm font-bold">✕</button>
                </div>

                <div class="space-y-4 text-xs">
                    <!-- Formula 1: Regular Staff Gross & Deductions -->
                    <div class="bg-gray-50 border border-gray-200 rounded-2xl p-4 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-black text-gray-900 text-xs">1. Regular Office Staff Model</span>
                            <span class="px-2 py-0.5 bg-emerald-50 text-emerald-800 text-[10px] font-black rounded-md border border-emerald-200">Statutory Taxable</span>
                        </div>
                        <p class="text-gray-600 text-xs">Monthly base rate divided into semi-monthly cutoffs with mandatory DOLE/BIR statutory deductions.</p>
                        <div class="bg-white border border-gray-200 rounded-xl p-3 font-mono text-gray-900 font-bold text-xs space-y-1">
                            <div>Gross = Cutoff Base Pay (15 Days) + Performance Bonus + Claims</div>
                            <div class="text-rose-700">Deductions = SSS (4.5%) + PhilHealth (2.5%) + Pag-IBIG (₱100) + Withholding Tax</div>
                            <div class="text-emerald-700 font-black">Net Pay = Gross Pay − Statutory Deductions</div>
                        </div>
                    </div>

                    <!-- Formula 2: TNVS Driver Model -->
                    <div class="bg-purple-50/40 border border-purple-200 rounded-2xl p-4 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-black text-purple-900 text-xs">2. TNVS Driver Earnings Model</span>
                            <span class="px-2 py-0.5 bg-purple-100 text-purple-800 text-[10px] font-black rounded-md border border-purple-200">Fleet Operations</span>
                        </div>
                        <p class="text-gray-700 text-xs">Operating under the standard Philippine transport model. Driver compensation is composed of base pay, trip earnings, and verified expense reimbursements.</p>
                        <div class="bg-white border border-purple-100 rounded-xl p-3 font-mono text-purple-900 font-bold text-xs space-y-1">
                            <div>Gross = Base Pay + Net Trip Fares + Holiday/OT + Reimbursements</div>
                            <div class="text-rose-700">Deductions = Standard Statutory (SSS, PhilHealth, Pag-IBIG) + Loans</div>
                            <div class="text-purple-900 font-black">Net Pay = Gross Pay - Total Deductions + Reimbursements</div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end border-t border-gray-100 pt-3">
                    <button type="button" @click="showTransparencyModal = false" class="bg-gray-900 text-white font-black text-xs px-5 py-2.5 rounded-xl">
                        Close Reference Guide
                    </button>
                </div>
            </div>
        </div>

    </div>

@endsection
