# Authentication Module Documentation

## Overview

The Authentication Module handles user login, logout, and JWT token management for the Accountant Management System.

## Module Location

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── Public/
│   │           └── Auth/
│   │               └── AuthController.php
│   ├── Requests/
│   │   └── Auth/
│   │       ├── LoginRequest.php
│   │       └── RegisterRequest.php
│   └── Middleware/
│       └── Authenticate.php
├── Services/
│   └── Auth/
│       └── AuthService.php
└── Models/
    └── User.php
```

## Components

### 1. AuthController

**File**: `app/Http/Controllers/Api/Public/Auth/AuthController.php`

**Purpose**: Handle HTTP requests for authentication

**Methods**:

#### login()
```php
public function login(LoginRequest $request)
```
- **Purpose**: Authenticate user and return JWT token
- **Request**: LoginRequest (validated email and password)
- **Process**:
  1. Validate credentials via LoginRequest
  2. Call AuthService->login()
  3. Return JWT token and user data
- **Response**: 
  ```json
  {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "profile": {
      "id": 1,
      "name": "User Name",
      "email": "user@example.com",
      "status": 1,
      "createdAt": "2024-01-01T00:00:00.000000Z"
    },
    "role": {
      "id": 1,
      "name": "Admin",
      "guardName": "api"
    },
    "permissions": [
      "all_tasks",
      "create_task",
      "edit_task",
      "update_task",
      "delete_task"
    ]
  }
  ```
  **Response Headers**:
  ```
  Authorization: {token}
  ```
- **Error Responses**:
  - 401: Invalid credentials
  - 422: Validation error

#### logout()
```php
public function logout()
```
- **Purpose**: Invalidate current JWT token
- **Process**:
  1. Get authenticated user from token
  2. Invalidate token
  3. Return success message
- **Response**:
  ```json
  {
    "message": "Successfully logged out"
  }
  ```


### 2. AuthService

**File**: `app/Services/Auth/AuthService.php`

**Purpose**: Business logic for authentication

**Methods**:

#### login($credentials)
```php
public function login(array $credentials)
```
- **Purpose**: Verify credentials and generate JWT token with user details
- **Parameters**:
  - `$credentials`: Array with 'email' and 'password'
- **Process**:
  1. Attempt authentication with credentials
  2. Check user status (must be active)
  3. Generate JWT token
  4. Load user role and permissions
  5. Return token, profile, role, and permissions
- **Returns**: Response with:
  - `token`: JWT authentication token
  - `profile`: User profile data (UserResource)
  - `role`: User role data (RoleResource)
  - `permissions`: Array of permission names
- **Response Headers**: Includes `Authorization` header with token
- **Throws**: UnauthorizedException if credentials invalid

**Actual Implementation**:
```php
return response()->json([
    'token' => $userToken,
    'profile' => new UserResource($user),
    'role' => new RoleResource($role),
    'permissions' => $this->userPermissionService->getUserPermissions($user),
], 200)->header('Authorization', $userToken);
```

### 3. LoginRequest

**File**: `app/Http/Requests/Auth/LoginRequest.php`

**Purpose**: Validate login input

**Validation Rules**:
```php
[
    'email' => 'required|email',
    'password' => 'required|string|min:6'
]
```

**Custom Messages**:
- Email required
- Valid email format required
- Password required
- Password minimum 6 characters

### 4. Authenticate Middleware

**File**: `app/Http/Middleware/Authenticate.php`

**Purpose**: Verify JWT token on protected routes

**Process**:
1. Extract token from Authorization header
2. Verify token signature
3. Check token expiration
4. Load user from database
5. Attach user to request
6. Allow request to proceed

**Token Format**: `Authorization: Bearer {token}`

## Authentication Flow

```
┌─────────────────────────────────────────────────────────────┐
│                    Authentication Flow                       │
└─────────────────────────────────────────────────────────────┘

