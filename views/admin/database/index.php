<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div><h1>&#128452; Database Tools</h1><p>Export SQL backup, import SQL file, view table statistics</p></div>
</div>

<div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:14px 18px;margin-bottom:24px;font-size:13px;color:#991B1B">
    <strong>&#9888; Warning:</strong> Import will execute raw SQL against your database. Only import files from trusted sources. Always take an export backup before importing.
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px">

    <!-- Export -->
    <div class="card">
        <div class="card-header"><span class="card-title">&#8595; Export Database</span></div>
        <div class="card-body">
            <p style="font-size:13px;color:#64748B;margin-bottom:16px">
                Download a full SQL dump of all tables and data. Use this as a backup before making changes.
            </p>
            <div style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:6px;padding:10px 14px;margin-bottom:16px;font-size:12px;color:#166534">
                <strong>Database:</strong> <?= Helpers::e($dbName) ?> &bull;
                <strong>Tables:</strong> <?= count($tableStats) ?> &bull;
                <strong>Total Size:</strong> <?= number_format(array_sum(array_column($tableStats,'size_mb')),2) ?> MB
            </div>
            <a href="/admin/database?action=export" class="btn btn-primary" onclick="return confirm('Download full database backup now?')">
                &#8595; Download SQL Backup
            </a>
        </div>
    </div>

    <!-- Import -->
    <div class="card">
        <div class="card-header"><span class="card-title">&#8593; Import Database</span></div>
        <div class="card-body">
            <p style="font-size:13px;color:#64748B;margin-bottom:16px">
                Upload a <code>.sql</code> file to restore or migrate. Max file size: <strong>50 MB</strong>.
            </p>
            <form method="POST" enctype="multipart/form-data">
                <?= Helpers::csrf() ?>
                <input type="hidden" name="action" value="import">
                <div class="form-group">
                    <label>SQL File</label>
                    <input type="file" name="sql_file" class="form-control" accept=".sql" required>
                    <div class="form-hint">Only <code>.sql</code> files. This will execute all statements in the file.</div>
                </div>
                <button type="submit" class="btn btn-danger"
                        onclick="return confirm('WARNING: This will execute SQL against your live database. Are you sure? Take a backup first!')">
                    &#8593; Import SQL File
                </button>
            </form>
        </div>
    </div>

</div>

<!-- Table stats -->
<div class="card">
    <div class="card-header">
        <span class="card-title">Table Statistics</span>
        <span style="font-size:12px;color:#94A3B8;margin-left:auto"><?= count($tableStats) ?> tables</span>
    </div>
    <div class="table-wrap">
        <table id="dbStatsTbl">
            <thead>
                <tr>
                    <th>Table Name</th>
                    <th>Rows (approx)</th>
                    <th>Size (MB)</th>
                    <th>Engine</th>
                    <th>Collation</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($tableStats as $t): ?>
            <tr>
                <td><code style="font-size:12px"><?= Helpers::e($t['table_name'] ?? $t['TABLE_NAME'] ?? '') ?></code></td>
                <td><?= number_format((int)($t['table_rows'] ?? $t['TABLE_ROWS'] ?? 0)) ?></td>
                <td><?= number_format((float)($t['size_mb'] ?? 0), 3) ?></td>
                <td><span class="badge badge-info" style="font-size:11px"><?= Helpers::e($t['engine'] ?? $t['ENGINE'] ?? '') ?></span></td>
                <td style="font-size:12px;color:#64748B"><?= Helpers::e($t['table_collation'] ?? $t['TABLE_COLLATION'] ?? '') ?></td>
                <td style="font-size:12px;color:#64748B"><?= $t['create_time'] ?? $t['CREATE_TIME'] ?? '—' ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
$(function(){ $('#dbStatsTbl').DataTable({pageLength:25,destroy:true,order:[[0,'asc']]}); });
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
