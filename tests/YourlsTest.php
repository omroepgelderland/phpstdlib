<?php

declare(strict_types=1);

namespace gldstdlib;

use PHPUnit\Framework\TestCase;

final class YourlsTest extends TestCase
{
    private const URI = 'https://ogld.nl/yourls-api.php';

    public function test_get_shorturl_bestaand(): void
    {
        $yourls = new Yourls(self::URI);
        $short_url = $yourls->get_shorturl(
            'https://www.gld.nl/nieuws/8286931/deze-grote-geldersman-krijgt-na-eeuwen-een-passend-eerbetoon'
        );
        $this->assertEquals('https://ogld.nl/hVDJ', $short_url);
    }
}
