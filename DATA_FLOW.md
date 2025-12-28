# Data Flow Diagrams

## 1. Authentication Flow

```
┌─────────┐
│  User   │
└────┬────┘
     │
     │ 1. POST /api/auth/login
     │    {email: "user@example.com", password: "pass"}
     ▼
┌─────────────────┐
│  Frontend       │
│  (React)        │
│  LoginPage.tsx  │
└────┬────────────┘
     │
     │ 2. HTTP Request (JSON)
     │    Headers: Content-Type: application/json
     ▼
┌─────────────────┐       ┌──────────────┐
│  Backend API    │──────▶│  ForceHttps  │
│  (Laravel)      │       │  Middleware  │
└────┬────────────┘       └──────────────┘
     │
     │ 3. Rate Limiting Check
     ▼
┌─────────────────┐       ┌──────────────┐
│  ThrottleAuth   │──────▶│  Cache       │
│  Middleware     │       │  (Rate Limit)│
└────┬────────────┘       └──────────────┘
     │
     │ 4. Validate Input
     ▼
┌─────────────────┐
│  AuthController│
│  .login()      │
└────┬────────────┘
     │
     │ 5. Find User (Encrypted Email)
     ▼
┌─────────────────┐       ┌──────────────┐
│  findUserByEmail│──────▶│  User Model  │
│  ()             │       │  (Encrypted) │
└────┬────────────┘       └──────────────┘
     │
     │ 6. Decrypt Email (via EncryptsFields trait)
     ▼
┌─────────────────┐
│  FieldEncryption│
│  Service        │
│  .decrypt()     │
└────┬────────────┘
     │
     │ 7. Verify Password
     ▼
┌─────────────────┐
│  Hash::check()  │
│  (bcrypt)       │
└────┬────────────┘
     │
     │ 8. Check MFA
     ▼
┌─────────────────┐
│  mfa_enabled?   │──Yes──▶ Return {requires_mfa: true}
│                 │
│                 │──No───▶ Generate Token
└────┬────────────┘
     │
     │ 9. Create Sanctum Token
     ▼
┌─────────────────┐
│  User Model     │
│  .createToken() │
└────┬────────────┘
     │
     │ 10. Store Token
     ▼
┌─────────────────┐
│  personal_access│
│  _tokens table  │
│  - token (hash) │
│  - user_id      │
└────┬────────────┘
     │
     │ 11. Audit Log
     ▼
┌─────────────────┐       ┌──────────────┐
│  AuditLogService│──────▶│  audit_logs  │
│  .logAuth()     │       │  table       │
└────┬────────────┘       └──────────────┘
     │
     │ 12. Return Response
     ▼
┌─────────────────┐
│  JSON Response  │
│  {token, user}  │
└────┬────────────┘
     │
     │ 13. Store & Redirect
     ▼
┌─────────────────┐
│  Frontend       │──────▶ localStorage.setItem('auth_token')
│  (React)        │      │ localStorage.setItem('auth_user')
│                 │      │ navigate('/')
└─────────────────┘      └──────────────┘
```

---

## 2. Event Creation Flow

