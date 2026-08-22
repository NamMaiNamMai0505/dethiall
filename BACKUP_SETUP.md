# Database Backup Implementation Guide

This document explains the automated backup system implemented using Spatie Laravel Backup.

## Overview

The backup system provides:
- ✅ Automated daily database backups at 2 AM
- ✅ Weekly full backups (database + files) on Sundays at 3 AM
- ✅ Automatic cleanup of old backups (7+ days retention)
- ✅ Health monitoring and email notifications
- ✅ Support for local and S3 storage
- ✅ Email notifications to multiple recipients

---

## Installation Steps

### 1. Install Package Dependencies

On your **local development** or **server**, run:

```bash
composer install
```

This will install `spatie/laravel-backup` (v9.1) which was added to `composer.json`.

### 2. Configure Environment Variables

Add these variables to your `.env` file:

```env
# Backup Configuration
BACKUP_FILENAME_PREFIX=qlhl-backup-
BACKUP_ARCHIVE_PASSWORD=your-secure-password-here
BACKUP_NOTIFICATIONS_MAIL=hoangtuyenblogger@gmail.com,thotruong1976@gmail.com

# Mail Configuration (ensure SMTP is configured)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="Student Management System"

# Optional: AWS S3 for Remote Backups
AWS_ACCESS_KEY_ID=your-aws-key
AWS_SECRET_ACCESS_KEY=your-aws-secret
AWS_DEFAULT_REGION=ap-southeast-1
AWS_BUCKET=qlhl-backups
```

**Important Notes:**
- For **Gmail**: Use an App Password (not regular password). Enable 2FA and generate app password from Google Account settings.
- `BACKUP_ARCHIVE_PASSWORD`: Optional encryption for backup archives.
- S3 configuration is optional but recommended for production.

### 3. Update Docker Compose Configuration

#### On Your Production Server

Edit your `docker-compose.yml` file at `~/apps/infras/docker-compose.yml` and add the scheduler service:

```yaml
services:
  # Your existing qlhl service
  qlhl:
    image: ghcr.io/quan-ly-hoc-vien-cdhc2:latest
    # ... existing configuration ...

  # Add this new scheduler service
  qlhl-scheduler:
    image: ghcr.io/quan-ly-hoc-vien-cdhc2:latest
    container_name: qlhl-scheduler
    restart: unless-stopped
    command: php artisan schedule:work
    env_file:
      - .env
    volumes:
      - ./storage:/var/www/html/storage
      - ./bootstrap/cache:/var/www/html/bootstrap/cache
    networks:
      - app-network
    depends_on:
      - qlhl-db

  # Your existing database service
  qlhl-db:
    # ... existing configuration ...

networks:
  app-network:
    driver: bridge
```

#### Deploy the Scheduler Service

After updating `docker-compose.yml`, deploy the scheduler:

```bash
cd ~/apps/infras
docker compose up -d qlhl-scheduler
docker compose ps qlhl-scheduler
```

Verify it's running:

```bash
docker compose logs -f qlhl-scheduler
```

You should see output like:
```
INFO  Running schedule tasks every minute.
```

---

## Backup Schedule

The following tasks run automatically:

| Task | Schedule | Time | Description |
|------|----------|------|-------------|
| **Database Backup** | Daily | 2:00 AM | Backs up MySQL database only (faster) |
| **Full Backup** | Weekly (Sunday) | 3:00 AM | Database + application files |
| **Cleanup** | Daily | 4:00 AM | Removes backups older than retention policy |
| **Health Check** | Daily | 5:00 AM | Monitors backup health and sends alerts |

### Retention Policy

- Keep all backups for **7 days**
- Keep daily backups for **16 more days** (23 days total)
- Keep weekly backups for **8 weeks** (2 months)
- Keep monthly backups for **4 months**
- Keep yearly backups for **2 years**
- Delete oldest backups when storage exceeds **5000 MB**

---

## Manual Backup Commands

### Run Backups Manually

```bash
# Database only
php artisan backup:run --only-db

# Full backup (database + files)
php artisan backup:run

# Database only to S3
php artisan backup:run --only-db --only-to-disk=s3
```

### View Backup Status

```bash
# List all backups
php artisan backup:list

# Check backup health
php artisan backup:monitor

# Clean old backups manually
php artisan backup:clean
```

### Docker Commands

If you need to run commands inside the Docker container:

```bash
# Run backup inside container
docker compose exec qlhl php artisan backup:run --only-db

# List backups
docker compose exec qlhl php artisan backup:list

# View scheduler logs
docker compose logs -f qlhl-scheduler
```

---

## Backup Storage Locations

### Local Storage (Default)

Backups are stored in:
```
storage/app/backups/
```

Structure:
```
storage/app/backups/
├── qlhl-backup-2025-11-14-020000.zip
├── qlhl-backup-2025-11-15-020000.zip
└── qlhl-backup-2025-11-16-030000.zip
```

### AWS S3 Storage (Optional)

To enable S3 backups:

1. Configure AWS credentials in `.env` (see step 2 above)
2. Update `config/backup.php`:

```php
'destination' => [
    'disks' => [
        'local',
        's3',  // Add this line
    ],
],
```

3. Update monitoring:

```php
'monitor_backups' => [
    [
        'name' => env('APP_NAME', 'laravel-backup'),
        'disks' => ['local', 's3'],  // Add s3 here
        // ...
    ],
],
```

