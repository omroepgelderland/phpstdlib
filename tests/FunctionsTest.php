<?php

declare(strict_types=1);

namespace gldstdlib;

use gldstdlib\exception\GLDException;
use PHPUnit\Framework\TestCase;

use function gldstdlib\safe\file_put_contents;
use function gldstdlib\safe\json_decode;

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

    public function test_maak_url_met_querystring(): void
    {
        $this->assertEquals(
            'https://gld.nl?key0=1&key1=woord%20woord&key2=2.3',
            maak_url_met_querystring('https://gld.nl', [
                'key0' => true,
                'key1' => 'woord woord',
                'key2' => 2.3,
                'nullkey' => null,
            ])
        );
        $this->assertEquals(
            'https://gld.nl',
            maak_url_met_querystring('https://gld.nl', [])
        );
    }

    public function test_guzzle_get_contents(): void
    {
        $data = guzzle_get_contents('https://postman-echo.com/get?foo=bar');
        $json = json_decode($data);
        $this->assertEquals(
            'bar',
            $json->args->foo,
        );
    }

    public function test_guzzle_get_contents_fail(): void
    {
        $this->expectException(\GuzzleHttp\Exception\ConnectException::class);
        guzzle_get_contents('fh9w3yr9w3ywhg');
    }

    public function test_guzzle_get_contents_http_error(): void
    {
        $this->expectException(\GuzzleHttp\Exception\ServerException::class);
        $this->expectExceptionCode(500);
        guzzle_get_contents('https://postman-echo.com/status/500');
    }

    public function test_guzzle_get_size(): void
    {
        $this->assertEquals(
            422744,
            guzzle_get_size(
                'https://fastly.picsum.photos/id/10/2500/1667.jpg?hmac=J04WWC_ebchx3WwzbM-Z4_KC_LeLBWr5LZMaAkWkF68'
            ),
        );
    }

    public function test_guzzle_get_size_fail(): void
    {
        $this->expectException(\GuzzleHttp\Exception\ConnectException::class);
        guzzle_get_size('fh9w3yr9w3ywhg');
    }

    public function test_curl_get_size_http_error(): void
    {
        $this->expectException(\GuzzleHttp\Exception\ServerException::class);
        $this->expectExceptionCode(500);
        guzzle_get_size('https://postman-echo.com/status/500');
    }

    public function test_parse_telefoonnummer(): void
    {
        $this->assertEquals(
            '+31617777777',
            parse_telefoonnummer('+31617777777')
        );
        $this->assertEquals(
            '+31617777777',
            parse_telefoonnummer('06 17777777')
        );
        $this->assertEquals(
            '+31617777777',
            parse_telefoonnummer('0617777777')
        );
        $this->assertEquals(
            '+31617777777',
            parse_telefoonnummer('06-17777777')
        );
        $this->assertEquals(
            '+31617777777',
            parse_telefoonnummer('06-17 77 77 77')
        );
        $this->assertEquals(
            '+31201234567',
            parse_telefoonnummer('020 1234567')
        );
        $this->assertEquals(
            '+31301234567',
            parse_telefoonnummer('030-1234567')
        );
        $this->assertEquals(
            '+31612345678',
            parse_telefoonnummer('+31 6 12345678')
        );
        $this->assertEquals(
            '+31612345678',
            parse_telefoonnummer('+31612345678')
        );
        $this->assertEquals(
            '+31612345678',
            parse_telefoonnummer('0031 6 12345678')
        );
        $this->assertEquals(
            '+31612345678',
            parse_telefoonnummer('0031-6-12345678')
        );
        $this->assertEquals(
            '+31612345678',
            parse_telefoonnummer('+31 (0)6 1234 5678')
        );
        $this->assertEquals(
            '+31612345678',
            parse_telefoonnummer('06 - 1234 5678')
        );
    }

    public function test_parse_telefoonnummer_ongeldig(): void
    {
        $this->expectException(GLDException::class);
        parse_telefoonnummer('123');
    }

    public function test_parse_telefoonnummer_ongeldig_nl(): void
    {
        $this->expectException(GLDException::class);
        parse_telefoonnummer('0601111111');
    }

    public function test_parse_telefoonnummer_ongeldig_internationaal(): void
    {
        $this->expectException(GLDException::class);
        parse_telefoonnummer('+485555555555555555');
    }

    public function test_format_telefoonnummer(): void
    {
        // mobiel
        $this->assertEquals(
            '+31 6 17 77 77 77',
            format_telefoonnummer('+31617777777')
        );
        // viercijferig netnummer
        $this->assertEquals(
            '+31 514 12 34 56',
            format_telefoonnummer('+31514123456')
        );
        // driecijferig netnummer
        $this->assertEquals(
            '+31 26 371 37 13',
            format_telefoonnummer('+31263713713')
        );
    }

    public function test_vervang_bestandsnaam_tekens(): void
    {
        $this->assertEquals(
            'ac⁄dc fooː ﹤bar﹥﹖.ext',
            vervang_bestandsnaam_tekens('ac/dc foo: <bar>?.ext')
        );
    }
}
