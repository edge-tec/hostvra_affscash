<?php
/**
 * Affiliate reward popup partial.
 *
 * Included by views/layouts/affiliate.php. Self-contained — no side effects
 * if disabled, no popup configured, or the affiliate has already dismissed
 * the current version. Dismissal POSTs to /affiliate/popup/dismiss.
 */
if (\Auth::role() !== 'affiliate') return;

$_popupAffId = (int)\Auth::affiliateId();
if ($_popupAffId <= 0) return;

$_popup = PopupService::currentFor($_popupAffId);
if (!$_popup) return;

$_popupCsrf = htmlspecialchars(\Auth::generateCsrf(), ENT_QUOTES);
$_popupVer  = (int)$_popup['version'];
?>
<div id="afPopupBackdrop" style="position:fixed;inset:0;background:rgba(15,23,42,.72);z-index:9998;display:flex;align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px)">
    <div role="dialog" aria-modal="true" aria-labelledby="afPopupTitle"
         style="background:linear-gradient(160deg,#4F46E5 0%,#7C3AED 60%,#EC4899 100%);color:#fff;border-radius:20px;max-width:440px;width:100%;padding:0;box-shadow:0 24px 64px -16px rgba(15,23,42,.5);position:relative;overflow:hidden;animation:afPopupIn .25s ease-out">
        <button type="button" id="afPopupClose"
                aria-label="Close"
                style="position:absolute;top:12px;right:12px;background:rgba(255,255,255,.18);border:none;color:#fff;width:32px;height:32px;border-radius:50%;font-size:18px;cursor:pointer;z-index:2">&times;</button>

        <?php if (!empty($_popup['image_path'])): ?>
        <div style="background:rgba(255,255,255,.06);padding:24px 24px 0;display:flex;justify-content:center">
            <img src="<?= htmlspecialchars($_popup['image_path'], ENT_QUOTES) ?>"
                 alt="" style="max-width:200px;max-height:160px;border-radius:14px;box-shadow:0 8px 24px rgba(0,0,0,.18)">
        </div>
        <?php endif; ?>

        <div style="padding:24px 28px 26px;text-align:center">
            <h2 id="afPopupTitle" style="margin:0 0 12px;font-size:22px;font-weight:800;line-height:1.25">
                <?= htmlspecialchars((string)$_popup['title'], ENT_QUOTES) ?>
            </h2>
            <div style="font-size:14px;line-height:1.55;opacity:.92">
                <?= nl2br(htmlspecialchars((string)($_popup['body'] ?? ''), ENT_QUOTES)) ?>
            </div>

            <div style="margin-top:22px;display:flex;flex-direction:column;gap:8px">
                <?php if (!empty($_popup['cta_label']) && !empty($_popup['cta_url'])): ?>
                <a href="<?= htmlspecialchars((string)$_popup['cta_url'], ENT_QUOTES) ?>"
                   target="_blank" rel="noopener"
                   id="afPopupCta"
                   style="display:inline-block;background:#fff;color:#4F46E5;padding:11px 22px;border-radius:8px;font-weight:700;text-decoration:none;font-size:14px">
                    <?= htmlspecialchars((string)$_popup['cta_label'], ENT_QUOTES) ?>
                </a>
                <?php endif; ?>
                <button type="button" id="afPopupDismiss"
                        style="background:transparent;color:rgba(255,255,255,.85);border:none;padding:6px;font-size:12.5px;text-decoration:underline;cursor:pointer">
                    Don't show this again
                </button>
            </div>
        </div>
    </div>
</div>
<style>
@keyframes afPopupIn { from { transform:scale(.94); opacity:0 } to { transform:scale(1); opacity:1 } }
</style>
<script>
(function(){
    var bd = document.getElementById('afPopupBackdrop');
    if (!bd) return;
    function close(){ bd.parentNode && bd.parentNode.removeChild(bd); }
    function dismiss(){
        var fd = new FormData();
        fd.append('_token',  '<?= $_popupCsrf ?>');
        fd.append('version', '<?= $_popupVer ?>');
        fetch('/affiliate/popup/dismiss', { method:'POST', body: fd, credentials:'same-origin' })
            .catch(function(){})
            .finally(close);
    }
    document.getElementById('afPopupClose').addEventListener('click', close);
    document.getElementById('afPopupDismiss').addEventListener('click', dismiss);
    // Close on Esc + backdrop click — does NOT mark as dismissed.
    bd.addEventListener('click', function(e){ if (e.target === bd) close(); });
    document.addEventListener('keydown', function(e){ if (e.key === 'Escape') close(); });
})();
</script>
