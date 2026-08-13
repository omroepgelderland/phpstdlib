<?php

declare(strict_types=1);

namespace gldstdlib\GoogleSheets;

/**
 * Handles communication with the Google API.
 */
final class API
{
    private ?\Google\Client $client = null;
    private ?\Google\Service\Sheets $service = null;

    /**
     * @param $application_name The name of the application in Google API.
     * @param $client_id The client ID from Google API.
     * @param $client_secret The client secret from Google API.
     * @param $access_type The access type for Google API. Default is 'offline'.
     * @param $accessToken Optional cached access token
     * @param ?callable(string): void $refreshAccessTokenCallback Optional
     * callback which is called when the access token is refreshed. The new
     * access token is passed as an argument to the callback.
     */
    public function __construct(
        private readonly string $application_name,
        private readonly string $client_id,
        private readonly string $client_secret,
        private readonly string $access_type = 'offline',
        private ?string $accessToken = null,
        private readonly mixed $refreshAccessTokenCallback = null,
    ) {
    }

    /**
     * Returns the client object
     */
    public function getClient(): \Google\Client
    {
        return $this->client ??= $this->createClient();
    }

    private function createClient(): \Google\Client
    {
        $client = new \Google\Client([
            'application_name' => $this->application_name,
            'scopes' => \Google\Service\Sheets::SPREADSHEETS,
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'access_type' => $this->access_type,
        ]);

        if ($this->accessToken !== null) {
            $client->setAccessToken($this->accessToken);
        }

        // If there is no previous token or it's expired.
        if ($this->accessToken === null || $client->isAccessTokenExpired()) {
            // Refresh the token if possible, else fetch a new one.
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            } else {
                throw new AccessTokenException('Google access token expired');
            }

            if ($this->refreshAccessTokenCallback !== null) {
                ($this->refreshAccessTokenCallback)($client->getAccessToken());
            }
        }

        return $client;
    }

    /**
     * Returns the service-object for Google Sheets
     */
    public function getService(): \Google\Service\Sheets
    {
        return $this->service ??= new \Google\Service\Sheets($this->getClient());
    }
}