```
┌─────────┐
│  User   │
└────┬────┘
     │
     │ 1. Fill Form
     │    {title, occurs_at, description}
     ▼
┌─────────────────┐
│  EventsPage     │
│  (React)        │
└────┬────────────┘
     │
     │ 2. POST /api/events
     │    Authorization: Bearer {token}
     │    Body: {title, occurs_at, description}
     ▼
┌─────────────────┐       ┌──────────────┐
│  Backend API    │──────▶│  Sanctum     │
│  /api/events    │       │  Auth        │
└────┬────────────┘       └──────────────┘
     │
     │ 3. Validate Request
     ▼
┌─────────────────┐
│  EventController│
│  .store()       │
└────┬────────────┘
     │
     │ 4. Validate Input
     │    - title: required|string|max:255
     │    - occurs_at: required|date
     │    - description: nullable|string
     ▼
┌─────────────────┐
│  Request        │
│  Validation     │
└────┬────────────┘
     │
     │ 5. Create Event Model
     ▼
┌─────────────────────────┐
│  Event::create()        │
│  {                      │
│    user_id: auth()->id()│
│    title: ...           │
│    occurs_at: ...       │
│    description: ...     │
│  }                      │
└────┬────────────────────┘
     │
     │ 6. Encrypt Description
     ▼
┌─────────────────┐       ┌─────────────────┐
│  EncryptsFields │──────▶│  FieldEncryption│
│  Trait          │       │  Service        │
│  (saving event) │       │  .encrypt()     │
└────┬────────────┘       └─────────────────┘
     │
     │ 7. AES-256-CBC Encryption
     │    Key: DB_FIELD_ENCRYPTION_KEY
     ▼
┌─────────────────┐
│  Encrypted      │
│  Description    │
│  (Base64)       │
└────┬────────────┘
     │
     │ 8. Save to Database
     ▼
┌─────────────────┐
│  SQLite DB      │
│  events table   │
│  - id           │
│  - user_id      │
│  - title        │
│  - occurs_at    │
│  - description* │ (* = encrypted)
└────┬────────────┘
     │
     │ 9. Audit Log
     ▼
┌─────────────────┐       ┌─────────────────┐
│  Auditable      │──────▶│  AuditLogService│
│  Trait          │       │  .logCreate()   │
│  (created event)│       └─────────────────┘
└────┬────────────┘
     │
     │ 10. Store Audit Entry
     ▼
┌─────────────────┐
│  audit_logs     │
│  table          │
│  - action: create│
│  - resource_type: events│
│  - resource_id: 1│
│  - user_id: 1   │
└────┬────────────┘
     │
     │ 11. Return Event (Decrypted)
     ▼
┌─────────────────┐
│  Event Model    │──────▶ EncryptsFields trait
│  (retrieved)    │      │ (decrypts description)
└────┬────────────┘      └────────────────────────┘
     │
     │ 12. JSON Response
     ▼
┌─────────────────┐
│  Frontend       │──────▶ Display in Event List
│  (React)        │      │ Update UI
└─────────────────┘      └─────────────────────────┘
```

---

## 3. Helpdesk Chat - Bot Response Flow

```
┌─────────┐
│  User   │
└────┬────┘
     │
     │ 1. Type Message
     │    "How do I create an event?"
     ▼
┌──────────────────┐
│  HelpdeskChatPage│
│  (React)         │
└────┬─────────────┘
     │
     │ 2. POST /api/helpdesk/chats/{id}/messages
     │    {message: "How do I create an event?"}
     ▼
┌───────────────────────┐
│  Helpdesk             │
│  Controller           │
│  .addMessageFromUser()│
└────┬──────────────────┘
     │
     │ 3. Policy Check
     ▼
┌─────────────────┐
│  HelpdeskChat   │
│  Policy::view() │
└────┬────────────┘
     │
     │ 4. Check Chat Status
     │    (not closed)
     ▼
┌─────────────────┐
│  Save User      │
│  Message        │
└────┬────────────┘
     │
     │ 5. Encrypt Content
     ▼
┌─────────────────┐       ┌────────────────┐
│  HelpdeskMessage│──────▶│  EncryptsFields│
│  Model          │       │  (encrypts     │
│  .create()      │       │   content)     │
└────┬────────────┘       └────────────────┘
     │
     │ 6. Store in Database
     ▼
┌─────────────────┐
│  helpdesk_      │
│  messages table │
│  - content*     │ (* = encrypted)
└────┬────────────┘
     │
     │ 7. Check Transfer Request
     ▼
┌─────────────────────┐
│  isTransferRequest()│
│  ("transfer me")    │
└────┬────────────────┘
     │
     │ 8. Build Conversation Context
     ▼
┌────────────────────┐
│  buildConversation │
│  Context()         │
│  - Get all messages│
│  - Format:         │
│    User: ...       │
│    Bot: ...        │
└────┬───────────────┘
     │
     │ 9. Call Google Gemini API
     ▼
┌────────────────────┐       ┌──────────────┐
│  HTTP Client       │──────▶│  Google      │
│  (Guzzle)          │       │  Gemini API  │
│  POST https://     │       │  (NLP)       │
│  generativelanguage│       │              │
│  .googleapis.com/  │       │              │
│  v1beta/models/    │       │              │
│  gemini-2.5-flash  │       │              │
│  :generateContent  │       │              │
└────┬───────────────┘       └──────────────┘
     │
     │ 10. Request Body
     │     {
     │       contents: [{
     │         parts: [{
     │           text: "System prompt + conversation"
     │         }]
     │       }]
     │     }
     │
     │ 11. API Response
     │     {
     │       candidates: [{
     │         content: {
     │           parts: [{
     │             text: "Bot response text"
     │           }]
     │         }
     │       }]
     │     }
     ▼
┌─────────────────┐
│  Parse Response │
│  Extract Text   │
└────┬────────────┘
     │
     │ 12. Save Bot Message
     ▼
┌──────────────────┐
│  HelpdeskMessage │──────▶ EncryptsFields (encrypts content)
│  Model           │
│  .create()       │
│  sender_type: bot│
└────┬─────────────┘
     │
     │ 13. Return Messages
     ▼
┌─────────────────┐
│  JSON Response  │
│  [user_msg,     │
│   bot_msg]      │
└────┬────────────┘
     │
     │ 14. Display Messages
     ▼
┌─────────────────┐
│  Frontend       │──────▶ Render in Chat UI
│  (React)        │      │ Auto-scroll
└─────────────────┘      └───────────────────┘
```

