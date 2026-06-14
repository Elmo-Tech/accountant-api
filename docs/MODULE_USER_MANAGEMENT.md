# User Management Module Documentation

## Overview

The User Management Module handles user accounts, authentication, roles, and permissions. It uses Laravel's built-in authentication system combined with Spatie Permission package for role-based access control (RBAC). Users can be assigned roles, and each role has specific permissions that control access to different parts of the application.

## Module Location

```
app/
├── Models/
│   └── User.php
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── Private/
│   │           └── User/
│   │               └── UserController.php
│   ├── Requests/
│   │   └── User/
│   │       ├── CreateUserRequest.php
│   │       └── UpdateUserRequest.php
│   └── Resources/
│       └── User/
│           ├── AllUserDataResource.php
│           ├── UserResource.php
│           └── AllUserCollection.php
├── Services/
│   └── User/
│       └── UserService.php
├── Enums/
│   └── User/
│       └── UserStatus.php
└── Filters/
    └── User/
        ├── FilterUser.php
        └── FilterUserRole.php
```

## Database Schema

### users Table

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary key |
| first_name | VARCHAR(255) | User's first name |
| last_name | VARCHAR(255) | User's last name |
| username | VARCHAR(255) | Unique username for login |
| email | VARCHAR(255) | Unique email address |
| phone | VARCHAR(255) | Phone number |
| address | TEXT | Physical address |
| password | VARCHAR(255) | Hashed password |
| status | TINYINT | User status (1=ACTIVE, 0=INACTIVE) |
| avatar | VARCHAR(255) | Avatar image path |
| per_hour_rate | DECIMAL(8,2) | Hourly rate for billing |
| email_verified_at | TIMESTAMP | Email verification timestamp |
| remember_token | VARCHAR(100) | Remember me token |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Update timestamp |

### Related Tables (Spatie Permission)

**roles Table**
- id, name, guard_name, created_at, updated_at

**permissions Table**
- id, name, guard_name, created_at, updated_at

**model_has_roles Table**
- role_id, model_type, model_id

**role_has_permissions Table**
- permission_id, role_id

**model_has_permissions Table**
- permission_id, model_type, model_id

## Enums

### UserStatus (app/Enums/User/UserStatus.php)

```php
enum UserStatus: int
{
    case ACTIVE = 1;    // User can log in and use the system
    case INACTIVE = 0;  // User is disabled
}
```


## Models

### User (app/Models/User.php)

Main user model with authentication and authorization.

#### Fillable Fields:

```php
'first_name', 'last_name', 'username', 'address', 'email', 'phone', 
'password', 'status', 'avatar', 'per_hour_rate'
```

#### Hidden Fields:

```php
'password', 'remember_token'
```

#### Casts:

```php
'email_verified_at' => 'datetime',
'status' => UserStatus::class
```

#### Traits:

- `HasApiTokens`: Laravel Sanctum API tokens
- `HasFactory`: Model factories
- `Notifiable`: Notifications
- `HasRoles`: Spatie Permission roles and permissions

#### Interfaces:

- `JWTSubject`: JWT authentication

#### Methods:

**setPasswordAttribute($value)**
- Automatically hashes password when setting
- Uses Laravel's Hash facade

```php
public function setPasswordAttribute($value)
{
    $this->attributes['password'] = Hash::make($value);
}
```

**getJWTIdentifier()**
- Returns user's primary key for JWT
- Required by JWTSubject interface

**getJWTCustomClaims()**
- Returns custom JWT claims
- Currently returns empty array

**getFullNameAttribute()**
- Computed attribute for full name
- Returns: "FirstName LastName"

```php
public function getFullNameAttribute()
{
    return $this->first_name . ' ' . $this->last_name;
}
```

## Controllers

### UserController (app/Http/Controllers/Api/Private/User/UserController.php)

Main controller for user CRUD operations.

#### Methods:

**index(Request $request)**
- Permission: `all_users`
- Returns: Paginated list of all users
- Supports filtering by:
  - `filter[search]`: Search in name, username, phone, address
  - `filter[status]`: Filter by status (0 or 1)
  - `filter[role]`: Filter by role ID
  - `pageSize`: Items per page (default: 10)
