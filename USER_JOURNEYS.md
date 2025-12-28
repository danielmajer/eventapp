# User Journeys

## Journey 1: Regular User - First Time Login & Create Event

**Actor:** Regular User (no MFA enabled)  
**Goal:** Log in and create a new event  
**Duration:** ~2 minutes

### Steps:

1. **Access Application**
   - User navigates to `http://localhost:5173/`
   - **Page:** Login Page
   - **UI Elements:** Email input, Password input, Login button, "Forgot password?" link

2. **Login**
   - User enters email: `user@example.com`
   - User enters password: `password123`
   - User clicks "Login" button
   - **Backend:** 
     - Rate limiting check (5 attempts/15min)
     - Find user by encrypted email
     - Verify password hash
     - Check MFA status (none)
     - Generate Sanctum token
     - Audit log: `auth.login_success`
   - **Frontend:**
     - Store token in localStorage
     - Store user in localStorage
     - Redirect to Events Page

3. **View Events Dashboard**
   - **Page:** Events Page (`/`)
   - **UI Elements:** Header with navigation, "Create New Event" button, empty event list
   - Events list loads (initially empty)
   - **Backend:** `GET /api/events` - Returns user's events (filtered by user_id)

4. **Create Event**
   - User clicks "Create New Event" button
   - Form appears (modal or inline)
   - User enters:
     - Title: "Team Meeting"
     - Date & Time: "2025-12-25 14:00"
     - Description: "Quarterly review meeting"
   - User clicks "Create"
   - **Backend:**
     - `POST /api/events`
     - Validate input
     - Create event with `user_id = auth()->id()`
     - Encrypt description field
     - Save to database
     - Audit log: `create` event
   - **Frontend:**
     - Event appears in list
     - Form clears
     - Success feedback

5. **Edit Event Description**
   - User clicks "Edit" button on an event
   - Description field becomes editable (inline)
   - User updates description: "Quarterly review meeting - Q4 2025"
   - User clicks "Save" or presses Enter
   - **Backend:**
     - `PUT /api/events/{id}`
     - Policy check: `EventPolicy::update()`
     - Encrypt new description
     - Update database
     - Audit log: `update` event
   - **Frontend:**
     - Description updates in place
     - Edit mode exits

6. **Logout**
   - User clicks "Logout" button in header
   - **Backend:**
     - `POST /api/auth/logout`
     - Delete Sanctum token
     - Audit log: `auth.logout`
   - **Frontend:**
     - Clear localStorage
     - Redirect to Login Page

---

## Journey 2: User with MFA - Login Flow

**Actor:** User with MFA enabled  
**Goal:** Log in with two-factor authentication  
**Duration:** ~1 minute

### Steps:

1. **Initial Login**
   - User enters email and password
   - Clicks "Login"
   - **Backend:**
     - Validates credentials
     - Detects `mfa_enabled = true`
     - Returns `{requires_mfa: true}` (no token yet)
     - Audit log: `auth.login_mfa_required`
   - **Frontend:**
     - UI switches to MFA verification form
     - Email field hidden
     - 6-digit code input shown

2. **MFA Verification**
   - User opens authenticator app (Google Authenticator, Authy, etc.)
   - User sees 6-digit code
   - User enters code in form
   - User clicks "Verify & Sign in"
   - **Backend:**
     - `POST /api/auth/mfa/verify`
         - Find user by encrypted email
         - Verify TOTP code with `mfa_secret`
         - Generate Sanctum token
         - Audit log: `auth.mfa_verification_success`
   - **Frontend:**
     - Store token and user
     - Redirect to Events Page

**Alternative Path - Invalid Code:**
- User enters wrong code
- **Backend:** Returns error
- **Frontend:** Shows error message
- User can retry (rate limited)

---

## Journey 3: User - Password Reset

**Actor:** User who forgot password  
**Goal:** Reset password and regain access  
**Duration:** ~3 minutes

### Steps:

