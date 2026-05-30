<?php
/**
 * Shared date-range filter bar for every Fraud Detection page.
 * Renders the chip presets (Today / Yesterday / Last 7 / Last 15 / This Month /
 * Last Month / Last 90 / This Year / Last Year) plus From/To inputs.
 *
 * Caller-overridable variables (set BEFORE include):
 *   $fdfFrom        — current "from" value (YYYY-MM-DD)
 *   $fdfTo          — current "to"   value (YYYY-MM-DD)
 *   $fdfFormId      — unique form id (default 'fd-filter-form-XXX')
 *   $fdfResetUrl    — Reset button target (default: current path, no query)
 *   $fdfHiddenInputs — assoc array of extra hidden inputs to preserve in the form
 */
$_fdfFrom     = $fdfFrom        ?? date('Y-m-01');
$_fdfTo       = $fdfTo          ?? date('Y-m-d');
$_fdfFormId   = $fdfFormId      ?? 'fd-filter-form-' . substr(md5(uniqid('', true)), 0, 6);
$_fdfResetUrl = $fdfResetUrl    ?? strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$_fdfHidden   = $fdfHiddenInputs ?? [];
$_fdfFromId   = $_fdfFormId . '-from';
$_fdfToId     = $_fdfFormId . '-to';
?>
<div class="card mb-3">
    <div class="card-body" style="padding:14px 18px">
        <form method="GET" id="<?= htmlspecialchars($_fdfFormId, ENT_QUOTES) ?>" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
            <?php foreach ($_fdfHidden as $_k => $_v): if ($_v === null || $_v === '') continue; ?>
            <input type="hidden" name="<?= htmlspecialchars((string)$_k, ENT_QUOTES) ?>" value="<?= htmlspecialchars((string)$_v, ENT_QUOTES) ?>">
            <?php endforeach; ?>

            <div style="width:100%">
                <?php $drpFromId=$_fdfFromId; $drpToId=$_fdfToId; $drpFormId=$_fdfFormId; include BASE_PATH.'/views/partials/date_range_picker.php'; ?>
            </div>

            <div class="form-group mb-0" style="min-width:140px">
                <label style="font-size:12px">From</label>
                <input type="date" id="<?= htmlspecialchars($_fdfFromId, ENT_QUOTES) ?>" name="from" class="form-control" value="<?= htmlspecialchars($_fdfFrom, ENT_QUOTES) ?>" style="font-size:13px">
            </div>
            <div class="form-group mb-0" style="min-width:140px">
                <label style="font-size:12px">To</label>
                <input type="date" id="<?= htmlspecialchars($_fdfToId, ENT_QUOTES) ?>" name="to" class="form-control" value="<?= htmlspecialchars($_fdfTo, ENT_QUOTES) ?>" style="font-size:13px">
            </div>
            <div style="display:flex;gap:8px;padding-bottom:1px">
                <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                <a href="<?= htmlspecialchars($_fdfResetUrl, ENT_QUOTES) ?>" class="btn btn-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>
