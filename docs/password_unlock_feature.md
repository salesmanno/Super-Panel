# Password Unlock Feature for "Ditt Nettsted"

## Overview
The "Ditt Nettsted" (Website Builder) module includes a password-based unlock mechanism that allows admins to temporarily bypass the template lock without losing and regaining module privileges.

## How It Works

### Lock Mechanism
- By default, once a website template is selected, the module is locked
- Lock is based on comparing `granted_at` timestamp vs `updated_at` timestamp
- Locked state prevents template changes to maintain stability

### Password Unlock
- Admins can unlock editing by providing the admin password
- Password is verified against `tenant_companies.admin_password_hash`
- Uses PHP's `password_verify()` function for secure comparison
- Unlock is stored in session with 30-minute expiration

### Session-Based Unlock
- Upon successful password verification, sets `$_SESSION['website_unlocked'] = true`
- Stores unlock timestamp in `$_SESSION['website_unlock_time']`
- Automatically expires after 30 minutes (1800 seconds)
- User must re-authenticate after expiration

## Files Modified

### 1. `/api/verify_admin_password.php` (NEW)
**Purpose**: API endpoint for password verification

**Features**:
- Accepts POST requests with JSON payload: `{"password": "admin_password"}`
- Validates password against `tenant_companies.admin_password_hash`
- Returns success/error response
- Sets session unlock flags on success

**Response Format**:
```json
{
  "success": true,
  "message": "Låst opp! Du kan nå redigere nettside-mal."
}
```

### 2. `/pages/ditt_nettsted.php` (MODIFIED)
**Changes**:
- Added session unlock check at page load
- Modified lock logic to bypass if `$isUnlockedByPassword = true`
- Added unlock form UI when locked
- Added JavaScript to handle password submission
- Shows success alert when unlocked via password

**UI Components**:
- Password input form with Bootstrap styling
- Real-time error display
- Loading state during verification
- Success message with expiration notice

## Security Considerations

1. **Password Hashing**: Uses bcrypt via `password_verify()`
2. **Session Timeout**: 30-minute automatic expiration
3. **HTTPS Required**: Password transmitted via HTTPS in production
4. **Session-Based**: Unlock tied to user session, expires on logout
5. **No Password Storage**: Password not stored in session, only unlock flag

## User Flow

1. Admin navigates to "Ditt Nettsted" module
2. If locked, sees warning with unlock form
3. Enters admin password and submits
4. API verifies password against database
5. On success, page reloads with editing enabled
6. Success message shows 30-minute expiration notice
7. Admin can now edit template selection
8. After 30 minutes OR logout, unlock expires

## Database Requirements

**Table**: `tenant_companies`  
**Column**: `admin_password_hash` (varchar(255))  
**Contains**: Bcrypt-hashed admin password

## Error Handling

- Missing password: Returns error message
- Invalid password: Returns "Feil passord" message
- No password configured: Returns "Admin-passord er ikke konfigurert"
- Network error: Client-side error handling with retry option
- Session expired: User must unlock again

## Future Enhancements

- [ ] Audit log for password unlock attempts
- [ ] Configurable unlock duration
- [ ] Email notification on password unlock
- [ ] Multi-factor authentication option
