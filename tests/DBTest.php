<?php

declare(strict_types=1);

namespace gldstdlib;

use gldstdlib\exception\NullException;
use gldstdlib\exception\SQLNoResultException;
use Medoo\Medoo;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DBTest extends TestCase
{
    private function get_mock_db(MockObject $mock_medoo): MockObject
    {
        $mock_db = $this->getMockBuilder(DB::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get_db'])
            ->getMock();
        $mock_db->method('get_db')->willReturn($mock_medoo);
        return $mock_db;
    }

    public function test_select_single(): void
    {
        $mock_medoo = $this->createMock(Medoo::class);
        $mock_medoo->method('get')->willReturn('foo');
        $mock_medoo->method('has')->willReturn(true);
        $mock_db = $this->get_mock_db($mock_medoo);

        $actual = $mock_db->select_single(
            'table',
            'column',
            [
                'id' => 1,
            ],
        );
        $this->assertEquals('foo', $actual);
    }

    public function test_select_single_join(): void
    {
        $mock_medoo = $this->createMock(Medoo::class);
        $mock_medoo->method('get')->willReturn('foo');
        $mock_medoo->method('has')->willReturn(true);
        $mock_db = $this->get_mock_db($mock_medoo);

        $actual = $mock_db->select_single(
            'table',
            'column',
            [
                'id' => 1,
            ],
            [
                '[>]account' => ['author_id' => 'user_id'],
            ],
        );
        $this->assertEquals('foo', $actual);
    }

    public function test_select_single_null(): void
    {
        $mock_medoo = $this->createMock(Medoo::class);
        $mock_medoo->method('get')->willReturn(null);
        $mock_medoo->method('has')->willReturn(true);
        $mock_db = $this->get_mock_db($mock_medoo);

        $actual = $mock_db->select_single(
            'table',
            'column',
            [
                'id' => 1,
            ],
        );
        $this->assertNull($actual);
    }

    public function test_select_single_null_join(): void
    {
        $mock_medoo = $this->createMock(Medoo::class);
        $mock_medoo->method('get')->willReturn(null);
        $mock_medoo->method('has')->willReturn(true);
        $mock_db = $this->get_mock_db($mock_medoo);

        $actual = $mock_db->select_single(
            'table',
            'column',
            [
                'id' => 1,
            ],
            [
                '[>]account' => ['author_id' => 'user_id'],
            ],
        );
        $this->assertNull($actual);
    }

    public function test_select_single_null_exception(): void
    {
        $mock_medoo = $this->createMock(Medoo::class);
        $mock_medoo->method('get')->willReturn(null);
        $mock_medoo->method('has')->willReturn(true);
        $mock_db = $this->get_mock_db($mock_medoo);

        $this->expectException(NullException::class);
        $mock_db->select_single(
            'table',
            'column',
            [
                'id' => 1,
            ],
            [],
            false
        );
    }

    public function test_select_single_null_exception_join(): void
    {
        $mock_medoo = $this->createMock(Medoo::class);
        $mock_medoo->method('get')->willReturn(null);
        $mock_medoo->method('has')->willReturn(true);
        $mock_db = $this->get_mock_db($mock_medoo);

        $this->expectException(NullException::class);
        $mock_db->select_single(
            'table',
            'column',
            [
                'id' => 1,
            ],
            [
                '[>]account' => ['author_id' => 'user_id'],
            ],
            false
        );
    }

    public function test_select_single_noresult(): void
    {
        $mock_medoo = $this->createMock(Medoo::class);
        $mock_medoo->method('get')->willReturn(null);
        $mock_medoo->method('has')->willReturn(false);
        $mock_db = $this->get_mock_db($mock_medoo);

        $this->expectException(SQLNoResultException::class);
        $mock_db->select_single(
            'table',
            'column',
            [
                'id' => 1,
            ],
            [],
            false
        );
    }

    public function test_select_single_noresult_join(): void
    {
        $mock_medoo = $this->createMock(Medoo::class);
        $mock_medoo->method('get')->willReturn(null);
        $mock_medoo->method('has')->willReturn(false);
        $mock_db = $this->get_mock_db($mock_medoo);

        $this->expectException(SQLNoResultException::class);
        $mock_db->select_single(
            'table',
            'column',
            [
                'id' => 1,
            ],
            [
                '[>]account' => ['author_id' => 'user_id'],
            ],
            false
        );
    }
}
