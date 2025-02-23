import {AuthClientContext, AuthClientOptions, EventType} from "./types";
import {Authentication, createAuthentication, startLoginFlow, startLogoutFlow} from "./auth";
import {createContext} from "./util";

export default class HawkAuthClient extends EventTarget {
    private readonly context: AuthClientContext;
    private authentication: Promise<Authentication> | null = null;

    constructor(options: AuthClientOptions) {
        super();
        this.context = createContext(this, options);
    }

    /**
     * @inheritDoc
     */
    public addEventListener(type: EventType, callback: EventListenerOrEventListenerObject | null, options?: AddEventListenerOptions | boolean) {
        super.addEventListener(type, callback, options);
    }

    /**
     * @inheritDoc
     */
    public removeEventListener(type: EventType, callback: EventListenerOrEventListenerObject | null, options?: EventListenerOptions | boolean) {
        super.removeEventListener(type, callback, options);
    }

    /**
     * Starts the login process.
     * This is normally done when the user clicks a login button.
     */
    public login(redirectAfterLogin?: string | URL) {
        return startLoginFlow(this.context, redirectAfterLogin);
    }

    /**
     * Starts the logout process.
     * This is normally done when the user clicks a logout button.
     * If the user is not logged in, this function will do nothing.
     * @param redirectAfterLogout
     */
    public logout(redirectAfterLogout?: string | URL) {
        return startLogoutFlow(this.context, redirectAfterLogout);
    }

    /**
     * Returns the current authentication state.
     * It allows access to the current user's token, data, and more.
     */
    public async getAuth(): Promise<Authentication> {
        if (this.authentication === null) {
            this.authentication = createAuthentication(this.context);
        }
        return await this.authentication;
    }
}