### Helpdesk Agent Filter Flow
```
┌─────────┐
│  Agent  │
│  Clicks │
│  Filter │
│  Button │
└────┬────┘
     │
     ▼
┌───────────────────┐
│  Frontend         │
│  HelpdeskAgentPage│
│  setFilter()      │
└────┬──────────────┘
     │
     │ 1. Update filter state
     │    ('all' | 'open' | 'transferred' | 'closed')
     ▼
┌─────────────────┐
│  Filter Chats   │
│  Client-Side    │
│  chats.filter() │
└────┬────────────┘
     │
     │ 2. Filter logic
     │    if (filter === 'all') return true
     │    return chat.status === filter
     ▼
┌──────────────────┐
│  Update UI       │
│  - Show filtered │
│    chats only    │
│  - Highlight     │
│    active filter │
│  - Update empty  │
│    state message │
└──────────────────┘
```

**Filter Options:**
- **All** - Shows all chats (no filtering)
- **Open** - Shows chats with `status === 'open'`
- **Transferred** - Shows chats with `status === 'transferred'`
- **Closed** - Shows chats with `status === 'closed'`

**Benefits:**
- ✅ Client-side filtering (fast, no API calls)
- ✅ Agents can quickly find priority chats (transferred)
- ✅ Better workflow organization
- ✅ Visual feedback (active filter highlighted)

---

## 4. Password Reset Flow

```
┌─────────┐
│  User   │
└────┬────┘
     │
     │ 1. Request Reset
     │    POST /api/auth/password/email
     │    {email: "user@example.com"}
     ▼
┌──────────────────────────┐
│  AuthController          │
│  .sendPasswordResetLink()│
└────┬─────────────────────┘
     │
     │ 2. Find User (Encrypted Email)
     ▼
┌──────────────────┐
│  findUserByEmail │
│  ()              │
└────┬─────────────┘
     │
     │ 3. Generate Token
     ▼
┌──────────────────┐
│  Str::random(64) │
│  Token: abc123...│
└────┬─────────────┘
     │
     │ 4. Hash Token
     ▼
┌─────────────────┐
│  Hash::make()   │
│  (bcrypt)       │
└────┬────────────┘
     │
     │ 5. Store Token
     ▼
┌──────────────────┐
│  password_reset_ │
│  tokens table    │
│  - email (PK)    │
│  - token (hashed)│
│  - created_at    │
└────┬─────────────┘
     │
     │ 6. Generate Reset Link
     ▼
┌───────────────────────────────────────┐
│  Frontend URL                         │
│  + token                              │
│  + email                              │
│  = /password/reset?token=...&email=...│
└────┬──────────────────────────────────┘
     │
     │ 7. Return Link
     ▼
┌─────────────────┐
│  JSON Response  │
│  {reset_link:   │
│   "http://..."} │
└────┬────────────┘
     │
     │ 8. Display Link
     ▼
┌─────────────────┐
│  Frontend       │──────▶ Show in UI (clickable)
│  (React)        │
└─────────────────┘
     │
     │ 9. User Clicks Link
     ▼
┌─────────────────┐
│  Password Reset │
│  Page           │
│  Reads URL params│
└────┬────────────┘
     │
     │ 10. User Enters New Password
     │     POST /api/auth/password/reset
     │     {token, email, password, password_confirmation}
     ▼
┌──────────────────┐
│  AuthController  │
│  .resetPassword()│
└────┬─────────────┘
     │
     │ 11. Find User
     ▼
┌─────────────────┐
│  findUserByEmail│
│  ()             │
└────┬────────────┘
     │
     │ 12. Verify Token
     ▼
┌───────────────────┐
│  DB Query         │
│  password_reset_  │
│  tokens           │
│  WHERE email = ...│
└────┬──────────────┘
     │
     │ 13. Check Token
     │     - Exists?
     │     - Not expired? (60 min)
     │     - Hash matches?
     ▼
┌─────────────────┐
│  Hash::check()  │
│  (token)        │
└────┬────────────┘
     │
     │ 14. Update Password
     ▼
┌────────────────────────┐
│  User Model            │
│  .forceFill()          │
│  password: Hash::make()│
└────┬───────────────────┘
     │
     │ 15. Delete Token (Single-Use)
     ▼
┌───────────────────┐
│  DELETE FROM      │
│  password_reset_  │
│  tokens           │
│  WHERE email = ...│
└────┬──────────────┘
     │
     │ 16. Audit Log
     ▼
┌──────────────────────────┐
│  AuditLogService         │
│  .logAuth()              │
│  password_reset_completed│
└────┬─────────────────────┘
     │
     │ 17. Success Response
     ▼
┌─────────────────┐
│  Frontend       │──────▶ Show success, redirect to login
│  (React)        │
└─────────────────┘
```

