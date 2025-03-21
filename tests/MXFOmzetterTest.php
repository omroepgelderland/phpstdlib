<?php

declare(strict_types=1);

namespace gldstdlib;

use gldstdlib\exception\MXFOmzetterException;
use PHPUnit\Framework\TestCase;

final class MXFOmzetterTest extends TestCase
{
    private function test_callback(
        \FFMpeg\Media\AdvancedMedia $video,
        FFMpegMXFFormat $format,
        float $percentage
    ): void {
        $this->assertInstanceOf(\FFMpeg\Media\AdvancedMedia::class, $video);
        $this->assertInstanceOf(FFMpegMXFFormat::class, $format);
        $this->assertIsFloat($percentage);
        $this->assertGreaterThanOrEqual(0, $percentage);
        $this->assertLessThanOrEqual(100, $percentage);
    }

    public function test_exception(): void
    {
        $omzetter = new MXFOmzetter(path_join(__DIR__, '../testdata/mxf_enkel_stereo.mxf'));
        $this->expectException(MXFOmzetterException::class);
        $omzetter->omzetten('/tmp/mxftest.mxf');
    }

    public function test_dubbel_mono(): void
    {
        $omzetter = new MXFOmzetter(path_join(__DIR__, '../testdata/mxf_dubbel_mono.mxf'));
        $this->assertTrue($omzetter->is_dubbel_mono_mxf());
        $omzetter->omzetten('/tmp/mxftest.mxf', $this->test_callback(...));
    }

    public function test_dubbel_stereo(): void
    {
        $omzetter = new MXFOmzetter(path_join(__DIR__, '../testdata/mxf_dubbel_stereo.mxf'));
        $this->assertFalse($omzetter->is_dubbel_mono_mxf());
    }

    public function test_enkel_mono(): void
    {
        $omzetter = new MXFOmzetter(path_join(__DIR__, '../testdata/mxf_enkel_mono.mxf'));
        $this->assertFalse($omzetter->is_dubbel_mono_mxf());
    }

    public function test_enkel_stereo(): void
    {
        $omzetter = new MXFOmzetter(path_join(__DIR__, '../testdata/mxf_enkel_stereo.mxf'));
        $this->assertFalse($omzetter->is_dubbel_mono_mxf());
    }
}
