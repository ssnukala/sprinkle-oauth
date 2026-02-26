/*
 * OAuth Sprinkle TypeScript Interfaces
 *
 * @link      https://github.com/ssnukala/sprinkle-oauth
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-oauth/blob/master/LICENSE.md (MIT License)
 */

/**
 * OAuth provider definition for UI display.
 */
export interface OAuthProvider {
    id: string
    name: string
    icon: string
    color: string
}

/**
 * OAuth connection data from the backend.
 */
export interface OAuthConnection {
    id: number
    provider: string
    provider_user_id: string
    user_data?: Record<string, any>
    expires_at?: string
    created_at?: string
    updated_at?: string
}

/**
 * Result from an OAuth popup flow.
 */
export interface OAuthResult {
    success: boolean
    provider: string
    action: 'login' | 'link'
    message?: string
    user?: Record<string, any>
    isNewUser?: boolean
    redirect?: string
}

/**
 * Configuration options for the useOAuth composable.
 */
export interface OAuthConfig {
    /** Base URL for OAuth API endpoints (default: '/api/oauth') */
    apiBaseUrl?: string
    /** OAuth flow mode: 'popup' opens a popup window, 'redirect' navigates the current page */
    mode?: 'popup' | 'redirect'
    /** Popup window width (default: 500) */
    popupWidth?: number
    /** Popup window height (default: 600) */
    popupHeight?: number
    /** Redirect URL after successful login in redirect mode (default: '/dashboard') */
    redirectUrl?: string
}

/**
 * Google Sheets read response.
 */
export interface SheetsReadResponse {
    spreadsheetId: string
    range: string
    headers: string[]
    rows: Record<string, any>[]
    totalRows: number
}

/**
 * Google Sheets append request.
 */
export interface SheetsAppendRequest {
    spreadsheetId: string
    range?: string
    rows: Record<string, any>[]
    columns?: string[]
}

/**
 * Google Sheets append response.
 */
export interface SheetsAppendResponse {
    spreadsheetId: string
    updatedRange: string
    updatedRows: number
}
