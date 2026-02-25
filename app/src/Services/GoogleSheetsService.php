<?php

declare(strict_types=1);

/*
 * UserFrosting OAuth Sprinkle (https://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-oauth
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-oauth/blob/master/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\OAuth\Services;

use Google\Client as GoogleClient;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use UserFrosting\Sprinkle\OAuth\Repository\OAuthConnectionRepository;
use UserFrosting\Sprinkle\OAuth\Factory\OAuthProviderFactory;

/**
 * Google Sheets Service
 *
 * Provides read/write access to Google Sheets using stored OAuth tokens.
 * Handles automatic token refresh when tokens expire.
 */
class GoogleSheetsService
{
    public function __construct(
        protected OAuthProviderFactory $providerFactory,
        protected OAuthConnectionRepository $connectionRepository
    ) {
    }

    /**
     * Get authenticated Sheets client for a user.
     * Refreshes token automatically if expired.
     *
     * @param int $userId User ID
     * @return Sheets Authenticated Google Sheets service
     * @throws \RuntimeException If no Google connection exists
     */
    public function getSheetsClient(int $userId): Sheets
    {
        $connection = $this->connectionRepository->findByUserIdAndProvider($userId, 'google');
        if (!$connection) {
            throw new \RuntimeException('No Google OAuth connection found for user.');
        }

        $client = $this->providerFactory->getGoogleClient();
        $client->setAccessToken($connection->access_token);

        // Auto-refresh if token expired
        if ($client->isAccessTokenExpired() && $connection->refresh_token) {
            $newToken = $client->fetchAccessTokenWithRefreshToken($connection->refresh_token);

            if (!isset($newToken['error'])) {
                $this->connectionRepository->update($connection, [
                    'access_token' => $newToken['access_token'],
                    'expires_at' => isset($newToken['expires_in'])
                        ? date('Y-m-d H:i:s', time() + $newToken['expires_in'])
                        : null,
                ]);
            }
        }

        return new Sheets($client);
    }

    /**
     * Read rows from a Google Sheet.
     *
     * Returns the header row and data rows as associative arrays.
     *
     * @param int    $userId        User ID
     * @param string $spreadsheetId Google Spreadsheet ID
     * @param string $range         Sheet range (e.g., 'Sheet1' or 'Sheet1!A1:K')
     * @return array{header: string[], rows: array<array<string, string>>}
     */
    public function readSheet(int $userId, string $spreadsheetId, string $range = 'Sheet1'): array
    {
        $sheets = $this->getSheetsClient($userId);
        $response = $sheets->spreadsheets_values->get($spreadsheetId, $range);
        $values = $response->getValues() ?? [];

        if (empty($values)) {
            return ['header' => [], 'rows' => []];
        }

        $header = $values[0];
        $rows = array_map(function ($row) use ($header) {
            $obj = [];
            foreach ($header as $idx => $col) {
                $obj[$col] = $row[$idx] ?? '';
            }
            return $obj;
        }, array_slice($values, 1));

        return ['header' => $header, 'rows' => $rows];
    }

    /**
     * Append rows to a Google Sheet.
     *
     * @param int      $userId        User ID
     * @param string   $spreadsheetId Google Spreadsheet ID
     * @param array    $rows          Array of associative arrays (column => value)
     * @param string[] $columns       Column order for output
     * @param string   $range         Target range (e.g., 'Sheet1!A1')
     * @return array{updatedRows: int, updatedRange: string}
     */
    public function appendRows(
        int $userId,
        string $spreadsheetId,
        array $rows,
        array $columns,
        string $range = 'Sheet1!A1'
    ): array {
        $sheets = $this->getSheetsClient($userId);

        $values = array_map(function ($row) use ($columns) {
            return array_map(fn($col) => (string) ($row[$col] ?? ''), $columns);
        }, $rows);

        $body = new ValueRange(['values' => $values]);
        $result = $sheets->spreadsheets_values->append($spreadsheetId, $range, $body, [
            'valueInputOption' => 'RAW',
            'insertDataOption' => 'INSERT_ROWS',
        ]);

        return [
            'updatedRows' => $result->getUpdates()->getUpdatedRows(),
            'updatedRange' => $result->getUpdates()->getUpdatedRange(),
        ];
    }

    /**
     * Extract spreadsheet ID from a Google Sheets URL.
     *
     * @param string $url Google Sheets URL
     * @return string|null Spreadsheet ID or null if not a valid URL
     */
    public static function extractSpreadsheetId(string $url): ?string
    {
        if (preg_match('/\/spreadsheets\/d\/([a-zA-Z0-9-_]+)/', $url, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
