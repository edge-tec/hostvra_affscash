<?php
/**
 * Fraud Center shared filter bar partial.
 * Required variables (set before include):
 *   $affiliateList   array of [{id, affiliate_code, name}]
 *   $offerList       array of [{id, name}]
 *   $affId           int  (current affiliate filter, 0 = all)
 *   $offerId         int  (current offer filter, 0 = all)
 *   $fraudFilterUrl  string  base URL for this section (e.g. '/admin/fraud-center/click-intelligence')
 *   $showConvId      bool   (optional) show Conversion ID input
 *   $convId          string (optional) current conversion_id filter value
 *   $exportParams    array  (optional) extra hidden params to pass on export (e.g. ['tab'=>$tab])
 */
$_fShowConvId   = $showConvId ?? false;
$_fConvId       = $convId ?? '';
$_fExportParams = $exportParams ?? [];
$_fCurrentGet   = array_filter($_GET, fn($k) => !in_array($k,['affiliate_id','offer_id','conversion_id','from','to','export']), ARRAY_FILTER_USE_KEY);
// Date range — populated from controller via $dr (fraud_date_range()) when available.
$_fFrom = $dr['from'] ?? (Helpers::get('from') ?: date('Y-m-01'));
$_fTo   = $dr['to']   ?? (Helpers::get('to')   ?: date('Y-m-d'));
$_fFormId = 'fdc-bar-' . substr(md5(uniqid('', true)), 0, 6);
$_fFromId = $_fFormId . '-from';
$_fToId   = $_fFormId . '-to';
?>
<style>
.fdc-filter-bar{background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:12px 16px;margin-bottom:14px;display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end}
.fdc-filter-bar label{font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.04em;display:block;margin-bottom:3px}
.fdc-filter-bar select,.fdc-filter-bar input[type=text],.fdc-filter-bar input[type=number],.fdc-filter-bar input[type=date]{font-size:13px;padding:7px 10px;border:1px solid #D1D5DB;border-radius:7px;background:#fff;color:#111827;outline:none}
.fdc-filter-bar select:focus,.fdc-filter-bar input:focus{border-color:#6366F1}
.fdc-filter-bar-presets{flex-basis:100%}
</style>
<form method="get" id="<?= htmlspecialchars($_fFormId, ENT_QUOTES) ?>" class="fdc-filter-bar">
    <?php foreach ($_fCurrentGet as $k => $v): ?>
    <input type="hidden" name="<?= Helpers::e($k) ?>" value="<?= Helpers::e($v) ?>">
    <?php endforeach; ?>

    <div class="fdc-filter-bar-presets">
        <?php $drpFromId=$_fFromId; $drpToId=$_fToId; $drpFormId=$_fFormId; include BASE_PATH.'/views/partials/date_range_picker.php'; ?>
    </div>

    <div>
        <label>From</label>
        <input type="date" id="<?= htmlspecialchars($_fFromId, ENT_QUOTES) ?>" name="from" value="<?= Helpers::e($_fFrom) ?>">
    </div>
    <div>
        <label>To</label>
        <input type="date" id="<?= htmlspecialchars($_fToId, ENT_QUOTES) ?>" name="to" value="<?= Helpers::e($_fTo) ?>">
    </div>

    <div>
        <label>Affiliate</label>
        <select name="affiliate_id" style="min-width:170px">
            <option value="0">All Affiliates</option>
            <?php foreach ($affiliateList as $a): ?>
            <option value="<?= $a['id'] ?>" <?= ($affId??0)==$a['id']?'selected':'' ?>>
                <?= Helpers::e($a['affiliate_code'].' — '.$a['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label>Offer</label>
        <select name="offer_id" style="min-width:150px">
            <option value="0">All Offers</option>
            <?php foreach ($offerList as $o): ?>
            <option value="<?= $o['id'] ?>" <?= ($offerId??0)==$o['id']?'selected':'' ?>><?= Helpers::e($o['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <?php if ($_fShowConvId): ?>
    <div>
        <label>Conversion ID</label>
        <input type="text" name="conversion_id" placeholder="Partial match..." value="<?= Helpers::e($_fConvId) ?>" style="min-width:160px;font-family:monospace;font-size:12px">
    </div>
    <?php endif; ?>

    <div style="display:flex;gap:6px">
        <button type="submit" class="fds-btn fds-btn-primary" style="padding:7px 16px;font-size:13px">Filter</button>
        <a href="<?= $fraudFilterUrl ?>?<?= http_build_query(array_filter(['affiliate_id'=>0,'offer_id'=>0])) ?>" class="fds-btn fds-btn-outline" style="padding:7px 14px;font-size:13px">Clear</a>
    </div>

    <div style="margin-left:auto">
        <?php
        $expParams = array_merge($_fCurrentGet, ['affiliate_id'=>($affId??0),'offer_id'=>($offerId??0),'from'=>$_fFrom,'to'=>$_fTo,'export'=>'csv'], $_fExportParams);
        if ($_fShowConvId && $_fConvId !== '') $expParams['conversion_id'] = $_fConvId;
        ?>
        <a href="?<?= http_build_query($expParams) ?>" class="fds-btn fds-btn-outline" style="padding:7px 14px;font-size:13px">&#11123; Export CSV</a>
    </div>
</form>
