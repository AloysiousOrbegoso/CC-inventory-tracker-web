@extends('layouts.sidebar')

@section('title', 'Calendar')
@section('subtitle', 'Meetings and scheduled events across your branches.')

@section('content')
<div class="max-w-[1200px] mx-auto">
    <div class="grid grid-cols-1 lg:grid-cols-[1fr_1.2fr] gap-6">
        
        {{-- ═══ LEFT PANEL: SCHEDULE ═══ --}}
        <div class="bg-white border border-[rgba(28,25,23,.12)] rounded-2xl">
            <div class="p-6 pb-0">
                <h2 class="text-lg font-bold text-[#1C1917] mb-4">Schedule</h2>
            </div>
            <div class="px-6 pb-6">
                {{-- Week Selector --}}
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-xl font-extrabold text-[#B45353]" id="currentMonth">{{ now()->format('F') }}</h3>
                    <div class="flex gap-1.5">
                        <button class="w-8 h-8 rounded-lg border border-[rgba(28,25,23,.12)] bg-white flex items-center justify-center hover:bg-[#FDF5D6] transition-colors" onclick="changeWeek(-1)">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                        </button>
                        <button class="w-8 h-8 rounded-lg border border-[rgba(28,25,23,.12)] bg-white flex items-center justify-center hover:bg-[#FDF5D6] transition-colors" onclick="changeWeek(1)">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                        </button>
                    </div>
                </div>

                {{-- Week Days --}}
                <div class="flex gap-2 mb-5 pb-5 border-b border-[rgba(28,25,23,.12)]" id="weekDays">
                    @php
                        $today = now();
                        $startOfWeek = $today->copy()->startOfWeek();
                    @endphp
                    @for ($i = 0; $i < 5; $i++)
                        @php $day = $startOfWeek->copy()->addDays($i); @endphp
                        <div class="flex flex-col items-center gap-1 px-3 py-2 rounded-lg cursor-pointer transition-all hover:bg-[#FDF5D6] min-w-[52px] {{ $day->isToday() ? 'bg-[#B45353] text-white' : '' }}" data-date="{{ $day->toDateString() }}">
                            <span class="text-2xl font-bold leading-none {{ $day->isToday() ? 'text-white' : '' }}">{{ $day->format('d') }}</span>
                            <span class="text-[10px] font-semibold uppercase opacity-60 {{ $day->isToday() ? '!opacity-100 text-white' : '' }}">{{ $day->format('D') }}</span>
                        </div>
                    @endfor
                </div>

                {{-- Tabs --}}
                <div class="flex gap-4 mb-4 border-b border-[rgba(28,25,23,.12)] pb-2">
                    <button class="text-[13px] font-semibold text-[#B45353] border-b-2 border-[#B45353] pb-2 cal-tab active" data-tab="meetings" onclick="switchTab(this, 'meetings')">Meetings</button>
                    <button class="text-[13px] font-semibold text-[#1C1917] opacity-50 border-b-2 border-transparent pb-2 cal-tab" data-tab="tasks" onclick="switchTab(this, 'tasks')">Tasks</button>
                    <button class="text-[13px] font-semibold text-[#1C1917] opacity-50 border-b-2 border-transparent pb-2 cal-tab" data-tab="events" onclick="switchTab(this, 'events')">Events</button>
                </div>

                {{-- Meetings Content --}}
                <div id="meetingsContent">
                    @if(isset($weekMeetings) && $weekMeetings->count())
                        @foreach ($weekMeetings as $meeting)
                            <div class="border border-[rgba(28,25,23,.12)] rounded-xl p-3.5 mb-2.5">
                                <span class="inline-block px-3 py-1 rounded-full bg-[#FDF5D6] text-[10px] font-bold text-[#5C2D1B] mb-2">{{ $meeting->branch->name ?? 'General' }}</span>
                                <div class="text-[14px] font-bold text-[#1C1917] mb-1">{{ $meeting->title }}</div>
                                <div class="text-[12px] text-[#1C1917] opacity-60">{{ $meeting->start_time }} - {{ $meeting->end_time }}</div>
                                <div class="text-[12px] text-[#1C1917] opacity-50">{{ $meeting->location ?? 'Online' }}</div>
                                @if(auth()->user()->isSuperAdmin())
                                <button onclick="deleteMeeting({{ $meeting->id }})" class="mt-2 text-[11px] text-red-600 bg-transparent border-none cursor-pointer hover:underline">Delete</button>
                            @endif
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-8">
                            <div class="text-[32px] mb-3">📅</div>
                            <div class="text-[14px] font-bold text-[#1C1917] mb-1">No meetings this week</div>
                            <div class="text-[12px] text-[#1C1917] opacity-40 mb-4">Schedule a meeting to get started with your team.</div>
                            <div style="padding:12px;background:#FDF5D6;border-radius:8px;text-align:left;font-size:11px;color:#5C2D1B">
                                <div style="font-weight:700;margin-bottom:4px">💡 Quick tips:</div>
                                <div>• Use meetings for team syncs and standups</div>
                                <div>• Tasks help track individual responsibilities</div>
                                <div>• Events are great for deadlines and milestones</div>
                            </div>
                        </div>
                    @endif
                    @if(auth()->user()->isManager())
                    <button class="flex items-center gap-2 px-4 py-2.5 bg-[rgba(180,83,83,.08)] text-[#B45353] border border-[#B45353] rounded-lg text-[13px] font-semibold cursor-pointer transition-all hover:bg-[#B45353] hover:text-white mt-2.5" onclick="openModal()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Schedule a Meeting
                    </button>
                    @endif
                </div>

                {{-- Tasks Content --}}
                <div id="tasksContent" class="hidden">
                    <div class="text-center py-8">
                        <div class="text-[32px] mb-3">✅</div>
                        <div class="text-[14px] font-bold text-[#1C1917] mb-1">No tasks yet</div>
                        <div class="text-[12px] text-[#1C1917] opacity-40">Create tasks to assign action items to your team.</div>
                    </div>
                </div>

                {{-- Events Content --}}
                <div id="eventsContent" class="hidden">
                    <div class="text-center py-8">
                        <div class="text-[32px] mb-3">🎉</div>
                        <div class="text-[14px] font-bold text-[#1C1917] mb-1">No events yet</div>
                        <div class="text-[12px] text-[#1C1917] opacity-40">Add events for deadlines, holidays, or special occasions.</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══ RIGHT PANEL: MONTHLY CALENDAR ═══ --}}
        <div>
            <div class="bg-white border border-[rgba(28,25,23,.12)] rounded-2xl p-6 mb-5">
                <h3 class="text-xl font-extrabold text-[#1C1917] mb-4" id="monthlyTitle">{{ now()->format('F') }}</h3>
                
                {{-- Monthly Calendar Grid --}}
                <div class="grid grid-cols-7 gap-1 mb-5">
                    @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $dayName)
                        <div class="text-[10px] font-bold uppercase opacity-40 text-center py-2">{{ $dayName }}</div>
                    @endforeach

                    @php
                        $year = now()->year;
                        $month = now()->month;
                        $firstDay = Carbon\Carbon::create($year, $month, 1);
                        $daysInMonth = $firstDay->daysInMonth;
                        $startDayOfWeek = $firstDay->dayOfWeek;
                        $prevMonth = $firstDay->copy()->subMonth();
                        $prevMonthDays = $prevMonth->daysInMonth;
                    @endphp

                    {{-- Previous month days --}}
                    @for ($i = $startDayOfWeek - 1; $i >= 0; $i--)
                        <div class="flex items-center justify-center h-9 text-[13px] font-medium rounded-lg opacity-30 cursor-pointer hover:bg-[#FDF5D6]">{{ $prevMonthDays - $i }}</div>
                    @endfor

                    {{-- Current month days --}}
                    @for ($day = 1; $day <= $daysInMonth; $day++)
                        @php
                            $isToday = $day === now()->day;
                            $hasEvent = in_array($day, $eventDays ?? []);
                        @endphp
                        <div class="flex items-center justify-center h-9 text-[13px] font-medium rounded-lg cursor-pointer transition-all hover:bg-[#FDF5D6] {{ $isToday ? 'bg-[#B45353] text-white font-bold' : '' }} {{ $hasEvent && !$isToday ? 'border-2 border-[#B45353] text-[#B45353] font-semibold' : '' }}" 
                             data-day="{{ $day }}" onclick="selectDay({{ $day }}, event)">
                            {{ $day }}
                        </div>
                    @endfor

                    {{-- Next month days --}}
                    @php
                        $totalCells = $startDayOfWeek + $daysInMonth;
                        $remaining = (7 - ($totalCells % 7)) % 7;
                    @endphp
                    @for ($i = 1; $i <= $remaining; $i++)
                        <div class="flex items-center justify-center h-9 text-[13px] font-medium rounded-lg opacity-30 cursor-pointer hover:bg-[#FDF5D6]">{{ $i }}</div>
                    @endfor
                </div>

                {{-- Tabs --}}
                <div class="flex gap-4 border-b border-[rgba(28,25,23,.12)] pb-2 mb-4">
                    <button class="text-[13px] font-semibold text-[#B45353] border-b-2 border-[#B45353] pb-2 monthly-tab active" data-tab="monthly-meetings" onclick="switchMonthlyTab(this, 'monthly-meetings')">Meetings</button>
                    <button class="text-[13px] font-semibold text-[#1C1917] opacity-50 border-b-2 border-transparent pb-2 monthly-tab" data-tab="monthly-tasks" onclick="switchMonthlyTab(this, 'monthly-tasks')">Tasks</button>
                    <button class="text-[13px] font-semibold text-[#1C1917] opacity-50 border-b-2 border-transparent pb-2 monthly-tab" data-tab="monthly-events" onclick="switchMonthlyTab(this, 'monthly-events')">Events</button>
                </div>

                {{-- Monthly Meetings Content --}}
                <div id="monthlyMeetingsContent">
                    @if(isset($meetings) && $meetings->count())
                        @foreach ($meetings->where('meeting_type', 'meeting')->take(3) as $meeting)
                            <div class="border border-[rgba(28,25,23,.12)] rounded-xl p-3.5 mb-2.5">
                                <span class="inline-block px-3 py-1 rounded-full bg-[#FDF5D6] text-[10px] font-bold text-[#5C2D1B] mb-2">{{ $meeting->branch->name ?? 'General' }}</span>
                                <div class="text-[14px] font-bold text-[#1C1917] mb-1">{{ $meeting->title }}</div>
                                <div class="text-[12px] text-[#1C1917] opacity-60">{{ \Carbon\Carbon::parse($meeting->date)->format('M d') }} | {{ $meeting->start_time }} - {{ $meeting->end_time }}</div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-6">
                            <div class="text-[24px] mb-2">📋</div>
                            <div class="text-[12px] font-semibold text-[#1C1917] mb-1">No meetings this month</div>
                            <div class="text-[11px] text-[#1C1917] opacity-40">Use the week panel to schedule one.</div>
                        </div>
                    @endif
                </div>

                {{-- Monthly Tasks Content --}}
                <div id="monthlyTasksContent" class="hidden">
                    @if(isset($meetings) && $meetings->where('meeting_type', 'task')->count())
                        @foreach ($meetings->where('meeting_type', 'task')->take(3) as $task)
                            <div class="border border-[rgba(28,25,23,.12)] rounded-xl p-3.5 mb-2.5">
                                <span class="inline-block px-3 py-1 rounded-full bg-[#FDF5D6] text-[10px] font-bold text-[#5C2D1B] mb-2">{{ $task->branch->name ?? 'General' }}</span>
                                <div class="text-[14px] font-bold text-[#1C1917] mb-1">{{ $task->title }}</div>
                                <div class="text-[12px] text-[#1C1917] opacity-60">{{ \Carbon\Carbon::parse($task->date)->format('M d') }} | {{ $task->start_time }} - {{ $task->end_time }}</div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-6">
                            <div class="text-[24px] mb-2">✅</div>
                            <div class="text-[12px] font-semibold text-[#1C1917] mb-1">No tasks this month</div>
                            <div class="text-[11px] text-[#1C1917] opacity-40">Create tasks to track team responsibilities.</div>
                        </div>
                    @endif
                </div>

                {{-- Monthly Events Content --}}
                <div id="monthlyEventsContent" class="hidden">
                    <div class="text-center py-6">
                        <div class="text-[24px] mb-2">🎉</div>
                        <div class="text-[12px] font-semibold text-[#1C1917] mb-1">No events this month</div>
                        <div class="text-[11px] text-[#1C1917] opacity-40">Add events for special occasions or deadlines.</div>
                    </div>
                </div>
            </div>

            {{-- ═══ UPCOMING SECTION ═══ --}}
            <div class="bg-white border border-[rgba(28,25,23,.12)] rounded-2xl p-5">
                <h3 class="text-base font-bold text-[#1C1917] mb-4">Upcoming</h3>
                @if(isset($upcomingMeetings) && $upcomingMeetings->count())
                    @foreach ($upcomingMeetings as $upcoming)
                        <div class="flex items-center gap-3 py-2.5 border-b border-[rgba(28,25,23,.12)] last:border-0">
                            <span class="text-2xl font-extrabold text-[#1C1917] min-w-[40px]">{{ \Carbon\Carbon::parse($upcoming->date)->format('d') }}</span>
                            <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-[#e0e7ff] text-[#3730a3]">{{ $upcoming->branch->name ?? 'General' }}</span>
                            <div class="flex-1">
                                <div class="text-[13px] font-semibold text-[#1C1917]">{{ $upcoming->title }}</div>
                            </div>
                            <span class="text-[12px] text-[#1C1917] opacity-50">{{ $upcoming->start_time }}</span>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-6">
                        <div class="text-[24px] mb-2">📆</div>
                        <div class="text-[12px] font-semibold text-[#1C1917] mb-1">All clear</div>
                        <div class="text-[11px] text-[#1C1917] opacity-40">No upcoming meetings scheduled.</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════ SCHEDULE MEETING MODAL ═══════════════════════════ --}}
