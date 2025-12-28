# Sources and References

This document lists all sources, references, documentation, standards, and specifications used in the development of the Event Management System.

---

## Official Documentation

### Laravel Framework
- **Laravel Documentation**
  - URL: https://laravel.com/docs
  - Version: Laravel 12
  - Sections Used:
    - Authentication (Sanctum)
    - Eloquent ORM
    - Migrations
    - Middleware
    - Policies and Gates
    - Service Providers
    - Artisan Commands
    - Validation
    - HTTP Client
    - Logging

- **Laravel Sanctum Documentation**
  - URL: https://laravel.com/docs/sanctum
  - Usage: Token-based API authentication
  - Features: Personal access tokens, token expiration

- **Laravel Security Documentation**
  - URL: https://laravel.com/docs/security
  - Topics: Password hashing, encryption, CSRF protection

### React
- **React Documentation**
  - URL: https://react.dev
  - Version: React 18
  - Sections Used:
    - Hooks (useState, useEffect, useRef)
    - Functional Components
    - Event Handling
    - Conditional Rendering

- **React Router Documentation**
  - URL: https://reactrouter.com
  - Version: React Router DOM v6
  - Features: BrowserRouter, Routes, Route, Navigate, useNavigate

### Vite
- **Vite Documentation**
  - URL: https://vitejs.dev
  - Usage: Build tool and development server
  - Features: HMR, TypeScript support, plugin system

### Tailwind CSS
- **Tailwind CSS Documentation**
  - URL: https://tailwindcss.com/docs
  - Usage: Utility-first CSS framework
  - Features: Responsive design, custom colors, spacing utilities

### TypeScript
- **TypeScript Documentation**
  - URL: https://www.typescriptlang.org/docs
  - Usage: Type safety for JavaScript
  - Features: Interfaces, type inference, type checking

---

## Third-Party Libraries

### PHP Libraries

#### PHPGangsta GoogleAuthenticator
- **Package:** `phpgangsta/googleauthenticator`
- **Repository:** https://github.com/PHPGangsta/GoogleAuthenticator
- **Documentation:** https://github.com/PHPGangsta/GoogleAuthenticator
- **Usage:** TOTP (Time-based One-Time Password) for MFA
- **License:** BSD-3-Clause
- **Methods Used:**
  - `createSecret()` - Generate MFA secret
  - `verifyCode()` - Verify TOTP code
  - `getQRCodeGoogleUrl()` - Generate QR code URL

### JavaScript/TypeScript Libraries

#### Lucide React
- **Package:** `lucide-react`
- **Repository:** https://github.com/lucide-icons/lucide
- **Documentation:** https://lucide.dev
- **Usage:** Icon library for React
- **License:** ISC

#### qrcode.react
- **Package:** `qrcode.react`
- **Repository:** https://github.com/zpao/qrcode.react
- **Documentation:** https://github.com/zpao/qrcode.react
- **Usage:** QR code generation in React
- **License:** ISC

---

## External APIs

### Google Gemini API
- **Service:** Google Generative AI
- **Documentation:** https://ai.google.dev/docs
- **API Reference:** https://ai.google.dev/api
- **Endpoint:** `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent`
- **Authentication:** API Key (Bearer token)
- **Model Used:** `gemini-2.5-flash`
- **Usage:** Natural language processing for helpdesk bot
- **Request Format:** JSON with system prompts and conversation history
- **Response Format:** JSON with generated text

