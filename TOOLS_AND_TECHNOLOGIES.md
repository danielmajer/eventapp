# Tools and Technologies

This document describes all the tools, frameworks, libraries, and technologies used in the Event Management System.

---

## Frontend Tools

### Core Framework
- **React 18**
  - **Purpose:** JavaScript library for building user interfaces
  - **Usage:** Main frontend framework for all UI components
  - **Features Used:**
    - Functional components with hooks
    - `useState` for local state management
    - `useEffect` for side effects and lifecycle
    - `useRef` for DOM references
    - Context API (via localStorage for auth persistence)

### Build Tool
- **Vite**
  - **Purpose:** Next-generation frontend build tool
  - **Usage:** Development server and production builds
  - **Benefits:**
    - Fast HMR (Hot Module Replacement)
    - Optimized production builds
    - ES modules support
    - TypeScript support

### Routing
- **React Router DOM v6**
  - **Purpose:** Client-side routing for single-page applications
  - **Usage:** Navigation between pages (Login, Events, Chat, Preferences, Helpdesk)
  - **Components Used:**
    - `BrowserRouter` - Router provider
    - `Routes` - Route container
    - `Route` - Individual route definitions
    - `Navigate` - Programmatic navigation
    - `useNavigate` - Navigation hook

### Styling
- **Tailwind CSS**
  - **Purpose:** Utility-first CSS framework
  - **Usage:** All component styling
  - **Features Used:**
    - Utility classes for spacing, colors, typography
    - Responsive design utilities
    - Hover and focus states
    - Custom color palette (blue, purple, slate, red, green)
  - **Configuration:** `tailwind.config.js`

### Icons
- **Lucide React**
  - **Purpose:** Beautiful, customizable icon library
  - **Usage:** Icons throughout the UI
  - **Icons Used:**
    - `Calendar` - Events icon
    - `MessageSquare` - Chat/Helpdesk icon
    - `Settings` - Preferences icon
    - `User` - User profile icon
    - `LogOut` - Logout button
    - `Plus` - Create/add actions
    - `Edit2` - Edit actions
    - `Trash2` - Delete actions
    - `Save` - Save actions
    - `Send` - Send message
    - `Bot` - Bot messages
    - `Clock` - Time display
    - `CheckCircle` - Success states
    - `Loader2` - Loading spinners
    - `Filter` - Filter icon for helpdesk
    - `XCircle` - Close/delete actions
    - `Headphones` - Helpdesk agent icon

### QR Code Generation
- **qrcode.react**
  - **Purpose:** Generate QR codes in React
  - **Usage:** Display QR codes for MFA setup
  - **Features:**
    - SVG-based QR codes
    - Customizable size and styling
    - Error correction levels

### HTTP Client
- **Fetch API (Native)**
  - **Purpose:** Make HTTP requests to backend API
  - **Usage:** Custom `apiRequest` wrapper function
  - **Features:**
    - Bearer token authentication
    - Intelligent error handling
    - JSON request/response handling
    - TypeScript type safety
    - Error message extraction
  - **Error Handling:**
    - Extracts text messages from JSON error responses
    - Detects HTML error pages and returns generic message
    - Handles Laravel validation errors (extracts first error)
    - Provides status code-based fallback messages
    - Never displays raw JSON or HTML to users
    - Custom `ApiError` class with status code support

### State Management
- **localStorage API**
  - **Purpose:** Browser storage for persistence
  - **Usage:** Store authentication token and user data
  - **Data Stored:**
    - `auth_token` - Sanctum API token
    - `auth_user` - User object (JSON stringified)

### Type Safety
- **TypeScript**
  - **Purpose:** Static type checking for JavaScript
  - **Usage:** Type definitions for all components and API responses
  - **Types Defined:**
    - `User` - User interface
    - `Event` - Event interface
    - `Message` - Chat message interface
    - `Chat` - Chat interface
    - Component props interfaces

---

## Backend Tools

### Core Framework
- **Laravel 12**
  - **Purpose:** PHP web application framework
  - **Usage:** Backend API server
  - **Features Used:**
    - Eloquent ORM
    - Routing system
    - Middleware
    - Service providers
    - Artisan commands
    - Migrations
    - Seeders
    - Policies and Gates
    - Events and Listeners

### Authentication
- **Laravel Sanctum**
  - **Purpose:** Token-based API authentication
  - **Usage:** Secure API endpoints
  - **Features:**
    - Personal access tokens
    - Token expiration
    - Token revocation
    - Per-device tokens
  - **Storage:** `personal_access_tokens` table

