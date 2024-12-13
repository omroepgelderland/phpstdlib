<?php

declare(strict_types=1);

namespace gldstdlib;

use PHPUnit\Framework\TestCase;

use function gldstdlib\safe\file_put_contents;

final class FunctionsTest extends TestCase
{
    public function test_rrmdir(): void
    {
        \mkdir('/tmp/rrmdirtest/subdir', recursive:true);
        file_put_contents('/tmp/rrmdirtest/file1.txt', '1');
        file_put_contents('/tmp/rrmdirtest/subdir/file2', 'x');
        rrmdir('/tmp/rrmdirtest');
        $this->assertDirectoryDoesNotExist('/tmp/rrmdirtest');
    }

    public function test_path_join(): void
    {
        $this->assertEquals(
            '/',
            path_join('/')
        );
        $this->assertEquals(
            'rel1/rel2',
            path_join('rel1', 'rel2')
        );
        $this->assertEquals(
            '/abs1/file',
            path_join('/abs1', 'file')
        );
        $this->assertEquals(
            '/abs1/dir/',
            path_join('/abs1', 'dir/')
        );
        $this->assertEquals(
            '/p1/p1/p2/p2',
            path_join('/p1/p1', 'p2/p2')
        );
        $this->assertEquals(
            'p1/p2',
            path_join('p1/', '/p2')
        );
    }
}