- Response: `AllUserCollection`

**create(CreateUserRequest $request)**
- Permission: `create_user`
- Creates new user with role assignment
- Uploads avatar if provided
- Hashes password automatically
- Returns: Success message

**edit(Request $request)**
- Permission: `edit_user`
- Parameters: `userId`
- Returns: AllUserDataResource with user details and roles

**update(UpdateUserRequest $request)**
- Permission: `update_user`
- Updates user information
- Updates avatar if new one provided (deletes old)
- Updates password only if provided
- Syncs role assignment
- Returns: Success message

**delete(Request $request)**
- Permission: `delete_user`
- Parameters: `userId`
- Deletes user avatar from storage
- Soft deletes user record
- Returns: Success message

**changeStatus(Request $request)**
- Permission: `change_user_status`
- Parameters: `userId`, `status`
- Changes user active/inactive status
- Returns: Success message

## Services

### UserService (app/Services/User/UserService.php)

Business logic for user operations.

#### Methods:

**allUsers()**
- Retrieves all users with filtering
- Supports search, status, and role filters
- Returns: Collection of User

**createUser(array $userData)**
- Creates new user
- Handles avatar upload
- Assigns role to user
- Parameters:
  - `firstName`: First name (required)
  - `lastName`: Last name (required)
  - `username`: Unique username (required)
  - `email`: Unique email (required)
  - `phone`: Phone number (optional)
  - `address`: Address (optional)
  - `password`: Password (required, min 8 chars with complexity)
  - `status`: User status (required, 0 or 1)
  - `roleId`: Role ID (required)
  - `avatar`: Avatar image file (optional)
  - `perHourRate`: Hourly rate (required)
- Returns: User model

**editUser(int $userId)**
- Retrieves user with roles
- Returns: User model with roles relationship

**updateUser(array $userData)**
- Updates user information
- Handles avatar upload and old avatar deletion
- Updates password only if provided
- Syncs role assignment
- Same parameters as create plus `userId`
- Returns: User model

**deleteUser(int $userId)**
- Deletes user avatar from storage
- Deletes user record

**changeUserStatus(int $userId, int $status)**
- Updates user status
- Returns: Number of affected rows

## Business Logic

### Password Management

Passwords are automatically hashed when set using the model mutator:

```php
// In User model
public function setPasswordAttribute($value)
{
    $this->attributes['password'] = Hash::make($value);
}

// Usage in service
$user->password = $userData['password']; // Automatically hashed
```

Password requirements:
- Minimum 8 characters
- Mixed case (uppercase and lowercase)
- Numbers
- Symbols
- Not compromised (checked against haveibeenpwned.com)

### Avatar Management

Avatars are stored in `storage/app/public/avatars/`:

```php
// Upload new avatar
if (isset($userData['avatar']) && $userData['avatar'] instanceof UploadedFile) {
    $avatarPath = $this->uploadService->uploadFile($userData['avatar'], 'avatars');
    $user->avatar = $avatarPath;
}

// Delete old avatar when updating
if ($avatarPath) {
    Storage::disk('public')->delete($user->avatar);
    $user->avatar = $avatarPath;
}

// Delete avatar when deleting user
if ($user->avatar) {
    Storage::disk('public')->delete($user->avatar);
}
```

### Role Assignment

Users are assigned roles using Spatie Permission:

```php
// Assign role on creation
$role = Role::find($userData['roleId']);
$user->assignRole($role->id);

// Sync role on update (removes old roles, assigns new)
$role = Role::find($userData['roleId']);
$user->syncRoles($role->id);
```

Each user can have one role, which determines their permissions.

### User Status

User status controls whether a user can access the system:

- **ACTIVE (1)**: User can log in and use all features
- **INACTIVE (0)**: User cannot log in, effectively disabled

```php
// Change status
User::where('id', $userId)->update(['status' => UserStatus::ACTIVE]);
```


## API Endpoints

### User Management

#### GET /api/v1/users
Get all users with filtering.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
```
filter[search]  - Search in name, username, phone, address
filter[status]  - Filter by status (0=inactive, 1=active)
filter[role]    - Filter by role ID
pageSize        - Items per page (default: 10)
```

