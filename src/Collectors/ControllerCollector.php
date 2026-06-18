<?php

namespace Performance\Observatory\Collectors;

class ControllerCollector extends AbstractCollector
{
    public function getName(): string
    {
        return 'controller';
    }

    public function boot(): void
    {
        // TODO: Implement ControllerCollector collection logic
    }
}