<div class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center" id="scheduleModal">
    <div class="bg-white rounded-2xl shadow-xl w-[90%] max-w-[500px] max-h-[90vh] overflow-y-auto animate-[slideIn_0.2s_ease]">
        <div class="px-6 py-5 border-b border-[rgba(28,25,23,.12)] flex items-center justify-between">
            <h3 class="text-base font-bold text-[#1C1917]">Schedule Meeting</h3>
            <button class="w-8 h-8 rounded-lg bg-[#FDF5D6] flex items-center justify-center hover:bg-[rgba(28,25,23,.1)] transition-colors" onclick="closeModal()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="px-6 py-5">
            <form id="scheduleForm">
                <div class="mb-4">
                    <label class="block text-[13px] font-semibold text-[#1C1917] mb-1.5">Meeting Title</label>
                    <input type="text" class="w-full px-3.5 py-2.5 border border-[rgba(28,25,23,.12)] rounded-lg text-[13px] focus:outline-none focus:border-[#B45353] transition-colors" placeholder="Enter meeting title" id="meetingTitle">
                </div>

                <div class="mb-4">
                    <label class="block text-[13px] font-semibold text-[#1C1917] mb-1.5">Time</label>
                    <div class="grid grid-cols-[1fr_auto_1fr] gap-3 items-end">
                        <div class="flex items-center gap-2">
                            <input type="text" class="w-14 px-3 py-2.5 border border-[rgba(28,25,23,.12)] rounded-lg text-[13px] text-center focus:outline-none focus:border-[#B45353]" placeholder="00" maxlength="2" id="startHour">
                            <span class="opacity-50">:</span>
                            <input type="text" class="w-14 px-3 py-2.5 border border-[rgba(28,25,23,.12)] rounded-lg text-[13px] text-center focus:outline-none focus:border-[#B45353]" placeholder="00" maxlength="2" id="startMin">
                            <select class="px-3 py-2.5 border border-[rgba(28,25,23,.12)] rounded-lg text-[13px] cursor-pointer bg-white focus:outline-none focus:border-[#B45353]" id="startAmPm">
                                <option>AM</option>
                                <option>PM</option>
                            </select>
                        </div>
                        <span class="pb-2.5 text-[13px] opacity-50">to</span>
                        <div class="flex items-center gap-2">
                            <input type="text" class="w-14 px-3 py-2.5 border border-[rgba(28,25,23,.12)] rounded-lg text-[13px] text-center focus:outline-none focus:border-[#B45353]" placeholder="00" maxlength="2" id="endHour">
                            <span class="opacity-50">:</span>
                            <input type="text" class="w-14 px-3 py-2.5 border border-[rgba(28,25,23,.12)] rounded-lg text-[13px] text-center focus:outline-none focus:border-[#B45353]" placeholder="00" maxlength="2" id="endMin">
                            <select class="px-3 py-2.5 border border-[rgba(28,25,23,.12)] rounded-lg text-[13px] cursor-pointer bg-white focus:outline-none focus:border-[#B45353]" id="endAmPm">
                                <option>AM</option>
                                <option>PM</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-[13px] font-semibold text-[#1C1917] mb-1.5">Business</label>
                    <select class="w-full px-3.5 py-2.5 border border-[rgba(28,25,23,.12)] rounded-lg text-[13px] cursor-pointer bg-white focus:outline-none focus:border-[#B45353]" id="businessSelect">
                        <option value="" disabled selected>choose business</option>
                        @if(isset($branches))
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-[13px] font-semibold text-[#1C1917] mb-1.5">Meeting Description</label>
                    <textarea class="w-full px-3.5 py-2.5 border border-[rgba(28,25,23,.12)] rounded-lg text-[13px] min-h-[80px] resize-y focus:outline-none focus:border-[#B45353]" placeholder="What is the meeting about?" id="meetingDescription"></textarea>
                </div>
            </form>
        </div>
        <div class="px-6 py-4 border-t border-[rgba(28,25,23,.12)] flex justify-end gap-2.5">
            <button class="px-5 py-2.5 bg-[#FDF5D6] text-[#1C1917] border border-[rgba(28,25,23,.12)] rounded-lg text-[13px] font-semibold cursor-pointer hover:bg-[#F5F0E0] transition-colors" onclick="closeModal()">Cancel</button>
            <button class="px-5 py-2.5 bg-[#B45353] text-white border-none rounded-lg text-[13px] font-semibold cursor-pointer hover:bg-[#9F4242] transition-colors" onclick="submitMeeting()">Schedule a Meeting</button>
        </div>
    </div>
</div>

<style>
    @keyframes slideIn {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<script>
    // ══ MODAL FUNCTIONS ══════════════════════════════════════════════════
    function openModal() {
        const modal = document.getElementById('scheduleModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal() {
        const modal = document.getElementById('scheduleModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    document.getElementById('scheduleModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeModal();
    });

    // ══ TAB FUNCTIONALITY ═══════════════════════════════════════════════
    function switchTab(el, tabName) {
        // Update tab styles
        el.parentElement.querySelectorAll('.cal-tab').forEach(t => {
            t.classList.remove('active', 'text-[#B45353]', 'border-[#B45353]');
            t.classList.add('text-[#1C1917]', 'opacity-50', 'border-transparent');
        });
        el.classList.add('active', 'text-[#B45353]', 'border-[#B45353]');
        el.classList.remove('text-[#1C1917]', 'opacity-50', 'border-transparent');

        // Show/hide content
        document.getElementById('meetingsContent').classList.toggle('hidden', tabName !== 'meetings');
        document.getElementById('tasksContent').classList.toggle('hidden', tabName !== 'tasks');
        document.getElementById('eventsContent').classList.toggle('hidden', tabName !== 'events');
    }

    function switchMonthlyTab(el, tabName) {
        // Update tab styles
        el.parentElement.querySelectorAll('.monthly-tab').forEach(t => {
            t.classList.remove('active', 'text-[#B45353]', 'border-[#B45353]');
            t.classList.add('text-[#1C1917]', 'opacity-50', 'border-transparent');
        });
        el.classList.add('active', 'text-[#B45353]', 'border-[#B45353]');
        el.classList.remove('text-[#1C1917]', 'opacity-50', 'border-transparent');

        // Show/hide content
        document.getElementById('monthlyMeetingsContent').classList.toggle('hidden', tabName !== 'monthly-meetings');
        document.getElementById('monthlyTasksContent').classList.toggle('hidden', tabName !== 'monthly-tasks');
        document.getElementById('monthlyEventsContent').classList.toggle('hidden', tabName !== 'monthly-events');
    }

    // ══ WEEK DAY SELECTION ═══════════════════════════════════════════════
    document.querySelectorAll('#weekDays .week-day').forEach(day => {
        day.addEventListener('click', function() {
            document.querySelectorAll('#weekDays .week-day').forEach(d => d.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // ══ CALENDAR DAY SELECTION ═══════════════════════════════════════════
    function selectDay(day, e) {
        document.querySelectorAll('.calendar-day').forEach(d => d.classList.remove('selected'));
        e.target.classList.add('selected');
    }

    // ══ WEEK NAVIGATION ══════════════════════════════════════════════════
    let currentWeekOffset = 0;
    let selectedDate = new Date();

    function changeWeek(direction) {
        currentWeekOffset += direction;
        updateWeekDisplay();
    }

    function updateWeekDisplay() {
        const today = new Date();
        const startOfWeek = new Date(today);
        startOfWeek.setDate(today.getDate() - today.getDay() + (currentWeekOffset * 7));
        
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'];
        
        document.getElementById('currentMonth').textContent = monthNames[startOfWeek.getMonth()];
        
        const weekDaysContainer = document.getElementById('weekDays');
        weekDaysContainer.innerHTML = '';
        
        for (let i = 0; i < 5; i++) {
            const day = new Date(startOfWeek);
            day.setDate(startOfWeek.getDate() + i);
            
            const isToday = day.toDateString() === today.toDateString();
            const dayEl = document.createElement('div');
            dayEl.className = `flex flex-col items-center gap-1 px-3 py-2 rounded-lg cursor-pointer transition-all hover:bg-[#FDF5D6] min-w-[52px] ${isToday ? 'bg-[#B45353] text-white' : ''}`;
            dayEl.dataset.date = day.toISOString().split('T')[0];
            dayEl.onclick = function() {
                document.querySelectorAll('#weekDays .week-day').forEach(d => d.classList.remove('active'));
                this.classList.add('active');
                selectedDate = day;
            };
            
            dayEl.innerHTML = `
                <span class="text-2xl font-bold leading-none ${isToday ? 'text-white' : ''}">${day.getDate()}</span>
                <span class="text-[10px] font-semibold uppercase opacity-60 ${isToday ? '!opacity-100 text-white' : ''}">${day.toLocaleDateString('en-US', { weekday: 'short' })}</span>
            `;
            
            weekDaysContainer.appendChild(dayEl);
        }
    }

    // ══ FORM SUBMISSION ══════════════════════════════════════════════════
    function submitMeeting() {
        const title = document.getElementById('meetingTitle').value;
        const startHour = document.getElementById('startHour').value;
        const startMin = document.getElementById('startMin').value;
        const endHour = document.getElementById('endHour').value;
        const endMin = document.getElementById('endMin').value;
        const business = document.getElementById('businessSelect').value;
        const description = document.getElementById('meetingDescription').value;

        if (!title.trim()) { alert('Please enter a meeting title.'); return; }
        if (!startHour || !startMin || !endHour || !endMin) { alert('Please fill in all time fields.'); return; }
        if (!business) { alert('Please select a business.'); return; }
        if (!description.trim()) { alert('Please enter a meeting description.'); return; }

        const formData = {
            title: title,
            start_time: `${startHour}:${startMin} ${document.getElementById('startAmPm').value}`,
            end_time: `${endHour}:${endMin} ${document.getElementById('endAmPm').value}`,
            branch_id: business,
            description: description,
            date: selectedDate.toISOString().split('T')[0],
            meeting_type: 'meeting',
            location: 'Online'
        };

        fetch('{{ url("/calendar/meetings") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                addMeetingToUI(data.data);
                document.getElementById('scheduleForm').reset();
                closeModal();
                alert('Meeting scheduled successfully!');
            } else {
                alert('Error: ' + (data.message || 'Failed to create meeting'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while scheduling the meeting.');
        });
    }

    function addMeetingToUI(data) {
        const meetingsContent = document.getElementById('meetingsContent');
        const emptyState = meetingsContent.querySelector('.text-center');
        if (emptyState) emptyState.remove();
        
        const businessSelect = document.getElementById('businessSelect');
        const businessName = data.branch?.name || businessSelect.options[businessSelect.selectedIndex]?.text || 'General';
        
        const meetingCard = document.createElement('div');
        meetingCard.className = 'border border-[rgba(28,25,23,.12)] rounded-xl p-3.5 mb-2.5';
        meetingCard.dataset.meetingId = data.id;
        meetingCard.innerHTML = `
            <span class="inline-block px-3 py-1 rounded-full bg-[#FDF5D6] text-[10px] font-bold text-[#5C2D1B] mb-2">${businessName}</span>
            <div class="text-[14px] font-bold text-[#1C1917] mb-1">${data.title}</div>
            <div class="text-[12px] text-[#1C1917] opacity-60">${data.start_time} - ${data.end_time}</div>
            <div class="text-[12px] text-[#1C1917] opacity-50">${data.location || 'Online'}</div>
            @if(auth()->user()->isSuperAdmin())
            <button onclick="deleteMeeting(${data.id})" class="mt-2 text-[11px] text-red-600 bg-transparent border-none cursor-pointer hover:underline">Delete</button>
            @endif
        `;
        
        meetingsContent.insertBefore(meetingCard, meetingsContent.querySelector('button'));
    }

    // ══ DELETE MEETING ══════════════════════════════════════════════════
    function deleteMeeting(id) {
        if (!confirm('Are you sure you want to delete this meeting?')) return;

        fetch(`/calendar/meetings/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                const card = document.querySelector(`[data-meeting-id="${id}"]`);
                if (card) card.remove();
                alert('Meeting deleted successfully.');
            } else {
                alert('Error: ' + (data.message || 'Failed to delete meeting'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the meeting.');
        });
    }

    // ══ TIME INPUT VALIDATION ════════════════════════════════════════════
    document.querySelectorAll('.form-time-input input').forEach(input => {
        input.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length === 2) {
                const parent = this.closest('.form-time-input');
                if (parent) {
                    const inputs = parent.querySelectorAll('input');
                    const currentIndex = Array.from(inputs).indexOf(this);
                    if (currentIndex < inputs.length - 1) {
                        inputs[currentIndex + 1].focus();
                    }
                }
            }
        });
    });
</script>
@endsection
