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
        if (config('observatory.storage.async', true) && function_exists('defer')) {
            \defer(function () use ($data) {
                $this->saveToDatabase($data);
            });
        } else {
            // Synchronous fallback or terminable middleware handles it
            $this->saveToDatabase($data);
        }
    }

    protected function saveToDatabase(array $data): void
    {
        try {
            $connection = config('observatory.storage.connection');
            
            // In a real implementation we would split the data into relational tables
            // For now, we will store the raw JSON payload in a single table for the MVP
            DB::connection($connection)->table('observatory_requests')->insert([
                'request_id' => $data['request_id'],
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
