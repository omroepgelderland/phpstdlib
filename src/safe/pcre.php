<?php

declare(strict_types=1);

namespace gldstdlib\safe;

/**
 * Searches subject for matches to
 * pattern and replaces them with
 * replacement.
 *
 * @param string[]|string $pattern The pattern to search for. It can be either a string or an array with
 * strings.
 *
 * Several PCRE modifiers
 * are also available.
 * @param string[]|string $replacement The string or an array with strings to replace. If this parameter is a
 * string and the pattern parameter is an array,
 * all patterns will be replaced by that string. If both
 * pattern and replacement
 * parameters are arrays, each pattern will be
 * replaced by the replacement counterpart. If
 * there are fewer elements in the replacement
 * array than in the pattern array, any extra
 * patterns will be replaced by an empty string.
 *
 * replacement may contain references of the form
 * \\n or
 * $n, with the latter form
 * being the preferred one. Every such reference will be replaced by the text
 * captured by the n'th parenthesized pattern.
 * n can be from 0 to 99, and
 * \\0 or $0 refers to the text matched
 * by the whole pattern. Opening parentheses are counted from left to right
 * (starting from 1) to obtain the number of the capturing subpattern.
 * To use backslash in replacement, it must be doubled
 * ("\\\\" PHP string).
 *
 * When working with a replacement pattern where a backreference is
 * immediately followed by another number (i.e.: placing a literal number
 * immediately after a matched pattern), you cannot use the familiar
 * \\1 notation for your backreference.
 * \\11, for example, would confuse
 * preg_replace since it does not know whether you
 * want the \\1 backreference followed by a literal
 * 1, or the \\11 backreference
 * followed by nothing.  In this case the solution is to use
 * ${1}1.  This creates an isolated
 * $1 backreference, leaving the 1
 * as a literal.
 *
 * When using the deprecated e modifier, this function escapes
 * some characters (namely ', ",
 * \ and NULL) in the strings that replace the
 * backreferences. This is done to ensure that no syntax errors arise
 * from backreference usage with either single or double quotes (e.g.
 * 'strlen(\'$1\')+strlen("$2")'). Make sure you are
 * aware of PHP's string
 * syntax to know exactly how the interpreted string will look.
 * @param string[]|string $subject The string or an array with strings to search and replace.
 *
 * If subject is an array, then the search and
 * replace is performed on every entry of subject,
 * and the return value is an array as well.
 * @param $limit The maximum possible replacements for each pattern in each
 * subject string. Defaults to
 * -1 (no limit).
 * @param $count If specified, this variable will be filled with the number of
 * replacements done.
 * @return ($subject is string ? string : string[]) preg_replace returns an array if the
 * subject parameter is an array, or a string
 * otherwise.
 *
 * If matches are found, the new subject will
 * be returned, otherwise subject will be
 * returned unchanged.
 *
 * @throws PcreException
 *
 */
function preg_replace(  // @phpstan-ignore parameterByRef.unusedType
    array|string $pattern,
    array|string $replacement,
    array|string $subject,
    int $limit = -1,
    ?int &$count = null,
): array|string {
    \error_clear_last();
    $result = \preg_replace($pattern, $replacement, $subject, $limit, $count);
    if (\preg_last_error() !== \PREG_NO_ERROR || $result === null) {
        throw PcreException::createFromPhpError();
    }
    return $result;
}
