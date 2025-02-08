<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Tests\Feature\Aspects\Transaction;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Ngmy\LaravelAop\Tests\Feature\Aspects\Transaction\stubs\Targets\TestTarget1;
use Ngmy\LaravelAop\Tests\TestCase;

/**
 * @internal
 *
 * @covers \Ngmy\LaravelAop\Aspects\Transaction\Attributes\Transactional
 * @covers \Ngmy\LaravelAop\Aspects\Transaction\Interceptors\TransactionalInterceptor
 */
final class TransactionTest extends TestCase
{
    /**
     * The database connections.
     *
     * @var list<string>
     */
    private array $connections = [
        'test_connection_1',
        'test_connection_2',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        foreach ($this->connections as $connection) {
            Schema::connection($connection)->dropIfExists('test_table');

            Schema::connection($connection)->create('test_table', static function (Blueprint $table): void {
                $table->increments('id');
                $table->string('name');
            });
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->connections as $connection) {
            Schema::connection($connection)->dropIfExists('test_table');
        }

        parent::tearDown();
    }

    public function testTransaction(): void
    {
        $target = $this->app->make(TestTarget1::class);

        self::assertSame(0, DB::connection('test_connection_1')->transactionLevel());

        $target->method1();

        $this->assertDatabaseHas('test_table', ['id' => 1, 'name' => 'method1'], 'test_connection_1');
        self::assertSame(0, DB::connection('test_connection_1')->transactionLevel());

        $target->method7();

        $this->assertDatabaseHas('test_table', ['id' => 7, 'name' => 'method7'], 'test_connection_1');
        $this->assertDatabaseHas('test_table', ['id' => 7, 'name' => 'method7'], 'test_connection_2');
        self::assertSame(0, DB::connection('test_connection_1')->transactionLevel());
        self::assertSame(0, DB::connection('test_connection_2')->transactionLevel());

        try {
            $target->method2();

            self::fail('An exception should have been thrown');
        } catch (\Throwable $e) {
            $this->assertDatabaseMissing('test_table', ['id' => 2, 'name' => 'method2'], 'test_connection_1');
            self::assertSame(0, DB::connection('test_connection_1')->transactionLevel());
        }

        try {
            $target->method8();

            self::fail('An exception should have been thrown');
        } catch (\Throwable $e) {
            $this->assertDatabaseMissing('test_table', ['id' => 8, 'name' => 'method8'], 'test_connection_1');
            $this->assertDatabaseMissing('test_table', ['id' => 8, 'name' => 'method8'], 'test_connection_2');
            self::assertSame(0, DB::connection('test_connection_1')->transactionLevel());
            self::assertSame(0, DB::connection('test_connection_2')->transactionLevel());
        }

        try {
            $target->method3();

            self::fail('An exception should have been thrown');
        } catch (\Throwable $e) {
            $this->assertDatabaseHas('test_table', ['id' => 3, 'name' => 'method3'], 'test_connection_1');
            self::assertSame(0, DB::connection('test_connection_1')->transactionLevel());
        }

        try {
            $target->method9();

            self::fail('An exception should have been thrown');
        } catch (\Throwable $e) {
            $this->assertDatabaseHas('test_table', ['id' => 9, 'name' => 'method9'], 'test_connection_1');
            $this->assertDatabaseHas('test_table', ['id' => 9, 'name' => 'method9'], 'test_connection_2');
            self::assertSame(0, DB::connection('test_connection_1')->transactionLevel());
            self::assertSame(0, DB::connection('test_connection_2')->transactionLevel());
        }

        config(['aop.transaction.rollback_for' => [\RuntimeException::class]]);

        try {
            $target->method2();

            self::fail('An exception should have been thrown');
        } catch (\Throwable $e) {
            $this->assertDatabaseHas('test_table', ['id' => 2, 'name' => 'method2'], 'test_connection_1');
            self::assertSame(0, DB::connection('test_connection_1')->transactionLevel());
        }

        try {
            $target->method8();

            self::fail('An exception should have been thrown');
        } catch (\Throwable $e) {
            $this->assertDatabaseHas('test_table', ['id' => 8, 'name' => 'method8'], 'test_connection_1');
            $this->assertDatabaseHas('test_table', ['id' => 8, 'name' => 'method8'], 'test_connection_2');
            self::assertSame(0, DB::connection('test_connection_1')->transactionLevel());
            self::assertSame(0, DB::connection('test_connection_2')->transactionLevel());
        }

        try {
            $target->method4();

            self::fail('An exception should have been thrown');
        } catch (\Throwable $e) {
            $this->assertDatabaseMissing('test_table', ['id' => 4, 'name' => 'method4'], 'test_connection_1');
            self::assertSame(0, DB::connection('test_connection_1')->transactionLevel());
        }

        try {
            $target->method10();

            self::fail('An exception should have been thrown');
        } catch (\Throwable $e) {
            $this->assertDatabaseMissing('test_table', ['id' => 10, 'name' => 'method10'], 'test_connection_1');
            $this->assertDatabaseMissing('test_table', ['id' => 10, 'name' => 'method10'], 'test_connection_2');
            self::assertSame(0, DB::connection('test_connection_1')->transactionLevel());
            self::assertSame(0, DB::connection('test_connection_2')->transactionLevel());
        }

        try {
            $target->method5();

            self::fail('An exception should have been thrown');
        } catch (\Throwable $e) {
            $this->assertDatabaseHas('test_table', ['id' => 5, 'name' => 'method5'], 'test_connection_1');
            self::assertSame(0, DB::connection('test_connection_1')->transactionLevel());
        }

        try {
            $target->method11();

            self::fail('An exception should have been thrown');
        } catch (\Throwable $e) {
            $this->assertDatabaseHas('test_table', ['id' => 11, 'name' => 'method11'], 'test_connection_1');
            $this->assertDatabaseHas('test_table', ['id' => 11, 'name' => 'method11'], 'test_connection_2');
            self::assertSame(0, DB::connection('test_connection_1')->transactionLevel());
            self::assertSame(0, DB::connection('test_connection_2')->transactionLevel());
        }

        try {
            $target->method6();

            self::fail('An exception should have been thrown');
        } catch (\Throwable $e) {
            $this->assertDatabaseMissing('test_table', ['id' => 6, 'name' => 'method6'], 'test_connection_1');
            self::assertSame(0, DB::connection('test_connection_1')->transactionLevel());
        }

        try {
            $target->method12();

            self::fail('An exception should have been thrown');
        } catch (\Throwable $e) {
            $this->assertDatabaseMissing('test_table', ['id' => 12, 'name' => 'method12'], 'test_connection_1');
            $this->assertDatabaseMissing('test_table', ['id' => 12, 'name' => 'method12'], 'test_connection_2');
            self::assertSame(0, DB::connection('test_connection_1')->transactionLevel());
            self::assertSame(0, DB::connection('test_connection_2')->transactionLevel());
        }

        try {
            $target->method13();

            self::fail('An exception should have been thrown');
        } catch (\Throwable $e) {
            $this->assertDatabaseMissing('test_table', ['id' => 13, 'name' => 'method13'], 'test_connection_1');
            $this->assertDatabaseHas('test_table', ['id' => 13, 'name' => 'method13'], 'test_connection_2');
            self::assertSame(0, DB::connection('test_connection_1')->transactionLevel());
            self::assertSame(0, DB::connection('test_connection_2')->transactionLevel());
        }

        try {
            $target->method14();

            self::fail('An exception should have been thrown');
        } catch (\Throwable $e) {
            $this->assertDatabaseHas('test_table', ['id' => 14, 'name' => 'method14'], 'test_connection_1');
            $this->assertDatabaseMissing('test_table', ['id' => 14, 'name' => 'method14'], 'test_connection_2');
            self::assertSame(0, DB::connection('test_connection_1')->transactionLevel());
            self::assertSame(0, DB::connection('test_connection_2')->transactionLevel());
        }
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', $this->connections[0]);

        foreach ($this->connections as $connection) {
            $app['config']->set("database.connections.{$connection}", [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]);
        }
    }
}
