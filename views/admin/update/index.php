<?php require BASE_PATH . '/views/layouts/admin.php'; ?>
<style>
/* ── Script Upgrade Page Styles ──────────────────────────────────────── */
.upg-card{background:#fff;border:1px solid var(--border);border-radius:10px;margin-bottom:20px;overflow:hidden}
.upg-header{padding:18px 24px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px}
.upg-stat{text-align:center;padding:18px 20px}
.upg-stat .num{font-size:32px;font-weight:800;line-height:1}
.upg-stat .lbl{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--text-muted);margin-top:6px}
.mig-btn{display:inline-flex;align-items:center;gap:8px;background:#4F46E5;color:#fff;border:none;border-radius:8px;padding:12px 28px;font-size:15px;font-weight:700;cursor:pointer;text-decoration:none;transition:background .15s}
.mig-btn:hover{background:#4338CA}
.mig-btn.green{background:#059669}.mig-btn.green:hover{background:#047857}
.mig-btn.red{background:#DC2626}.mig-btn.red:hover{background:#B91C1C}
.mig-btn.grey{background:#64748B}.mig-btn.grey:hover{background:#475569}
.mig-btn:disabled{background:#94A3B8;cursor:not-allowed}
.upg-tab{padding:11px 18px;border:none;background:none;cursor:pointer;font-size:13px;font-weight:600;color:var(--text-muted);border-bottom:3px solid transparent;margin-bottom:-2px;transition:all .15s;white-space:nowrap}
.upg-tab.active{color:#4F46E5;border-bottom-color:#4F46E5}
.drop-zone{border:2px dashed #C7D2FE;border-radius:12px;background:#FAFAFE;padding:50px 30px;text-align:center;cursor:pointer;transition:all .2s}
.drop-zone:hover,.drop-zone.drag-over{border-color:#4F46E5;background:#EEF2FF}
.step-row{display:flex;gap:12px;align-items:flex-start;padding:12px 0;border-bottom:1px solid #F1F5F9}
.step-row:last-child{border-bottom:none}
.step-dot{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;flex-shrink:0}
.badge-new{background:#D1FAE5;color:#065F46;font-size:10px;font-weight:700;padding:2px 7px;border-radius:10px}
.badge-mod{background:#DBEAFE;color:#1E40AF;font-size:10px;font-weight:700;padding:2px 7px;border-radius:10px}
.badge-mig{background:#EDE9FE;color:#5B21B6;font-size:10px;font-weight:700;padding:2px 7px;border-radius:10px}
.log-row{display:flex;gap:10px;align-items:flex-start;padding:9px 16px;border-bottom:1px solid #F8FAFC;font-size:13px}
.log-row:last-child{border-bottom:none}
.log-row.sub{padding-left:36px;font-size:12px;color:var(--text-muted)}
.pbar-wrap{height:10px;background:#E2E8F0;border-radius:99px;overflow:hidden}
.pbar-fill{height:10px;border-radius:99px;background:linear-gradient(90deg,#4F46E5,#7C3AED);transition:width .5s ease}
</style>

<?php
$curVersion = $appVersion['version'] ?? '1.0.0';
$newFilesCount = $staged ? count(array_filter($staged['files'], fn($f)=>$f['type']==='new')) : 0;
$modFilesCount = $staged ? count(array_filter($staged['files'], fn($f)=>$f['type']==='modified')) : 0;
$newMigCount   = $staged ? count(array_filter($staged['migrations'], fn($m)=>$m['status']==='new')) : 0;
$hasPending    = !empty($pending);
?>

<!-- PAGE HEADER -->
<div class="page-header">
    <div>
        <h1>&#128640; Script Upgrade</h1>
        <p>Upload your new script ZIP — database migrations, new features, and code files update automatically. All existing data is preserved.</p>
    </div>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <span style="background:#1E293B;color:#94A3B8;font-size:13px;font-weight:700;padding:6px 14px;border-radius:20px">
            Current v<?= Helpers::e($curVersion) ?>
        </span>
        <span style="background:<?= $hasPending?'#FEF3C7':'#D1FAE5' ?>;color:<?= $hasPending?'#92400E':'#065F46' ?>;font-size:13px;font-weight:700;padding:6px 14px;border-radius:20px">
            DB Schema v<?= $currentVer ?> <?= $hasPending ? '· '.count($pending).' pending' : '✓' ?>
        </span>
    </div>
</div>

<?php if ($flash = Helpers::getFlash()): ?>
<div class="alert alert-<?= $flash['type'] ?>" style="margin-bottom:16px;font-size:14px"><?= Helpers::e($flash['message']) ?></div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════
     UPGRADE RESULT CARD
═══════════════════════════════════════════════════════ -->
<?php if ($upgradeResult): ?>
<div class="upg-card" style="border-color:<?= $upgradeResult['success']?'#6EE7B7':'#FCA5A5' ?>;border-width:2px">
    <div style="padding:20px 24px;background:<?= $upgradeResult['success']?'#F0FDF4':'#FEF2F2' ?>;border-bottom:1px solid <?= $upgradeResult['success']?'#A7F3D0':'#FCA5A5' ?>">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
            <div style="display:flex;align-items:center;gap:14px">
                <span style="font-size:40px"><?= $upgradeResult['success']?'🎉':'⚠️' ?></span>
                <div>
                    <div style="font-size:19px;font-weight:800;color:<?= $upgradeResult['success']?'#065F46':'#991B1B' ?>">
                        <?= $upgradeResult['success'] ? 'Upgrade Completed Successfully!' : 'Upgrade Completed with Errors' ?>
                    </div>
                    <div style="font-size:13px;color:var(--text-muted);margin-top:3px">
                        All existing data preserved · Backup ID: <code><?= Helpers::e($upgradeResult['backup']) ?></code>
                        <?php if ($upgradeResult['new_ver']): ?>
                        · Upgraded to v<?= Helpers::e($upgradeResult['new_ver']) ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div style="display:flex;gap:8px">
                <?php if ($upgradeResult['backup']): ?>
                <form method="POST" style="display:inline"
                      onsubmit="return confirm('Roll back to the pre-upgrade backup?\nThis restores code files only. DB changes stay in place.')">
                    <?= Helpers::csrf() ?>
                    <input type="hidden" name="action" value="rollback">
                    <input type="hidden" name="backup_id" value="<?= Helpers::e($upgradeResult['backup']) ?>">
                    <button class="mig-btn red" style="padding:8px 16px;font-size:13px">&#8635; Rollback Files</button>
                </form>
                <?php endif; ?>
                <form method="POST" style="display:inline">
                    <?= Helpers::csrf() ?>
                    <input type="hidden" name="action" value="dismiss_result">
                    <button class="mig-btn grey" style="padding:8px 16px;font-size:13px">Dismiss</button>
                </form>
            </div>
        </div>
    </div>
    <!-- Log rows -->
    <div>
        <?php foreach ($upgradeResult['log'] as $l): ?>
        <div class="log-row <?= !empty($l['sub'])?'sub':'' ?>">
            <span style="font-size:15px;flex-shrink:0"><?= $l['ok']?'✅':'❌' ?></span>
            <div>
                <span style="font-weight:<?= empty($l['sub'])?'700':'400' ?>"><?= Helpers::e($l['step']) ?>:</span>
                <span style="color:var(--text-muted)"> <?= Helpers::e($l['msg']) ?></span>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (!empty($upgradeResult['errors'])): ?>
        <div style="padding:12px 16px;background:#FEF2F2;border-top:1px solid #FCA5A5">
            <div style="font-size:12px;font-weight:700;color:#991B1B;margin-bottom:6px">ERRORS DETAILS:</div>
            <?php foreach ($upgradeResult['errors'] as $e): ?>
            <div style="font-size:12px;color:#B91C1C;margin-bottom:4px;font-family:monospace">• <?= Helpers::e($e) ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════
     PENDING MIGRATIONS ALERT (always visible if any pending)
═══════════════════════════════════════════════════════ -->
<?php if ($hasPending): ?>
<div class="upg-card" style="border-color:#FCD34D;border-width:2px">
    <div style="padding:16px 24px;background:#FFFBEB;border-bottom:1px solid #FDE68A;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
        <div style="display:flex;align-items:center;gap:12px">
            <span style="font-size:28px">⚠️</span>
            <div>
                <div style="font-size:16px;font-weight:800;color:#92400E">
                    <?= count($pending) ?> Database Migration<?= count($pending)>1?'s':'' ?> Pending
                </div>
                <div style="font-size:13px;color:#B45309;margin-top:2px">
                    Your database needs to be updated. Click <strong>Run Migrations Now</strong> to apply changes. All existing data is preserved.
                </div>
            </div>
        </div>
        <form method="POST" onsubmit="return confirmMigrate()">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="action" value="migrate_only">
            <button type="submit" class="mig-btn" id="migrateBtn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                Run Migrations Now
            </button>
        </form>
    </div>
    <!-- Pending list -->
    <div>
        <?php foreach ($pending as $pf): ?>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 24px;border-bottom:1px solid #FEF9C3">
            <div style="display:flex;align-items:center;gap:10px">
                <span style="background:#FEF3C7;color:#92400E;font-size:11px;font-weight:700;padding:2px 8px;border-radius:8px">PENDING</span>
                <span style="font-family:monospace;font-size:13px;font-weight:600;color:#78350F"><?= Helpers::e($pf) ?></span>
                <span style="font-size:12px;color:#B45309">
                    — <?= Helpers::e(ucfirst(str_replace('_',' ',preg_replace('/^\d+_/','',pathinfo($pf,PATHINFO_FILENAME))))) ?>
                </span>
            </div>
            <form method="POST" style="display:inline">
                <?= Helpers::csrf() ?>
                <input type="hidden" name="action" value="run_one">
                <input type="hidden" name="file" value="<?= Helpers::e($pf) ?>">
                <button type="submit" class="btn btn-secondary btn-sm"
                        onclick="return confirm('Run only <?= Helpers::e($pf) ?>?')">Run this one</button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php else: ?>
<div style="display:flex;align-items:center;gap:10px;padding:12px 18px;background:#F0FDF4;border:1px solid #A7F3D0;border-radius:8px;margin-bottom:20px">
    <span style="font-size:20px">✅</span>
    <span style="font-size:14px;font-weight:600;color:#065F46">Database is up to date — all <?= $currentVer ?> migration<?= $currentVer!=1?'s':'' ?> applied.</span>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════
     STAGED PACKAGE REVIEW
═══════════════════════════════════════════════════════ -->
<?php if ($staged && !$upgradeResult): ?>
<?php $newVer = $staged['version']['version'] ?? '?'; ?>
<div class="upg-card" style="border-color:#A5B4FC;border-width:2px">
    <!-- Header -->
    <div style="padding:20px 24px;background:linear-gradient(135deg,#EEF2FF 0%,#F5F3FF 100%);border-bottom:1px solid #C7D2FE">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:14px">
            <div style="display:flex;align-items:center;gap:14px">
                <span style="font-size:42px">📦</span>
                <div>
                    <div style="font-size:19px;font-weight:800;color:#1E1B4B">Package Ready to Install</div>
                    <div style="display:flex;align-items:center;gap:10px;margin-top:6px;flex-wrap:wrap">
                        <span style="background:#1E293B;color:#CBD5E1;font-size:12px;font-weight:700;padding:4px 12px;border-radius:12px">v<?= Helpers::e($curVersion) ?></span>
                        <span style="color:#6366F1;font-weight:800;font-size:18px">→</span>
                        <span style="background:#4F46E5;color:#fff;font-size:12px;font-weight:700;padding:4px 12px;border-radius:12px">v<?= Helpers::e($newVer) ?></span>
                        <span style="font-size:12px;color:#6B7280"><?= Helpers::e($staged['orig_name']) ?> (<?= round($staged['file_size']/1024) ?> KB)</span>
                    </div>
                </div>
            </div>
            <div style="display:flex;gap:10px;align-items:center">
                <form method="POST" style="display:inline">
                    <?= Helpers::csrf() ?>
                    <input type="hidden" name="action" value="cancel_staged">
                    <button class="mig-btn grey" style="padding:10px 18px;font-size:13px">✕ Cancel</button>
                </form>
                <form method="POST" id="fullUpgradeForm" onsubmit="return startFullUpgrade()">
                    <?= Helpers::csrf() ?>
                    <input type="hidden" name="action" value="run_full_upgrade">
                    <button type="submit" class="mig-btn" id="fullUpgradeBtn"
                            style="padding:13px 30px;font-size:15px;font-weight:800;box-shadow:0 4px 12px rgba(79,70,229,.35)">
                        &#9654;&nbsp; Run Full Upgrade
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Summary row -->
    <div style="display:flex;flex-wrap:wrap;gap:0;border-bottom:1px solid #E0E7FF">
        <?php
        $summaryItems = [
            ['icon'=>'🗄️','num'=>$newMigCount, 'lbl'=>'DB Migrations','bg'=>'#EDE9FE','col'=>'#5B21B6'],
            ['icon'=>'✨','num'=>$newFilesCount,'lbl'=>'New Files',    'bg'=>'#D1FAE5','col'=>'#065F46'],
            ['icon'=>'📝','num'=>$modFilesCount,'lbl'=>'Updated Files','bg'=>'#DBEAFE','col'=>'#1E40AF'],
            ['icon'=>'🛡️','num'=>'✓',           'lbl'=>'Data Safe',   'bg'=>'#F0FDF4','col'=>'#059669'],
        ];
        foreach ($summaryItems as $si): ?>
        <div style="flex:1;min-width:120px;display:flex;align-items:center;gap:10px;padding:14px 20px;background:<?= $si['bg'] ?>;border-right:1px solid #E0E7FF">
            <span style="font-size:22px"><?= $si['icon'] ?></span>
            <div>
                <div style="font-size:20px;font-weight:800;color:<?= $si['col'] ?>"><?= $si['num'] ?></div>
                <div style="font-size:11px;font-weight:700;color:<?= $si['col'] ?>;opacity:.8"><?= $si['lbl'] ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Progress bar (hidden until running) -->
    <div id="upgradeProgressBox" style="display:none;padding:16px 24px;background:#EEF2FF;border-bottom:1px solid #C7D2FE">
        <div style="font-size:13px;font-weight:700;color:#4F46E5;margin-bottom:8px" id="progressLabel">⏳ Starting upgrade...</div>
        <div class="pbar-wrap"><div class="pbar-fill" id="progressFill" style="width:5%"></div></div>
        <div style="font-size:11px;color:var(--text-muted);margin-top:6px">Please wait — do not close this page</div>
    </div>

    <!-- 2-col detail -->
    <div style="display:grid;grid-template-columns:1fr 1fr;min-height:260px">
        <!-- Left: What happens -->
        <div style="padding:20px 24px;border-right:1px solid #E0E7FF">
            <div style="font-size:11px;font-weight:700;color:#6366F1;letter-spacing:.6px;margin-bottom:14px">WHAT WILL HAPPEN</div>
            <?php
            $steps = [
                ['1','💾','Database Backup',   'Full SQL dump taken before any changes'],
                ['2','🗄️','Apply '.$newMigCount.' Migration'.($newMigCount!=1?'s':''), 'New columns & tables added safely (IF NOT EXISTS)'],
                ['3','⚙️','Sync Config',        'New settings added — your current values unchanged'],
                ['4','📝','Replace '.($newFilesCount+$modFilesCount).' Code File'.($newFilesCount+$modFilesCount!=1?'s':''), 'Old files backed up before replacement'],
                ['5','⚡','Clear Cache',        'OPcache flushed — new code takes effect instantly'],
            ];
            foreach ($steps as [$n,$ic,$t,$d]): ?>
            <div class="step-row">
                <div class="step-dot" style="background:#4F46E5;color:#fff"><?= $n ?></div>
                <div>
                    <div style="font-weight:700;font-size:13px"><?= $ic ?> <?= $t ?></div>
                    <div style="font-size:12px;color:var(--text-muted);margin-top:2px"><?= $d ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Right: changelog + file list -->
        <div style="padding:20px 24px;overflow:hidden">
            <?php if ($staged['changelog']): ?>
            <div style="font-size:11px;font-weight:700;color:#6366F1;letter-spacing:.6px;margin-bottom:8px">WHAT'S NEW</div>
            <div style="font-size:12px;line-height:1.7;white-space:pre-line;max-height:130px;overflow-y:auto;padding:10px 12px;background:#F8FAFF;border:1px solid #E0E7FF;border-radius:6px;margin-bottom:14px;color:#1E293B">
                <?= Helpers::e(mb_substr($staged['changelog'],0,800)).(mb_strlen($staged['changelog'])>800?"\n...":"") ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($staged['migrations'])): ?>
            <div style="font-size:11px;font-weight:700;color:#6366F1;letter-spacing:.6px;margin-bottom:6px">DATABASE CHANGES</div>
            <?php foreach ($staged['migrations'] as $m): ?>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:5px">
                <span class="<?= $m['status']==='new'?'badge-mig':'' ?>" style="<?= $m['status']!=='new'?'background:#F1F5F9;color:#64748B;font-size:10px;font-weight:700;padding:2px 7px;border-radius:10px':'' ?>">
                    <?= $m['status']==='new' ? 'NEW' : 'EXISTS' ?>
                </span>
                <span style="font-size:12px;font-family:monospace;color:#374151"><?= Helpers::e($m['name']) ?></span>
                <?php if (!empty($m['copied'])): ?><span style="font-size:10px;color:#059669">✓ copied</span><?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($staged['files'])): ?>
            <div style="font-size:11px;font-weight:700;color:#6366F1;letter-spacing:.6px;margin:12px 0 6px">
                CODE CHANGES (<?= count($staged['files']) ?>)
            </div>
            <div style="max-height:130px;overflow-y:auto;border:1px solid var(--border);border-radius:6px">
                <?php foreach ($staged['files'] as $sf): ?>
                <div style="display:flex;align-items:center;gap:7px;padding:4px 10px;border-bottom:1px solid #F8FAFC;font-size:11px">
                    <span class="<?= $sf['type']==='new'?'badge-new':'badge-mod' ?>"><?= strtoupper($sf['type']) ?></span>
                    <span style="font-family:monospace;color:#374151;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= Helpers::e($sf['path']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════
     UPLOAD ZONE
═══════════════════════════════════════════════════════ -->
<div class="upg-card">
    <div class="upg-header">
        <div>
            <h3 style="margin:0;font-size:16px;font-weight:700">
                <?= $staged ? '📦 Upload Different Version' : '📦 Upload New Script Version' ?>
            </h3>
            <p style="margin:4px 0 0;font-size:13px;color:var(--text-muted)">
                Upload your new version .zip — the system detects changes and shows a review before applying anything.
            </p>
        </div>
    </div>
    <div style="padding:24px">
        <form method="POST" enctype="multipart/form-data" id="uploadForm" onsubmit="return handleUploadSubmit()">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="action" value="upload_analyze">
            <input type="file" id="zipFileInput" name="script_zip" accept=".zip" style="display:none" required
                   onchange="onFileSelected(this)">

            <!-- Drop Zone -->
            <label for="zipFileInput" class="drop-zone" id="dropZone" style="display:block"
                 ondragover="event.preventDefault();this.classList.add('drag-over')"
                 ondragleave="this.classList.remove('drag-over')"
                 ondrop="handleFileDrop(event)">
                <div style="font-size:52px;margin-bottom:12px">📦</div>
                <div style="font-size:16px;font-weight:700;color:#1E1B4B;margin-bottom:6px">Drop your .zip file here</div>
                <div style="font-size:13px;color:var(--text-muted);margin-bottom:16px">or click to browse files</div>
                <div id="selectedFileInfo" style="display:none;padding:8px 20px;background:#EDE9FE;border-radius:20px;font-size:13px;font-weight:600;color:#5B21B6;max-width:100%;overflow:hidden;text-overflow:ellipsis"></div>
            </label>

            <div id="uploadActionRow" style="display:none;text-align:center;margin-top:18px">
                <button type="submit" id="analyzeBtn" class="mig-btn" style="font-size:15px;padding:13px 36px">
                    🔍 Analyze Package
                </button>
                <div style="font-size:12px;color:var(--text-muted);margin-top:8px">
                    Nothing changes until you review and confirm. A database backup is always taken first.
                </div>
            </div>

            <div id="uploadProgressRow" style="display:none;text-align:center;margin-top:18px">
                <div class="pbar-wrap" style="max-width:400px;margin:0 auto 8px"><div class="pbar-fill" style="width:50%"></div></div>
                <div style="font-size:13px;color:#4F46E5;font-weight:600">⏳ Uploading and analyzing package...</div>
                <div style="font-size:12px;color:var(--text-muted);margin-top:4px">Please wait, do not close this page</div>
            </div>
        </form>

        <!-- Info strip -->
        <div style="margin-top:18px;padding:12px 16px;background:#F0FDF4;border:1px solid #A7F3D0;border-radius:8px;font-size:13px;color:#065F46;line-height:1.6">
            <strong>🔒 Safety guarantee:</strong>
            Database is backed up before any change. Your <code>config/</code>, <code>uploads/</code>, and all existing records are
            <strong>never deleted or overwritten</strong>. If anything fails, one-click rollback restores your files.
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     STAT CARDS ROW
═══════════════════════════════════════════════════════ -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px;margin-bottom:20px">
    <div class="upg-card" style="margin:0">
        <div class="upg-stat">
            <div class="num" style="color:#4F46E5"><?= $currentVer ?></div>
            <div class="lbl">Applied Migrations</div>
        </div>
    </div>
    <div class="upg-card" style="margin:0;border-color:<?= $hasPending?'#FDE68A':'#A7F3D0' ?>">
        <div class="upg-stat">
            <div class="num" style="color:<?= $hasPending?'#D97706':'#059669' ?>"><?= count($pending) ?></div>
            <div class="lbl">Pending</div>
        </div>
    </div>
    <div class="upg-card" style="margin:0;border-color:<?= empty($missingKeys)?'#A7F3D0':'#FDE68A' ?>">
        <div class="upg-stat">
            <div class="num" style="color:<?= empty($missingKeys)?'#059669':'#D97706' ?>"><?= count($missingKeys) ?></div>
            <div class="lbl">Missing Config</div>
        </div>
    </div>
    <div class="upg-card" style="margin:0">
        <div class="upg-stat">
            <div class="num" style="color:#374151;font-size:16px"><?= Helpers::e($curVersion) ?></div>
            <div class="lbl">App Version</div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     TABS
═══════════════════════════════════════════════════════ -->
<div style="border-bottom:2px solid var(--border);display:flex;gap:0;overflow-x:auto">
    <button class="upg-tab active" id="tab-history" onclick="switchTab('history')">📋 Migration History</button>
    <button class="upg-tab" id="tab-config"  onclick="switchTab('config')">⚙️ Config Sync</button>
    <button class="upg-tab" id="tab-backups" onclick="switchTab('backups')">🗄️ Backups <?= count($backups)?'('.count($backups).')':'' ?></button>
    <button class="upg-tab" id="tab-system"  onclick="switchTab('system')">🔧 System Check</button>
</div>

<!-- TAB: Migration History -->
<div class="upg-card" id="panel-history" style="border-radius:0 0 8px 8px;border-top:none;margin-bottom:20px">
    <?php if (empty($migrationList)): ?>
    <div style="padding:20px;text-align:center;color:var(--text-muted)">No migration files found in install/migrations/</div>
    <?php else: ?>
    <div class="table-wrap" style="padding:0">
        <table style="font-size:13px;width:100%">
            <thead>
                <tr>
                    <th style="padding:10px 16px">Migration File</th>
                    <th>Status</th>
                    <th>Batch</th>
                    <th>Applied At</th>
                    <th style="text-align:right">Time</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($migrationList as $m): ?>
            <tr>
                <td style="padding:10px 16px;font-family:monospace;font-size:12px;white-space:nowrap"><?= Helpers::e($m['file']) ?></td>
                <td>
                    <?php if (($m['status']??'')=='applied'): ?>
                        <span class="badge badge-success">✓ Applied</span>
                    <?php elseif(($m['status']??'')=='failed'): ?>
                        <span class="badge badge-danger">✗ Failed</span>
                    <?php else: ?>
                        <span class="badge badge-warning">● Pending</span>
                    <?php endif; ?>
                </td>
                <td style="color:var(--text-muted)"><?= $m['batch'] ? '#'.$m['batch'] : '—' ?></td>
                <td style="font-size:12px;color:var(--text-muted);white-space:nowrap">
                    <?= $m['applied_at'] ? date('Y-m-d H:i', strtotime($m['applied_at'])) : '—' ?>
                </td>
                <td style="text-align:right;color:var(--text-muted)">
                    <?= $m['execution_ms'] ? number_format($m['execution_ms']).'ms' : '—' ?>
                </td>
                <td style="font-size:11px;max-width:240px;overflow:hidden;text-overflow:ellipsis;color:<?= ($m['status']??'')==='failed'?'#DC2626':'var(--text-muted)' ?>">
                    <?= Helpers::e(mb_substr($m['error_message'] ?? $m['message'] ?? '', 0, 120)) ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- TAB: Config Sync -->
<div class="upg-card" id="panel-config" style="display:none;border-radius:0 0 8px 8px;border-top:none;margin-bottom:20px">
    <div class="upg-header">
        <div>
            <div style="font-size:14px;font-weight:700">Config Key Synchronization</div>
            <div style="font-size:12px;color:var(--text-muted);margin-top:3px">
                Adds any new default config keys that are missing. Your existing settings are never changed.
            </div>
        </div>
        <form method="POST">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="action" value="sync_config">
            <button class="btn btn-secondary btn-sm">↻ Sync Defaults Now</button>
        </form>
    </div>
    <?php if (empty($missingKeys)): ?>
    <div style="padding:16px 24px;font-size:13px;color:#059669">✅ All config keys are present — nothing missing.</div>
    <?php else: ?>
    <table style="font-size:12px;width:100%">
        <thead><tr><th style="padding:8px 16px">Missing Key</th><th style="padding:8px 16px">Will be set to</th></tr></thead>
        <tbody>
        <?php foreach ($missingKeys as $k=>$v): ?>
        <tr style="border-top:1px solid var(--border)">
            <td style="padding:8px 16px;font-family:monospace;color:#1D4ED8"><?= Helpers::e($k) ?></td>
            <td style="padding:8px 16px;font-family:monospace;color:#047857"><?= Helpers::e(is_bool($v)?($v?'true':'false'):(string)$v) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- TAB: Backups -->
<div class="upg-card" id="panel-backups" style="display:none;border-radius:0 0 8px 8px;border-top:none;margin-bottom:20px">
    <?php if (empty($backups)): ?>
    <div style="padding:20px 24px;color:var(--text-muted);text-align:center">
        No backups yet. A full database + code backup is created automatically before every upgrade.
    </div>
    <?php else: ?>
    <table style="font-size:13px;width:100%">
        <thead><tr>
            <th style="padding:10px 16px">Backup ID</th>
            <th>Date & Time</th>
            <th>DB Backup</th>
            <th>Size</th>
            <th>Action</th>
        </tr></thead>
        <tbody>
        <?php foreach ($backups as $b): ?>
        <tr style="border-top:1px solid var(--border)">
            <td style="padding:10px 16px;font-family:monospace;font-size:12px"><?= Helpers::e($b['id']) ?></td>
            <td style="font-size:13px"><?= Helpers::e($b['date']) ?></td>
            <td><?= $b['has_db']?'<span class="badge badge-success">✓ Yes</span>':'<span class="badge badge-muted">No</span>' ?></td>
            <td style="color:var(--text-muted)"><?= Helpers::e($b['db_size']) ?></td>
            <td>
                <form method="POST" style="display:inline"
                      onsubmit="return confirm('Roll back to backup <?= Helpers::e($b['id']) ?>?\n\nThis restores code files only.\nDatabase records stay as they are.')">
                    <?= Helpers::csrf() ?>
                    <input type="hidden" name="action" value="rollback">
                    <input type="hidden" name="backup_id" value="<?= Helpers::e($b['id']) ?>">
                    <button class="btn btn-secondary btn-sm" style="color:#DC2626;border-color:#FCA5A5">↺ Rollback</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- TAB: System Check -->
<div class="upg-card" id="panel-system" style="display:none;border-radius:0 0 8px 8px;border-top:none;margin-bottom:20px">
    <table style="font-size:13px;width:100%">
        <thead><tr>
            <th style="padding:10px 16px">Check</th>
            <th>Result</th>
            <th>Details</th>
        </tr></thead>
        <tbody>
        <?php foreach ($systemChecks as $c): ?>
        <tr style="border-top:1px solid var(--border)">
            <td style="padding:10px 16px;font-weight:600"><?= Helpers::e($c['label']) ?></td>
            <td>
                <?= $c['ok']
                    ? '<span class="badge badge-success">✓ OK</span>'
                    : '<span class="badge badge-danger">✗ Fail</span>' ?>
            </td>
            <td style="font-size:12px;color:<?= $c['ok']?'var(--text-muted)':'#DC2626' ?>"><?= Helpers::e($c['detail']) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
// ── Tab switching ─────────────────────────────────────────────────────────────
var TAB_IDS = ['history','config','backups','system'];
function switchTab(id) {
    TAB_IDS.forEach(function(t) {
        var p = document.getElementById('panel-' + t);
        var b = document.getElementById('tab-' + t);
        if (p) p.style.display = (t === id) ? 'block' : 'none';
        if (b) b.classList.toggle('active', t === id);
    });
    try { localStorage.setItem('upg_active_tab', id); } catch(e) {}
}
(function() {
    var saved = '';
    try { saved = localStorage.getItem('upg_active_tab') || 'history'; } catch(e) { saved = 'history'; }
    switchTab(saved);
})();

// ── File upload ──────────────────────────────────────────────────────────────
var droppedFile = null; // stores file from drag-drop (avoids DataTransfer constructor)

function showSelectedFile(f) {
    if (!f) return;
    var sizeKB = Math.round(f.size / 1024);
    var sizeStr = sizeKB > 1024 ? (sizeKB/1024).toFixed(1)+' MB' : sizeKB+' KB';
    var info = document.getElementById('selectedFileInfo');
    if (info) { info.textContent = '📦 ' + f.name + ' (' + sizeStr + ')'; info.style.display = 'inline-block'; }
    var row = document.getElementById('uploadActionRow');
    if (row) row.style.display = 'block';
}
function onFileSelected(input) {
    if (!input.files || !input.files.length) return;
    droppedFile = null; // clear any previously dragged file
    showSelectedFile(input.files[0]);
}
function handleFileDrop(e) {
    e.preventDefault();
    var dz = document.getElementById('dropZone');
    if (dz) dz.classList.remove('drag-over');
    if (!e.dataTransfer || !e.dataTransfer.files.length) return;
    var f = e.dataTransfer.files[0];
    if (!f.name.toLowerCase().endsWith('.zip')) {
        alert('Please drop a .zip file.');
        return;
    }
    droppedFile = f;
    showSelectedFile(f);
}
function handleUploadSubmit() {
    var fi   = document.getElementById('zipFileInput');
    var file = droppedFile || (fi && fi.files && fi.files.length ? fi.files[0] : null);
    if (!file) { alert('Please select a .zip file first.'); return false; }
    if (!file.name.toLowerCase().endsWith('.zip')) { alert('Please select a .zip file.'); return false; }

    var btn  = document.getElementById('analyzeBtn');
    var aRow = document.getElementById('uploadActionRow');
    var pRow = document.getElementById('uploadProgressRow');
    if (btn)  btn.disabled = true;
    if (aRow) aRow.style.display = 'none';
    if (pRow) pRow.style.display = 'block';
    var pbarFill = pRow ? pRow.querySelector('.pbar-fill') : null;

    var form = document.getElementById('uploadForm');
    var fd   = new FormData(form);
    // If file came via drag-drop, FormData has an empty script_zip — replace it
    if (droppedFile) {
        fd.delete('script_zip');
        fd.append('script_zip', droppedFile, droppedFile.name);
    }

    var xhr = new XMLHttpRequest();
    xhr.upload.addEventListener('progress', function(e) {
        if (e.lengthComputable && pbarFill) {
            // Cap upload progress at 85%; remaining 15% is server-side processing
            pbarFill.style.width = Math.round(e.loaded / e.total * 85) + '%';
        }
    });
    xhr.addEventListener('load', function() {
        if (pbarFill) pbarFill.style.width = '100%';
        window.location.href = xhr.responseURL || window.location.href;
    });
    xhr.addEventListener('error', function() {
        alert('Upload failed. Please check your connection and try again.');
        if (btn)  { btn.disabled = false; btn.textContent = '🔍 Analyze Package'; }
        if (aRow) aRow.style.display = 'block';
        if (pRow) pRow.style.display = 'none';
    });
    xhr.open('POST', form.getAttribute('action') || window.location.href);
    xhr.send(fd);
    return false; // prevent native form submit; XHR handles it
}

// ── Full upgrade progress animation ──────────────────────────────────────────
function startFullUpgrade() {
    var confirmed = confirm(
        'Start full upgrade now?\n\n' +
        '✅ Full database backup taken first\n' +
        '✅ All existing data preserved\n' +
        '✅ Rollback available if needed\n\n' +
        'Do not close this page during the upgrade.\n\nContinue?'
    );
    if (!confirmed) return false;

    var btn  = document.getElementById('fullUpgradeBtn');
    var pBox = document.getElementById('upgradeProgressBox');
    var fill = document.getElementById('progressFill');
    var lbl  = document.getElementById('progressLabel');
    if (btn)  { btn.disabled = true; btn.innerHTML = '⏳ Upgrading...'; }
    if (pBox) pBox.style.display = 'block';

    var steps = [
        [10,  '💾 Taking database backup...'],
        [30,  '🗄️ Applying database migrations...'],
        [55,  '⚙️ Syncing config defaults...'],
        [78,  '📝 Replacing code files...'],
        [92,  '⚡ Clearing cache...'],
        [98,  '✅ Almost done...'],
    ];
    var i = 0;
    var iv = setInterval(function() {
        if (i < steps.length) {
            if (fill) fill.style.width = steps[i][0] + '%';
            if (lbl)  lbl.textContent  = steps[i][1];
            i++;
        } else {
            clearInterval(iv);
        }
    }, 1800);
    return true;
}

// ── Migrate confirmation ──────────────────────────────────────────────────────
function confirmMigrate() {
    var ok = confirm(
        'Run all <?= count($pending) ?> pending database migration(s) now?\n\n' +
        '✅ Only adds new columns/tables (IF NOT EXISTS)\n' +
        '✅ No existing data is deleted or changed\n\n' +
        'Continue?'
    );
    if (!ok) return false;
    var btn = document.getElementById('migrateBtn');
    if (btn) { btn.disabled = true; btn.innerHTML = '⏳ Running migrations...'; }
    return true;
}
</script>
