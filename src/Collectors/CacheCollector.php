<?php

namespace Performance\Observatory\Collectors;

class CacheCollector extends AbstractCollector
{
    public function getName(): string
    {
        return 'cache';
    }

    public function boot(): void
    {
        // TODO: Implement CacheCollector collection logic
    }
}