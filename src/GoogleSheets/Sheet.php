<?php

declare(strict_types=1);

namespace gldstdlib\GoogleSheets;

/**
 * Functions for entering form data into Google Sheets.
 *
 * Write permissions must be granted on the sheet to the account associated with
 * the client ID and secret.
 */
class Sheet
{
    /** @var list<Tab> */
    private ?array $tabs = null;

    /**
     * Creates a sheet object for an existing sheet.
     *
     * @param $api API object
     * @param $id ID (looks like 1Hmm8znDINMUiCt-YhZD9KI3tuzloXJWxEgiCNArvrTs)
     */
    public function __construct(
        private readonly API $api,
        public readonly string $id,
    ) {
    }

    /**
     * Returns a list of all tabs.
     *
     * @return list<Tab>
     */
    public function getTabs(): array
    {
        if (!isset($this->tabs)) {
            $this->tabs = [];
            foreach ($this->api->getService()->spreadsheets->get($this->id)->sheets as $sheet) {
                $this->tabs[] = new Tab($this->api, $this, $sheet->properties->title);
            }
        }
        return $this->tabs;
    }

    /**
     * Returns a tab.
     *
     * @param ?string $name Name of the tab. Null means the first tab.
     *
     * @return Tab
     *
     * @throws SheetsException If the tab does not exist
     */
    public function getTab(?string $name = null): Tab
    {
        foreach ($this->getTabs() as $tab) {
            if ($name === null || $tab->name == $name) {
                return $tab;
            }
        }
        throw new SheetsException(
            "Sheet {$this->id} has no tab named {$name}"
        );
    }

    /**
     * Removes cached data for the sheet stored in object properties.
     */
    public function clearCache(): void
    {
        $this->tabs = null;
    }
}
