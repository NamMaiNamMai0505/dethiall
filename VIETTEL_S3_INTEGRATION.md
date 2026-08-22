# Tích hợp Viettel Cloud S3 cho Backup


Tích hợp với **Viettel Cloud Object Storage** sử dụng package `kaibatech/viettel-cloud-s3`.

### Package sử dụng
- **Backup**: `spatie/laravel-backup` ^9.1
- **Storage**: `kaibatech/viettel-cloud-s3` ^1.0 (thay thế `league/flysystem-aws-s3-v3`)

---
### full document https://github.com/kaibatech/viettel-cloud-s3
## Cấu hình

### 1. Environment Variables (.env)

Thêm các biến sau vào file `.env` của bạn:

```env
# Viettel Cloud S3 Configuration
VIETTEL_S3_KEY=atm279507-s3user
VIETTEL_S3_SECRET=Rvel5mkxjm++4M0GhuNFUilysAV3PB01K/1b5PRg
VIETTEL_S3_REGION=us-east-1
VIETTEL_S3_BUCKET=data.qlhl
VIETTEL_S3_ENDPOINT=https://vcos.cloudstorage.com.vn
VIETTEL_S3_URL=https://atm279507-s3user.vcos.cloudstorage.com.vn/data.qlhl


```

### 2. Lấy Credentials từ Viettel Cloud
   - **Access Key**
   - **Secret Key**:
   - **Endpoint**

---

## Cài đặt ở dev env

### Cài đặt Dependencies

```bash
# Local (nếu có composer)
composer update
```

### Deploy Scheduler Service
Thêm vào `docker-compose.yml`:

```yaml
  qlhl-scheduler:
    image: qlhl-cdhc2:0.1
    command: ["php", "artisan", "schedule:work"]
    depends_on:
      mariadb:
        condition: service_healthy
    volumes:
      - ./.env:/var/www/.env
    environment:
      - APP_ENV=production
    restart: unless-stopped
```

Deploy:
```bash
docker compose up -d qlhl-scheduler
docker compose logs -f qlhl-scheduler
```

---

## Test Backup

### Test Manual Backup

```bash
# Backup database to Viettel S3
docker compose exec qlhl php artisan backup:run --only-db --only-to-disk=s3

# Backup to both local and S3
docker compose exec qlhl php artisan backup:run --only-db

# List all backups
docker compose exec qlhl php artisan backup:list

# Check backup health
docker compose exec qlhl php artisan backup:monitor
```


## Schedule Backup Tự động

Các backup sẽ chạy tự động theo lịch:

| Thời gian | Task | Destination |
|-----------|------|-------------|
| 02:00 AM | Database backup | Viettel S3 |
| 03:00 AM | Full backup (Chủ nhật) | Viettel S3 |
| 04:00 AM | Cleanup old backups | Both |
| 05:00 AM | Health monitor | Both |

---

## Cấu trúc File trên Viettel S3

```
s3://data.qlhl/
└── laravel-backup/
    ├── qlhl-backup-2025-11-15-020000.zip
    ├── qlhl-backup-2025-11-16-020000.zip
    └── qlhl-backup-2025-11-17-030000.zip  (full backup)
```

---

## Email Notifications

Thông báo sẽ được gửi tới:
- ✉️ hoangtuyenblogger@gmail.com
- ✉️ thotruong1976@gmail.com

Khi:
- ✅ Backup thành công
- ❌ Backup thất bại
- ⚠️ Backup không healthy
- 🧹 Cleanup hoàn thành

---

## Backup Retention Policy

- **7 ngày đầu**: Giữ tất cả backups
- **16 ngày tiếp**: Giữ 1 backup/ngày
- **8 tuần tiếp**: Giữ 1 backup/tuần
- **4 tháng tiếp**: Giữ 1 backup/tháng
- **2 năm**: Giữ 1 backup/năm
- **Max storage**: 5000 MB (tự động xóa backup cũ nhất khi vượt quá)

---

## Troubleshooting

### Lỗi: Could not connect to disk s3

