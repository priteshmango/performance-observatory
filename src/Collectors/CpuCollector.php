<?php

namespace Performance\Observatory\Collectors;

class CpuCollector extends AbstractCollector
{
    public function getName(): string
    {
        return 'cpu';
    }

    public function boot(): void
    {
        // TODO: Implement CpuCollector collection logic
    }
}