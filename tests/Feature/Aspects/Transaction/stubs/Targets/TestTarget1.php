<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Tests\Feature\Aspects\Transaction\stubs\Targets;

use Illuminate\Support\Facades\DB;
use Ngmy\LaravelAop\Aspects\Transaction\Attributes\Transactional;
use Ngmy\LaravelAop\Tests\Feature\Aspects\Transaction\stubs\Exceptions\TestException1;
use Ngmy\LaravelAop\Tests\Feature\Aspects\Transaction\stubs\Exceptions\TestException2;

class TestTarget1
{
    #[Transactional]
    public function method1(): void
    {
        DB::table('test_table')->insert(['id' => 1, 'name' => 'method1']);
    }

    #[Transactional]
    public function method2(): void
    {
        DB::table('test_table')->insert(['id' => 2, 'name' => 'method2']);

        throw new \Exception('Test exception');
    }

    #[Transactional(noRollbackFor: [\Exception::class])]
    public function method3(): void
    {
        DB::table('test_table')->insert(['id' => 3, 'name' => 'method3']);

        throw new \Exception('Test exception');
    }

    #[Transactional]
    public function method4(): void
    {
        DB::table('test_table')->insert(['id' => 4, 'name' => 'method4']);

        throw new \RuntimeException('Test exception');
    }

    #[Transactional(noRollbackFor: [TestException1::class])]
    public function method5(): void
    {
        DB::table('test_table')->insert(['id' => 5, 'name' => 'method5']);

        throw new TestException1('Test exception');
    }

    #[Transactional(rollbackFor: [TestException2::class])]
    public function method6(): void
    {
        DB::table('test_table')->insert(['id' => 6, 'name' => 'method6']);

        throw new TestException2('Test exception');
    }

    #[Transactional]
    #[Transactional(connection: 'test_connection_2')]
    public function method7(): void
    {
        DB::table('test_table')->insert(['id' => 7, 'name' => 'method7']);

        DB::connection('test_connection_2')->table('test_table')->insert(['id' => 7, 'name' => 'method7']);
    }

    #[Transactional]
    #[Transactional(connection: 'test_connection_2')]
    public function method8(): void
    {
        DB::table('test_table')->insert(['id' => 8, 'name' => 'method8']);

        DB::connection('test_connection_2')->table('test_table')->insert(['id' => 8, 'name' => 'method8']);

        throw new \Exception('Test exception');
    }

    #[Transactional(noRollbackFor: [\Exception::class])]
    #[Transactional(connection: 'test_connection_2', noRollbackFor: [\Exception::class])]
    public function method9(): void
    {
        DB::table('test_table')->insert(['id' => 9, 'name' => 'method9']);

        DB::connection('test_connection_2')->table('test_table')->insert(['id' => 9, 'name' => 'method9']);

        throw new \Exception('Test exception');
    }

    #[Transactional]
    #[Transactional(connection: 'test_connection_2')]
    public function method10(): void
    {
        DB::table('test_table')->insert(['id' => 10, 'name' => 'method10']);

        DB::connection('test_connection_2')->table('test_table')->insert(['id' => 10, 'name' => 'method10']);

        throw new \RuntimeException('Test exception');
    }

    #[Transactional(noRollbackFor: [TestException1::class])]
    #[Transactional(connection: 'test_connection_2', noRollbackFor: [TestException1::class])]
    public function method11(): void
    {
        DB::table('test_table')->insert(['id' => 11, 'name' => 'method11']);

        DB::connection('test_connection_2')->table('test_table')->insert(['id' => 11, 'name' => 'method11']);

        throw new TestException1('Test exception');
    }

    #[Transactional(rollbackFor: [TestException2::class])]
    #[Transactional(connection: 'test_connection_2', rollbackFor: [TestException2::class])]
    public function method12(): void
    {
        DB::table('test_table')->insert(['id' => 12, 'name' => 'method12']);

        DB::connection('test_connection_2')->table('test_table')->insert(['id' => 12, 'name' => 'method12']);

        throw new TestException2('Test exception');
    }

    #[Transactional(rollbackFor: [TestException2::class])]
    #[Transactional(connection: 'test_connection_2')]
    public function method13(): void
    {
        DB::table('test_table')->insert(['id' => 13, 'name' => 'method13']);

        DB::connection('test_connection_2')->table('test_table')->insert(['id' => 13, 'name' => 'method13']);

        throw new TestException2('Test exception');
    }

    #[Transactional]
    #[Transactional(connection: 'test_connection_2', rollbackFor: [TestException2::class])]
    public function method14(): void
    {
        DB::table('test_table')->insert(['id' => 14, 'name' => 'method14']);

        DB::connection('test_connection_2')->table('test_table')->insert(['id' => 14, 'name' => 'method14']);

        throw new TestException2('Test exception');
    }
}
