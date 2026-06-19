<?php

namespace Performance\Observatory\Collectors;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RequestCollector extends AbstractCollector
{
    public function getName(): string
    {
        return 'request';
    }

    public function boot(): void
    {
        $request = $this->app->make('request');
        
        $laravelStart = defined('LARAVEL_START') ? LARAVEL_START : $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
        $bootDuration = microtime(true) - $laravelStart;
        
        $this->record('laravel_start', $laravelStart);
        $this->record('boot_duration', $bootDuration);
        $this->record('start_time', $this->manager->getStartTime());
        $this->record('method', $request->method());
        
        $requestType = 'Page Load';
        if ($request->ajax() || $request->wantsJson()) {
            $requestType = 'AJAX / API';
        }
        $this->record('request_type', $requestType);
        
        $this->record('url', $request->fullUrl());
        $this->record('ip', $request->ip());
        $this->record('headers', $this->formatHeaders($request->headers->all()));
        $this->record('payload_size', strlen($request->getContent()));

        // Listen for the response to get final metrics
        $this->app['events']->listen('kernel.handled', function ($event) {
            $this->onHandled($event->request, $event->response);
        });
    }

    protected function onHandled(Request $request, $response): void
    {
        $this->record('end_time', microtime(true));
        $this->record('duration', microtime(true) - $this->manager->getStartTime());
        $this->record('status', $response->getStatusCode());
        
        if (method_exists($response, 'headers')) {
            $this->record('response_headers', $this->formatHeaders($response->headers->all()));
        }
        
        if (method_exists($response, 'getContent')) {
            $this->record('response_size', strlen($response->getContent() ?: ''));
        }
    }

    protected function formatHeaders(array $headers): array
    {
        $formatted = [];
        foreach ($headers as $key => $value) {
            $formatted[$key] = implode(', ', $value);
        }
        return $formatted;
    }
}
