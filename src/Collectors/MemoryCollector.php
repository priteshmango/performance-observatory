<?php

namespace Performance\Observatory\Collectors;

class MemoryCollector extends AbstractCollector
{
    public function getName(): string
    {
        return 'memory';
    }

    public function boot(): void
    {
        // TODO: Implement MemoryCollector collection logic
    }
}