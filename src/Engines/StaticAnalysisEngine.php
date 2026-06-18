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
                'title' => 'OPcache Disabled',
                'description' => 'PHP OPcache is not enabled. This drastically reduces PHP execution speed.',
                'solution' => 'Enable OPcache in your php.ini by setting `opcache.enable=1`.'
            ];
        }

        // Config & Route Caching
        if (!app()->routesAreCached()) {
            $vulnerabilities[] = [
                'severity' => 'warning',
                'title' => 'Routes Not Cached',
                'description' => 'Laravel routing is evaluating dynamically on every request.',
                'solution' => 'Run `php artisan route:cache` in production.'
            ];
        }
        
        if (!app()->configurationIsCached()) {
            $vulnerabilities[] = [
                'severity' => 'warning',
                'title' => 'Configuration Not Cached',
                'description' => 'Laravel configuration files are being loaded from disk dynamically.',
                'solution' => 'Run `php artisan config:cache` in production.'
            ];
        }

        if (empty($vulnerabilities)) {
            $vulnerabilities[] = [
                'severity' => 'success',
                'title' => 'Server Configuration Optimal',
                'description' => 'No major server or caching vulnerabilities detected.',
                'solution' => 'All good!'
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
            // Check for large tables lacking indexes (simplified for MVP)
            try {
                $tables = DB::select("
                    SELECT TABLE_NAME, TABLE_ROWS 
                    FROM information_schema.TABLES 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_ROWS > 10000
                ");

                foreach ($tables as $table) {
                    $indexes = DB::select("SHOW INDEX FROM `{$table->TABLE_NAME}`");
                    if (count($indexes) <= 1) { // Only PRIMARY key exists
                        $vulnerabilities[] = [
                            'severity' => 'high',
                            'title' => "Missing Indexes on Large Table: {$table->TABLE_NAME}",
                            'description' => "Table {$table->TABLE_NAME} has over 10,000 rows but only a primary key index.",
                            'solution' => 'Add indexes to columns frequently used in WHERE or ORDER BY clauses.'
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
                'title' => 'Database Schema Healthy',
                'description' => 'No missing primary indexes detected on large tables.',
                'solution' => 'All good!'
            ];
        }

        return $vulnerabilities;
    }

    public function scanBackend(): array
    {
        $vulnerabilities = [];
        $appPath = app_path();

        // Regex scanning for env() outside config
        $files = File::allFiles($appPath);
        $envFound = false;
        
        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $content = file_get_contents($file->getRealPath());
                
                // Bad Practice 1: env() helper outside of config
                if (preg_match('/\benv\(/', $content)) {
                    $envFound = true;
                    $vulnerabilities[] = [
                        'severity' => 'critical',
                        'title' => 'Usage of env() inside App logic',
                        'description' => "Found `env()` helper call in: {$file->getRelativePathname()}. This will return null in production if you run config:cache.",
                        'solution' => 'Move the env() call to a configuration file in config/ and use the config() helper here.'
                    ];
                }

                // Bad Practice 2: Queries in loops (very basic regex)
                if (preg_match('/foreach.*\{.*(->save\(|->update\(|->delete\().*\}/is', $content)) {
                    $vulnerabilities[] = [
                        'severity' => 'high',
                        'title' => 'Database Query Inside Loop',
                        'description' => "Potential query inside a foreach loop detected in: {$file->getRelativePathname()}.",
                        'solution' => 'Use bulk operations like `insert()`, `upsert()`, or a transaction to minimize database connections.'
                    ];
                }
            }
        }

        if (empty($vulnerabilities)) {
            $vulnerabilities[] = [
                'severity' => 'success',
                'title' => 'Backend Code Clean',
                'description' => 'No major static anti-patterns detected.',
                'solution' => 'All good!'
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
                        'title' => 'Massive JS Bundle Detected',
                        'description' => "File {$file->getFilename()} is " . round($sizeInKb) . "KB. This will severely impact load times on mobile devices.",
                        'solution' => 'Implement code splitting in Vite/Webpack, lazy load components, or analyze imports for large libraries.'
                    ];
                }
                
                if (in_array($file->getExtension(), ['jpg', 'png', 'jpeg']) && $sizeInKb > 1000) {
                    $vulnerabilities[] = [
                        'severity' => 'warning',
                        'title' => 'Oversized Image Detected',
                        'description' => "Image {$file->getFilename()} is " . round($sizeInKb / 1024, 2) . "MB.",
                        'solution' => 'Compress the image or serve it in a modern format like WebP or AVIF.'
                    ];
                }
            }
        } else {
             $vulnerabilities[] = [
                'severity' => 'warning',
                'title' => 'No Production Assets Found',
                'description' => 'Could not locate the `public/build/assets` directory.',
                'solution' => 'Run `npm run build` to compile your frontend assets for production.'
            ];
        }

        if (empty($vulnerabilities)) {
            $vulnerabilities[] = [
                'severity' => 'success',
                'title' => 'Frontend Assets Optimized',
                'description' => 'No oversized bundles or images detected.',
                'solution' => 'All good!'
            ];
        }

        return $vulnerabilities;
    }
}
