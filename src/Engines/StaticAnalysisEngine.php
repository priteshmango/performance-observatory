<?php

namespace Performance\Observatory\Engines;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class StaticAnalysisEngine
{
    public function scanServer(): array
    {
        $vulnerabilities = [];

        // OPcache Check
        if (!function_exists('opcache_get_status') || !opcache_get_status()) {
            $vulnerabilities[] = [
                'severity' => 'critical',
                'title' => 'Missing OPcache (Huge Speed Loss)',
                'description' => 'Your server is currently recompiling your PHP code on every single page load. This is a massive waste of server resources and slows down your website tremendously.',
                'solution' => 'Please ask your hosting provider or server admin to "Enable PHP OPcache". This one change will instantly double the speed of your application.'
            ];
        }

        // Config & Route Caching
        if (!app()->routesAreCached()) {
            $vulnerabilities[] = [
                'severity' => 'warning',
                'title' => 'Uncached Routes (Slow Page Loads)',
                'description' => 'Laravel is having to read and register all of your website\'s URLs dynamically every time someone visits a page. This causes a noticeable delay.',
                'solution' => 'Open your terminal/command prompt and run this exact command: `php artisan route:cache`. This bundles your routes into a fast file.'
            ];
        }
        
        if (!app()->configurationIsCached()) {
            $vulnerabilities[] = [
                'severity' => 'warning',
                'title' => 'Uncached Configuration Files',
                'description' => 'Your application is reading raw configuration files from the disk continuously. This disk reading slows down your server response times.',
                'solution' => 'Open your terminal and run: `php artisan config:cache`. This combines all config settings into a lightning-fast memory cache.'
            ];
        }

        if (empty($vulnerabilities)) {
            $vulnerabilities[] = [
                'severity' => 'success',
                'title' => 'Server is Highly Optimized',
                'description' => 'Your server configuration is excellent. All caching mechanisms are active.',
                'solution' => 'Great job! Your server foundation is rock solid.'
            ];
        }

        return $vulnerabilities;
    }

    public function scanDatabase(): array
    {
        $vulnerabilities = [];
        $connection = DB::connection();
        $driver = $connection->getDriverName();

        if ($driver === 'mysql') {
            try {
                $tables = DB::select("
                    SELECT TABLE_NAME, TABLE_ROWS 
                    FROM information_schema.TABLES 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_ROWS > 10000
                ");

                foreach ($tables as $table) {
                    $indexes = DB::select("SHOW INDEX FROM `{$table->TABLE_NAME}`");
                    if (count($indexes) <= 1) { 
                        $vulnerabilities[] = [
                            'severity' => 'high',
                            'title' => "Dangerous Database Table: {$table->TABLE_NAME}",
                            'description' => "The database table '{$table->TABLE_NAME}' has grown very large (over 10,000 rows), but it is missing 'Indexes'. Without indexes, the database has to scan every single row one-by-one to find data, which will freeze your website during heavy traffic.",
                            'solution' => "Please create a database migration and add an index to the columns you frequently search by. Example code: `$table->index('user_id');`"
                        ];
                    }
                }
            } catch (\Exception $e) {
                // Ignore if permissions fail
            }
        }

        if (empty($vulnerabilities)) {
            $vulnerabilities[] = [
                'severity' => 'success',
                'title' => 'Database is Optimized',
                'description' => 'We did not find any dangerously large un-indexed tables.',
                'solution' => 'No database structure changes are needed right now.'
            ];
        }

        return $vulnerabilities;
    }

    public function scanBackend(): array
    {
        $vulnerabilities = [];
        $appPath = app_path();

        $files = File::allFiles($appPath);
        
        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $content = file_get_contents($file->getRealPath());
                
                // Bad Practice 1: env() helper outside of config
                if (preg_match('/\benv\(/', $content)) {
                    $vulnerabilities[] = [
                        'severity' => 'critical',
                        'title' => 'Fatal Error Risk: env() helper used directly',
                        'description' => "We found the `env()` function being used inside your code here: `{$file->getRelativePathname()}`. If you optimize your server (which you should!), this function will start returning NULL and break your live site.",
                        'solution' => 'Please open that file and replace the `env("SOMETHING")` call with `config("app.something")`. Then put the env call inside your config/app.php file.'
                    ];
                }

                // Bad Practice 2: Queries in loops
                if (preg_match('/foreach.*\{.*(->save\(|->update\(|->delete\().*\}/is', $content)) {
                    $vulnerabilities[] = [
                        'severity' => 'high',
                        'title' => 'Website Freezing Loop Detected',
                        'description' => "We detected database writing inside a loop in this file: `{$file->getRelativePathname()}`. This means if you have 100 items, it makes 100 separate database connections, which will cripple your database server.",
                        'solution' => 'Please refactor this code. Instead of saving inside the loop, prepare an array of data and use Laravel\'s bulk `insert()` or `upsert()` method outside the loop.'
                    ];
                }
            }
        }

        if (empty($vulnerabilities)) {
            $vulnerabilities[] = [
                'severity' => 'success',
                'title' => 'Backend Code is Clean',
                'description' => 'We did not find any major performance-killing coding mistakes.',
                'solution' => 'Your Laravel PHP code follows good performance standards.'
            ];
        }

        return $vulnerabilities;
    }

    public function scanFrontend(): array
    {
        $vulnerabilities = [];
        $publicPath = public_path('build/assets');

        if (File::exists($publicPath)) {
            $files = File::allFiles($publicPath);
            foreach ($files as $file) {
                $sizeInKb = $file->getSize() / 1024;
                if ($file->getExtension() === 'js' && $sizeInKb > 500) {
                    $vulnerabilities[] = [
                        'severity' => 'high',
                        'title' => 'Massive Javascript File (Slow Mobile Load)',
                        'description' => "The file `{$file->getFilename()}` is very large (" . round($sizeInKb) . "KB). Users on 3G or weak mobile connections will have to stare at a blank screen for a long time while this downloads.",
                        'solution' => 'Please configure your Vite/Webpack to "Code Split" this file into smaller chunks, or remove heavy libraries you are no longer using.'
                    ];
                }
                
                if (in_array($file->getExtension(), ['jpg', 'png', 'jpeg']) && $sizeInKb > 1000) {
                    $vulnerabilities[] = [
                        'severity' => 'warning',
                        'title' => 'Giant Unoptimized Image',
                        'description' => "The image `{$file->getFilename()}` is heavily bloated (" . round($sizeInKb / 1024, 2) . "MB) and is hurting your Google PageSpeed score.",
                        'solution' => 'Please run this image through an online compressor (like TinyPNG) or convert it to modern WebP format.'
                    ];
                }
            }
        } else {
             $vulnerabilities[] = [
                'severity' => 'warning',
                'title' => 'Missing Compiled Assets',
                'description' => 'We could not find your production Javascript/CSS files.',
                'solution' => 'Open your terminal and run `npm run build` so your website loads the minified production files instead of the slow development files.'
            ];
        }

        if (empty($vulnerabilities)) {
            $vulnerabilities[] = [
                'severity' => 'success',
                'title' => 'Frontend is Blazing Fast',
                'description' => 'Your images and Javascript files are perfectly sized and compressed.',
                'solution' => 'Your website is ready to deliver a fast experience to end users.'
            ];
        }

        return $vulnerabilities;
    }
}
