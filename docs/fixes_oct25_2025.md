# Fixes - October 25, 2025

## Issues Fixed

### 1. Firmadata - Implemented Password Unlock Feature
**Problem**: 
- "Firmadata" showed message "Kontakt systemadministrator for å endre firmadata" 
- All fields were hardcoded as `readonly` with no way to edit
- No unlock mechanism existed for firmadata editing

**Solution**: Implemented complete password-protected editing system:
1. **Session Check**: Added check for `$_SESSION['website_unlocked']` with 30-minute timeout
2. **Unlock Form**: Added password input form that appears when locked
3. **Dynamic Fields**: Fields are `readonly` when locked, editable when unlocked
4. **Save Function**: Added save button and API endpoint for updating company data
5. **JavaScript**: Added unlock handler and save handler functions
6. **API Endpoint**: Created `api/update_company_data.php` for secure data updates

**How It Works**:
1. **Locked State (Default)**: 
   - Warning message: "Låst: Oppgi admin-passord for å redigere firmadata"
   - Password input form appears
   - All fields are `readonly`
   
2. **Unlocking Process**:
   - User enters admin password
   - JavaScript calls `/api/verify_admin_password.php`
   - On success: Sets `$_SESSION['website_unlocked']` and `$_SESSION['website_unlock_time']`
   - Page reloads showing unlocked state
   
3. **Unlocked State**:
   - Success message: "Låst opp! Du kan nå redigere firmadata. Opplåsingen utløper om 30 minutter"
   - All fields become editable
   - "Lagre Endringer" button appears
   
4. **Saving Changes**:
   - User edits fields and clicks "Lagre Endringer"
   - JavaScript calls `/api/update_company_data.php`
   - API verifies session is still unlocked
   - Updates allowed fields in `tenant_companies` table

**Files Modified**:
- `modules/deres_firma_content.php` - Added unlock UI and dynamic field logic
- `api/update_company_data.php` - NEW: API endpoint for saving company data

**Session Variables Used**:
- `$_SESSION['website_unlocked']` - Boolean flag indicating unlocked state
- `$_SESSION['website_unlock_time']` - Timestamp of unlock (for 30-min expiry)

**Security Features**:
- Password verified against `tenant_companies.admin_password_hash` (bcrypt)
- Session-based unlock with automatic 30-minute timeout
- API validates session before allowing updates
- Only allowed fields can be updated (whitelist)

---

### 2. Ditt Nettsted - Database Table Mismatch
**Problem**: 
- Page was trying to query non-existent tables (`website_templates`, `webconfiguration`)
- Used wrong column name (`slug` instead of `module_key`)
- Password unlock feature wasn't being triggered because lock logic never activated
- No useful interface for viewing/managing website settings

**Root Cause**:
The code was written for a different database schema that doesn't exist in production.

**Solution**: Complete rewrite of `pages/ditt_nettsted.php`:
- Fixed table references to use `tenant_website_config` (existing table)
- Fixed column reference to use `module_key` instead of `slug`
- Created clean UI showing current website configuration
- Displays: publication status, enabled/disabled state, SEO settings, display name
- Password unlock now works correctly
- Lock mechanism tracks if settings exist and compares timestamps

**Files Modified**:
- `pages/ditt_nettsted.php` (complete rewrite)

**Lock Mechanism Explanation**:
1. If no settings exist (`tenant_website_config` is empty) → UNLOCKED (can configure)
2. If settings exist AND `granted_at` <= `updated_at` → LOCKED (needs password)
3. If password unlocked → Session flag set for 30 minutes → UNLOCKED temporarily
4. Password verification via `api/verify_admin_password.php`

**Database Tables Used**:
- `tenant_website_config` - Stores website configuration per company
- `tenant_companies` - Contains `admin_password_hash` for unlock
- `tenant_admin_modules` - Tracks when module was granted to admin
- `system_modules` - Module definitions (uses `module_key` column)

---

## Testing

✅ PHP syntax validated for both files
✅ Database queries use correct table names
✅ Lock mechanism logic verified
✅ Password unlock API endpoint functional

---

## Related Files

**Core Files**:
- `pages/ditt_nettsted.php` - Website configuration viewer
- `modules/deres_firma_content.php` - Company data management
- `api/verify_admin_password.php` - Password verification endpoint

**Documentation**:
- `docs/password_unlock_feature.md` - Password unlock documentation
- `replit.md` - Updated module description

---

## Shared Session Variables

Both "Ditt Nettsted" and "Firmadata" modules share the same session variables for unlock status:

```php
$_SESSION['website_unlocked']      // Boolean: true when unlocked
$_SESSION['website_unlock_time']   // Timestamp: when unlock occurred
```

**This means:**
- ✅ Unlocking in "Firmadata" also unlocks "Ditt Nettsted" 
- ✅ Unlocking in "Ditt Nettsted" also unlocks "Firmadata"
- ✅ Both expire after 30 minutes
- ✅ Single password verification unlocks both modules

**Where Session is Set**: `api/verify_admin_password.php` (lines 49-50)

**Where Session is Checked**:
- `pages/ditt_nettsted.php` (lines 24-33)
- `modules/deres_firma_content.php` (lines 9-18)
