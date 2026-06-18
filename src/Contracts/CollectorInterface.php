<?php

namespace Performance\Observatory\Contracts;

interface CollectorInterface
{
    /**
     * Start the collection process. Register listeners, hooks, etc.
     */
    public function boot(): void;

    /**
     * Get the name of the collector.
     */
    public function getName(): string;

    /**
     * Get the collected data.
     */
    public function getData(): array;
}
