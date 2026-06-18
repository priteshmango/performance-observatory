<?php

namespace Performance\Observatory\Engines;

class RootCauseEngine
{
    public function analyze(array $metrics, float $totalDuration): array
    {
        $insights = [];

        // 1. Boot Time Analysis
        $bootTime = $metrics['request']['boot_duration'] ?? 0;
        if ($bootTime > 0.2) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Slow Framework Boot',
                'description' => 'Laravel took ' . round($bootTime * 1000) . 'ms just to boot up. This is usually caused by heavy service providers or un-cached configurations.',
                'solution' => 'Run `php artisan optimize` or `php artisan config:cache` and `php artisan route:cache` on your production server.'
            ];
        }

        // 2. Database Analysis
        if (isset($metrics['database'])) {
            $db = $metrics['database'];
            
            // N+1 Query Detection (Duplicate Queries)
            if (!empty($db['queries'])) {
                $queryCounts = array_count_values(array_column($db['queries'], 'sql'));
                foreach ($queryCounts as $sql => $count) {
                    if ($count > 3) {
                        $insights[] = [
                            'type' => 'critical',
                            'title' => 'N+1 Query Problem Detected',
                            'description' => "The identical query was executed {$count} times during this request.",
                            'solution' => 'Use Eloquent Eager Loading (`with()`) to load relationships upfront instead of lazy loading them in a loop.'
                        ];
                        break; // Only show one N+1 warning to avoid clutter
                    }
                }
            }

            // Slow Queries
            if (!empty($db['queries'])) {
                foreach ($db['queries'] as $query) {
                    if ($query['time'] > 100) {
                        $insights[] = [
                            'type' => 'critical',
                            'title' => 'Slow Database Query',
                            'description' => 'A query took ' . round($query['time']) . 'ms to execute.',
                            'solution' => 'Analyze the query using `EXPLAIN`. You likely need to add a database index to the columns used in the WHERE or ORDER BY clauses.'
                        ];
                        break;
                    }
                }
            }

            if ($db['total_queries'] > 30 && ($metrics['cache']['hits'] ?? 0) === 0) {
                $insights[] = [
                    'type' => 'warning',
                    'title' => 'High DB Load & No Cache',
                    'description' => "Executed {$db['total_queries']} queries but no cache hits were recorded.",
                    'solution' => 'Implement Redis or Memcached using `Cache::remember()` for frequently accessed data that rarely changes.'
                ];
            }
        }

        // 3. Application Execution / Payload Analysis
        $payloadSize = $metrics['request']['payload_size'] ?? 0;
        if ($payloadSize > 500000) { // 500kb
            $insights[] = [
                'type' => 'warning',
                'title' => 'Massive Payload Size',
                'description' => 'The server processed or returned over ' . round($payloadSize / 1024) . 'KB of data.',
                'solution' => 'Implement pagination, select only specific columns from the database, or compress the response.'
            ];
        }

        if (empty($insights)) {
            $insights[] = [
                'type' => 'success',
                'title' => 'Optimal Performance',
                'description' => 'No major bottlenecks detected in this request.',
                'solution' => 'Keep up the good work!'
            ];
        }

        return $insights;
    }
}
