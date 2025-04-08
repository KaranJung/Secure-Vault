# Secure API Vault - Architecture Documentation
Live: https://vault.bugmenepal.xyz/
Email: underside001@gmail.com

## 1. System Overview
A PHP-based web application for securely managing API credentials with:
- User authentication and session management
- Encrypted credential storage (AES-256)
- Expiry tracking and notifications
- Modern responsive UI with futuristic theme
- Comprehensive security logging

## 2. Core Components

### 2.1 Authentication System
- **Login/Registration**: Email/password based with session tokens
- **Password Reset**: Token-based with expiry validation
- **Session Management**: Server-side session storage
- **Admin Mode**: Special privileges for administrators

### 2.2 Credential Management
- **Encryption**: AES-256 CBC mode with unique IV per encryption
- **CRUD Operations**: 
  - Create: Add new API credentials
  - Read: View credentials (decrypted on-demand)
  - Update: Modify existing entries
  - Delete: Remove credentials
- **Expiry Tracking**: Visual indicators for soon-to-expire credentials
- **Tagging System**: Organize credentials with multiple tags

### 2.3 Database Layer
- **MySQL Database**: Relational structure with foreign keys
- **Tables**:
  - `users`: User accounts and authentication
  - `api_credentials`: Encrypted API keys and metadata
  - `password_resets`: Temporary reset tokens
  - `security_log`: Audit trail of security events

### 2.4 UI/UX Components
- **Dashboard**: Overview of all credentials with status indicators
- **Responsive Design**: Works on mobile and desktop
- **Modern Styling**: Glassmorphism effects with Tailwind CSS
- **Interactive Elements**: Alpine.js for dynamic functionality

## 3. Security Architecture

### 3.1 Data Protection
- **Encryption-at-rest**: All sensitive data encrypted before storage
- **Input Sanitization**: Protection against XSS and injection
- **Session Security**: Regenerated on privilege changes

### 3.2 Access Control
- **Authentication Required**: For all sensitive operations
- **Password Requirements**: Enforced during registration
- **Admin Privileges**: Separate access level

### 3.3 Monitoring
- **Security Logging**: All sensitive operations logged
- **Failed Login Tracking**: For brute force protection
- **Maintenance Mode**: Graceful degradation

## 4. Technical Stack

### 4.1 Backend
- **PHP**: 7.4+ (with PDO for database access)
- **Database**: MySQL 5.7+
- **Encryption**: OpenSSL (AES-256-CBC)

### 4.2 Frontend
- **Tailwind CSS**: Utility-first styling
- **Alpine.js**: Lightweight interactivity
- **Font Awesome**: Icon set
- **Google Fonts**: Custom typography

## 5. Deployment Considerations
- **Web Server**: Apache/Nginx with PHP-FPM
- **Environment Variables**: For sensitive configuration
- **Database Backups**: Regular encrypted backups
- **Security Updates**: Regular patching schedule

## 6. Future Enhancements
- Two-factor authentication
- API access for automation
- Browser extension integration
- Team/sharing functionality
