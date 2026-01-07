# ORCA DAM - Laravel 12 Installation Guide

## Installing on Fresh Laravel 12

Since you have a Laravel 12 skeleton, here's how to integrate ORCA:

### Step 1: Install Dependencies

Replace your `composer.json` require section with:

```json
"require": {
    "php": "^8.2",
    "aws/aws-sdk-php": "^3.0",
    "guzzlehttp/guzzle": "^7.2",
    "intervention/image": "^3.0",
    "laravel/framework": "^12.0",
    "laravel/sanctum": "^4.0",
    "laravel/tinker": "^2.10"
}
```

Then run:
```bash
composer update
```

### Step 2: Copy ORCA Files

From the ZIP, copy these directories to your Laravel project:

```
app/Http/Controllers/     → Copy all controllers
app/Models/              → Copy Asset.php, Tag.php (keep your User.php, but add the role methods)
app/Policies/            → Copy AssetPolicy.php
app/Services/            → Copy both service files (use the Laravel 12 versions!)
database/migrations/     → Copy all migration files
database/seeders/        → Copy AdminUserSeeder.php
resources/views/         → Copy all view files and folders
routes/web.php          → Merge the routes
routes/api.php          → Merge the routes
```

### Step 3: Update User Model

In your existing `app/Models/User.php`, add these methods:

```php
use Illuminate\Database\Eloquent\Relations\HasMany;

// ... inside the User class ...

/**
 * Get all assets uploaded by this user
 */
public function assets(): HasMany
{
    return $this->hasMany(Asset::class);
}

/**
 * Check if user is an admin
 */
public function isAdmin(): bool
{
    return $this->role === 'admin';
}

/**
 * Check if user is an editor
 */
public function isEditor(): bool
{
    return $this->role === 'editor';
}

/**
 * Check if user can manage all assets
 */
public function canManageAllAssets(): bool
{
    return $this->isAdmin();
}
```

### Step 4: Update S3Service and DiscoverController

**IMPORTANT**: Use the updated versions I just created:
- `S3Service-laravel12.php` → Copy to `app/Services/S3Service.php`
- `DiscoverController-laravel12.php` → Copy to `app/Http/Controllers/DiscoverController.php`

These are updated for Intervention Image 3.x which is required for Laravel 12.

### Step 5: Configure PHP Memory Limit

**Important:** For handling large file uploads (PDFs, GIFs, videos), you need to increase PHP limits:

**For Laravel Herd users:**
1. Locate your Herd PHP configuration file:
   - **macOS/Linux**: `~/.config/herd/bin/php84/php.ini`
   - **Windows**: `C:\Users\<username>\.config\herd\bin\php84\php.ini`
   - **To find yours**: Run `php --ini` and check "Loaded Configuration File"
2. Edit the following values:
   ```ini
   memory_limit = 256M
   upload_max_filesize = 100M
   post_max_size = 100M
   max_execution_time = 300
   max_input_time = 300
   ```
3. **Restart Herd** from the system tray (Stop/Start or Restart all services)

**For Apache/Nginx/php-fpm users:**
Create a `.user.ini` file in the `public/` directory:
```ini
memory_limit = 256M
upload_max_filesize = 100M
post_max_size = 100M
max_execution_time = 300
```
Then restart your web server.

**Note:** `.user.ini` files do NOT work with Laravel Herd - you must edit Herd's `php.ini` directly.

### Step 6: Configure Environment

Add to your `.env`:

```env
# AWS S3 Configuration
AWS_ACCESS_KEY_ID=your_aws_access_key
AWS_SECRET_ACCESS_KEY=your_aws_secret_key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
AWS_URL=https://your-bucket-name.s3.amazonaws.com
AWS_USE_PATH_STYLE_ENDPOINT=false

# AWS Rekognition (optional - for AI tagging)
AWS_REKOGNITION_ENABLED=false

# Set filesystem to S3
FILESYSTEM_DISK=s3
```

Also configure your S3 disk in `config/filesystems.php` (should already be there):

```php
's3' => [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION'),
    'bucket' => env('AWS_BUCKET'),
    'url' => env('AWS_URL'),
    'endpoint' => env('AWS_ENDPOINT'),
    'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
    'throw' => false,
],
```

### Step 7: Register Policy

