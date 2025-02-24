import {storeTokenFromResponse} from "./storage";
import {extractResponseData, fetchBackendData} from "./util";
import {AuthClientContext, AuthClientStorage, EventType, eventTypes, User} from "./types";
import {generateCodeChallenge, generateRandomString} from "./authUtil";
import {fetchUserForToken} from "./user";
import {Guard} from "./permissions";

function isTokenExpired(storage: AuthClientStorage) {
    const expires = storage.get('token-expires');
    if (expires === null) {
        return true;
    }

    return Date.now() > parseInt(expires, 10) * 10000;
}

async function fetchLoginUrl(context: AuthClientContext, redirectUrl: URL, codeChallenge: string, state: string) {
    const res = await extractResponseData(
        await fetchBackendData(context, 'auth-login-url', {
            redirectUrl: redirectUrl + '',
            codeChallenge: codeChallenge,
            state: state
        })
    )

    if (!res || !(typeof res === 'object') || !res.url || res.url.length === 0) {
        context.dispatchError('login-failed-to-fetch-url', 'Failed to fetch login URL');
        throw new Error('Failed to fetch login URL');
    }

    return res.url;
}

async function fetchLogoutUrl(context: AuthClientContext, idToken: string, redirectUrl: URL) {
    const res = await extractResponseData(
        await fetchBackendData(context, 'auth-logout-url', {
            redirectUrl: redirectUrl + '',
            idToken: idToken
        })
    );

    if (!res || !(typeof res === 'object') || !res.url || res.url.length === 0) {
        context.dispatchError('logout-failed-to-fetch-url', 'Failed to fetch logout URL');
        throw new Error('Failed to fetch logout URL');
    }

    return res.url;
}

function buildLoginRedirectUrl(context: AuthClientContext) {
    const url = context.currentUrl;
    const sp = url.searchParams;
    sp.delete('code');
    sp.delete('state');
    sp.delete('session_state');
    sp.delete('iss');
    sp.set('frontend-login', 'true');
    return url;
}

export async function startLoginFlow(context: AuthClientContext, redirectAfterLogin?: string | URL) {
    const storage = context.storage;
    storage.clear();

    try {
        const codeVerifier = generateRandomString(128);
        storage.set('code-verifier', codeVerifier);
        storage.set('redirect-url-after-login', (redirectAfterLogin || context.currentUrl) + '');
        const callbackUrl = buildLoginRedirectUrl(context);
        storage.set('callback-url', callbackUrl + '');
        const codeChallenge = await generateCodeChallenge(codeVerifier);
        const state = generateRandomString(64);
        storage.set('state', state);
        window.location.href = await fetchLoginUrl(context, callbackUrl, codeChallenge, state);
    } catch (e) {
        context.dispatchError('login-failed', 'Failed to start login flow: ' + e.message);
        throw new Error('Failed to fetch login URL: ' + e);
    }
}

async function handleLoginCallback(context: AuthClientContext) {
    const storage = context.storage;
    const storedState = storage.get('state');
    if (storedState === null) {
        return;
    }

    const urlParams = context.currentUrl.searchParams;
    if (!urlParams.has('frontend-login')) {
        return;
    }

    if (urlParams.get('state') !== storedState) {
        context.dispatchError('login-state-mismatch', 'State mismatch, either not our request or CSRF attack; ignoring');
        storage.clear();
        return;
    }

    const code = urlParams.get('code');
    if (code === null) {
        storage.clear();
        context.dispatchError('login-callback-missing-code', 'No code in URL');
        return;
    }

    const codeVerifier = storage.get('code-verifier');
    if (codeVerifier === null) {
        storage.clear();
        context.dispatchError('login-callback-misses-verifier', 'No code verifier in storage');
        return;
    }

    if (!(await storeTokenFromResponse(
        context,
        await fetchBackendData(context, 'auth-exchange-code-for-token', {
            code: code,
            codeVerifier: codeVerifier,
            redirectUrl: storage.get('callback-url') + ''
        })
    ))) {
        context.dispatchError('login-failed-to-fetch-token', 'Failed to fetch token');
        return;
    }

    const redirectUrl = storage.get('redirect-url-after-login');

    storage.remove('code-verifier');
    storage.remove('callback-url');
    storage.remove('redirect-url-after-login');
    storage.set('trigger-event-after-redirect', 'login');
    window.location.href = redirectUrl;
}

export async function startLogoutFlow(context: AuthClientContext, redirectAfterLogout?: string | URL) {
    const redirectUrl = new URL((redirectAfterLogout || context.currentUrl) + '');
    const fail = (message: string) => {
        context.dispatchError('logout-failed', message);
        window.location.href = redirectUrl.href;
    }
    try {
        const idToken = context.storage.get('id-token');
        if (idToken === null) {
            fail('No id token found');
            return;
        }

        const logoutUrl = await fetchLogoutUrl(context, idToken, redirectUrl);
        logoutLocal(context);
        window.location.href = logoutUrl;
    } catch (e) {
        fail('An error occurred: ' + e.message);
    }
}