**Response:**
```json
{
  "result": {
    "users": [
      {
        "userId": 1,
        "firstName": "John",
        "lastName": "Doe",
        "username": "johndoe",
        "phone": "+39 123 456 7890",
        "address": "Via Roma 123, Milano",
        "status": 1,
        "avatar": "http://example.com/storage/avatars/avatar.jpg",
        "perHourRate": 50.00,
        "email": "john.doe@example.com"
      }
    ]
  },
  "pagination": {
    "total": 25,
    "count": 10,
    "per_page": 10,
    "current_page": 1,
    "total_pages": 3
  }
}
```

#### POST /api/v1/users/create
Create new user.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Request Body (Form Data):**
```
firstName: John
lastName: Doe
username: johndoe
email: john.doe@example.com
phone: +39 123 456 7890
address: Via Roma 123, Milano
password: SecurePass123!
status: 1
roleId: 2
perHourRate: 50.00
avatar: [file]
```

**Response:**
```json
{
  "message": "Created successfully"
}
```

#### GET /api/v1/users/edit
Get user details for editing.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
```
userId - User ID (required)
```

**Response:**
```json
{
  "userId": 1,
  "firstName": "John",
  "lastName": "Doe",
  "name": "John Doe",
  "username": "johndoe",
  "phone": "+39 123 456 7890",
  "address": "Via Roma 123, Milano",
  "status": 1,
  "avatar": "http://example.com/storage/avatars/avatar.jpg",
  "roleId": 2,
  "perHourRate": 50.00,
  "email": "john.doe@example.com"
}
```

#### PUT /api/v1/users/update
Update user.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Request Body (Form Data):**
```
userId: 1
firstName: John
lastName: Doe
username: johndoe
email: john.doe@example.com
phone: +39 123 456 7890
address: Via Roma 123, Milano
password: NewSecurePass123! (optional)
status: 1
roleId: 2
perHourRate: 55.00
avatar: [file] (optional)
```

**Response:**
```json
{
  "message": "Updated successfully"
}
```

#### DELETE /api/v1/users/delete
Delete user.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "userId": 1
}
```

**Response:**
```json
{
  "message": "Deleted successfully"
}
```

#### PUT /api/v1/users/change-status
Change user status (activate/deactivate).

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "userId": 1,
  "status": 0
}
```

**Response:**
```json
{
  "message": "Updated successfully"
}
```

## Validation Rules

### CreateUserRequest

```php
'firstName' => 'required',
'lastName' => 'required',
'username' => 'required|unique:users,username',
'email' => 'required|unique:users,email',
'phone' => 'nullable',
'address' => 'nullable',
'status' => 'required|enum:UserStatus',
'password' => 'required|string|min:8|mixed_case|numbers|symbols|uncompromised',
'roleId' => 'required|numeric|exists:roles,id',
'avatar' => 'sometimes|nullable|image|mimes:jpeg,jpg,png,gif|max:2048',
'perHourRate' => 'required'
```

### UpdateUserRequest

```php
'userId' => 'required',
'firstName' => 'required',
'lastName' => 'required',
'username' => 'required|unique:users,username,{userId}',
'email' => 'required|unique:users,email,{userId}',
'phone' => 'nullable',
'address' => 'nullable',
'status' => 'required|enum:UserStatus',
'password' => 'sometimes|nullable|min:8|mixed_case|numbers|symbols|uncompromised',
'roleId' => 'required',
'avatar' => 'sometimes|nullable|image|mimes:jpeg,jpg,png,gif|max:2048',
'perHourRate' => 'required'
```

## Usage Examples

### JavaScript/Frontend Integration

#### Fetching All Users

```javascript
async function fetchUsers(filters = {}, pageSize = 10) {
  const params = new URLSearchParams();
  
  if (filters.search) params.append('filter[search]', filters.search);
  if (filters.status !== undefined) params.append('filter[status]', filters.status);
  if (filters.role) params.append('filter[role]', filters.role);
  params.append('pageSize', pageSize);
  
  const response = await fetch(`/api/v1/users?${params}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });
  
  const data = await response.json();
  return data;
}