### Google Charts API
- **Service:** Google Charts
- **Documentation:** https://developers.google.com/chart
- **QR Code API:** https://developers.google.com/chart/infographics/docs/qr_codes
- **Endpoint:** `https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=...`
- **Usage:** Generate QR code images for MFA setup
- **Format:** PNG image
- **Parameters:**
  - `chs` - Chart size (200x200)
  - `cht` - Chart type (qr)
  - `chl` - Data to encode (otpauth:// URL)

---

## Standards and Specifications

### OWASP Top 10
- **Source:** Open Web Application Security Project
- **URL:** https://owasp.org/www-project-top-ten
- **Usage:** Security best practices and threat modeling
- **Categories Addressed:**
  - A01: Broken Access Control
  - A02: Cryptographic Failures
  - A03: Injection
  - A04: Insecure Design
  - A05: Security Misconfiguration
  - A06: Vulnerable Components
  - A07: Authentication Failures
  - A08: Software and Data Integrity Failures
  - A09: Security Logging Failures
  - A10: Server-Side Request Forgery

### RFC Standards

#### RFC 6238 - TOTP (Time-based One-Time Password)
- **Title:** TOTP: Time-Based One-Time Password Algorithm
- **URL:** https://datatracker.ietf.org/doc/html/rfc6238
- **Usage:** MFA implementation standard
- **Features:** 6-digit codes, 30-second time windows

#### RFC 4226 - HOTP (HMAC-Based One-Time Password)
- **Title:** HOTP: An HMAC-Based One-Time Password Algorithm
- **URL:** https://datatracker.ietf.org/doc/html/rfc4226
- **Usage:** Base algorithm for TOTP

#### RFC 7519 - JWT (JSON Web Token)
- **Title:** JSON Web Token (JWT)
- **URL:** https://datatracker.ietf.org/doc/html/rfc7519
- **Note:** Not directly used, but similar token-based auth principles applied

### OAuth 2.0
- **Specification:** RFC 6749
- **URL:** https://datatracker.ietf.org/doc/html/rfc6749
- **Usage:** Authentication pattern reference (Sanctum uses similar principles)

### TLS/HTTPS
- **Specification:** TLS 1.2/1.3
- **Usage:** Secure transport layer
- **Enforcement:** ForceHttps middleware

### Security Headers
- **HSTS:** RFC 6797
  - URL: https://datatracker.ietf.org/doc/html/rfc6797
- **X-Frame-Options:** Non-standard but widely supported
- **X-Content-Type-Options:** Non-standard but widely supported
- **CSP (Content Security Policy):** W3C Specification
  - URL: https://www.w3.org/TR/CSP

---

## Database Standards

### SQL
- **Standard:** ANSI SQL
- **Usage:** Database queries and schema definitions
- **Features:** ACID compliance, transactions

### SQLite
- **Documentation:** https://www.sqlite.org/docs.html
- **SQL Syntax:** https://www.sqlite.org/lang.html
- **Usage:** Database engine and SQL syntax

---

## Security Best Practices

### Password Security
- **OWASP Password Storage Cheat Sheet**
  - URL: https://cheatsheetseries.owasp.org/cheatsheets/Password_Storage_Cheat_Sheet.html
  - Usage: bcrypt/Argon2 hashing, salt usage

### Encryption
- **AES-256-CBC**
  - Standard: FIPS 197 (Advanced Encryption Standard)
  - Usage: Field-level encryption
  - Implementation: Laravel Encrypter

### Rate Limiting
- **OWASP API Security Top 10**
  - URL: https://owasp.org/www-project-api-security
  - Usage: Rate limiting patterns and best practices

### Audit Logging
- **NIST SP 800-92**
  - Title: Guide to Computer Security Log Management
  - Usage: Audit logging best practices
  - URL: https://csrc.nist.gov/publications/detail/sp/800-92/final

---

## Design Patterns

### MVC (Model-View-Controller)
- **Source:** General software architecture pattern
- **Usage:** Laravel framework structure
- **Components:**
  - Models: Eloquent models
  - Views: React components (frontend)
  - Controllers: Laravel controllers

### Repository Pattern
- **Usage:** Eloquent ORM implements repository-like pattern
- **Benefits:** Data access abstraction

### Service Layer Pattern
- **Usage:** Custom services (AuditLogService, FieldEncryptionService, SecurityMonitoringService)
- **Benefits:** Business logic separation

### Middleware Pattern
- **Usage:** Laravel middleware stack
- **Examples:** ForceHttps, ThrottleAuth, Sanctum authentication

### Policy Pattern
- **Usage:** Laravel Policies (EventPolicy, HelpdeskChatPolicy)
- **Benefits:** Authorization logic encapsulation

### Trait Pattern
- **Usage:** EncryptsFields trait, Auditable trait
- **Benefits:** Code reuse and composition

---

## Code Style and Standards

### PHP Standards
- **PSR-12:** Extended Coding Style Guide
  - URL: https://www.php-fig.org/psr/psr-12
  - Usage: PHP code formatting

- **PSR-4:** Autoloading Standard
  - URL: https://www.php-fig.org/psr/psr-4
  - Usage: Namespace and class autoloading

### JavaScript/TypeScript Standards
- **ESLint:** JavaScript linting
  - URL: https://eslint.org
  - Usage: Code quality and consistency

- **TypeScript Style Guide**
  - URL: https://www.typescriptlang.org/docs/handbook/declaration-files/do-s-and-don-ts.html
  - Usage: TypeScript best practices

### CSS Standards
- **Tailwind CSS Best Practices**
  - URL: https://tailwindcss.com/docs/reusing-styles
  - Usage: Utility-first CSS approach

---

## Tutorials and Guides

### Laravel Tutorials
- **Laravel Official Tutorials**
  - URL: https://laravel.com/docs
  - Topics: Authentication, API development, database migrations

### React Tutorials
- **React Official Tutorial**
  - URL: https://react.dev/learn
  - Topics: Components, hooks, state management

### Security Guides
- **OWASP Web Application Security Testing Guide**
  - URL: https://owasp.org/www-project-web-security-testing-guide
  - Usage: Security testing and implementation

---

## Community Resources

### Stack Overflow
- **URL:** https://stackoverflow.com
- **Usage:** Problem-solving and code examples
- **Tags:** laravel, react, php, typescript, tailwindcss

### GitHub
- **Repositories Referenced:**
  - Laravel Framework: https://github.com/laravel/laravel
  - React: https://github.com/facebook/react
  - PHPGangsta GoogleAuthenticator: https://github.com/PHPGangsta/GoogleAuthenticator
  - Lucide Icons: https://github.com/lucide-icons/lucide

### Laravel Community
- **Laravel News:** https://laravel-news.com
- **Laracasts:** https://laracasts.com (educational resource)

### React Community
- **React Blog:** https://react.dev/blog
- **React GitHub Discussions:** https://github.com/facebook/react/discussions

---

## Tools Documentation

### Composer
- **Documentation:** https://getcomposer.org/doc
- **Usage:** PHP dependency management
- **Commands:** install, require, update

### npm
- **Documentation:** https://docs.npmjs.com
- **Usage:** JavaScript package management
- **Commands:** install, run, build

### Git
- **Documentation:** https://git-scm.com/doc
- **Usage:** Version control
- **Commands:** commit, push, pull, branch

### Vite
- **Documentation:** https://vitejs.dev/guide
- **Usage:** Build tool configuration
- **Plugins:** React plugin, TypeScript support

---

## Browser APIs

### Web APIs Used
- **Fetch API**
  - Documentation: https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API
  - Usage: HTTP requests from frontend

- **localStorage API**
  - Documentation: https://developer.mozilla.org/en-US/docs/Web/API/Window/localStorage
  - Usage: Client-side data persistence

- **URL API**
  - Documentation: https://developer.mozilla.org/en-US/docs/Web/API/URL
  - Usage: URL parsing for password reset tokens

---

## Testing Resources

### PHPUnit
- **Documentation:** https://phpunit.de/documentation.html
- **Usage:** PHP unit testing framework
- **Integration:** Laravel's built-in testing

### Vitest
- **Documentation:** https://vitest.dev
- **Usage:** Frontend unit testing (potential)
- **Integration:** Vite-based testing

---

## Deployment Resources

### Nginx
- **Documentation:** https://nginx.org/en/docs
- **Usage:** Reverse proxy and web server
- **Configuration:** SSL/TLS, proxy_pass

### PHP-FPM
- **Documentation:** https://www.php.net/manual/en/install.fpm.php
- **Usage:** PHP process management
- **Configuration:** Pool management, performance tuning

---

## Design Resources

### UI/UX Principles
- **Material Design:** https://material.io/design (reference for design patterns)
- **Human Interface Guidelines:** https://developer.apple.com/design (iOS design principles)
- **Web Content Accessibility Guidelines (WCAG):** https://www.w3.org/WAI/WCAG21/quickref
  - Usage: Accessibility best practices

### Color Theory
- **Tailwind CSS Color Palette:** https://tailwindcss.com/docs/customizing-colors
- **Usage:** Consistent color scheme (blue, purple, slate, red, green)

---

## Academic and Research Sources

### Cryptography
- **NIST Cryptographic Standards and Guidelines**
  - URL: https://csrc.nist.gov/projects/cryptographic-standards-and-guidelines
  - Usage: Encryption algorithm selection (AES-256-CBC)

### Security Research
- **OWASP Research**
  - URL: https://owasp.org/www-project-research
  - Usage: Security threat analysis

---

## License Information

### Open Source Licenses
- **Laravel:** MIT License
- **React:** MIT License
- **Tailwind CSS:** MIT License
- **Lucide React:** ISC License
- **PHPGangsta GoogleAuthenticator:** BSD-3-Clause License
- **qrcode.react:** ISC License

### Commercial Services
- **Google Gemini API:** Google Cloud Terms of Service
- **Google Charts API:** Google Terms of Service

---

## Version Information

### Framework Versions
- **Laravel:** 12.x
- **React:** 18.x
- **PHP:** 8.4+
- **Node.js:** 18+ (for Vite)

### Package Versions
- **React Router DOM:** ^6.x
- **Tailwind CSS:** ^3.x
- **TypeScript:** ^5.x
- **Vite:** ^5.x
- **Laravel Sanctum:** ^4.x

---

## Additional Resources

### Development Environment
- **VS Code Extensions:**
  - PHP Intelephense
  - ESLint
  - Prettier
  - Tailwind CSS IntelliSense
  - React snippets

### Documentation Tools
- **Markdown:** https://www.markdownguide.org
  - Usage: Documentation formatting

### Diagram Tools
- **ASCII Art:** https://asciiflow.com
- **Flowcharts:** Text-based diagrams in markdown

---

## Summary

This project draws from:
- **Official Documentation:** Laravel, React, Vite, Tailwind CSS
- **Standards:** OWASP, RFC specifications, PSR standards
- **Third-Party Libraries:** PHPGangsta GoogleAuthenticator, Lucide React, qrcode.react
- **External APIs:** Google Gemini API, Google Charts API
- **Security Resources:** OWASP Top 10, NIST guidelines
- **Community Resources:** Stack Overflow, GitHub, Laravel/React communities

All sources have been used to ensure best practices, security, and maintainability in the application development.

