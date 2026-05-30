# Affscash Application Installation Guide

This guide covers how to install the Affscash tracking platform directly on **aaPanel** using the standard LNMP (Linux, Nginx, MySQL, PHP) stack, without Docker.

---

## 🚀 aaPanel Installation (Recommended)

aaPanel makes managing your web server, PHP, MySQL, and SSL certificates incredibly easy.

### 1. Prerequisites
Ensure you have the following installed in your aaPanel App Store:
- **Nginx** (or Apache)
- **MySQL** (version 5.7 or 8.0)
- **PHP** (version 8.1 or higher recommended)
- **phpMyAdmin** (optional but helpful)

### 2. Create the Website
1. Log into your aaPanel dashboard.
2. Go to **Website** and click **Add site**.
3. Enter your domain (e.g., `yourdomain.com`).
4. In the **Database** dropdown, select **MySQL** to create a database simultaneously. Note down the Database Name, User, and Password.
5. Set PHP version to your installed version (e.g., PHP-81).
6. Click **Submit**.

### 3. Upload Project Files
1. In aaPanel, go to **Files** and navigate to your new website's document root (e.g., `/www/wwwroot/yourdomain.com`).
2. Delete the default `index.html` and `404.html` files.
3. Upload all the files from this repository directly into this folder, or use the aaPanel terminal to clone the repository:
   ```bash
   cd /www/wwwroot/yourdomain.com
   git clone https://github.com/edge-tec/affscshnetwork-aapanel.git .
   ```
4. Create the following folders if they don't exist: `config`, `storage`, `uploads`, `logs`.
5. Ensure these folders and the `install/` folder have `0777` permissions (read, write, execute for all). You can set this by right-clicking the folders in aaPanel Files and selecting **Permission**.

### 4. Setup Configuration
1. Inside the `config` folder, create a new file named `config.json` (if it does not exist).
2. Edit the file and paste the following (replacing with your aaPanel database details):
```json
{
    "app": {
        "name": "Affscash",
        "url": "https://yourdomain.com",
        "timezone": "UTC",
        "version": "1.0.0",
        "debug": false,
        "auto_migrate": true
    },
    "database": {
        "host": "127.0.0.1",
        "port": 3306,
        "name": "YOUR_DATABASE_NAME",
        "user": "YOUR_DATABASE_USER",
        "password": "YOUR_DATABASE_PASSWORD",
        "charset": "utf8mb4"
    }
}
```
3. Save the file.

### 5. Configure URL Rewrite (Nginx)
If you are using Nginx on aaPanel, you must configure URL rewrites for the routing to work properly.
1. Go to **Website** and click on your domain name.
2. Click on **URL rewrite** in the left menu.
3. Paste the contents of the `aapanel-nginx.conf` file provided in the repository:
```nginx
# Block direct access to sensitive directories
location ~ ^/(config|core|models|controllers|logs|storage|data)/ {
    deny all;
}

# Block sensitive install files (but allow install/index.php for first-time setup)
location = /install/schema.sql { deny all; }
location = /install/install.lock { deny all; }
location = /install/config_defaults.json { deny all; }

# Force public route directories through the router
location /reviews {
    try_files $uri $uri/ /index.php?$query_string;
}

location /blog {
    try_files $uri $uri/ /index.php?$query_string;
}

# Route everything else through index.php
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

# Security headers
add_header X-Content-Type-Options "nosniff" always;
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
```
4. Click **Save**.
*(Note: If you are using Apache, the included `.htaccess` file will handle this automatically.)*

### 6. SSL Configuration
1. Go to **Website** and click on your domain name.
2. Click on **SSL**.
3. Select **Let's Encrypt**, choose your domain, and click **Apply**.
4. Once applied, enable **Force HTTPS**.

### 7. Run the Installer
Simply open `https://yourdomain.com` in your web browser. 
Because `"auto_migrate": true` is set, the application will automatically connect to your database, build the tables, and lock the installer!