// Usage
const users = await fetchUsers({ status: 1, role: 2 }, 20);
console.log(`Found ${users.data.length} active users`);
```

#### Creating a User

```javascript
async function createUser(userData, avatarFile) {
  const formData = new FormData();
  
  formData.append('firstName', userData.firstName);
  formData.append('lastName', userData.lastName);
  formData.append('username', userData.username);
  formData.append('email', userData.email);
  formData.append('phone', userData.phone || '');
  formData.append('address', userData.address || '');
  formData.append('password', userData.password);
  formData.append('status', userData.status);
  formData.append('roleId', userData.roleId);
  formData.append('perHourRate', userData.perHourRate);
  
  if (avatarFile) {
    formData.append('avatar', avatarFile);
  }
  
  const response = await fetch('/api/v1/users/create', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`
    },
    body: formData
  });
  
  const result = await response.json();
  return result;
}

// Usage
const avatarFile = document.getElementById('avatar-input').files[0];
await createUser({
  firstName: 'John',
  lastName: 'Doe',
  username: 'johndoe',
  email: 'john.doe@example.com',
  phone: '+39 123 456 7890',
  address: 'Via Roma 123, Milano',
  password: 'SecurePass123!',
  status: 1,
  roleId: 2,
  perHourRate: 50.00
}, avatarFile);
```

#### Updating a User

```javascript
async function updateUser(userData, avatarFile = null) {
  const formData = new FormData();
  
  formData.append('userId', userData.userId);
  formData.append('firstName', userData.firstName);
  formData.append('lastName', userData.lastName);
  formData.append('username', userData.username);
  formData.append('email', userData.email);
  formData.append('phone', userData.phone || '');
  formData.append('address', userData.address || '');
  formData.append('status', userData.status);
  formData.append('roleId', userData.roleId);
  formData.append('perHourRate', userData.perHourRate);
  
  if (userData.password) {
    formData.append('password', userData.password);
  }
  
  if (avatarFile) {
    formData.append('avatar', avatarFile);
  }
  
  const response = await fetch('/api/v1/users/update', {
    method: 'PUT',
    headers: {
      'Authorization': `Bearer ${token}`
    },
    body: formData
  });
  
  const result = await response.json();
  return result;
}

// Usage
await updateUser({
  userId: 1,
  firstName: 'John',
  lastName: 'Doe',
  username: 'johndoe',
  email: 'john.doe@example.com',
  phone: '+39 123 456 7890',
  address: 'Via Roma 123, Milano',
  status: 1,
  roleId: 2,
  perHourRate: 55.00
});
```

#### Changing User Status

```javascript
async function changeUserStatus(userId, status) {
  const response = await fetch('/api/v1/users/change-status', {
    method: 'PUT',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      userId: userId,
      status: status
    })
  });
  
  const result = await response.json();
  return result;
}

// Usage - Deactivate user
await changeUserStatus(1, 0);

// Usage - Activate user
await changeUserStatus(1, 1);
```


#### Deleting a User

```javascript
async function deleteUser(userId) {
  const response = await fetch('/api/v1/users/delete', {
    method: 'DELETE',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      userId: userId
    })
  });
  
  const result = await response.json();
  return result;
}

// Usage
await deleteUser(1);
console.log('User deleted successfully');
```

#### User Management Component

```javascript
class UserManager {
  constructor() {
    this.users = [];
    this.currentPage = 1;
    this.pageSize = 10;
  }
  
  async loadUsers(filters = {}) {
    const response = await fetchUsers(filters, this.pageSize);
    this.users = response.result.users;
    this.pagination = response.pagination;
    return this.users;
  }
  
  async createUser(userData, avatarFile) {
    await createUser(userData, avatarFile);
    await this.loadUsers(); // Reload list
  }
  
  async updateUser(userData, avatarFile) {
    await updateUser(userData, avatarFile);
    await this.loadUsers(); // Reload list
  }
  
  async toggleUserStatus(userId, currentStatus) {
    const newStatus = currentStatus === 1 ? 0 : 1;
    await changeUserStatus(userId, newStatus);
    await this.loadUsers(); // Reload list
  }
  
  async deleteUser(userId) {
    if (confirm('Are you sure you want to delete this user?')) {
      await deleteUser(userId);
      await this.loadUsers(); // Reload list
    }
  }
  
  searchUsers(searchTerm) {
    return this.loadUsers({ search: searchTerm });
  }
  
  filterByRole(roleId) {
    return this.loadUsers({ role: roleId });
  }
  
  filterByStatus(status) {
    return this.loadUsers({ status: status });
  }
}

// Usage
const userManager = new UserManager();
await userManager.loadUsers();

// Search
await userManager.searchUsers('john');

// Filter by role
await userManager.filterByRole(2);

// Toggle status
await userManager.toggleUserStatus(1, 1);
```

## Permissions

The following permissions control access to user management features:

| Permission | Description |
|-----------|-------------|
| all_users | View all users list |
| create_user | Create new users |
| edit_user | View user details for editing |
| update_user | Update user information |
| delete_user | Delete users |
| change_user_status | Activate/deactivate users |

## Testing

### Manual Testing Checklist

#### User CRUD Operations

- [ ] Create user with all fields
- [ ] Create user with minimal fields
- [ ] Verify unique username validation
- [ ] Verify unique email validation
- [ ] Verify password complexity requirements
- [ ] Upload avatar during creation
- [ ] View users list
- [ ] Search users by name/username
- [ ] Filter users by status
- [ ] Filter users by role
- [ ] Edit user details
- [ ] Update user without changing password
- [ ] Update user with new password
- [ ] Update user avatar
- [ ] Delete user
- [ ] Verify avatar is deleted from storage

#### User Status Management

- [ ] Activate user
- [ ] Deactivate user
- [ ] Verify inactive user cannot log in
- [ ] Verify active user can log in

#### Role Assignment

- [ ] Assign role during user creation
- [ ] Change user role
- [ ] Verify role permissions are applied
- [ ] Test with different roles

### API Testing with cURL

#### Create User

```bash
curl -X POST https://accountant-api.testingelmo.com/api/v1/users/create \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "firstName=John" \
  -F "lastName=Doe" \
  -F "username=johndoe" \
  -F "email=john.doe@example.com" \
  -F "phone=+39 123 456 7890" \
  -F "address=Via Roma 123, Milano" \
  -F "password=SecurePass123!" \
  -F "status=1" \
  -F "roleId=2" \
  -F "perHourRate=50.00" \
  -F "avatar=@/path/to/avatar.jpg"
```

#### Get All Users

```bash
curl -X GET "https://accountant-api.testingelmo.com/api/v1/users?filter[status]=1&filter[role]=2&pageSize=20" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Update User

```bash
curl -X PUT https://accountant-api.testingelmo.com/api/v1/users/update \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "userId=1" \
  -F "firstName=John" \
  -F "lastName=Doe" \
  -F "username=johndoe" \
  -F "email=john.doe@example.com" \
  -F "phone=+39 123 456 7890" \
  -F "address=Via Roma 123, Milano" \
  -F "status=1" \
  -F "roleId=2" \
  -F "perHourRate=55.00"
```

#### Change User Status

```bash
curl -X PUT https://accountant-api.testingelmo.com/api/v1/users/change-status \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "userId": 1,
    "status": 0
  }'
```

#### Delete User

```bash
curl -X DELETE https://accountant-api.testingelmo.com/api/v1/users/delete \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "userId": 1
  }'
