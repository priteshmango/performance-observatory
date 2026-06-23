<?php

namespace Performance\Observatory\Collectors;

class CpuCollector extends AbstractCollector
{
    protected $startCpu = [];

    public function getName(): string
    {
        return 'cpu';
    }

    public function boot(): void
    {
        if (function_exists('getrusage')) {
            $this->startCpu = getrusage();
        }
    }

    public function getData(): array
    {
        if (function_exists('getrusage') && !empty($this->startCpu)) {
            $endCpu = getrusage();
            
            // Calculate CPU times in milliseconds
            // utime = user time, stime = system time
            $startUserTime = ($this->startCpu['ru_utime.tv_sec'] * 1000) + ($this->startCpu['ru_utime.tv_usec'] / 1000);
            $endUserTime = ($endCpu['ru_utime.tv_sec'] * 1000) + ($endCpu['ru_utime.tv_usec'] / 1000);
            
            $startSysTime = ($this->startCpu['ru_stime.tv_sec'] * 1000) + ($this->startCpu['ru_stime.tv_usec'] / 1000);
            $endSysTime = ($endCpu['ru_stime.tv_sec'] * 1000) + ($endCpu['ru_stime.tv_usec'] / 1000);
            
            $userCpuTime = $endUserTime - $startUserTime;
            $sysCpuTime = $endSysTime - $startSysTime;
            
            $this->record('user_cpu_ms', $userCpuTime);
            $this->record('sys_cpu_ms', $sysCpuTime);
            $this->record('total_cpu_ms', $userCpuTime + $sysCpuTime);
        }

        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            if (is_array($load)) {
                $this->record('load_avg_1m', $load[0]);
                $this->record('load_avg_5m', $load[1]);
                $this->record('load_avg_15m', $load[2]);
            }
        }

        return parent::getData();
    }
}