**Nguyên nhân**: Credentials không đúng hoặc endpoint sai

**Giải pháp**:
```bash
# Kiểm tra config
docker compose exec qlhl php artisan config:clear
docker compose exec qlhl php artisan config:cache

# Kiểm tra .env
docker compose exec qlhl cat .env | grep VIETTEL_S3
```

### Lỗi: SignatureDoesNotMatch

**Nguyên nhân**: Secret key không đúng

**Giải pháp**:
- Kiểm tra lại Secret Key từ Viettel Cloud Portal
- Đảm bảo không có khoảng trắng trong .env

### Lỗi: Access Denied

**Nguyên nhân**: User không có quyền với bucket

**Giải pháp**:
- Vào Viettel Cloud Portal
- Kiểm tra user `atm279507-s3user` có quyền read/write trên bucket `data.qlhl`

### Lỗi: mysqldump not found (Windows Local)

**Nguyên nhân**: Windows PATH không bao gồm mysqldump từ Laragon

**Lỗi hiển thị**:
```
'"mysqldump"' is not recognized as an internal or external command
Exitcode: 255: Unknown error
```

**Giải pháp**:

1. Thêm vào file `.env` của bạn:
```env
DB_DUMP_PATH=C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin
```

2. Hoặc thêm mysqldump vào System PATH:
   - Windows Settings → System → About → Advanced system settings
   - Environment Variables → System variables → Path
   - Thêm: `C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin`

3. Test lại backup:
```bash
php artisan backup:run --only-db
```

**Lưu ý**: Trên production (Docker/Linux), không cần `DB_DUMP_PATH` vì mysqldump đã được cài đặt sẵn qua `mysql-client` package.

---

## So sánh: AWS S3 vs Viettel Cloud S3

| Feature | AWS S3 | Viettel Cloud S3 |
|---------|--------|------------------|
| **Package** | `league/flysystem-aws-s3-v3` | `kaibatech/viettel-cloud-s3` |
| **Signature** | v4 | v4 (compatible) |
| **Endpoint** | `s3.amazonaws.com` | `vcos.cloudstorage.com.vn` |
| **Region** | Nhiều regions | `us-east-1` (virtual) |
| **Pricing** | USD | VND |
| **Support** | Global | Vietnam |

---

## Commands Hữu ích

```bash
# Backup ngay lập tức
docker compose exec qlhl php artisan backup:run --only-db --only-to-disk=s3

# List backups (cả local và S3)
docker compose exec qlhl php artisan backup:list

# Xem scheduled tasks
docker compose exec qlhl php artisan schedule:list

# Xem scheduler logs
docker compose logs -f qlhl-scheduler

# Test S3 connection
docker compose exec qlhl php artisan tinker
>>> Storage::disk('s3')->exists('laravel-backup')
>>> Storage::disk('s3')->files('laravel-backup')
```

---

## Restore từ Backup

### Download từ Viettel S3

```bash
# Vào Viettel Cloud Portal
# Download file backup về local

# Hoặc dùng command
docker compose exec qlhl php -r "
  Storage::disk('s3')->download(
    'laravel-backup/qlhl-backup-2025-11-15-020000.zip',
    '/tmp/backup.zip'
  );
"
```

### Restore Database

```bash
# Extract backup
unzip backup.zip

# Restore
docker compose exec -T mariadb mysql -u root -p eduka_v2 < db-dumps/mysql-eduka_v2.sql
```

---

## Files Modified

| File | Change |
|------|--------|
| `composer.json` | Thay `league/flysystem-aws-s3-v3` → `kaibatech/viettel-cloud-s3` |
| `config/filesystems.php` | Đổi driver `s3` → `viettel-s3`, update env vars |
| `.env.example` | Thêm `VIETTEL_S3_*` variables |
| `VIETTEL_S3_INTEGRATION.md` | Tài liệu này |

---

**Tích hợp hoàn tất!** ✅

Package `kaibatech/viettel-cloud-s3` sẽ tự động xử lý signature và endpoint cho Viettel Cloud.
