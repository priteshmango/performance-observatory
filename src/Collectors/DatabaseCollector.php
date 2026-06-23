<?php

namespace Performance\Observatory\Collectors;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Database\Events\TransactionRolledBack;
use Illuminate\Support\Facades\DB;

class DatabaseCollector extends AbstractCollector
{
    protected $queries = [];
    protected $connections = [];
    protected $transactions = [];
    
    public function getName(): string
    {
        return 'database';
    }

    public function boot(): void
    {
        $this->app['events']->listen(QueryExecuted::class, function (QueryExecuted $event) {
            $this->onQueryExecuted($event);
        });

        $this->app['events']->listen(TransactionBeginning::class, function ($event) {
            $this->transactions[] = ['type' => 'begin', 'connection' => $event->connectionName, 'time' => microtime(true)];
        });

        $this->app['events']->listen(TransactionCommitted::class, function ($event) {
            $this->transactions[] = ['type' => 'commit', 'connection' => $event->connectionName, 'time' => microtime(true)];
        });

        $this->app['events']->listen(TransactionRolledBack::class, function ($event) {
            $this->transactions[] = ['type' => 'rollback', 'connection' => $event->connectionName, 'time' => microtime(true)];
        });
    }

    protected $explaining = false;

    protected function onQueryExecuted(QueryExecuted $event): void
    {
        $time = $event->time; // in milliseconds
        
        $this->connections[$event->connectionName] = ($this->connections[$event->connectionName] ?? 0) + 1;

        $threshold = config('observatory.slow_query_threshold', 50);
        $explain = null;
        if ($time >= $threshold) {
            $explain = $this->explainQuery($event);
        }

        $this->queries[] = [
            'sql' => $event->sql,
            'bindings' => $this->formatBindings($event->bindings),
            'time' => $time,
            'connection' => $event->connectionName,
            'explain' => $explain,
        ];

        $this->record('queries', $this->queries);
        $this->record('connections_used', $this->connections);
        $this->record('transactions', $this->transactions);
        $this->record('total_time', array_sum(array_column($this->queries, 'time')));
        $this->record('total_queries', count($this->queries));
    }

    protected function explainQuery(QueryExecuted $event): ?array
    {
        if ($this->explaining) {
            return null;
        }

        $sql = trim($event->sql);
        // Only run EXPLAIN on SELECT queries to be completely safe
        if (!preg_match('/^\s*select\b/i', $sql)) {
            return null;
        }

        $this->explaining = true;
        try {
            $explainSql = "EXPLAIN " . $event->sql;
            $explainData = DB::connection($event->connectionName)->select($explainSql, $event->bindings);
            
            return collect($explainData)->map(function ($row) {
                return (array) $row;
            })->toArray();
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        } finally {
            $this->explaining = false;
        }
    }

    protected function formatBindings($bindings): array
    {
        return collect($bindings)->map(function ($binding) {
            if (is_object($binding) && method_exists($binding, '__toString')) {
                return (string) $binding;
            }
            if ($binding instanceof \DateTimeInterface) {
                return $binding->format('Y-m-d H:i:s');
            }
            return $binding;
        })->toArray();
    }
}
