<?php

namespace Performance\Observatory\Engines;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class StaticAnalysisEngine
{
    public function scanServer(): array
    {
        $vulnerabilities = [];

        // Debug Mode Check (Production Risk)
        if (config('app.debug')) {
            $vulnerabilities[] = [
                'severity' => 'critical',
                'title' => 'Debug Mode Enabled in Production',
                'description' => 'Your application has APP_DEBUG=true. This is a severe security vulnerability (exposing env variables and database details on errors) and a major performance killer, as Laravel has to collect extra telemetry on every request.',
                'solution' => 'Change `APP_DEBUG=true` to `APP_DEBUG=false` in your production .env file immediately.'
            ];
        }

        // OPcache Check
        if (!function_exists('opcache_get_status') || !opcache_get_status()) {
            $vulnerabilities[] = [
                'severity' => 'critical',
                'title' => 'Missing OPcache (Huge Speed Loss)',
                'description' => 'Your server is currently recompiling your PHP code on every single page load. This is a massive waste of server resources and slows down your website tremendously.',
                'solution' => 'Please ask your hosting provider or server admin to "Enable PHP OPcache". This one change will instantly double the speed of your application.'
            ];
        }

        // Synchronous Queue Check (Blocks user requests)
        if (config('queue.default') === 'sync') {
            $vulnerabilities[] = [
                'severity' => 'warning',
                'title' => 'Synchronous Queue Driver Active',
                'description' => 'Your application is using the "sync" queue driver. This means background jobs (like sending emails, processing uploads, or sending webhooks) are run during the request, making your users wait for them to finish.',
                'solution' => 'Configure a production-ready queue driver like `redis` or `database` by setting `QUEUE_CONNECTION=redis` in your .env file, and run queue workers.'
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

        // Check for Database Cache Driver (Performance Killer)
        if (config('cache.default') === 'database') {
            $vulnerabilities[] = [
                'severity' => 'critical',
                'title' => 'Database Cache Driver in Use (Severe Bottleneck)',
                'description' => 'You are using your relational database to store cache data. This causes massive locking issues and turns fast cache lookups into 700ms+ slow queries during heavy traffic.',
                'solution' => 'Change `CACHE_DRIVER=database` to `CACHE_DRIVER=redis` or `memcached` in your .env file immediately.'
            ];
        }

        // Check for Database Session Driver
        if (config('session.driver') === 'database') {
            $vulnerabilities[] = [
                'severity' => 'high',
                'title' => 'Database Session Driver',
                'description' => 'You are storing user sessions in the database. Every single page click forces a database write, crippling performance.',
                'solution' => 'Change `SESSION_DRIVER=database` to `SESSION_DRIVER=redis` or `cookie` in your .env file.'
            ];
        }

        if ($driver === 'mysql') {
            try {
                $tables = DB::select("
                    SELECT TABLE_NAME, TABLE_ROWS 
                    FROM information_schema.TABLES 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_ROWS > 1000
                ");

                foreach ($tables as $table) {
                    $indexes = DB::select("SHOW INDEX FROM `{$table->TABLE_NAME}`");
                    if (count($indexes) <= 1) { 
                        $vulnerabilities[] = [
                            'severity' => 'warning',
                            'title' => "Dangerous Database Table: {$table->TABLE_NAME}",
                            'description' => "The database table '{$table->TABLE_NAME}' has grown very large, but it is missing 'Indexes'. Without indexes, the database has to scan every single row one-by-one to find data.",
                            'solution' => "Please create a database migration and add an index to the columns you frequently search by. Example code: `$table->index('user_id');`"
                        ];
                    }
                }
            } catch (\Exception $e) {
                // Ignore if permissions fail
            }
        } elseif ($driver === 'pgsql') {
            try {
                $tables = DB::select("
                    SELECT relname AS table_name, n_live_tup AS table_rows
                    FROM pg_stat_user_tables
                    WHERE n_live_tup > 1000
                ");

                foreach ($tables as $table) {
                    $indexCountResult = DB::selectOne("
                        SELECT count(*) as count 
                        FROM pg_index 
                        WHERE indrelid = :table::regclass
                    ", ['table' => $table->table_name]);
                    
                    $indexCount = $indexCountResult ? (int) $indexCountResult->count : 0;

                    if ($indexCount <= 1) { 
                        $vulnerabilities[] = [
                            'severity' => 'warning',
                            'title' => "Dangerous Database Table: {$table->table_name}",
                            'description' => "The database table '{$table->table_name}' has grown very large, but it has only {$indexCount} index(es) (likely just the primary key). Without indexes on searched columns, queries will scan the entire table.",
                            'solution' => "Please create a database migration and add an index to columns frequently used in WHERE, JOIN, or ORDER BY clauses."
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
                'description' => 'We did not find any dangerously large un-indexed tables or bad configuration drivers.',
                'solution' => 'No database structure changes are needed right now.'
            ];
        }

        return $vulnerabilities;
    }

    public function scanBackend(): array
    {
        $vulnerabilities = [];
        $appPath = app_path();

        if (!File::exists($appPath)) {
            return [];
        }

        $files = File::allFiles($appPath);
        
        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                // Skip very large files (e.g. autogenerated stubs, libraries) to prevent timeouts
                if ($file->getSize() > 100 * 1024) {
                    continue;
                }

                $lines = file($file->getRealPath());
                if (!$lines) {
                    continue;
                }
                
                foreach ($lines as $lineNumber => $line) {
                    $actualLine = $lineNumber + 1;
                    $trimmedLine = trim($line);

                    // Skip comment lines to avoid false positives
                    if (str_starts_with($trimmedLine, '//') || 
                        str_starts_with($trimmedLine, '#') || 
                        str_starts_with($trimmedLine, '*') || 
                        str_starts_with($trimmedLine, '/*') || 
                        str_starts_with($trimmedLine, '*/')) {
                        continue;
                    }

                    // Bad Practice 1: env() helper outside of config
                    if (preg_match('/\benv\(/', $line)) {
                        $vulnerabilities[] = [
                            'severity' => 'critical',
                            'title' => 'Fatal Error Risk: env() helper used directly',
                            'description' => "We found the `env()` function being used inside your code here: `{$file->getRelativePathname()}` on Line {$actualLine}. If you optimize your server, this function will start returning NULL and break your live site.",
                            'solution' => 'Please open that file, go to line ' . $actualLine . ', and replace the `env("SOMETHING")` call with `config("app.something")`. Then put the env call inside your config/app.php file.'
                        ];
                    }

                    // Bad Practice 2: Queries / writes in loops
                    if (preg_match('/(->save\(|->update\(|->delete\(|::create\(|::updateOrCreate\(|::firstOrCreate\(|DB::table\(|DB::insert\(|DB::update\()/', $line)) {
                        // Context check: check the previous 10 lines for loops
                        $hasLoop = false;
                        for ($i = max(0, $lineNumber - 10); $i < $lineNumber; $i++) {
                            if (strpos($lines[$i], 'foreach') !== false || strpos($lines[$i], 'while') !== false || strpos($lines[$i], 'for (') !== false) {
                                $hasLoop = true;
                                break;
                            }
                        }
                        
                        if ($hasLoop) {
                            $vulnerabilities[] = [
                                'severity' => 'high',
                                'title' => 'Website Freezing Loop Detected',
                                'description' => "We detected database writing near a loop in this file: `{$file->getRelativePathname()}` around Line {$actualLine}. This means if you have 100 items, it makes 100 separate database connections.",
                                'solution' => 'Please refactor this code. Instead of saving inside the loop, prepare an array of data and use Laravel\'s bulk `insert()` or `upsert()` method outside the loop.'
                            ];
                        }
                    }
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
        
        $potentialPaths = [
            public_path('build/assets'),
            public_path('assets'),
            public_path('dist'),
            public_path('css'),
            public_path('js')
        ];

        $assetsFound = false;
        
        foreach ($potentialPaths as $publicPath) {
            if (File::exists($publicPath)) {
                $files = File::allFiles($publicPath);
                if (count($files) > 0) {
                    $assetsFound = true;
                }
                
                foreach ($files as $file) {
                    $sizeInKb = $file->getSize() / 1024;
                    
                    // JS File check (> 500KB)
                    if ($file->getExtension() === 'js' && $sizeInKb > 500) {
                        $vulnerabilities[] = [
                            'severity' => 'high',
                            'title' => "Massive Javascript File: {$file->getFilename()} (Slow Mobile Load)",
                            'description' => "The file `{$file->getFilename()}` is very large (" . round($sizeInKb) . "KB). Users on 3G or weak mobile connections will experience long delays.",
                            'solution' => 'Please configure your Vite/Webpack to "Code Split" this file into smaller chunks, or remove heavy libraries you are no longer using.'
                        ];
                    }
                    
                    // CSS File check (> 250KB)
                    if ($file->getExtension() === 'css' && $sizeInKb > 250) {
                        $vulnerabilities[] = [
                            'severity' => 'warning',
                            'title' => "Bloated CSS File: {$file->getFilename()} (Blocks Rendering)",
                            'description' => "The stylesheet `{$file->getFilename()}` is very large (" . round($sizeInKb) . "KB) and delays page rendering as it is render-blocking.",
                            'solution' => 'Minify the CSS, purge unused CSS rules using tools like PurgeCSS, or split styles by page route.'
                        ];
                    }
                    
                    // Image check (> 1MB)
                    if (in_array($file->getExtension(), ['jpg', 'png', 'jpeg']) && $sizeInKb > 1000) {
                        $vulnerabilities[] = [
                            'severity' => 'warning',
                            'title' => "Giant Unoptimized Image: {$file->getFilename()}",
                            'description' => "The image `{$file->getFilename()}` is heavily bloated (" . round($sizeInKb / 1024, 2) . "MB) and hurts page load speed.",
                            'solution' => 'Compress the image using TinyPNG or similar tools, resize it to display size, or convert it to a modern format like WebP/AVIF.'
                        ];
                    }
                }
            }
        }

        if (!$assetsFound) {
             $vulnerabilities[] = [
                'severity' => 'warning',
                'title' => 'Missing Compiled Assets',
                'description' => 'We could not find production Javascript/CSS assets in common asset folders.',
                'solution' => 'Run `npm run build` in your project terminal to generate production-ready assets.'
            ];
        }

        if (empty($vulnerabilities)) {
            $vulnerabilities[] = [
                'severity' => 'success',
                'title' => 'Frontend is Blazing Fast',
                'description' => 'Your images and asset files are perfectly sized and compressed.',
                'solution' => 'Your website is ready to deliver a fast experience to end users.'
            ];
        }

        return $vulnerabilities;
    }
}
