# Phoenix AI - Project Structure Reference

## Complete Project Structure

```
phoenix-ai/
├── README.md                           # Main project documentation
├── ROADMAP.md                          # Development roadmap and timeline
├── PROJECT_STRUCTURE.md               # This file - project structure reference
├── LICENSE                             # MIT License
├── .gitignore                          # Git ignore rules
├── docker-compose.yml                  # Docker development environment
├── 
├── backend/                            # Laravel Backend Application
│   ├── app/
│   │   ├── Console/
│   │   │   └── Commands/               # Artisan commands
│   │   ├── Events/                     # Application events
│   │   ├── Exceptions/                 # Exception handlers
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── Admin/              # Admin panel controllers
│   │   │   │   │   ├── AdminController.php
│   │   │   │   │   ├── UserController.php
│   │   │   │   │   ├── AIAssistantController.php
│   │   │   │   │   ├── CategoryController.php
│   │   │   │   │   ├── CreditPackageController.php
│   │   │   │   │   ├── PaymentController.php
│   │   │   │   │   ├── AnalyticsController.php
│   │   │   │   │   ├── BlogController.php
│   │   │   │   │   └── SettingsController.php
│   │   │   │   ├── API/                # API controllers
│   │   │   │   │   ├── AuthController.php
│   │   │   │   │   ├── ChatController.php
│   │   │   │   │   ├── AIAssistantController.php
│   │   │   │   │   ├── CategoryController.php
│   │   │   │   │   ├── UserController.php
│   │   │   │   │   ├── PaymentController.php
│   │   │   │   │   └── WebhookController.php
│   │   │   │   └── Web/                # Web controllers
│   │   │   │       ├── HomeController.php
│   │   │   │       ├── AuthController.php
│   │   │   │       ├── ChatController.php
│   │   │   │       ├── ProfileController.php
│   │   │   │       └── BlogController.php
│   │   │   ├── Middleware/             # Custom middleware
│   │   │   │   ├── AdminMiddleware.php
│   │   │   │   ├── CreditMiddleware.php
│   │   │   │   ├── RateLimitMiddleware.php
│   │   │   │   └── ContentFilterMiddleware.php
│   │   │   ├── Requests/               # Form request validation
│   │   │   │   ├── AIAssistantRequest.php
│   │   │   │   ├── ChatRequest.php
│   │   │   │   ├── RegisterRequest.php
│   │   │   │   └── PaymentRequest.php
│   │   │   └── Resources/              # API resources
│   │   │       ├── AIAssistantResource.php
│   │   │       ├── ChatResource.php
│   │   │       ├── UserResource.php
│   │   │       └── CategoryResource.php
│   │   ├── Jobs/                       # Queue jobs
│   │   │   ├── ProcessChatMessage.php
│   │   │   ├── SendEmail.php
│   │   │   ├── ProcessPayment.php
│   │   │   └── GenerateAnalytics.php
│   │   ├── Listeners/                  # Event listeners
│   │   ├── Mail/                       # Mail classes
│   │   │   ├── WelcomeEmail.php
│   │   │   ├── PaymentConfirmation.php
│   │   │   └── PasswordReset.php
│   │   ├── Models/                     # Eloquent models
│   │   │   ├── User.php
│   │   │   ├── AIAssistant.php
│   │   │   ├── Category.php
│   │   │   ├── Chat.php
│   │   │   ├── Message.php
│   │   │   ├── CreditPackage.php
│   │   │   ├── Transaction.php
│   │   │   ├── BlogPost.php
│   │   │   ├── Setting.php
│   │   │   └── Webhook.php
│   │   ├── Notifications/              # Notification classes
│   │   ├── Policies/                   # Authorization policies
│   │   ├── Providers/                  # Service providers
│   │   │   ├── AppServiceProvider.php
│   │   │   ├── AuthServiceProvider.php
│   │   │   ├── EventServiceProvider.php
│   │   │   ├── RouteServiceProvider.php
│   │   │   └── OpenAIServiceProvider.php
│   │   ├── Rules/                      # Custom validation rules
│   │   └── Services/                   # Business logic services
│   │       ├── OpenAIService.php
│   │       ├── PaymentService.php
│   │       ├── CreditService.php
│   │       ├── AnalyticsService.php
│   │       ├── EmailService.php
│   │       └── ContentFilterService.php
│   ├── bootstrap/
│   │   ├── app.php
│   │   └── cache/
│   ├── config/                         # Configuration files
│   │   ├── app.php
│   │   ├── database.php
│   │   ├── mail.php
│   │   ├── queue.php
│   │   ├── services.php                # Third-party service configs
│   │   ├── openai.php                  # OpenAI configuration
│   │   ├── payment.php                 # Payment gateway configs
│   │   └── phoenix.php                 # Custom app configuration
│   ├── database/
│   │   ├── factories/                  # Model factories
│   │   │   ├── UserFactory.php
│   │   │   ├── AIAssistantFactory.php
│   │   │   └── CategoryFactory.php
│   │   ├── migrations/                 # Database migrations
│   │   │   ├── 2024_01_01_000000_create_users_table.php
│   │   │   ├── 2024_01_01_000001_create_categories_table.php
│   │   │   ├── 2024_01_01_000002_create_ai_assistants_table.php
│   │   │   ├── 2024_01_01_000003_create_chats_table.php
│   │   │   ├── 2024_01_01_000004_create_messages_table.php
│   │   │   ├── 2024_01_01_000005_create_credit_packages_table.php
│   │   │   ├── 2024_01_01_000006_create_transactions_table.php
│   │   │   ├── 2024_01_01_000007_create_blog_posts_table.php
│   │   │   ├── 2024_01_01_000008_create_settings_table.php
│   │   │   └── 2024_01_01_000009_create_webhooks_table.php
│   │   └── seeders/                    # Database seeders
│   │       ├── DatabaseSeeder.php
│   │       ├── AdminSeeder.php
│   │       ├── CategorySeeder.php
│   │       ├── AIAssistantSeeder.php
│   │       ├── CreditPackageSeeder.php
│   │       └── SettingsSeeder.php
│   ├── public/                         # Public web assets
│   │   ├── index.php
│   │   ├── .htaccess
│   │   ├── favicon.ico
│   │   ├── css/
│   │   ├── js/
│   │   ├── images/
│   │   └── storage -> ../storage/app/public
│   ├── resources/
│   │   ├── css/
│   │   ├── js/
│   │   ├── lang/                       # Language files
│   │   │   ├── en/
│   │   │   ├── es/
│   │   │   ├── fr/
│   │   │   ├── de/
│   │   │   └── ar/
│   │   └── views/                      # Blade templates
│   │       ├── layouts/
│   │       │   ├── app.blade.php
│   │       │   ├── admin.blade.php
│   │       │   └── email.blade.php
│   │       ├── auth/
│   │       ├── admin/
│   │       ├── chat/
│   │       ├── blog/
│   │       └── emails/
│   ├── routes/
│   │   ├── web.php                     # Web routes
│   │   ├── api.php                     # API routes
│   │   ├── channels.php                # Broadcasting routes
│   │   └── console.php                 # Artisan commands
│   ├── storage/
│   │   ├── app/
│   │   │   ├── public/                 # Public storage
│   │   │   │   ├── avatars/            # AI assistant avatars
│   │   │   │   ├── logos/              # Site logos
│   │   │   │   ├── packages/           # Package images
│   │   │   │   └── blog/               # Blog images
│   │   │   └── private/                # Private storage
│   │   ├── framework/
│   │   └── logs/
│   ├── tests/                          # Application tests
│   │   ├── Feature/
│   │   │   ├── AuthTest.php
│   │   │   ├── ChatTest.php
│   │   │   ├── PaymentTest.php
│   │   │   └── AdminTest.php
│   │   └── Unit/
│   │       ├── OpenAIServiceTest.php
│   │       ├── CreditServiceTest.php
│   │       └── PaymentServiceTest.php
│   ├── vendor/                         # Composer dependencies
│   ├── .env                            # Environment configuration
│   ├── .env.example                    # Environment template
│   ├── artisan                         # Artisan CLI
│   ├── composer.json                   # PHP dependencies
│   ├── composer.lock
│   ├── phpunit.xml                     # PHPUnit configuration
│   └── webpack.mix.js                  # Laravel Mix configuration
│
├── frontend/                           # React Frontend Application
│   ├── public/
│   │   ├── index.html
│   │   ├── favicon.ico
│   │   ├── logo192.png
│   │   ├── logo512.png
│   │   ├── manifest.json
│   │   └── robots.txt
│   ├── src/
│   │   ├── components/                 # React components
│   │   │   ├── common/                 # Shared components
│   │   │   │   ├── Header.tsx
│   │   │   │   ├── Footer.tsx
│   │   │   │   ├── Sidebar.tsx
│   │   │   │   ├── Modal.tsx
│   │   │   │   ├── Button.tsx
│   │   │   │   ├── Input.tsx
│   │   │   │   ├── LoadingSpinner.tsx
│   │   │   │   └── ErrorBoundary.tsx
│   │   │   ├── auth/                   # Authentication components
│   │   │   │   ├── LoginForm.tsx
│   │   │   │   ├── RegisterForm.tsx
│   │   │   │   ├── ForgotPasswordForm.tsx
│   │   │   │   └── ResetPasswordForm.tsx
│   │   │   ├── chat/                   # Chat components
│   │   │   │   ├── ChatInterface.tsx
│   │   │   │   ├── MessageList.tsx
│   │   │   │   ├── MessageInput.tsx
│   │   │   │   ├── MessageBubble.tsx
│   │   │   │   ├── ChatSettings.tsx
│   │   │   │   └── VoiceControls.tsx
│   │   │   ├── ai/                     # AI Assistant components
│   │   │   │   ├── AIAssistantCard.tsx
│   │   │   │   ├── AIAssistantList.tsx
│   │   │   │   ├── CategoryFilter.tsx
│   │   │   │   └── AIAssistantDetail.tsx
│   │   │   ├── user/                   # User components
│   │   │   │   ├── Profile.tsx
│   │   │   │   ├── CreditBalance.tsx
│   │   │   │   ├── PurchaseHistory.tsx
│   │   │   │   └── UserDashboard.tsx
│   │   │   ├── payment/                # Payment components
│   │   │   │   ├── CreditPackages.tsx
│   │   │   │   ├── PaymentForm.tsx
│   │   │   │   ├── StripePayment.tsx
│   │   │   │   ├── PayPalPayment.tsx
│   │   │   │   └── BankDepositForm.tsx
│   │   │   ├── admin/                  # Admin components
│   │   │   │   ├── AdminDashboard.tsx
│   │   │   │   ├── UserManagement.tsx
│   │   │   │   ├── AIAssistantManagement.tsx
│   │   │   │   ├── CategoryManagement.tsx
│   │   │   │   ├── PaymentManagement.tsx
│   │   │   │   ├── AnalyticsDashboard.tsx
│   │   │   │   ├── BlogManagement.tsx
│   │   │   │   └── SettingsPanel.tsx
│   │   │   └── blog/                   # Blog components
│   │   │       ├── BlogList.tsx
│   │   │       ├── BlogPost.tsx
│   │   │       ├── BlogEditor.tsx
│   │   │       └── BlogSidebar.tsx
│   │   ├── pages/                      # Page components
│   │   │   ├── HomePage.tsx
│   │   │   ├── LoginPage.tsx
│   │   │   ├── RegisterPage.tsx
│   │   │   ├── ChatPage.tsx
│   │   │   ├── AIAssistantsPage.tsx
│   │   │   ├── ProfilePage.tsx
│   │   │   ├── PaymentPage.tsx
│   │   │   ├── AdminPage.tsx
│   │   │   ├── BlogPage.tsx
│   │   │   ├── AboutPage.tsx
│   │   │   ├── ContactPage.tsx
│   │   │   ├── PrivacyPage.tsx
│   │   │   ├── TermsPage.tsx
│   │   │   └── NotFoundPage.tsx
│   │   ├── hooks/                      # Custom React hooks
│   │   │   ├── useAuth.ts
│   │   │   ├── useChat.ts
│   │   │   ├── useAIAssistants.ts
│   │   │   ├── useCredits.ts
│   │   │   ├── usePayments.ts
│   │   │   ├── useWebSocket.ts
│   │   │   ├── useLocalStorage.ts
│   │   │   └── useDebounce.ts
│   │   ├── services/                   # API services
│   │   │   ├── api.ts                  # Base API configuration
│   │   │   ├── authService.ts
│   │   │   ├── chatService.ts
│   │   │   ├── aiService.ts
│   │   │   ├── userService.ts
│   │   │   ├── paymentService.ts
│   │   │   ├── adminService.ts
│   │   │   └── blogService.ts
│   │   ├── store/                      # State management (Redux/Zustand)
│   │   │   ├── index.ts
│   │   │   ├── authSlice.ts
│   │   │   ├── chatSlice.ts
│   │   │   ├── aiSlice.ts
│   │   │   ├── userSlice.ts
│   │   │   ├── paymentSlice.ts
│   │   │   └── uiSlice.ts
│   │   ├── utils/                      # Utility functions
│   │   │   ├── constants.ts
│   │   │   ├── helpers.ts
│   │   │   ├── validators.ts
│   │   │   ├── formatters.ts
│   │   │   ├── storage.ts
│   │   │   └── websocket.ts
│   │   ├── styles/                     # Styling files
│   │   │   ├── globals.css
│   │   │   ├── variables.css
│   │   │   ├── components.css
│   │   │   └── themes.css
│   │   ├── types/                      # TypeScript type definitions
│   │   │   ├── auth.ts
│   │   │   ├── chat.ts
│   │   │   ├── ai.ts
│   │   │   ├── user.ts
│   │   │   ├── payment.ts
│   │   │   └── api.ts
│   │   ├── App.tsx                     # Main App component
│   │   ├── App.css                     # App styles
│   │   ├── index.tsx                   # Entry point
│   │   ├── index.css                   # Global styles
│   │   ├── reportWebVitals.ts          # Performance reporting
│   │   └── setupTests.ts               # Test setup
│   ├── package.json                    # Node.js dependencies
│   ├── package-lock.json
│   ├── tsconfig.json                   # TypeScript configuration
│   ├── tailwind.config.js              # Tailwind CSS configuration
│   └── craco.config.js                 # Create React App configuration
│
├── installer/                          # Installation Wizard
│   ├── index.php                       # Main installer interface
│   ├── config.php                      # Configuration handler
│   ├── database.php                    # Database setup
│   ├── requirements.php                # System requirements check
│   ├── permissions.php                 # File permissions check
│   ├── complete.php                    # Installation completion
│   ├── assets/
│   │   ├── css/
│   │   │   └── installer.css           # Installer styles
│   │   ├── js/
│   │   │   └── installer.js            # Installer JavaScript
│   │   └── images/
│   │       ├── logo.png
│   │       └── wizard-steps.png
│   ├── includes/
│   │   ├── functions.php               # Helper functions
│   │   ├── database-setup.php          # Database setup functions
│   │   └── config-generator.php        # Configuration file generator
│   └── templates/
│       ├── header.php                  # Installer header
│       ├── footer.php                  # Installer footer
│       └── steps.php                   # Installation steps
│
├── docs/                               # Documentation
│   ├── README.md                       # Main documentation
│   ├── INSTALLATION.md                 # Installation guide
│   ├── CONFIGURATION.md                # Configuration guide
│   ├── DEPLOYMENT.md                   # Deployment guide
│   ├── TROUBLESHOOTING.md              # Troubleshooting guide
│   ├── api/                            # API documentation
│   │   ├── authentication.md
│   │   ├── chat.md
│   │   ├── ai-assistants.md
│   │   ├── users.md
│   │   ├── payments.md
│   │   └── webhooks.md
│   ├── user-guide/                     # User documentation
│   │   ├── getting-started.md
│   │   ├── creating-ai-assistants.md
│   │   ├── chat-features.md
│   │   ├── payment-system.md
│   │   ├── admin-panel.md
│   │   └── customization.md
│   └── developer/                      # Developer documentation
│       ├── architecture.md
│       ├── database-schema.md
│       ├── extending.md
│       └── contributing.md
│
└── deployment/                         # Deployment Scripts & Configuration
    ├── scripts/
    │   ├── deploy.sh                   # Main deployment script
    │   ├── setup-server.sh             # Server setup script
    │   ├── backup.sh                   # Backup script
    │   ├── restore.sh                  # Restore script
    │   └── update.sh                   # Update script
    ├── docker/
    │   ├── Dockerfile                  # Docker configuration
    │   ├── docker-compose.yml          # Docker Compose configuration
    │   ├── nginx/
    │   │   └── default.conf            # Nginx configuration
    │   └── php/
    │       └── php.ini                 # PHP configuration
    ├── plesk/
    │   ├── package.xml                 # Plesk package configuration
    │   ├── install.sh                  # Plesk installation script
    │   ├── uninstall.sh                # Plesk uninstallation script
    │   └── upgrade.sh                  # Plesk upgrade script
    └── server-configs/
        ├── apache/
        │   └── .htaccess               # Apache configuration
        ├── nginx/
        │   └── site.conf               # Nginx site configuration
        └── php/
            └── php.ini                 # Production PHP configuration
```

