<?php
/**
 * Reusable Export dropdown — CSV + Excel.
 *
 * Optional vars (set BEFORE require'ing this partial):
 *   $exportBaseGet   array  base GET params (defaults to $_GET minus 'export')
 *   $exportXlsEnabled bool   show the "Download as Excel" option (default true)
 */
$exportBaseGet     = $exportBaseGet     ?? array_diff_key($_GET, ['export' => 1]);
$exportXlsEnabled  = $exportXlsEnabled  ?? true;
static $_expCounter = 0;
$_expCounter++;
$_expId = 'rep-export-' . $_expCounter;
$_csvUrl = '?' . http_build_query(array_merge($exportBaseGet, ['export' => 'csv']));
$_xlsUrl = '?' . http_build_query(array_merge($exportBaseGet, ['export' => 'xls']));
?>
<div class="rep-export-wrap" style="display:inline-block;position:relative">
    <button type="button" class="btn btn-primary btn-sm"
            id="<?= $_expId ?>-btn"
            onclick="(function(id){var m=document.getElementById(id+'-menu');m.style.display=m.style.display==='block'?'none':'block';})('<?= $_expId ?>')"
            style="white-space:nowrap;background:#0EA5E9;border-color:#0EA5E9;color:#fff;font-weight:600">
        &#128229; Export Report &#9660;
    </button>
    <div id="<?= $_expId ?>-menu" style="display:none;position:absolute;right:0;top:calc(100% + 4px);background:#fff;border:1px solid #E2E8F0;border-radius:8px;box-shadow:0 8px 24px rgba(15,23,42,.12);min-width:200px;z-index:100;overflow:hidden">
        <a href="<?= $_csvUrl ?>" style="display:flex;align-items:center;gap:10px;padding:10px 14px;color:#0F172A;text-decoration:none;font-size:13px;border-bottom:1px solid #F1F5F9">
            <span style="font-size:16px">&#128196;</span>
            <span>
                <div style="font-weight:600">Download as CSV</div>
                <div style="font-size:11px;color:#64748B">.csv — opens in Excel, Google Sheets, Numbers</div>
            </span>
        </a>
        <?php if ($exportXlsEnabled): ?>
        <a href="<?= $_xlsUrl ?>" style="display:flex;align-items:center;gap:10px;padding:10px 14px;color:#0F172A;text-decoration:none;font-size:13px">
            <span style="font-size:16px">&#128202;</span>
            <span>
                <div style="font-weight:600">Download as Excel</div>
                <div style="font-size:11px;color:#64748B">.xls — formatted spreadsheet</div>
            </span>
        </a>
        <?php endif; ?>
    </div>
</div>
<script>
(function(id){
    document.addEventListener('click', function(e){
        var btn  = document.getElementById(id+'-btn');
        var menu = document.getElementById(id+'-menu');
        if (!btn || !menu) return;
        if (!btn.contains(e.target) && !menu.contains(e.target)) menu.style.display = 'none';
    });
})('<?= $_expId ?>');
</script>
