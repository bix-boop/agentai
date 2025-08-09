# Phoenix AI

A SaaS platform for managing and deploying AI assistants (chatbots) with a ChatGPT-like UI and an installation wizard suitable for Plesk-based hosting (upload ZIP, extract, run installer).

## Highlights
- AI assistants with configurable expertise, training, tones, writing styles, languages, and image generation
- Embeddable chat widgets
- Credits-based billing with Stripe, PayPal, and bank deposit
- Blog CMS, analytics, and admin dashboard
- Installer wizard (no CLI required) for easy setup on shared hosting/Plesk

## Tech Stack
- PHP 8.1+ without Composer dependency for easy shared hosting deployment
- Custom lightweight MVC (Router, Controller, View, Model) using PDO
- MySQL/MariaDB (SQLite optional with minor edits)
- Plain JS and minimal CSS (Tailwind-compatible structure; can be swapped)

## Quick Start (Plesk / Shared Hosting)
1) Create a new domain/subdomain in Plesk with Apache + PHP 8.1+ enabled
2) Upload the Phoenix AI ZIP to the domain root and extract
3) Ensure `public` is the document root (Plesk: Hosting Settings -> Document Root = `httpdocs/public` or appropriate path)
4) Browse to your domain and follow the installer wizard

## Local Development
- Requirements: PHP 8.1+, MySQL
- Serve `public/` via a local web server (Apache/Nginx) with URL rewriting enabled

## Security Notes
- Ensure `.env` and `/install` are not publicly accessible post install (installer auto-locks)
- Set proper file permissions for `storage/` directories (writable by web server)

## License
Proprietary. All rights reserved.