### Database
- **SQLite**
  - **Purpose:** Lightweight relational database
  - **Usage:** Primary data storage
  - **Location:** `database/database.sqlite`
  - **Features:**
    - File-based database
    - No server required
    - ACID compliant
    - Supports transactions

### ORM
- **Eloquent ORM**
  - **Purpose:** Active Record implementation for Laravel
  - **Usage:** Database operations
  - **Features Used:**
    - Model relationships
    - Query builder
    - Mass assignment protection
    - Model events (created, updated, deleted)
    - Accessors and mutators
    - Scopes

### Password Hashing
- **bcrypt/Argon2**
  - **Purpose:** Secure password hashing
  - **Usage:** Hash user passwords
  - **Implementation:** Laravel's `Hash` facade
  - **Methods:**
    - `Hash::make()` - Hash password
    - `Hash::check()` - Verify password

### Multi-Factor Authentication
- **PHPGangsta GoogleAuthenticator**
  - **Purpose:** TOTP (Time-based One-Time Password) implementation
  - **Usage:** Two-factor authentication
  - **Package:** `phpgangsta/googleauthenticator:dev-master`
  - **Features:**
    - Generate MFA secrets
    - Verify TOTP codes
    - QR code URL generation
  - **Methods:**
    - `createSecret()` - Generate secret key
    - `verifyCode()` - Verify 6-digit code
    - `getQRCodeGoogleUrl()` - Generate QR code URL

### HTTP Client
- **Guzzle HTTP Client**
  - **Purpose:** HTTP client for making external API requests
  - **Usage:** Call Google Gemini API
  - **Implementation:** Laravel's `Http` facade
  - **Features:**
    - JSON request/response handling
    - Bearer token authentication
    - Error handling
    - Timeout configuration

### Encryption
- **Laravel Encrypter**
  - **Purpose:** AES-256-CBC encryption
  - **Usage:** Field-level encryption
  - **Implementation:** Custom `FieldEncryptionService`
  - **Features:**
    - Encrypt sensitive fields (email, description, content, mfa_secret)
    - Automatic encryption on save
    - Automatic decryption on retrieve
    - Key management via `DB_FIELD_ENCRYPTION_KEY`

### Logging
- **Laravel Logging System**
  - **Purpose:** Application logging
  - **Usage:** Security and audit logs
  - **Channels:**
    - `security` - Security-specific logs
    - `local` - General application logs
  - **Storage:** `storage/logs/security.log` and `storage/logs/laravel.log`
  - **Features:**
    - Daily log rotation
    - 90-day retention
    - Structured logging

### Rate Limiting
- **Laravel Rate Limiter**
  - **Purpose:** Prevent brute force attacks
  - **Usage:** Custom `ThrottleAuth` middleware
  - **Features:**
    - IP-based rate limiting
    - Email-based rate limiting
    - Configurable attempts and time windows
    - Cache-based storage
  - **Configuration:** 5 attempts per 15 minutes for auth endpoints

### Validation
- **Laravel Validation**
  - **Purpose:** Request validation
  - **Usage:** Validate all API inputs
  - **Rules Used:**
    - `required` - Field must be present
    - `email` - Valid email format
    - `string` - String type
    - `min:8` - Minimum length
    - `confirmed` - Password confirmation
    - `max:255` - Maximum length
    - `nullable` - Optional field

### Caching
- **Laravel Cache**
  - **Purpose:** Application caching
  - **Usage:** Rate limiting, configuration caching
  - **Driver:** File-based (configurable to Redis, etc.)
  - **Storage:** `storage/framework/cache`

---

## External Services

### AI/NLP Service
- **Google Gemini API**
  - **Purpose:** Natural language processing for helpdesk bot
  - **Usage:** Generate intelligent responses to user queries
  - **Endpoint:** `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent`
  - **Authentication:** API Key (Bearer token)
  - **Model:** `gemini-2.5-flash`
  - **Features:**
    - Conversational AI
    - Context-aware responses
    - System prompts for bot behavior
    - JSON request/response format

### QR Code Service
- **Google Charts API**
  - **Purpose:** Generate QR code images
  - **Usage:** Display QR codes for MFA setup
  - **Endpoint:** `https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=...`
  - **Format:** PNG image
  - **Size:** 200x200 pixels

---

## Development Tools

