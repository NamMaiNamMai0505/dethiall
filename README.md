# Training Management System (Quản lý Đào Tạo)

A comprehensive training management system built with Laravel for managing instructors, classes, lớp, and training schedules.

## 🚀 Quick Start

### Prerequisites
- PHP 8.1 or higher
- Composer
- MySQL/MariaDB
- Node.js & npm (for frontend assets)

### Easy Setup (Recommended)

**For Linux/macOS:**
```bash
# One-time setup
./setup.sh

# Start the application
./start.sh
```

**For Windows:**
```batch
# One-time setup
setup.bat

# Start the application
php artisan serve
```

### Manual Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd quan_ly_huan_luyen_vien
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database configuration**
   Edit your `.env` file with your database credentials:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=quan_ly_huan_luyen
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

5. **Run migrations and seeders**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```
   
   Or use the convenient command:
   ```bash
   php artisan db:refresh-with-seeders
   ```

6. **Build frontend assets**
   ```bash
   npm run dev
   ```

### Starting the Application

1. **Start the Laravel development server**
   ```bash
   php artisan serve
   ```
   The application will be available at `http://localhost:8000`

2. **For development with hot reload**
   ```bash
   npm run dev
   ```

3. **For production build**
   ```bash
   npm run build
   ```

## 🎯 Quick Commands

| Command | Description |
|---------|-------------|
| `./setup.sh` | Complete setup (Linux/macOS) |
| `setup.bat` | Complete setup (Windows) |
| `./start.sh` | Start development server (Linux/macOS) |
| `php artisan serve` | Start development server |
| `php artisan db:refresh-with-seeders` | Reset database with sample data |
| `php artisan db:stats` | View database statistics |
| `php artisan migrate:status` | View migration status |

### NPM Scripts

| Command | Description |
|---------|-------------|
| `npm run dev` | Start Vite dev server with hot reload |
| `npm run build` | Build assets for production |
| `npm run setup` | Setup database and build assets |
| `npm run fresh` | Fresh database with seeders and build |
| `npm run start` | Start Laravel development server |
| `npm run stats` | Show database statistics |

## 🔐 Default Login Credentials

**Admin User:**
- Email: `admin@example.com`
- Password: `it@Cdhc2`

**Sample Users:**
- `nguyenvana@example.com` / `it@Cdhc2`
- `tranthib@example.com` / `it@Cdhc2`
- `levanc@example.com` / `it@Cdhc2`

## 📊 Database Overview

The system includes the following main entities:
- **Users** (2sample users)
- **Units** (7 organizational units)
- **Specializations** (5 training specializations)
- **Instructors** (8 sample instructors)
- **Classes** (7 training classes)

### View Database Statistics
```bash
php artisan db:stats
```

## 🛠️ Useful Commands

```bash
# Refresh database with sample data
php artisan db:refresh-with-seeders

# View database statistics
php artisan db:stats

# Run individual seeder
php artisan db:seed --class=UserSeeder
php artisan migrate --path=database/migrations/2025_11_02_125102_update_subjects_table_code_unique_with_soft_delete.php

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear

```

## 📂 Project Structure

```
quan_ly_huan_luyen_vien/
├── app/
├── modules/                 # Modular structure
│   ├── Authentication/
│   ├── Class/
│   ├── Dashboard/
│   ├── Home/
│   ├── Instructor/
│   ├── Specialization/
│   ├── TrainingSchedule/
│   └── Unit/
├── database/
│   ├── migrations/
│   └── seeders/
├── resources/
├── routes/
└── ...
```

## 📖 About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
