# Product Catalog Management System

A modern Laravel + Vue.js application for managing product catalog imports via CSV files with background processing and real-time progress tracking.

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Technology Stack](#technology-stack)
- [Getting Started](#getting-started)
- [Step-by-Step Usage Guide](#step-by-step-usage-guide)
- [CSV File Format](#csv-file-format)
- [Project Structure](#project-structure)
- [Database Schema](#database-schema)
- [API Endpoints](#api-endpoints)
- [Configuration](#configuration)
- [Troubleshooting](#troubleshooting)
- [License](#license)

## Overview

This application enables authenticated users to upload and process large CSV files containing product information. Built with Laravel 12 and Vue 3, it features background job processing, real-time progress tracking, and comprehensive authentication including two-factor authentication (2FA).

The system is designed to handle bulk product imports from suppliers (e.g., SanMar promotional products), with robust error handling, UTF-8 encoding support, and automatic product updates.

## Features

### Authentication System
- **User Registration & Login** - Secure account creation with email/password
- **Password Reset** - Email-based password recovery
- **Two-Factor Authentication (2FA)** - Optional TOTP-based security with QR codes
- **Email Verification** - Optional email confirmation
- **Profile Management** - Update user details and settings

### CSV Upload & Processing
- **Drag & Drop Upload** - Modern file upload interface
- **Multiple File Support** - Upload multiple CSV files sequentially
- **Background Processing** - Queue-based job processing for large files
- **Real-Time Progress** - Live progress tracking with percentage updates
- **Error Handling** - Detailed error messages and recovery
- **UTF-8 Support** - Automatic BOM removal and character cleaning
- **UPSERT Logic** - Updates existing products, inserts new ones

### Product Management
- **Product Catalog** - Store product titles, descriptions, pricing, sizes, and colors
- **Automatic Updates** - Duplicate products are updated with new data
- **Data Validation** - Required field validation and data cleaning
- **Large Scale Support** - Handle millions of rows efficiently

## Technology Stack

- **Backend**: Laravel 12 (PHP 8.2+)
- **Frontend**: Vue 3 with TypeScript and Composition API
- **SPA Framework**: Inertia.js (server-side routing with Vue frontend)
- **UI Framework**: Tailwind CSS + [shadcn-vue](https://www.shadcn-vue.com) components
- **Database**: SQLite (default, configurable to MySQL/PostgreSQL)
- **Queue System**: Database-based queue for background processing
- **Authentication**: Laravel Fortify with 2FA support
- **Build Tool**: Vite for fast frontend compilation

## Getting Started

### Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js and npm
- SQLite (or MySQL/PostgreSQL)
- XAMPP or similar web server

### Installation

1. **Clone the repository** (or navigate to project directory):
   ```bash
   cd c:\xampp\htdocs\test-project
   ```

2. **Install PHP dependencies**:
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**:
   ```bash
   npm install
   ```

4. **Setup environment file**:
   ```bash
   copy .env.example .env
   php artisan key:generate
   ```

5. **Create database and run migrations**:
   ```bash
   php artisan migrate
   ```

6. **Build frontend assets**:
   ```bash
   npm run build
   ```

### Running the Application

#### Option 1: Start All Services (Recommended)
```bash
composer run dev
```

This single command starts:
- Laravel development server (`http://localhost:8000`)
- Queue worker (processes CSV uploads)
- Vite dev server (frontend hot reload)

#### Option 2: Start Services Individually

Open three separate terminal windows:

**Terminal 1 - Laravel Server**:
```bash
php artisan serve
```

**Terminal 2 - Queue Worker** (REQUIRED for CSV processing):
```bash
php artisan queue:listen --tries=1
```

**Terminal 3 - Frontend Dev Server**:
```bash
npm run dev
```

## Step-by-Step Usage Guide

### Step 1: Create a Demo Account

1. Open browser and navigate to `http://localhost:8000`
2. Click **"Register"** or go to `/register`
3. Fill in the registration form:
   - **Name**: Demo User
   - **Email**: demo@example.com
   - **Password**: password123
   - **Confirm Password**: password123
4. Click **"Register"** button
5. You'll be automatically logged in and redirected to `/dashboard`

### Step 2: Navigate to CSV Uploads

1. In the sidebar, click **"CSV Uploads"** (or navigate to `/uploads`)
2. You'll see the Uploads Management Page with:
   - Upload zone at the top
   - Table showing upload history

### Step 3: Upload a CSV File

#### Option A: Use the Provided Test File

The project includes a test CSV file: `yoprint_test_import.csv`

1. **Drag and drop** the file onto the upload zone

   **OR**

2. **Click the upload zone** and select the file from your file browser

#### Option B: Create Your Own CSV

Create a CSV file with the required headers (see [CSV File Format](#csv-file-format) section below).

### Step 4: Monitor Upload Progress

1. File uploads and appears in the table with status **"PENDING"**
2. Queue worker processes the file automatically
3. Status changes in real-time:
   - **PENDING** - File queued, waiting to be processed
   - **PROCESSING** - Currently processing with progress bar
   - **COMPLETED** - All rows successfully imported
   - **FAILED** - Error occurred (see error message)
4. Progress updates appear automatically every 2 seconds
5. Progress bar shows: "1,234 / 5,000 rows (24.68%)"

### Step 5: View Results

After upload completes:

1. Products are stored in the `products` table
2. Upload record shows final status in the table
3. Sort table by clicking column headers (Date or Filename)
4. Review processed rows count and total time

### Additional Features

#### Enable Two-Factor Authentication (Optional)

1. Go to **Settings** → **Two-Factor Authentication**
2. Click **"Enable"**
3. Scan QR code with authenticator app (Google Authenticator, Authy, etc.)
4. Enter verification code to confirm
5. Save recovery codes in a secure location
6. Next login will require 2FA code

#### Update Profile

Navigate to **Settings** menu:
- **Profile** - Update name and email
- **Password** - Change password
- **Appearance** - Customize theme settings
- **Two-Factor** - Manage 2FA settings

## CSV File Format

### Required Headers

Your CSV file must include these columns:

- **UNIQUE_KEY** (required) - Unique product identifier
- **PRODUCT_TITLE** (required) - Product name

### Optional Headers

- **PRODUCT_DESCRIPTION** - Detailed product description
- **STYLE#** - Style or SKU number
- **SANMAR_MAINFRAME_COLOR** - Manufacturer color code
- **SIZE** - Product size (S, M, L, XL, etc.)
- **COLOR_NAME** - Display color name
- **PIECE_PRICE** - Unit price (decimal format)

### Example CSV Format

```csv
UNIQUE_KEY,PRODUCT_TITLE,PRODUCT_DESCRIPTION,STYLE#,SANMAR_MAINFRAME_COLOR,SIZE,COLOR_NAME,PIECE_PRICE
PROD001,Test Product,A sample product description,STY123,BLUE,M,Navy Blue,19.99
PROD002,Another Product,Second sample product,STY124,RED,L,Crimson Red,24.99
PROD003,Third Product,Third sample product,STY125,GRN,XL,Forest Green,29.99
```

### Important Notes

- Headers are case-insensitive
- File must be saved as `.csv` extension
- Use UTF-8 encoding
- Duplicate `UNIQUE_KEY` values will update existing products
- Missing optional fields will be stored as `NULL`
- Invalid rows are logged but don't stop processing

## Project Structure

```
test-project/
├── app/
│   ├── Actions/Fortify/          # Authentication actions
│   ├── Events/                    # Event classes
│   ├── Http/Controllers/          # Application controllers
│   │   ├── FileController.php    # CSV upload controller
│   │   └── Settings/             # Settings controllers
│   ├── Jobs/                      # Queue job classes
│   │   └── ProcessCsvUpload.php  # CSV processing job
│   └── Models/                    # Eloquent models
│       ├── User.php              # User model
│       ├── Product.php           # Product model
│       └── FileUpload.php        # File upload model
├── config/                        # Configuration files
│   ├── fortify.php               # Authentication config
│   └── queue.php                 # Queue config
├── database/
│   ├── migrations/               # Database migrations
│   └── database.sqlite           # SQLite database file
├── public/                        # Public assets
├── resources/
│   ├── css/                      # Stylesheets
│   ├── js/                       # Vue.js application
│   │   ├── components/           # Vue components
│   │   ├── layouts/              # Page layouts
│   │   │   ├── AppLayout.vue    # Main app layout
│   │   │   └── AuthLayout.vue   # Auth pages layout
│   │   ├── pages/                # Page components
│   │   │   ├── Uploads.vue      # CSV upload page
│   │   │   ├── Dashboard.vue    # Dashboard page
│   │   │   └── auth/            # Auth pages
│   │   └── routes/               # Route helpers
│   └── views/                    # Blade templates
├── routes/
│   ├── web.php                   # Web routes
│   └── settings.php              # Settings routes
├── storage/
│   └── app/uploads/              # Uploaded CSV files
├── .env                          # Environment configuration
├── composer.json                 # PHP dependencies
├── package.json                  # Node.js dependencies
└── README.md                     # This file
```

## Database Schema

### Users Table
```sql
CREATE TABLE users (
    id INTEGER PRIMARY KEY,
    name VARCHAR(255),
    email VARCHAR(255) UNIQUE,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255),
    two_factor_secret TEXT NULL,
    two_factor_recovery_codes TEXT NULL,
    two_factor_confirmed_at TIMESTAMP NULL,
    remember_token VARCHAR(100),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Products Table
```sql
CREATE TABLE products (
    unique_key VARCHAR(255) PRIMARY KEY,
    product_title TEXT,
    product_description TEXT NULL,
    style VARCHAR(255) NULL,
    sanmar_mainframe_color VARCHAR(255) NULL,
    size VARCHAR(255) NULL,
    color_name VARCHAR(255) NULL,
    piece_price DECIMAL(10,2) NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### File Uploads Table
```sql
CREATE TABLE file_uploads (
    id INTEGER PRIMARY KEY,
    filename VARCHAR(255),
    original_filename VARCHAR(255),
    status VARCHAR(255) DEFAULT 'pending',
    total_rows INTEGER NULL,
    processed_rows INTEGER DEFAULT 0,
    error_message TEXT NULL,
    user_id INTEGER NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## API Endpoints

### Authentication Routes (Laravel Fortify)
- `POST /register` - Create new user account
- `POST /login` - Authenticate user
- `POST /logout` - End user session
- `POST /forgot-password` - Request password reset
- `POST /reset-password` - Reset password with token

### CSV Upload Routes
- `GET /uploads` - Upload management page
- `GET /api/uploads` - List all uploads (JSON)
- `POST /api/uploads` - Upload CSV file (JSON)
- `GET /api/uploads/{id}` - Get upload status (JSON)

### Settings Routes
- `GET /settings/profile` - Profile settings page
- `PATCH /settings/profile` - Update profile
- `GET /settings/password` - Password change page
- `PUT /settings/password` - Update password
- `GET /settings/two-factor` - 2FA settings page
- `POST /settings/two-factor` - Enable 2FA
- `DELETE /settings/two-factor` - Disable 2FA

## Configuration

### Environment Variables

Key configuration in `.env` file:

```ini
# Application
APP_NAME=Laravel
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=sqlite
# DB_DATABASE=/path/to/database.sqlite

# Queue
QUEUE_CONNECTION=database

# Mail (for password reset)
MAIL_MAILER=log
# MAIL_FROM_ADDRESS=hello@example.com
```

### Queue Configuration

The application uses database-based queues by default. Configure in `config/queue.php`:

```php
'default' => env('QUEUE_CONNECTION', 'database'),
```

### Upload Configuration

File uploads are stored in `storage/app/uploads/` with UUID filenames for security.

Maximum upload size can be configured in `php.ini`:
```ini
upload_max_filesize = 100M
post_max_size = 100M
max_execution_time = 300
```

## Troubleshooting

### CSV Upload Stuck on "PENDING"

**Problem**: Queue worker is not running

**Solution**:
```bash
php artisan queue:listen --tries=1
```

The queue worker must be running to process CSV files. If you stop the worker, pending uploads will remain in the queue until it's restarted.

### Upload Shows "FAILED"

**Possible Causes**:
1. Missing required columns (`UNIQUE_KEY` or `PRODUCT_TITLE`)
2. File encoding issues (not UTF-8)
3. Corrupted CSV file
4. Permission issues with storage directory

**Solution**:
- Check the error message displayed in the uploads table
- Ensure CSV has required headers
- Save CSV as UTF-8 encoded
- Verify `storage/app/uploads/` directory exists and is writable

### Can't Log In / Forgot Password

**Reset Database**:
```bash
php artisan migrate:fresh
```

Then create a new account. Note: This will delete all existing data.

### Frontend Not Loading / Styles Missing

**Rebuild Assets**:
```bash
npm run build
```

Or for development with hot reload:
```bash
npm run dev
```

### Queue Jobs Keep Failing

**View Failed Jobs**:
```bash
php artisan queue:failed
```

**Retry Failed Jobs**:
```bash
php artisan queue:retry all
```

**Clear Failed Jobs**:
```bash
php artisan queue:flush
```

### Clear Cache Issues

If experiencing configuration or cache issues:
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## Development Commands

### Useful Artisan Commands

```bash
# View routes
php artisan route:list

# Create new migration
php artisan make:migration create_table_name

# Create new controller
php artisan make:controller ControllerName

# Create new model
php artisan make:model ModelName

# Run tests
php artisan test

# View queue status
php artisan queue:work --once

# Monitor queue
php artisan queue:monitor
```

### Frontend Development

```bash
# Install dependencies
npm install

# Development with hot reload
npm run dev

# Build for production
npm run build

# Type checking
npm run type-check

# Linting
npm run lint
```

## Key Files Reference

### Backend
- **Upload Controller**: `app/Http/Controllers/FileController.php`
- **Processing Job**: `app/Jobs/ProcessCsvUpload.php`
- **Product Model**: `app/Models/Product.php`
- **FileUpload Model**: `app/Models/FileUpload.php`
- **User Model**: `app/Models/User.php`

### Frontend
- **Upload Page**: `resources/js/pages/Uploads.vue`
- **Dashboard**: `resources/js/pages/Dashboard.vue`
- **App Layout**: `resources/js/layouts/AppLayout.vue`
- **Login Page**: `resources/js/pages/auth/Login.vue`
- **Register Page**: `resources/js/pages/auth/Register.vue`

### Configuration
- **Routes**: `routes/web.php`, `routes/settings.php`
- **Fortify Config**: `config/fortify.php`
- **Queue Config**: `config/queue.php`

## How It Works

### CSV Processing Flow

1. **Upload**: User uploads CSV file via drag-and-drop or file picker
2. **Storage**: File stored in `storage/app/uploads/` with UUID filename
3. **Queue**: Job dispatched to database queue
4. **Processing**: Background worker processes file:
   - Reads CSV headers and validates
   - Counts total rows
   - Processes each row:
     - Cleans UTF-8 characters
     - Validates required fields
     - Upserts into products table
     - Updates progress every 100 rows
5. **Completion**: Status updated to "completed" or "failed"
6. **Real-time Updates**: Frontend polls API every 2 seconds for progress

### UPSERT Logic

Products are inserted or updated based on `unique_key`:
- **If product exists**: Update all fields with new data
- **If product is new**: Insert new record
- This ensures data stays current with latest imports

### Background Processing

The queue system allows:
- Non-blocking uploads (user can continue using app)
- Processing large files without timeout
- Multiple concurrent file processing
- Automatic retry on failure
- Progress tracking and error recovery

## Security Features

- **Authentication**: Laravel Fortify with secure password hashing
- **Two-Factor Authentication**: TOTP-based 2FA with recovery codes
- **CSRF Protection**: All forms protected with CSRF tokens
- **Rate Limiting**: Login attempts limited to prevent brute force
- **File Validation**: Only CSV files accepted for upload
- **SQL Injection Prevention**: Eloquent ORM with parameter binding
- **XSS Protection**: Vue.js automatic escaping
- **Secure File Storage**: Uploaded files stored outside public directory

## Performance Optimization

- **Queue Workers**: Background processing for CPU-intensive tasks
- **Batch Updates**: Progress updated every 100 rows for efficiency
- **Database Indexing**: Primary keys and indexes on frequently queried columns
- **Vite Build**: Fast frontend compilation and hot module replacement
- **Lazy Loading**: Vue components loaded on demand
- **Database Transactions**: UPSERT operations for efficient updates

## License

This project is based on the Laravel + Vue starter kit and is open-sourced software licensed under the MIT license.

## Contributing

Thank you for considering contributing to this project! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Support

For issues, questions, or contributions, please refer to the official Laravel documentation:
- [Laravel Documentation](https://laravel.com/docs)
- [Inertia.js Documentation](https://inertiajs.com)
- [Vue.js Documentation](https://vuejs.org)
- [Tailwind CSS Documentation](https://tailwindcss.com)