### Package Managers
- **npm (Node Package Manager)**
  - **Purpose:** Manage frontend dependencies
  - **Usage:** Install and manage React packages
  - **Configuration:** `package.json`
  - **Commands:**
    - `npm install` - Install dependencies
    - `npm run dev` - Start development server
    - `npm run build` - Build for production

- **Composer**
  - **Purpose:** PHP dependency manager
  - **Usage:** Manage Laravel and PHP packages
  - **Configuration:** `composer.json`
  - **Commands:**
    - `composer install` - Install dependencies
    - `composer require` - Add new package
    - `composer update` - Update packages

### Code Quality
- **ESLint** (Frontend)
  - **Purpose:** JavaScript/TypeScript linting
  - **Usage:** Code quality and consistency
  - **Configuration:** `.eslintrc` or `eslint.config.js`

- **PHP CS Fixer** (Backend)
  - **Purpose:** PHP code style fixing
  - **Usage:** Enforce PSR-12 coding standards

### Version Control
- **Git**
  - **Purpose:** Version control system
  - **Usage:** Track code changes
  - **Repository:** Local Git repository

### Development Servers
- **Vite Dev Server**
  - **Purpose:** Frontend development server
  - **Port:** 5173 (default)
  - **Features:**
    - Hot Module Replacement (HMR)
    - Fast refresh
    - Source maps

- **PHP Built-in Server**
  - **Purpose:** Backend development server
  - **Command:** `php artisan serve`
  - **Port:** 8000 (default)
  - **Features:**
    - No additional server required
    - Suitable for development

---

## Security Tools

### TLS/HTTPS
- **ForceHttps Middleware**
  - **Purpose:** Enforce HTTPS connections
  - **Usage:** Redirect HTTP to HTTPS in production
  - **Implementation:** Custom middleware
  - **Features:**
    - Automatic HTTP to HTTPS redirect
    - Security headers injection

### Security Headers
- **HSTS (HTTP Strict Transport Security)**
  - **Purpose:** Force HTTPS connections
  - **Header:** `Strict-Transport-Security: max-age=31536000; includeSubDomains`

- **X-Content-Type-Options**
  - **Purpose:** Prevent MIME type sniffing
  - **Header:** `X-Content-Type-Options: nosniff`

- **X-Frame-Options**
  - **Purpose:** Prevent clickjacking
  - **Header:** `X-Frame-Options: DENY`

- **X-XSS-Protection**
  - **Purpose:** Enable XSS filter
  - **Header:** `X-XSS-Protection: 1; mode=block`

- **Referrer-Policy**
  - **Purpose:** Control referrer information
  - **Header:** `Referrer-Policy: strict-origin-when-cross-origin`

### Audit Logging
- **Custom AuditLogService**
  - **Purpose:** Track all security-relevant actions
  - **Usage:** Log user actions, authentication events, access denials
  - **Storage:**
    - Database: `audit_logs` table
    - File: `storage/logs/security.log`
  - **Events Logged:**
    - Login success/failure
    - MFA enable/disable/verification
    - Password reset requests/completions
    - Event create/update/delete
    - Access denied attempts
    - Rate limit violations

### Security Monitoring
- **SecurityMonitoringService**
  - **Purpose:** Detect suspicious patterns
  - **Usage:** Analyze audit logs for security threats
  - **Patterns Detected:**
    - Multiple failed login attempts
    - Unusual access patterns
    - Access denied attempts
  - **Command:** `php artisan security:monitor`

---

## Database Tools

### Migration System
- **Laravel Migrations**
  - **Purpose:** Version control for database schema
  - **Usage:** Create and modify database tables
  - **Commands:**
    - `php artisan migrate` - Run migrations
    - `php artisan migrate:rollback` - Rollback migrations
    - `php artisan migrate:fresh` - Drop all tables and re-run migrations
  - **Files:** `database/migrations/*.php`

### Seeding
- **Laravel Seeders**
  - **Purpose:** Populate database with initial data
  - **Usage:** Create test users, sample data
  - **Commands:**
    - `php artisan db:seed` - Run all seeders
    - `php artisan db:seed --class=ClassName` - Run specific seeder
  - **Files:** `database/seeders/*.php`

### Database Management
- **SQLite CLI**
  - **Purpose:** Command-line interface for SQLite
  - **Usage:** Direct database queries and management
  - **Command:** `sqlite3 database/database.sqlite`

---

## Artisan Commands

### Custom Commands
- **Security Monitor**
  - **Command:** `php artisan security:monitor`
  - **Purpose:** Run security monitoring checks
  - **Usage:** Analyze audit logs for suspicious activity