1. User Login Request
   POST /api/v1/auth/login
   Body: { email, password }
   │
   ▼
2. LoginRequest Validation
   - Validate email format
   - Validate password length
   │
   ▼
3. AuthController->login()
   - Receive validated request
   │
   ▼
4. AuthService->login()
   - Verify credentials against database
   - Check user status (active)
   │
   ▼
5. Generate JWT Token
   - Create token with user ID and email
   - Set expiration (JWT_TTL from config)
   │
   ▼
6. Load User Role and Permissions
   - Get user's role via Spatie Permission
   - Get all permissions for the user
   │
   ▼
7. Return Response
   - Token (in body and Authorization header)
   - User profile (UserResource)
   - User role (RoleResource)
   - User permissions (array)
   │
   ▼
8. Client Stores Data
   - Save token in localStorage/sessionStorage
   - Save user profile
   - Save role and permissions for UI control
   │
   ▼
9. Subsequent Requests
   - Include token in Authorization header
   │
   ▼
10. Authenticate Middleware
    - Verify token on each request
    - Load user
    - Attach to request
    │
    ▼
11. Request Proceeds
     - User available via auth()->user()
     - Permissions checked via middleware
```


## Configuration

### JWT Configuration

**File**: `config/jwt.php`

**Key Settings**:
```php
'secret' => env('JWT_SECRET'),
'ttl' => env('JWT_TTL', 60), // Token lifetime in minutes
'refresh_ttl' => env('JWT_REFRESH_TTL', 20160), // Refresh token lifetime
'algo' => 'HS256', // Hashing algorithm
```

### Environment Variables

**File**: `.env`

```env
JWT_SECRET=your-secret-key
JWT_TTL=60
JWT_REFRESH_TTL=20160
```

## API Endpoints

### Login

**Endpoint**: `POST /api/v1/auth/login`

**Authentication**: Not required

**Request Body**:
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Success Response** (200):
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "profile": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "status": 1,
    "createdAt": "2024-01-01T00:00:00.000000Z",
    "updatedAt": "2024-01-01T00:00:00.000000Z"
  },
  "role": {
    "id": 1,
    "name": "Admin",
    "guardName": "api",
    "createdAt": "2024-01-01T00:00:00.000000Z"
  },
  "permissions": [
    "all_tasks",
    "create_task",
    "edit_task",
    "update_task",
    "delete_task",
    "all_clients",
    "create_client",
    "edit_client",
    "update_client",
    "delete_client"
  ]
}
```

**Response Headers**:
```
Authorization: eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
Content-Type: application/json
```

**Error Responses**:

401 Unauthorized:
```json
{
  "message": "Invalid credentials"
}
```

422 Validation Error:
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 6 characters."]
  }
}
```

### Logout

**Endpoint**: `POST /api/v1/auth/logout`

**Authentication**: Required (JWT token)

**Headers**:
```
Authorization: Bearer {token}
```

**Success Response** (200):
```json
{
  "message": "Successfully logged out"
}
```

**Error Response** (401):
```json
{
  "message": "Unauthenticated."
}
```

## Usage Examples

### Login Example (JavaScript)

```javascript
// Login request
const login = async (email, password) => {
  try {
    const response = await fetch('http://api.example.com/api/v1/auth/login', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ email, password })
    });
    
    const data = await response.json();
    
    if (response.ok) {
      // Store token from response body
      localStorage.setItem('token', data.token);
      localStorage.setItem('user', JSON.stringify(data.profile));
      localStorage.setItem('role', JSON.stringify(data.role));
      localStorage.setItem('permissions', JSON.stringify(data.permissions));
      
      // Token is also available in Authorization header
      const authHeader = response.headers.get('Authorization');
      console.log('Token from header:', authHeader);
      
      return data;
    } else {
      throw new Error(data.message);
    }
  } catch (error) {
    console.error('Login failed:', error);
    throw error;
  }
};

