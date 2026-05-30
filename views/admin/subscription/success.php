<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div style="max-width: 600px; margin: 60px auto; padding: 20px 0; text-align: center;">
    <div style="background: rgba(30, 27, 75, 0.45); backdrop-filter: blur(12px); border: 1px solid rgba(16, 185, 129, 0.35); border-radius: 20px; padding: 40px; box-shadow: 0 10px 40px rgba(0,0,0,0.4);">
        
        <!-- Large Glowing Success Icon -->
        <div style="width: 80px; height: 80px; background: rgba(16, 185, 129, 0.15); border: 2px solid #10B981; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 30px; box-shadow: 0 0 30px rgba(16, 185, 129, 0.3); animation: pulseSuccess 2s infinite;">
            <span style="font-size: 40px; color: #34D399;">✓</span>
        </div>

        <h2 style="font-size: 26px; font-weight: 800; margin: 0 0 16px; color: #FFFFFF;">Platform Access Restored!</h2>
        
        <p style="font-size: 14.5px; color: #94A3B8; line-height: 1.6; margin: 0 0 30px;">
            Your payment was processed successfully. The subscription limits on your affiliate tracking network have been extended, and all core functions (clicks tracking, conversions, postbacks, and manager views) have been fully unlocked.
        </p>

        <!-- CTA Redirection -->
        <a href="/admin/dashboard" style="display: inline-block; background: linear-gradient(135deg, #10B981 0%, #059669 100%); color: #FFFFFF; font-weight: 700; font-size: 14.5px; text-decoration: none; padding: 14px 40px; border-radius: 8px; box-shadow: 0 4px 20px rgba(16, 185, 129, 0.3); transition: all 0.3s;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
            Proceed to Administrative Dashboard
        </a>

    </div>
</div>

<style>
@keyframes pulseSuccess {
    0%, 100% {
        transform: scale(1);
        box-shadow: 0 0 30px rgba(16, 185, 129, 0.3);
    }
    50% {
        transform: scale(1.05);
        box-shadow: 0 0 40px rgba(16, 185, 129, 0.5);
    }
}
</style>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
