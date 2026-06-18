<?php

namespace Performance\Observatory\Collectors;

class EventCollector extends AbstractCollector
{
    public function getName(): string
    {
        return 'event';
    }

    public function boot(): void
    {
        // TODO: Implement EventCollector collection logic
    }
}