```

## Troubleshooting

### Common Issues

#### Issue: Password validation fails

**Cause:** Password doesn't meet complexity requirements.

**Solution:**
- Minimum 8 characters
- Must contain uppercase letters
- Must contain lowercase letters
- Must contain numbers
- Must contain symbols
- Must not be compromised (checked against haveibeenpwned.com)

Example valid password: `SecurePass123!`

#### Issue: Avatar upload fails

**Cause:** File size too large or wrong format.

**Solution:**
1. Check file size (max 2MB)
2. Check file format (jpeg, jpg, png, gif only)
3. Verify storage/app/public/avatars directory exists and is writable

#### Issue: User cannot log in after creation

**Cause:** User status is INACTIVE or wrong credentials.

**Solution:**
1. Check user status: `SELECT status FROM users WHERE id = ?`
2. Verify status is 1 (ACTIVE)
3. Update if needed: `UPDATE users SET status = 1 WHERE id = ?`
4. Verify password is correct

#### Issue: Duplicate username/email error

**Cause:** Username or email already exists.

**Solution:**
1. Check existing users:
   ```sql
   SELECT * FROM users WHERE username = ? OR email = ?;
   ```
2. Use different username/email
3. Consider soft-deleted users that may have same username/email

#### Issue: Role not assigned to user

**Cause:** Role assignment failed or role doesn't exist.

**Solution:**
1. Check if role exists: `SELECT * FROM roles WHERE id = ?`
2. Check role assignment: `SELECT * FROM model_has_roles WHERE model_id = ?`
3. Manually assign if needed:
   ```php
   $user->assignRole($roleId);
   ```

#### Issue: Avatar not displaying

**Cause:** Storage link not created or wrong path.

**Solution:**
1. Create storage link: `php artisan storage:link`
2. Verify avatar path in database
3. Check file exists: `storage/app/public/avatars/{filename}`
4. Verify URL generation in resource

### Database Queries for Debugging

#### Check user with role

```sql
SELECT 
    u.id,
    u.first_name,
    u.last_name,
    u.username,
    u.email,
    u.status,
    r.name as role_name
