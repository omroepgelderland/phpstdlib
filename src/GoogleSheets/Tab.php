<?php

declare(strict_types=1);

namespace gldstdlib\GoogleSheets;

/**
 * @phpstan-type CellValue bool|string|float
 * @phpstan-type InputCellValue CellValue|\DateTimeInterface|null
 * @phpstan-type InputRow array<string, InputCellValue>
 */
class Tab
{
    /** @var ?list<string> */
    private ?array $columnTitles = null;
    /** @var ?list<array<CellValue, CellValue>> */
    private ?array $data = null;

    /**
     * Creates a new object.
     *
     * @param $api API object
     * @param $sheet Spreadsheet.
     * @param $name Name of the tab.
     */
    public function __construct(
        private readonly API $api,
        private readonly Sheet $sheet,
        public readonly string $name,
    ) {
    }

    /**
     * Appends a new row to a sheet based on column titles.
     * The input data is an associative array whose keys are the column titles
     * in the sheet.
     *
     * @param InputRow $data
     *
     * @throws SheetsException If the input data contains keys that are not
     * present in the spreadsheet.
     */
    public function appendAssoc(array $data): \Google\Service\Sheets\AppendValuesResponse
    {
        $column_titles = $this->getColumnTitles();

        foreach (\array_keys($data) as $key) {
            if (!\in_array($key, $column_titles)) {
                throw new SheetsException(
                    "Sheet {$this->sheet->id} tab {$this->name} has no column titled {$key}",
                );
            }
        }

        $input_data = [];
        foreach ($column_titles as $column_title) {
            if (\array_key_exists($column_title, $data)) {
                $value_to_add = $data[$column_title];
            } else {
                $value_to_add = '';
            }
            $input_data[] = self::formatInput($value_to_add);
        }

        $this->data = null;

        return $this->api->getService()->spreadsheets_values->append(
            $this->sheet->id,
            $this->getRange(),
            new \Google\Service\Sheets\ValueRange([
                'values' => [$input_data],
            ]),
            [
                'valueInputOption' => 'USER_ENTERED',
            ],
        );
    }

    /**
     * Overwrites data in the sheet starting from the first row below the column
     * titles.
     *
     * @param list<InputRow> $data Array of associative arrays with the column
     * titles as keys.
     *
     * @throws SheetsException If an input key does not match a column in the
     * sheet.
     */
    public function setAssoc(array $data): \Google\Service\Sheets\UpdateValuesResponse
    {
        $column_titles = $this->getColumnTitles();
        $column_keys = \array_flip($column_titles);

        $input_data = [];
        foreach ($data as $item) {
            $input_item = \array_fill(0, \count($column_titles), '');
            foreach ($item as $key => $value) {
                if (!\array_key_exists($key, $column_keys)) {
                    throw new SheetsException(
                        "Sheet {$this->sheet->id} tab {$this->name} has no column titled {$key}",
                    );
                }
                $column = $column_keys[$key];
                $input_item[$column] = self::formatInput($value);
            }
            $input_data[] = $input_item;
        }

        $this->data = null;

        return $this->api->getService()->spreadsheets_values->update(
            $this->sheet->id,
            $this->getRange('2', (string)(\count($input_data) + 1)),
            new \Google\Service\Sheets\ValueRange([
                'values' => $input_data,
            ]),
            [
                'valueInputOption' => 'USER_ENTERED',
            ],
        );
    }

    /**
     * Returns the content of a tab as an associative array.
     * The headers on row 1 are the keys.
     *
     * @return list<array<CellValue, CellValue>>
     */
    public function getData(): array
    {
        if ($this->data === null) {
            $result = $this->api->getService()->spreadsheets_values->get($this->sheet->id, $this->getRange());
            $data = $result->getValues();
            $header = \array_shift($data);
            $this->data = [];
            foreach ($data as $row) {
                $row = \array_slice($row, 0, \count($header));
                $item = [];
                foreach ($row as $i => $cell) {
                    $item[$header[$i]] = $cell;
                }
                $this->data[] = $item;
            }
        }
        return $this->data;
    }

    /**
     * Writes a value to a single cell.
     *
     * @param $column Column in A1 notation (A, B, C, ...).
     * @param $row Row number (1, 2, 3, ...).
     * @param InputCellValue $value
     */
    public function setCell(
        string $column,
        int $row,
        mixed $value,
    ): \Google\Service\Sheets\UpdateValuesResponse {
        $this->data = null;
        $this->columnTitles = null;
        return $this->api->getService()->spreadsheets_values->update(
            $this->sheet->id,
            $this->getRange($column . $row, $column . $row),
            new \Google\Service\Sheets\ValueRange([
                'values' => [[self::formatInput($value)]],
            ]),
            [
                'valueInputOption' => 'USER_ENTERED',
            ],
        );
    }

