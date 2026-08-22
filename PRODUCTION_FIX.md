# Fix Lỗi Backup trên Production

## Vấn đề

```
The backup destination cannot be reached.
InvalidArgumentException: Credentials must be an instance of 'Aws\Credentials\CredentialsInterface
```

**Nguyên nhân**: Trên production vẫn còn file cũ `app/Providers/ViettelS3ServiceProvider.php` đang conflict với package mới `kaibatech/viettel-cloud-s3`.

## Giải pháp: Xóa Provider Cũ

### Bước 1: SSH vào Production Server

```bash
# Kết nối qua Tailscale
ssh user@production-server
```

### Bước 2: Xóa Provider Cũ

```bash
# CD vào thư mục project
cd /path/to/qlhl

# Xóa file provider cũ
rm -f app/Providers/ViettelS3ServiceProvider.php

# Hoặc nếu đang trong Docker container
docker compose exec qlhl rm -f app/Providers/ViettelS3ServiceProvider.php
```

### Bước 3: Xóa Provider từ bootstrap/providers.php (nếu còn)

Kiểm tra file `bootstrap/providers.php`:

```bash
docker compose exec qlhl cat bootstrap/providers.php
```

**Nếu thấy dòng này, XÓA đi**:
```php
App\Providers\ViettelS3ServiceProvider::class,  // <- XÓA DÒNG NÀY
```

**Chỉ giữ lại**:
```php
Kaibatech\ViettelCloudS3\ViettelCloudS3ServiceProvider::class,  // <- GIỮ LẠI
```

### Bước 4: Clear Cache

```bash
docker compose exec qlhl php artisan config:clear
docker compose exec qlhl php artisan cache:clear
docker compose exec qlhl php artisan optimize:clear
```

### Bước 5: Kiểm tra .env có đủ biến Viettel S3

```bash
docker compose exec qlhl cat .env | grep VIETTEL_S3
```

**Phải có đầy đủ**:
```env
VIETTEL_S3_KEY=atm279507-s3user
VIETTEL_S3_SECRET=your-secret-key-here
VIETTEL_S3_REGION=us-east-1
VIETTEL_S3_BUCKET=data.qlhl
VIETTEL_S3_ENDPOINT=https://atm279507-s3user.vcos.cloudstorage.com.vn
VIETTEL_S3_URL=https://atm279507-s3user.vcos.cloudstorage.com.vn/data.qlhl
```

**Nếu thiếu, thêm vào file .env trên production**.

### Bước 6: Restart Scheduler Container

```bash
docker compose restart qlhl-scheduler
```

### Bước 7: Test Backup

```bash
# Test backup thủ công
docker compose exec qlhl php artisan backup:run --only-db --only-to-disk=s3

# Kiểm tra logs
docker compose logs -f qlhl-scheduler
```

## Nếu vẫn lỗi: Pull code mới từ Git

Nếu các bước trên không giải quyết được, nghĩa là code trên production chưa được update:

```bash
# Pull code mới nhất
cd /path/to/qlhl
git pull origin main

# Rebuild Docker image với code mới
docker compose down
docker compose build --no-cache qlhl
docker compose up -d

# Clear cache
docker compose exec qlhl php artisan optimize:clear

# Restart scheduler
docker compose restart qlhl-scheduler
```

## Verify

```bash
# 1. Kiểm tra provider đang dùng
docker compose exec qlhl php artisan about

# 2. Test S3 connection
docker compose exec qlhl php artisan tinker
>>> Storage::disk('s3')->exists('test');
>>> Storage::disk('s3')->put('test/hello.txt', 'Hello Viettel Cloud!');
>>> Storage::disk('s3')->get('test/hello.txt');
>>> exit

# 3. Test backup
docker compose exec qlhl php artisan backup:run --only-db --only-to-disk=s3

# 4. Kiểm tra trên Viettel Cloud Portal
# https://vcos.cloudstorage.com.vn
# Bucket: data.qlhl → Folder: laravel-backup
```

## Checklist

- [ ] Xóa `app/Providers/ViettelS3ServiceProvider.php`
- [ ] Xóa dòng `App\Providers\ViettelS3ServiceProvider::class` trong `bootstrap/providers.php`
- [ ] Giữ lại `Kaibatech\ViettelCloudS3\ViettelCloudS3ServiceProvider::class`
- [ ] Kiểm tra `.env` có đủ biến `VIETTEL_S3_*`
- [ ] Clear cache Laravel
- [ ] Restart scheduler container
- [ ] Test backup thủ công
- [ ] Verify file backup trên Viettel Cloud Portal

## Timeline Fix

1. **Immediate** (5 phút): Xóa provider cũ, clear cache, restart
2. **If needed** (10 phút): Pull code mới, rebuild Docker
3. **Verify** (2 phút): Test backup và check Viettel Cloud Portal
