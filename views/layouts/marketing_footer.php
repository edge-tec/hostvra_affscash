</div> <!-- End section-container -->

<footer style="background: rgba(11, 9, 26, 0.7); backdrop-filter: blur(10px); border-top: 1px solid var(--border-glass); padding: 40px 30px; text-align: center; font-size: 13.5px; color: #64748B;">
    <div style="max-width: 1100px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
        <div>
            &copy; <?= date('Y') ?> <?= Helpers::e(Config::get('config','app.name') ?? 'EliteAli') ?> Performance. All Rights Reserved.
        </div>
        <div style="display: flex; gap: 24px;">
            <a href="/privacy-policy" style="color: #64748B; text-decoration: none;">Privacy Policy</a>
            <a href="/docs" style="color: #64748B; text-decoration: none;">Developer Guides</a>
            <a href="/login" style="color: #64748B; text-decoration: none;">Sign In</a>
        </div>
    </div>
</footer>

</body>
</html>
