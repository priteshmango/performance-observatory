<?php

namespace Performance\Observatory\Engines;

class RootCauseEngine
{
    public function analyze(array $data): array
    {
        $causes = [];

        // Database Analysis
        if (isset($data['metrics']['database'])) {
            $db = $data['metrics']['database'];
            
            // Check for slow total DB time
            if ($db['total_time'] > 500) {
                $causes[] = [
                    'severity' => 'critical',
                    'message' => 'Database consumes a significant portion of request time (' . round($db['total_time']) . 'ms).',
                ];
            }

            // Check for N+1 issues
            if ($db['total_queries'] > 50) {
                $causes[] = [
                    'severity' => 'high',
                    'message' => 'High number of database queries (' . $db['total_queries'] . '). Possible N+1 issue.',
                ];
            }
        }

        // Backend Processing Analysis
        if (isset($data['total_duration'])) {
            if ($data['total_duration'] > 1.5) {
                $causes[] = [
                    'severity' => 'critical',
                    'message' => 'Total request duration is very slow (' . round($data['total_duration'], 2) . 's).',
                ];
            }
        }

        // Missing Indexes Placeholder (This would involve analyzing the EXPLAIN payload from DB collector)
        // ...

        // Sort by severity (critical, high, medium, low)
        // In a real app, we'd map severity to numeric scores to sort.

        return $causes;
    }
}