---

## 5. Field Encryption Flow

```
┌────────────────────┐
│  User Input        │
│  (Plaintext)       │
│  "user@example.com"│
└────┬───────────────┘
     │
     │ 1. Model Save Event
     ▼
┌──────────────────────────────┐
│  Event Model                 │
│  $event->description = "text"│
│  $event->save()              │
└────┬─────────────────────────┘
     │
     │ 2. EncryptsFields Trait
     │    (saving event)
     ▼
┌──────────────────────┐
│  bootEncryptsFields()│
│  static::saving()    │
└────┬─────────────────┘
     │
     │ 3. Check Field in $encrypted
     │    protected $encrypted = ['description']
     ▼
┌──────────────────────────┐
│  getEncryptedFields()    │
│  Returns: ['description']│
└────┬─────────────────────┘
     │
     │ 4. Check if Already Encrypted
     ▼
┌─────────────────┐
│  FieldEncryption│
│  Service        │
│  .isEncrypted() │
└────┬────────────┘
     │
     │ 5. Encrypt Value
     ▼
┌─────────────────┐
│  FieldEncryption│
│  Service        │
│  .encrypt()     │
└────┬────────────┘
     │
     │ 6. Get Encryption Key
     ▼
┌────────────────────────┐
│  config('database.     │ 
│  field_encryption_key')│
│  or APP_KEY            │
└────┬───────────────────┘
     │
     │ 7. Ensure Key Length (32 chars)
     │    Hash if needed
     ▼
┌─────────────────┐
│  substr(hash(   │
│  'sha256', key),│
│  0, 32)         │
└────┬────────────┘
     │
     │ 8. AES-256-CBC Encryption
     ▼
┌──────────────────┐
│  Encrypter       │
│  (Laravel)       │
│  .encryptString()│
└────┬─────────────┘
     │
     │ 9. Encrypted Value
     │    Base64 encoded
     ▼
┌─────────────────┐
│  Encrypted      │
│  "eyJpdiI6..."  │
└────┬────────────┘
     │
     │ 10. Store in Database
     ▼
┌────────────────────┐
│  SQLite DB         │
│  events.description│
│  (encrypted)       │
└────┬───────────────┘
     │
     │ 11. Model Retrieve
     ▼
┌─────────────────┐
│  Event::find(1) │
└────┬────────────┘
     │
     │ 12. EncryptsFields Trait
     │     (retrieved event)
     ▼
┌─────────────────┐
│  bootEncryptsFields()│
│  static::retrieved()│
└────┬────────────┘
     │
     │ 13. Decrypt Value
     ▼
┌─────────────────┐
│  FieldEncryption│
│  Service        │
│  .decrypt()     │
└────┬────────────┘
     │
     │ 14. AES-256-CBC Decryption
     ▼
┌─────────────────┐
│  Encrypter      │
│  .decryptString()│
└────┬────────────┘
     │
     │ 15. Plaintext Value
     ▼
┌─────────────────┐
│  "user@example.com"│
└────┬────────────┘
     │
     │ 16. Return to Application
     ▼
┌─────────────────┐
│  Frontend       │──────▶ Display to User
│  (React)        │
└─────────────────┘
```

