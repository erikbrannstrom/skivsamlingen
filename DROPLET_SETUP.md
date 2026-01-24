# Digital Ocean Droplet Setup (1GB Memory)

This guide sets up an Ubuntu server to run CodeIgniter (PHP 5.6) and Laravel (PHP 8.3) simultaneously with Nginx, as required by the incremental migration strategy.

## 1. Create the Droplet

- **Image**: Ubuntu 22.04 LTS
- **Plan**: Basic, 1GB RAM
- **Region**: Choose closest to your users
- **Authentication**: SSH key

## 2. Initial Server Setup

```bash
# Connect as root
ssh root@your_droplet_ip

# Update system
apt update && apt upgrade -y

# Create non-root user for application management
adduser erikbrannstrom
usermod -aG sudo erikbrannstrom

# Copy SSH key to erikbrannstrom user
mkdir -p /home/erikbrannstrom/.ssh
cp ~/.ssh/authorized_keys /home/erikbrannstrom/.ssh/
chown -R erikbrannstrom:erikbrannstrom /home/erikbrannstrom/.ssh
chmod 700 /home/erikbrannstrom/.ssh
chmod 600 /home/erikbrannstrom/.ssh/authorized_keys
```

## 3. Add Swap (Important for 1GB Droplet)

A 1GB Droplet needs swap to handle memory spikes:

```bash
fallocate -l 1G /swapfile
chmod 600 /swapfile
mkswap /swapfile
swapon /swapfile
echo '/swapfile none swap sw 0 0' >> /etc/fstab

# Verify
free -h
```

## 4. Install Nginx

```bash
apt install nginx -y
systemctl enable nginx
```

## 5. Install Dual PHP Versions

```bash
apt install software-properties-common -y
add-apt-repository ppa:ondrej/php
apt update

# PHP 8.3 for Laravel
apt install php8.3-fpm php8.3-mysql php8.3-xml php8.3-mbstring \
    php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath -y

# PHP 5.6 for CodeIgniter
apt install php5.6-fpm php5.6-mysql php5.6-xml php5.6-mbstring \
    php5.6-curl php5.6-gd -y
```

## 6. Verify PHP-FPM Services

```bash
systemctl status php5.6-fpm
systemctl status php8.3-fpm
ls -la /var/run/php/
```

## 7. Install Composer

```bash
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
```

## 8. Install MySQL

```bash
apt install mysql-server -y
mysql_secure_installation
```

**Configure authentication for PHP 5.6 compatibility:**

MySQL 8.0 uses `caching_sha2_password` by default, which PHP 5.6 doesn't support. Change the default to `mysql_native_password`:

```bash
nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

Add under the `[mysqld]` section:

```ini
[mysqld]
default_authentication_plugin = mysql_native_password
```

Restart MySQL:

```bash
systemctl restart mysql
```

**Create database and user:**

```bash
mysql -u root -p
```
```sql
CREATE DATABASE skivsamlingen_s CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'skivsamlingen'@'localhost' IDENTIFIED WITH mysql_native_password BY 'your_secure_password';
GRANT ALL PRIVILEGES ON skivsamlingen_s.* TO 'skivsamlingen'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## 9. Set Up Directory Structure

```bash
mkdir -p /var/www/skivsamlingen.se
chown -R erikbrannstrom:www-data /var/www/skivsamlingen.se
chmod -R 775 /var/www/skivsamlingen.se
```

## 10. Configure Nginx

```bash
nano /etc/nginx/sites-available/skivsamlingen.conf
```

Add the nginx config from `LARAVEL_MIGRATION_PLAN.md` section 0.4, then:

```bash
ln -s /etc/nginx/sites-available/skivsamlingen.conf /etc/nginx/sites-enabled/
rm /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx
```

## 11. Firewall

```bash
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw enable
```

## 12. SSL with Let's Encrypt

Install Certbot and obtain SSL certificates:

```bash
apt install certbot python3-certbot-nginx -y

# Obtain certificate (replace with your domain)
certbot --nginx -d skivsamlingen.se -d www.skivsamlingen.se
```

Certbot will:
- Obtain the certificate
- Automatically configure Nginx for HTTPS
- Set up HTTP to HTTPS redirects

