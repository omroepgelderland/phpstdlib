<?php

declare(strict_types=1);

namespace gldstdlib\tests;

use gldstdlib\EmailAddress;
use gldstdlib\exception\GLDException;
use gldstdlib\safe\SafeException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPUnit\Framework\TestCase;

use function gldstdlib\add_mail_attachment_data;
use function gldstdlib\add_mail_attachment_paths;
use function gldstdlib\build_mail;
use function gldstdlib\format_telefoonnummer;
use function gldstdlib\fs;
use function gldstdlib\guzzle_get_contents;
use function gldstdlib\guzzle_get_size;
use function gldstdlib\maak_url_met_querystring;
use function gldstdlib\ns;
use function gldstdlib\parse_telefoonnummer;
use function gldstdlib\path_join;
use function gldstdlib\rrmdir;
use function gldstdlib\safe\file_put_contents;
use function gldstdlib\safe\json_decode;
use function gldstdlib\send_mail;
use function gldstdlib\split_email_address;
use function gldstdlib\vervang_bestandsnaam_tekens;

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

    public function test_blob_mime_content_type_returns_text_plain_for_plain_text(): void
    {
        self::assertSame(
            'text/plain',
            \gldstdlib\blob_mime_content_type('Hello world'),
        );
    }

    public function test_blob_mime_content_type_returns_image_png_for_png_data(): void
    {
        $pngData = \base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+yZ5kAAAAASUVORK5CYII=',
            true,
        );
        self::assertNotFalse($pngData);

        self::assertSame(
            'image/png',
            \gldstdlib\blob_mime_content_type($pngData),
        );
    }

    public function test_blob_mime_content_type_returns_application_octet_stream_for_binary_data(): void
    {
        $data = "\x00\x01\x02\x03\x04\x05";

        self::assertSame(
            'application/octet-stream',
            \gldstdlib\blob_mime_content_type($data),
        );
    }

    public function test_ns_returns_value(): void
    {
        self::assertSame('abc', ns('abc'));
        self::assertSame(123, ns(123));
    }

    public function test_ns_throws_on_null(): void
    {
        $this->expectException(SafeException::class);
        ns(null);
    }

    public function test_fs_returns_value(): void
    {
        self::assertSame('abc', fs('abc'));
        self::assertSame(123, fs(123));
    }

    public function test_fs_throws_on_false(): void
    {
        $this->expectException(SafeException::class);
        fs(false);
    }

    public function test_build_mail_throws_when_no_plaintext_or_html_message_is_provided(): void
    {
        $mail = $this->getMockBuilder(PHPMailer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->expectException(GLDException::class);
        $this->expectExceptionMessage('No plaintext or HTML message provided');

        build_mail(
            mail: $mail,
            to: 'to@example.com',
            from: 'from@example.com',
            subject: 'Subject',
            text_message: null,
            html_message: null,
        );
    }

    public function test_build_mail_configures_plaintext_email(): void
    {
        $calls = [
            'setFrom' => null,
            'addAddress' => [],
            'addCC' => [],
            'addReplyTo' => null,
            'isHTML' => null,
            'isSendmail' => 0,
        ];

        $mail = $this->getMockBuilder(PHPMailer::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'isSendmail',
                'setFrom',
                'addAddress',
                'addCC',
                'addReplyTo',
                'isHTML',
                'addAttachment',
                'addStringAttachment',
            ])
            ->getMock();

        $mail->method('isSendmail')
            ->willReturnCallback(function () use (&$calls): void {
                $calls['isSendmail']++;
            });

        $mail->method('setFrom')
            ->willReturnCallback(function (string $email, string $name = '') use (&$calls): bool {
                $calls['setFrom'] = [$email, $name];
                return true;
            });

        $mail->method('addAddress')
            ->willReturnCallback(function (string $email, string $name = '') use (&$calls): bool {
                $calls['addAddress'][] = [$email, $name];
                return true;
            });

        $mail->method('addCC')
            ->willReturnCallback(function (string $email, string $name = '') use (&$calls): bool {
                $calls['addCC'][] = [$email, $name];
                return true;
            });

        $mail->method('addReplyTo')
            ->willReturnCallback(function (string $email, string $name = '') use (&$calls): bool {
                $calls['addReplyTo'] = [$email, $name];
                return true;
            });

        $mail->method('isHTML')
            ->willReturnCallback(function (bool $isHtml) use (&$calls): void {
                $calls['isHTML'] = $isHtml;
            });

        build_mail(
            mail: $mail,
            to: [
                'alice@example.com',
                $this->createEmailAddress('bob@example.com', 'Bob Example'),
            ],
            from: $this->createEmailAddress('sender@example.com', 'Sender Name'),
            subject: 'Test subject',
            text_message: 'Plain text body',
            cc: [
                'cc1@example.com',
                $this->createEmailAddress('cc2@example.com', 'CC Two'),
            ],
            reply_to: $this->createEmailAddress('reply@example.com', 'Reply Name'),
        );

        self::assertSame(1, $calls['isSendmail']);
        self::assertSame(['sender@example.com', 'Sender Name'], $calls['setFrom']);
        self::assertSame(
            [
                ['alice@example.com', ''],
                ['bob@example.com', 'Bob Example'],
            ],
            $calls['addAddress'],
        );
        self::assertSame(
            [
                ['cc1@example.com', ''],
                ['cc2@example.com', 'CC Two'],
            ],
            $calls['addCC'],
        );
        self::assertSame(['reply@example.com', 'Reply Name'], $calls['addReplyTo']);
        self::assertFalse($calls['isHTML']);

        self::assertSame('UTF-8', $mail->CharSet);
        self::assertSame('7bit', $mail->Encoding);
        self::assertSame('Test subject', $mail->Subject);
        self::assertSame('Plain text body', $mail->Body);
        self::assertSame('Plain text body', $mail->AltBody);
    }

    public function test_build_mail_generates_plaintext_from_html_string(): void
    {
        $isHtml = null;

        $mail = $this->getMockBuilder(PHPMailer::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'isSendmail',
                'setFrom',
                'addAddress',
                'addCC',
                'addReplyTo',
                'isHTML',
                'addAttachment',
                'addStringAttachment',
            ])
            ->getMock();

        $mail->method('isSendmail')->willReturn(null);
        $mail->method('setFrom')->willReturn(true);
        $mail->method('addAddress')->willReturn(true);
        $mail->method('addCC')->willReturn(true);
        $mail->method('addReplyTo')->willReturn(true);
        $mail->method('isHTML')
            ->willReturnCallback(function (bool $value) use (&$isHtml): void {
                $isHtml = $value;
            });

        build_mail(
            mail: $mail,
            to: 'to@example.com',
            from: 'from@example.com',
            subject: 'HTML subject',
            text_message: null,
            html_message: '<html><body><h1>Hello</h1><p>World</p></body></html>',
        );

        self::assertTrue($isHtml);
        self::assertStringContainsString('<h1>Hello</h1>', $mail->Body);
        self::assertSame('HelloWorld', \preg_replace('/\s+/', '', $mail->AltBody));
    }

    public function test_build_mail_with_dom_document_uses_saved_html_and_text_content(): void
    {
        $isHtml = null;

        $dom = new \DOMDocument();
        $dom->loadHTML('<html><body><p>Hello <strong>world</strong></p></body></html>');

        $mail = $this->getMockBuilder(PHPMailer::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'isSendmail',
                'setFrom',
                'addAddress',
                'addCC',
                'addReplyTo',
                'isHTML',
                'addAttachment',
                'addStringAttachment',
            ])
            ->getMock();

        $mail->method('isSendmail')->willReturn(null);
        $mail->method('setFrom')->willReturn(true);
        $mail->method('addAddress')->willReturn(true);
        $mail->method('addCC')->willReturn(true);
        $mail->method('addReplyTo')->willReturn(true);
        $mail->method('isHTML')
            ->willReturnCallback(function (bool $value) use (&$isHtml): void {
                $isHtml = $value;
            });

        build_mail(
            mail: $mail,
            to: 'to@example.com',
            from: 'from@example.com',
            subject: 'DOM subject',
            text_message: null,
            html_message: $dom,
        );

        self::assertTrue($isHtml);
        self::assertIsString($mail->Body);
        self::assertStringContainsString('<strong>world</strong>', $mail->Body);
        self::assertStringContainsString('Hello', $mail->AltBody);
        self::assertStringContainsString('world', $mail->AltBody);
    }

    public function test_build_mail_adds_attachment_paths_and_attachment_data(): void
    {
        $pathCalls = [];
        $dataCalls = [];

        $path = $this->createTempFile('attachment contents');

        $mail = $this->getMockBuilder(PHPMailer::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'isSendmail',
                'setFrom',
                'addAddress',
                'addCC',
                'addReplyTo',
                'isHTML',
                'addAttachment',
                'addStringAttachment',
            ])
            ->getMock();

        $mail->method('isSendmail')->willReturn(null);
        $mail->method('setFrom')->willReturn(true);
        $mail->method('addAddress')->willReturn(true);
        $mail->method('addCC')->willReturn(true);
        $mail->method('addReplyTo')->willReturn(true);
        $mail->method('isHTML')->willReturn(null);

        $mail->method('addAttachment')
            ->willReturnCallback(
                function (
                    string $attachmentPath,
                    string $name = '',
                    string $encoding = 'base64',
                    string $type = '',
                    string $disposition = 'attachment',
                ) use (&$pathCalls): bool {
                    $pathCalls[] = [
                        'path' => $attachmentPath,
                        'name' => $name,
                        'encoding' => $encoding,
                        'type' => $type,
                        'disposition' => $disposition,
                    ];
                    return true;
                },
            );

        $mail->method('addStringAttachment')
            ->willReturnCallback(
                function (
                    string $string,
                    string $filename,
                    string $encoding = 'base64',
                    string $type = '',
                    string $disposition = 'attachment',
                ) use (&$dataCalls): bool {
                    $dataCalls[] = [
                        'data' => $string,
                        'filename' => $filename,
                        'encoding' => $encoding,
                        'type' => $type,
                        'disposition' => $disposition,
                    ];
                    return true;
                },
            );

        build_mail(
            mail: $mail,
            to: 'to@example.com',
            from: 'from@example.com',
            subject: 'Attachment subject',
            text_message: 'Body',
            html_message: null,
            attachment_paths: [[
                'path' => $path,
                'filename' => 'custom-name.txt',
                'mimetype' => 'text/plain',
            ],
            ],
            attachment_data: [[
                'data' => 'raw-data',
                'filename' => 'raw.txt',
                'mimetype' => 'text/plain',
            ],
            ],
        );

        self::assertCount(1, $pathCalls);
        self::assertSame($path, $pathCalls[0]['path']);
        self::assertSame('custom-name.txt', $pathCalls[0]['name']);
        self::assertSame('text/plain', $pathCalls[0]['type']);

        self::assertCount(1, $dataCalls);
        self::assertSame('raw-data', $dataCalls[0]['data']);
        self::assertSame('raw.txt', $dataCalls[0]['filename']);
        self::assertSame('text/plain', $dataCalls[0]['type']);
    }

    public function test_send_mail_wraps_build_mail_exception(): void
    {
        $this->expectException(GLDException::class);
        $this->expectExceptionMessage('Failed to send email:');

        send_mail(
            to: 'to@example.com',
            from: 'from@example.com',
            subject: 'Subject',
            text_message: null,
            html_message: null,
        );
    }

    public function test_add_mail_attachment_paths_with_string_path(): void
    {
        $path = $this->createTempFile('hello world');

        $expectedMimeType = \mime_content_type($path);
        if ($expectedMimeType === false) {
            $expectedMimeType = 'application/octet-stream';
        }

        $mail = $this->getMockBuilder(PHPMailer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addAttachment'])
            ->getMock();

        $mail->expects($this->once())
            ->method('addAttachment')
            ->with(
                $path,
                '',
                $this->anything(),
                $expectedMimeType,
                'attachment',
            );

        add_mail_attachment_paths($mail, [$path]);
    }

    public function test_add_mail_attachment_paths_with_explicit_filename_and_mimetype(): void
    {
        $path = $this->createTempFile('hello world');

        $mail = $this->getMockBuilder(PHPMailer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addAttachment'])
            ->getMock();

        $mail->expects($this->once())
            ->method('addAttachment')
            ->with(
                $path,
                'report.txt',
                $this->anything(),
                'text/custom',
                'attachment',
            );

        add_mail_attachment_paths($mail, [[
            'path' => $path,
            'filename' => 'report.txt',
            'mimetype' => 'text/custom',
        ],
        ]);
    }

    public function test_add_mail_attachment_paths_with_explicit_filename_and_detected_mimetype(): void
    {
        $path = $this->createTempFile('hello world');

        $expectedMimeType = \mime_content_type($path);
        if ($expectedMimeType === false) {
            $expectedMimeType = 'application/octet-stream';
        }

        $mail = $this->getMockBuilder(PHPMailer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addAttachment'])
            ->getMock();

        $mail->expects($this->once())
            ->method('addAttachment')
            ->with(
                $path,
                'report.txt',
                $this->anything(),
                $expectedMimeType,
                'attachment',
            );

        add_mail_attachment_paths($mail, [[
            'path' => $path,
            'filename' => 'report.txt',
        ],
        ]);
    }

    public function test_add_mail_attachment_data_with_explicit_mimetype(): void
    {
        $captured = null;

        $mail = $this->getMockBuilder(PHPMailer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addStringAttachment'])
            ->getMock();

        $mail->expects($this->once())
            ->method('addStringAttachment')
            ->willReturnCallback(
                static function (
                    string $data,
                    string $filename,
                    string $encoding = 'base64',
                    string $type = '',
                    string $disposition = 'attachment',
                ) use (&$captured): bool {
                    $captured = [
                        'data' => $data,
                        'filename' => $filename,
                        'encoding' => $encoding,
                        'type' => $type,
                        'disposition' => $disposition,
                    ];

                    return true;
                },
            );

        add_mail_attachment_data($mail, [[
            'data' => 'hello world',
            'filename' => 'greeting.txt',
            'mimetype' => 'text/plain',
        ],
        ]);

        self::assertSame(
            [
                'data' => 'hello world',
                'filename' => 'greeting.txt',
                'encoding' => 'base64',
                'type' => 'text/plain',
                'disposition' => 'attachment',
            ],
            $captured,
        );
    }

    public function test_add_mail_attachment_data_with_detected_mimetype(): void
    {
        $captured = null;
        $data = 'hello world';

        $mail = $this->getMockBuilder(PHPMailer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addStringAttachment'])
            ->getMock();

        $mail->expects($this->once())
            ->method('addStringAttachment')
            ->willReturnCallback(
                static function (
                    string $arg_data,
                    string $filename,
                    string $encoding = 'base64',
                    string $type = '',
                    string $disposition = 'attachment',
                ) use (&$captured): bool {
                    $captured = [
                        'data' => $arg_data,
                        'filename' => $filename,
                        'encoding' => $encoding,
                        'type' => $type,
                        'disposition' => $disposition,
                    ];

                    return true;
                },
            );

        add_mail_attachment_data($mail, [[
            'data' => $data,
            'filename' => 'greeting.txt',
        ],
        ]);

        self::assertSame($data, $captured['data']);
        self::assertSame('greeting.txt', $captured['filename']);
        self::assertSame('base64', $captured['encoding']);
        self::assertSame('attachment', $captured['disposition']);
        self::assertIsString($captured['type']);
        self::assertNotSame('', $captured['type']);
    }

    public function test_split_email_address_with_string(): void
    {
        self::assertSame(
            ['user@example.com', ''],
            split_email_address('user@example.com'),
        );
    }

    public function test_split_email_address_with_email_address_object(): void
    {
        $address = $this->createEmailAddress('user@example.com', 'John Doe');

        self::assertSame(
            ['user@example.com', 'John Doe'],
            split_email_address($address),
        );
    }

    private function createTempFile(string $contents): string
    {
        $path = \tempnam(\sys_get_temp_dir(), 'phpunit-mail-');
        self::assertNotFalse($path);

        file_put_contents($path, $contents);

        $this->addToAssertionCount(1);

        return $path;
    }

    private function createEmailAddress(string $email, string $name): EmailAddress
    {
        $reflection = new \ReflectionClass(EmailAddress::class);
        /** @var EmailAddress $address */
        $address = $reflection->newInstanceWithoutConstructor();

        $address->email = $email;
        $address->name = $name;

        return $address;
    }
}