---

## 6. Complete System Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                        Frontend (React SPA)                         │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐             │
│  │ Login    │  │ Events   │  │ Chat     │  │ Prefs    │             │
│  │ Page     │  │ Page     │  │ Page     │  │ Page     │             │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘  └────┬─────┘             │
│       │              │              │              │                │
│       └──────────────┴──────────────┴──────────────┘                │
│                          │                                          │
│                    ┌─────▼─────┐                                    │
│                    │  API Client│                                   │
│                    │  (api.ts)  │                                   │
│                    │  - Bearer Token│                               │
│                    │  - Error Handling│                             │
│                    └─────┬─────┘                                    │
└──────────────────────────┼──────────────────────────────────────────┘
                           │
                           │ HTTPS/TLS
                           │ JSON
                           │ Authorization: Bearer {token}
                           ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    Backend (Laravel API)                            │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │              Middleware Stack                                │   │
│  │  ┌──────────────┐  ┌───────────────┐  ┌───────────────┐      │   │
│  │  │ ForceHttps   │  │ ThrottleAuth  │  │ Sanctum Auth  │      │   │
│  │  │ - Reject HTTP│  │ - Rate Limit  │  │ - Verify Token│      │   │
│  │  │ - Security   │  │ - Log Attempts│  │ - Load User   │      │   │
│  │  │   Headers    │  │               │  │               │      │   │
│  │  └──────────────┘  └───────────────┘  └───────────────┘      │   │
│  └──────────────────────────────────────────────────────────────┘   │
│                          │                                          │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │              Controllers                                     │   │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐                    │   │
│  │  │ Auth     │  │ Event    │  │ Helpdesk  │                   │   │
│  │  │ Controller│ │ Controller│ │ Controller│                   │   │
│  │  └────┬─────┘  └────┬─────┘  └────┬─────┘                    │   │
│  └───────┼──────────────┼──────────────┼────────────────────────┘   │
│          │              │              │                            │
│  ┌───────▼──────────────▼──────────────▼───────────────────────┐    │
│  │              Policies & Gates                               │    │
│  │  ┌──────────┐  ┌──────────┐  ┌───────────┐                  │    │
│  │  │ Event    │  │ Helpdesk │  │ act-as-   │                  │    │
│  │  │ Policy   │  │ Policy   │  │ helpdesk  │                  │    │
│  │  │ - view   │  │ - view   │  │ agent     │                  │    │
│  │  │ - update │  │          │  │ gate      │                  │    │
│  │  │ - delete │  │          │  │           │                  │    │
│  │  └──────────┘  └──────────┘  └───────────┘                  │    │
│  └─────────────────────────────────────────────────────────────┘    │
│                          │                                          │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │              Models                                          │   │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐                    │   │
│  │  │ User     │  │ Event    │  │ Helpdesk  │                   │   │
│  │  │          │  │          │  │ Chat      │                   │   │
│  │  │ - email* │  │ - desc*  │  │ - content*│                   │   │
│  │  │ - mfa_*  │  │          │  │           │                   │   │
│  │  └────┬─────┘  └────┬─────┘  └────┬─────┘                    │   │
│  │       │              │              │                        │   │
│  │  ┌────▼──────────────▼──────────────▼───────────────────┐    │   │
│  │  │  EncryptsFields Trait                                │    │   │
│  │  │  - Automatic encryption on save                      │    │   │
│  │  │  - Automatic decryption on retrieve                  │    │   │
│  │  └──────────────────────────────────────────────────────┘    │   │
│  └──────────────────────────────────────────────────────────────┘   │
│                          │                                          │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │              Services                                        │   │
│  │  ┌────────────────┐ ┌──────────────┐ ┌────────────────┐      │   │
│  │  │ FieldEncryption│ │ AuditLog     │ │ SecurityMonitor│      │   │
│  │  │ Service        │ │ Service      │ │ Service        │      │   │
│  │  │ - encrypt()    │ │ - log()      │ │ - checkAlerts()│      │   │
│  │  │ - decrypt()    │ │ - logAuth()  │ │ - getStats()   │      │   │
│  │  │ - isEncrypted()│ │ - logCreate()│ │                │      │   │
│  │  └────────────────┘ └──────────────┘ └────────────────┘      │   │
│  └──────────────────────────────────────────────────────────────┘   │
│                          │                                          │
└──────────────────────────┼──────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    Database (SQLite - Encrypted)                    │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐             │
│  │ users    │  │ events   │  │ helpdesk_│  │ password_│             │
│  │          │  │          │  │ chats    │  │ reset_   │             │
│  │ - id     │  │ - id     │  │ - id     │  │ tokens   │             │
│  │ - email* │  │ - title  │  │ - user_id│  │ - email  │             │
│  │ - password│ │ - occurs_│  │ - status │  │ - token  │             │
│  │ - role   │  │   at     │  │          │  │ - created│             │
│  │ - mfa_*  │  │ - desc*  │  │          │  │   _at    │             │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘             │
│                                                                     │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐                           │
│  │ helpdesk_│  │ audit_   │  │ personal_│                           │
│  │ messages │  │ logs     │  │ access_  │                           │
│  │          │  │          │  │ tokens   │                           │
│  │ - id     │  │ - id     │  │ - id     │                           │
│  │ - chat_id│  │ - action │  │ - token  │                           │
│  │ - sender_│  │ - user_id│  │ - name   │                           │
│  │   type   │  │ - resource│ │ - abilities│                         │
│  │ - content*│ │ - metadata│ │          │                           │
│  └──────────┘  └──────────┘  └──────────┘                           │
│                                                                     │
│  * = Encrypted Fields (AES-256-CBC)                                 │
└─────────────────────────────────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────────┐
│              External Services                                      │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │  Google Gemini API (NLP for Helpdesk Bot)                    │   │
│  │  Endpoint: https://generativelanguage.googleapis.com/        │   │
│  │  Model: gemini-2.5-flash                                     │   │
│  │  Authentication: API Key (Bearer)                            │   │
│  └──────────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 7. Security Flow (Request Processing)

