<?php

$collectors = [
    'ServerCollector', 'RouteCollector', 'MiddlewareCollector', 'ControllerCollector',
    'CacheCollector', 'QueueCollector', 'RedisCollector', 'ViewCollector',
    'ApiCollector', 'FilesystemCollector', 'FrontendCollector', 'NetworkCollector',
    'BrowserCollector', 'MemoryCollector', 'CpuCollector', 'EventCollector',
    'MailCollector', 'SessionCollector'
];

$stub = <<<PHP
<?php

namespace Performance\Observatory\Collectors;

class {CLASS_NAME} extends AbstractCollector
{
    public function getName(): string
    {
        return '{LOWER_NAME}';
    }

    public function boot(): void
    {
        // TODO: Implement {CLASS_NAME} collection logic
    }
}
PHP;

foreach ($collectors as $collector) {
    $file = __DIR__ . '/src/Collectors/' . $collector . '.php';
    if (!file_exists($file)) {
        $lowerName = strtolower(str_replace('Collector', '', $collector));
        $content = str_replace(['{CLASS_NAME}', '{LOWER_NAME}'], [$collector, $lowerName], $stub);
        file_put_contents($file, $content);
        echo "Created $collector\n";
    }
}
