# PHP_Laravel12_Store_BackUP_On_DropBox

## Overview

This project demonstrates how to configure **Laravel 12** to automatically back up the application files and database to **Dropbox** using the **spatie/laravel-backup** and **spatie/flysystem-dropbox** packages.

This setup is suitable for:

* Production backup automation
* Learning Laravel filesystem drivers
* MCA / BCA final-year projects
* Interview demonstrations

---

## Features

* Full Laravel application backup
* Database backup
* Automatic upload to Dropbox
* Secure API-based Dropbox integration
* Easy restore support

---

## Prerequisites

* PHP 8.2 or higher
* Composer
* Laravel 12
* Dropbox account

---

## Step 1: Install Laravel 12

> Skip this step if your Laravel project already exists.

```bash
composer create-project laravel/laravel example-app
cd example-app
```

---

## Step 2: Configure Dropbox as a Filesystem Driver

### Install Dropbox Flysystem Package

```bash
composer require spatie/flysystem-dropbox
```

### Update `AppServiceProvider`

**File:** `app/Providers/AppServiceProvider.php`

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use Spatie\Dropbox\Client as DropboxClient;
use Spatie\FlysystemDropbox\DropboxAdapter;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Storage::extend('dropbox', function (Application $app, array $config) {
            $adapter = new DropboxAdapter(new DropboxClient(
                $config['authorization_token']
            ));

            return new FilesystemAdapter(
                new Filesystem($adapter, $config),
                $adapter,
                $config
            );
        });
    }
}
```

---

### Configure Filesystem Disk

**File:** `config/filesystems.php`

```php
'disks' => [
    'dropbox' => [
        'driver' => 'dropbox',
        'key' => env('DROPBOX_APP_KEY'),
        'secret' => env('DROPBOX_APP_SECRET'),
        'authorization_token' => env('DROPBOX_AUTH_TOKEN'),
    ],
],
```

---

## Step 3: Get Dropbox API Credentials

### Create Dropbox App

1. Go to **Dropbox App Console**
2. Click **Create App**
3. Choose **Scoped access**
4. Choose **Full Dropbox** or **App Folder**
5. Give your app a name

### Set Permissions

Enable the following permission:

* `files.content.write`

### Generate Access Token

From the app dashboard, generate:

* App Key
* App Secret
* Access Token

### Update `.env` File

```env
DROPBOX_APP_KEY=your_app_key
DROPBOX_APP_SECRET=your_app_secret
DROPBOX_AUTH_TOKEN=your_access_token
```

---

## Step 4: Install Spatie Laravel Backup Package

```bash
composer require spatie/laravel-backup
```

### Publish Configuration

```bash
php artisan vendor:publish --provider="Spatie\\Backup\\BackupServiceProvider"
```

This will create:

* `config/backup.php`

---

## Step 5: Configure Backup Destination

**File:** `config/backup.php`

```php
'destination' => [
    'disks' => [
        'dropbox',
    ],
],
```

---

## Step 6: Run Backup Command

```bash
php artisan backup:run
```

### Result

* Application files backup
* Database backup
* Zip file uploaded to Dropbox

You can verify the backup inside your Dropbox account.

---

## Screenshot
### Get Dropbox API Key & Secret & Access Token
<img width="1789" height="813" alt="image" src="https://github.com/user-attachments/assets/b2988cb5-7bb9-4885-939d-91acbef83786" />

<img width="1808" height="921" alt="image" src="https://github.com/user-attachments/assets/3de44568-494e-4ad1-9929-ab10e32a0f03" />

<img width="1498" height="733" alt="image" src="https://github.com/user-attachments/assets/4ea7c9ee-7a09-43e0-8df5-3079a1705c32" />

![Uploading image.png…]()



## Useful Backup Commands

```bash
php artisan backup:run        # Run backup
php artisan backup:clean      # Remove old backups
php artisan backup:list       # List all backups
```

---

## Security Notes

* Never commit `.env` file
* Keep Dropbox token secret
* Use App Folder access for safety

---

## Troubleshooting

### Backup Not Uploading

* Check Dropbox token
* Ensure `dropbox` disk name matches
* Clear config cache

```bash
php artisan config:clear
```

### Permission Errors

* Verify Dropbox permissions
* Regenerate access token

---

## Use Cases

* Production backups
* Daily cron backups
* Disaster recovery
* Cloud-based backup storage

---

## Conclusion

This setup provides a **secure, automated, and scalable** way to back up Laravel 12 applications to Dropbox using Spatie packages.

---

## Author

**Mihir Mehta**
Laravel Developer
