# Phoenix AI - SaaS Platform for AI Agents

A comprehensive, production-ready SaaS platform for creating and managing AI chatbots with ChatGPT-style UI/UX. Deploy easily on Ubuntu servers using Plesk UI, just like WordPress or Laravel applications.

![Phoenix AI Platform](docs/images/phoenix-ai-banner.png)

## 🚀 Features

### Core Platform
- **AI Assistant Management**: Create unlimited AI assistants with custom personalities, expertise, and behavior
- **ChatGPT-Style Interface**: Modern, responsive chat interface with real-time streaming responses
- **Multi-Language Support**: 200+ languages with automatic translation capabilities
- **Credit System**: Flexible pay-per-use credit system with multiple payment gateways
- **User Management**: Comprehensive user profiles, tiers, and access control
- **Admin Dashboard**: Powerful analytics, user management, and system configuration

### AI Capabilities
- **OpenAI Integration**: Support for GPT-3.5, GPT-4, and future models
- **Custom Training**: System prompts, temperature control, and response customization
- **Image Generation**: DALL-E integration for AI-generated images
- **Voice Features**: Text-to-speech with 500+ premium voices
- **Content Filtering**: Automatic content moderation and safety controls
- **Memory Management**: Conversation context and history management

### Business Features
- **Payment Processing**: Stripe, PayPal, and bank deposit support
- **Credit Packages**: Tiered pricing with VIP access levels
- **Analytics Dashboard**: Revenue tracking, user engagement, and AI performance metrics
- **Blog System**: Built-in CMS for content marketing
- **SEO Optimization**: Search engine friendly URLs and meta tags
- **Multi-Currency**: Support for multiple currencies and regions

### Technical Excellence
- **Laravel Backend**: Robust, scalable PHP framework
- **React Frontend**: Modern TypeScript-based user interface
- **Real-time Chat**: WebSocket support for instant messaging
- **Database Optimization**: Efficient queries and caching
- **Security**: Authentication, authorization, and data protection
- **API-First**: RESTful APIs for third-party integrations

## 🛠 Technology Stack

### Backend
- **Framework**: Laravel 10 (PHP 8.4)
- **Database**: MySQL 8.0
- **Authentication**: Laravel Sanctum
- **Payment**: Stripe, PayPal, Laravel Cashier
- **Queue**: Redis/Database queues
- **Storage**: Local/S3 file storage
- **Email**: SMTP/SendGrid/Mailgun

### Frontend
- **Framework**: React 18 with TypeScript
- **Styling**: Tailwind CSS
- **State Management**: Redux Toolkit/Zustand
- **HTTP Client**: Axios
- **Build Tool**: Vite
- **UI Components**: Headless UI

### Infrastructure
- **Web Server**: Nginx/Apache
- **Process Manager**: PHP-FPM
- **Caching**: Redis/Memcached
- **Search**: Full-text search
- **Monitoring**: Laravel Telescope
- **Deployment**: Plesk-compatible

## 📦 Installation

### Quick Installation (Recommended)

1. **Download & Extract**
   ```bash
   # Download the latest release
   wget https://github.com/your-repo/phoenix-ai/releases/latest/download/phoenix-ai.zip
   unzip phoenix-ai.zip
   ```

2. **Upload to Server**
   - Upload the extracted files to your domain directory via Plesk File Manager
   - Ensure the web root points to the `backend/public` directory

3. **Run Installation Wizard**
   - Navigate to `https://yourdomain.com/installer`
   - Follow the step-by-step installation wizard
   - The installer will automatically configure everything

### Manual Installation

<details>
<summary>Click to expand manual installation steps</summary>

#### Prerequisites
- PHP 8.1 or higher
- MySQL 8.0 or higher
- Node.js 16 or higher
- Composer
- NPM/Yarn

#### Backend Setup
```bash
cd backend
composer install --optimize-autoloader --no-dev
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
```

#### Frontend Setup
```bash
cd frontend
npm install
npm run build
```

#### Web Server Configuration
Configure your web server to point to `backend/public` directory.

</details>

## 🚀 Deployment

### Plesk Deployment (Recommended)

Phoenix AI is designed for easy deployment on Plesk-managed servers:

