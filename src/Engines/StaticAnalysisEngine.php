<?php

namespace Performance\Observatory\Engines;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class StaticAnalysisEngine
{
    public function scanServer(): array
    {
        $checks = [];

        // 1. Debug Mode Check
        if (config('app.debug')) {
            $checks[] = [
                'severity' => 'critical',
                'title' => 'Laravel Debug Mode',
                'description' => 'Debug mode is ENABLED (APP_DEBUG=true). This exposes sensitive environment credentials, database passwords, and telemetry data. It also significantly slows down response times.',
                'solution' => 'Change `APP_DEBUG=true` to `APP_DEBUG=false` in your production .env file.'
            ];
        } else {
            $checks[] = [
                'severity' => 'success',
                'title' => 'Laravel Debug Mode',
                'description' => 'Debug mode is DISABLED (Safe for production).',
                'solution' => ''
            ];
        }

        // 2. OPcache Check
        if (!function_exists('opcache_get_status') || !opcache_get_status()) {
            $checks[] = [
                'severity' => 'critical',
                'title' => 'PHP OPcache Caching',
                'description' => 'OPcache is DISABLED or not working on CLI. Your server is recompiling your PHP scripts on every single request, causing high CPU load and slow execution.',
                'solution' => 'Enable OPcache by setting `opcache.enable=1` and `opcache.enable_cli=1` in your php.ini configuration.'
            ];
        } else {
            $status = opcache_get_status(false);
            $cachedFiles = is_array($status) && isset($status['scripts']) ? count($status['scripts']) : 0;
            $checks[] = [
                'severity' => 'success',
                'title' => 'PHP OPcache Caching',
                'description' => 'OPcache is ENABLED and caching compiled PHP bytecode (' . ($cachedFiles ?: 'active') . ' scripts currently cached).',
                'solution' => ''
            ];
        }

        // 3. Queue Driver Check
        $queueDriver = config('queue.default');
        if ($queueDriver === 'sync') {
            $checks[] = [
                'severity' => 'warning',
                'title' => 'Asynchronous Queue System',
                'description' => 'The default queue connection is set to `sync`. Background jobs (such as emails, notifications, and webhooks) are run during the request, forcing your users to wait.',
                'solution' => 'Configure a production-ready queue driver like `redis` or `database` by setting `QUEUE_CONNECTION=redis` in your .env file, and run queue workers.'
            ];
        } else {
            $checks[] = [
                'severity' => 'success',
                'title' => 'Asynchronous Queue System',
                'description' => "Queue connection is configured to run asynchronously using the `{$queueDriver}` driver.",
                'solution' => ''
            ];
        }

        // 4. Route Caching Check
        if (app()->routesAreCached()) {
            $checks[] = [
                'severity' => 'success',
                'title' => 'Route Cache Optimization',
                'description' => 'Application routes are cached and compiled, resulting in faster URL matching.',
                'solution' => ''
            ];
        } else {
            $checks[] = [
                'severity' => 'warning',
                'title' => 'Route Cache Optimization',
                'description' => 'Routes are not cached. Laravel must parse and register all routes on every single request page load.',
                'solution' => 'Run `php artisan route:cache` on your production server.'
            ];
        }

        // 5. Configuration Caching Check
        if (app()->configurationIsCached()) {
            $checks[] = [
                'severity' => 'success',
                'title' => 'Configuration Cache Optimization',
                'description' => 'Configuration files are cached and compiled into a single file.',
                'solution' => ''
            ];
        } else {
            $checks[] = [
                'severity' => 'warning',
                'title' => 'Configuration Cache Optimization',
                'description' => 'Configuration files are uncached. The application reads raw files from the disk continuously.',
                'solution' => 'Run `php artisan config:cache` on your production server.'
            ];
        }

        return $checks;
    }

