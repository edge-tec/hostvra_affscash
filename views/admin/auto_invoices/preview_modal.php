<?php
/**
 * Invoice Preview Modal View
 */
?>
<div id="modalInvoiceQuickView" class="aig-modal-overlay">
    <div class="aig-modal" style="max-width:800px;">
        <div class="aig-modal-header" style="background:#f8fafc;">
            <div>
                <h3 id="quickViewInvNum" style="margin:0;font-size:16px;font-weight:700;color:#1e293b;">Invoice Preview</h3>
                <p id="quickViewInvSub" style="margin:2px 0 0 0;font-size:12px;color:#64748b;"></p>
            </div>
            <button type="button" onclick="closeQuickViewModal()" style="border:none;background:none;font-size:22px;cursor:pointer;color:#64748b;">&times;</button>
        </div>
        <div class="aig-modal-body" style="padding:24px;">
            <div id="quickViewContent">
                <!-- Dynamically populated if needed -->
            </div>
        </div>
        <div class="aig-modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeQuickViewModal()">Close</button>
        </div>
    </div>
</div>

<script>
function closeQuickViewModal() {
    document.getElementById('modalInvoiceQuickView').style.display = 'none';
}
</script>
