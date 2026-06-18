<?php

namespace Performance\Observatory\Collectors;

class RedisCollector extends AbstractCollector
{
    public function getName(): string
    {
        return 'redis';
    }

    public function boot(): void
    {
        // TODO: Implement RedisCollector collection logic
    }
}