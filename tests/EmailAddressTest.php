<?php

declare(strict_types=1);

namespace gldstdlib\tests;

use gldstdlib\EmailAddress;
use PHPUnit\Framework\TestCase;

final class EmailAddressTest extends TestCase
{
    public function test_constructor_stores_name_and_email(): void
    {
        $adres = new EmailAddress('Remy Glaser', 'rglaser@gld.nl');

        self::assertSame('Remy Glaser', $adres->name);
        self::assertSame('rglaser@gld.nl', $adres->email);
    }

    public function test_format_returns_only_email_when_name_is_empty(): void
    {
        $adres = new EmailAddress('', 'rglaser@gld.nl');

        self::assertSame('rglaser@gld.nl', $adres->format());
    }

    public function test_format_returns_name_and_email_when_name_is_not_empty(): void
    {
        $adres = new EmailAddress('Remy Glaser', 'rglaser@gld.nl');

        self::assertSame('Remy Glaser <rglaser@gld.nl>', $adres->format());
    }

    public function test_createAddressList_returns_empty_string_for_empty_list(): void
    {
        self::assertSame('', EmailAddress::create_address_list([]));
    }

    public function test_createAddressList_with_string_list(): void
    {
        $lijst = [
            'a@example.com',
            'b@example.com',
        ];

        self::assertSame(
            'a@example.com,b@example.com',
            EmailAddress::create_address_list($lijst)
        );
    }

    public function test_createAddressList_with_emailAddress_objects(): void
    {
        $lijst = [
            new EmailAddress('Remy Glaser', 'rglaser@gld.nl'),
            new EmailAddress('', 'info@example.com'),
        ];

        self::assertSame(
            'Remy Glaser <rglaser@gld.nl>,info@example.com',
            EmailAddress::create_address_list($lijst)
        );
    }

    public function test_createAddressList_with_mixed_strings_and_objects(): void
    {
        $lijst = [
            'plain@example.com',
            new EmailAddress('Remy Glaser', 'rglaser@gld.nl'),
            new EmailAddress('', 'info@example.com'),
        ];

        self::assertSame(
            'plain@example.com,Remy Glaser <rglaser@gld.nl>,info@example.com',
            EmailAddress::create_address_list($lijst)
        );
    }
}