### Built-in Commands
- **Route List**
  - **Command:** `php artisan route:list`
  - **Purpose:** Display all registered routes

- **Config Clear**
  - **Command:** `php artisan config:clear`
  - **Purpose:** Clear configuration cache

- **Cache Clear**
  - **Command:** `php artisan cache:clear`
  - **Purpose:** Clear application cache

- **Optimize**
  - **Command:** `php artisan optimize`
  - **Purpose:** Optimize application for production

- **Tinker**
  - **Command:** `php artisan tinker`
  - **Purpose:** Interactive REPL for Laravel

---

## Testing Tools

### Frontend Testing
- **Vitest** (Potential)
  - **Purpose:** Unit testing for Vite projects
  - **Usage:** Test React components and utilities

### Backend Testing
- **PHPUnit** (Laravel)
  - **Purpose:** PHP unit testing framework
  - **Usage:** Test Laravel controllers, models, services
  - **Configuration:** `phpunit.xml`

---

## Deployment Tools

### Process Managers
- **PM2** (Potential)
  - **Purpose:** Process manager for Node.js applications
  - **Usage:** Keep frontend server running in production

- **Supervisor** (Potential)
  - **Purpose:** Process control system
  - **Usage:** Manage PHP workers and queues

### Web Servers
- **Nginx** (Recommended)
  - **Purpose:** Reverse proxy and web server
  - **Usage:** Serve static files, proxy API requests
  - **Features:**
    - SSL/TLS termination
    - Load balancing
    - Static file serving

- **Apache** (Alternative)
  - **Purpose:** Web server
  - **Usage:** Alternative to Nginx

### PHP-FPM
- **PHP-FPM**
  - **Purpose:** FastCGI Process Manager for PHP
  - **Usage:** Handle PHP requests in production
  - **Features:**
    - Process pooling
    - Performance optimization
    - Resource management

---

## Monitoring and Debugging

### Logging
- **Laravel Log Channels**
  - **Channels:**
    - `local` - General application logs
    - `security` - Security-specific logs
  - **Storage:** `storage/logs/`
  - **Rotation:** Daily with 90-day retention

### Error Tracking
- **Laravel Exception Handler**
  - **Purpose:** Handle and log exceptions
  - **Usage:** Custom exception handling
  - **Storage:** Log files and error responses

### Debugging
- **Laravel Debugbar** (Development)
  - **Purpose:** Debug toolbar for Laravel
  - **Usage:** Inspect queries, requests, responses

- **Browser DevTools**
  - **Purpose:** Frontend debugging
  - **Usage:** Inspect network requests, console logs, React components

---

## Environment Configuration

### Configuration Files
- **`.env`** (Backend)
  - **Purpose:** Environment variables
  - **Variables:**
    - `APP_KEY` - Application encryption key
    - `DB_CONNECTION` - Database connection type
    - `DB_DATABASE` - Database path
    - `DB_FIELD_ENCRYPTION_KEY` - Field encryption key
    - `GEMINI_API_KEY` - Google Gemini API key
    - `FRONTEND_URL` - Frontend application URL

- **`.env`** (Frontend)
  - **Purpose:** Frontend environment variables
  - **Variables:**
    - `VITE_API_URL` - Backend API URL

### Configuration Management
- **Laravel Config**
  - **Purpose:** Application configuration
  - **Files:** `config/*.php`
  - **Caching:** `php artisan config:cache`

---

## Summary

### Frontend Stack
- **Framework:** React 18 + TypeScript
- **Build Tool:** Vite
- **Routing:** React Router DOM
- **Styling:** Tailwind CSS
- **Icons:** Lucide React
- **State:** React Hooks + localStorage

### Backend Stack
- **Framework:** Laravel 12
- **Language:** PHP 8.4+
- **Database:** SQLite
- **ORM:** Eloquent
- **Auth:** Laravel Sanctum
- **MFA:** PHPGangsta GoogleAuthenticator

### Security Stack
- **Encryption:** Laravel Encrypter (AES-256-CBC)
- **Hashing:** bcrypt/Argon2
- **Rate Limiting:** Custom middleware
- **Audit Logging:** Custom service
- **Security Headers:** Custom middleware

### External Services
- **AI/NLP:** Google Gemini API
- **QR Codes:** Google Charts API

### Development Tools
- **Package Managers:** npm, Composer
- **Version Control:** Git
- **Servers:** Vite Dev Server, PHP Built-in Server

This comprehensive toolset provides a modern, secure, and maintainable application architecture.