## Key Features by Directory

### Backend (`/backend`)
- **Models**: Core data structures (User, AIAssistant, Chat, etc.)
- **Controllers**: API and web request handling
- **Services**: Business logic (OpenAI, Payment, Credit management)
- **Jobs**: Background processing (chat, emails, payments)
- **Middleware**: Authentication, rate limiting, content filtering
- **Migrations**: Database schema management
- **Tests**: Comprehensive test coverage

### Frontend (`/frontend`)
- **Components**: Reusable React components with TypeScript
- **Pages**: Full page components for routing
- **Hooks**: Custom React hooks for state management
- **Services**: API communication layer
- **Store**: Global state management
- **Utils**: Helper functions and utilities
- **Types**: TypeScript type definitions

### Installer (`/installer`)
- **PHP-based**: Simple web installer like WordPress
- **Requirements Check**: System compatibility validation
- **Database Setup**: Automated database configuration
- **Configuration**: Environment file generation
- **Plesk Compatible**: Works with Plesk file manager

### Documentation (`/docs`)
- **User Guide**: End-user documentation
- **API Docs**: Developer API reference
- **Installation**: Step-by-step setup guide
- **Deployment**: Production deployment guide

### Deployment (`/deployment`)
- **Scripts**: Automated deployment tools
- **Docker**: Containerization support
- **Plesk**: Plesk-specific configurations
- **Server Configs**: Web server configurations

## Development Workflow

1. **Backend Development**: Laravel API with comprehensive testing
2. **Frontend Development**: React TypeScript with modern tooling
3. **Integration Testing**: End-to-end testing with real API calls
4. **Documentation**: Comprehensive documentation for all features
5. **Deployment**: Automated deployment with Plesk compatibility
6. **Testing**: Unit, integration, and end-to-end testing

## File Naming Conventions

- **PHP Files**: PascalCase (e.g., `AIAssistantController.php`)
- **React Components**: PascalCase (e.g., `ChatInterface.tsx`)
- **Services**: camelCase (e.g., `authService.ts`)
- **Database**: snake_case (e.g., `ai_assistants`)
- **Routes**: kebab-case (e.g., `/api/ai-assistants`)
- **CSS Classes**: kebab-case (e.g., `.chat-interface`)

This structure ensures maintainability, scalability, and ease of deployment while following industry best practices.