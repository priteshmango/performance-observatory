<?php

namespace Performance\Observatory;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Str;
use Performance\Observatory\Contracts\CollectorInterface;
use Performance\Observatory\Storage\StorageManager;

class ObservatoryManager
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @var CollectorInterface[]
     */
    protected $collectors = [];

    /**
     * @var string
     */
    protected $requestId;

    /**
     * @var float
     */
    protected $startTime;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->requestId = (string) Str::uuid();
        $this->startTime = microtime(true);
    }

    public function boot(): void
    {
        // Load collectors based on config
        $collectorMapping = [
            'request' => \Performance\Observatory\Collectors\RequestCollector::class,
            'database' => \Performance\Observatory\Collectors\DatabaseCollector::class,
            'route' => \Performance\Observatory\Collectors\RouteCollector::class,
            'middleware' => \Performance\Observatory\Collectors\MiddlewareCollector::class,
            'controller' => \Performance\Observatory\Collectors\ControllerCollector::class,
            'cache' => \Performance\Observatory\Collectors\CacheCollector::class,
            'redis' => \Performance\Observatory\Collectors\RedisCollector::class,
            'queue' => \Performance\Observatory\Collectors\QueueCollector::class,
            'view' => \Performance\Observatory\Collectors\ViewCollector::class,
            'api' => \Performance\Observatory\Collectors\ApiCollector::class,
            'filesystem' => \Performance\Observatory\Collectors\FilesystemCollector::class,
            'event' => \Performance\Observatory\Collectors\EventCollector::class,
            'mail' => \Performance\Observatory\Collectors\MailCollector::class,
            'session' => \Performance\Observatory\Collectors\SessionCollector::class,
            'frontend' => \Performance\Observatory\Collectors\FrontendCollector::class,
            'memory' => \Performance\Observatory\Collectors\MemoryCollector::class,
            'cpu' => \Performance\Observatory\Collectors\CpuCollector::class,
            'server' => \Performance\Observatory\Collectors\ServerCollector::class,
        ];

        $config = config('observatory.collectors', []);

        foreach ($collectorMapping as $name => $class) {
            if ($config[$name] ?? false) {
                $collector = new $class($this->app, $this);
                $this->addCollector($collector);
                $collector->boot();
            }
        }

        // Register termination to save data
        $this->app->terminating(function () {
            $this->terminate();
        });
    }

    public function addCollector(CollectorInterface $collector): void
    {
        $this->collectors[$collector->getName()] = $collector;
    }

    public function getRequestId(): string
    {
        return $this->requestId;
    }

    public function getStartTime(): float
    {
        return $this->startTime;
    }

    public function terminate(): void
    {
        $data = [
            'request_id' => $this->requestId,
            'timestamp' => now()->toDateTimeString(),
            'total_duration' => microtime(true) - $this->startTime,
            'metrics' => []
        ];

        foreach ($this->collectors as $name => $collector) {
            $data['metrics'][$name] = $collector->getData();
        }

        // Store data using StorageManager
        // In a real app, this should probably be deferred.
        // We'll implement StorageManager to handle async saving.
        $this->app->make(StorageManager::class)->store($data);
    }
}
