<?php
/**
 * _calendar.php — Interactive Academic Calendar Widget for NPC ELMS Dashboards
 * 
 * Supports two responsive modes:
 *  - 'compact': Single-column vertical layout for sidebars / narrow columns (Student Dashboard)
 *  - 'full': Generous 2-column grid layout for full-width dashboards (Admin & Faculty Dashboards)
 * 
 * Admin capabilities:
 *  - Interactive Date & Milestone Editor modal to add, edit, or delete calendar dates
 *  - Automatic persistence to backend/academic_calendar.json with live synchronization
 */

$calPortal = $CALENDAR_PORTAL ?? $NPC_PORTAL ?? 'student';
$calMode   = $CALENDAR_MODE ?? ($calPortal === 'student' ? 'compact' : 'full');
$calId     = 'npc-cal-' . $calPortal . '-' . mt_rand(100, 999);
$todayFormatted = date('l, F j, Y');

$calendarFile = __DIR__ . '/../backend/academic_calendar.json';
$calendarData = [];
if (file_exists($calendarFile)) {
    $raw = @file_get_contents($calendarFile);
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $calendarData = $decoded;
    }
}
$userRole = $_SESSION['base_role'] ?? $_SESSION['role'] ?? 'student';
$isAdminUser = in_array($userRole, ['admin', 'registrar']);
$csrfToken = $_SESSION['csrf_token'] ?? '';
?>
<!-- ══════════ NPC Interactive Academic Calendar (<?= htmlspecialchars($calMode) ?> mode) ══════════ -->
<div id="<?= $calId ?>" class="npc-academic-calendar bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-sm overflow-hidden <?= ($calMode === 'compact') ? 'p-4 sm:p-5' : 'p-6' ?>" data-portal="<?= htmlspecialchars($calPortal) ?>" data-mode="<?= htmlspecialchars($calMode) ?>" data-is-admin="<?= $isAdminUser ? 'true' : 'false' ?>">
    <?php if ($calMode === 'compact'): ?>
        <!-- ── COMPACT MODE (Designed specifically for sidebar / narrow columns) ── -->
        <!-- Header: Title & Term Badge -->
        <div class="flex items-center justify-between gap-2 pb-3 mb-3 border-b border-outline-variant/60">
            <div class="flex items-center gap-2 min-w-0">
                <div class="w-8 h-8 rounded-xl bg-primary-container text-on-primary flex items-center justify-center npc-navy-card shrink-0">
                    <span class="material-symbols-outlined text-[18px]">calendar_month</span>
                </div>
                <div class="min-w-0">
                    <h3 class="text-sm font-bold text-primary leading-tight truncate">Academic Calendar</h3>
                    <p class="text-[10px] text-on-surface-variant font-mono truncate">1st Semester · AY 2026–2027</p>
                </div>
            </div>
            <div class="flex items-center gap-1.5 shrink-0">
                <?php if ($isAdminUser): ?>
                <button type="button" class="cal-manage-btn px-2 py-1 rounded-lg border border-primary/30 bg-primary/10 hover:bg-primary/20 text-primary text-[11px] font-bold font-mono transition-colors inline-flex items-center gap-0.5 cursor-pointer shadow-xs" title="Edit Calendar Dates & Milestones">
                    <span class="material-symbols-outlined text-[13px]">edit_calendar</span> Edit
                </button>
                <?php endif; ?>
                <button type="button" class="cal-today-btn px-2 py-1 rounded-lg border border-outline-variant bg-surface-container hover:bg-surface-container-high text-primary text-[11px] font-bold font-mono transition-colors cursor-pointer shadow-xs">
                    Today
                </button>
            </div>
        </div>

        <!-- Dedicated Month Navigation Bar (Never wraps or overflows) -->
        <div class="flex items-center justify-between bg-surface-container-low/80 rounded-xl px-2.5 py-1.5 border border-outline-variant/50 mb-3">
            <button type="button" class="cal-prev-btn p-1 rounded-lg hover:bg-surface-container text-on-surface transition-colors cursor-pointer" title="Previous Month">
                <span class="material-symbols-outlined text-[18px]">chevron_left</span>
            </button>
            <span class="cal-month-title font-bold text-xs text-primary font-mono select-none tracking-wide">
                <?= date('F Y') ?>
            </span>
            <button type="button" class="cal-next-btn p-1 rounded-lg hover:bg-surface-container text-on-surface transition-colors cursor-pointer" title="Next Month">
                <span class="material-symbols-outlined text-[18px]">chevron_right</span>
            </button>
        </div>

        <!-- Full-Width Monthly Grid -->
        <div class="bg-surface-container-low/40 rounded-xl p-2.5 border border-outline-variant/40">
            <!-- Day of Week Header -->
            <div class="grid grid-cols-7 gap-1 text-center font-mono text-[10px] font-bold text-on-surface-variant/80 uppercase pb-1.5 mb-1.5 border-b border-outline-variant/30">
                <span class="text-error/80">Su</span>
                <span>Mo</span>
                <span>Tu</span>
                <span>We</span>
                <span>Th</span>
                <span>Fr</span>
                <span class="text-primary/80">Sa</span>
            </div>

            <!-- Dynamic Days Container -->
            <div class="cal-days-grid grid grid-cols-7 gap-1 text-center text-xs">
                <!-- Injected via JavaScript -->
            </div>
        </div>

        <!-- Selected Date & Events Strip (Stacked below grid for 100% full width) -->
        <div class="mt-3.5 pt-3 border-t border-outline-variant/50">
            <div class="flex items-center justify-between gap-2 mb-1.5">
                <span class="text-[10px] font-mono font-bold uppercase tracking-wider text-secondary flex items-center gap-1">
                    <span class="material-symbols-outlined text-[13px]">event</span>
                    Selected Date
                </span>
                <span class="cal-selected-badge text-[10px] font-mono px-2 py-0.5 rounded-full bg-primary-container text-on-primary font-bold">
                    TODAY
                </span>
            </div>
            <h4 class="cal-selected-title text-xs font-bold text-primary truncate mb-2"><?= $todayFormatted ?></h4>

            <?php if ($isAdminUser): ?>
            <button type="button" class="cal-edit-selected-btn mb-2.5 w-full py-1.5 px-2.5 rounded-lg border border-dashed border-primary/40 hover:bg-primary/10 text-primary text-[11px] font-bold transition-all flex items-center justify-center gap-1 cursor-pointer">
                <span class="material-symbols-outlined text-[14px]">edit_note</span> Edit / Add Milestone for Date
            </button>
            <?php endif; ?>

            <!-- Events / Notes List -->
            <div class="cal-events-container space-y-2 max-h-44 overflow-y-auto custom-scroll pr-1">
                <!-- Populated dynamically -->
            </div>

            <!-- Quick Action Links -->
            <div class="mt-3 pt-2.5 border-t border-outline-variant/40 flex items-center justify-between gap-2 text-xs">
                <?php if ($calPortal === 'student'): ?>
                    <a href="/student/schedule.php" class="px-2.5 py-1.5 rounded-lg bg-surface-container hover:bg-surface-container-high text-primary font-bold text-[11px] transition-colors inline-flex items-center gap-1 truncate shadow-xs">
                        <span class="material-symbols-outlined text-[14px]">calendar_month</span> Full Schedule →
                    </a>
                    <a href="/student/qrcode.php" class="px-2.5 py-1.5 rounded-lg border border-outline-variant hover:bg-surface-container text-on-surface-variant font-semibold text-[11px] transition-colors inline-flex items-center gap-1 shrink-0">
                        <span class="material-symbols-outlined text-[14px]">qr_code_scanner</span> Scan QR
                    </a>
                <?php elseif ($calPortal === 'faculty'): ?>
                    <a href="/teacher/classes.php" class="px-2.5 py-1.5 rounded-lg bg-surface-container hover:bg-surface-container-high text-primary font-bold text-[11px] transition-colors inline-flex items-center gap-1 truncate">
                        <span class="material-symbols-outlined text-[14px]">school</span> My Classes →
                    </a>
                    <a href="/teacher/attendance.php" class="px-2.5 py-1.5 rounded-lg border border-outline-variant hover:bg-surface-container text-secondary font-semibold text-[11px] transition-colors inline-flex items-center gap-1 shrink-0">
                        <span class="material-symbols-outlined text-[14px]">qr_code_scanner</span> Attendance
                    </a>
                <?php else: ?>
                    <a href="/admin/academic/schedules.php" class="px-2.5 py-1.5 rounded-lg bg-surface-container hover:bg-surface-container-high text-primary font-bold text-[11px] transition-colors inline-flex items-center gap-1 truncate">
                        <span class="material-symbols-outlined text-[14px]">edit_calendar</span> Manage →
                    </a>
                    <a href="/admin/system/settings.php" class="px-2.5 py-1.5 rounded-lg border border-outline-variant hover:bg-surface-container text-on-surface-variant font-semibold text-[11px] transition-colors inline-flex items-center gap-1 shrink-0">
                        <span class="material-symbols-outlined text-[14px]">settings</span> Settings
                    </a>
                <?php endif; ?>
            </div>
        </div>

    <?php else: ?>
        <!-- ── FULL MODE (Generous 2-column layout for full-width dashboards) ── -->
        <!-- Top Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 mb-4 border-b border-outline-variant/60">
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-xl bg-primary-container text-on-primary flex items-center justify-center npc-navy-card shrink-0">
                    <span class="material-symbols-outlined text-[22px]">calendar_month</span>
                </div>
                <div>
                    <h3 class="text-base font-bold text-primary leading-tight">Academic Calendar & Master Milestones</h3>
                    <p class="text-xs text-on-surface-variant font-mono">1st Semester · Academic Year 2026–2027</p>
                </div>
            </div>

            <!-- Month Controls & Admin Actions -->
            <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
                <button type="button" class="cal-prev-btn p-1.5 rounded-lg border border-outline-variant bg-surface hover:bg-surface-container text-on-surface transition-colors cursor-pointer" title="Previous Month">
                    <span class="material-symbols-outlined text-[18px]">chevron_left</span>
                </button>
                <span class="cal-month-title font-bold text-sm text-primary px-3 min-w-[140px] text-center select-none font-mono">
                    <?= date('F Y') ?>
                </span>
                <button type="button" class="cal-next-btn p-1.5 rounded-lg border border-outline-variant bg-surface hover:bg-surface-container text-on-surface transition-colors cursor-pointer" title="Next Month">
                    <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                </button>
                <button type="button" class="cal-today-btn px-3 py-1 rounded-lg border border-outline-variant bg-surface-container-low hover:bg-surface-container text-primary text-xs font-semibold transition-colors cursor-pointer font-mono" title="Jump to Today">
                    Today
                </button>
                <?php if ($isAdminUser): ?>
                <button type="button" class="cal-manage-btn ml-1 px-3 py-1 rounded-lg border border-primary/40 bg-primary text-on-primary hover:opacity-90 text-xs font-bold transition-all inline-flex items-center gap-1.5 cursor-pointer shadow-xs">
                    <span class="material-symbols-outlined text-[15px]">edit_calendar</span> Edit Dates
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- 2-Column Grid Layout -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-start">
            <!-- Left: Calendar Grid (7 cols) -->
            <div class="md:col-span-7 bg-surface-container-low/40 rounded-xl p-4 border border-outline-variant/50">
                <div class="grid grid-cols-7 gap-1 text-center font-mono text-[11px] font-bold text-on-surface-variant/80 uppercase pb-2 mb-2 border-b border-outline-variant/30">
                    <span class="text-error/80">Sun</span>
                    <span>Mon</span>
                    <span>Tue</span>
                    <span>Wed</span>
                    <span>Thu</span>
                    <span>Fri</span>
                    <span class="text-primary/80">Sat</span>
                </div>
                <div class="cal-days-grid grid grid-cols-7 gap-1.5 text-center text-xs">
                    <!-- Injected via JavaScript -->
                </div>
            </div>

            <!-- Right: Selected Date & Milestones Agenda (5 cols) -->
            <div class="md:col-span-5 flex flex-col gap-3">
                <div class="bg-surface-container-low/60 rounded-xl p-4 border border-outline-variant/60">
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <span class="text-[10px] font-mono font-bold uppercase tracking-wider text-secondary flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">event</span>
                            Selected Date
                        </span>
                        <span class="cal-selected-badge text-[10px] font-mono px-2 py-0.5 rounded-full bg-primary-container text-on-primary font-bold">
                            TODAY
                        </span>
                    </div>
                    <h4 class="cal-selected-title text-sm font-bold text-primary truncate"><?= $todayFormatted ?></h4>
                    
                    <?php if ($isAdminUser): ?>
                    <button type="button" class="cal-edit-selected-btn mt-2.5 w-full py-1.5 px-3 rounded-xl border border-dashed border-primary/40 hover:bg-primary/10 text-primary text-xs font-bold transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                        <span class="material-symbols-outlined text-[15px]">edit_note</span> Edit / Add Milestone for this Date
                    </button>
                    <?php endif; ?>
                </div>

                <!-- Events / Milestones -->
                <div class="cal-events-container space-y-2.5 max-h-64 overflow-y-auto custom-scroll pr-1">
                    <!-- Populated dynamically -->
                </div>

                <!-- Footer -->
                <div class="pt-3 border-t border-outline-variant/40 flex items-center justify-between text-xs">
                    <?php if ($calPortal === 'faculty'): ?>
                        <a href="/teacher/classes.php" class="text-primary font-semibold hover:underline inline-flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">school</span> My Class Schedules →
                        </a>
                        <a href="/teacher/attendance.php" class="text-secondary font-semibold hover:underline inline-flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">qr_code_scanner</span> Live Attendance
                        </a>
                    <?php else: ?>
                        <a href="/admin/academic/schedules.php" class="text-primary font-semibold hover:underline inline-flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">edit_calendar</span> Master Schedules →
                        </a>
                        <a href="/admin/system/settings.php" class="text-on-surface-variant hover:text-primary font-medium inline-flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">settings</span> Academic Settings
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if ($isAdminUser): ?>
<!-- ══════════ Admin Calendar Date / Milestone Editor Modal ══════════ -->
<div id="npc-cal-modal-<?= $calId ?>" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm animate-fade-in" role="dialog" aria-modal="true">
    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-5 sm:p-6 max-w-md w-full shadow-2xl relative text-left">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-outline-variant/60">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-xl bg-primary text-on-primary flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">edit_calendar</span>
                </div>
                <div>
                    <h3 class="text-sm sm:text-base font-bold text-primary">Manage Academic Milestone</h3>
                    <p class="text-[11px] text-on-surface-variant font-mono">Add or update official campus dates & events</p>
                </div>
            </div>
            <button type="button" class="cal-modal-close p-1.5 rounded-lg hover:bg-surface-container text-on-surface-variant transition-colors cursor-pointer" title="Close">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>

        <form class="cal-event-form space-y-3.5" onsubmit="return false;">
            <input type="hidden" name="id" class="cal-input-id" value="">
            
            <div>
                <label class="block text-xs font-semibold text-primary mb-1">Target Date <span class="text-error">*</span></label>
                <input type="date" name="date" class="cal-input-date w-full px-3 py-2 text-xs font-mono rounded-xl border border-outline-variant bg-surface text-on-surface focus:ring-2 focus:ring-primary focus:outline-none" required>
            </div>

            <div>
                <label class="block text-xs font-semibold text-primary mb-1">Milestone Title <span class="text-error">*</span></label>
                <input type="text" name="title" class="cal-input-title w-full px-3 py-2 text-xs rounded-xl border border-outline-variant bg-surface text-on-surface focus:ring-2 focus:ring-primary focus:outline-none" placeholder="e.g. Midterm Examination Week" required>
            </div>

            <div>
                <label class="block text-xs font-semibold text-primary mb-1">Category / Type <span class="text-error">*</span></label>
                <select name="type" class="cal-input-type w-full px-3 py-2 text-xs rounded-xl border border-outline-variant bg-surface text-on-surface focus:ring-2 focus:ring-primary focus:outline-none">
                    <option value="academic">🟢 Academic Day / Semester Milestone</option>
                    <option value="exam">🟡 Examination Period (Prelim, Midterm, Finals)</option>
                    <option value="holiday">🔴 Campus Holiday / No Classes</option>
                    <option value="deadline">🟣 Grade Deadline / Registrar Submission</option>
                    <option value="event">🔵 Institutional Event / Program</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-primary mb-1">Description & Notes</label>
                <textarea name="desc" rows="2" class="cal-input-desc w-full px-3 py-2 text-xs rounded-xl border border-outline-variant bg-surface text-on-surface focus:ring-2 focus:ring-primary focus:outline-none" placeholder="Instructions, scope, or affected programs..."></textarea>
            </div>

            <div class="pt-3 border-t border-outline-variant/60 flex items-center justify-between gap-2">
                <button type="button" class="cal-modal-delete hidden px-3 py-2 rounded-xl border border-error/40 text-error hover:bg-error/10 text-xs font-bold transition-colors cursor-pointer inline-flex items-center gap-1">
                    <span class="material-symbols-outlined text-[16px]">delete</span> Delete
                </button>
                <div class="flex items-center gap-2 ml-auto">
                    <button type="button" class="cal-modal-cancel px-3 py-2 rounded-xl border border-outline-variant text-on-surface hover:bg-surface-container text-xs font-semibold transition-colors cursor-pointer">
                        Cancel
                    </button>
                    <button type="button" class="cal-modal-save px-4 py-2 rounded-xl bg-primary text-on-primary hover:opacity-90 text-xs font-bold transition-all shadow-sm cursor-pointer inline-flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px]">save</span> Save Milestone
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Calendar Script Engine -->
<script>
(function () {
    var root = document.getElementById('<?= $calId ?>');
    if (!root) return;

    var portal = root.getAttribute('data-portal') || 'student';
    var mode = root.getAttribute('data-mode') || 'compact';
    var isAdmin = root.getAttribute('data-is-admin') === 'true';

    var prevBtn = root.querySelector('.cal-prev-btn');
    var nextBtn = root.querySelector('.cal-next-btn');
    var todayBtn = root.querySelector('.cal-today-btn');
    var manageBtn = root.querySelector('.cal-manage-btn');
    var editSelectedBtn = root.querySelector('.cal-edit-selected-btn');

    var monthTitle = root.querySelector('.cal-month-title');
    var daysGrid = root.querySelector('.cal-days-grid');
    var selectedTitle = root.querySelector('.cal-selected-title');
    var selectedBadge = root.querySelector('.cal-selected-badge');
    var eventsContainer = root.querySelector('.cal-events-container');

    // Server-hydrated NPC Academic Milestones (2026-2027)
    var ACADEMIC_EVENTS = <?= json_encode($calendarData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?> || {};

    var viewDate = new Date();
    var selectedDate = new Date();

    function formatDateKey(d) {
        var y = d.getFullYear();
        var m = String(d.getMonth() + 1).padStart(2, '0');
        var day = String(d.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + day;
    }

    function isSameDay(d1, d2) {
        return d1.getFullYear() === d2.getFullYear() &&
               d1.getMonth() === d2.getMonth() &&
               d1.getDate() === d2.getDate();
    }

    function renderEvents(d) {
        var key = formatDateKey(d);
        var isToday = isSameDay(d, new Date());

        selectedTitle.textContent = d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
        selectedBadge.textContent = isToday ? 'TODAY' : d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }).toUpperCase();
        selectedBadge.className = isToday 
            ? 'cal-selected-badge text-[10px] font-mono px-2 py-0.5 rounded-full bg-primary-container text-on-primary font-bold shadow-xs'
            : 'cal-selected-badge text-[10px] font-mono px-2 py-0.5 rounded-full bg-surface-container text-on-surface-variant font-medium';

        var dayEvents = ACADEMIC_EVENTS[key] || [];

        if (dayEvents.length === 0) {
            var upcoming = [];
            for (var k in ACADEMIC_EVENTS) {
                if (k >= key) {
                    ACADEMIC_EVENTS[k].forEach(function(ev) {
                        upcoming.push({ date: k, ev: ev });
                    });
                }
            }

            var html = '<div class="p-2.5 rounded-xl bg-surface-container/60 border border-outline-variant/40 text-center text-xs text-on-surface-variant">' +
                       '<p class="font-medium text-[11px]">No special event on this date.</p>' +
                       '<p class="text-[10px] opacity-70 mt-0.5">Regular class lectures & consults apply.</p>' +
                       '</div>';

            if (upcoming.length > 0) {
                html += '<p class="text-[10px] font-mono font-bold uppercase tracking-wider text-on-surface-variant/70 mt-2 mb-1">Upcoming Milestones:</p>';
                upcoming.slice(0, 2).forEach(function(item) {
                    var itemDate = new Date(item.date + 'T00:00:00');
                    var dateBadge = itemDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                    html += '<div class="p-2 rounded-xl border border-outline-variant/50 bg-surface-container-lowest flex items-start gap-2 hover:bg-surface-container-low transition-colors cursor-pointer" onclick="window[\'__selectCal_' + '<?= $calId ?>' + '\'](\'' + item.date + '\')">' +
                            '<span class="font-mono text-[9px] font-bold px-1.5 py-0.5 rounded bg-surface-container text-primary shrink-0 mt-0.5">' + dateBadge + '</span>' +
                            '<div class="min-w-0 flex-1">' +
                            '<p class="text-xs font-semibold text-primary truncate leading-tight">' + item.ev.title + '</p>' +
                            '<p class="text-[10px] text-on-surface-variant truncate">' + (item.ev.desc || '') + '</p>' +
                            '</div>' +
                            '</div>';
                });
            }
            eventsContainer.innerHTML = html;
        } else {
            var html = '';
            dayEvents.forEach(function(ev) {
                var borderCol = ev.type === 'exam' ? 'border-amber-400/60 bg-amber-500/10' :
                               (ev.type === 'holiday' ? 'border-error/50 bg-error/10' :
                               (ev.type === 'deadline' ? 'border-purple-400/60 bg-purple-500/10' : 'border-primary/40 bg-primary/10'));
                var icon = ev.type === 'exam' ? 'edit_calendar' :
                          (ev.type === 'holiday' ? 'beach_access' :
                          (ev.type === 'deadline' ? 'timer' : 'school'));
                var textCol = ev.type === 'exam' ? 'text-amber-800 dark:text-amber-300' :
                             (ev.type === 'holiday' ? 'text-error' :
                             (ev.type === 'deadline' ? 'text-purple-800 dark:text-purple-300' : 'text-primary'));

                var editBtnHtml = '';
                if (isAdmin) {
                    editBtnHtml = '<button type="button" class="p-1 rounded hover:bg-black/10 dark:hover:bg-white/10 text-on-surface-variant transition-colors ml-auto cursor-pointer" title="Edit this event" onclick="window[\'__editEvent_' + '<?= $calId ?>' + '\'](\'' + key + '\', \'' + (ev.id || '') + '\')">' +
                                  '<span class="material-symbols-outlined text-[14px]">edit</span>' +
                                  '</button>';
                }

                html += '<div class="p-2.5 rounded-xl border ' + borderCol + ' flex items-start gap-2 shadow-xs">' +
                        '<span class="material-symbols-outlined text-[16px] ' + textCol + ' shrink-0 mt-0.5">' + icon + '</span>' +
                        '<div class="min-w-0 flex-1">' +
                        '<div class="flex items-center justify-between gap-1">' +
                        '<p class="text-xs font-bold ' + textCol + ' truncate leading-tight">' + ev.title + '</p>' +
                        editBtnHtml +
                        '</div>' +
                        '<p class="text-[11px] text-on-surface-variant mt-0.5 leading-tight">' + (ev.desc || '') + '</p>' +
                        '</div>' +
                        '</div>';
            });
            eventsContainer.innerHTML = html;
        }
    }

    function renderMonth() {
        var year = viewDate.getFullYear();
        var month = viewDate.getMonth();

        monthTitle.textContent = viewDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

        var firstDayIndex = new Date(year, month, 1).getDay();
        var daysInMonth = new Date(year, month + 1, 0).getDate();
        var daysInPrevMonth = new Date(year, month, 0).getDate();

        var today = new Date();
        var html = '';

        var cellHeight = (mode === 'compact') ? 'h-8' : 'h-9 sm:h-10';

        // Previous month padding cells
        for (var i = firstDayIndex - 1; i >= 0; i--) {
            var prevNum = daysInPrevMonth - i;
            html += '<div class="' + cellHeight + ' flex items-center justify-center text-on-surface-variant/30 font-mono text-[11px] select-none rounded-lg">' + prevNum + '</div>';
        }

        // Current month cells
        for (var day = 1; day <= daysInMonth; day++) {
            var cellDate = new Date(year, month, day);
            var dateKey = formatDateKey(cellDate);
            var isToday = isSameDay(cellDate, today);
            var isSelected = isSameDay(cellDate, selectedDate);
            var hasEvent = ACADEMIC_EVENTS[dateKey] && ACADEMIC_EVENTS[dateKey].length > 0;

            var cellClasses = 'cal-day-cell ' + cellHeight + ' relative flex flex-col items-center justify-center font-mono text-xs font-medium rounded-lg cursor-pointer transition-all select-none ';

            if (isSelected) {
                cellClasses += 'bg-primary text-on-primary font-bold shadow-sm ';
            } else if (isToday) {
                cellClasses += 'bg-amber-400 text-slate-950 font-bold shadow-sm ring-2 ring-amber-400/50 ';
            } else {
                cellClasses += 'text-on-surface hover:bg-surface-container-high ';
            }

            var dotHtml = '';
            if (hasEvent) {
                var ev = ACADEMIC_EVENTS[dateKey][0];
                var dotColor = ev.type === 'exam' ? 'bg-amber-400' :
                              (ev.type === 'holiday' ? 'bg-error' :
                              (ev.type === 'deadline' ? 'bg-purple-400' : 'bg-status-success'));
                if (isSelected) dotColor = 'bg-white';
                dotHtml = '<span class="absolute bottom-0.5 w-1 h-1 rounded-full ' + dotColor + '"></span>';
            }

            html += '<div class="' + cellClasses + '" data-date="' + dateKey + '" title="' + (hasEvent ? ACADEMIC_EVENTS[dateKey][0].title : '') + '">' +
                    '<span>' + day + '</span>' +
                    dotHtml +
                    '</div>';
        }

        daysGrid.innerHTML = html;

        daysGrid.querySelectorAll('.cal-day-cell').forEach(function (cell) {
            cell.addEventListener('click', function () {
                var key = this.getAttribute('data-date');
                var parts = key.split('-');
                selectedDate = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
                renderMonth();
                renderEvents(selectedDate);
            });
        });
    }

    window['__selectCal_' + '<?= $calId ?>'] = function (key) {
        var parts = key.split('-');
        selectedDate = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
        viewDate = new Date(selectedDate);
        renderMonth();
        renderEvents(selectedDate);
    };

    if (prevBtn) {
        prevBtn.addEventListener('click', function () {
            viewDate.setMonth(viewDate.getMonth() - 1);
            renderMonth();
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            viewDate.setMonth(viewDate.getMonth() + 1);
            renderMonth();
        });
    }

    if (todayBtn) {
        todayBtn.addEventListener('click', function () {
            viewDate = new Date();
            selectedDate = new Date();
            renderMonth();
            renderEvents(selectedDate);
        });
    }

    // ─── ADMIN MODAL INTERACTIONS ─────────────────────────────────────────────
    <?php if ($isAdminUser): ?>
    var modal = document.getElementById('npc-cal-modal-<?= $calId ?>');
    var modalClose = modal ? modal.querySelector('.cal-modal-close') : null;
    var modalCancel = modal ? modal.querySelector('.cal-modal-cancel') : null;
    var modalSave = modal ? modal.querySelector('.cal-modal-save') : null;
    var modalDelete = modal ? modal.querySelector('.cal-modal-delete') : null;

    var inputId = modal ? modal.querySelector('.cal-input-id') : null;
    var inputDate = modal ? modal.querySelector('.cal-input-date') : null;
    var inputTitle = modal ? modal.querySelector('.cal-input-title') : null;
    var inputType = modal ? modal.querySelector('.cal-input-type') : null;
    var inputDesc = modal ? modal.querySelector('.cal-input-desc') : null;

    function openModal(dateKey, evObj) {
        if (!modal) return;
        dateKey = dateKey || formatDateKey(selectedDate);

        inputDate.value = dateKey;
        if (evObj) {
            inputId.value = evObj.id || '';
            inputTitle.value = evObj.title || '';
            inputType.value = evObj.type || 'academic';
            inputDesc.value = evObj.desc || '';
            modalDelete.classList.remove('hidden');
        } else {
            inputId.value = '';
            inputTitle.value = '';
            inputType.value = 'academic';
            inputDesc.value = '';
            modalDelete.classList.add('hidden');
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal() {
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    if (modalClose) modalClose.addEventListener('click', closeModal);
    if (modalCancel) modalCancel.addEventListener('click', closeModal);

    if (manageBtn) {
        manageBtn.addEventListener('click', function () {
            var key = formatDateKey(selectedDate);
            var existing = (ACADEMIC_EVENTS[key] && ACADEMIC_EVENTS[key][0]) ? ACADEMIC_EVENTS[key][0] : null;
            openModal(key, existing);
        });
    }

    if (editSelectedBtn) {
        editSelectedBtn.addEventListener('click', function () {
            var key = formatDateKey(selectedDate);
            var existing = (ACADEMIC_EVENTS[key] && ACADEMIC_EVENTS[key][0]) ? ACADEMIC_EVENTS[key][0] : null;
            openModal(key, existing);
        });
    }

    window['__editEvent_' + '<?= $calId ?>'] = function (dateKey, eventId) {
        var existing = null;
        if (ACADEMIC_EVENTS[dateKey]) {
            existing = ACADEMIC_EVENTS[dateKey].find(function (e) { return e.id === eventId; }) || ACADEMIC_EVENTS[dateKey][0];
        }
        openModal(dateKey, existing);
    };

    if (modalSave) {
        modalSave.addEventListener('click', async function () {
            var dateVal = inputDate.value.trim();
            var titleVal = inputTitle.value.trim();
            var typeVal = inputType.value;
            var descVal = inputDesc.value.trim();
            var idVal = inputId.value;

            if (!dateVal || !titleVal) {
                alert('Please provide both date and milestone title.');
                return;
            }

            modalSave.disabled = true;
            modalSave.innerHTML = '<span class="material-symbols-outlined text-[16px] animate-spin">progress_activity</span> Saving...';

            try {
                var csrfToken = '<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>';
                var res = await fetch('/api/calendar.php?action=save_event', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({
                        date: dateVal,
                        title: titleVal,
                        type: typeVal,
                        desc: descVal,
                        id: idVal,
                        csrf_token: csrfToken
                    })
                });
                var data = await res.json();
                if (!data.success) {
                    throw new Error(data.error || 'Failed to save milestone');
                }

                // Update local dataset with server's updated events
                if (data.events) {
                    ACADEMIC_EVENTS = data.events;
                }

                // Jump view to the modified date
                var parts = dateVal.split('-');
                selectedDate = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
                viewDate = new Date(selectedDate);

                renderMonth();
                renderEvents(selectedDate);
                closeModal();

                if (window.notify) {
                    window.notify(data.message || 'Milestone saved successfully!', 'success');
                } else {
                    alert(data.message || 'Milestone saved successfully!');
                }
            } catch (err) {
                alert('Error saving milestone: ' + err.message);
            } finally {
                modalSave.disabled = false;
                modalSave.innerHTML = '<span class="material-symbols-outlined text-[16px]">save</span> Save Milestone';
            }
        });
    }

    if (modalDelete) {
        modalDelete.addEventListener('click', async function () {
            var dateVal = inputDate.value.trim();
            var idVal = inputId.value;

            if (!await npcConfirm({
                title: 'Delete Milestone',
                message: 'Are you sure you want to delete this academic milestone?',
                type: 'danger',
                confirmText: 'Yes, Delete'
            })) {
                return;
            }

            modalDelete.disabled = true;
            modalDelete.innerHTML = '<span class="material-symbols-outlined text-[16px] animate-spin">progress_activity</span>';

            try {
                var csrfToken = '<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>';
                var res = await fetch('/api/calendar.php?action=delete_event', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({
                        date: dateVal,
                        id: idVal,
                        csrf_token: csrfToken
                    })
                });
                var data = await res.json();
                if (!data.success) {
                    throw new Error(data.error || 'Failed to delete milestone');
                }

                if (data.events) {
                    ACADEMIC_EVENTS = data.events;
                }

                renderMonth();
                renderEvents(selectedDate);
                closeModal();

                if (window.notify) {
                    window.notify('Academic milestone deleted.', 'info');
                } else {
                    alert('Academic milestone deleted.');
                }
            } catch (err) {
                alert('Error deleting milestone: ' + err.message);
            } finally {
                modalDelete.disabled = false;
                modalDelete.innerHTML = '<span class="material-symbols-outlined text-[16px]">delete</span> Delete';
            }
        });
    }
    <?php endif; ?>

    renderMonth();
    renderEvents(selectedDate);
})();
</script>
