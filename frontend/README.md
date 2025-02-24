# TypeScript Auth Client

A TypeScript library for seamless integration with
the [HAWK Auth client](https://github.com/HAWK-Digital-Environments/hawk-auth-client).
This frontend library provides authentication, authorization, and user management capabilities for browser-based
applications.

## Installation

```bash
npm install @hawk-hhg/auth-client
```

## Basic Setup

```typescript
import {HawkAuthClient} from '@hawk-hhg/auth-client';

const client = new HawkAuthClient({
    // The endpoint URL of your auth API
    // If omitted, the client will use the current page URL
    endpointUrl: 'https://your-app.com/auth-api'
});
```

## Core Features

### Authentication

The authentication system provides a comprehensive solution for managing user sessions in your frontend application. It
handles the complete OAuth flow, token management, and authenticated API requests.

The system automatically manages token refresh cycles and provides a clean interface for making authenticated HTTP
requests. The `auth.fetch()` method works just like the native `fetch` API but automatically includes authentication
headers and handles token refresh when needed.

```typescript
// Initialize authentication
const auth = await client.getAuth();

// Check authentication status
if (await auth.isAuthenticated()) {
    // User is logged in
}

// Login
await client.login();

// Logout
await client.logout();

// Get current token
const token = auth.getToken();

// Refresh token
await auth.refreshToken();

// Make authenticated requests
const response = await auth.fetch('/api/data');
```

### Event System

The event system enables reactive programming patterns by allowing your application to respond to authentication state
changes. This is particularly useful for updating UI components when the user logs in or out, or when implementing
single-page applications that need to maintain authentication state.

The system provides events for login and logout operations, making it easy to integrate with any frontend framework or
vanilla JavaScript application.

```typescript
client.addEventListener('login', () => {
    console.log('User logged in');
    // Update UI components
    // Fetch user-specific data
    // Initialize protected features
});

client.addEventListener('logout', () => {
    console.log('User logged out');
    // Clear sensitive data
    // Reset UI state
    // Redirect to public area
});
```

### Guard System

The Guard system provides a sophisticated permission checking mechanism that supports role-based access control (RBAC),
group hierarchies, and resource-based permissions. This enables fine-grained access control in your frontend
application.

You can verify if a user:

- Has specific roles (e.g., 'admin', 'editor')
- Belongs to certain groups or their subgroups
- Has permission to perform specific actions on resources

The Guard system is particularly useful for:

- Conditionally rendering UI elements based on permissions
- Protecting route access
- Managing feature availability
- Controlling resource-specific actions

```typescript
const guard = auth.getGuard();

// Role-based checks
await guard.hasAnyRole('admin', 'editor');

// Group checks - supports hierarchical group structures
await guard.hasAnyGroup('organization.managers');
await guard.hasAnyOrHasChildOfAny('organization.managers');

// Resource scope validation - check permissions for specific resources
await guard.hasAnyResourceScope('document-123', 'read', 'write');
```

### User & Profile Management

The user management system provides access to both basic user information and detailed profile data. This separation
allows for efficient data loading and clear separation of concerns between identity information and profile details.

The system supports:

- Basic user information (ID, username, etc.)
- Extended profile data
- Cached profile access
- Automatic profile updates

```typescript
// Get user information
const user = await auth.getUser();

// Get user profile
const profile = await auth.getProfile();
```

## Complete Example

Here's a comprehensive example showing the main features:

```typescript
import {HawkAuthClient} from '@hawk-hhg/auth-client';

async function initializeAuth() {
    const client = new HawkAuthClient({
        endpointUrl: '/auth-api'
    });

    // Set up event listeners
    client.addEventListener('login', () => {
        console.log('User logged in');
    });
    client.addEventListener('logout', () => {
        console.log('User logged out');
    });

    // Initialize authentication
    const auth = await client.getAuth();

    if (!(await auth.isAuthenticated())) {
        await client.login();
        return;
    }

    // Get user information
    const user = await auth.getUser();
    console.log('Logged in user:', user);

    // Check permissions
    const guard = auth.getGuard();
    const canRead = await guard.hasAnyResourceScope('documents', 'read');

    if (canRead) {
        // Make authenticated request
        const response = await auth.fetch('/api/documents');
        const documents = await response.json();
        console.log('Documents:', documents);
    }
}
```

## Postcardware

You're free to use this package, but if it makes it to your production environment we highly appreciate you sending us a
postcard from your hometown, mentioning which of our package(s) you are using.

```
HAWK Fakultät Gestaltung
Interaction Design Lab
Renatastraße 11
31134 Hildesheim
```

Thank you :D
