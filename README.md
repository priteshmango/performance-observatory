# Performance Observatory

A production-grade Laravel package designed to provide complete end-to-end explanation of why any page is slow. It monitors every measurable step from initial request until the page becomes fully interactive in the browser.

## Installation

Since this package is developed locally, you can install it into your existing Laravel application using Composer's path repository feature.

### 1. Link the GitHub Repository

In your existing Laravel application's `composer.json`, add a repository pointing to the GitHub URL where this package is hosted:

```json
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/yourusername/performance-observatory"
        }
    ],
```

*(Be sure to replace the URL with the actual link to your GitHub repository).*

### 2. Install via Composer

Run the following command in your Laravel application's root directory:

```bash
composer require performance/observatory @dev
```

### 3. Publish Configuration & Assets

Publish the package's configuration file:

```bash
php artisan vendor:publish --tag="observatory-config"
```

This will create a `config/observatory.php` file in your application where you can toggle individual collectors, define the storage connection, and adjust sampling rates.

### 4. Run Migrations

The package requires a database table to store the telemetry data. Run your migrations:

```bash
php artisan migrate
```

*(By default, it uses your application's default database connection, but this can be changed in `config/observatory.php`)*.

---

## Injecting the Frontend Tracker

To gather metrics like Time to First Byte (TTFB), Largest Contentful Paint (LCP), and full DOM rendering times, you must inject the tracker script into your Blade layouts.

Add the following inside your `<head>` tag in your main layout file (e.g., `resources/views/layouts/app.blade.php`):

```html
@if(config('observatory.enabled'))
    <meta name="observatory-id" content="{{ app(\Performance\Observatory\ObservatoryManager::class)->getRequestId() }}">
    <meta name="observatory-endpoint" content="{{ url(config('observatory.route_prefix') . '/api/frontend-metrics') }}">
    <script src="{{ asset('vendor/observatory/tracker.js') }}" defer></script>
@endif
```

*(Note: We will add an artisan command to publish the `tracker.js` asset to your public directory in the next iteration)*.

---

## Accessing the Dashboard

The Performance Observatory comes with a built-in, premium dashboard to visualize your metrics. It uses a single-page architecture built right into the package without requiring any Node.js compilation on your end.

Simply navigate to the following URL in your browser:

```
http://your-app.test/observatory
```

*(You can customize the `/observatory` URL prefix in your published `config/observatory.php` file).*
