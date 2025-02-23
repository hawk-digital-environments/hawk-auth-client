export function generateRandomString(length) {
    const randomCharFromList = () => {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        return chars.charAt(Math.floor(Math.random() * chars.length));
    };
    const cryptoSecureRandomNumber = () => {
        const array = new Uint32Array(1);
        window.crypto.getRandomValues(array);
        return array[0];
    };
    const aOrB = () => cryptoSecureRandomNumber() % 2 === 0 ? 'a' : 'b';

    let text = '';
    while (text.length < length) {
        text += aOrB() === 'a' ? randomCharFromList() : cryptoSecureRandomNumber();
    }

    return text.substring(0, length);
}

async function sha256(plain) {
    const encoder = new TextEncoder();
    const data = encoder.encode(plain);
    return await window.crypto.subtle.digest('SHA-256', data);
}

function base64urlencode(a) {
    return btoa(String.fromCharCode.apply(null, new Uint8Array(a)))
        .replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

export async function generateCodeChallenge(codeVerifier) {
    const hashed = await sha256(codeVerifier);
    return base64urlencode(hashed);
}
