<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\DatabaseTransactionsManager;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use PDO;

/**
 * Test policy:
 * - SQLite in-memory only (shared cache so schema survives per-test app reboot)
 * - DatabaseTransactions on every test (never RefreshDatabase)
 * - Migrations run once per process before transactions start
 */
abstract class TestCase extends BaseTestCase
{
    use DatabaseTransactions {
        beginDatabaseTransaction as protected laravelBeginDatabaseTransaction;
    }

    private const SQLITE_MEMORY_DATABASE = 'file:codebase_llm_assistant_testing?mode=memory&cache=shared';

    protected static bool $databaseMigrated = false;

    protected static ?PDO $inMemoryPdo = null;

    public function createApplication(): Application
    {
        $this->forceTestingEnvironment();

        $app = parent::createApplication();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => self::SQLITE_MEMORY_DATABASE,
        ]);

        return $app;
    }

    protected function refreshApplication(): void
    {
        parent::refreshApplication();

        $this->assertUsesInMemorySqlite();
        $this->ensureDatabaseIsMigrated();
        $this->rememberInMemoryPdo();
    }

    protected function setUp(): void
    {
        $this->forceTestingEnvironment();

        parent::setUp();
    }

    public function beginDatabaseTransaction(): void
    {
        $database = $this->app->make('db');
        $connections = $this->connectionsToTransact();

        $this->restoreInMemoryPdo($database, $connections);

        $this->app->instance(
            'db.transactions',
            $transactionsManager = new DatabaseTransactionsManager($connections),
        );

        foreach ($connections as $name) {
            $connection = $database->connection($name);
            $connection->setTransactionManager($transactionsManager);
            $dispatcher = $connection->getEventDispatcher();

            $connection->unsetEventDispatcher();
            $connection->beginTransaction();
            $connection->setEventDispatcher($dispatcher);
        }

        $this->beforeApplicationDestroyed(function () use ($database, $connections): void {
            foreach ($connections as $name) {
                $connection = $database->connection($name);
                $dispatcher = $connection->getEventDispatcher();

                $connection->unsetEventDispatcher();

                while ($connection->transactionLevel() > 0) {
                    $connection->rollBack();
                }

                $connection->setEventDispatcher($dispatcher);
                static::$inMemoryPdo = $connection->getPdo();
            }
        });
    }

    protected function forceTestingEnvironment(): void
    {
        foreach ([
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => self::SQLITE_MEMORY_DATABASE,
            'DB_URL' => '',
            'DB_HOST' => '',
            'DB_PORT' => '',
            'DB_USERNAME' => '',
            'DB_PASSWORD' => '',
            'CACHE_STORE' => 'array',
            'QUEUE_CONNECTION' => 'sync',
            'SESSION_DRIVER' => 'array',
        ] as $key => $value) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    protected function assertUsesInMemorySqlite(): void
    {
        if (config('database.default') !== 'sqlite') {
            $this->fail('Tests must use sqlite. Got: '.config('database.default'));
        }

        $database = (string) config('database.connections.sqlite.database');

        if (! str_contains($database, 'mode=memory')) {
            $this->fail('Tests must use sqlite in-memory. Got: '.$database);
        }
    }

    protected function ensureDatabaseIsMigrated(): void
    {
        if (static::$databaseMigrated) {
            return;
        }

        $this->artisan('migrate', ['--force' => true]);
        static::$databaseMigrated = true;
    }

    protected function rememberInMemoryPdo(): void
    {
        if (static::$inMemoryPdo !== null) {
            return;
        }

        static::$inMemoryPdo = $this->app->make('db')->connection()->getPdo();
    }

    /**
     * @param  array<int, string|null>  $connections
     */
    protected function restoreInMemoryPdo(mixed $database, array $connections): void
    {
        if (static::$inMemoryPdo === null) {
            return;
        }

        foreach ($connections as $name) {
            $database->connection($name)->setPdo(static::$inMemoryPdo);
        }
    }
}
