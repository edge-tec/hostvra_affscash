<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <h2 style="font-family: var(--font-heading); font-weight: 700; color: #FFFFFF; margin: 0;">🎨 Appearance &amp; Landing Page Builder</h2>
    <a href="/" target="_blank" class="btn-premium" style="text-decoration: none;">👁️ Preview Live Site</a>
</div>

<?php if (!empty($success)): ?>
<div class="alert alert-success" style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); color: #34D399; padding: 14px 20px; border-radius: 8px; margin-bottom: 20px;">
    <?= Helpers::e($success) ?>
</div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div class="alert alert-error" style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #FCA5A5; padding: 14px 20px; border-radius: 8px; margin-bottom: 20px;">
    <?= Helpers::e($error) ?>
</div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 30px; margin-bottom: 40px;">
    
    <!-- Customization Form -->
    <div class="glass-card">
        <div class="glass-header">
            <h3 class="glass-title">Page Layout Settings</h3>
        </div>
        <form action="/admin/landing/builder" method="POST" enctype="multipart/form-data" style="padding: 24px;">
            <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">

            <!-- Hero Section Editing -->
            <h4 style="font-family: var(--font-heading); color: #8B5CF6; border-bottom: 1px solid rgba(139,92,246,0.15); padding-bottom: 8px; margin-bottom: 16px;">1. Hero Header Customization</h4>
            
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 13px; margin-bottom: 6px; color: #C7D2FE;">Hero Main Headline</label>
                <input type="text" name="hero_headline" required class="glass-input" style="width: 100%;" value="<?= Helpers::e($lp['hero_headline'] ?? '') ?>" placeholder="e.g. The Highest Paying CPA Affiliate Network">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; margin-bottom: 6px; color: #C7D2FE;">Hero Short Description</label>
                <textarea name="hero_sub" required class="glass-input" style="width: 100%; height: 80px; resize: vertical;" placeholder="Enter details about your tracker or offer network..."><?= Helpers::e($lp['hero_sub'] ?? '') ?></textarea>
            </div>

            <!-- Brand logo and styling -->
            <h4 style="font-family: var(--font-heading); color: #8B5CF6; border-bottom: 1px solid rgba(139,92,246,0.15); padding-bottom: 8px; margin-bottom: 16px; margin-top: 30px;">2. Branding &amp; Colors</h4>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 13px; margin-bottom: 6px; color: #C7D2FE;">Upload Network Logo</label>
                    <input type="file" name="logo_file" class="glass-input" style="width: 100%;">
                    <input type="hidden" name="logo_path" value="<?= Helpers::e($lp['logo_path'] ?? '') ?>">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; margin-bottom: 6px; color: #C7D2FE;">Color Palette Style</label>
                    <div style="display: flex; gap: 10px; align-items: center; margin-top: 10px;">
                        <input type="color" name="primary_color" value="<?= Helpers::e($lp['primary_color'] ?? '#6366F1') ?>" style="cursor: pointer; width: 40px; height: 34px; border: none; border-radius: 6px;">
                        <span style="font-size: 12px; color: var(--text-muted);">Primary Theme Accent</span>
                    </div>
                </div>
            </div>

            <!-- Tracker features -->
            <h4 style="font-family: var(--font-heading); color: #8B5CF6; border-bottom: 1px solid rgba(139,92,246,0.15); padding-bottom: 8px; margin-bottom: 16px; margin-top: 30px;">3. Showcase Features</h4>
            
            <div style="margin-bottom: 16px; background: rgba(15, 12, 38, 0.2); border: 1px solid rgba(139,92,246,0.1); padding: 16px; border-radius: 8px;">
                <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px; color: #FFFFFF;">Feature Campaign 1</label>
                <input type="text" name="item_1_title" required class="glass-input" style="width: 100%; margin-bottom: 8px;" value="<?= Helpers::e($content['item_1_title'] ?? 'Real-time Analytics') ?>">
                <input type="text" name="item_1_desc" required class="glass-input" style="width: 100%;" value="<?= Helpers::e($content['item_1_desc'] ?? 'Monitor details of clicks and conversions dynamically.') ?>">
            </div>

            <div style="margin-bottom: 16px; background: rgba(15, 12, 38, 0.2); border: 1px solid rgba(139,92,246,0.1); padding: 16px; border-radius: 8px;">
                <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px; color: #FFFFFF;">Feature Campaign 2</label>
                <input type="text" name="item_2_title" required class="glass-input" style="width: 100%; margin-bottom: 8px;" value="<?= Helpers::e($content['item_2_title'] ?? 'Global Smartlinks') ?>">
                <input type="text" name="item_2_desc" required class="glass-input" style="width: 100%;" value="<?= Helpers::e($content['item_2_desc'] ?? 'Auto-rotation filters maximizing global CPC and ROI targets.') ?>">
            </div>

            <div style="margin-bottom: 24px; background: rgba(15, 12, 38, 0.2); border: 1px solid rgba(139,92,246,0.1); padding: 16px; border-radius: 8px;">
                <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px; color: #FFFFFF;">Feature Campaign 3</label>
                <input type="text" name="item_3_title" required class="glass-input" style="width: 100%; margin-bottom: 8px;" value="<?= Helpers::e($content['item_3_title'] ?? 'Weekly Fast Payouts') ?>">
                <input type="text" name="item_3_desc" required class="glass-input" style="width: 100%;" value="<?= Helpers::e($content['item_3_desc'] ?? 'Request invoice releases automatically with zero delay.') ?>">
            </div>

            <!-- SEO Tags -->
            <h4 style="font-family: var(--font-heading); color: #8B5CF6; border-bottom: 1px solid rgba(139,92,246,0.15); padding-bottom: 8px; margin-bottom: 16px; margin-top: 30px;">4. SEO Configuration</h4>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 13px; margin-bottom: 6px; color: #C7D2FE;">Meta Title</label>
                    <input type="text" name="seo_title" class="glass-input" style="width: 100%;" value="<?= Helpers::e($seoTitle) ?>" placeholder="My Tracking Network">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; margin-bottom: 6px; color: #C7D2FE;">Meta Keywords</label>
                    <input type="text" name="seo_keywords" class="glass-input" style="width: 100%;" value="<?= Helpers::e($seoKeywords) ?>" placeholder="cpa, affiliate, network">
                </div>
            </div>
            <div style="margin-bottom: 30px;">
                <label style="display: block; font-size: 13px; margin-bottom: 6px; color: #C7D2FE;">Meta Description</label>
                <input type="text" name="seo_description" class="glass-input" style="width: 100%;" value="<?= Helpers::e($seoDesc) ?>" placeholder="Enter high-converting description details...">
            </div>

            <!-- FAQ Section -->
            <h4 style="font-family: var(--font-heading); color: #8B5CF6; border-bottom: 1px solid rgba(139,92,246,0.15); padding-bottom: 8px; margin-bottom: 16px; margin-top: 30px;">5. Landing FAQ</h4>
            <div style="margin-bottom: 30px; background: rgba(15, 12, 38, 0.2); border: 1px solid rgba(139,92,246,0.1); padding: 16px; border-radius: 8px;">
                <label style="display: block; font-size: 13px; margin-bottom: 6px; color: #C7D2FE;">Frequently Asked Question</label>
                <input type="text" name="q_1" required class="glass-input" style="width: 100%; margin-bottom: 8px;" value="<?= Helpers::e($content['q_1'] ?? 'How to register?') ?>">
                <label style="display: block; font-size: 13px; margin-bottom: 6px; color: #C7D2FE;">Answer</label>
                <textarea name="a_1" required class="glass-input" style="width: 100%; height: 60px;" placeholder="Answer..."><?= Helpers::e($content['a_1'] ?? 'Click register at the top right to create affiliate campaign profile.') ?></textarea>
            </div>

            <button type="submit" class="btn-premium" style="width: 100%; padding: 14px 20px; font-size: 15px; font-weight: 700;">💾 Save Customization Styles</button>
        </form>
    </div>

    <!-- Live Preview Desktop Mockup Panel -->
    <div>
        <div class="glass-card" style="position: sticky; top: 100px;">
            <div class="glass-header">
                <h3 class="glass-title">📱 Real-time Preview</h3>
            </div>
            <div style="padding: 24px; text-align: center; background: rgba(11, 9, 26, 0.9); border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                
                <!-- Mockup browser -->
                <div style="background: rgba(15, 12, 38, 0.8); border: 1px solid var(--border-glass); border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.5);">
                    <!-- Header -->
                    <div style="background: rgba(30, 27, 75, 0.5); padding: 8px 16px; border-bottom: 1px solid rgba(255,255,255,0.08); display: flex; align-items: center; gap: 8px;">
                        <span style="width: 8px; height: 8px; background: #EF4444; border-radius: 50%;"></span>
                        <span style="width: 8px; height: 8px; background: #F59E0B; border-radius: 50%;"></span>
                        <span style="width: 8px; height: 8px; background: #10B981; border-radius: 50%;"></span>
                        <span style="background: rgba(255,255,255,0.06); padding: 2px 24px; border-radius: 6px; font-size: 10px; color: var(--text-muted); margin-left: 20px; flex-grow: 1; text-align: left;">tracker.mybrand.com</span>
                    </div>

                    <!-- Inner Mockup Body -->
                    <div style="padding: 30px 20px; text-align: center; color: #FFFFFF;">
                        <!-- Logo mockup -->
                        <div style="max-height: 24px; margin-bottom: 16px;">
                            <span style="font-family: var(--font-heading); font-weight: 700; color: #FFFFFF; letter-spacing: 0.05em; font-size: 18px;">🌐 <?= Helpers::e(Config::get('config','app.name') ?? 'AffTracker') ?></span>
                        </div>

                        <!-- Headline mockup -->
                        <h4 id="previewHeadline" style="font-size: 17px; font-weight: 800; line-height: 1.3; margin: 0 0 10px; color: #FFFFFF; font-family: var(--font-heading);">
                            <?= Helpers::e($lp['hero_headline'] ?? 'Premier Performance CPA Network') ?>
                        </h4>

                        <!-- Subheadline mockup -->
                        <p id="previewSub" style="font-size: 11px; color: var(--text-muted); margin: 0 0 20px; line-height: 1.4;">
                            <?= Helpers::e($lp['hero_sub'] ?? 'Join us to access exclusive high-converting tracking campaigns and fast payouts.') ?>
                        </p>

                        <!-- CTA button mockup -->
                        <button type="button" style="background: linear-gradient(135deg, <?= Helpers::e($lp['primary_color'] ?? '#6366F1') ?> 0%, #8B5CF6 100%); color: #FFFFFF; border: none; font-size: 11px; font-weight: 700; padding: 8px 20px; border-radius: 6px; cursor: default;">
                            Start Free Registration
                        </button>
                    </div>
                </div>

                <div style="font-size: 12px; color: var(--text-muted); margin-top: 16px;">This mockup represents a simplified simulation of your customized Hero section layout.</div>
            </div>
        </div>
    </div>

</div>

<script>
// Dynamic update of Live Mockup Preview
$('input[name="hero_headline"]').on('input', function() {
    $('#previewHeadline').text($(this).val());
});

$('textarea[name="hero_sub"]').on('input', function() {
    $('#previewSub').text($(this).val());
});
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