---

## Email Notifications

Notifications are sent to:
- hoangtuyenblogger@gmail.com
- thotruong1976@gmail.com

### Events That Trigger Emails

✅ **Success Notifications:**
- Backup completed successfully
- Cleanup completed successfully
- Healthy backup found

❌ **Failure Notifications:**
- Backup failed
- Cleanup failed
- Unhealthy backup detected

### Test Email Notifications

```bash
# Test if email is working
php artisan backup:run --only-db

# Check logs for email sending
tail -f storage/logs/laravel.log
```

---

## Troubleshooting

### Scheduler Not Running

**Check if scheduler service is running:**
```bash
docker compose ps qlhl-scheduler
```

**View scheduler logs:**
```bash
docker compose logs -f qlhl-scheduler
```

**Restart scheduler:**
```bash
docker compose restart qlhl-scheduler
```

### Backup Failed

**Check backup logs:**
```bash
docker compose exec qlhl php artisan backup:run --only-db
```

**Common issues:**
- Database credentials incorrect in `.env`
- Insufficient disk space
- MySQL `mysqldump` not available in container
- Permission issues on `storage/` directory

**Fix permissions:**
```bash
docker compose exec qlhl chmod -R 775 storage bootstrap/cache
```

### Email Not Sending

**Check SMTP configuration:**
```bash
# Test mail config
docker compose exec qlhl php artisan tinker
>>> Mail::raw('Test backup notification', function($msg) {
...     $msg->to('hoangtuyenblogger@gmail.com')->subject('Test');
... });
```

**Check mail logs:**
```bash
tail -f storage/logs/laravel.log | grep -i mail
```

### Backup Files Too Large

**Exclude unnecessary files in `config/backup.php`:**
```php
'exclude' => [
    base_path('vendor'),
    base_path('node_modules'),
    base_path('storage/framework'),
    base_path('storage/logs'),
    base_path('public/uploads'),  // Add large directories
],
```

**Use database-only backups:**
```bash
php artisan backup:run --only-db
```

---

## GitHub Actions Integration (Optional)

You can also trigger backups from GitHub Actions. Create `.github/workflows/backup.yml`:

```yaml
name: Manual Database Backup

on:
  workflow_dispatch:  # Manual trigger
  schedule:
    - cron: '0 14 * * *'  # Daily at 2 PM UTC (adjust for timezone)

jobs:
  backup:
    runs-on: ubuntu-latest
    steps:
      - name: Connect to Tailscale
        uses: tailscale/github-action@v4
        with:
          authkey: ${{ secrets.TAILSCALE_AUTHKEY }}
          tags: tag:ci

      - name: Run Backup on Server
        run: |
          ssh -o StrictHostKeyChecking=no cdhc2-itn@${{ secrets.DEST_IPv4 }} << 'EOF'
            cd ~/apps/infras
            docker compose exec -T qlhl php artisan backup:run --only-db
            docker compose exec -T qlhl php artisan backup:list
          EOF
```

---

## Monitoring and Maintenance

### Weekly Check (Recommended)

1. Verify backups exist:
   ```bash
   docker compose exec qlhl php artisan backup:list
   ```

2. Check backup health:
   ```bash
   docker compose exec qlhl php artisan backup:monitor
   ```

3. Review disk space:
   ```bash
   docker compose exec qlhl du -sh storage/app/backups/
   ```

### Monthly Check

1. Test restore process (on staging/test environment)
2. Verify email notifications are working
3. Check S3 storage if enabled
4. Review and adjust retention policy if needed

---

## Restore from Backup

### Local Restore

1. Download the backup file:
   ```bash
   docker cp qlhl:/var/www/html/storage/app/backups/qlhl-backup-2025-11-14-020000.zip ./
   ```

2. Extract the backup:
   ```bash
   unzip qlhl-backup-2025-11-14-020000.zip
   ```

3. Find the database dump:
   ```bash
   cd db-dumps/
   gunzip mysql-eduka_v2-*.sql.gz
   ```

4. Restore the database:
   ```bash
   docker compose exec -T qlhl-db mysql -u root -p eduka_v2 < mysql-eduka_v2-*.sql
   ```

### S3 Restore

If backups are stored on S3, download first:
```bash
aws s3 cp s3://qlhl-backups/qlhl-backup-2025-11-14-020000.zip ./
```

Then follow the local restore steps above.

---

## Security Best Practices

1. ✅ **Encrypt backups** using `BACKUP_ARCHIVE_PASSWORD`
2. ✅ **Use S3 with encryption** for remote storage
3. ✅ **Restrict access** to backup files (proper file permissions)
4. ✅ **Use IAM roles** for S3 access instead of access keys when possible
5. ✅ **Rotate passwords** regularly
6. ✅ **Test restore process** periodically

---

## Summary

✅ **Completed:**
- Spatie Laravel Backup package installed
- Backup schedule configured (daily database, weekly full)
- Email notifications configured for 2 recipients
- S3 support configured (ready to enable)
- Docker scheduler service configuration provided

🔧 **Next Steps:**
1. Run `composer install` to install the backup package
2. Update `.env` with backup configuration
3. Add scheduler service to production `docker-compose.yml`
4. Deploy and test backups

📧 **Support:**
- Documentation: https://spatie.be/docs/laravel-backup
- GitHub: https://github.com/spatie/laravel-backup
