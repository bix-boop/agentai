# Phoenix AI - Software Requirements Specification (SRS)

## 1. Purpose
Build a SaaS platform to create, manage, and monetize AI assistants, deployable on Plesk via a ZIP + installer wizard.

## 2. Scope
- AI Assistants: profiles, categories, training, response controls, voice (optional), image gen
- Chat: ChatGPT-like UX, streaming, memory window
- Monetization: credits, packages, Stripe, PayPal, bank deposit
- Admin: users, assistants, packages, payments, settings, analytics
- Blog CMS: posts, categories, SEO, scheduling
- Security: auth, roles, content filtering, captcha, auditing

## 3. Users & Roles
- Visitor: browse, free chat limit
- Registered User: chat, manage credits, purchase
- Admin: manage all resources and settings

## 4. Functional Requirements
- Assistants CRUD with training instructions and config (temperature, penalties, limits)
- Categories CRUD and filtering
- Conversations/messages persistence with credit usage tracking
- Language, tone, writing style selectors
- Image generation via command (e.g., /img ...)
- Embeddable widget snippet
- Payments: checkout + webhooks + manual bank deposit
- Email (SMTP) for notifications and password resets
- Analytics reports by date range
- Blog: posts, categories, drafts, schedule

## 5. Non-Functional Requirements
- PHP 8.1+, no shell access required; runs on shared hosting/Plesk
- MySQL 5.7+/MariaDB 10.3+
- Secure by default (prepared statements, CSRF, XSS, password hashing)
- Performance: caching, pagination, streaming
- Maintainability: modular MVC, clear configs, docs

## 6. Constraints & Assumptions
- No Composer required (can be added later)
- Installer handles environment setup and schema
- .env holds secrets; storage is writable by web server

## 7. System Architecture
- Custom MVC with simple Router
- Controllers call Services and Models
- Views are PHP templates with a base layout
- Public assets under `public/assets`

## 8. Data Model (high-level)
- users(id, name, email, password_hash, role, status, credits, created_at)
- assistants(id, name, slug, expertise, description, avatar_path, training, config_json, visibility, created_at)
- categories(id, name, icon, is_active)
- assistant_category(assistant_id, category_id)
- conversations(id, user_id, assistant_id, lang, tone, style, memory_limit, created_at)
- messages(id, conversation_id, sender, content, tokens, created_at)
- packages(id, name, price_cents, credits, tier, image_path, is_active)
- orders(id, user_id, package_id, amount_cents, currency, status, payment_method, created_at)
- payments(id, order_id, provider, reference, status, payload_json, created_at)
- settings(key, value)
- blog_posts(id, title, slug, body, meta_title, meta_description, status, published_at)
- blog_categories(id, name, slug)
- blog_post_category(post_id, category_id)

## 9. Installation Flow
1. Requirements check
2. DB credentials
3. App settings and admin user
4. Write `.env`
5. Import schema and seed
6. Lock installer

## 10. Risks
- API key misuse -> content filter and moderation
- Payment disputes -> webhook signature verification
- Shared hosting limits -> no long-running jobs; use simple cron

This SRS evolves with the project and should be kept in sync.