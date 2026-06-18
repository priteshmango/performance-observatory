<?php

namespace Performance\Observatory\Collectors;

use Illuminate\Contracts\Foundation\Application;
use Performance\Observatory\Contracts\CollectorInterface;
use Performance\Observatory\ObservatoryManager;

abstract class AbstractCollector implements CollectorInterface
{
    protected $app;
    protected $manager;
    protected $data = [];

    public function __construct(Application $app, ObservatoryManager $manager)
    {
        $this->app = $app;
        $this->manager = $manager;
    }

    public function getData(): array
    {
        return $this->data;
    }

    protected function record(string $key, $value): void
    {
        $this->data[$key] = $value;
    }
}
