<?php

namespace Performance\Observatory\Collectors;

use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Http\Client\Events\ConnectionFailed;

class ApiCollector extends AbstractCollector
{
    protected $requests = [];

    public function getName(): string
    {
        return 'api';
    }

    public function boot(): void
    {
        $this->app['events']->listen(RequestSending::class, function (RequestSending $event) {
            $this->onRequestSending($event);
        });

        $this->app['events']->listen(ResponseReceived::class, function (ResponseReceived $event) {
            $this->onResponseReceived($event);
        });

        $this->app['events']->listen(ConnectionFailed::class, function (ConnectionFailed $event) {
            $this->onConnectionFailed($event);
        });
    }

    protected function onRequestSending(RequestSending $event): void
    {
        $hash = spl_object_hash($event->request);
        
        $this->requests[$hash] = [
            'url' => $event->request->url(),
            'method' => $event->request->method(),
            'headers' => $this->formatHeaders($event->request->headers()),
            'start_time' => microtime(true),
            'status' => 'pending',
            'duration' => 0,
            'is_internal' => $this->isInternalUrl($event->request->url()),
        ];
        
        $this->updateMetrics();
    }

    protected function onResponseReceived(ResponseReceived $event): void
    {
        $hash = spl_object_hash($event->request);
        
        if (isset($this->requests[$hash])) {
            $duration = (microtime(true) - $this->requests[$hash]['start_time']) * 1000; // in ms
            $this->requests[$hash]['status'] = $event->response->status();
            $this->requests[$hash]['duration'] = $duration;
            $this->requests[$hash]['response_headers'] = $this->formatHeaders($event->response->headers());
        }
        
        $this->updateMetrics();
    }

    protected function onConnectionFailed(ConnectionFailed $event): void
    {
        $hash = spl_object_hash($event->request);
        
        if (isset($this->requests[$hash])) {
            $duration = (microtime(true) - $this->requests[$hash]['start_time']) * 1000;
            $this->requests[$hash]['status'] = 'failed';
            $this->requests[$hash]['duration'] = $duration;
        }
        
        $this->updateMetrics();
    }

    protected function isInternalUrl(string $url): bool
    {
        $targetHost = parse_url($url, PHP_URL_HOST);
        if (!$targetHost) {
            return true; // relative URLs are internal
        }
        
        // Use container request to check current host
        try {
            $request = $this->app->make('request');
            $currentHost = $request ? $request->getHost() : 'localhost';
        } catch (\Exception $e) {
            $currentHost = 'localhost';
        }
        
        $appUrl = config('app.url');
        $appHost = $appUrl ? parse_url($appUrl, PHP_URL_HOST) : null;
        
        return in_array($targetHost, [
            $currentHost,
            $appHost,
            'localhost',
            '127.0.0.1',
            '::1'
        ], true);
    }

    protected function formatHeaders(array $headers): array
    {
        $formatted = [];
        foreach ($headers as $key => $value) {
            $formatted[$key] = is_array($value) ? implode(', ', $value) : $value;
        }
        return $formatted;
    }

    protected function updateMetrics(): void
    {
        $completedRequests = array_filter($this->requests, function ($req) {
            return $req['status'] !== 'pending';
        });

        $this->record('requests', array_values($this->requests));
        $this->record('total_time', array_sum(array_column($completedRequests, 'duration')));
        $this->record('total_queries', count($this->requests));
    }
}