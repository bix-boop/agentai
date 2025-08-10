# Phoenix AI - Complete Login & Authentication Guide

## 🔐 Authentication System Overview

Phoenix AI features a robust, secure authentication system with role-based access control, credit management, and comprehensive security features.

---

## 🚀 Initial Setup & Admin Access

### Step 1: Complete Installation
1. Run the installer wizard at `/installer/`
2. Complete all 6 installation steps
3. Note the admin credentials you create in **Step 4**

### Step 2: Admin Login
- **URL**: `https://yourdomain.com/`
- **Email**: The admin email you entered in Step 4
- **Password**: The admin password you created in Step 4
- **Auto-redirect**: Admin users are automatically redirected to `/admin` dashboard

---

## 👥 User Registration & Login Flow

### New User Registration
1. Visit the main site: `https://yourdomain.com/`
2. Click **"Get Started"** button
3. Fill out the registration form:
   - Full Name
   - Email Address
   - Password (min 8 characters)
   - Confirm Password
4. **Welcome Credits**: New users automatically receive 1,000 free credits
5. **Auto-login**: Users are logged in immediately after registration

### Existing User Login
1. Visit the main site: `https://yourdomain.com/`
2. Click **"Sign In"** button
3. Enter your credentials:
   - Email Address
   - Password
4. **Dashboard**: Regular users are redirected to `/dashboard`

---

## 🛡️ Security Features

### Password Security
- **Minimum Length**: 8 characters
- **Hashing**: All passwords are hashed using PHP's `password_hash()` with bcrypt
- **Never Stored**: Plain text passwords are never stored in the database

### Login Protection
- **Failed Attempts**: Users are locked after 5 failed login attempts
- **Account Lockout**: 15-minute lockout period after too many failures
- **Automatic Reset**: Failed attempts reset after successful login

### Session Management
- **Laravel Sanctum**: Secure token-based authentication
- **Token Storage**: Tokens stored securely in localStorage (frontend)
- **Auto-expiry**: Sessions expire based on Laravel configuration

---

## 👤 User Roles & Permissions

### Admin Users
- **Full Access**: Complete platform management
- **User Management**: View, edit, disable user accounts
- **AI Assistant Management**: Create, edit, delete any AI assistant
- **Analytics**: Access to all platform metrics and reports
- **Payment Management**: Approve bank deposits, process refunds
- **System Settings**: Configure OpenAI API, payment gateways, etc.

### Regular Users
- **Dashboard Access**: Personal dashboard with chat history
- **AI Assistants**: Browse and chat with available AI assistants
- **Credit Management**: View balance, purchase credits
- **Profile Management**: Update personal information
- **Chat History**: Access to all their conversations

---

## 💳 Credit System & Access Control

### Credit Balance
- **New Users**: 1,000 welcome credits
- **Credit Consumption**: 1 credit per character in AI responses
- **Balance Tracking**: Real-time credit balance updates
- **Purchase Options**: Multiple payment methods available

### Tier System
- **Tier 1**: Basic access (default for all users)
- **Tier 2+**: Premium access unlocked through credit package purchases
- **VIP Features**: Higher tiers access exclusive AI assistants

### Access Control
- **Public AI Assistants**: Available to all authenticated users
- **Tier-Restricted**: Some AI assistants require specific tier levels
- **Package-Restricted**: Premium AI assistants require specific credit packages

---

## 🔄 Complete User Journey

### 1. First-Time Visitor
```
Landing Page → Click "Get Started" → Registration Form → Welcome Credits → Dashboard
```

### 2. Returning User
```
Landing Page → Click "Sign In" → Login Form → Dashboard (or Admin Dashboard)
```

### 3. AI Interaction Flow
```
Dashboard → Browse AI Assistants → Select Assistant → Start Chat → Real-time Conversation
```

### 4. Credit Purchase Flow
```
Low Credits → Visit Pricing Page → Select Package → Choose Payment Method → Complete Purchase → Credits Added
```

---

## 🛠️ Admin Management Tasks

### User Management
1. **Login as Admin**: Use your Step 4 credentials
2. **Navigate**: Admin Dashboard → Users tab
3. **Actions Available**:
   - View user details and activity
   - Manually adjust credit balances
   - Disable/enable user accounts
   - View user transaction history

### AI Assistant Management
1. **Create New AI**: Admin Dashboard → AI Assistants → Create New
2. **Configure AI**:
   - Basic Info: Name, description, category
   - AI Training: System prompt, personality settings
   - Features: Voice, image generation, web search
   - Access Control: Public/private, tier requirements
