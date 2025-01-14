<?php

declare(strict_types=1);

namespace gldstdlib\safe;

class FtpException extends SafeException
{
    public static function createFromPhpError(): static
    {
        $error = error_get_last();
        return new static($error['message'] ?? 'An error occurred', 0, $error['type'] ?? 1);
    }
}
