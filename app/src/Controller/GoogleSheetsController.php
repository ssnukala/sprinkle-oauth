<?php

declare(strict_types=1);

/*
 * UserFrosting OAuth Sprinkle (https://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-oauth
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-oauth/blob/master/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\OAuth\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
use UserFrosting\Sprinkle\OAuth\Services\GoogleSheetsService;

/**
 * Google Sheets Controller
 *
 * REST API endpoints for reading and writing Google Sheets data.
 * Requires authenticated user with Google OAuth connection and spreadsheets scope.
 */
class GoogleSheetsController
{
    public function __construct(
        protected GoogleSheetsService $sheetsService,
        protected Authenticator $authenticator
    ) {
    }

    /**
     * Read data from a Google Sheet.
     *
     * GET /api/oauth/sheets/read?spreadsheetId=xxx&range=Sheet1
     */
    public function read(Request $request, Response $response): Response
    {
        $currentUser = $this->authenticator->user();
        if ($currentUser === null) {
            return $this->jsonResponse($response, ['error' => 'Authentication required'], 401);
        }

        $params = $request->getQueryParams();
        $spreadsheetId = $params['spreadsheetId'] ?? '';
        $range = $params['range'] ?? 'Sheet1';

        if (empty($spreadsheetId)) {
            return $this->jsonResponse($response, ['error' => 'spreadsheetId parameter is required'], 400);
        }

        // Extract ID from URL if a full URL was passed
        $extractedId = GoogleSheetsService::extractSpreadsheetId($spreadsheetId);
        if ($extractedId !== null) {
            $spreadsheetId = $extractedId;
        }

        try {
            $data = $this->sheetsService->readSheet($currentUser->id, $spreadsheetId, $range);
            return $this->jsonResponse($response, $data);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'error' => 'Failed to read sheet: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Append rows to a Google Sheet.
     *
     * POST /api/oauth/sheets/append
     * Body: { "spreadsheetId": "xxx", "columns": ["col1", "col2"], "rows": [{...}, ...], "range": "Sheet1!A1" }
     */
    public function append(Request $request, Response $response): Response
    {
        $currentUser = $this->authenticator->user();
        if ($currentUser === null) {
            return $this->jsonResponse($response, ['error' => 'Authentication required'], 401);
        }

        $body = (array) $request->getParsedBody();
        $spreadsheetId = $body['spreadsheetId'] ?? '';
        $columns = $body['columns'] ?? [];
        $rows = $body['rows'] ?? [];
        $range = $body['range'] ?? 'Sheet1!A1';

        if (empty($spreadsheetId)) {
            return $this->jsonResponse($response, ['error' => 'spreadsheetId is required'], 400);
        }

        if (empty($columns) || empty($rows)) {
            return $this->jsonResponse($response, ['error' => 'columns and rows are required'], 400);
        }

        // Extract ID from URL if a full URL was passed
        $extractedId = GoogleSheetsService::extractSpreadsheetId($spreadsheetId);
        if ($extractedId !== null) {
            $spreadsheetId = $extractedId;
        }

        try {
            $result = $this->sheetsService->appendRows(
                $currentUser->id,
                $spreadsheetId,
                $rows,
                $columns,
                $range
            );
            return $this->jsonResponse($response, $result, 201);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'error' => 'Failed to append to sheet: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Write JSON response.
     */
    protected function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
