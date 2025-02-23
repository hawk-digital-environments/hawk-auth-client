import {AuthClientContext, User, UserProfile} from "./types";
import {extractResponseData, fetchBackendDataWithToken, handleMissingOptionalFeature} from "./util";

export async function fetchUserForToken(context: AuthClientContext): Promise<User | null> {
    const res = await extractResponseData(
        handleMissingOptionalFeature(
            await fetchBackendDataWithToken(context, 'user-info', {}),
            context,
            'user-info'
        )
    )

    if (!res || !(typeof res === 'object')) {
        return null;
    }

    // Add all claims to the object, so we can access them directly
    const claims = res?.claims ?? {};
    for (const key in claims) {
        if (claims.hasOwnProperty(key)) {
            const jsKey = stringToJsProperty(key);
            if (!res.hasOwnProperty(jsKey)) {
                res[jsKey] = claims[key];
            }
        }
    }

    Object.defineProperty(res, 'profile', {
        get: async function () {
            return await fetchProfileForToken(context);
        }
    })

    return res;
}

/**
 * Replaces all dashes and underscores with camelCase.
 * @param string
 */
function stringToJsProperty(string: string): string {
    return string.replace(/[-_](.)/g, (_, group) => group.toUpperCase());
}

export async function fetchProfileForToken(context: AuthClientContext): Promise<UserProfile | null> {
    const res = await extractResponseData(
        handleMissingOptionalFeature(
            await fetchBackendDataWithToken(context, 'user-profile', {}),
            context,
            'user-profile'
        )
    )

    if (!res || !(typeof res === 'object')) {
        return null;
    }

    // Add local attributes to the object, so we can access them directly
    const attributesLocal = res?.structure?.attributesLocal ?? {};
    for (const key in attributesLocal) {
        if (attributesLocal.hasOwnProperty(key)) {
            const jsKey = stringToJsProperty(key);
            if (!res.hasOwnProperty(jsKey)) {
                res[jsKey] = res.attributes[attributesLocal[key]] ?? undefined;
            }
        }
    }

    // Add additional data keys to the object, so we can access them directly
    const additionalData = res?.additionalData ?? {};
    for (const key in additionalData) {
        if (additionalData.hasOwnProperty(key)) {
            const jsKey = stringToJsProperty(key);
            if (!res.hasOwnProperty(jsKey)) {
                res[jsKey] = additionalData[key];
            }
        }
    }

    return res as UserProfile;
}
