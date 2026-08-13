<?php

declare(strict_types=1);

namespace gldstdlib\tests;

use gldstdlib\GoogleSheets\AccessTokenException;
use gldstdlib\GoogleSheets\API;
use gldstdlib\GoogleSheets\Date;
use gldstdlib\GoogleSheets\Sheet;
use gldstdlib\GoogleSheets\SheetsException;
use gldstdlib\GoogleSheets\Tab;
use gldstdlib\tests\GoogleSheets\FakeSheetsService;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class GoogleSheetsTest extends TestCase
{
    public function test_api_getClient_returns_cached_instance(): void
    {
        $api = new API('app', 'client-id', 'client-secret', 'offline', 'token');
        $client = new \Google\Client();

        $this->setPrivateProperty($api, 'client', $client);

        self::assertSame($client, $api->getClient());
    }

    public function test_api_getService_returns_cached_instance(): void
    {
        $api = new API('app', 'client-id', 'client-secret', 'offline', 'token');
        $service = new \Google\Service\Sheets();

        $this->setPrivateProperty($api, 'service', $service);

        self::assertSame($service, $api->getService());
    }

    public function test_api_getClient_throws_when_no_access_token_and_no_refresh_token(): void
    {
        $this->expectException(AccessTokenException::class);

        (new API('app', 'client-id', 'client-secret', 'offline', null))->getClient();
    }

    public function test_sheet_getTabs_returns_all_tabs_from_service(): void
    {
        $api = new API('app', 'client-id', 'client-secret', 'offline', 'token');
        $api = $this->withFakeService($api, [
            'First',
            'Second',
        ]);

        $sheet = new Sheet($api, 'sheet-id');

        $tabs = $sheet->getTabs();

        self::assertCount(2, $tabs);
        self::assertSame('First', $tabs[0]->name);
        self::assertSame('Second', $tabs[1]->name);
    }

    public function test_sheet_getTab_throws_for_missing_tab(): void
    {
        $api = new API('app', 'client-id', 'client-secret', 'offline', 'token');
        $api = $this->withFakeService($api, ['Alpha']);

        $sheet = new Sheet($api, 'sheet-id');

        $this->expectException(SheetsException::class);
        $this->expectExceptionMessage('has no tab named Missing');

        $sheet->getTab('Missing');
    }

    public function test_tab_getColumnTitles_ignores_empty_tail(): void
    {
        $tab = $this->tabWithData([
            "'Demo'!1:1" => [['Name', 'Email', '', 'Ignored']],
        ]);

        self::assertSame(['Name', 'Email'], $tab->getColumnTitles());
    }

    public function test_tab_appendAssoc_validates_keys_before_writing(): void
    {
        $tab = $this->tabWithData([
            "'Demo'!1:1" => [['Name', 'Email']],
        ]);

        $response = $tab->appendAssoc([
            'Name' => 'Alice',
            'Email' => 'alice@example.com',
        ]);

        self::assertInstanceOf(\Google\Service\Sheets\AppendValuesResponse::class, $response);
    }

    public function test_tab_appendAssoc_throws_for_unknown_column(): void
    {
        $tab = $this->tabWithData([
            "'Demo'!1:1" => [['Name', 'Email']],
        ]);

        $this->expectException(SheetsException::class);
        $this->expectExceptionMessage('has no column titled Unknown');

        $tab->appendAssoc(['Unknown' => 'value']);
    }

    public function test_tab_setAssoc_throws_for_unknown_column(): void
    {
        $tab = $this->tabWithData([
            "'Demo'!1:1" => [['Name', 'Email']],
        ]);

        $this->expectException(SheetsException::class);
        $this->expectExceptionMessage('has no column titled Missing');

        $tab->setAssoc([
            ['Missing' => 'value'],
        ]);
    }

    public function test_tab_getData_maps_headers_to_rows(): void
    {
        $tab = $this->tabWithData([
            "'Demo'" => [
                ['Name', 'Email'],
                ['Alice', 'alice@example.com'],
                ['Bob', 'bob@example.com'],
            ],
        ]);

        self::assertSame(
            [
                ['Name' => 'Alice', 'Email' => 'alice@example.com'],
                ['Name' => 'Bob', 'Email' => 'bob@example.com'],
            ],
            $tab->getData()
        );
    }

    public function test_tab_clearCache_resets_cached_state(): void
    {
        $tab = $this->tabWithData([
            "'Demo'!1:1" => [['Name', 'Email']],
            "'Demo'" => [
                ['Name', 'Email'],
                ['Alice', 'alice@example.com'],
            ],
        ]);

        self::assertSame(['Name', 'Email'], $tab->getColumnTitles());

        $tab->clearCache();

        self::assertSame(['Name', 'Email'], $tab->getColumnTitles());
    }

    public function test_tab_indexToColumnName_handles_common_cases(): void
    {
        self::assertSame('A', Tab::indexToColumnName(0));
        self::assertSame('B', Tab::indexToColumnName(1));
        self::assertSame('Z', Tab::indexToColumnName(25));
        self::assertSame('AA', Tab::indexToColumnName(26));
        self::assertSame('AB', Tab::indexToColumnName(27));
        self::assertSame('AZ', Tab::indexToColumnName(51));
        self::assertSame('BA', Tab::indexToColumnName(52));
        self::assertSame('AAA', Tab::indexToColumnName(702));
    }

    public function test_tab_setCell_uses_formatted_value_and_clears_cache(): void
    {
        $tab = $this->tabWithData([
            "'Demo'!1:1" => [['Name', 'Email']],
        ]);

        $response = $tab->setCell('B', 2, 'Alice');

        self::assertInstanceOf(\Google\Service\Sheets\UpdateValuesResponse::class, $response);
    }

    public function test_date_class_is_a_datetime_subclass(): void
    {
        $date = new Date('2024-01-02');

        self::assertInstanceOf(\DateTime::class, $date);
        self::assertSame('2024-01-02', $date->format('Y-m-d'));
    }

    private function tabWithData(array $valuesByRange): Tab
    {
        $api = new API('app', 'client-id', 'client-secret', 'offline', 'token');
        $api = $this->withFakeService($api, [], $valuesByRange);
        $sheet = new Sheet($api, 'sheet-id');

        return new Tab($api, $sheet, 'Demo');
    }

    private function withFakeService(API $api, array $tabs, array $valuesByRange = []): API
    {
        $service = new FakeSheetsService($tabs, $valuesByRange);
        $this->setPrivateProperty($api, 'service', $service);

        return $api;
    }

    private function setPrivateProperty(object $object, string $name, mixed $value): void
    {
        $property = new ReflectionProperty($object, $name);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }
}
