<?php

declare(strict_types=1);

namespace gldstdlib\safe;

class PcreException extends SafeException
{
    public static function createFromPhpError(): static
    {
        $errorMap = [
            \PREG_INTERNAL_ERROR => 'PREG_INTERNAL_ERROR: Internal error',
            \PREG_BACKTRACK_LIMIT_ERROR => 'PREG_BACKTRACK_LIMIT_ERROR: Backtrack limit reached',
            \PREG_RECURSION_LIMIT_ERROR => 'PREG_RECURSION_LIMIT_ERROR: Recursion limit reached',
            \PREG_BAD_UTF8_ERROR => 'PREG_BAD_UTF8_ERROR: Invalid UTF8 character',
            \PREG_BAD_UTF8_OFFSET_ERROR => 'PREG_BAD_UTF8_OFFSET_ERROR',
            \PREG_JIT_STACKLIMIT_ERROR => 'PREG_JIT_STACKLIMIT_ERROR',
        ];
        $errMsg = $errorMap[preg_last_error()] ?? 'Unknown PCRE error: ' . preg_last_error();
        return new static($errMsg, \preg_last_error());
    }
}