1. **Request Password Reset**
   - User on Login Page clicks "Forgot your password?"
   - **Page:** Password Reset Request Page (`/reset-password`)
   - User enters email: `user@example.com`
   - User clicks "Send Reset Link"
   - **Backend:**
     - `POST /api/auth/password/email`
     - Rate limiting check
     - Find user by encrypted email
     - Generate 64-character random token
     - Hash token with bcrypt
     - Store in `password_reset_tokens` table
     - Generate reset link URL
     - Audit log: `auth.password_reset_requested`
   - **Frontend:**
     - Success message shown
     - Reset link displayed in blue box
     - Link format: `http://localhost:5173/password/reset?token=...&email=...`

2. **Click Reset Link**
   - User clicks the reset link (or copies/pastes)
   - **Page:** Password Reset Page (`/password/reset?token=...&email=...`)
   - Page reads token and email from URL parameters
   - Email field pre-filled (disabled)
   - Form ready for new password

3. **Enter New Password**
   - User enters new password: `NewPass123!`
   - User confirms password: `NewPass123!`
   - User clicks "Reset Password"
   - **Backend:**
     - `POST /api/auth/password/reset`
     - Rate limiting check
     - Find user by encrypted email
     - Verify token:
         - Check token exists in database
         - Check token not expired (60 minutes)
         - Verify hash matches
     - Update password (hash with bcrypt)
     - **Delete token** (single-use enforcement)
     - Audit log: `auth.password_reset_completed`
   - **Frontend:**
     - Success message shown
     - Auto-redirect to Login Page after 3 seconds

4. **Login with New Password**
   - User returns to Login Page
   - User enters email and new password
   - User logs in successfully
   - **Backend:**
     - Validates new password
     - Generates token
     - Audit log: `auth.login_success`

**Alternative Paths:**
- **Token Expired:** User sees error, must request new link
- **Token Already Used:** User sees error, must request new link
- **Invalid Token:** User sees error, must request new link

---

## Journey 4: User - Helpdesk Chat with Bot

**Actor:** Regular User  
**Goal:** Get help from AI bot, optionally transfer to human  
**Duration:** ~5-10 minutes

### Steps:

1. **Start Chat**
   - User navigates to `/chat`
   - **Page:** Helpdesk Chat Page
   - User sees chat list (sidebar) and main chat area
   - User clicks "New Chat" button
   - **Backend:**
     - `POST /api/helpdesk/chats`
     - Create chat with status "open"
     - Optional: If initial message provided, save it and generate bot reply
   - **Frontend:**
     - New chat appears in sidebar
     - Chat opens in main area

2. **Chat with Bot**
   - User types: "How do I create an event?"
   - User clicks "Send"
   - **Backend:**
     - `POST /api/helpdesk/chats/{id}/messages`
     - Save user message (encrypt content)
     - Check if transfer request (not detected)
     - Build conversation context (all previous messages)
     - Call Google Gemini API:
         - Endpoint: `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent`
         - System prompt: Helpdesk bot instructions
         - User message + conversation history
     - Receive bot response
     - Save bot message (encrypt content)
     - Audit log: `helpdesk.user_message`
   - **Frontend:**
     - User message appears immediately
     - Loading indicator shown
     - Bot message appears when received
     - Auto-scroll to bottom

3. **Continue Conversation**
   - User asks follow-up: "How do I edit an event?"
   - Bot responds with instructions
   - Conversation continues

4. **Request Human Transfer**
   - User types: "transfer me" or "I want to talk to a human"
   - User clicks "Send"
   - **Backend:**
     - Detects transfer request via `isTransferRequest()`
     - Updates chat status to "transferred"
     - Adds automatic bot message: "This chat has been transferred to a human agent..."
     - Audit log: `helpdesk.transfer_requested`
   - **Frontend:**
     - Chat status badge changes to "Transferred"
     - Bot message appears
     - User can still send messages (waiting for agent)

