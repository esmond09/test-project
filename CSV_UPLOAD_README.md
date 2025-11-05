# CSV Upload System Documentation

## Overview

This application allows users to upload CSV files containing product data. The files are processed in the background using Laravel's queue system, with real-time status updates displayed in the UI.

## Features

✅ **CSV File Upload** - Users can upload CSV files through a simple UI
✅ **Background Processing** - Files are processed asynchronously using Laravel queues
✅ **Real-time Status Updates** - UI polls every 2 seconds for upload status
✅ **Upload History** - View all recent uploads with their status and progress
✅ **Idempotent Uploads** - Same file can be uploaded multiple times without duplicates
✅ **UPSERT Support** - Updates existing records based on UNIQUE_KEY
✅ **UTF-8 Character Cleaning** - Automatically removes non-UTF-8 characters
✅ **Progress Tracking** - Shows number of rows processed vs total rows

## Database Schema

### Products Table
- `unique_key` (Primary Key) - Unique identifier for each product
- `product_title` - Product name
- `product_description` - Product description
- `style` - Style number
- `sanmar_mainframe_color` - Mainframe color
- `size` - Product size
- `color_name` - Color name
- `piece_price` - Price per piece (decimal)
- `created_at`, `updated_at` - Timestamps

### File Uploads Table
- `id` - Primary key
- `filename` - Stored filename
- `original_filename` - Original uploaded filename
- `status` - Upload status (pending, processing, completed, failed)
- `total_rows` - Total number of rows in CSV
- `processed_rows` - Number of rows processed so far
- `error_message` - Error message if failed
- `user_id` - Foreign key to users table
- `created_at`, `updated_at` - Timestamps

## CSV File Format

The CSV file must have the following columns (header row required):

```csv
UNIQUE_KEY,PRODUCT_TITLE,PRODUCT_DESCRIPTION,STYLE#,SANMAR_MAINFRAME_COLOR,SIZE,COLOR_NAME,PIECE_PRICE
```

### Example CSV:
```csv
UNIQUE_KEY,PRODUCT_TITLE,PRODUCT_DESCRIPTION,STYLE#,SANMAR_MAINFRAME_COLOR,SIZE,COLOR_NAME,PIECE_PRICE
PROD001,Cotton T-Shirt,Comfortable 100% cotton t-shirt,TS-100,WHITE,M,White,15.99
PROD002,Athletic Shorts,Performance athletic shorts,AS-200,BLACK,L,Black,22.50
```

### Field Requirements:
- **UNIQUE_KEY** (Required) - Must be unique across all products
- **PRODUCT_TITLE** (Required) - Product name
- **PRODUCT_DESCRIPTION** (Optional)
- **STYLE#** (Optional)
- **SANMAR_MAINFRAME_COLOR** (Optional)
- **SIZE** (Optional)
- **COLOR_NAME** (Optional)
- **PIECE_PRICE** (Optional) - Decimal format (e.g., 15.99)

## How It Works

### Upload Process:
1. User selects a CSV file through the UI
2. File is uploaded to `/api/uploads` endpoint
3. File is stored in `storage/app/uploads/` directory
4. A `FileUpload` record is created with status "pending"
5. `ProcessCsvUpload` job is dispatched to the queue
6. User is immediately returned a success response

### Background Processing:
1. Queue worker picks up the job
2. Status is updated to "processing"
3. CSV file is opened and headers are validated
4. Each row is processed:
   - Non-UTF-8 characters are cleaned
   - Data is validated (UNIQUE_KEY and PRODUCT_TITLE required)
   - Record is upserted into products table using UNIQUE_KEY
5. Progress is updated every 100 rows
6. Final status is set to "completed" or "failed"

### Real-time Updates:
- UI polls `/api/uploads` every 2 seconds
- Displays current status and progress for each upload
- Shows progress bar for files being processed
- Displays error messages for failed uploads

## Setup Instructions

### 1. Install Dependencies
```bash
composer install
npm install
```

### 2. Configure Environment
Make sure your `.env` file has:
```env
DB_CONNECTION=sqlite
QUEUE_CONNECTION=database
```

### 3. Run Migrations
```bash
php artisan migrate
```

### 4. Start Services

You need to run THREE services simultaneously:

**Terminal 1 - Laravel Server:**
```bash
php artisan serve
```

**Terminal 2 - Queue Worker:**
```bash
php artisan queue:work --tries=3
```

**Terminal 3 - Vite Dev Server:**
```bash
npm run dev
```

Or use the built-in dev command:
```bash
composer dev
```

## Usage Instructions

### 1. Access the Application
Navigate to: `http://localhost:8000`

### 2. Login/Register
Create an account or login to access the upload page

### 3. Navigate to CSV Uploads
Click on "CSV Uploads" in the sidebar navigation

### 4. Upload a CSV File
1. Click "Select CSV File" button
2. Choose your CSV file (must be .csv format)
3. File will be uploaded and queued for processing
4. Watch the real-time progress in the upload history section

### 5. Monitor Progress
- The upload history shows all your recent uploads
- Status badges show: PENDING, PROCESSING, COMPLETED, or FAILED
- Progress bars show how many rows have been processed
- Completed uploads show total rows processed
- Failed uploads display error messages

## Testing

### Use the Sample CSV
A sample CSV file is included at: `storage/app/sample.csv`

