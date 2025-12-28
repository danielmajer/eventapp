## Sitemap

### Frontend Routes (React SPA)

```
/
├── Public Routes (Unauthenticated)
│   ├── / (Login Page)
│   ├── /reset-password (Password Reset Request)
│   └── /password/reset?token=...&email=... (Password Reset Form)
│
└── Protected Routes (Authenticated)
    ├── / (Events Page - Dashboard)
    ├── /chat (User Helpdesk Chat)
    ├── /preferences (User Preferences & MFA)
    └── /helpdesk (Helpdesk Agent Console) [helpdesk_agent/admin only]
```

### Backend API Routes

```
/api
├── Authentication (Public)
│   ├── POST /auth/login
│   ├── POST /auth/logout [auth:sanctum]
│   ├── POST /auth/password/email
│   ├── POST /auth/password/reset
│   ├── POST /auth/mfa/setup [auth:sanctum]
│   ├── POST /auth/mfa/confirm [auth:sanctum]
│   ├── POST /auth/mfa/disable [auth:sanctum]
│   └── POST /auth/mfa/verify
│
├── User Management (Protected)
│   └── GET /user [auth:sanctum]
│
├── Events (Protected)
│   ├── GET /events [auth:sanctum]
│   ├── POST /events [auth:sanctum]
│   ├── PUT /events/{event} [auth:sanctum]
│   └── DELETE /events/{event} [auth:sanctum]
│
└── Helpdesk
    ├── User Side (Protected)
    │   ├── GET /helpdesk/my-chats [auth:sanctum]
    │   ├── POST /helpdesk/chats [auth:sanctum]
    │   ├── POST /helpdesk/chats/{chat}/messages [auth:sanctum]
    │   ├── POST /helpdesk/chats/{chat}/close [auth:sanctum]
    │   └── GET /helpdesk/chats/{chat} [auth:sanctum]
    │
    └── Agent Side (Protected + Role)
        ├── GET /helpdesk/chats [auth:sanctum, helpdesk_agent]
        ├── GET /helpdesk/chats/{chat} [auth:sanctum, helpdesk_agent]
        ├── POST /helpdesk/chats/{chat}/agent-messages [auth:sanctum, helpdesk_agent]
        ├── POST /helpdesk/chats/{chat}/transfer [auth:sanctum, helpdesk_agent]
        └── POST /helpdesk/chats/{chat}/close [auth:sanctum, helpdesk_agent]
```
