# Quick Start Guide - CSV Upload System

## 🚀 Start the Application (3 Steps)

### Option 1: Using Composer (Recommended)
Open ONE terminal and run:
```bash
composer dev
```

This will start all three services (Laravel server, queue worker, and Vite) simultaneously.

### Option 2: Manual Start
Open THREE separate terminals and run:

**Terminal 1 - Laravel Server:**
```bash
php artisan serve
```

**Terminal 2 - Queue Worker (REQUIRED!):**
```bash
php artisan queue:work
```

**Terminal 3 - Vite Dev Server:**
```bash
npm run dev
```

## 📝 Testing Instructions

### 1. Access the Application
Open your browser and go to: `http://localhost:8000`

### 2. Register/Login
- Click "Register" if you don't have an account
- Or login with your existing credentials

### 3. Navigate to CSV Uploads
- Click on **"CSV Uploads"** in the left sidebar (look for the upload icon)

### 4. Test Upload #1 - Initial Upload
1. Click **"Select CSV File"** button
2. Navigate to `storage/app/sample.csv`
3. Select the file and wait
4. Watch the upload appear in the history section below
5. Status will change: PENDING → PROCESSING → COMPLETED
6. Progress bar shows: "Processing... X / 10 rows"
7. When done, you'll see: "Successfully processed 10 rows"

### 5. Test Upload #2 - Idempotency Test
1. Upload the **same file** again (`sample.csv`)
2. Watch it process again
3. No duplicate products created!
4. PROD001 price updated from 15.99 to 16.99 (due to PROD006 entry)

### 6. Test Upload #3 - UPSERT Test
1. Open `storage/app/sample.csv` in a text editor
2. Change any PIECE_PRICE value (e.g., change 15.99 to 19.99)
3. Save the file
4. Upload the modified file
5. Watch it process
6. The product price will be updated, not duplicated

## ✅ What to Look For

### Successful Upload Shows:
- ✅ Green "COMPLETED" badge
- ✅ "Successfully processed 10 rows"
- ✅ Upload timestamp
- ✅ Original filename displayed

### During Processing Shows:
- ⏳ Blue "PROCESSING" badge
- ⏳ Progress bar with percentage
- ⏳ "Processing... 5 / 10 rows (50%)"

### Failed Upload Shows:
- ❌ Red "FAILED" badge
- ❌ Error message explaining what went wrong

## 🎯 Features to Test

### Real-time Updates
- Keep the page open while file processes
- Watch the status update automatically every 2 seconds
- No need to refresh the page!

### Multiple Uploads
- Upload multiple CSV files
- Each tracks independently
- All show in the history list

### Error Handling
- Try uploading a non-CSV file (should show validation error)
- Try uploading an empty file (should fail gracefully)

## 🐛 Troubleshooting

### Files Stay in "PENDING" Status
**Problem:** Queue worker is not running

**Solution:**
```bash
php artisan queue:work
```

### "Route [uploads] not defined" Error
**Problem:** Routes not generated

**Solution:**
```bash
php artisan wayfinder:generate
```

### Page Not Loading
**Problem:** Vite dev server not running

**Solution:**
```bash
npm run dev
```

### Upload Button Does Nothing
**Problem:** Not authenticated

**Solution:** Make sure you're logged in

## 📊 Database Verification

To verify products were imported correctly:

```bash
php artisan tinker
```

Then run:
```php
// Count total products
\App\Models\Product::count();

// See all products
\App\Models\Product::all();

// Find specific product
\App\Models\Product::find('PROD001');

// Check upload history
\App\Models\FileUpload::all();
```

## 📁 Sample CSV Details

The included `storage/app/sample.csv` contains:
- 10 product entries
- Product types: T-shirts, shorts, hoodies, caps, shoes, jackets, etc.
- Price range: $12.99 - $89.99
- **Special**: PROD001 appears twice (as PROD001 and PROD006) to test UPSERT

## 🎓 Expected Results

After uploading `sample.csv`:

1. **First Upload:**
   - 10 rows processed
   - 10 products created
   - PROD001 has price $16.99 (from PROD006, the later entry)

2. **Second Upload (same file):**
   - 10 rows processed
   - 0 new products created
   - All existing products updated (timestamps changed)
   - Still only 10 products total

3. **Modified Upload:**
   - 10 rows processed
   - 0 new products created
   - Only changed fields updated
   - Still only 10 products total

## 🔧 Advanced Testing

### Custom CSV File
Create your own CSV with this format:

```csv
UNIQUE_KEY,PRODUCT_TITLE,PRODUCT_DESCRIPTION,STYLE#,SANMAR_MAINFRAME_COLOR,SIZE,COLOR_NAME,PIECE_PRICE
TEST001,Test Product,Test Description,TST-01,BLUE,L,Blue,25.99
```

**Required Fields:**
- UNIQUE_KEY
- PRODUCT_TITLE

**Optional Fields:**
- All others

### Large File Test
- Create a CSV with 1000+ rows
- Upload it
- Watch the progress bar update as it processes
- Progress updates every 100 rows

## 📚 Next Steps

Once you've verified everything works:

1. Check [CSV_UPLOAD_README.md](CSV_UPLOAD_README.md) for complete documentation
2. Review API endpoints for integration
3. Customize the UI in `resources/js/pages/Uploads.vue`
4. Modify processing logic in `app/Jobs/ProcessCsvUpload.php`
5. Add more validation rules as needed

## ⚡ Quick Commands Reference

```bash
# Start everything
composer dev

# Stop queue worker (Ctrl+C in the terminal)

# View logs
tail -f storage/logs/laravel.log

# Clear cache
php artisan cache:clear

# Reset database (WARNING: deletes all data)
php artisan migrate:fresh

# Check queue jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

---

**Ready to go!** Just run `composer dev` and start uploading! 🎉
