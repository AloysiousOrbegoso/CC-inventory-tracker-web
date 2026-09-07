<div class="py-4 sm:py-5 flex flex-col h-full overflow-y-auto">
    {{-- ═══ Primary Navigation ═══ --}}
    <div class="px-2.5 sm:px-3 mb-1">
        @php $isActive = request()->is('dashboard'); @endphp
        <a href="{{ $isActive ? '#' : url('/dashboard') }}" class="flex items-center gap-2 sm:gap-2.5 px-2.5 sm:px-3 py-2 sm:py-2.5 rounded-lg text-[12px] sm:text-[13px] font-medium text-[#1C1917] no-underline transition-all duration-150 {{ $isActive ? 'bg-[#B45353]/15 text-[#B45353] font-bold border-l-3 border-[#B45353] pointer-events-none' : 'hover:bg-[rgba(180,83,83,.08)] hover:text-[#B45353]' }}">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0">
                <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
            </svg>
            Dashboard
        </a>
        @php $isActive = request()->is('calendar*'); @endphp
        <a href="{{ $isActive ? '#' : url('/calendar') }}" class="flex items-center gap-2 sm:gap-2.5 px-2.5 sm:px-3 py-2 sm:py-2.5 rounded-lg text-[12px] sm:text-[13px] font-medium text-[#1C1917] no-underline transition-all duration-150 {{ $isActive ? 'bg-[#B45353]/15 text-[#B45353] font-bold border-l-3 border-[#B45353] pointer-events-none' : 'hover:bg-[rgba(180,83,83,.08)] hover:text-[#B45353]' }}">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
            Calendar
        </a>
        @php $isActive = request()->is('analytics*'); @endphp
        <a href="{{ $isActive ? '#' : url('/analytics') }}" class="flex items-center gap-2 sm:gap-2.5 px-2.5 sm:px-3 py-2 sm:py-2.5 rounded-lg text-[12px] sm:text-[13px] font-medium text-[#1C1917] no-underline transition-all duration-150 {{ $isActive ? 'bg-[#B45353]/15 text-[#B45353] font-bold border-l-3 border-[#B45353] pointer-events-none' : 'hover:bg-[rgba(180,83,83,.08)] hover:text-[#B45353]' }}">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0">
                <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
            </svg>
            Analytics
        </a>
        @php $isActive = request()->is('reports*'); @endphp
        <a href="{{ $isActive ? '#' : url('/reports') }}" class="flex items-center gap-2 sm:gap-2.5 px-2.5 sm:px-3 py-2 sm:py-2.5 rounded-lg text-[12px] sm:text-[13px] font-medium text-[#1C1917] no-underline transition-all duration-150 {{ $isActive ? 'bg-[#B45353]/15 text-[#B45353] font-bold border-l-3 border-[#B45353] pointer-events-none' : 'hover:bg-[rgba(180,83,83,.08)] hover:text-[#B45353]' }}">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
            </svg>
            Reports
        </a>
    </div>

    {{-- ═══ Business Management ═══ --}}
    <div class="px-2.5 sm:px-3 py-2.5 sm:py-3 mt-1.5 sm:mt-2 border-t border-[rgba(28,25,23,.12)]">
        @php $isActive = request()->is('branches') || request()->is('branches/*'); @endphp
        <a href="{{ $isActive ? '#' : url('/branches') }}" class="flex items-center gap-2 sm:gap-2.5 px-2.5 sm:px-3 py-2 sm:py-2.5 rounded-lg text-[12px] sm:text-[13px] font-medium text-[#1C1917] no-underline transition-all duration-150 {{ $isActive ? 'bg-[#B45353]/15 text-[#B45353] font-bold border-l-3 border-[#B45353] pointer-events-none' : 'hover:bg-[rgba(180,83,83,.08)] hover:text-[#B45353]' }}">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0">
                <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
            </svg>
            Businesses
        </a>
        @php $isActive = request()->is('business/workers*'); @endphp
        <a href="{{ $isActive ? '#' : url('/business/workers') }}" class="flex items-center gap-2 sm:gap-2.5 px-2.5 sm:px-3 py-2 sm:py-2.5 rounded-lg text-[12px] sm:text-[13px] font-medium text-[#1C1917] no-underline transition-all duration-150 {{ $isActive ? 'bg-[#B45353]/15 text-[#B45353] font-bold border-l-3 border-[#B45353] pointer-events-none' : 'hover:bg-[rgba(180,83,83,.08)] hover:text-[#B45353]' }}">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            Employees
        </a>
        @php $isActive = request()->is('business/verification'); @endphp
        <a href="{{ $isActive ? '#' : url('/business/verification') }}" class="flex items-center gap-2 sm:gap-2.5 px-2.5 sm:px-3 py-2 sm:py-2.5 rounded-lg text-[12px] sm:text-[13px] font-medium text-[#1C1917] no-underline transition-all duration-150 {{ $isActive ? 'bg-[#B45353]/15 text-[#B45353] font-bold border-l-3 border-[#B45353] pointer-events-none' : 'hover:bg-[rgba(180,83,83,.08)] hover:text-[#B45353]' }}">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            Legal
        </a>
    </div>

    {{-- ═══ Financial ═══ --}}
    <div class="px-2.5 sm:px-3 py-2.5 sm:py-3 border-t border-[rgba(28,25,23,.12)]">
        @php $isActive = request()->is('payments'); @endphp
        <a href="{{ $isActive ? '#' : url('/payments') }}" class="flex items-center gap-2 sm:gap-2.5 px-2.5 sm:px-3 py-2 sm:py-2.5 rounded-lg text-[12px] sm:text-[13px] font-medium text-[#1C1917] no-underline transition-all duration-150 {{ $isActive ? 'bg-[#B45353]/15 text-[#B45353] font-bold border-l-3 border-[#B45353] pointer-events-none' : 'hover:bg-[rgba(180,83,83,.08)] hover:text-[#B45353]' }}">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0">
                <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
            </svg>
            Payments
        </a>
    </div>

    {{-- ═══ Support ═══ --}}
    <div class="px-2.5 sm:px-3 py-2.5 sm:py-3 border-t border-[rgba(28,25,23,.12)]">
        @php $isActive = request()->is('help'); @endphp
        <a href="{{ $isActive ? '#' : url('/help') }}" class="flex items-center gap-2 sm:gap-2.5 px-2.5 sm:px-3 py-2 sm:py-2.5 rounded-lg text-[12px] sm:text-[13px] font-medium text-[#1C1917] no-underline transition-all duration-150 {{ $isActive ? 'bg-[#B45353]/15 text-[#B45353] font-bold border-l-3 border-[#B45353] pointer-events-none' : 'hover:bg-[rgba(180,83,83,.08)] hover:text-[#B45353]' }}">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0">
                <circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
            Help Center
        </a>
        @php $isActive = request()->is('about'); @endphp
        <a href="{{ $isActive ? '#' : url('/about') }}" class="flex items-center gap-2 sm:gap-2.5 px-2.5 sm:px-3 py-2 sm:py-2.5 rounded-lg text-[12px] sm:text-[13px] font-medium text-[#1C1917] no-underline transition-all duration-150 {{ $isActive ? 'bg-[#B45353]/15 text-[#B45353] font-bold border-l-3 border-[#B45353] pointer-events-none' : 'hover:bg-[rgba(180,83,83,.08)] hover:text-[#B45353]' }}">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
            </svg>
            About
        </a>
    </div>

    {{-- ═══ Logout ═══ --}}
    <div class="mt-auto px-2.5 sm:px-3 pt-2.5 sm:pt-3 border-t border-[rgba(28,25,23,.12)]">
        <form method="POST" action="{{ url('/logout') }}">
            @csrf
            <button type="submit" class="flex items-center gap-2 sm:gap-2.5 px-2.5 sm:px-3 py-2 sm:py-2.5 rounded-lg text-[12px] sm:text-[13px] font-semibold text-red-600 bg-red-50 border-none cursor-pointer w-full transition-all duration-150 hover:bg-red-100">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                Log Out
            </button>
        </form>
    </div>
</div>