FROM users u
LEFT JOIN model_has_roles mhr ON u.id = mhr.model_id AND mhr.model_type = 'App\\Models\\User'
LEFT JOIN roles r ON mhr.role_id = r.id
WHERE u.id = 1;
```

#### Find users without roles

```sql
SELECT 
    u.id,
    u.username,
    u.email
FROM users u
LEFT JOIN model_has_roles mhr ON u.id = mhr.model_id AND mhr.model_type = 'App\\Models\\User'
WHERE mhr.role_id IS NULL;
```

#### Find inactive users

```sql
SELECT 
    id,
    username,
    email,
    first_name,
    last_name,
    status
FROM users
WHERE status = 0
ORDER BY updated_at DESC;
```

#### Check user permissions

```sql
SELECT 
    u.username,
    r.name as role_name,
    p.name as permission_name
FROM users u
JOIN model_has_roles mhr ON u.id = mhr.model_id
JOIN roles r ON mhr.role_id = r.id
JOIN role_has_permissions rhp ON r.id = rhp.role_id
JOIN permissions p ON rhp.permission_id = p.id
WHERE u.id = 1
ORDER BY p.name;
```

#### Find users by role

```sql
SELECT 
    u.id,
    u.username,
    u.email,
    u.first_name,
    u.last_name,
    r.name as role_name
FROM users u
JOIN model_has_roles mhr ON u.id = mhr.model_id
JOIN roles r ON mhr.role_id = r.id
WHERE r.id = 2
ORDER BY u.first_name, u.last_name;
```


### Performance Optimization

#### Indexing Recommendations

```sql
-- Index for username lookups
CREATE INDEX idx_users_username 
ON users(username);

-- Index for email lookups
CREATE INDEX idx_users_email 
ON users(email);

-- Index for status filtering
CREATE INDEX idx_users_status 
ON users(status);

