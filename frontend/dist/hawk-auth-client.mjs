const b = [
  // Emitted every time the authentication state is updated, this includes, login, logout and token-refresh
  "auth-state-changed",
  // Emitted when a user logged in. This event will be executed AFTER all redirects are done.
  "login",
  // Emitted when a user has logged out. This event will be executed AFTER all redirects are done.
  "logout",
  // Emitted every time access token gets refreshed, both automatic and manual.
  "token-refresh",
  // Emitted every time an error occurs. If "preventDefault" is triggered, the error handler will not detect this error!
  "error"
], F = [
  "token",
  "refresh-token",
  "token-expires",
  "id-token",
  "code-verifier",
  "state",
  "redirect-url-after-login",
  "callback-url",
  "trigger-event-after-redirect"
];
function P(t) {
  const e = new FormData();
  for (const r in t)
    t.hasOwnProperty(r) && e.append(r, t[r]);
  return e;
}
function k(t, e, r) {
  const n = t.endpointUrl, a = n.searchParams;
  if (r)
    for (const o in r)
      a.set(o, r[o]);
  return a.set("hawk-api-route", e), a.set("state", t.storage.get("state")), n;
}
function m(t, e, r) {
  return t.status !== 200 && e.dispatchError("fetch-backend-data-failed", `Failed to fetch data from backend: [${t.status}], endpoint: ${r}`), t;
}
async function u(t, e, r) {
  return m(
    await fetch(k(t, e), {
      method: "POST",
      body: P(r)
    }),
    t,
    e
  );
}
async function d(t, e, r) {
  return m(
    await E(t, k(t, e, r), {
      method: "GET",
      // Enable caching, so we do not overwhelm the backend with requests
      cache: "default"
    }),
    t,
    e
  );
}
async function c(t, e = null) {
  return t.status !== 200 ? e : t.headers.get("content-type") !== "application/json" ? await t.text() : await t.json();
}
function g(t, e, r) {
  return t.status === 404 && e.dispatchError("missing-optional-feature", `The feature "${r}" is not enabled in your api.`), t;
}
function A(t, e) {
  const r = e.errorHandler ?? ((s, i) => console.error("Auth Client error:", s, i)), n = (s, i) => {
    new CustomEvent("error", {
      detail: {
        error: s,
        message: i
      },
      cancelable: !0
    }).defaultPrevented || r(s, i);
  }, a = L();
  return {
    get currentUrl() {
      return new URL(e.currentUrl || window.location.href);
    },
    get endpointUrl() {
      return new URL(e.endpointUrl || this.currentUrl.href);
    },
    dispatchEvent: (s, i) => {
      t.dispatchEvent(
        i instanceof Event ? i : new CustomEvent(s, {
          detail: i,
          cancelable: !0
        })
      );
    },
    dispatchError: n,
    storage: a
  };
}
function L(t) {
  t = t || "hawk_auth_client_";
  const e = (r) => t + r;
  return {
    clear: () => {
      F.map((r) => sessionStorage.removeItem(e(r)));
    },
    remove: (r) => {
      sessionStorage.removeItem(e(r));
    },
    set: (r, n) => {
      sessionStorage.setItem(e(r), n);
    },
    get: (r) => sessionStorage.getItem(e(r))
  };
}
async function v(t, e) {
  const r = await c(e);
  return !r || !r.token || !r.expires || !r.idToken ? (t.dispatchError("invalid-token-response", "Failed to refresh token"), U(t), !1) : (t.storage.set("token", r.token), t.storage.set("token-expires", r.expires), t.storage.set("refresh-token", r.refreshToken), t.storage.set("id-token", r.idToken), t.dispatchEvent("auth-state-changed"), !0);
}
function p(t) {
  const e = () => {
    const o = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    return o.charAt(Math.floor(Math.random() * o.length));
  }, r = () => {
    const o = new Uint32Array(1);
    return window.crypto.getRandomValues(o), o[0];
  }, n = () => r() % 2 === 0 ? "a" : "b";
  let a = "";
  for (; a.length < t; )
    a += n() === "a" ? e() : r();
  return a.substring(0, t);
}
async function R(t) {
  const r = new TextEncoder().encode(t);
  return await window.crypto.subtle.digest("SHA-256", r);
}
function T(t) {
  return btoa(String.fromCharCode.apply(null, new Uint8Array(t))).replace(/\+/g, "-").replace(/\//g, "_").replace(/=+$/, "");
}
async function O(t) {
  const e = await R(t);
  return T(e);
}
async function w(t) {
  const e = await c(
    g(
      await d(t, "user-info", {}),
      t,
      "user-info"
    )
  );
  if (!e || typeof e != "object")
    return null;
  const r = (e == null ? void 0 : e.claims) ?? {};
  for (const n in r)
    if (r.hasOwnProperty(n)) {
      const a = f(n);
      e.hasOwnProperty(a) || (e[a] = r[n]);
    }
  return Object.defineProperty(e, "profile", {
    get: async function() {
      return await S(t);
    }
  }), e;
}
function f(t) {
  return t.replace(/[-_](.)/g, (e, r) => r.toUpperCase());
}
async function S(t) {
  var a;
  const e = await c(
    g(
      await d(t, "user-profile", {}),
      t,
      "user-profile"
    )
  );
  if (!e || typeof e != "object")
    return null;
  const r = ((a = e == null ? void 0 : e.structure) == null ? void 0 : a.attributesLocal) ?? {};
  for (const o in r)
    if (r.hasOwnProperty(o)) {
      const s = f(o);
      e.hasOwnProperty(s) || (e[s] = e.attributes[r[o]] ?? void 0);
    }
  const n = (e == null ? void 0 : e.additionalData) ?? {};
  for (const o in n)
    if (n.hasOwnProperty(o)) {
      const s = f(o);
      e.hasOwnProperty(s) || (e[s] = n[o]);
    }
  return e;
}
async function C(t, e) {
  const r = await c(
    g(
      await d(t, "permissions", { resource: e }),
      t,
      "permissions"
    )
  );
  return !r || !Array.isArray(r == null ? void 0 : r.scopes) ? [] : r.scopes;
}
function l(t, ...e) {
  for (const r of e)
    if (t.includes(r))
      return !0;
  return !1;
}
function j(t, e) {
  return t.indexOf(e) === 0;
}
function D(t, ...e) {
  const r = (n, a) => {
    const o = n.replace(/^\//, ""), s = a.replace(/^\//, "");
    if (o === s || j(o, s))
      return !0;
  };
  for (const n of e)
    for (const a of t)
      if (r(a, n))
        return !0;
  return !1;
}
class H {
  constructor(e, r) {
    this.context = e, this.userFactory = r;
  }
  /**
   * Checks if the user has any one of the given roles.
   * @param roles
   */
  async hasAnyRole(...e) {
    const r = await this.userFactory();
    return r === null ? !1 : l(r.roles, ...e);
  }
  /**
   * Checks if the user has any one of the given groups.
   * This method checks for exact matches only, the hierarchy is not considered.
   * @param groups
   */
  async hasAnyGroup(...e) {
    const r = await this.userFactory();
    return r === null ? !1 : l(r.groups, ...e);
  }
  /**
   * Checks if the list contains any of the given groups or any child of the given groups.
   * @param groups
   */
  async hasAnyOrHasChildOfAny(...e) {
    const r = await this.userFactory();
    return r === null ? !1 : D(r.groups, ...e);
  }
  /**
   * Checks if the user has any of the given scopes on the given resource.
   * Each resource can have fine-grained permissions, for any kind of action you can imagine.
   * Common scopes are "read", "write", "delete", "admin" etc.
   * If the user has no permissions on the given resource, false is returned.
   * @param resource The resource to check the permissions for. Either the name or uuid of the resource.
   * @param scopes A list of scopes to check for. If this list is empty, this method will return true as long as the user has any scope on the resource.
   */
  async hasAnyResourceScope(e, ...r) {
    const n = await C(this.context, e);
    return r.length === 0 ? n.length > 0 : l(
      n,
      ...r
    );
  }
}
function y(t) {
  const e = t.get("token-expires");
  return e === null ? !0 : Date.now() > parseInt(e, 10) * 1e4;
}
async function N(t, e, r, n) {
  const a = await c(
    await u(t, "auth-login-url", {
      redirectUrl: e + "",
      codeChallenge: r,
      state: n
    })
  );
  if (!a || typeof a != "object" || !a.url || a.url.length === 0)
    throw t.dispatchError("login-failed-to-fetch-url", "Failed to fetch login URL"), new Error("Failed to fetch login URL");
  return a.url;
}
async function B(t, e, r) {
  const n = await c(
    await u(t, "auth-logout-url", {
      redirectUrl: r + "",
      idToken: e
    })
  );
  if (!n || typeof n != "object" || !n.url || n.url.length === 0)
    throw t.dispatchError("logout-failed-to-fetch-url", "Failed to fetch logout URL"), new Error("Failed to fetch logout URL");
  return n.url;
}
function I(t) {
  const e = t.currentUrl, r = e.searchParams;
  return r.delete("code"), r.delete("state"), r.delete("session_state"), r.delete("iss"), r.set("frontend-login", "true"), e;
}
async function _(t, e) {
  const r = t.storage;
  r.clear();
  try {
    const n = p(128);
    r.set("code-verifier", n), r.set("redirect-url-after-login", (e || t.currentUrl) + "");
    const a = I(t);
    r.set("callback-url", a + "");
    const o = await O(n), s = p(64);
    r.set("state", s), window.location.href = await N(t, a, o, s);
  } catch (n) {
    throw t.dispatchError("login-failed", "Failed to start login flow: " + n.message), new Error("Failed to fetch login URL: " + n);
  }
}
async function G(t) {
  const e = t.storage, r = e.get("state");
  if (r === null)
    return;
  const n = t.currentUrl.searchParams;
  if (!n.has("frontend-login"))
    return;
  if (n.get("state") !== r) {
    t.dispatchError("login-state-mismatch", "State mismatch, either not our request or CSRF attack; ignoring"), e.clear();
    return;
  }
  const a = n.get("code");
  if (a === null) {
    e.clear(), t.dispatchError("login-callback-missing-code", "No code in URL");
    return;
  }
  const o = e.get("code-verifier");
  if (o === null) {
    e.clear(), t.dispatchError("login-callback-misses-verifier", "No code verifier in storage");
    return;
  }
  if (!await v(
    t,
    await u(t, "auth-exchange-code-for-token", {
      code: a,
      codeVerifier: o,
      redirectUrl: e.get("callback-url") + ""
    })
  )) {
    t.dispatchError("login-failed-to-fetch-token", "Failed to fetch token");
    return;
  }
  const s = e.get("redirect-url-after-login");
  e.remove("code-verifier"), e.remove("callback-url"), e.remove("redirect-url-after-login"), e.set("trigger-event-after-redirect", "login"), window.location.href = s;
}
async function $(t, e) {
  const r = new URL((e || t.currentUrl) + ""), n = (a) => {
    t.dispatchError("logout-failed", a), window.location.href = r.href;
  };
  try {
    const a = t.storage.get("id-token");
    if (a === null) {
      n("No id token found");
      return;
    }
    const o = await B(t, a, r);
    U(t), window.location.href = o;
  } catch (a) {
    n("An error occurred: " + a.message);
  }
}
async function h(t) {
  const e = t.storage.get("refresh-token"), r = t.storage.get("state");
  return e === null ? (t.dispatchError("failed-to-refresh-token", "No refresh token found"), t.storage.clear(), !1) : await v(
    t,
    await u(t, "auth-refresh-token", {
      refreshToken: e + ""
    })
  ) ? (t.storage.set("state", r), t.dispatchEvent("token-refresh"), !0) : (t.dispatchError("failed-to-refresh-token", "Failed to refresh token"), !1);
}
async function E(t, e, r) {
  const n = () => {
    const o = r || {};
    o.headers = o.headers || {};
    const s = t.storage.get("token");
    return s !== null && (o.headers.Authorization = "Bearer " + s), o;
  }, a = await fetch(e, n());
  if (a.status === 401) {
    if (!await h(t))
      throw t.dispatchError("authenticated-fetch-failed", "Failed to refresh token"), new Error("Failed to refresh token, cannot authenticate request");
    return await fetch(e, n());
  }
  return a;
}
function q(t) {
  const e = t.storage, r = e.get("trigger-event-after-redirect");
  b.indexOf(`${r}`) !== -1 && t.dispatchEvent(r), e.remove("trigger-event-after-redirect");
}
async function z(t) {
  return q(t), await G(t), new K(t);
}
function U(t) {
  t.dispatchEvent("auth-state-changed"), t.storage.clear(), t.storage.set("trigger-event-after-redirect", "logout");
}
class K {
  constructor(e) {
    this.authenticatedPromise = null, this.context = e;
  }
  /**
   * Returns the current token if it exists. Otherwise, returns null.
   */
  getToken() {
    return this.context.storage.get("token");
  }
  /**
   * Returns true if the user is authenticated.
   */
  isAuthenticated() {
    return this.authenticatedPromise === null && (this.authenticatedPromise = new Promise(async (e) => {
      const r = this.context.storage;
      if (r.get("token") === null) {
        e(!1);
        return;
      }
      if (y(r)) {
        if (await h(this.context)) {
          e(!y(r));
          return;
        }
        e(!1);
        return;
      }
      e(!0);
    }).then((e) => (this.authenticatedPromise = null, e))), this.authenticatedPromise;
  }
  /**
   * A wrapper around fetch that automatically adds the authentication token to the request.
   * If the 401 status is returned, the token is refreshed and the request is retried.
   */
  async fetch(e, r) {
    return E(this.context, e, r);
  }
  /**
   * Manually triggers a token refresh.
   * Returns true if the token was successfully refreshed.
   * Returns false if the token could not be refreshed.
   */
  refreshToken() {
    return h(this.context);
  }
  /**
   * Returns the current user, if the user is authenticated.
   */
  async getUser() {
    return w(this.context);
  }
  /**
   * Returns the profile information for the current user, if the user is authenticated.
   *
   * IMPORTANT: While the profile gives you access to ALL user data, it is a performance hit to load it every time.
   * If you know which attributes you need, you should create a custom "claim" for them that will be added to the "user-info" endpoint.
   * Those claims will be automatically available in the user object.
   */
  async getProfile() {
    return w(this.context);
  }
  /**
   * Returns the guard for the current user, if present.
   * A guard is a set of permissions that the user has, describing what the user is allowed to do and with which resources.
   * You can also check if the user is part of a specific group or has a specific role.
   */
  getGuard() {
    return new H(this.context, () => this.getUser());
  }
}
class V extends EventTarget {
  constructor(e) {
    super(), this.authentication = null, this.context = A(this, e);
  }
  /**
   * @inheritDoc
   */
  addEventListener(e, r, n) {
    super.addEventListener(e, r, n);
  }
  /**
   * @inheritDoc
   */
  removeEventListener(e, r, n) {
    super.removeEventListener(e, r, n);
  }
  /**
   * Starts the login process.
   * This is normally done when the user clicks a login button.
   */
  login(e) {
    return _(this.context, e);
  }
  /**
   * Starts the logout process.
   * This is normally done when the user clicks a logout button.
   * If the user is not logged in, this function will do nothing.
   * @param redirectAfterLogout
   */
  logout(e) {
    return $(this.context, e);
  }
  /**
   * Returns the current authentication state.
   * It allows access to the current user's token, data, and more.
   */
  async getAuth() {
    return this.authentication === null && (this.authentication = z(this.context)), await this.authentication;
  }
}
export {
  V as default
};
