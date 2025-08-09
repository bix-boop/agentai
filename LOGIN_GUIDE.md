# 🔐 Phoenix AI Login & Access Guide

## 🎯 **QUICK ACCESS**

### **Admin Access**
- **URL**: `https://legozo.com/`
- **Email**: The admin email you entered in Step 4 of installation
- **Password**: The admin password you entered in Step 4 of installation
- **Features**: Full platform management, user administration, AI assistant management

### **User Access**
- **URL**: `https://legozo.com/`
- **Registration**: Click "Get Started" button
- **Login**: Click "Sign In" button
- **Welcome Credits**: 1,000 credits automatically added upon registration

---

## 🚀 **COMPLETE AUTHENTICATION SYSTEM**

### **1. Installation Process**
1. **Step 1**: System requirements check
2. **Step 2**: License agreement
3. **Step 3**: Database configuration
4. **Step 4**: Site configuration + **ADMIN CREDENTIALS**
5. **Step 5**: Installation execution
6. **Step 6**: Completion confirmation

### **2. Admin Account Creation**
During **Step 4** of installation, you provide:
- **Admin Name**: Display name for the admin user
- **Admin Email**: Login email (must be valid email format)
- **Admin Password**: Secure password (minimum 8 characters)

The installer automatically:
- Creates admin user with `role = 'admin'`
- Sets `is_active = true` and `is_verified = true`
- Grants 100,000 welcome credits
- Stores password using Laravel's secure hashing

### **3. User Registration Flow**
1. User visits `https://legozo.com/`
2. Clicks "Get Started" or "Sign In"
3. **Registration**: Fills out name, email, password
4. **Email Verification**: Optional (configurable in admin settings)
5. **Welcome Credits**: Automatically receives 1,000 credits
6. **Auto-Login**: Immediately logged in after registration

---

## 🔒 **AUTHENTICATION FEATURES**

### **Security Features**
- ✅ **Password Hashing**: Laravel's secure bcrypt hashing
- ✅ **Failed Login Protection**: Account locks after 5 failed attempts
- ✅ **Session Management**: Laravel Sanctum tokens
- ✅ **Remember Me**: Persistent login option
- ✅ **IP Tracking**: Last login IP address tracking
- ✅ **Role-Based Access**: Admin, moderator, user roles

### **Login Attempt Protection**
- **Max Attempts**: 5 failed login attempts
- **Lock Duration**: 15 minutes
- **Reset**: Successful login resets failed attempts
- **Tracking**: Per-user failed login counting

### **Token Management**
- **API Tokens**: Laravel Sanctum for API authentication
- **Token Types**: `auth_token` (session) or `remember_token` (persistent)
- **Automatic Cleanup**: Expired tokens automatically removed

---

## 🎨 **USER INTERFACE**

### **Landing Page** (`/`)
- **Non-authenticated**: Shows landing page with login/register buttons
- **Authenticated Users**: Auto-redirects to `/dashboard`
- **Authenticated Admins**: Auto-redirects to `/admin`

### **User Dashboard** (`/dashboard`)
- **My Chats**: Chat history and active conversations
- **AI Assistants**: Browse and select AI assistants
- **Settings**: Account settings and preferences
- **Credits**: Current balance and usage history

### **Admin Dashboard** (`/admin`)
- **Overview**: Platform statistics and metrics
- **Users**: User management and administration
- **AI Assistants**: AI assistant management
- **Settings**: System configuration and OpenAI setup

---

## 🛠️ **TROUBLESHOOTING**

### **Can't Login?**
1. **Check Admin Credentials**: Use exact email/password from Step 4
2. **Verify Installation**: Run `https://legozo.com/verify-installation`
3. **Check Database**: Ensure admin user exists with `role = 'admin'`
4. **Reset Password**: Use Laravel's password reset (if configured)

### **Installation Issues?**
1. **Re-run Installation**: `https://legozo.com/installer/`
2. **Check System**: `https://legozo.com/health-check`
3. **Debug Info**: `https://legozo.com/debug`
4. **Final Optimization**: `https://legozo.com/final-optimization`

### **Database Verification**
```sql
-- Check if admin user exists
SELECT * FROM users WHERE role = 'admin';

-- Check user count
SELECT COUNT(*) as total_users FROM users;

-- Check if tables exist
SHOW TABLES;
```

### **Laravel Commands**
```bash
# Check Laravel status
php artisan --version

# Check migration status
php artisan migrate:status

# Clear all caches
php artisan optimize:clear

# Optimize for production
php artisan optimize
```

---

## 🎯 **POST-INSTALLATION SETUP**

### **1. Configure OpenAI**
1. Login as admin
2. Go to **Settings** tab
3. Enter your **OpenAI API Key**
4. Save configuration

### **2. Customize Platform**
1. **Site Settings**: Update site name, logo, colors
2. **Welcome Credits**: Configure default credits for new users
3. **Email Settings**: Configure SMTP for email notifications
4. **Payment Settings**: Setup Stripe/PayPal for credit purchases

### **3. Create Content**
1. **AI Assistants**: Create specialized AI assistants
2. **Categories**: Organize assistants by category
3. **Credit Packages**: Setup pricing tiers
4. **Blog Posts**: Add content for SEO and engagement

---

## 🔗 **API ENDPOINTS**

### **Authentication Endpoints**
- `POST /api/v1/auth/register` - User registration
- `POST /api/v1/auth/login` - User login
- `POST /api/v1/auth/logout` - User logout
- `GET /api/v1/auth/me` - Get current user
- `POST /api/v1/auth/refresh` - Refresh token

### **System Endpoints**
- `GET /api/v1/status` - System status
- `GET /api/v1/system/health` - Health check
- `GET /api/v1/categories` - List categories
- `GET /api/v1/ai-assistants` - List AI assistants

---

## 🎉 **SUCCESS INDICATORS**

Your Phoenix AI installation is **PERFECT** when:

- ✅ **Admin Login**: You can login with your Step 4 credentials
- ✅ **User Registration**: New users can register and receive credits
- ✅ **Dashboard Access**: Both admin and user dashboards load properly
- ✅ **API Responses**: All API endpoints return valid JSON
- ✅ **Database**: All tables exist with proper data
- ✅ **Laravel**: Framework responds correctly to all commands

**🚀 Ready to launch your AI assistant platform!**