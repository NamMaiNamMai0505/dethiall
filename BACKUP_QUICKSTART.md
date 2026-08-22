# Backup System - Quick Start Guide

## 🚀 Quick Setup (3 Steps)

### Step 1: Install Dependencies
```bash
composer install
```

### Step 2: Configure Environment
Add to your `.env` file:
```env
# Backup Notifications
BACKUP_NOTIFICATIONS_MAIL=hoangtuyenblogger@gmail.com,thotruong1976@gmail.com

# SMTP Configuration (if not already configured)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-gmail-app-password
MAIL_ENCRYPTION=tls
```

### Step 3: Deploy Scheduler Service
On your production server at `~/apps/infras`, edit `docker-compose.yml` and add:

```yaml
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
```

Then deploy:
```bash
cd ~/apps/infras
docker compose up -d qlhl-scheduler
docker compose ps qlhl-scheduler
```

---

## ✅ What's Configured

| Feature | Status | Details |
|---------|--------|---------|
| **Daily DB Backup** | ✅ Configured | Runs at 2:00 AM |
| **Weekly Full Backup** | ✅ Configured | Sundays at 3:00 AM |
| **Auto Cleanup** | ✅ Configured | Removes backups older than 7 days |
| **Email Notifications** | ✅ Configured | Sent to 2 recipients |
| **S3 Support** | ✅ Ready | Just add AWS credentials |

---

## 🧪 Test the Backup System

### Test Backup Manually
```bash
# Run database backup
docker compose exec qlhl php artisan backup:run --only-db

# List all backups
docker compose exec qlhl php artisan backup:list

# Check backup health
docker compose exec qlhl php artisan backup:monitor
```

### Test Email Notifications
```bash
docker compose exec qlhl php artisan backup:run --only-db
# Check your email: hoangtuyenblogger@gmail.com, thotruong1976@gmail.com
```

### Check Scheduler is Running
```bash
docker compose logs -f qlhl-scheduler
```

---

## 📍 Backup Location

Backups are stored in:
```
storage/app/backups/
├── qlhl-backup-2025-11-14-020000.zip
├── qlhl-backup-2025-11-15-020000.zip
└── ...
```

---

## 📧 Gmail Setup (If Using Gmail)

1. Enable 2-Factor Authentication on your Google Account
2. Go to: https://myaccount.google.com/apppasswords
3. Create an "App Password" for "Mail"
4. Use the generated 16-character password in `.env` as `MAIL_PASSWORD`

---

## 📚 Full Documentation

See [BACKUP_SETUP.md](BACKUP_SETUP.md) for:
- Detailed configuration options
- Troubleshooting guide
- Restore procedures
- S3 configuration
- Security best practices

---

## 🆘 Quick Troubleshooting

**Scheduler not running?**
```bash
docker compose restart qlhl-scheduler
docker compose logs qlhl-scheduler
```

**Backup failed?**
```bash
docker compose exec qlhl php artisan backup:run --only-db
# Check the error output
```

**Emails not sending?**
- Check SMTP credentials in `.env`
- For Gmail, use App Password (not regular password)
- Check logs: `docker compose logs qlhl`

---

## ✨ Files Modified/Created

| File | Status | Description |
|------|--------|-------------|
| `composer.json` | ✅ Modified | Added `spatie/laravel-backup` package |
| `bootstrap/app.php` | ✅ Modified | Added backup schedule configuration |
| `config/backup.php` | ✅ Created | Backup package configuration |
| `.env.example` | ✅ Modified | Added backup environment variables |
| `docker-compose.scheduler.yml` | ✅ Created | Scheduler service example |
| `BACKUP_SETUP.md` | ✅ Created | Complete documentation |
| `BACKUP_QUICKSTART.md` | ✅ Created | This quick start guide |

---

**Next:** Run `composer install` and deploy the scheduler service! 🎉
