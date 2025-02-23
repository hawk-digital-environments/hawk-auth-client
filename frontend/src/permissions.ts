import {AuthClientContext, User} from "./types";
import {extractResponseData, fetchBackendDataWithToken, handleMissingOptionalFeature} from "./util";

async function fetchScopesForResource(context: AuthClientContext, resource: string): Promise<string[]> {
    const data = await extractResponseData(
        handleMissingOptionalFeature(
            await fetchBackendDataWithToken(context, 'permissions', {resource}),
            context,
            'permissions'
        )
    );

    if (!data || !Array.isArray(data?.scopes)) {
        return [];
    }

    return data.scopes;
}

function hasAny(list: string[], ...values: string[]): boolean {
    for (const value of values) {
        if (list.includes(value)) {
            return true;
        }
    }
    return false;
}

function stringStartsWith(s: string, prefix: string): boolean {
    return s.indexOf(prefix) === 0;
}

function hasAnyGroupOrHasChildOfAny(groups: string[], ...values: string[]): boolean {
    const comparePaths = (a: string, b: string): boolean => {
        const aNormalized = a.replace(/^\//, '');
        const bNormalized = b.replace(/^\//, '');
        if (aNormalized === bNormalized) {
            return true;
        }
        if (stringStartsWith(aNormalized, bNormalized)) {
            return true;
        }
    }
    for (const value of values) {
        for (const group of groups) {
            if (comparePaths(group, value)) {
                return true;
            }
        }
    }
    return false;
}

export class Guard {
    private readonly context: AuthClientContext;
    private readonly userFactory: () => Promise<User | null>;

    constructor(
        context: AuthClientContext,
        userFactory: () => Promise<User | null>
    ) {
        this.context = context;
        this.userFactory = userFactory;
    }

    /**
     * Checks if the user has any one of the given roles.
     * @param roles
     */
    public async hasAnyRole(...roles: string[]): Promise<boolean> {
        const user = await this.userFactory();
        if (user === null) {
            return false;
        }
        return hasAny(user.roles, ...roles);
    }

    /**
     * Checks if the user has any one of the given groups.
     * This method checks for exact matches only, the hierarchy is not considered.
     * @param groups
     */
    public async hasAnyGroup(...groups: string[]): Promise<boolean> {
        const user = await this.userFactory();
        if (user === null) {
            return false;
        }
        return hasAny(user.groups, ...groups);
    }

    /**
     * Checks if the list contains any of the given groups or any child of the given groups.
     * @param groups
     */
    public async hasAnyOrHasChildOfAny(...groups: string[]): Promise<boolean> {
        const user = await this.userFactory();
        if (user === null) {
            return false;
        }
        return hasAnyGroupOrHasChildOfAny(user.groups, ...groups);
    }

    /**
     * Checks if the user has any of the given scopes on the given resource.
     * Each resource can have fine-grained permissions, for any kind of action you can imagine.
     * Common scopes are "read", "write", "delete", "admin" etc.
     * If the user has no permissions on the given resource, false is returned.
     * @param resource The resource to check the permissions for. Either the name or uuid of the resource.
     * @param scopes A list of scopes to check for. If this list is empty, this method will return true as long as the user has any scope on the resource.
     */
    public async hasAnyResourceScope(resource: string, ...scopes: string[]): Promise<boolean> {
        const grantedScopes = await fetchScopesForResource(this.context, resource);

        if (scopes.length === 0) {
            return grantedScopes.length > 0;
        }

        return hasAny(
            grantedScopes,
            ...scopes
        )
    }
}
