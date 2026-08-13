<?php

declare(strict_types=1);

namespace gldstdlib\tests\GoogleSheets;

final class FakeSheetsValues
{
    public function __construct(private array $valuesByRange = [])
    {
    }

    public function get(string $sheetId, string $range): object
    {
        $values = $this->valuesByRange[$range] ?? [];

        return new class ($values) {
            public function __construct(public array $values)
            {
            }

            public function getValues(): array
            {
                return $this->values;
            }
        };
    }

    public function append(string $spreadsheetId, string $range, object $valueRange, array $options = []): \Google\Service\Sheets\AppendValuesResponse
    {
        return new \Google\Service\Sheets\AppendValuesResponse();
    }

    public function update(string $spreadsheetId, string $range, object $valueRange, array $options = []): \Google\Service\Sheets\UpdateValuesResponse
    {
        return new \Google\Service\Sheets\UpdateValuesResponse();
    }

    public function clear(string $spreadsheetId, string $range, object $request): void
    {
    }
}
