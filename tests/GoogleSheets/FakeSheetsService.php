<?php

declare(strict_types=1);

namespace gldstdlib\tests\GoogleSheets;

final class FakeSheetsService extends \Google\Service\Sheets
{
    public function __construct(array $tabs, array $valuesByRange = [])
    {
        $this->spreadsheets = new class ($tabs) {
            public function __construct(private array $tabs)
            {
            }

            public function get(string $sheetId): object
            {
                return new class ($this->tabs) {
                    public array $sheets;

                    public function __construct(array $tabs)
                    {
                        $this->sheets = [];
                        foreach ($tabs as $tabName) {
                            $this->sheets[] = (object)['properties' => (object)['title' => $tabName]];
                        }
                    }
                };
            }
        };

        $this->spreadsheets_values = new FakeSheetsValues($valuesByRange);
    }
}
