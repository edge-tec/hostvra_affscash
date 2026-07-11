</main>
</div>
</div>
<?php $copyright = Config::get('config','app.footer_copyright'); if ($copyright): ?>
<footer style="text-align:center;padding:12px 20px;font-size:12px;color:#94A3B8;border-top:1px solid #E2E8F0;background:#fff"><?= Helpers::e($copyright) ?></footer>
<?php endif; ?>
<script src="/assets/js/app.min.js"></script>
<script>
// ── Automatic conversion postback re-fire ────────────────────────────────
// Silently fires any orphaned conversions (postback_sent=0, no log entries)
// from the last 24 hours as soon as an admin loads any admin page.
// No confirmation dialog, no page reload — runs entirely in the background.
(function() {
    var token = (document.querySelector('meta[name="csrf-token"]') || {}).content;
    if (!token) return;
    var fired = false;

    function autoFirePending() {
        if (fired) return;
        fired = true;
        fetch('/admin/conversions', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: '_token=' + encodeURIComponent(token) + '&action=auto_fire_pending',
            credentials: 'same-origin'
        })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.fired > 0) {
                console.log('[AutoFire] Automatically fired ' + d.fired + ' pending conversion postback(s).');
            }
        })
        .catch(function() {})
        .finally(function() { fired = false; });
    }

    // Run once immediately on page load
    autoFirePending();

    // Then run every 2 minutes while admin panel is open
    setInterval(autoFirePending, 120000);
})();

// ── Background Fraud Score Report scoring ─────────────────────────────────
// Drains the fraud-score queue from EVERY admin page, not just the Fraud
// Score Report itself. So even if the admin never opens that report — and
// even if the system cron isn't wired up — every conversion gets scored as
// long as any admin is browsing the panel. When the admin finally opens the
// report, data is already current and no manual "Re-check" click is needed.
//
//   - Fires once on page load (after the autoFirePending tick).
//   - Repeats on a long interval (60s) while the queue has a backlog.
//   - Stretches the cadence (5 min) once everything is fresh — heartbeat
//     mode keeps the loop alive without burning API quota.
//   - Pauses while the tab is hidden, resumes on refocus.
//   - Fire-and-forget: failures are logged to the console and ignored so
//     they never block other admin work.
//   - Skips itself on the Fraud Score Report page, which already runs its
//     own faster on-page streamer.
(function(){
    if (location.pathname.indexOf('/admin/fraud-score-report') === 0) return;

    var FAST_MS     = 60000;   // 60s while backlog exists
    var SLOW_MS     = 300000;  // 5 min heartbeat once drained
    var BATCH       = 5;
    var ENDPOINT    = '/admin/fraud-score-report';
    var inflight    = false;
    var timer       = null;

    function tick() {
        if (inflight) return;
        if (document.hidden) return;
        inflight = true;
        fetch(ENDPOINT, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=tick&batch=' + BATCH,
            credentials: 'same-origin'
        })
        .then(function(r){ return r.ok ? r.json() : null; })
        .then(function(d){
            inflight = false;
            if (!d) { schedule(SLOW_MS); return; }
            if (typeof d.processed === 'number' && d.processed > 0) {
                console.log('[FraudScore] Background scored ' + d.processed + ' conversion(s), ' + (d.remaining || 0) + ' remaining.');
            }
            schedule((d.remaining || 0) > 0 ? FAST_MS : SLOW_MS);
        })
        .catch(function(){
            inflight = false;
            schedule(SLOW_MS);
        });
    }
    function schedule(ms){
        if (timer) clearTimeout(timer);
        timer = setTimeout(tick, ms);
    }
    document.addEventListener('visibilitychange', function(){
        if (!document.hidden) tick();
    });

    // First fire on a short delay so it doesn't compete with the page's
    // critical-path requests, then take over the loop.
    setTimeout(tick, 3000);
})();
</script>
<?php $egt = Config::get('config', 'app.enable_gtranslate'); if ($egt === null || $egt == 1): ?>
<!-- GTranslate: https://gtranslate.io/ -->
<style>.gtranslate_wrapper { zoom: 0.75; }</style>
<div class="gtranslate_wrapper"></div>
<script>window.gtranslateSettings = {"default_language":"en","detect_browser_language":true,"languages":["en","fr","de","it","es","pt","nl","hi","ur","bn","ru","pl"],"wrapper_selector":".gtranslate_wrapper","float_switcher_open_direction":"top","float_position":"bottom-left"}</script>
<script src="https://cdn.gtranslate.net/widgets/latest/float.js" defer></script>
<?php endif; ?>
</body>
</html>
