import {AuthClientContext, AuthClientOptions, ErrorHandler} from "./types";
import {createStorage} from "./storage";
import {authenticatedFetch} from "./auth";

function buildRequestBody(data: Record<string, string>) {
    const body = new FormData();
    for (const key in data) {
        if (data.hasOwnProperty(key)) {
            body.append(key, data[key]);
        }
    }
    return body;
}

function buildBackendUrl(context: AuthClientContext, endpoint: string, data?: Record<string, string>): URL {
    const backendUrl = context.endpointUrl;
    const searchParams = backendUrl.searchParams;
    if (data) {
        for (const key in data) {
            searchParams.set(key, data[key]);
        }
    }
    searchParams.set('hawk-api-route', endpoint);
    // Add the state parameter to the URL - This will automatically invalidate caches on login/logout
    searchParams.set('state', context.storage.get('state'));
    return backendUrl;
}

function validateResponse(response: Response, context: AuthClientContext, endpoint: string) {
    if (response.status !== 200) {
        context.dispatchError('fetch-backend-data-failed', `Failed to fetch data from backend: [${response.status}], endpoint: ${endpoint}`);
    }

    return response;
}

export async function fetchBackendData(context: AuthClientContext, endpoint: string, data: Record<string, string>) {
    return validateResponse(
        await fetch(buildBackendUrl(context, endpoint), {
            method: 'POST',
            body: buildRequestBody(data)
        }),
        context,
        endpoint
    );
}

export async function fetchBackendDataWithToken(context: AuthClientContext, endpoint: string, data: Record<string, string>) {
    return validateResponse(
        await authenticatedFetch(context, buildBackendUrl(context, endpoint, data), {
            method: 'GET',
            // Enable caching, so we do not overwhelm the backend with requests
            cache: 'default'
        }),
        context,
        endpoint
    );
}

export async function extractResponseData(response: Response, fallback: any = null) {
    if (response.status !== 200) {
        return fallback;
    }

    if (response.headers.get('content-type') !== 'application/json') {
        return await response.text();
    }

    return await response.json();
}

export function handleMissingOptionalFeature(response: Response, context: AuthClientContext, feature: string): Response {
    if (response.status === 404) {
        context.dispatchError('missing-optional-feature', `The feature "${feature}" is not enabled in your api.`);
    }
    return response;
}

export function createContext(eventTarget: EventTarget, options: AuthClientOptions): AuthClientContext {
    const errorHandler = options.errorHandler ?? ((type, message) =>
        console.error('Auth Client error:', type, message));
    const wrappedErrorHandler: ErrorHandler = (type, message) => {
        const event = new CustomEvent('error', {
            detail: {
                error: type,
                message: message
            },
            cancelable: true
        });

        if (event.defaultPrevented) {
            return;
        }

        errorHandler(type, message);
    }
    const storage = createStorage();
    const eventDispatcher: AuthClientContext['dispatchEvent'] = (type, data?) => {
        eventTarget.dispatchEvent(
            data instanceof Event ? data : new CustomEvent(type, {
                detail: data,
                cancelable: true
            })
        )
    }

    return {
        get currentUrl() {
            return new URL(options.currentUrl || window.location.href);
        },
        get endpointUrl() {
            return new URL(options.endpointUrl || this.currentUrl.href);
        },
        dispatchEvent: eventDispatcher,
        dispatchError: wrappedErrorHandler,
        storage: storage
    };
}