5. **Agent Responds**
   - Helpdesk agent views chat
   - Agent sends response
   - **Backend:**
     - `POST /api/helpdesk/chats/{id}/agent-messages`
     - Save agent message (encrypt content)
     - Audit log: `helpdesk.agent_message`
   - **Frontend:**
     - Agent message appears
     - User sees response

6. **Complete Chat**
   - User clicks "Complete Chat" button
   - **Backend:**
     - `POST /api/helpdesk/chats/{id}/close`
     - Update chat status to "closed"
     - Audit log: `helpdesk.chat_closed`
   - **Frontend:**
     - Chat status badge changes to "Closed"
     - Message input disabled
     - Send button disabled
     - Complete button hidden

---

## Journey 5: Helpdesk Agent - Respond to Multiple Chats

**Actor:** Helpdesk Agent or Admin  
**Goal:** Monitor and respond to user chats  
**Duration:** Ongoing

### Steps:

1. **Access Helpdesk Console**
   - Agent logs in (same flow as regular user)
   - Header shows "Helpdesk" button (visible to agents/admins)
   - Agent clicks "Helpdesk"
   - **Page:** Helpdesk Agent Console (`/helpdesk`)

2. **View All Chats**
   - **Backend:**
     - `GET /api/helpdesk/chats`
     - Middleware: `EnsureHelpdeskAgent` (checks role)
     - Returns all chats (not filtered by user_id)
   - **Frontend:**
     - Sidebar shows list of all chats
     - Filter buttons: All, Open, Transferred, Closed
     - Each chat shows: user email, status badge
     - Default filter: "All" (shows all chats)

3. **Filter Chats**
   - Agent clicks a filter button (e.g., "Transferred")
   - **Frontend:**
     - Filter state updates
     - Chat list filters client-side based on status
     - Active filter button highlights (colored background)
     - Shows only chats matching selected filter
     - Empty state message updates: "No [filter] chats available"
   - **Filter Options:**
     - **All** - Shows all chats regardless of status
     - **Open** - Shows only chats with status "open"
     - **Transferred** - Shows only chats with status "transferred" (waiting for agent)
     - **Closed** - Shows only chats with status "closed"

4. **Open Chat**
   - Agent clicks on a chat
   - **Backend:**
     - `GET /api/helpdesk/chats/{id}`
     - Policy check: `HelpdeskChatPolicy::view()` (allows agents)
     - Returns chat with all messages (decrypted)
   - **Frontend:**
     - Chat details load in main area
     - Message history displayed
     - Shows: user messages, bot messages, agent messages (if any)

5. **Respond to User**
   - Agent reads user's question
   - Agent types response: "To create an event, click the 'Create New Event' button..."
   - Agent clicks "Send"
   - **Backend:**
     - `POST /api/helpdesk/chats/{id}/agent-messages`
     - Policy check: Agent can respond
     - Check chat not closed
     - Save agent message (encrypt content)
     - Audit log: `helpdesk.agent_message`
   - **Frontend:**
     - Agent message appears in chat
     - User sees message in their chat page

6. **Close Chat**
   - After resolving issue, agent clicks "Close Chat"
   - **Backend:**
     - `POST /api/helpdesk/chats/{id}/close` (agent endpoint)
     - Update chat status to "closed"
     - Audit log: `helpdesk.chat_closed_by_agent`
   - **Frontend:**
     - Chat status updates
     - Message input disabled
     - User cannot send more messages

7. **Switch to Another Chat**
   - Agent clicks on different chat in sidebar
   - Process repeats

---

## Journey 6: User - Enable MFA

**Actor:** Regular User  
**Goal:** Set up two-factor authentication for account security  
**Duration:** ~3 minutes

### Steps:

1. **Navigate to Preferences**
   - User clicks "Preferences" in header
   - **Page:** User Preferences Page (`/preferences`)
   - **Backend:**
     - `GET /user` (if needed)
     - Returns user data including `mfa_enabled` status