```
HTTP Request
  │
  ▼
┌─────────────────┐
│  ForceHttps     │──▶ Check HTTPS
│  Middleware     │──▶ Reject HTTP (production)
│                 │──▶ Add Security Headers:
│                 │   - HSTS
│                 │   - X-Content-Type-Options
│                 │   - X-Frame-Options
│                 │   - X-XSS-Protection
│                 │   - Referrer-Policy
└────┬────────────┘
     │
     ▼
┌─────────────────┐
│  ThrottleAuth   │──▶ Check Rate Limit
│  Middleware     │──▶ IP + Email + Path
│  (if auth route)│──▶ 5 attempts / 15 minutes
│                 │──▶ Log violations
└────┬────────────┘
     │
     ▼
┌─────────────────┐
│  Sanctum Auth   │──▶ Extract Bearer Token
│  Middleware     │──▶ Verify Token Hash
│  (if protected) │──▶ Load User from DB
│                 │──▶ Set auth()->user()
└────┬────────────┘
     │
     ▼
┌─────────────────┐
│  Role Check     │──▶ Check User Role
│  (if needed)    │──▶ Verify Gate/Policy
│                 │──▶ Log Access Denied
└────┬────────────┘
     │
     ▼
┌─────────────────┐
│  Controller     │──▶ Validate Input
│                 │──▶ Process Request
└────┬────────────┘
     │
     ▼
┌─────────────────┐
│  Policy Check   │──▶ Authorize Action
│  (if needed)    │──▶ EventPolicy::update()
│                 │──▶ HelpdeskChatPolicy::view()
└────┬────────────┘
     │
     ▼
┌─────────────────┐
│  Model          │──▶ Encrypt Fields
│  Operation      │──▶ Save to DB
└────┬────────────┘
     │
     ▼
┌─────────────────┐
│  Audit Log      │──▶ Log Action
│                 │──▶ Store in audit_logs
└────┬────────────┘
     │
     ▼
HTTP Response
```

