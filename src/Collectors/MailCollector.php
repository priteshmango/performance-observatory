<?php

namespace Performance\Observatory\Collectors;

class MailCollector extends AbstractCollector
{
    public function getName(): string
    {
        return 'mail';
    }

    public function boot(): void
    {
        // TODO: Implement MailCollector collection logic
    }
}