In `app/Providers/AppServiceProvider.php` or `AuthServiceProvider.php`, add:

```php
use App\Models\Asset;
use App\Policies\AssetPolicy;

protected $policies = [
    Asset::class => AssetPolicy::class,
];
```

Or in Laravel 12, you can use model discovery (it should auto-discover if the naming matches).

### Step 8: Run Migrations

```bash
php artisan migrate
php artisan db:seed --class=AdminUserSeeder
```

### Step 9: Install Frontend Dependencies

```bash
npm install
npm run dev
```

### Step 10: Start Server

```bash
php artisan serve
```

Visit `http://localhost:8000` and login with:
- Email: `admin@orca.dam`
- Password: `password`

**Change this password immediately!**

---

## Key Differences from Laravel 10

### Intervention Image 3.x (Breaking Changes)

Laravel 12 uses Intervention Image 3.x which has different syntax:

**OLD (v2):**
```php
use Intervention\Image\Facades\Image;
$image = Image::make($content);
$image->resize(300, 300, function ($constraint) {
    $constraint->aspectRatio();
});
```

**NEW (v3):**
```php
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

$manager = new ImageManager(new Driver());
$image = $manager->read($content);
$image->scale(width: 300, height: 300);
```

The updated `S3Service-laravel12.php` and `DiscoverController-laravel12.php` files I provided already have these changes!

### Laravel Sanctum 4.x

Sanctum is now v4 for Laravel 12, but the API usage is the same.

---

## Troubleshooting

### "Class 'Intervention\Image\Facades\Image' not found"
You're using the old Intervention Image syntax. Use the updated S3Service file I provided.

### Memory exhausted errors when uploading large files
- **For Herd:** Edit `~/.config/herd/bin/php84/php.ini` with at least 256MB memory limit (see Step 5)
- **For Apache/Nginx:** Create `public/.user.ini` with at least 256MB memory limit (see Step 5)
- Restart your web server (Herd from system tray, or `sudo service apache2 restart`)
- Verify changes: Run `php -i | grep "upload_max_filesize\|post_max_size\|memory_limit"`
- Check `storage/logs/laravel.log` for specific error details
- For files >100MB, consider increasing memory limit further

### Upload showing 413 error (Content Too Large)
- **Symptom:** Browser shows 413 error, files rejected before reaching PHP
- **Cause:** Web server (Herd/Nginx/Apache) rejecting large POST requests
- **For Herd:** Edit Herd's `php.ini` file (see Step 5) and restart Herd
- **For Nginx:** Edit `/etc/nginx/nginx.conf` and set `client_max_body_size 100M;`
- **For Apache:** Edit `.htaccess` and set `LimitRequestBody 104857600`

### Upload stuck at 100% or showing 500 error
- Ensure toast notification system is implemented (check `resources/views/layouts/app.blade.php`)
- Check browser console for JavaScript errors
- Review `storage/logs/laravel.log` for server-side errors
- Verify PHP configuration is correct (see Step 5)

### Composer conflicts
Make sure you're using:
- `laravel/framework: ^12.0`
- `laravel/sanctum: ^4.0`
- `intervention/image: ^3.0`

### AWS SDK issues
The AWS SDK v3 works fine with Laravel 12.

---

## Quick Checklist

- [ ] Updated composer.json dependencies
- [ ] Ran `composer update`
- [ ] Copied all ORCA files to Laravel project
- [ ] Used Laravel 12 compatible S3Service and DiscoverController
- [ ] Updated User model with ORCA methods
- [ ] Configured PHP limits (Herd: edit `php.ini`, Apache/Nginx: create `public/.user.ini`)
- [ ] Restarted web server after PHP configuration changes
- [ ] Verified PHP limits with `php -i | grep upload_max_filesize`
- [ ] Configured .env with AWS credentials
- [ ] Registered AssetPolicy
- [ ] Ran migrations
- [ ] Created admin user
- [ ] Installed npm dependencies
- [ ] Started server

---

## Files to Use for Laravel 12

From the updated files I just created:
1. `composer-laravel12.json` → Your `composer.json` require section
2. `S3Service-laravel12.php` → `app/Services/S3Service.php`
3. `DiscoverController-laravel12.php` → `app/Http/Controllers/DiscoverController.php`

All other files from the ZIP work as-is with Laravel 12!
