<style>
    /* ── Settings Drawer ── */
    .settings-overlay {
        position: fixed; inset: 0; z-index: 9999;
        background: rgba(28,25,23,.5); backdrop-filter: blur(4px);
        visibility: hidden; justify-content: flex-end;
        cursor: pointer; opacity: 0;
        transition: opacity .25s ease, visibility .25s ease;
        overflow: hidden;
    }
    .settings-overlay.is-open { visibility: visible; opacity: 1; }

    .settings-drawer {
        width: 420px; max-width: 90vw; height: 100vh;
        background: #fff; border-radius: 20px 0 0 20px;
        padding: 28px 28px; display: flex; flex-direction: column;
        box-shadow: -8px 0 40px rgba(0,0,0,.15);
        transform: translateX(100%);
        transition: transform .28s cubic-bezier(.22,.68,0,1);
        cursor: default;
    }
    .settings-overlay.is-open .settings-drawer {
        transform: translateX(0);
    }

    .settings-drawer-scroll {
        flex: 1; overflow-y: auto; padding-right: 4px;
    }
    .settings-drawer-scroll::-webkit-scrollbar { width: 4px; }
    .settings-drawer-scroll::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }

    .settings-drawer-header {
        display: flex; align-items: center; gap: 12px;
        margin-bottom: 20px; padding-bottom: 16px;
        border-bottom: 1.5px solid var(--border); flex-shrink: 0;
    }
    .settings-drawer-header h2 { font-size: 20px; font-weight: 800; color: var(--brown); }

    /* ── Profile Card ── */
    .settings-profile-card {
        display: flex; align-items: center; gap: 14px;
        padding: 14px 16px; margin-bottom: 20px;
        background: rgba(180,83,83,.08); border: 1px solid rgba(180,83,83,.18);
        border-radius: 12px;
    }
    .settings-profile-avatar {
        width: 44px; height: 44px; border-radius: 50%;
        background: var(--terra); color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: 17px; font-weight: 800; flex-shrink: 0;
        text-transform: uppercase;
    }
    .settings-profile-info .name  { font-size: 15px; font-weight: 700; color: var(--brown); }
    .settings-profile-info .email { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
    .settings-profile-info .role  { font-size: 11px; font-weight: 600; color: var(--terra); margin-top: 3px; text-transform: capitalize; }

    /* ── Settings Sections ── */
    .settings-section {
        background: #fff; border: 1px solid var(--border);
        border-radius: 12px; padding: 20px;
        margin-bottom: 16px;
    }
    .settings-section h3 {
        font-size: 11px; font-weight: 700; text-transform: uppercase;
        letter-spacing: .06em; color: var(--text-muted); margin-bottom: 14px;
        display: flex; align-items: center; gap: 8px;
    }
    .settings-section h3 svg { opacity: .6; stroke: var(--terra); }

    /* ── Form Fields ── */
    .settings-field { margin-bottom: 14px; }
    .settings-field:last-child { margin-bottom: 0; }
    .settings-field-label {
        font-size: 11px; font-weight: 700; text-transform: uppercase;
        letter-spacing: .04em; color: var(--text-muted); margin-bottom: 5px;
    }
    .settings-input {
        width: 100%; height: 42px; padding: 0 14px;
        background: #F8F6F3; border: 1.5px solid var(--border);
        border-radius: 10px; font-size: 13px; color: var(--brown);
        font-family: var(--font);
        transition: border-color .15s, box-shadow .15s;
    }
    .settings-input::placeholder { color: rgba(28,25,23,.3); }
    .settings-input:focus {
        outline: none; border-color: var(--terra);
        box-shadow: 0 0 0 3px rgba(180,83,83,.12);
    }
    .settings-input.error { border-color: #dc2626; }
    .settings-field-error {
        font-size: 11px; color: #dc2626; font-weight: 600;
        margin-top: 4px; display: none;
    }
    .settings-field-error.is-visible { display: block; }
    .settings-field-success {
        font-size: 11px; color: #16a34a; font-weight: 600;
        margin-top: 4px; display: none;
    }
    .settings-field-success.is-visible { display: block; }

    /* ── Buttons ── */
    .settings-btn {
        padding: 9px 20px; border-radius: 8px;
        font-size: 13px; font-weight: 600; font-family: var(--font);
        cursor: pointer; transition: all .12s ease; border: none;
        display: inline-flex; align-items: center; gap: 6px;
    }
    .settings-btn--primary { background: var(--terra); color: #fff; }
    .settings-btn--primary:hover { background: #9F4242; }
    .settings-btn--primary:disabled { opacity: .5; cursor: not-allowed; }
    .settings-btn--secondary {
        background: transparent; color: var(--brown);
        border: 1.5px solid var(--border);
    }
    .settings-btn--secondary:hover { background: rgba(28,25,23,.04); }

    /* ── Quick Action Links ── */
    .settings-link-card {
        display: flex; align-items: center; gap: 10px;
        padding: 12px 14px; margin-bottom: 8px;
        background: rgba(180,83,83,.05); border: 1px solid rgba(180,83,83,.12);
        border-radius: 10px; text-decoration: none; color: var(--brown);
        transition: all .15s ease;
    }
    .settings-link-card:hover { background: rgba(180,83,83,.08); border-color: var(--terra); }
    .settings-link-card:last-child { margin-bottom: 0; }
    .settings-link-card__text { flex: 1; }
    .settings-link-card__title { font-size: 13px; font-weight: 600; }
    .settings-link-card__sub { font-size: 11px; color: var(--text-muted); margin-top: 1px; }
    .settings-link-card__arrow { color: var(--text-muted); font-size: 16px; }
    .settings-link-card svg { stroke: var(--terra); flex-shrink: 0; }

    /* ── Info Rows ── */
    .settings-info-row {
        display: flex; align-items: center; justify-content: space-between;
        padding: 11px 0; font-size: 13.5px; font-weight: 500; color: var(--brown);
        border-bottom: 1px solid rgba(28,25,23,.06);
    }
    .settings-info-row:last-child { border-bottom: none; }
    .settings-info-value { color: var(--text-muted); font-size: 13px; font-weight: 400; }

    /* ── Logout ── */
    .settings-logout-section {
        padding-top: 16px; border-top: 1.5px solid var(--border); flex-shrink: 0;
    }
    .settings-btn-logout {
        display: flex; align-items: center; justify-content: center;
        gap: 10px; width: 100%; height: 48px;
        background: transparent; color: #dc2626;
        border: 1.5px solid #dc2626; border-radius: 12px;
        font-size: 15px; font-weight: 700; font-family: var(--font);
        cursor: pointer; transition: all .15s ease;
    }
    .settings-btn-logout:hover { background: #dc2626; color: #fff; }

    /* ── Toast ── */
    .settings-toast-container {
        position: fixed; top: 20px; right: 20px; z-index: 10000;
        display: flex; flex-direction: column; gap: 8px;
    }
    .settings-toast {
        padding: 12px 20px; border-radius: 8px;
        font-size: 13px; font-weight: 600; color: #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,.12), 0 8px 24px rgba(0,0,0,.08);
        animation: settings-toast-in .25s ease; max-width: 360px;
    }
    .settings-toast--success { background: #16a34a; }
    .settings-toast--error { background: #dc2626; }
    @keyframes settings-toast-in {
        from { opacity: 0; transform: translateX(40px); }
        to { opacity: 1; transform: translateX(0); }
    }
</style>

{{-- ═══════════════════════════════════════════════════════
     SETTINGS DRAWER OVERLAY
     ═══════════════════════════════════════════════════════ --}}
<div class="settings-overlay" id="settingsOverlay" onclick="closeSettings(event)">
    <div class="settings-drawer" onclick="event.stopPropagation()">
        {{-- Header --}}
        <div class="settings-drawer-header">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--terra)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="3"/>
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
            </svg>
            <h2>Settings</h2>
        </div>

        <div class="settings-drawer-scroll">
            @php
                $sUser = (object) [
                    'name' => 'Admin Owner',
                    'email' => 'admin@nita.com',
                    'role' => 'super_admin',
                    'branch_id' => null,
                    'created_at' => now()->subMonths(6),
                    'id' => 1,
                ];
            @endphp

            {{-- Profile Card --}}
            <div class="settings-profile-card">
                <div class="settings-profile-avatar">{{ mb_substr($sUser->name, 0, 1) }}</div>
                <div class="settings-profile-info">
                    <div class="name">{{ $sUser->name }}</div>
                    <div class="email">{{ $sUser->email }}</div>
                    <div class="role">{{ str_replace('_', ' ', $sUser->role) }}</div>
                </div>
            </div>

            {{-- Profile Form --}}
            <div class="settings-section">
                <h3>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                    </svg>
                    Profile
                </h3>
                <form id="settings-profile-form" onsubmit="return settingsUpdateProfile(event)">
                    @csrf
                    @method('PUT')
                    <div class="settings-field">
                        <div class="settings-field-label">Name</div>
                        <input type="text" class="settings-input" id="settings-name" value="{{ $sUser->name }}" required>
                        <div class="settings-field-error" id="settings-error-name"></div>
                    </div>
                    <div class="settings-field">
                        <div class="settings-field-label">Email</div>
                        <input type="email" class="settings-input" id="settings-email" value="{{ $sUser->email }}" required>
                        <div class="settings-field-error" id="settings-error-email"></div>
                    </div>
                    <div class="settings-field-success" id="settings-profile-success"></div>
                    <button type="submit" class="settings-btn settings-btn--primary" id="settings-profile-btn">Save Changes</button>
                </form>
            </div>

            {{-- Change Password --}}
            <div class="settings-section">
                <h3>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    Change Password
                </h3>
                <form id="settings-password-form" onsubmit="return settingsUpdatePassword(event)">
                    @csrf
                    @method('PUT')
                    <div class="settings-field">
                        <div class="settings-field-label">Current Password</div>
                        <input type="password" class="settings-input" id="settings-pw-current" placeholder="Enter current password" required>
                        <div class="settings-field-error" id="settings-error-current"></div>
                    </div>
                    <div class="settings-field">
                        <div class="settings-field-label">New Password</div>
                        <input type="password" class="settings-input" id="settings-pw-new" placeholder="Minimum 6 characters" minlength="6" required>
                        <div class="settings-field-error" id="settings-error-new"></div>
                    </div>
                    <div class="settings-field">
                        <div class="settings-field-label">Confirm New Password</div>
                        <input type="password" class="settings-input" id="settings-pw-confirm" placeholder="Re-enter new password" minlength="6" required>
                        <div class="settings-field-error" id="settings-error-confirm"></div>
                    </div>
                    <div class="settings-field-success" id="settings-pw-success"></div>
                    <button type="submit" class="settings-btn settings-btn--primary" id="settings-pw-btn">Update Password</button>
                </form>
            </div>

            {{-- Quick Actions --}}
            <div class="settings-section">
                <h3>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                    </svg>
                    Quick Actions
                </h3>
                <a href="{{ url('/business/workers') }}?worker={{ $sUser->id }}" class="settings-link-card" id="settings-profile-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                    </svg>
                    <div class="settings-link-card__text">
                        <div class="settings-link-card__title">Edit Full Profile</div>
                        <div class="settings-link-card__sub">Update phone, address, skills, schedule, and more</div>
                    </div>
                    <span class="settings-link-card__arrow">&rarr;</span>
                </a>
                <a href="{{ url('/ingredients') }}" class="settings-link-card">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 8h1a4 4 0 1 1 0 8h-1"/><path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4Z"/>
                    </svg>
                    <div class="settings-link-card__text">
                        <div class="settings-link-card__title">Manage Ingredients</div>
                        <div class="settings-link-card__sub">Add, edit, or remove raw materials</div>
                    </div>
                    <span class="settings-link-card__arrow">&rarr;</span>
                </a>
                <a href="{{ url('/business/recipes') }}" class="settings-link-card">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                    </svg>
                    <div class="settings-link-card__text">
                        <div class="settings-link-card__title">Manage Recipes</div>
                        <div class="settings-link-card__sub">Update product recipes and procedures</div>
                    </div>
                    <span class="settings-link-card__arrow">&rarr;</span>
                </a>
                <a href="{{ url('/alerts') }}" class="settings-link-card">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                    <div class="settings-link-card__text">
                        <div class="settings-link-card__title">View Alerts</div>
                        <div class="settings-link-card__sub">Check pending discrepancy alerts</div>
                    </div>
                    <span class="settings-link-card__arrow">&rarr;</span>
                </a>
                <a href="{{ url('/api-docs') }}" class="settings-link-card">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                    </svg>
                    <div class="settings-link-card__text">
                        <div class="settings-link-card__title">API Documentation</div>
                        <div class="settings-link-card__sub">View the API reference</div>
                    </div>
                    <span class="settings-link-card__arrow">&rarr;</span>
                </a>
            </div>

            {{-- Account Info --}}
            <div class="settings-section">
                <h3>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
                    </svg>
                    Account Info
                </h3>
                <div class="settings-info-row">
                    <span>Role</span>
                    <span class="settings-info-value">{{ ucwords(str_replace('_', ' ', $sUser->role)) }}</span>
                </div>
                @if ($sUser->branch_id)
                    <div class="settings-info-row">
                        <span>Branch</span>
                        <span class="settings-info-value">{{ $sUser->branch->name ?? '—' }}</span>
                    </div>
                @endif
                <div class="settings-info-row">
                    <span>Member Since</span>
                    <span class="settings-info-value">{{ $sUser->created_at?->format('M Y') ?? '—' }}</span>
                </div>
            </div>

            {{-- Preferences --}}
            <div class="settings-section">
                <h3>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                    </svg>
                    Preferences
                </h3>
                <div class="settings-info-row">
                    <span>Currency</span>
                    <span class="settings-info-value">Philippine Peso (&#8369;)</span>
                </div>
                <div class="settings-info-row">
                    <span>Language</span>
                    <span class="settings-info-value">English</span>
                </div>
                <div class="settings-info-row">
                    <span>Notifications</span>
                    <span class="settings-info-value">Enabled</span>
                </div>
                <div class="settings-info-row">
                    <span>Timezone</span>
                    <span class="settings-info-value">Asia/Manila (PHT)</span>
                </div>
            </div>
        </div>

        {{-- Logout --}}
        <div class="settings-logout-section">
            <button type="button" class="settings-btn-logout" onclick="alert('Logout functionality will be connected later.')">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                Log Out
            </button>
        </div>
    </div>
</div>

{{-- Toast container --}}
<div class="settings-toast-container" id="settingsToastContainer"></div>

<script>
(function () {
    // ── Open / Close ──
    var overlay = document.getElementById('settingsOverlay');

    window.toggleSettings = function () {
        if (overlay.classList.contains('is-open')) {
            closeSettingsDrawer();
        } else {
            overlay.classList.add('is-open');
            document.body.style.overflow = 'hidden';
        }
    };

    window.closeSettings = function (event) {
        if (!event || event.target === overlay) {
            closeSettingsDrawer();
        }
    };

    function closeSettingsDrawer() {
        overlay.classList.remove('is-open');
        document.body.style.overflow = '';
    }

    // Escape key closes
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay.classList.contains('is-open')) {
            closeSettingsDrawer();
        }
    });

    // ── Staff profile link fix ──
    var link = document.getElementById('settings-profile-link');
    var role = '{{ $sUser->role }}';
    if (link && role === 'staff') {
        link.href = '#';
        link.addEventListener('click', function (e) {
            e.preventDefault();
            showSettingsToast('Staff profiles can be edited by your manager.', 'error');
        });
    }

    // ── Helpers ──
    function clearErrors(prefix) {
        document.querySelectorAll('[id^="' + prefix + '-error"]').forEach(function (el) {
            el.classList.remove('is-visible');
            el.textContent = '';
        });
    }
    function clearSuccess(prefix) {
        document.querySelectorAll('[id^="' + prefix + '-success"]').forEach(function (el) {
            el.classList.remove('is-visible');
            el.textContent = '';
        });
    }

    window.showSettingsToast = function (message, type) {
        var container = document.getElementById('settingsToastContainer');
        var toast = document.createElement('div');
        toast.className = 'settings-toast settings-toast--' + type;
        toast.textContent = message;
        container.appendChild(toast);
        setTimeout(function () { toast.remove(); }, 3000);
    };

    function getToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '{{ csrf_token() }}';
    }

    // ── Update Profile ──
    window.settingsUpdateProfile = function (event) {
        event.preventDefault();
        var btn = document.getElementById('settings-profile-btn');
        btn.disabled = true; btn.textContent = 'Saving…';
        clearErrors('settings'); clearSuccess('settings-profile');

        var name = document.getElementById('settings-name').value.trim();
        var email = document.getElementById('settings-email').value.trim();

        fetch('/settings/profile', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': getToken() },
            body: JSON.stringify({ name: name, email: email })
        })
        .then(function (r) { return r.json().then(function (d) { d.status = r.status; return d; }); })
        .then(function (resp) {
            btn.disabled = false; btn.textContent = 'Save Changes';
            if (resp.status >= 200 && resp.status < 300) {
                document.querySelector('.settings-profile-info .name').textContent = resp.user.name;
                document.querySelector('.settings-profile-info .email').textContent = resp.user.email;
                var el = document.getElementById('settings-profile-success');
                el.textContent = resp.message; el.classList.add('is-visible');
                setTimeout(function () { el.classList.remove('is-visible'); }, 3000);
            } else if (resp.status === 422 && resp.errors) {
                Object.keys(resp.errors).forEach(function (f) {
                    var e = document.getElementById('settings-error-' + f);
                    var i = document.getElementById('settings-' + f);
                    if (e) { e.textContent = resp.errors[f].join(', '); e.classList.add('is-visible'); }
                    if (i) i.classList.add('error');
                });
            } else {
                showSettingsToast(resp.message || 'Something went wrong.', 'error');
            }
        })
        .catch(function () {
            btn.disabled = false; btn.textContent = 'Save Changes';
            showSettingsToast('Network error.', 'error');
        });
        return false;
    };

    // ── Update Password ──
    window.settingsUpdatePassword = function (event) {
        event.preventDefault();
        var newPw = document.getElementById('settings-pw-new').value;
        var confirmPw = document.getElementById('settings-pw-confirm').value;
        if (newPw !== confirmPw) {
            var el = document.getElementById('settings-error-confirm');
            el.textContent = 'Passwords do not match.'; el.classList.add('is-visible');
            return false;
        }
        var btn = document.getElementById('settings-pw-btn');
        btn.disabled = true; btn.textContent = 'Updating…';
        clearErrors('settings-pw'); clearSuccess('settings-pw');

        fetch('/settings/password', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': getToken() },
            body: JSON.stringify({
                current_password: document.getElementById('settings-pw-current').value,
                new_password: newPw,
                new_password_confirmation: confirmPw
            })
        })
        .then(function (r) { return r.json().then(function (d) { d.status = r.status; return d; }); })
        .then(function (resp) {
            btn.disabled = false; btn.textContent = 'Update Password';
            if (resp.status >= 200 && resp.status < 300) {
                document.getElementById('settings-pw-current').value = '';
                document.getElementById('settings-pw-new').value = '';
                document.getElementById('settings-pw-confirm').value = '';
                var el = document.getElementById('settings-pw-success');
                el.textContent = resp.message; el.classList.add('is-visible');
                setTimeout(function () { el.classList.remove('is-visible'); }, 3000);
            } else if (resp.status === 422 && resp.errors) {
                var fieldMap = { current_password: 'current', new_password: 'new' };
                Object.keys(resp.errors).forEach(function (f) {
                    var elId = 'settings-error-' + (fieldMap[f] || f);
                    var el = document.getElementById(elId);
                    if (el) { el.textContent = resp.errors[f].join(', '); el.classList.add('is-visible'); }
                });
            } else {
                showSettingsToast(resp.message || 'Something went wrong.', 'error');
            }
        })
        .catch(function () {
            btn.disabled = false; btn.textContent = 'Update Password';
            showSettingsToast('Network error.', 'error');
        });
        return false;
    };

    // ── Auto-open if URL has ?settings hash ──
    if (window.location.hash === '#settings') {
        setTimeout(function () { window.toggleSettings(); }, 100);
    }
})();
</script>
