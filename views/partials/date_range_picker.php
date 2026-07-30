<?php
/**
 * Shared Date Range Picker Partial
 * Usage: include this file anywhere you have from/to date inputs.
 * Requires: <input type="date" id="input-from" name="from"> and <input type="date" id="input-to" name="to">
 *           Pass $drpFromId and $drpToId to override input IDs (default: 'input-from', 'input-to')
 *           Pass $drpFormId to auto-submit a form on preset click (optional)
 */
$_drpFrom   = $drpFromId   ?? 'input-from';
$_drpTo     = $drpToId     ?? 'input-to';
$_drpFormId = $drpFormId   ?? null;
?>
<style>
.drp-presets {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    align-items: center;
    margin-bottom: 12px;
}
.drp-btn {
    padding: 5px 13px;
    font-size: 11.5px;
    font-weight: 700;
    border: 1px solid rgba(226, 232, 240, 0.8);
    border-radius: 20px;
    background: #ffffff;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    white-space: nowrap;
    line-height: 1.3;
    box-shadow: 0 2px 4px rgba(0,0,0,0.03);
}
.drp-btn:hover {
    background: rgba(99, 102, 241, 0.1);
    color: #4f46e5;
    border-color: rgba(99, 102, 241, 0.3);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(99, 102, 241, 0.15);
}
.drp-btn.drp-active {
    background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);
    color: #ffffff;
    border-color: transparent;
    box-shadow: 0 3px 10px rgba(99, 102, 241, 0.35);
}
.drp-sep { color: #6366f1; font-size: 13px; padding: 0 4px; }
</style>

<div class="drp-presets" id="drp-presets-<?= $_drpFrom ?>">
    <span class="drp-sep">📅</span>
    <button type="button" class="drp-btn" onclick="drpSet('today','<?= $_drpFrom ?>','<?= $_drpTo ?>',<?= $_drpFormId ? "'$_drpFormId'" : 'null' ?>)">Today</button>
    <button type="button" class="drp-btn" onclick="drpSet('yesterday','<?= $_drpFrom ?>','<?= $_drpTo ?>',<?= $_drpFormId ? "'$_drpFormId'" : 'null' ?>)">Yesterday</button>
    <button type="button" class="drp-btn" onclick="drpSet('last7','<?= $_drpFrom ?>','<?= $_drpTo ?>',<?= $_drpFormId ? "'$_drpFormId'" : 'null' ?>)">Last 7 Days</button>
    <button type="button" class="drp-btn" onclick="drpSet('last15','<?= $_drpFrom ?>','<?= $_drpTo ?>',<?= $_drpFormId ? "'$_drpFormId'" : 'null' ?>)">Last 15 Days</button>
    <button type="button" class="drp-btn" onclick="drpSet('thismonth','<?= $_drpFrom ?>','<?= $_drpTo ?>',<?= $_drpFormId ? "'$_drpFormId'" : 'null' ?>)">This Month</button>
    <button type="button" class="drp-btn" onclick="drpSet('lastmonth','<?= $_drpFrom ?>','<?= $_drpTo ?>',<?= $_drpFormId ? "'$_drpFormId'" : 'null' ?>)">Last Month</button>
    <button type="button" class="drp-btn" onclick="drpSet('last90','<?= $_drpFrom ?>','<?= $_drpTo ?>',<?= $_drpFormId ? "'$_drpFormId'" : 'null' ?>)">Last 90 Days</button>
    <button type="button" class="drp-btn" onclick="drpSet('thisyear','<?= $_drpFrom ?>','<?= $_drpTo ?>',<?= $_drpFormId ? "'$_drpFormId'" : 'null' ?>)">This Year</button>
    <button type="button" class="drp-btn" onclick="drpSet('lastyear','<?= $_drpFrom ?>','<?= $_drpTo ?>',<?= $_drpFormId ? "'$_drpFormId'" : 'null' ?>)">Last Year</button>
</div>

<script>
(function() {
    if (window._drpInitialized) return;
    window._drpInitialized = true;

    function pad(n) { return n < 10 ? '0' + n : '' + n; }
    function fmt(d) { return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate()); }

    window.drpSet = function(preset, fromId, toId, formId) {
        var now   = new Date();
        var y     = now.getFullYear();
        var m     = now.getMonth();
        var today = fmt(now);
        var from, to;

        switch (preset) {
            case 'today':
                from = to = today; break;
            case 'yesterday':
                var y2 = new Date(now); y2.setDate(y2.getDate()-1);
                from = to = fmt(y2); break;
            case 'last7':
                var d7 = new Date(now); d7.setDate(d7.getDate()-6);
                from = fmt(d7); to = today; break;
            case 'last15':
                var d15 = new Date(now); d15.setDate(d15.getDate()-14);
                from = fmt(d15); to = today; break;
            case 'thismonth':
                from = y + '-' + pad(m+1) + '-01'; to = today; break;
            case 'lastmonth':
                var lm = new Date(y, m, 0);
                var lmFirst = new Date(y, m-1, 1);
                from = fmt(lmFirst); to = fmt(lm); break;
            case 'last90':
                var d90 = new Date(now); d90.setDate(d90.getDate()-89);
                from = fmt(d90); to = today; break;
            case 'thisyear':
                from = y + '-01-01'; to = today; break;
            case 'lastyear':
                from = (y-1) + '-01-01'; to = (y-1) + '-12-31'; break;
            default: return;
        }

        var elFrom = document.getElementById(fromId);
        var elTo   = document.getElementById(toId);
        if (elFrom) elFrom.value = from;
        if (elTo)   elTo.value   = to;

        // Highlight active preset button
        var container = document.getElementById('drp-presets-' + fromId);
        if (container) {
            container.querySelectorAll('.drp-btn').forEach(function(b) {
                b.classList.remove('drp-active');
            });
            // find clicked button
            event.target.classList.add('drp-active');
        }

        // Auto-submit form if provided
        // if (formId) {
        //     var form = document.getElementById(formId);
        //     if (form) form.submit();
        // }
    };
})();
</script>