    public function scanDatabase(): array
    {
        $checks = [];
        $connection = DB::connection();
        $driver = $connection->getDriverName();

        // 1. Cache Driver Check
        $cacheDriver = config('cache.default');
        if ($cacheDriver === 'database') {
            $checks[] = [
                'severity' => 'critical',
                'title' => 'Fast Caching System',
                'description' => 'You are using your relational database (`database`) to store cache data. This causes row locking issues and turns cache reads/writes into heavy database queries during traffic spikes.',
                'solution' => 'Change `CACHE_DRIVER=database` to `CACHE_DRIVER=redis` or `memcached` in your .env file.'
            ];
        } else {
            $checks[] = [
                'severity' => 'success',
                'title' => 'Fast Caching System',
                'description' => "Cache system is configured to use the high-performance `{$cacheDriver}` driver.",
                'solution' => ''
            ];
        }

        // 2. Session Driver Check
        $sessionDriver = config('session.driver');
        if ($sessionDriver === 'database') {
            $checks[] = [
                'severity' => 'warning',
                'title' => 'Session Storage Driver',
                'description' => 'You are storing user session state in the database. Every single page load or API request forces a database write, creating unnecessary database load.',
                'solution' => 'Change `SESSION_DRIVER=database` to `SESSION_DRIVER=redis` or `cookie` in your .env file.'
            ];
        } else {
            $checks[] = [
                'severity' => 'success',
                'title' => 'Session Storage Driver',
                'description' => "Session storage is configured to use the `{$sessionDriver}` driver.",
                'solution' => ''
            ];
        }

        // 3. Database Table Indexes Check
        $indexedTablesPassed = true;
        $unindexedTables = [];
        
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
                        $indexedTablesPassed = false;
                        $unindexedTables[] = [
                            'table' => $table->TABLE_NAME,
                            'rows' => $table->TABLE_ROWS
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
                        $indexedTablesPassed = false;
                        $unindexedTables[] = [
                            'table' => $table->table_name,
                            'rows' => $table->table_rows
                        ];
                    }
                }
            } catch (\Exception $e) {
                // Ignore if permissions fail
            }
        }

        if ($indexedTablesPassed) {
            $checks[] = [
                'severity' => 'success',
                'title' => 'Database Table Indexing',
                'description' => 'All scanned tables have proper indexes configured (no large tables are missing indexes).',
                'solution' => ''
            ];
        } else {
            foreach ($unindexedTables as $tbl) {
                $checks[] = [
                    'severity' => 'warning',
                    'title' => "Missing Indexes on Table: {$tbl['table']}",
                    'description' => "The database table '{$tbl['table']}' has grown large ({$tbl['rows']} rows) but is missing indexes (except possibly the primary key). Queries searching this table will run table scans.",
                    'solution' => "Create a database migration to add indexes to columns frequently used in WHERE or JOIN clauses. E.g., `$table->index('column_name');`"
                ];
            }
        }

        return $checks;
    }

    public function scanBackend(): array
    {
        $checks = [];
        $appPath = app_path();

        if (!File::exists($appPath)) {
            return [];
        }

        $files = File::allFiles($appPath);
        $directEnvCalls = [];
        $loopsWithQueries = [];
        
        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
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

                    if (str_starts_with($trimmedLine, '//') || 
                        str_starts_with($trimmedLine, '#') || 
                        str_starts_with($trimmedLine, '*') || 
                        str_starts_with($trimmedLine, '/*') || 
                        str_starts_with($trimmedLine, '*/')) {
                        continue;
                    }

                    // Direct env() calls
                    if (preg_match('/\benv\(/', $line)) {
                        $directEnvCalls[] = [
                            'file' => $file->getRelativePathname(),
                            'line' => $actualLine
                        ];
                    }

                    // DB queries in loops
                    if (preg_match('/(->save\(|->update\(|->delete\(|::create\(|::updateOrCreate\(|::firstOrCreate\(|DB::table\(|DB::insert\(|DB::update\()/', $line)) {
                        $hasLoop = false;
                        for ($i = max(0, $lineNumber - 10); $i < $lineNumber; $i++) {
                            if (strpos($lines[$i], 'foreach') !== false || strpos($lines[$i], 'while') !== false || strpos($lines[$i], 'for (') !== false) {
                                $hasLoop = true;
                                break;
                            }
                        }
                        
                        if ($hasLoop) {
                            $loopsWithQueries[] = [
                                'file' => $file->getRelativePathname(),
                                'line' => $actualLine
                            ];
                        }
                    }
                }
            }
        }

        // Add Check: Direct env() calls
        if (empty($directEnvCalls)) {
            $checks[] = [
                'severity' => 'success',
                'title' => 'Safe Environment Helper Usage',
                'description' => 'No direct env() function helper calls were found outside configuration files.',
                'solution' => ''
            ];
        } else {
            foreach ($directEnvCalls as $call) {
                $checks[] = [
                    'severity' => 'critical',
                    'title' => "Direct env() Call: {$call['file']}",
                    'description' => "Found `env()` helper function call on Line {$call['line']}. In cached production mode, this will return NULL and cause errors.",
                    'solution' => "Replace `env('KEY')` with `config('file.key')` and place the `env()` call inside your config files."
                ];
            }
        }

        // Add Check: Database loops
        if (empty($loopsWithQueries)) {
            $checks[] = [
                'severity' => 'success',
                'title' => 'No Database Queries in Loops',
                'description' => 'No database inserts, updates, or query operations were detected inside loops.',
                'solution' => ''
            ];
        } else {
            foreach ($loopsWithQueries as $loop) {
                $checks[] = [
                    'severity' => 'warning',
                    'title' => "Database Operation in Loop: {$loop['file']}",
                    'description' => "Database write or query operation detected inside or near a loop on Line {$loop['line']}. This causes a massive performance bottleneck.",
                    'solution' => 'Refactor the loop to collect data in memory first, then execute a bulk database operation (like insert() or upsert()) outside the loop.'
                ];
            }
        }

        return $checks;
    }

    public function scanFrontend(): array
    {
        $checks = [];
        
        $potentialPaths = [
            public_path('build/assets'),
            public_path('assets'),
            public_path('dist'),
            public_path('css'),
            public_path('js')
        ];

        $assetsFound = false;
        $largeJsFiles = [];
        $largeCssFiles = [];
        $largeImages = [];
        
        foreach ($potentialPaths as $publicPath) {
            if (File::exists($publicPath)) {
                $files = File::allFiles($publicPath);
                if (count($files) > 0) {
                    $assetsFound = true;
                }
                
                foreach ($files as $file) {
                    $sizeInKb = $file->getSize() / 1024;
                    
                    if ($file->getExtension() === 'js' && $sizeInKb > 500) {
                        $largeJsFiles[] = [
                            'name' => $file->getFilename(),
                            'size' => $sizeInKb
                        ];
                    }
                    
                    if ($file->getExtension() === 'css' && $sizeInKb > 250) {
                        $largeCssFiles[] = [
                            'name' => $file->getFilename(),
                            'size' => $sizeInKb
                        ];
                    }
                    
                    if (in_array($file->getExtension(), ['jpg', 'png', 'jpeg']) && $sizeInKb > 1000) {
                        $largeImages[] = [
                            'name' => $file->getFilename(),
                            'size' => $sizeInKb
                        ];
                    }
                }
            }
        }

        // 1. Assets Compiled Check
        if ($assetsFound) {
            $checks[] = [
                'severity' => 'success',
                'title' => 'Production Assets Status',
                'description' => 'Compiled CSS/JS production assets were successfully located in public assets folders.',
                'solution' => ''
            ];
        } else {
            $checks[] = [
                'severity' => 'warning',
                'title' => 'Production Assets Status',
                'description' => 'Could not find compiled production Javascript/CSS assets in common asset folders.',
                'solution' => 'Run `npm run build` in your project terminal to generate production assets.'
            ];
        }

        // 2. Javascript Size Check
        if (empty($largeJsFiles)) {
            $checks[] = [
                'severity' => 'success',
                'title' => 'Javascript Bundle Sizes',
                'description' => 'All frontend Javascript bundles are optimally sized (under 500KB).',
                'solution' => ''
            ];
        } else {
            foreach ($largeJsFiles as $js) {
                $checks[] = [
                    'severity' => 'warning',
                    'title' => "Large JS Asset: {$js['name']}",
                    'description' => "The Javascript file size is " . round($js['size']) . "KB (exceeds recommended 500KB threshold). This can slow down initial page loads on mobile.",
                    'solution' => 'Enable code-splitting, dynamic imports, or remove unused libraries in your Vite/Webpack config.'
                ];
            }
        }

        // 3. CSS Size Check
        if (empty($largeCssFiles)) {
            $checks[] = [
                'severity' => 'success',
                'title' => 'CSS Bundle Sizes',
                'description' => 'All stylesheets are optimally sized (under 250KB).',
                'solution' => ''
            ];
        } else {
            foreach ($largeCssFiles as $css) {
                $checks[] = [
                    'severity' => 'warning',
                    'title' => "Large CSS Asset: {$css['name']}",
                    'description' => "The stylesheet size is " . round($css['size']) . "KB (exceeds recommended 250KB threshold). Large CSS files block rendering.",
                    'solution' => 'Purge unused CSS rules using PurgeCSS/Tailwind configuration, or separate styles by page route.'
                ];
            }
        }

        // 4. Image size check
        if (empty($largeImages)) {
            $checks[] = [
                'severity' => 'success',
                'title' => 'Image Asset Optimization',
                'description' => 'All checked public image assets are optimized (under 1MB).',
                'solution' => ''
            ];
        } else {
            foreach ($largeImages as $img) {
                $checks[] = [
                    'severity' => 'warning',
                    'title' => "Giant Image Asset: {$img['name']}",
                    'description' => "The image file size is " . round($img['size'] / 1024, 2) . "MB (exceeds recommended 1MB threshold).",
                    'solution' => 'Compress the image (e.g. TinyPNG), resize it, or convert it to a modern format like WebP/AVIF.'
                ];
            }
        }

        return $checks;
    }
}
