<p align="center">
  <a href="https://linanok.com" target="_blank">
    <img src="logo.svg" width="300" alt="Linanok Logo">
  </a>
</p>

# Linanok

[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/licenses/MIT)
[![Docker](https://img.shields.io/badge/Docker-Ready-green.svg)](https://github.com/orgs/Linanok/packages)
[![Laravel](https://img.shields.io/badge/Laravel-12.x-green.svg)](https://laravel.com/)

Linanok is a professional URL shortening application designed specifically for organizations and companies. It provides
a robust platform for managing and tracking shortened URLs at an enterprise level, helping businesses maintain their
brand identity while sharing concise, memorable links.

## 📋 Table of Contents

- [Features](#-features)
- [Why Linanok?](#-why-linanok)
- [Compatibility](#-compatibility)
- [Quick Start](#-quick-start)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Usage](#-usage)
- [Contributing](#-contributing)
- [Testing](#-testing)
- [Security](#-security)
- [License](#-license)
- [Support](#-support)

## ✨ Features

### 🔗 URL Management

- **Centralized Dashboard**: Manage all your shortened URLs from one place
- **Multiple Domain Support**: Use multiple branded domains for different departments
- **Link Expiration & Scheduling**: Set expiration dates and schedule link activation
- **Password Protection**: Secure sensitive links with password protection
- **URL Tagging**: Organize links with custom tags
- **Deep Link Support**: Create nested URL structures like `a/b/c/d` for organized link hierarchies
- **Import/Export Functionality**: Easily import existing URLs from CSV/Excel files and export your link data for backup or migration purposes

### 📊 Analytics & Tracking

- **Detailed Visit Statistics**: Comprehensive analytics on link performance
- **Visitor Location Tracking**: Geographic insights into your audience
- **Device & Browser Analytics**: Understand how users access your links

### 👥 Team & Access Control

- **Role-Based Access Control (RBAC)**: Granular permissions for team members
- **Department Groups**: Organize users by departments or teams
- **Domain Ownership Management**: Assign domains to specific teams
- **User Role Management**: Flexible role assignment and management
- **Activity Logging**: Track all user actions and changes

## 🚀 Why Linanok?

Unlike typical URL shorteners designed for individual use, **Linanok** is purpose-built for organizations and teams that
need advanced management, security, and organizational features.

### Key Differentiators

- **🏢 Enterprise-Grade Access Control**: Fine-grained RBAC and permissions management
- **🌐 Multiple Domain Management**: Maintain brand consistency across departments
- **📈 Advanced Analytics**: Actionable insights for organizational decision-making
- **🔒 Enhanced Security**: Password protection, expiration, and scheduling features
- **🏷️ Custom Tagging**: Scale-friendly link organization
- **👥 Organization-Focused Design**: Built for team collaboration and enterprise workflows

## 🧩 Compatibility

- **PHP**: 8.3, 8.4
- **Databases (CI-tested)**:
    - PostgreSQL: 13, 14, 15, 16, 17, 18
    - MariaDB: 10.6, 10.11, 11.4, 11.8
    - SQLite

### ⚠️ Windows Limitations

**Laravel Horizon is not supported on Windows** due to its dependency on Unix-specific process control features. Windows
users should use the standard Laravel queue worker instead:

```bash
php artisan queue:work
```

**PCNTL and POSIX extensions are not supported on Windows**. These extensions are required for:

- Process control and signal handling
- Advanced queue management features
- Some background job processing capabilities

For Windows development, consider using one of these options:

- Docker with WSL2 (recommended)
- Standard queue workers instead of Horizon

## ⚡ Quick Start

The fastest way to get Linanok running:

Visit [github.com/linanok/linanok-platform](https://github.com/linanok/linanok-platform) for the quickest deployment
using our prebuilt Docker images.

## 📦 Installation

### Prerequisites

- **Docker** (version 20.10 or higher)
- **Docker Compose** (version 2.0 or higher)
- **Git** (for cloning the repository)

### Docker Installation (Recommended)

1. **Clone the repository:**
   ```bash
   git clone https://github.com/linanok/linanok.git
   cd linanok
   ```

2. **Create environment file:**
   ```bash
   cp .env.docker.example .env
   ```

3. **Configure environment variables:**
   Edit the `.env` file with your specific settings:
   ```env
   APP_NAME=Linanok
   APP_ENV=production
   APP_KEY=base64:aP28hHSMnZ5BDSzG1N2N4A3swRcM7+HYugXSLXoJIHc=
   APP_DEBUG=false
   APP_TIMEZONE=UTC
   APP_URL=http://localhost:8000

   OCTANE_WORKERS=8
   QUEUE_WORKER_MAX_PROCESSES=4

   TRUSTED_PROXIES=*

   APP_MAINTENANCE_DRIVER=file

   BCRYPT_ROUNDS=12

   LOG_CHANNEL=stack
   LOG_STACK=single
   LOG_DEPRECATIONS_CHANNEL=null
   LOG_LEVEL=debug

   DB_CONNECTION=pgsql
   DB_HOST=postgres
   DB_PORT=5432
   DB_DATABASE=linanok
   DB_USERNAME=postgres
   DB_PASSWORD=postgres

   SESSION_DRIVER=redis
   SESSION_LIFETIME=120
   SESSION_ENCRYPT=false
   SESSION_PATH=/
   SESSION_DOMAIN=null
 
   FILESYSTEM_DISK=local
   QUEUE_CONNECTION=redis

   CACHE_STORE=redis
   CACHE_PREFIX=

   REDIS_CLIENT=predis
   REDIS_HOST=redis
   REDIS_PASSWORD=redis
   REDIS_PORT=6379
   REDIS_DB=0
   REDIS_CACHE_DB=1
   REDIS_CACHE_CONNECTION=cache

   VITE_APP_NAME="${APP_NAME}"
   ```

4. **Generate application key:**
   ```bash
   docker compose run --rm cli php artisan key:generate --show
   ```
   Copy the generated key and update it in the `.env` file.

5. **Start the application:**
   ```bash
   docker compose up -d
   ```

   This launches the `web`, `queue-worker`, `scheduler`, `postgres`, and `redis` services. The `scheduler` runs
   Laravel's scheduled tasks automatically every minute.

6. **Create a super admin user:**
   ```bash
   docker compose run --rm cli php artisan make:super-admin
   ```

The application will be available at `http://localhost:8000`

### Using the CLI Container

The CLI container allows you to run artisan commands and other PHP scripts without having to install PHP locally:

```bash
# Run any artisan command
docker compose run --rm cli php artisan migrate

# Access the container shell for multiple commands
docker compose run --rm cli bash

# Examples:
docker compose run --rm cli php artisan migrate
docker compose run --rm cli php artisan queue:work
docker compose run --rm cli php artisan tinker
```

### Manual Installation

If you prefer to install Linanok without Docker:

1. **Clone the repository:**
   ```bash
   git clone https://github.com/your-username/linanok.git
   cd linanok
   ```

2. **Install PHP dependencies:**
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

   **⚠️ Windows Users**: Laravel Horizon requires the `pcntl` and `posix` extensions which are not supported on Windows.
   If you're running `composer install` on Windows, use:
   ```bash
   composer install --no-dev --optimize-autoloader --ignore-platform-req=ext-pcntl --ignore-platform-req=ext-posix
   ```

   **Note**: This will allow you to install dependencies, but Laravel Horizon will not work on Windows. Use standard
   queue workers instead.

3. **Install Node.js dependencies:**
   ```bash
   npm install
   npm run build
   ```

4. **Set up your database:**
    - For PostgreSQL: Create a PostgreSQL database
    - For SQLite: No setup required (database file will be created automatically)
    - Update the `.env` file with your database credentials

5. **Configure environment:**
   ```bash
   cp .env.example .env
   # Edit .env with your settings
   php artisan key:generate
   ```

6. **Run database setup:**
   ```bash
   php artisan migrate
   php artisan db:seed --class=ProductionDatabaseSeeder
   ```

   **Note**: If you need pre-populated test data for development, you can use the DevelopmentDatabaseSeeder instead:
   ```bash
   php artisan db:seed --class=DevelopmentDatabaseSeeder
   ```

7. **Set up queue worker:**
   ```bash
   php artisan queue:work
   ```

   **Alternative**: You can also use Laravel Horizon for better queue management:
   ```bash
   php artisan horizon
   ```
   **Note**: Horizon only works with Redis as the queue driver.

   **⚠️ Windows Users**: Laravel Horizon is not supported on Windows due to missing PCNTL and POSIX extensions. Use the
   standard queue worker (`php artisan queue:work`) instead.

8. **Create a super admin user:**
   ```bash
   php artisan make:super-admin
   ```

9. **Start the development server:**
   ```bash
   php artisan serve
   ```

## ⚙️ Configuration

### Environment Variables

Key configuration options in your `.env` file:

- **Database**: Configure PostgreSQL, MariaDB, or SQLite connection settings
- **Redis**: Set up Redis for caching and queue management
- **Queue**: Set queue driver (Redis recommended for production)
- **Logging**: Configure log levels and storage

### Database Setup

Linanok supports PostgreSQL, MariaDB, and SQLite databases. PostgreSQL and MariaDB are recommended for production
environments, while SQLite is perfect for development and smaller deployments.

**For PostgreSQL**, ensure your database is properly configured with:

- UTF-8 encoding
- Proper user permissions
- Adequate connection limits

**For MariaDB**, ensure your database is properly configured with:

- UTF-8 (utf8mb4) encoding
- Proper user permissions
- Adequate connection limits

**For SQLite**, the database file will be automatically created in the `database/` directory.

### Optional: MaxMind GeoLite2 Database

Country-based analytics are optional. To enable them, set `MAXMIND_LICENSE_KEY` in your `.env`. Linanok will download
and use MaxMind's GeoLite2-Country database automatically. Without a license key, Linanok works normally but shows no
geolocation data, and related charts and statistics remain empty.

#### Getting a MaxMind License Key

1. **Create a MaxMind account**
   at [https://www.maxmind.com/en/geolite2/signup](https://www.maxmind.com/en/geolite2/signup)
2. **Generate a license key**
   at [https://www.maxmind.com/en/accounts/current/license-key](https://www.maxmind.com/en/accounts/current/license-key)
3. **Add the license key** to your `.env` file:
   ```env
   MAXMIND_LICENSE_KEY=your_license_key_here
   ```

#### Database Download Commands

```bash
# Download the database manually
php artisan maxmind:download

# Force download even if file is recent
php artisan maxmind:download --force

# Download in quiet mode (useful for cron jobs)
php artisan maxmind:download --quiet
```

#### Automatic Updates

The MaxMind database is automatically updated every week via Laravel's scheduler. To enable automatic updates:

**For Docker deployments**, the scheduler service runs automatically and handles all scheduled tasks including MaxMind
database updates.

**For manual installations**, add this to your crontab:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

The database file is stored at `storage/maxmind/GeoLite2-Country.mmdb` and will be automatically used by the application
for IP geolocation if present. If not present, the application continues to work normally without geolocation data.

### Redis Setup

Redis is required for caching, sessions, and queue management. Configure Redis in your `.env` file:

```env
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
```

For production, ensure Redis is properly secured and configured for your environment.

### Queue Configuration

For production environments, configure a proper queue driver:

```env
QUEUE_CONNECTION=redis
REDIS_HOST=your-redis-host
REDIS_PASSWORD=your-redis-password
```

**Note**: Laravel Horizon only works with Redis as the queue driver.

### Scheduler

Linanok uses Laravel's scheduler for recurring tasks (cleanups, data refreshes, etc.).

- Docker: The `scheduler` service runs automatically and invokes `php artisan schedule:run` every minute.
    - View logs: `docker compose logs -f scheduler`
    - Run once manually: `docker compose run --rm cli php artisan schedule:run`
- Manual installs: Set up a cron entry to run the scheduler every minute:
  ```bash
  * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
  ```
  On Windows without Docker, use Task Scheduler or run the command via WSL.

### Optimization Commands

For production deployments, run these optimization command:

```bash
php artisan optimize
```

To clear cached configurations:

```bash
php artisan optimize:clear
```

### File Permissions

Ensure proper file permissions:

```bash
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## 🎯 Usage

### Getting Started

1. **Access the Admin Panel**: Navigate to `http://localhost:8000/admin`
2. **Create Your First Domain**: Add a custom domain for your organization
3. **Create Links**: Start shortening URLs with your domain
4. **Set Up Teams**: Invite team members and assign roles
5. **Monitor Analytics**: Track link performance and visitor insights

### Key Workflows

- **Link Management**: Create, edit, and organize your shortened URLs
- **Domain Management**: Manage multiple branded domains
- **User Management**: Control access and permissions for team members
- **Analytics Review**: Monitor performance and visitor behavior

## 🤝 Contributing

We welcome contributions from the community! Here's how you can help:

### Development Setup

1. **Fork the repository**
2. **Clone your fork**: `git clone https://github.com/your-username/linanok.git`
3. **Install dependencies**: `composer install && npm install`
4. **Set up environment**: `cp .env.example .env`
5. **Run migrations**: `php artisan migrate --seed --seeder=DevelopmentDatabaseSeeder`
6. **Start development server**: `php artisan serve`

### Making Changes

1. **Create a feature branch**: `git checkout -b feature/your-feature-name`
2. **Make your changes** and add tests
3. **Run tests**: `php artisan test`
4. **Commit your changes**: `git commit -m "Add your feature description"`
5. **Push to your fork**: `git push origin feature/your-feature-name`
6. **Create a Pull Request**

### Code Style

- Follow Laravel Pint with Laravel preset for code formatting
- Write meaningful commit messages
- Add tests for new features
- Update documentation as needed

## 🧪 Testing

Run the test suite to ensure everything is working correctly:

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --filter=LinkTest

# Run with coverage (requires Xdebug)
php artisan test --coverage
```

## 🔐 Security

If you find a security issue in Linanok, please let us know by either:

* **Emailing us** at [security@linanok.com](mailto:security@linanok.com), or
* **Opening a security advisory**
  via [Linanok’s GitHub Security tab](https://github.com/linanok/linanok/security/advisories).

When reporting, please include details such as how to reproduce the problem, what you expected to happen, what actually
happened, and any proof-of-concept if possible. We ask that you do not disrupt services or access more data than needed
to demonstrate the issue.

We will confirm your report within 72 hours, review it, and address it based on its severity. If needed, we’ll
coordinate with you on when and how to disclose the details.

Thank you for helping keep Linanok safe.

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

- **Issues**: Report bugs and feature requests on [GitHub Issues](https://github.com/linanok/linanok/issues)
- **Discussions**: Join the conversation on [GitHub Discussions](https://github.com/linanok/linanok/discussions)

---

**Made with ❤️ for organizations that need professional URL management**
