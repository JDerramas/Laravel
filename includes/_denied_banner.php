<?php
/**
 * _denied_banner.php — Shared "Access Denied" notice.
 *
 * Shows an animated banner when the user lands on their portal with
 * ?denied=admin or ?denied=faculty (set by auth.php guards).
 * Auto-dismisses, remembers dismissal for the session.
 *
 * Usage (anywhere in body):  include '_denied_banner.php';
 */
$npcDenied = isset($_GET['denied']) ? $_GET['denied'] : '';
if ($npcDenied === 'admin' || $npcDenied === 'faculty'):
    $isFac = ($npcDenied === 'faculty');
?>
<div id="npc-denied-banner" role="alert"
     class="no-print fixed top-20 left-4 right-4 md:left-8 md:right-8 z-[70] mx-auto max-w-3xl
            rounded-2xl border border-error/40 bg-error-container text-on-surface px-5 py-4">
    <div class="flex items-start gap-3">
        <span class="material-symbols-outlined text-error text-[26px] shrink-0 mt-0.5">gpp_maybe</span>
        <div class="flex-1 min-w-0">
            <p class="font-bold text-sm text-error">Access restricted</p>
            <p class="text-xs text-on-surface-variant mt-0.5 leading-relaxed">
                <?php if ($isFac): ?>
                    Faculty portals are exclusive to <strong>teachers and administrators</strong> signed in with their NPC Gmail.
                    Your account doesn't have faculty access, so you've been redirected here.
                <?php else: ?>
                    Admin pages are exclusive to <strong>administrators</strong> only. Your NPC Gmail account doesn't have
                    admin privileges, so you've been redirected to your own portal.
                <?php endif; ?>
                This attempt was recorded in the security log.
            </p>
        </div>
        <button type="button" onclick="(function(b){b.closest('#npc-denied-banner').classList.add('dismissed');try{sessionStorage.setItem('npc-denied-dismissed','1');}catch(e){}})(this)"
                aria-label="Dismiss" class="shrink-0 p-1.5 rounded-full hover:bg-error/10 text-on-surface-variant hover:text-error transition-colors cursor-pointer">
            <span class="material-symbols-outlined text-[18px]">close</span>
        </button>
    </div>
</div>
<script>
    (function () {
        try { if (sessionStorage.getItem('npc-denied-dismissed') === '1') {
            var b = document.getElementById('npc-denied-banner'); if (b) b.remove(); return;
        } } catch (e) {}
        setTimeout(function () {
            var b = document.getElementById('npc-denied-banner');
            if (b) b.classList.add('dismissed');
        }, 9000);
    })();
</script>
<?php endif; ?>