**Verify auto-renewal:**
```bash
certbot renew --dry-run
```

Certificates auto-renew via a systemd timer. Check its status:
```bash
systemctl status certbot.timer
```

**Manual renewal (if needed):**
```bash
certbot renew
systemctl reload nginx
```

## 13. Database Migration (MySQL 5.7 to 8.0)

Migrate the database from the old server (Ubuntu 18, MySQL 5.7) to the new server (Ubuntu 22, MySQL 8.0).

### On the Old Server (MySQL 5.7)

```bash
# Export the database
mysqldump -u root -p \
  --single-transaction \
  --routines \
  --triggers \
  --set-gtid-purged=OFF \
  skivsamlingen_s > skivsamlingen_backup.sql

# Compress the dump
gzip skivsamlingen_backup.sql
```

### Transfer to New Server

```bash
# From your local machine or old server
scp skivsamlingen_backup.sql.gz erikbrannstrom@new_droplet_ip:/tmp/
```

### On the New Server (MySQL 8.0)

```bash
# Decompress
gunzip /tmp/skivsamlingen_backup.sql.gz

# Import the database
mysql -u root -p skivsamlingen_s < /tmp/skivsamlingen_backup.sql

# Verify import
mysql -u root -p -e "USE skivsamlingen_s; SHOW TABLES;"

# Clean up
rm /tmp/skivsamlingen_backup.sql
```

### MySQL 5.7 to 8.0 Compatibility Notes

**Character set:** MySQL 8.0 defaults to `utf8mb4`. The database was created with this charset so no changes should be needed.

**SQL modes:** MySQL 8.0 has stricter defaults. If you encounter errors, check the SQL mode:

```sql
SELECT @@sql_mode;
```

To temporarily relax strict mode (if needed for legacy data):

```sql
SET GLOBAL sql_mode = 'NO_ENGINE_SUBSTITUTION';
```

Or permanently in `/etc/mysql/mysql.conf.d/mysqld.cnf`:

```ini
[mysqld]
sql_mode = NO_ENGINE_SUBSTITUTION
```

Then restart MySQL:

```bash
systemctl restart mysql
```

---

## Using the erikbrannstrom User

The `erikbrannstrom` user separates application management from system administration. Root handles system-level tasks; erikbrannstrom handles code and application tasks.

### When to Use Root

```bash
ssh root@your_droplet_ip

# System updates
apt update && apt upgrade -y

# Install packages
apt install some-package

# Edit Nginx config
nano /etc/nginx/sites-available/skivsamlingen.conf
nginx -t
systemctl reload nginx

# Edit PHP config
nano /etc/php/8.3/fpm/pool.d/www.conf
systemctl restart php8.3-fpm

# View system logs
journalctl -u nginx
```

### When to Use erikbrannstrom

```bash
ssh erikbrannstrom@your_droplet_ip

# Deploy code
cd /var/www/skivsamlingen.se
git pull origin master

# Install Laravel dependencies
cd /var/www/skivsamlingen.se/laravel
composer install --no-dev --optimize-autoloader

# Run Laravel commands
php artisan migrate
php artisan cache:clear
php artisan config:cache
php artisan route:cache

# View application logs
tail -f /var/www/skivsamlingen.se/laravel/storage/logs/laravel.log

# Edit application config
nano /var/www/skivsamlingen.se/laravel/.env
```

### Example Deployment Workflow

```bash
# 1. SSH as non-root user
ssh erikbrannstrom@your_droplet_ip

# 2. Pull latest code
cd /var/www/skivsamlingen.se
git pull origin master

# 3. Update Laravel
cd laravel
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. If nginx config changed, switch to root
sudo nano /etc/nginx/sites-available/skivsamlingen.conf
sudo nginx -t
sudo systemctl reload nginx
```

### Task Summary

| Task | User |
|------|------|
| System updates, package installation | root |
| Nginx/PHP configuration | root |
| Service management (systemctl) | root |
| Deploying application code | erikbrannstrom |
| Running composer/artisan | erikbrannstrom |
| Viewing application logs | erikbrannstrom |
| Editing .env files | erikbrannstrom |
