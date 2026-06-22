<?php

namespace Performance\Observatory\Storage;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StorageManager
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function store(array $data): void
    {
        // Since this is called from Laravel's terminating() callback,
        // the response has already been sent to the browser.
        // We can safely save synchronously here without blocking the user.
        $this->saveToDatabase($data);
    }

    protected function saveToDatabase(array $data): void
    {
        try {
            $connection = config('observatory.storage.connection');
            
            // In a real implementation we would split the data into relational tables
            // For now, we will store the raw JSON payload in a single table for the MVP
            DB::connection($connection)->table('observatory_requests')->insert([
                'request_id' => $data['request_id'],
                'parent_request_id' => $data['metrics']['request']['parent_request_id'] ?? null,
                'url' => $data['metrics']['request']['url'] ?? 'unknown',
                'method' => $data['metrics']['request']['method'] ?? 'unknown',
                'total_duration' => $data['total_duration'],
                'metrics_payload' => json_encode($data['metrics']),
                'created_at' => $data['timestamp'],
            ]);
        } catch (\Exception $e) {
            Log::error('Performance Observatory failed to store metrics: ' . $e->getMessage());
        }
    }
}