2. **Initiate MFA Setup**
   - User sees "Two-Factor Authentication" section
   - Status shows: "Disabled"
   - User clicks "Enable 2FA" or "Setup MFA"
   - **Backend:**
     - `POST /api/auth/mfa/setup`
     - Generate MFA secret using `PHPGangsta_GoogleAuthenticator`
     - Save secret to user (encrypted)
     - Generate QR code URL:
         - Format: `otpauth://totp/AppName:email?secret=...&issuer=...`
         - QR code service: Google Charts API
     - Return: `{secret, qr_code_url, user}`
   - **Frontend:**
     - QR code image displayed
     - Secret code shown (for manual entry)
     - Verification code input appears

3. **Scan QR Code**
   - User opens authenticator app (Google Authenticator, Authy, Microsoft Authenticator, etc.)
   - User scans QR code
   - Account added to app
   - App shows 6-digit code

4. **Verify Setup**
   - User enters 6-digit code from app
   - User clicks "Confirm" or "Verify"
   - **Backend:**
     - `POST /api/auth/mfa/confirm`
     - Verify code using `PHPGangsta_GoogleAuthenticator::verifyCode()`
     - If valid:
         - Set `mfa_enabled = true`
         - Save user
         - Audit log: `auth.mfa_enabled`
     - If invalid:
         - Return error
         - User can retry
   - **Frontend:**
     - If success: Status changes to "Enabled"
     - QR code hidden (only shown during setup)
     - Success message shown

5. **Test MFA Login**
   - User logs out
   - User logs in again
   - After password, MFA form appears
   - User enters code from app
   - User successfully logs in

**Alternative Path - Disable MFA:**
- User clicks "Disable 2FA"
- User must enter current MFA code to disable
- **Backend:**
  - `POST /api/auth/mfa/disable`
  - Verify code
  - Set `mfa_enabled = false`
  - Optionally clear `mfa_secret`
  - Audit log: `auth.mfa_disabled`

---

## Journey 7: User - Edit Event Description

**Actor:** Regular User  
**Goal:** Update event description  
**Duration:** ~30 seconds

### Steps:

1. **View Events**
   - User on Events Page
   - Sees list of events

2. **Edit Event**
   - User clicks "Edit" button on an event
   - **Frontend:**
     - Description field becomes editable (inline)
     - "Edit" button changes to "Save" and "Cancel"
     - User can type new description

3. **Save Changes**
   - User updates description: "Updated description"
   - User clicks "Save"
   - **Backend:**
     - `PUT /api/events/{id}`
     - Policy check: `EventPolicy::update()` (user owns event)
     - Validate: Only description can be updated
     - Encrypt new description
     - Update database
     - Audit log: `update` event (with old/new values)
   - **Frontend:**
     - Description updates in place
     - Edit mode exits
     - Success feedback (optional)

**Alternative Path - Cancel:**
- User clicks "Cancel"
- Description reverts to original
- Edit mode exits

---

## Journey 8: User - Delete Event

**Actor:** Regular User  
**Goal:** Remove an event  
**Duration:** ~10 seconds

### Steps:

1. **Delete Event**
   - User on Events Page
   - User clicks "Delete" button on an event
   - **Frontend:**
     - Confirmation dialog (optional, or immediate)
     - User confirms deletion

2. **Process Deletion**
   - **Backend:**
     - `DELETE /api/events/{id}`
     - Policy check: `EventPolicy::delete()` (user owns event)
     - Log event data before deletion (audit)
     - Delete event from database
     - Audit log: `delete` event
   - **Frontend:**
     - Event removed from list
     - Success feedback

---

## Journey 9: User - Multiple Failed Login Attempts (Rate Limiting)

**Actor:** Attacker or User with Wrong Password  
**Goal:** Attempt brute force login  
**Duration:** ~2 minutes

### Steps:

1. **Failed Attempt 1**
   - User enters wrong password
   - **Backend:**
     - Validates credentials
     - Login fails
     - Rate limiter: 1/5 attempts
     - Audit log: `auth.login_failed`
   - **Frontend:**
     - Error message: "Invalid credentials"