---

## 8. MFA Setup Flow

```
┌─────────┐
│  User   │
└────┬────┘
     │
     │ 1. POST /api/auth/mfa/setup
     ▼
┌─────────────────┐
│  AuthController │
│  .setupMfa()    │
└────┬────────────┘
     │
     │ 2. Check if Secret Exists
     ▼
┌─────────────────┐
│  User Model     │
│  mfa_secret?    │
└────┬────────────┘
     │
     │ 3. Generate Secret
     ▼
┌─────────────────────┐
│  PHPGangsta_        │
│  GoogleAuthenticator│
│  .createSecret()    │
└────┬────────────────┘
     │
     │ 4. Encrypt Secret
     ▼
┌──────────────────┐
│  User Model      │──────▶ EncryptsFields (encrypts mfa_secret)
│  mfa_secret = ...│
└────┬─────────────┘
     │
     │ 5. Save to Database
     ▼
┌─────────────────┐
│  users table    │
│  mfa_secret*    │ (* = encrypted)
└────┬────────────┘
     │
     │ 6. Generate QR Code URL
     ▼
┌─────────────────┐
│  otpauth://     │
│  totp/App:email?│
│  secret=...&    │
│  issuer=App     │
└────┬────────────┘
     │
     │ 7. Generate QR Code Image
     ▼
┌─────────────────┐
│  Google Charts  │
│  API            │
│  QR Code        │
└────┬────────────┘
     │
     │ 8. Return Response
     ▼
┌─────────────────┐
│  {secret,       │
│   qr_code_url,  │
│   user}         │
└────┬────────────┘
     │
     │ 9. Display QR Code
     ▼
┌─────────────────┐
│  Frontend       │──────▶ Show QR code, secret, input field
│  (React)        │
└─────────────────┘
     │
     │ 10. User Scans & Enters Code
     │     POST /api/auth/mfa/confirm
     │     {code: "123456"}
     ▼
┌────────────────────┐
│  AuthController    │
│  .confirmMfaSetup()│
└────┬───────────────┘
     │
     │ 11. Verify Code
     ▼
┌─────────────────────┐
│  PHPGangsta_        │
│  GoogleAuthenticator│
│  .verifyCode()      │
│  (secret, code, 2)  │
└────┬────────────────┘
     │
     │ 12. Enable MFA
     ▼
┌────────────────────┐
│  User Model        │
│  mfa_enabled = true│
│  .save()           │
└────┬───────────────┘
     │
     │ 13. Audit Log
     ▼
┌─────────────────┐
│  AuditLogService│
│  .logAuth()     │
│  mfa_enabled    │
└────┬────────────┘
     │
     │ 14. Success
     ▼
┌─────────────────┐
│  Frontend       │──────▶ Show success, hide QR code
│  (React)        │
└─────────────────┘
```

---

## 9. Audit Logging Flow

```
Any Action
  │
  ▼
┌──────────────────┐
│  Controller      │
│  or Model Event  │
└────┬─────────────┘
     │
     │ 1. Call Audit Service
     ▼
┌─────────────────┐
│  AuditLogService│
│  .log()         │
│  or .logAuth()  │
│  or .logCreate()│
└────┬────────────┘
     │
     │ 2. Build Log Data
     ▼
┌───────────────────────────┐
│  {                        │
│    action: "create"       │
│    user_id: 1             │
│    user_email: "..."      │
│    resource_type: "events"│
│    resource_id: 1         │
│    ip_address: "127.0.0.1"│
│    user_agent: "..."      │
│    metadata: {...}        │
│  }                        │
└────┬──────────────────────┘
     │
     │ 3. Log to File
     ▼
┌─────────────────┐
│  Log::channel(  │
│  'security')    │
│  .info()        │
└────┬────────────┘
     │
     │ 4. Store in Database
     ▼
┌─────────────────┐
│  audit_logs     │
│  table          │
│  INSERT         │
└────┬────────────┘
     │
     │ 5. Indexes for Fast Queries
     ▼
┌─────────────────┐
│  Indexes:       │
│  - user_id      │
│  - action       │
│  - resource_type│
│  - created_at   │
└─────────────────┘
```

---

## 10. Rate Limiting Flow

