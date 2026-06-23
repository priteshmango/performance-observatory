<?php

namespace Performance\Observatory\Collectors;

class MemoryCollector extends AbstractCollector
{
    protected $startMemory = 0;

    public function getName(): string
    {
        return 'memory';
    }

    public function boot(): void
    {
        $this->startMemory = memory_get_usage(true);
    }

    public function getData(): array
    {
        $this->record('start_memory', $this->startMemory);
        $this->record('peak_memory', memory_get_peak_usage(true));
        $this->record('end_memory', memory_get_usage(true));
        $this->record('memory_limit', ini_get('memory_limit'));

        return parent::getData();
    }
}