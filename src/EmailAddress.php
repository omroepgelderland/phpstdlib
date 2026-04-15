<?php

declare(strict_types=1);

namespace gldstdlib;

/**
 * Email address with name
 */
class EmailAddress
{
    /**
     * @param $name Name
     * @param $email Email address
     */
    public function __construct(
        public string $name,
        public string $email
    ) {
    }

    /**
     * Returns a formatted address with name and email.
     */
    public function format(): string
    {
        if (\strlen($this->name) === 0) {
            return $this->email;
        } else {
            return \sprintf(
                '%s <%s>',
                $this->name,
                $this->email
            );
        }
    }

    /**
     * Returns a formatted list of email addresses with names
     *
     * @param list<EmailAddress>|list<string> $list
     */
    public static function create_address_list(array $list): string
    {
        if (\count($list) === 0) {
            return '';
        }
        $str_arr = [];
        foreach ($list as $address) {
            if (\is_string($address)) {
                $str_arr[] = $address;
            } else {
                $str_arr[] = $address->format();
            }
        }
        return \implode(',', $str_arr);
    }
}
