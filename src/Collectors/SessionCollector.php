<?php

namespace Performance\Observatory\Collectors;

class SessionCollector extends AbstractCollector
{
    public function getName(): string
    {
        return 'session';
    }

    public function boot(): void
    {
        // TODO: Implement SessionCollector collection logic
    }
}