1. **Upload Files**: Extract and upload via Plesk File Manager
2. **Set Document Root**: Point to `backend/public`
3. **Run Installer**: Visit `/installer` and follow the wizard
4. **Configure SSL**: Enable SSL certificate in Plesk
5. **Set Up Cron**: Add Laravel scheduler to cron jobs

### Traditional Server Deployment

<details>
<summary>Server deployment instructions</summary>

#### Nginx Configuration
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/phoenix-ai/backend/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

#### Apache Configuration
```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /path/to/phoenix-ai/backend/public
    
    <Directory /path/to/phoenix-ai/backend/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

</details>

## 🔧 Configuration

### Environment Variables

Key configuration options in `.env`:

```env
# Application
APP_NAME="Phoenix AI"
APP_URL=https://yourdomain.com

# Database
DB_HOST=localhost
DB_DATABASE=phoenix_ai
DB_USERNAME=your_username
DB_PASSWORD=your_password

# OpenAI
OPENAI_API_KEY=your_openai_api_key

# Payment Gateways
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
PAYPAL_CLIENT_ID=your_paypal_client_id
PAYPAL_CLIENT_SECRET=your_paypal_secret

# Email
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
```

### System Settings

Configure additional settings through the admin panel:
- Credit pricing and packages
- AI model preferences
- Content filtering rules
- Email templates
- Payment gateway settings

## 👥 User Guide

### Creating AI Assistants

1. **Basic Setup**
   - Upload profile image (640x700px recommended)
   - Set name and expertise area
   - Create unique web address (slug)
   - Write compelling description

2. **AI Training**
   - Define system prompt and personality
   - Set response parameters (temperature, penalties)
   - Configure message length limits
   - Enable/disable special features

3. **Access Control**
   - Set visibility (public/private)
   - Define tier requirements
   - Assign to credit packages

### Managing Credits

1. **Credit Packages**
   - Create tiered pricing options
   - Set credit amounts and pricing
   - Add package benefits and features
   - Configure VIP access levels

2. **Payment Processing**
   - Automatic credit card processing via Stripe
   - PayPal integration for global payments
   - Manual bank deposit approval
   - Real-time credit balance updates

### Analytics & Reports

1. **User Analytics**
   - Registration and engagement trends
   - Credit usage patterns
   - Popular AI assistants
   - Revenue by time period

2. **AI Performance**
   - Usage statistics per assistant
   - Average response times
   - User satisfaction ratings
   - Cost analysis and profitability

## 🔌 API Documentation

Phoenix AI provides comprehensive REST APIs for third-party integrations:

### Authentication
```bash
# Login to get access token
curl -X POST https://yourdomain.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}'
```

### Chat with AI Assistant
```bash
# Start new chat
curl -X POST https://yourdomain.com/api/chats \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"ai_assistant_id":1,"message":"Hello, how can you help me?"}'
```

### Create AI Assistant
```bash
# Create new AI assistant
curl -X POST https://yourdomain.com/api/ai-assistants \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Marketing Expert","system_prompt":"You are a marketing expert...","category_id":1}'
```

Full API documentation available at `/api/documentation`

## 🛡 Security

### Built-in Security Features

- **Authentication**: Multi-factor authentication support
- **Authorization**: Role-based access control
- **Data Protection**: Encrypted sensitive data
- **Rate Limiting**: API and chat rate limiting
- **Content Filtering**: Automatic content moderation
- **SQL Injection**: Prepared statements and ORM protection
- **XSS Protection**: Input sanitization and output encoding
- **CSRF Protection**: Token-based CSRF protection

### Security Best Practices

1. **Regular Updates**: Keep dependencies updated
2. **SSL/TLS**: Always use HTTPS in production
3. **Database Security**: Use strong passwords and restricted access
4. **API Keys**: Secure storage of OpenAI and payment gateway keys
5. **Backup Strategy**: Regular automated backups
6. **Monitoring**: Set up error and intrusion monitoring

## 🔄 Updates & Maintenance

### Automatic Updates
- In-dashboard update notifications
- One-click update system (coming soon)
- Automatic security patches

### Manual Updates
```bash
# Backup current installation
php artisan backup:run