3. **Monitor Performance**: View usage statistics and ratings

### Payment Management
1. **Bank Deposits**: Admin Dashboard → Payments → Pending Approvals
2. **Approve/Reject**: Review deposit information and approve manually
3. **Refunds**: Process refunds for any payment method
4. **Analytics**: Monitor revenue trends and payment method performance

---

## 🔧 Technical Implementation

### Backend Authentication (Laravel)
- **Controller**: `app/Http/Controllers/API/AuthController.php`
- **Model**: `app/Models/User.php`
- **Middleware**: Laravel Sanctum for API authentication
- **Routes**: Defined in `routes/api.php`

### Frontend Authentication (React)
- **Hook**: `src/hooks/useAuth.ts` - Main authentication logic
- **Components**: `src/components/auth/AuthModal.tsx` - Login/register modal
- **Services**: `src/services/api.ts` - API communication
- **Storage**: `src/services/TokenManager.ts` - Token management

### Database Schema
```sql
-- Users table with security features
users: id, name, email, password, role, credits_balance, failed_login_attempts, locked_until, last_login_at

-- Sessions and tokens managed by Laravel Sanctum
personal_access_tokens: tokenable_id, name, token, abilities, expires_at
```

---

## 🔍 Troubleshooting

### Common Login Issues

**Issue**: "Invalid credentials"
- **Solution**: Check email and password carefully
- **Note**: Email is case-sensitive

**Issue**: "Account is locked"
- **Solution**: Wait 15 minutes or contact admin to unlock
- **Prevention**: Ensure correct password to avoid lockouts

**Issue**: "Session expired"
- **Solution**: Simply log in again
- **Cause**: Tokens expire for security

### Admin Access Issues

**Issue**: Can't access admin dashboard
- **Check**: Ensure your user has `role = 'admin'` in database
- **Solution**: Update user role in database or create new admin

**Issue**: Forgot admin password
- **Solution**: Reset directly in database:
```sql
UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE role = 'admin';
-- This sets password to 'password'
```

---

## 📊 Login Analytics

### Tracking Metrics
- **User Registrations**: Daily signup counts
- **Login Frequency**: User engagement tracking
- **Failed Attempts**: Security monitoring
- **Session Duration**: User activity patterns

### Admin Insights
- **User Growth**: Registration trends over time
- **Active Users**: Daily, weekly, monthly active users
- **Retention Rate**: How many users return after registration
- **Conversion Rate**: Registration to first purchase

---

## 🎯 Best Practices

### For Users
1. **Strong Passwords**: Use unique, complex passwords
2. **Secure Logout**: Always log out on shared devices
3. **Credit Monitoring**: Keep track of your credit balance
4. **Regular Activity**: Active users get better AI assistant recommendations

### For Admins
1. **Regular Monitoring**: Check user activity and system health
2. **Security Audits**: Review failed login attempts regularly
3. **Credit Management**: Monitor credit consumption patterns
4. **AI Performance**: Track which assistants are most effective

---

## 📞 Support & Maintenance

### User Support
- **Self-Service**: Users can reset passwords via email (when configured)
- **Admin Assistance**: Admins can manually reset passwords and unlock accounts
- **Credit Issues**: Admins can manually adjust credit balances

### System Maintenance
- **Database Cleanup**: Regularly clean up expired sessions
- **Security Updates**: Keep Laravel and dependencies updated
- **Monitoring**: Use analytics to identify unusual patterns

---

## 🚀 Quick Reference

### Key URLs
- **Main Site**: `https://yourdomain.com/`
- **Admin Dashboard**: `https://yourdomain.com/admin`
- **AI Assistants**: `https://yourdomain.com/ai-assistants`
- **Pricing**: `https://yourdomain.com/pricing`
- **User Dashboard**: `https://yourdomain.com/dashboard`

### Default Credentials (After Installation)
- **Admin Email**: As entered in Step 4 of installer
- **Admin Password**: As created in Step 4 of installer
- **New User Credits**: 1,000 credits automatically assigned

### API Endpoints
- **Login**: `POST /api/v1/auth/login`
- **Register**: `POST /api/v1/auth/register`
- **Logout**: `POST /api/v1/auth/logout`
- **User Info**: `GET /api/v1/auth/user`

---

*This guide covers the complete authentication system. For technical details, refer to the source code in the `backend/app/Http/Controllers/API/AuthController.php` and `frontend/src/hooks/useAuth.ts` files.*