    /**
     * Removes cached data for the tab stored in object properties.
     */
    public function clearCache(): void
    {
        $this->data = null;
        $this->columnTitles = null;
    }

    /**
     * Returns the column titles that appear in the first row.
     * The list is cut off when an empty cell appears.
     *
     * @return list<string> List of column titles
     */
    public function getColumnTitles(): array
    {
        if ($this->columnTitles !== null) {
            return $this->columnTitles;
        }

        $cells = $this->api->getService()->spreadsheets_values->get(
            $this->sheet->id,
            $this->getRange('1', '1'),
        )->values[0];
        $empty_position = \array_search('', $cells);
        if ($empty_position === false) {
            return $this->columnTitles = $cells;
        } else {
            return $this->columnTitles = \array_values(\array_slice($cells, 0, (int)$empty_position));
        }
    }

    /**
     * Returns the range in A1 notation including the current tab name.
     * https://developers.google.com/sheets/api/guides/concepts
     *
     * @param $start (optional)
     * @param $end (optional)
     */
    private function getRange(?string $start = null, ?string $end = null): string
    {
        $tab0 = \str_replace("'", "''", $this->name);
        $tab = "'{$tab0}'";
        if ($start === null) {
            return $tab;
        } elseif ($end === null) {
            return "{$tab}!{$start}";
        } else {
            return "{$tab}!{$start}:{$end}";
        }
    }

    /**
     * Returns the number of rows (excluding the column names in the first row).
     */
    public function getRowCount(): int
    {
        return $this->getMaxRow() - 1;
    }

    /**
     * Returns the contents of a block within a tab as an associative array.
     * The headers on row 1 are the keys.
     *
     * @param $end_column Last column. Empty columns at the end are ignored.
     * @param $end_row Last row. Empty rows at the end are ignored.
     * @param $start_column First column (default A).
     * @param $start_row First row (default 1).
     *
     * @return list<array<CellValue, CellValue>>
     */
    public function getDataBlock(
        string $end_column,
        int $end_row,
        string $start_column = 'A',
        int $start_row = 1,
    ): array {
        $range = $this->getRange($start_column . $start_row, $end_column . $end_row);
        $result = $this->api->getService()->spreadsheets_values->get($this->sheet->id, $range);
        $data = $result->getValues();
        $header = \array_shift($data);
        $assoc = [];
        foreach ($data as $row) {
            $item = [];
            foreach ($row as $i => $cell) {
                $item[$header[$i]] = $cell;
            }
            $assoc[] = $item;
        }
        return $assoc;
    }

    /**
     * Returns the last column that contains data as an array index (int
     * starting at 0).
     */
    public function getMaxColumnIndex(): int
    {
        $max = 0;
        foreach ($this->getData() as $row) {
            if (\count($row) > $max) {
                $max = \count($row);
            }
        }
        return $max - 1;
    }

    /**
     * Returns the last column with data in A1 notation.
     */
    public function getMaxColumn(): string
    {
        return self::indexToColumnName($this->getMaxColumnIndex());
    }

    /**
     * Returns the number of the last row that contains data.
     */
    public function getMaxRow(): int
    {
        return \count($this->getData()) + 1;
    }

    /**
     * Formats the input by type for entry into a cell.
     *
     * @param InputCellValue $input
     *
     * @return CellValue
     */
    private static function formatInput(mixed $input)
    {
        if ($input === null) {
            return '';
        } elseif ($input instanceof Date) {
            return $input->format('Y-m-d');
        } elseif ($input instanceof \DateTimeInterface) {
            return $input->format('Y-m-d H:i:s');
        } elseif (\is_string($input)) {
            $input = \str_replace('"', '""', $input);
            return \sprintf(
                '=TO_TEXT("%s")',
                $input,
            );
        } else {
            return $input;
        }
    }

    /**
     * Converts an array index number to a column title in A1 notation.
     *
     * @param $i Index.
     *
     * @return string Column title
     */
    public static function indexToColumnName(int $i): string
    {
        $i++;
        $ans = '';
        while ($i > 0) {
            $mod = ($i - 1) % 26;
            $ans = \range('A', 'Z')[$mod] . $ans;
            $i = \floor(($i - $mod) / 26);
        }
        return $ans;
    }

    /**
     * Clears cells.
     *
     * @param $from Starting cell in A1 notation.
     * @param $to Ending cell in A1 notation.
     */
    public function clear(string $from, string $to): void
    {
        $this->api->getService()->spreadsheets_values->clear(
            $this->sheet->id,
            $this->getRange($from, $to),
            new \Google\Service\Sheets\ClearValuesRequest(),
        );
    }
}