# Download and extract new version
# Run update command
php artisan app:update

# Clear caches
php artisan optimize:clear
php artisan config:cache
```

### Maintenance Mode
```bash
# Enable maintenance mode
php artisan down --message="Updating Phoenix AI"

# Disable maintenance mode
php artisan up
```

## 🎯 Monetization

### Revenue Streams

1. **Credit Sales**: Primary revenue from credit packages
2. **Subscription Tiers**: Monthly/yearly VIP access
3. **Custom AI Creation**: Premium AI assistant creation
4. **API Access**: Third-party API usage fees
5. **White Label**: Custom branding options

### Pricing Strategies

- **Freemium**: Free credits for new users
- **Pay-per-Use**: Credits based on actual usage
- **Tiered Packages**: Different levels with varying benefits
- **Enterprise**: Custom pricing for large organizations

## 📊 Performance

### Optimization Features

- **Database Indexing**: Optimized database queries
- **Caching**: Redis/Memcached for fast responses
- **CDN Support**: Static asset delivery optimization
- **Queue System**: Background job processing
- **Image Optimization**: Automatic image compression
- **Lazy Loading**: Progressive content loading

### Scalability

- **Horizontal Scaling**: Load balancer support
- **Database Clustering**: Master-slave configuration
- **Microservices Ready**: API-first architecture
- **Cloud Deployment**: AWS, Google Cloud, Azure support

## 🤝 Contributing

We welcome contributions to Phoenix AI! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

### Development Setup

```bash
# Clone repository
git clone https://github.com/your-repo/phoenix-ai.git
cd phoenix-ai

# Backend setup
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed

# Frontend setup
cd ../frontend
npm install
npm run dev

# Start development servers
php artisan serve # Backend on :8000
npm run dev       # Frontend on :3000
```

## 📝 License

Phoenix AI is open-source software licensed under the [MIT License](LICENSE).

## 🆘 Support

### Community Support
- **Documentation**: Comprehensive guides and tutorials
- **GitHub Issues**: Bug reports and feature requests
- **Community Forum**: User discussions and help

### Premium Support
- **Priority Support**: 24/7 technical assistance
- **Custom Development**: Tailored features and integrations
- **Training Sessions**: Team training and onboarding
- **Consultation**: Architecture and optimization advice

### Contact
- **Website**: https://phoenix-ai.com
- **Email**: support@phoenix-ai.com
- **Discord**: https://discord.gg/phoenix-ai
- **Twitter**: @PhoenixAI

## 🌟 Roadmap

### Upcoming Features

#### v2.0 (Q2 2024)
- [ ] Multi-tenant SaaS support
- [ ] Advanced analytics dashboard
- [ ] Mobile applications (iOS/Android)
- [ ] Voice chat capabilities
- [ ] AI model fine-tuning

#### v2.1 (Q3 2024)
- [ ] Marketplace for AI assistants
- [ ] Advanced workflow automation
- [ ] Integration with popular tools (Slack, Discord, etc.)
- [ ] Advanced reporting and exports
- [ ] Custom domain support

#### v3.0 (Q4 2024)
- [ ] AI assistant marketplace
- [ ] Advanced conversation flows
- [ ] Team collaboration features
- [ ] Advanced security features
- [ ] Enterprise SSO integration

### Long-term Vision
- Global AI assistant ecosystem
- Advanced AI capabilities beyond text
- Industry-specific AI solutions
- Educational and training platforms
- AI-powered business automation

---

## 🎉 Getting Started

Ready to launch your AI assistant platform? 

1. **[Download Phoenix AI](https://github.com/your-repo/phoenix-ai/releases/latest)**
2. **[Follow Installation Guide](#installation)**
3. **[Join Our Community](https://discord.gg/phoenix-ai)**
4. **[Read the Documentation](docs/)**

Transform your business with the power of AI assistants today!

---

<div align="center">
  <strong>Built with ❤️ by the Phoenix AI Team</strong>
  <br>
  <a href="https://phoenix-ai.com">Website</a> •
  <a href="docs/">Documentation</a> •
  <a href="https://discord.gg/phoenix-ai">Community</a> •
  <a href="mailto:support@phoenix-ai.com">Support</a>
</div>