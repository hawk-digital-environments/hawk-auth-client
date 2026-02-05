import {AuthClientContext, AuthClientStorage, StorageKey, storageKeys} from './types';
import {extractResponseData} from './util';
import {logoutLocal} from './auth';

export function createStorage(baseKey?: string): AuthClientStorage {
    // sessionStorage is only available in browser environments, so if it does not exist,
    // will return a no-op storage implementation
    if (typeof sessionStorage === 'undefined') {
        return {
            clear: () => void 0,
            remove: (key: StorageKey) => void 0,
            set: (key: StorageKey, value: string) => void 0,
            get: (key: StorageKey): string | null => null
        };
    }
    
    baseKey = baseKey || 'hawk_auth_client_';
    const getRealKey = (key: StorageKey) => baseKey + key;
    return {
        clear: () => {
            storageKeys.map(key => sessionStorage.removeItem(getRealKey(key)));
        },
        remove: (key: StorageKey) => {
            sessionStorage.removeItem(getRealKey(key));
        },
        set: (key: StorageKey, value: string) => {
            sessionStorage.setItem(getRealKey(key), value);
        },
        get: (key: StorageKey): string | null => sessionStorage.getItem(getRealKey(key))
    };
}

export async function storeTokenFromResponse(context: AuthClientContext, res: Response) {
    const data = await extractResponseData(res);

    if (!data || !data.token || !data.expires || !data.idToken) {
        context.dispatchError('invalid-token-response', 'Failed to refresh token');
        logoutLocal(context);
        return false;
    }

    context.storage.set('token', data.token);
    context.storage.set('token-expires', data.expires);
    context.storage.set('refresh-token', data.refreshToken);
    context.storage.set('id-token', data.idToken);
    context.dispatchEvent('auth-state-changed');
    return true;
}
