<?php

namespace Performance\Observatory\Collectors;

class MiddlewareCollector extends AbstractCollector
{
    public function getName(): string
    {
        return 'middleware';
    }

    public function boot(): void
    {
        // TODO: Implement MiddlewareCollector collection logic
    }
}