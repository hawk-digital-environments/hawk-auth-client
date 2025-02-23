export const errorTypes = [
    // Generic error when the login flow should have been started. Not sure what went wrong
    'login-failed',
    // Failed to fetch the keycloak login/authentication url from the backend server. Probably a misconfiguration or network error.
    'login-failed-to-fetch-url',
    // User authenticated against keycloak, but the state in the query does not match our locally stored state.
    // This means we either detected a CSRF attack, or another library got a callback with unfortunate timing.
    'login-state-mismatch',
    // User authenticated against keycloak, but we did not receive a "code" we could exchange for a token.
    'login-callback-missing-code',
    // User authenticated against keycloak, but the "PKCE" verifier is not in our storage. This is weird and unexpected, browser tinkering?
    'login-callback-misses-verifier',
    // User authenticated against keycloak, but failed to call the backend to exchange the code into a token.
    // Could have multiple reasons, maybe a misconfiguration or network error, maybe a connection issue of the backend to keycloak.
    'login-failed-to-fetch-token',
    // A token was received from the backend (either login or refresh) but the response does not have the required structure.
    // Take a look in the browser's network console as it is probably an issue in the OAuth flow
    'invalid-token-response',
    // The client tried to request  data (userinfo, profile, permissions,...) from the backend, but the server
    // responded with a non 200 code. This is most likely happening due to the user being not authenticated.
    'fetch-backend-data-failed',
    // The user tried to do an "authenticated fetch" using the auth's "fetch" implementation.
    // But even after refreshing the token the response status code is still not 200. This is probably an issue in the
    // endpoint you are trying to reach.
    'authenticated-fetch-failed',
    // The client failed to refresh the current access token. Either because the refresh token was not found in storage (which is weird)
    // or because the server responded with unexpected data (which may happen due to session timeouts or connection issues with the backend or keycloak).
    'failed-to-refresh-token',
    // Generic error when the logout flow should have been started. Not sure what went wrong
    'logout-failed',
    // Failed to fetch the keycloak logout url from the backend server. Probably a misconfiguration or network error.
    'logout-failed-to-fetch-url',
    // You tried to use a function that requires a frontend-api feature that is not enabled.
    'missing-optional-feature',
] as const;
export type ErrorType = typeof errorTypes[number];

export type ErrorHandler = (type: ErrorType, message: string) => void;

export const eventTypes = [
    // Emitted every time the authentication state is updated, this includes, login, logout and token-refresh
    'auth-state-changed',
    // Emitted when a user logged in. This event will be executed AFTER all redirects are done.
    'login',
    // Emitted when a user has logged out. This event will be executed AFTER all redirects are done.
    'logout',
    // Emitted every time access token gets refreshed, both automatic and manual.
    'token-refresh',
    // Emitted every time an error occurs. If "preventDefault" is triggered, the error handler will not detect this error!
    'error'
] as const;
export type EventType = typeof eventTypes[number];

export interface AuthClientContext {
    /**
     * The url of the server endpoint that is configured to handle our requests.
     */
    endpointUrl: URL;

    /**
     * The current URL of the page.
     */
    currentUrl: URL;

    /**
     * Dispatches an event that can be listened to on the client
     * @param type
     * @param data
     */
    dispatchEvent: (type: EventType, data?: any) => void;

    /**
     * A function that can be used to dispatch errors to the user.
     * If the error handler is configured in the AuthClientOptions, it will be called with the error type and message.
     */
    dispatchError: ErrorHandler;

    /**
     * A storage object that can be used to store and retrieve data.
     * By default, the data is stored in the browser's localStorage.
     */
    storage: AuthClientStorage;
}

export interface AuthClientOptions {
    /**
     * The url of the server endpoint that is configured to handle our requests.
     * If omitted the current URL of the browser will be used.
     * This defines the route where you configured the auth client's frontend api on the server.
     * Note, that the route MUST be able to receive GET and POST requests.
     */
    endpointUrl: string;

    /**
     * The current URL of the page. If omitted window.location.href will be used.
     */
    currentUrl?: string;

    /**
     * A function that will be called when an error occurs.
     * You can use this to display error messages to the user or handle errors in any other way.
     */
    errorHandler?: ErrorHandler;
}

export const storageKeys = [
    'token',
    'refresh-token',
    'token-expires',
    'id-token',
    'code-verifier',
    'state',
    'redirect-url-after-login',
    'callback-url',
    'trigger-event-after-redirect'
] as const;
export type StorageKey = typeof storageKeys[number];

export interface AuthClientStorage {
    /**
     * Clears all stored data.
     */
    clear(): void;

    /**
     * Removes a single item from storage.
     */
    remove(key: StorageKey): void;

    /**
     * Stores a value in the storage.
     */
    set(key: StorageKey, value: string): void;

    /**
     * Retrieves a value from storage.
     */
    get(key: StorageKey): string | null;
}

interface UserWithoutClaims {
    /**
     * The unique identifier of the user. This is usually a UUID.
     */
    id: string;

    /**
     * The username of the user.
     */
    username: string;

    /**
     * Claims are additional information about the user.
     * They are provided by keycloak through "Client scopes" and "Protocol mappers".
     * Claims can contain any data configure for your realm, as long as the "Add to userinfo" switch is enabled.
     */
    claims: Record<string, any>;

    /**
     * The roles of the user.
     * Roles are provided by keycloak through "Client roles" and "Realm roles".
     */
    roles: string[];

    /**
     * The groups of the user.
     * Groups are provided by keycloak through "Groups".
     */
    groups: string[];

    /**
     * Returns the user's profile.
     * Note that this is a promise, because the profile might not be loaded yet.
     * Also note, that the "user-profile" is a separate endpoint from the "user-info" and might not be available in all setups!
     *
     * IMPORTANT: While the profile gives you access to ALL user data, it is a performance hit to load it every time.
     * If you know which attributes you need, you should create a custom "claim" for them that will be added to the "user-info" endpoint.
     * Those claims will be automatically available in the user object.
     */
    profile: Promise<UserProfile | null>;
}

export type User<CLAIMS = Record<string, any>> = UserWithoutClaims & CLAIMS;

interface UserProfileWithoutAttributes {
    username: string;
    firstName: string;
    lastName: string;
    email: string;
    attributes: Record<string, string>;
    structure: {
        attributes: Array<any>;
        attributesLocal: Record<string, string>;
        groups: Array<any>;
        groupsLocal: Record<string, string>;
    },
    additionalData: Record<string, string>
}

export type UserProfile<ATTRIBUTES = Record<string, string>, ADDITIONAL_DATA = Record<string, string>> =
    UserProfileWithoutAttributes
    & ATTRIBUTES
    & ADDITIONAL_DATA;
