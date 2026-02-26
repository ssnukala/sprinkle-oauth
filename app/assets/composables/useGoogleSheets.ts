/*
 * OAuth Sprinkle (https://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-oauth
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-oauth/blob/master/LICENSE.md (MIT License)
 */

import { ref, computed } from 'vue'
import type { SheetsReadResponse, SheetsAppendRequest, SheetsAppendResponse } from '../interfaces'

/**
 * Vue composable for Google Sheets integration via the OAuth Sprinkle backend.
 *
 * Provides methods to read from and append to Google Sheets using the
 * authenticated user's Google OAuth connection. All API calls go through
 * the backend endpoints which handle token refresh automatically.
 *
 * @param apiBaseUrl - Base URL for OAuth API (default: '/api/oauth')
 */
export function useGoogleSheets(apiBaseUrl: string = '/api/oauth') {
    const loading = ref(false)
    const error = ref<string | null>(null)
    const lastReadData = ref<SheetsReadResponse | null>(null)

    /**
     * Extract spreadsheet ID from a Google Sheets URL.
     *
     * Accepts URLs like:
     * - https://docs.google.com/spreadsheets/d/SPREADSHEET_ID/edit
     * - https://docs.google.com/spreadsheets/d/SPREADSHEET_ID/edit#gid=0
     * - Just the raw spreadsheet ID
     */
    function extractSpreadsheetId(urlOrId: string): string {
        const match = urlOrId.match(/\/spreadsheets\/d\/([a-zA-Z0-9_-]+)/)
        return match ? match[1] : urlOrId.trim()
    }

    /**
     * Read data from a Google Sheet.
     *
     * @param spreadsheetId - Spreadsheet ID or full Google Sheets URL
     * @param range - Sheet range (e.g., 'Sheet1!A1:Z100'). Defaults to 'Sheet1'
     * @returns Parsed sheet data with headers and rows
     */
    async function readSheet(
        spreadsheetId: string,
        range: string = 'Sheet1'
    ): Promise<SheetsReadResponse> {
        loading.value = true
        error.value = null

        const id = extractSpreadsheetId(spreadsheetId)

        try {
            const params = new URLSearchParams({
                spreadsheetId: id,
                range,
            })

            const response = await fetch(`${apiBaseUrl}/sheets/read?${params}`, {
                headers: { 'Accept': 'application/json' },
            })

            if (!response.ok) {
                const errorData = await response.json().catch(() => null)
                throw new Error(
                    errorData?.description || errorData?.message || `Failed to read sheet (${response.status})`
                )
            }

            const data: SheetsReadResponse = await response.json()
            lastReadData.value = data
            return data
        } catch (err: any) {
            error.value = err.message || 'Failed to read Google Sheet'
            throw err
        } finally {
            loading.value = false
        }
    }

    /**
     * Append rows to a Google Sheet.
     *
     * @param request - Append request with spreadsheet ID, rows, and optional columns
     * @returns Append result with updated range and row count
     */
    async function appendToSheet(request: SheetsAppendRequest): Promise<SheetsAppendResponse> {
        loading.value = true
        error.value = null

        const id = extractSpreadsheetId(request.spreadsheetId)

        try {
            const response = await fetch(`${apiBaseUrl}/sheets/append`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    spreadsheetId: id,
                    range: request.range || 'Sheet1',
                    rows: request.rows,
                    columns: request.columns,
                }),
            })

            if (!response.ok) {
                const errorData = await response.json().catch(() => null)
                throw new Error(
                    errorData?.description || errorData?.message || `Failed to append to sheet (${response.status})`
                )
            }

            return await response.json()
        } catch (err: any) {
            error.value = err.message || 'Failed to append to Google Sheet'
            throw err
        } finally {
            loading.value = false
        }
    }

    /**
     * Import sheet data as CSV text (for use with DataChat).
     *
     * Reads a Google Sheet and converts it to CSV format that can be
     * pasted into a DataChat textarea.
     *
     * @param spreadsheetId - Spreadsheet ID or full URL
     * @param range - Sheet range
     * @param separator - CSV separator (default: ',')
     * @returns CSV text string
     */
    async function importAsCSV(
        spreadsheetId: string,
        range: string = 'Sheet1',
        separator: string = ','
    ): Promise<string> {
        const data = await readSheet(spreadsheetId, range)

        if (!data.rows || data.rows.length === 0) {
            return ''
        }

        const lines: string[] = []

        // Header row
        if (data.headers?.length) {
            lines.push(data.headers.map(h => escapeCsvField(h, separator)).join(separator))
        }

        // Data rows
        for (const row of data.rows) {
            const values = data.headers.map(h => {
                const val = row[h]
                return escapeCsvField(val != null ? String(val) : '', separator)
            })
            lines.push(values.join(separator))
        }

        return lines.join('\n')
    }

    /**
     * Export data rows to a Google Sheet.
     *
     * @param spreadsheetId - Spreadsheet ID or full URL
     * @param rows - Array of row objects
     * @param columns - Column order (field keys)
     * @param range - Target sheet range
     */
    async function exportRows(
        spreadsheetId: string,
        rows: Record<string, any>[],
        columns?: string[],
        range: string = 'Sheet1'
    ): Promise<SheetsAppendResponse> {
        return appendToSheet({
            spreadsheetId,
            range,
            rows,
            columns,
        })
    }

    /**
     * Escape a CSV field value (quote if it contains separator, quotes, or newlines).
     */
    function escapeCsvField(value: string, separator: string): string {
        if (value.includes(separator) || value.includes('"') || value.includes('\n')) {
            return '"' + value.replace(/"/g, '""') + '"'
        }
        return value
    }

    return {
        loading,
        error,
        lastReadData,
        extractSpreadsheetId,
        readSheet,
        appendToSheet,
        importAsCSV,
        exportRows,
    }
}
