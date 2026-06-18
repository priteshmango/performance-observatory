<?php

namespace Performance\Observatory\Collectors;

class FilesystemCollector extends AbstractCollector
{
    public function getName(): string
    {
        return 'filesystem';
    }

    public function boot(): void
    {
        // TODO: Implement FilesystemCollector collection logic
    }
}