export async function refreshToken(context: AuthClientContext) {
    const refreshToken = context.storage.get('refresh-token');
    const state = context.storage.get('state');
    if (refreshToken === null) {
        context.dispatchError('failed-to-refresh-token', 'No refresh token found');
        context.storage.clear();
        return false;
    }

    if (!await storeTokenFromResponse(
        context,
        await fetchBackendData(context, 'auth-refresh-token', {
            refreshToken: refreshToken + ''
        }))) {
        context.dispatchError('failed-to-refresh-token', 'Failed to refresh token');
        return false;
    }

    // Re-instantiate the state
    context.storage.set('state', state);
    context.dispatchEvent('token-refresh');
    return true;
}

export async function authenticatedFetch(context: AuthClientContext, input: RequestInfo | URL, initRequest?: RequestInit) {
    const buildInit = () => {
        const init = initRequest || {};
        init.headers = init.headers || {};
        const token = context.storage.get('token');
        if (token !== null) {
            (init.headers as any).Authorization = 'Bearer ' + token;
        }
        return init;
    }

    const response = await fetch(input, buildInit());
    if (response.status === 401) {
        if (!await refreshToken(context)) {
            context.dispatchError('authenticated-fetch-failed', 'Failed to refresh token');
            throw new Error('Failed to refresh token, cannot authenticate request');
        }
        return await fetch(input, buildInit());
    }

    return response;
}

function triggerEventsAfterRedirect(context: AuthClientContext): void {
    const storage = context.storage;
    const event = storage.get('trigger-event-after-redirect') as EventType | null;
    if (eventTypes.indexOf(`${event}`) !== -1) {
        context.dispatchEvent(event);
    }
    storage.remove('trigger-event-after-redirect');
}

export async function createAuthentication(context: AuthClientContext): Promise<Authentication> {
    triggerEventsAfterRedirect(context);
    await handleLoginCallback(context);
    return new Authentication(context);
}

export function logoutLocal(context: AuthClientContext) {
    context.dispatchEvent('auth-state-changed');
    context.storage.clear();
    context.storage.set('trigger-event-after-redirect', 'logout');
}

export class Authentication {
    private readonly context: AuthClientContext;
    private authenticatedPromise: Promise<boolean> | null = null;

    constructor(context: AuthClientContext) {
        this.context = context;
    }

    /**
     * Returns the current token if it exists. Otherwise, returns null.
     */
    public getToken(): string | null {
        return this.context.storage.get('token');
    }

    /**
     * Returns true if the user is authenticated.
     */
    public isAuthenticated(): Promise<boolean> {
        if (this.authenticatedPromise === null) {
            this.authenticatedPromise = new Promise<boolean>(async resolve => {
                const storage = this.context.storage;
                if (storage.get('token') === null) {
                    resolve(false);
                    return;
                }

                if (isTokenExpired(storage)) {
                    if (await refreshToken(this.context)) {
                        resolve(!isTokenExpired(storage));
                        return;
                    }

                    resolve(false);
                    return;
                }

                resolve(true);
            }).then(r => {
                this.authenticatedPromise = null;
                return r;
            });
        }

        return this.authenticatedPromise;
    }

    /**
     * A wrapper around fetch that automatically adds the authentication token to the request.
     * If the 401 status is returned, the token is refreshed and the request is retried.
     */
    public async fetch(input: RequestInfo | URL, initRequest?: RequestInit) {
        return authenticatedFetch(this.context, input, initRequest);
    }

    /**
     * Manually triggers a token refresh.
     * Returns true if the token was successfully refreshed.
     * Returns false if the token could not be refreshed.
     */
    public refreshToken(): Promise<boolean> {
        return refreshToken(this.context);
    }

    /**
     * Returns the current user, if the user is authenticated.
     */
    public async getUser(): Promise<User | null> {
        return fetchUserForToken(this.context);
    }

    /**
     * Returns the profile information for the current user, if the user is authenticated.
     *
     * IMPORTANT: While the profile gives you access to ALL user data, it is a performance hit to load it every time.
     * If you know which attributes you need, you should create a custom "claim" for them that will be added to the "user-info" endpoint.
     * Those claims will be automatically available in the user object.
     */
    public async getProfile(): Promise<User | null> {
        return fetchUserForToken(this.context);
    }

    /**
     * Returns the guard for the current user, if present.
     * A guard is a set of permissions that the user has, describing what the user is allowed to do and with which resources.
     * You can also check if the user is part of a specific group or has a specific role.
     */
    public getGuard(): Guard {
        return new Guard(this.context, () => this.getUser());
    }
}