```
Request to Auth Endpoint
  │
  ▼
┌─────────────────┐
│  ThrottleAuth   │
│  Middleware     │
└────┬────────────┘
     │
     │ 1. Generate Key
     ▼
┌─────────────────────┐
│  resolveRequest     │
│  Signature()        │
│  sha1(IP|email|path)│
└────┬────────────────┘
     │
     │ 2. Check Rate Limit
     ▼
┌────────────────────┐
│  RateLimiter       │
│  .tooManyAttempts()│
└────┬───────────────┘
     │
     │ 3. Query Cache
     ▼
┌─────────────────────┐
│  Cache Store        │
│  (Redis/File)       │
│  Key: "throttle:..."│
└────┬────────────────┘
     │
     │ 4. Check Attempts
     │    >= 5? → Block
     │    < 5? → Allow
     ▼
┌────────────────────────┐
│  If Blocked:           │
│  - Calculate retry time│
│  - Log violation       │
│  - Return 429          │
│                        │
│  If Allowed:           │
│  - Increment counter   │
│  - Continue            │
└────┬───────────────────┘
     │
     ▼
Continue to Controller
```

---

## 11. Error Handling Flow

```
API Request
  │
  ▼
┌─────────────────┐
│  Fetch API      │
│  Request        │
└────┬────────────┘
     │
     │ Response received
     ▼
┌─────────────────┐
│  Check Status   │
│  res.ok?        │
└────┬────────────┘
     │
     ├─► If OK (200-299)
     │   └─► Parse JSON
     │   └─► Return data
     │
     └─► If Error (!res.ok)
         │
         ▼
┌─────────────────┐
│  Read Response  │
│  as Text        │
└────┬────────────┘
     │
     ▼
┌───────────────────────┐
│  extractErrorMessage()│
│  (text, status)       │
└────┬──────────────────┘
     │
     │ 1. Check if HTML
     ▼
┌─────────────────┐
│  Starts with    │
│  <!DOCTYPE or   │
│  <html?         │
└────┬────────────┘
     │
     ├─► If HTML:
     │   └─► Return: "An error occurred. Please try again later."
     │
     └─► If not HTML:
         │
         ▼
┌─────────────────┐
│  Try Parse JSON │
└────┬────────────┘
     │
     ├─► If JSON:
     │   ├─► Check json.message
     │   ├─► Check json.errors (validation)
     │   └─► Extract first error message
     │
     └─► If not JSON:
         │
         ▼
┌─────────────────┐
│  Check Status   │
│  Code           │
└────┬────────────┘
     │
     ├─► 401 → "Authentication required..."
     ├─► 403 → "You do not have permission..."
     ├─► 404 → "The requested resource..."
     ├─► 422 → "Validation failed..."
     ├─► 429 → "Too many requests..."
     ├─► 500/502/503 → "Server error..."
     └─► Default → "An error occurred..."
     │
     ▼
┌───────────────────┐
│  Create ApiError  │
│  (message, status)│
└────┬──────────────┘
     │
     ▼
┌─────────────────┐
│  Throw Error    │
│  (caught by     │
│   component)    │
└────┬────────────┘
     │
     ▼
┌─────────────────┐
│  Component      │
│  Error Handler  │
└────┬────────────┘
     │
     ▼
┌─────────────────┐
│  Display Clean  │
│  Error Message  │
│  to User        │
└─────────────────┘
```

---

## Data Flow Summary

### Request Flow
1. **Frontend** → HTTP Request → **Backend API**
2. **Middleware Stack** → Security checks → **Controller**
3. **Controller** → Validation → **Policy/Gate**
4. **Policy** → Authorization → **Model**
5. **Model** → Encryption → **Database**
6. **Database** → Storage → **Response**
7. **Response** → Decryption → **Frontend**

### Encryption Points
- **Input:** User enters plaintext
- **Model Save:** EncryptsFields trait encrypts
- **Database:** Stores encrypted value
- **Model Retrieve:** EncryptsFields trait decrypts
- **Output:** Frontend receives plaintext

### Security Layers
1. **Transport:** HTTPS/TLS
2. **Rate Limiting:** ThrottleAuth middleware
3. **Authentication:** Sanctum tokens
4. **Authorization:** Policies & Gates
5. **Data Protection:** Field-level encryption
6. **Audit:** All actions logged

