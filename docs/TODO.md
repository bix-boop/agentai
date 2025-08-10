# Phoenix AI - Detailed TODO and Roadmap

This document tracks implementation status and tasks across the entire codebase. It will be continuously updated during development.

## High-Priority Fixes (Blocking)
- Backend: Align Composer requirements with stable framework versions (Laravel ^11). [DONE]
- Frontend: Align React and router to stable versions compatible with CRA. [DONE]
- Installer: Verify PHP CLI detection and artisan execution in Plesk. Ensure .env generation and migrations succeed. [PENDING TEST]
- Auth Flow: End-to-end register/login via API and frontend integration. [TO TEST]
- Admin Seeding: Ensure installer creates admin user in `users` table. [TO TEST]

## Backend (Laravel)
- Routes
  - `routes/api.php`: Validate all endpoints and middleware bindings. Add missing validations and rate-limiting where necessary. [REVIEW]
  - `routes/web.php`: Serve SPA and exclude `/api` endpoints. [DONE]
- Controllers (`app/Http/Controllers`)
  - API
    - `AuthController.php`: Verify email/verification and password reset flows. Add throttling. [REVIEW]
    - `ChatController.php`: Confirm streaming, message limits, moderation hooks. [REVIEW]
    - `AIAssistantController.php`: Validate slug uniqueness, image uploads, and training fields. [REVIEW]
    - `PaymentController.php`: Verify Stripe/PayPal intents and webhooks. [REVIEW]
    - `WebhookController.php`: Validate signatures and idempotency. [REVIEW]
    - `AnalyticsController.php`: Ensure metrics aggregation aligns with schema. [REVIEW]
  - Admin
    - `AdminController.php`: Permissions, pagination, and filters. [REVIEW]
    - `SettingsController.php`: Test OpenAI key validation, config write. [REVIEW]
- Models (`app/Models`)
  - Ensure casts, fillables/guarded, relationships, and scopes are correct. [REVIEW]
  - Add indexes in migrations where missing (e.g., `slug`, `email`, foreign keys). [TODO]
- Migrations (`database/migrations`)
  - Confirm all tables, FKs, and cascading behaviors. [REVIEW]
  - Add missing unique constraints and composite indexes for performance. [TODO]
- Seeders (`database/seeders`)
  - `DefaultSettingsSeeder.php`: Validate defaults for payments, security, UI. [REVIEW]
  - Add initial categories/packages consistent with installer defaults to allow CLI setup without installer. [TODO]
- Services (`app/Services`)
  - `OpenAIService.php`: Centralize chat/image generation, cost tracking, token accounting. [TODO]
  - `PaymentService.php`: Abstract Stripe/PayPal flows, idempotency, and error mapping. [TODO]
  - `CreditService.php`: Enforce per-character credit consumption and limits. [TODO]
  - `AnalyticsService.php`: Daily aggregates, dashboards. [TODO]
- Config (`config/phoenix.php`)
  - Cross-check all toggles (tones, styles, language, filters). [REVIEW]
- Views
  - Move any legacy PHP views/routes to Laravel or frontend. [TODO]

## Installer (`/installer`)
- Requirements step: improve permission checks for `backend/storage` and `bootstrap/cache` (recursive). [DONE]
- Database step: add connectivity retry and better error messages. [DONE]
- Application setup: validate URLs and email formats. [DONE]
- Installation step: stream artisan output logs, capture errors. [DONE]
- Post-install: link `storage`, run `php artisan config:cache`. [DONE]
- Frontend build: after build, copy `frontend/build` to `backend/public/app`. [DONE]

## Frontend (CRA)
- Auth
  - `hooks/useAuth.ts`: Ensure token storage, refresh, and logout. [REVIEW]
  - `components/auth/AuthModal.tsx`: Review login/register flows and error handling. [DONE]
  - Replace modal-based auth with dedicated pages for SEO fallback. [TODO]
- Chat
  - `components/chat/*`: Add streaming, loading states, and error boundaries. [REVIEW]
  - `hooks/useChat.ts`: Implement chat hook and wire to backend endpoints. [DONE]
  - Chat API alignment (field names and response shapes). [DONE]
  - Implement image generation API handler to return { message, images, credits_used, user_credits_remaining }. [DONE]
- AI Catalog
  - `components/ai/AIAssistantGallery.tsx`: Filter/sort by categories/tier. [TODO]
- Admin
  - `pages/AdminDashboard.tsx`: Hook to admin API endpoints, add charts. [TODO]
- Routing
  - Ensure 404 page and protected route wrapper. [TODO]
- Theming
  - Tailwind dark mode, color customization settings integration. [TODO]

## Compatibility checks performed
- Laravel framework lockfile is v12; composer.json updated to ^12 to match. [OK]
- Laravel SPA routing vs API routing: compatible. [OK]
- Installer Node build and copy step: compatible with CRA output. [OK]
- Frontend React 18 + RRD v6 compatibility with existing code: basic review passed; full run pending actual build. [PENDING]
- CORS config allows all origins for API; Sanctum bearer token usage confirmed. [OK]

## Next actions
- Validate auth/admin endpoint contracts vs frontend usage and normalize responses where needed. [IN PROGRESS]
- Standardize backend error shapes for auth endpoints to include message, error_code, errors[]. [DONE]
- End-to-end smoke test (auth, gallery, start chat) and fix any breakages encountered. [NEXT]

## Payments
- Stripe
  - Create payment intent, confirm, webhook handling end-to-end in sandbox. [TODO]
- PayPal
  - Create/capture order and webhook validation. [TODO]
- Bank Deposit
  - Admin manual approval flow and notifications. [TODO]
- Controller endpoints presence in backend: create intent/confirm PayPal/bank deposit verified. [REVIEW DONE]

## Analytics & Reporting
- Aggregate queries and caching. [TODO]
- Export endpoints (CSV/Excel) for sales and usage. [TODO]

## Deployment & Plesk
- Add `deployment/scripts/*` with server setup and cron examples. [TODO]
- Document Plesk steps and troubleshooting. [TODO]
- Installer Plesk readiness (PHP path detection, composer fallback via composer.phar, Node optional build copy): [DONE]
- Add `docs/ASSET_UPLOADS.md` to list optional uploads (frontend/build, composer.phar, vendor.zip, .env). [DONE]

## Legacy Cleanup (root PHP files)
- Migrate legacy scripts in repo root (e.g., `admin-login.php`, `admin-dashboard.php`, etc.) into Laravel routes or remove. [TODO]

## QA Checklist
- Installer completes: creates DB, admin user, seeds defaults. [ ]
- User registration/login, email verification, password reset. [ ]
- Chatting with AI, credits decrement, limits enforced. [ ]
- Payments: Stripe, PayPal, Bank deposit. [ ]
- Admin: Users, AIs, Categories, Packages, Transactions, Settings. [ ]
- Security: rate limit, CSRF, CORS, content filtering. [ ]
- Frontend build and served correctly under `backend/public`. [ ]

## E2E Smoke Test Steps
- Install via installer wizard; confirm .env/migrations/admin created. [PENDING]
- Register a user; login; verify token and profile endpoint. [PENDING]
- Browse AI assistants; start a chat; send a message; receive AI response. [IN PROGRESS]
- Purchase credits (Stripe test); verify credits update and webhook processed. [PENDING]
- Access admin dashboard as admin; review analytics endpoints. [PENDING]