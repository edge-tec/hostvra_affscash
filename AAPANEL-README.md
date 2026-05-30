# aaPanel Installation Guide

This project has been updated for compatibility with the aaPanel hosting system. Docker dependencies have been removed/ignored, and the project now runs directly on your standard LNMP/LAMP stack.

## Nginx URL Rewrite Rule (for aaPanel Nginx)

If you are using Nginx in aaPanel, you MUST configure the URL Rewrite rules to ensure the API and routing work properly, and sensitive folders are protected.

1. Go to your aaPanel **Websites** tab.
2. Click on the **Configuration** (or settings) for your domain.
3. Select the **URL rewrite** menu.
4. Copy and paste the contents of `aapanel-nginx.conf` (located in the project root) into the text box and click **Save**.

## Database & API Setup

1. The project does not require a complex SQL database setup (it uses JSON files inside `api/store/` as per `INSTALL.txt`).
2. Follow the standard setup outlined in `INSTALL.txt`:
   - Make sure `api/store/` has `755` permissions.
   - Update your password and secret token in `api/data.php`.
   - Go to `https://yourdomain.com/admin.html` to login and use your new setup.

## PHP Version
- Ensure your aaPanel website is set to use **PHP 7.4 or newer** (PHP 8.2 is recommended).
