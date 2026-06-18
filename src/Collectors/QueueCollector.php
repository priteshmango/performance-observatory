<?php

namespace Performance\Observatory\Collectors;

class QueueCollector extends AbstractCollector
{
    public function getName(): string
    {
        return 'queue';
    }

    public function boot(): void
    {
        // TODO: Implement QueueCollector collection logic
    }
}