<?php
/**
 * Reusable reject-with-reason modal.
 * Required vars (set BEFORE require'ing this partial):
 *   $rejectFormAction   string  URL to POST to (the controller's existing endpoint)
 *   $rejectStatusField  string  Hidden field name for the new status (e.g. 'status' or 'conv_status' or 'conv_action')
 *   $rejectStatusValue  string  Default 'rejected' — the value submitted in $rejectStatusField
 *   $rejectExtraHidden  array   key=>value of extra hidden fields the controller expects (e.g. ['action' => 'update_conv_status'])
 *
 * The modal is opened via JS:
 *   openRejectModal('<conversion_id>')
 *
 * The modal always renders all active reasons from rejection_reasons table plus
 * a "Other (custom reason)" option that reveals a textarea for one-off reasons.
 */
$rejectStatusField = $rejectStatusField ?? 'status';
$rejectStatusValue = $rejectStatusValue ?? 'rejected';
$rejectExtraHidden = $rejectExtraHidden ?? [];
$_rmReasons        = RejectionHelper::getActiveReasons();
?>
<div id="rejectReasonModal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:11000;align-items:center;justify-content:center;padding:16px">
    <div style="background:#fff;border-radius:12px;max-width:500px;width:100%;box-shadow:0 24px 64px rgba(0,0,0,.25);overflow:hidden">
        <div style="background:linear-gradient(135deg,#DC2626,#991B1B);color:#fff;padding:14px 18px;display:flex;align-items:center;justify-content:space-between">
            <div>
                <div style="font-weight:700;font-size:15px">&#10005; Reject Conversion</div>
                <div style="font-size:11px;opacity:.85;margin-top:2px">Pick a reason — it will be stored and shown in reports.</div>
            </div>
            <button type="button" onclick="closeRejectModal()" style="background:none;border:none;color:#fff;font-size:20px;cursor:pointer;line-height:1;opacity:.85">&times;</button>
        </div>
        <form method="POST" action="<?= Helpers::e($rejectFormAction) ?>" style="padding:18px" onsubmit="return rejectModalSubmit(event)">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="conversion_id" id="rrm-cid">
            <input type="hidden" name="<?= Helpers::e($rejectStatusField) ?>" value="<?= Helpers::e($rejectStatusValue) ?>">
            <?php foreach ($rejectExtraHidden as $_k => $_v): ?>
            <input type="hidden" name="<?= Helpers::e($_k) ?>" value="<?= Helpers::e($_v) ?>">
            <?php endforeach; ?>

            <div class="form-group">
                <label style="font-size:12px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.04em">Reason</label>
                <select id="rrm-select" class="form-control" onchange="rejectModalOnChange(this)" required>
                    <option value="">— Select reason —</option>
                    <?php foreach ($_rmReasons as $_r): ?>
                    <option value="<?= Helpers::e($_r['label']) ?>"><?= Helpers::e($_r['label']) ?></option>
                    <?php endforeach; ?>
                    <option value="__custom">Other (write a custom reason…)</option>
                </select>
            </div>

            <div class="form-group" id="rrm-custom-wrap" style="display:none">
                <label style="font-size:12px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.04em">Custom reason</label>
                <textarea id="rrm-custom" class="form-control" rows="3" maxlength="500"
                          placeholder="Describe the reason (max 500 chars)"></textarea>
            </div>

            <input type="hidden" name="rejection_reason" id="rrm-reason-final" value="">

            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:14px;border-top:1px solid #E2E8F0;padding-top:14px">
                <button type="button" class="btn btn-secondary" onclick="closeRejectModal()">Cancel</button>
                <button type="submit" class="btn btn-danger" id="rrm-submit">&#10005; Confirm Reject</button>
            </div>
        </form>
    </div>
</div>

<script>
(function(){
    var modal = document.getElementById('rejectReasonModal');
    if (!modal) return;
    modal.addEventListener('click', function(e){ if (e.target === modal) closeRejectModal(); });
})();
window.openRejectModal = function(convId, opts){
    document.getElementById('rrm-cid').value = String(convId || '');
    var sel = document.getElementById('rrm-select');
    var cwr = document.getElementById('rrm-custom-wrap');
    var ctx = document.getElementById('rrm-custom');
    var fin = document.getElementById('rrm-reason-final');
    if (sel) sel.value = '';
    if (cwr) cwr.style.display = 'none';
    if (ctx) ctx.value = '';
    if (fin) fin.value = '';
    document.getElementById('rejectReasonModal').style.display = 'flex';
};
window.closeRejectModal = function(){
    document.getElementById('rejectReasonModal').style.display = 'none';
};
window.rejectModalOnChange = function(sel){
    var cwr = document.getElementById('rrm-custom-wrap');
    if (sel.value === '__custom') {
        cwr.style.display = '';
        document.getElementById('rrm-custom').focus();
    } else {
        cwr.style.display = 'none';
    }
};
window.rejectModalSubmit = function(e){
    var sel  = document.getElementById('rrm-select');
    var ctx  = document.getElementById('rrm-custom');
    var fin  = document.getElementById('rrm-reason-final');
    if (!sel.value) { alert('Please select a rejection reason.'); e.preventDefault(); return false; }
    var reason = sel.value === '__custom' ? (ctx.value || '').trim() : sel.value;
    if (!reason) { alert('Please enter a custom reason or pick one from the list.'); e.preventDefault(); return false; }
    fin.value = reason;
    return true;
};
</script>