// Usage
login('user@example.com', 'password123')
  .then(data => {
    console.log('Logged in:', data.profile);
    console.log('Role:', data.role.name);
    console.log('Permissions:', data.permissions);
  })
  .catch(error => console.error('Error:', error));
```

### Authenticated Request Example

```javascript
// Make authenticated request
const getClients = async () => {
  const token = localStorage.getItem('token');
  
  const response = await fetch('http://api.example.com/api/v1/clients', {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });
  
  return await response.json();
};
```

### Logout Example

```javascript
// Logout request
const logout = async () => {
  const token = localStorage.getItem('token');
  
  try {
    const response = await fetch('http://api.example.com/api/v1/auth/logout', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    });
    
    if (response.ok) {
      // Clear stored data
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      console.log('Logged out successfully');
    }
  } catch (error) {
    console.error('Logout failed:', error);
  }
};
```


## Security Considerations

### Token Security

1. **Token Storage**:
   - Store tokens securely (localStorage or httpOnly cookies)
   - Never expose tokens in URLs
   - Clear tokens on logout

2. **Token Transmission**:
   - Always use HTTPS in production
   - Include token in Authorization header only
   - Never send token in query parameters

3. **Token Expiration**:
   - Tokens expire after JWT_TTL minutes (default 60)
   - Implement token refresh mechanism
   - Handle expired token errors gracefully

### Password Security

1. **Password Hashing**:
   - Uses bcrypt algorithm
   - Automatic via Laravel's Hash facade
   - Never store plain text passwords

2. **Password Requirements**:
   - Minimum 6 characters (configurable)
   - Consider adding complexity requirements
   - Implement password reset functionality

### Brute Force Protection

1. **Rate Limiting**:
   - Implement rate limiting on login endpoint
   - Use Laravel's throttle middleware
   - Example: 5 attempts per minute

2. **Account Lockout**:
   - Consider implementing account lockout after failed attempts
   - Send notification on suspicious activity

## Troubleshooting

### Common Issues

#### Issue: "Token has expired"
**Cause**: JWT token exceeded TTL
**Solution**: 
- Implement token refresh mechanism
- Request new token via login
- Increase JWT_TTL if appropriate

#### Issue: "Token could not be parsed"
**Cause**: Invalid token format
**Solution**:
- Verify token is included correctly in header
- Check token format: `Bearer {token}`
- Ensure no extra spaces or characters

#### Issue: "User not found"
**Cause**: User deleted or deactivated
**Solution**:
- Check user status in database
- Verify user ID in token payload
- Re-authenticate user

#### Issue: "JWT secret not set"
**Cause**: JWT_SECRET missing from .env
**Solution**:
```bash
php artisan jwt:secret
```

## Testing

### Manual Testing

1. **Test Login**:
   ```bash
   curl -X POST http://localhost:8000/api/v1/auth/login \
     -H "Content-Type: application/json" \
     -d '{"email":"user@example.com","password":"password"}'
   ```

2. **Test Authenticated Request**:
   ```bash
   curl -X GET http://localhost:8000/api/v1/clients \
     -H "Authorization: Bearer {your-token}"
   ```

3. **Test Logout**:
   ```bash
   curl -X POST http://localhost:8000/api/v1/auth/logout \
     -H "Authorization: Bearer {your-token}"
   ```

### Unit Test Example

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['token', 'user']);
    }

    public function test_user_cannot_login_with_invalid_credentials()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(401);
    }

    public function test_user_can_logout()
    {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $response = $this->withHeader('Authorization', "Bearer $token")
                         ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200);
    }
}
```

## Related Documentation

- [User Management Module](MODULE_USER_MANAGEMENT.md)
- [Permission Module](MODULE_PERMISSIONS.md)
- [API Reference](../PROJECT_DOCUMENTATION_EN.md#6-api-endpoints-reference)

---

**Last Updated**: 2024
**Module Version**: 1.0