This file contains:
- 10 sample products
- One duplicate entry (PROD001 and PROD006 are the same product with updated price)
- Demonstrates the UPSERT functionality

### Test Scenarios:

**1. Initial Upload:**
- Upload `sample.csv`
- All 10 products should be created
- Status should show "COMPLETED"
- Should process 10 rows

**2. Duplicate Upload (Idempotent):**
- Upload the same `sample.csv` again
- No duplicate products created
- PROD001 price should be updated to 16.99 (from PROD006 entry)
- Status should show "COMPLETED"
- Should process 10 rows again

**3. Modified Data (UPSERT):**
- Edit `sample.csv` and change PIECE_PRICE for any product
- Upload the modified file
- Existing product should be updated with new price
- Status should show "COMPLETED"

## API Endpoints

### GET `/api/uploads`
Get all file uploads for the authenticated user

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "original_filename": "sample.csv",
      "status": "completed",
      "total_rows": 10,
      "processed_rows": 10,
      "error_message": null,
      "created_at": "2025-11-05T13:05:00.000000Z",
      "updated_at": "2025-11-05T13:05:15.000000Z"
    }
  ]
}
```

### POST `/api/uploads`
Upload a new CSV file

**Request:**
- Method: POST
- Content-Type: multipart/form-data
- Body: `file` (CSV file)

**Response:**
```json
{
  "message": "File uploaded successfully and queued for processing",
  "data": {
    "id": 1,
    "original_filename": "sample.csv",
    "status": "pending",
    "created_at": "2025-11-05T13:05:00.000000Z"
  }
}
```

### GET `/api/uploads/{id}`
Get status of a specific upload

**Response:**
```json
{
  "data": {
    "id": 1,
    "original_filename": "sample.csv",
    "status": "processing",
    "total_rows": 10,
    "processed_rows": 5,
    "error_message": null,
    "created_at": "2025-11-05T13:05:00.000000Z",
    "updated_at": "2025-11-05T13:05:10.000000Z"
  }
}
```

## File Structure

```
app/
├── Http/Controllers/
│   └── FileController.php          # API endpoints for file upload
├── Jobs/
│   └── ProcessCsvUpload.php        # Background job for CSV processing
└── Models/
    ├── Product.php                 # Product model
    └── FileUpload.php              # File upload tracking model

database/migrations/
├── 2025_11_05_130358_create_products_table.php
└── 2025_11_05_130402_create_file_uploads_table.php

resources/js/
└── pages/
    └── Uploads.vue                 # Upload UI with real-time updates

routes/
└── web.php                         # Route definitions

storage/app/
├── uploads/                        # Uploaded CSV files stored here
└── sample.csv                      # Sample CSV for testing
```

## Technical Details

### Queue System
- Uses Laravel's built-in queue system
- Database driver for queue storage
- Jobs are processed by queue workers
- Failed jobs are retried up to 3 times

### UTF-8 Character Cleaning
The system automatically cleans CSV data:
- Removes BOM (Byte Order Mark)
- Converts to UTF-8 encoding
- Removes invalid UTF-8 characters
- Trims whitespace

### UPSERT Implementation
Uses Laravel's `updateOrCreate` method:
```php
Product::updateOrCreate(
    ['unique_key' => $data['unique_key']],  // Find by unique_key
    $data                                    // Update/Insert this data
);
```

### Real-time Updates
- Polling mechanism (2-second intervals)
- Fetches all uploads on each poll
- Updates UI reactively using Vue 3
- No WebSocket connection required

## Troubleshooting

### Queue Not Processing
**Problem:** Files stuck in "pending" status

**Solution:** Make sure queue worker is running:
```bash
php artisan queue:work
```

### CSV Upload Fails
**Problem:** "Error uploading file" message

**Solutions:**
- Check file is valid CSV format
- Ensure file has required headers
- Check file size (max 10MB)
- Verify UNIQUE_KEY and PRODUCT_TITLE columns exist

### Database Errors
**Problem:** Migration or insert errors

**Solution:** Reset database:
```bash
php artisan migrate:fresh
```

### Route Not Found
**Problem:** 404 error on `/uploads` or API routes

**Solution:** Regenerate routes:
```bash
php artisan wayfinder:generate
```

## Performance Considerations

- Progress updates every 100 rows (configurable in `ProcessCsvUpload.php`)
- Polling interval: 2 seconds (configurable in `Uploads.vue`)
- Max file size: 10MB (configurable in `FileController.php`)
- Large files may take time to process - monitor progress in UI

## Security Features

- Authentication required for all upload routes
- CSRF protection on all POST requests
- File type validation (only .csv allowed)
- File size limits enforced
- User-specific file isolation

## Future Enhancements

Possible improvements:
- WebSocket support for real-time updates (replace polling)
- File download for processed data
- Bulk product deletion
- CSV export functionality
- Advanced filtering and search
- Data validation rules configuration
- Email notifications on completion
- API rate limiting
- File upload via API token

## Support

For issues or questions, please check:
1. Laravel logs: `storage/logs/laravel.log`
2. Queue failed jobs table
3. Browser console for frontend errors

---

**Built with:**
- Laravel 12.x
- Vue 3
- Inertia.js
- Tailwind CSS
- shadcn-vue components
