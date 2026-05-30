<?php require BASE_PATH . '/views/layouts/affiliate.php'; ?>

<style>
.mgr-page { max-width: 680px; margin: 0 auto; }

/* Hero card */
.mgr-hero {
    background: linear-gradient(145deg, #0F172A 0%, #1E1B4B 50%, #1E293B 100%);
    border-radius: 20px;
    padding: 44px 32px 36px;
    text-align: center;
    position: relative;
    overflow: hidden;
    margin-bottom: 20px;
}
.mgr-hero::before {
    content: '';
    position: absolute;
    top: -60px; right: -60px;
    width: 220px; height: 220px;
    border-radius: 50%;
    background: rgba(99,102,241,.15);
}
.mgr-hero::after {
    content: '';
    position: absolute;
    bottom: -40px; left: -40px;
    width: 160px; height: 160px;
    border-radius: 50%;
    background: rgba(139,92,246,.1);
}
.mgr-label {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .2em;
    text-transform: uppercase;
    color: rgba(255,255,255,.4);
    margin-bottom: 20px;
    position: relative; z-index: 1;
}
.mgr-avatar-wrap {
    position: relative;
    display: inline-block;
    margin-bottom: 16px;
    z-index: 1;
}
.mgr-avatar-img {
    width: 96px; height: 96px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid rgba(255,255,255,.2);
    box-shadow: 0 8px 32px rgba(0,0,0,.4);
    display: block;
}
.mgr-avatar-initial {
    width: 96px; height: 96px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6D28D9, #4F46E5);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 36px;
    font-weight: 800;
    color: #fff;
    border: 4px solid rgba(255,255,255,.18);
    box-shadow: 0 8px 32px rgba(0,0,0,.4);
    margin: 0 auto;
}
.mgr-online-dot {
    position: absolute;
    bottom: 4px; right: 4px;
    width: 18px; height: 18px;
    border-radius: 50%;
    background: #10B981;
    border: 3px solid #1E293B;
}
.mgr-name {
    font-size: 22px;
    font-weight: 800;
    color: #fff;
    letter-spacing: -.01em;
    position: relative; z-index: 1;
}
.mgr-role {
    font-size: 12px;
    color: rgba(255,255,255,.45);
    margin-top: 6px;
    position: relative; z-index: 1;
}

/* Contact grid */
.mgr-contacts {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 20px;
}
@media (max-width: 500px) { .mgr-contacts { grid-template-columns: 1fr; } }

.mgr-contact-btn {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 18px;
    background: #fff;
    border: 1.5px solid #E8ECF4;
    border-radius: 14px;
    text-decoration: none;
    transition: all .18s ease;
    cursor: pointer;
    box-shadow: 0 1px 4px rgba(0,0,0,.05);
}
.mgr-contact-btn:hover {
    border-color: #A5B4FC;
    background: #F5F6FF;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(79,70,229,.12);
}
.mgr-contact-btn:active { transform: translateY(0); }

.mgr-contact-icon {
    width: 42px; height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.mgr-contact-text { min-width: 0; flex: 1; }
.mgr-contact-label {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .09em;
    color: #9CA3AF;
    margin-bottom: 3px;
}
.mgr-contact-value {
    font-size: 13px;
    font-weight: 600;
    color: #111827;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.mgr-contact-arrow {
    color: #CBD5E1;
    flex-shrink: 0;
    transition: transform .18s, color .18s;
}
.mgr-contact-btn:hover .mgr-contact-arrow {
    color: #6366F1;
    transform: translateX(3px);
}

/* Info card */
.mgr-info-card {
    background: linear-gradient(135deg, #F0F4FF 0%, #EEF2FF 100%);
    border: 1px solid #DDE4F9;
    border-radius: 14px;
    padding: 18px 20px;
    display: flex;
    align-items: flex-start;
    gap: 14px;
}
.mgr-info-icon {
    width: 36px; height: 36px;
    border-radius: 10px;
    background: linear-gradient(135deg, #4F46E5, #7C3AED);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.mgr-info-text { font-size: 13px; color: #4338CA; line-height: 1.6; }
.mgr-info-text strong { color: #1E1B4B; }

/* No manager state */
.mgr-empty {
    text-align: center;
    padding: 64px 24px;
    background: #fff;
    border: 1.5px dashed #E2E8F0;
    border-radius: 20px;
}
.mgr-empty-icon {
    width: 72px; height: 72px;
    border-radius: 50%;
    background: linear-gradient(135deg, #F0F4FF, #EEF2FF);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
}
.mgr-empty h3 { font-size: 18px; font-weight: 700; color: #1E293B; margin-bottom: 8px; }
.mgr-empty p  { font-size: 13px; color: #64748B; max-width: 300px; margin: 0 auto; }
</style>

<div class="page-header" style="margin-bottom:20px">
    <h1 style="font-size:22px;font-weight:800;color:#1E293B">Your Affiliate Manager</h1>
    <p style="font-size:13px;color:#64748B;margin-top:4px">Your dedicated point of contact</p>
</div>

<div class="mgr-page">

<?php if ($mgr): ?>

    <!-- Hero profile card -->
    <div class="mgr-hero">
        <div class="mgr-label">Your Dedicated Manager</div>
        <div class="mgr-avatar-wrap">
            <?php if (!empty($mgr['profile_pic'])): ?>
            <img src="<?= Helpers::e($mgr['profile_pic']) ?>" alt="" class="mgr-avatar-img">
            <?php else: ?>
            <div class="mgr-avatar-initial"><?= strtoupper(substr($mgr['first_name'], 0, 1)) ?></div>
            <?php endif; ?>
            <div class="mgr-online-dot"></div>
        </div>
        <div class="mgr-name"><?= Helpers::e(trim($mgr['first_name'] . ' ' . $mgr['last_name'])) ?></div>
        <div class="mgr-role">Affiliate Manager</div>
    </div>

    <!-- Contact buttons -->
    <div class="mgr-contacts">

        <?php if (!empty($mgr['email'])): ?>
        <a href="mailto:<?= Helpers::e($mgr['email']) ?>" class="mgr-contact-btn">
            <div class="mgr-contact-icon" style="background:#DBEAFE">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1D4ED8" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            </div>
            <div class="mgr-contact-text">
                <div class="mgr-contact-label">Email</div>
                <div class="mgr-contact-value"><?= Helpers::e($mgr['email']) ?></div>
            </div>
            <svg class="mgr-contact-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
        <?php endif; ?>

        <?php if (!empty($mgr['telegram'])): ?>
        <a href="https://t.me/<?= Helpers::e(ltrim($mgr['telegram'], '@')) ?>" target="_blank" rel="noopener" class="mgr-contact-btn">
            <div class="mgr-contact-icon" style="background:#E0F7FA">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="#2AABEE"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.562 8.248-1.97 9.289c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12L8.32 14.617l-2.96-.924c-.643-.204-.657-.643.136-.953l11.57-4.461c.537-.194 1.006.131.836.969z"/></svg>
            </div>
            <div class="mgr-contact-text">
                <div class="mgr-contact-label">Telegram</div>
                <div class="mgr-contact-value"><?= Helpers::e($mgr['telegram']) ?></div>
            </div>
            <svg class="mgr-contact-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
        <?php endif; ?>

        <?php if (!empty($mgr['skype'])): ?>
        <a href="skype:<?= Helpers::e($mgr['skype']) ?>?chat" class="mgr-contact-btn">
            <div class="mgr-contact-icon" style="background:#E0F2FE">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="#00AFF0"><path d="M12.073 0C5.405 0 0 5.405 0 12.073c0 2.39.69 4.618 1.887 6.495L.412 22.81a.818.818 0 0 0 1.007.982l4.342-1.42a12.028 12.028 0 0 0 6.312 1.78C18.741 24.152 24 18.601 24 12.073 24 5.405 18.741 0 12.073 0zm5.814 16.677c-.385.544-.943.982-1.675 1.314-.732.332-1.596.499-2.593.499-1.22 0-2.248-.223-3.082-.669-.613-.332-1.124-.782-1.534-1.35a3.138 3.138 0 0 1-.613-1.832c0-.438.153-.808.46-1.11.307-.301.696-.452 1.169-.452.385 0 .715.114.99.341.274.228.5.567.676 1.018.196.497.422.913.676 1.248.255.334.604.612 1.048.832.444.22.998.33 1.662.33.944 0 1.716-.218 2.313-.654.598-.437.897-.954.897-1.55 0-.487-.148-.88-.444-1.178-.296-.297-.69-.532-1.183-.703-.492-.17-1.164-.343-2.016-.518-1.14-.24-2.104-.524-2.891-.852-.787-.328-1.41-.797-1.869-1.406-.458-.61-.687-1.378-.687-2.304 0-.882.247-1.666.74-2.352.494-.686 1.187-1.213 2.08-1.58.893-.368 1.91-.552 3.05-.552 1.05 0 1.966.17 2.748.51.782.34 1.4.8 1.854 1.38.455.578.683 1.22.683 1.924 0 .432-.15.804-.452 1.117-.302.312-.68.468-1.134.468-.4 0-.725-.105-.974-.314-.25-.21-.478-.53-.686-.96-.212-.462-.45-.86-.715-1.193-.264-.334-.612-.61-1.044-.828-.43-.218-.98-.327-1.648-.327-.82 0-1.493.194-2.018.582-.524.388-.787.843-.787 1.365 0 .327.091.602.274.826.182.223.43.415.742.574.312.16.634.287.966.381.332.094.88.234 1.643.42 1.008.23 1.927.49 2.756.78.83.29 1.54.65 2.132 1.08.592.43 1.06.975 1.403 1.633.343.658.515 1.45.515 2.376 0 1.003-.212 1.893-.636 2.67z"/></svg>
            </div>
            <div class="mgr-contact-text">
                <div class="mgr-contact-label">Skype</div>
                <div class="mgr-contact-value"><?= Helpers::e($mgr['skype']) ?></div>
            </div>
            <svg class="mgr-contact-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
        <?php endif; ?>

        <?php if (!empty($mgr['phone'])): ?>
        <a href="tel:<?= Helpers::e($mgr['phone']) ?>" class="mgr-contact-btn">
            <div class="mgr-contact-icon" style="background:#D1FAE5">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.65 3.35 2 2 0 0 1 3.62 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.62a16 16 0 0 0 6 6l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            </div>
            <div class="mgr-contact-text">
                <div class="mgr-contact-label">Mobile</div>
                <div class="mgr-contact-value"><?= Helpers::e($mgr['phone']) ?></div>
            </div>
            <svg class="mgr-contact-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
        <?php endif; ?>

        <?php if (!empty($mgr['discord'])): ?>
        <a href="https://discord.com/users/<?= Helpers::e($mgr['discord']) ?>" target="_blank" rel="noopener" class="mgr-contact-btn">
            <div class="mgr-contact-icon" style="background:#EDE9FE">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="#5865F2"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057c.002.022.015.043.033.056a19.988 19.988 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 0 0-.041-.106 13.201 13.201 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/></svg>
            </div>
            <div class="mgr-contact-text">
                <div class="mgr-contact-label">Discord</div>
                <div class="mgr-contact-value"><?= Helpers::e($mgr['discord']) ?></div>
            </div>
            <svg class="mgr-contact-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
        <?php endif; ?>

    </div>

    <!-- Info box -->
    <div class="mgr-info-card">
        <div class="mgr-info-icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        </div>
        <div class="mgr-info-text">
            <strong>Need help?</strong> Your affiliate manager is your dedicated point of contact for offers, payments, and account questions. Reach out via any channel above and we'll get back to you shortly.
        </div>
    </div>

<?php else: ?>

    <!-- No manager assigned -->
    <div class="mgr-empty">
        <div class="mgr-empty-icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#6366F1" stroke-width="1.5"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
        </div>
        <h3>No Manager Assigned</h3>
        <p>You haven't been assigned an affiliate manager yet. Please contact support for assistance.</p>
    </div>

<?php endif; ?>

</div>

<?php require BASE_PATH . '/views/layouts/affiliate_footer.php'; ?>