2. **Failed Attempts 2-4**
   - User tries different passwords
   - Each attempt logged
   - Rate limiter: 2/5, 3/5, 4/5

3. **Failed Attempt 5**
   - User tries again
   - **Backend:**
     - Rate limiter: 5/5 attempts
     - Still allows (not blocked yet)

4. **Failed Attempt 6 (Rate Limited)**
   - User tries again
   - **Backend:**
     - Rate limiter detects: `tooManyAttempts()`
     - Calculates retry time (15 minutes)
     - Audit log: `rate_limit_exceeded`
     - Returns: `429 Too Many Requests`
   - **Frontend:**
     - Error message: "Too many attempts. Please try again in X minutes."
     - Shows retry countdown

5. **Wait Period**
   - User must wait 15 minutes
   - Or clear rate limit cache (admin action)

---

## Journey 10: Helpdesk Agent - Transfer Chat

**Actor:** Helpdesk Agent  
**Goal:** Transfer a chat that was auto-transferred  
**Duration:** ~1 minute

### Steps:

1. **View Transferred Chat**
   - Agent on Helpdesk Console
   - Sees chat with status "transferred"
   - Chat was auto-transferred when user said "transfer me"

2. **Agent Takes Over**
   - Agent opens chat
   - Reads conversation history
   - Sees user's question

3. **Agent Responds**
   - Agent types response
   - Agent sends message
   - User receives agent message
   - Conversation continues

**Note:** The "transfer" action is automatic when user requests it. Agent just responds normally.

---

## Journey 11: User - View Audit Logs (Admin)

**Actor:** Admin (Future Feature)  
**Goal:** Review security events  
**Duration:** Ongoing

### Steps:

1. **Access Audit Logs**
   - Admin navigates to audit log viewer (future feature)
   - **Backend:**
     - Query `audit_logs` table
     - Filter by date, user, action type
   - **Frontend:**
     - Display log entries in table
     - Show: timestamp, user, action, resource, IP address

2. **Review Security Events**
   - Admin sees:
     - Failed login attempts
     - Password resets
     - MFA enable/disable
     - Event create/update/delete
     - Access denied attempts
     - Rate limit violations

3. **Investigate Issues**
   - Admin filters by user
   - Admin checks suspicious patterns
   - Admin takes action if needed

---

## Error Scenarios

### Scenario 1: Invalid Credentials
- User enters wrong password
- **Response:** "Invalid credentials"
- **Action:** User retries or clicks "Forgot password?"

### Scenario 2: Expired Token
- User's session token expires
- **Response:** 401 Unauthorized
- **Action:** Frontend redirects to Login Page

### Scenario 3: Access Denied
- User tries to delete another user's event
- **Response:** 403 Forbidden
- **Action:** Error message shown, action blocked
- **Audit:** `access_denied` logged

### Scenario 4: Rate Limited
- User exceeds rate limit
- **Response:** 429 Too Many Requests
- **Action:** User must wait before retrying

### Scenario 5: Chat Closed
- User tries to send message to closed chat
- **Response:** 403 Forbidden
- **Message:** "This chat is closed. No new messages can be sent."
- **Action:** User cannot send message

---

## User Personas

### Persona 1: Regular User
- **Role:** `user`
- **Goals:** Manage events, get help when needed
- **Journeys:** 1, 3, 4, 6, 7, 8

### Persona 2: Security-Conscious User
- **Role:** `user`
- **Goals:** Secure account with MFA
- **Journeys:** 2, 6

### Persona 3: Helpdesk Agent
- **Role:** `helpdesk_agent`
- **Goals:** Respond to user queries, resolve issues
- **Journeys:** 5, 10

### Persona 4: Administrator
- **Role:** `admin`
- **Goals:** Manage system, access all features
- **Journeys:** All (has access to helpdesk console)

---

This document provides detailed user journeys for all major workflows in the application.