-- Composite index for common queries
CREATE INDEX idx_users_status_created 
ON users(status, created_at);
```

#### Query Optimization

- Use eager loading for roles: `User::with('roles')`
- Cache user permissions for frequently accessed users
- Avoid N+1 queries when loading user lists
- Use pagination for large user lists

## Integration with Other Modules

### Authentication Module
- Users authenticate with username/email and password
- JWT tokens are generated for authenticated users
- User status must be ACTIVE to log in

### Task Management Module
- Tasks are assigned to users
- User's per_hour_rate used for billing calculations
- Task time logs track which user worked on tasks

### Client Management Module
- Users create and manage clients
- created_by and updated_by track user actions

### Invoice Management Module
- Users create and manage invoices
- User permissions control invoice operations

### All Modules
- created_by and updated_by fields track user actions
- Permissions control access to all features

## Role-Based Access Control (RBAC)

### How It Works

1. **Roles**: Define groups of permissions (e.g., Admin, Accountant, Manager)
2. **Permissions**: Define specific actions (e.g., create_task, edit_invoice)
3. **Assignment**: Users are assigned one role
4. **Enforcement**: Middleware checks permissions before allowing actions

### Common Roles

**Administrator**
- Full access to all features
- User management
- System configuration

**Accountant**
- Client management
- Task management
- Invoice creation and management
- Report generation

**Manager**
- View all data
- Limited editing capabilities
- Report access

**Employee**
- View assigned tasks
- Time tracking
- Limited client access

### Permission Naming Convention

Permissions follow the pattern: `{action}_{resource}`

Examples:
- `all_users` - View users list
- `create_user` - Create new user
- `edit_user` - View user for editing
- `update_user` - Update user
- `delete_user` - Delete user
- `all_tasks` - View tasks list
- `create_task` - Create new task

### Checking Permissions

In controllers:
```php
$this->middleware('permission:all_users', ['only' => ['index']]);
```

In code:
```php
if ($user->can('create_user')) {
    // User has permission
}
```

In Blade templates:
```php
@can('create_user')
    <button>Create User</button>
@endcan
```

## Best Practices

### User Creation

1. **Strong Passwords**: Enforce password complexity requirements
2. **Unique Identifiers**: Ensure username and email are unique
3. **Role Assignment**: Always assign a role during creation
4. **Status**: Set appropriate initial status (usually ACTIVE)
5. **Avatar**: Optimize images before upload (max 2MB)

### User Management

1. **Deactivate Instead of Delete**: Use status INACTIVE instead of deleting
2. **Audit Trail**: Track who created/updated users
3. **Regular Review**: Periodically review user accounts
4. **Remove Unused**: Delete or deactivate unused accounts
5. **Update Roles**: Keep role assignments current

### Security

1. **Password Policy**: Enforce strong password requirements
2. **Account Lockout**: Implement after failed login attempts
3. **Session Management**: Use secure JWT tokens
4. **Permission Checks**: Always verify permissions before actions
5. **Audit Logging**: Log all user management actions

### Avatar Management

1. **File Size**: Limit to 2MB maximum
2. **File Types**: Only allow image formats (jpeg, jpg, png, gif)
3. **Storage**: Use Laravel's storage system
4. **Cleanup**: Delete old avatars when updating/deleting users
5. **Optimization**: Consider image optimization/resizing

## Common Use Cases

### 1. Creating an Administrator

```json
{
  "firstName": "Admin",
  "lastName": "User",
  "username": "admin",
  "email": "admin@example.com",
  "password": "AdminPass123!",
  "status": 1,
  "roleId": 1,
  "perHourRate": 0
}
```

### 2. Creating an Accountant

```json
{
  "firstName": "Maria",
  "lastName": "Rossi",
  "username": "mrossi",
  "email": "maria.rossi@example.com",
  "phone": "+39 123 456 7890",
  "password": "SecurePass123!",
  "status": 1,
  "roleId": 2,
  "perHourRate": 50.00
}
```

### 3. Deactivating a User

```json
{
  "userId": 5,
  "status": 0
}
```

### 4. Updating User Role

```json
{
  "userId": 5,
  "firstName": "Maria",
  "lastName": "Rossi",
  "username": "mrossi",
  "email": "maria.rossi@example.com",
  "status": 1,
  "roleId": 3,
  "perHourRate": 60.00
}
```

## Related Modules

- **Authentication**: User login and JWT token generation
- **Task Management**: Task assignment and time tracking
- **Client Management**: User actions tracking
- **Invoice Management**: User permissions and actions
- **Role Management**: Role and permission definitions
- **All Modules**: created_by and updated_by tracking

## Future Enhancements

- Two-factor authentication (2FA)
- Password reset functionality
- Email verification
- User activity logging
- Session management dashboard
- User groups/teams
- Bulk user operations
- User import/export
- Advanced permission management
- User profile customization
- Notification preferences
- API key management for users
- User analytics and reporting
- Account recovery options
- Social login integration

---

**Last Updated:** March 5, 2026  
**Module Version:** 1.0  
**Documentation Status:** Complete
