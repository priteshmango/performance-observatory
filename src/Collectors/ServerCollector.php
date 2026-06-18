<?php

namespace Performance\Observatory\Collectors;

class ServerCollector extends AbstractCollector
{
    public function getName(): string
    {
        return 'server';
    }

    public function boot(): void
    {
        // TODO: Implement ServerCollector collection logic
    }
}