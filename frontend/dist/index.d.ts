import { AuthClientOptions, EventType } from './types';
import { Authentication } from './auth';
export default class HawkAuthClient extends EventTarget {
    private readonly context;
    private authentication;
    constructor(options?: AuthClientOptions);
    /**
     * @inheritDoc
     */
    addEventListener(type: EventType, callback: EventListenerOrEventListenerObject | null, options?: AddEventListenerOptions | boolean): void;
    /**
     * @inheritDoc
     */
    removeEventListener(type: EventType, callback: EventListenerOrEventListenerObject | null, options?: EventListenerOptions | boolean): void;
    /**
     * Starts the login process.
     * This is normally done when the user clicks a login button.
     */
    login(redirectAfterLogin?: string | URL): Promise<void>;
    /**
     * Starts the logout process.
     * This is normally done when the user clicks a logout button.
     * If the user is not logged in, this function will do nothing.
     * @param redirectAfterLogout
     */
    logout(redirectAfterLogout?: string | URL): Promise<void>;
    /**
     * Returns the current authentication state.
     * It allows access to the current user's token, data, and more.
     */
    getAuth(): Promise<Authentication>;
}
