# Care4Mom - Standard Operating Procedures (SOP)

**Document Version**: 1.0  
**Last Updated**: December 2024  
**Prepared For**: PCFLSINC/Care4Mom Development Team  
**Classification**: Internal Development Documentation  

---

## 📋 **TABLE OF CONTENTS**

1. [Purpose & Scope](#purpose--scope)
2. [System Administration](#system-administration)
3. [Development Workflow](#development-workflow)
4. [Database Management](#database-management)
5. [User Management Procedures](#user-management-procedures)
6. [Deployment Procedures](#deployment-procedures)
7. [Monitoring & Maintenance](#monitoring--maintenance)
8. [Security Procedures](#security-procedures)
9. [Backup & Recovery](#backup--recovery)
10. [Troubleshooting Guidelines](#troubleshooting-guidelines)
11. [Quality Assurance](#quality-assurance)
12. [Emergency Procedures](#emergency-procedures)

---

## 🎯 **PURPOSE & SCOPE**

### **Purpose**
This Standard Operating Procedure (SOP) document provides comprehensive guidelines for the development, deployment, maintenance, and operation of the Care4Mom cancer care tracking application. It ensures consistent, reliable, and secure operation of the system while maintaining high standards of patient care and data protection.

### **Scope**
This SOP covers all aspects of Care4Mom system operations including:
- Development environment setup and maintenance
- Database administration and data integrity
- User account management and security
- Application deployment and updates
- System monitoring and performance optimization
- Emergency response and incident management

### **Target Audience**
- Development team members
- System administrators
- Database administrators
- Quality assurance personnel
- Project managers
- Healthcare stakeholders

---

## 🔧 **SYSTEM ADMINISTRATION**

### **A. Development Environment Setup**

#### **Prerequisites Installation Procedure**
1. **Verify PHP Installation**
   ```bash
   php --version  # Must be 8.3+
   ```
   - **Required Version**: PHP 8.3 or higher
   - **Test Result**: Should show PHP 8.3.6 (tested working version)

2. **Verify Composer Installation**
   ```bash
   composer --version  # Must be 2.8+
   ```
   - **Required Version**: Composer 2.8.10 or higher
   - **Purpose**: PHP dependency management

3. **Verify Node.js (Optional)**
   ```bash
   node --version  # Optional, for frontend tooling
   npm --version   # Optional, tested with npm 10.8.2
   ```
   - **Required for**: Frontend build tools and package management

4. **Verify PHP Modules**
   ```bash
   php -m | grep -E "(pdo_sqlite|mysqli|pdo_mysql|gd|curl|json)"
   ```
   - **Required Modules**: pdo_sqlite, mysqli, pdo_mysql, gd, curl, json
   - **Purpose**: Database connectivity, image processing, HTTP requests

#### **Development Server Procedures**

**CRITICAL: NEVER CANCEL Long-Running Commands**

1. **Start Development Server** (NEVER CANCEL)
   ```bash
   cd /path/to/Care4Mom
   php -S localhost:8000 -t public/
   ```
   - **Timeout Setting**: 3600+ seconds (1 hour minimum)
   - **Access URL**: http://localhost:8000
   - **Status**: Runs continuously until stopped with Ctrl+C
   - **Purpose**: Local development and testing

2. **Database Connectivity Test**
   ```bash
   php -f includes/db.php  # Should connect without errors
   ```
   - **Timeout Setting**: 30+ seconds
   - **Expected Result**: No error messages, successful connection

### **B. Directory Structure Maintenance**

#### **Required Directory Creation**
```bash
mkdir -p public sql includes assets/{css,js,images/{icons,gallery}} modules widgets/{modal,navbar,cards,charts}
```

#### **Permission Settings**
- **Folders**: 755 (rwxr-xr-x)
- **PHP Files**: 644 (rw-r--r--)
- **sql/ Directory**: 755 for database file storage
- **assets/ Directory**: 755 for uploads
- **logs/ Directory**: 777 for error logging

---

## 💻 **DEVELOPMENT WORKFLOW**

### **A. Code Development Standards**

#### **File Naming Conventions**
- **PHP Files**: lowercase with underscores (symptom_tracking.php)
- **CSS Classes**: kebab-case (large-text-mode)
- **JavaScript Functions**: camelCase (toggleLargeText)
- **Database Tables**: lowercase with underscores (symptom_logs)

#### **Code Quality Standards**
1. **PHP Syntax Validation** (5-10 seconds expected)
   ```bash
   find . -name "*.php" -exec php -l {} \;
   ```
   - **Timeout Setting**: 60+ seconds
   - **Purpose**: Validate syntax across entire codebase

2. **Module Testing Procedure** (3-5 seconds each)
   ```bash
   php -f modules/symptom.php
   php -f modules/med.php
   php -f modules/vitals.php
   ```
   - **Timeout Setting**: 30+ seconds per module
   - **Purpose**: Individual module functionality verification

### **B. Version Control Procedures**

#### **Commit Standards**
- **Commit Message Format**: `type(scope): description`
- **Examples**: 
  - `feat(symptom): add voice note recording`
  - `fix(database): resolve SQLite compatibility issue`
  - `docs(sop): update deployment procedures`

#### **Branch Management**
- **Main Branch**: Production-ready code only
- **Development Branch**: Active development work
- **Feature Branches**: Individual feature development
- **Hotfix Branches**: Critical production fixes

---

## 🗄️ **DATABASE MANAGEMENT**

### **A. Database Configuration**

#### **Development Database (SQLite)**
```php
// Development configuration in includes/db.php
define('DB_TYPE', 'sqlite');
define('DB_PATH', 'sql/care4mom.db');
```
- **File Location**: `sql/care4mom.db`
- **Automatic Creation**: Database file created on first use
- **Backup**: File-based backup simple copy operation

#### **Production Database (MySQL)**
```php
// Production configuration in includes/db.php
define('DB_TYPE', 'mysql');
define('DB_HOST', 'localhost');
define('DB_USER', 'outsrglr_Momcare');
define('DB_PASS', 'ethanJ#2015');
define('DB_NAME', 'outsrglr_Momcare');
```

### **B. Database Operations (NEVER CANCEL)**

#### **Schema Import Procedure**
```bash
mysql -u root care4mom < sql/care4mom.sql
```
- **Timeout Setting**: 120+ seconds (NEVER CANCEL)
- **Duration**: Can take 60+ seconds for large datasets
- **Purpose**: Initial database setup and schema creation

#### **Data Export Procedure**
```bash
mysqldump -u root care4mom > backup.sql
```
- **Timeout Setting**: 180+ seconds (NEVER CANCEL)
- **Duration**: Can take 90+ seconds for comprehensive backup
- **Purpose**: Data backup and migration

#### **Performance Monitoring**
- **Small Operations (1000 records)**: 1-2 seconds expected
- **Large Datasets**: 30+ seconds, NEVER CANCEL
- **Report Generation**: 45+ seconds for comprehensive reports

### **C. Data Integrity Procedures**

#### **Daily Data Validation**
1. **User Account Verification**
   - Verify demo accounts (admin123, patient123, caregiver123)
   - Test role-based access controls
   - Validate session management

2. **Health Data Integrity**
   - Verify symptom logging accuracy
   - Check medication tracking completeness
   - Validate timestamp accuracy for all entries

3. **System Performance Checks**
   - Monitor database response times
   - Check error log frequency
   - Validate backup completion

---

## 👤 **USER MANAGEMENT PROCEDURES**

### **A. Account Creation Process**

#### **Patient Account Setup**
1. **Navigate to Registration**
   - Access: `public/register.php`
   - Select Role: "Patient"
   - Complete profile information

2. **Account Verification**
   - Test login functionality
   - Verify dashboard access
   - Check accessibility features

3. **Initial Configuration**
   - Set large text preference
   - Configure emergency contacts
   - Set medication reminders

#### **Caregiver Account Setup**
1. **Account Creation**
   - Access: `public/register.php`
   - Select Role: "Caregiver"
   - Link to patient account

2. **Permission Configuration**
   - Set access levels for patient data
   - Configure notification preferences
   - Test communication features

### **B. User Support Procedures**

#### **Password Reset Process**
1. **Manual Reset** (Admin)
   - Access admin tools (TOOLS.php)
   - Password: 079777
   - Navigate to user management
   - Reset user password

2. **Security Verification**
   - Verify user identity
   - Document reset in audit log
   - Notify user of password change

#### **Account Troubleshooting**
1. **Login Issues**
   - Check database connectivity
   - Verify user credentials
   - Clear browser cache/cookies
   - Test with different browser

2. **Data Access Problems**
   - Verify role permissions
   - Check database integrity
   - Review error logs
   - Test with demo account

---

## 🚀 **DEPLOYMENT PROCEDURES**

### **A. Production Deployment**

#### **Pre-Deployment Checklist**
- [ ] All tests passing
- [ ] Database schema updated
- [ ] Configuration files updated
- [ ] SSL certificate installed
- [ ] Backup procedures verified

#### **cPanel Deployment Process**
1. **File Upload**
   - Upload all files to `public_html/care4mom/`
   - Maintain directory structure
   - Set proper file permissions

2. **Database Setup**
   - Create MySQL database
   - Import schema from `sql/care4mom.sql`
   - Update database credentials in `includes/db.php`

3. **Configuration Updates**
   ```php
   // Update production settings
   define('DB_TYPE', 'mysql');
   define('DB_HOST', 'localhost');
   define('DB_USER', 'outsrglr_Momcare');
   define('DB_PASS', 'ethanJ#2015');
   define('DB_NAME', 'outsrglr_Momcare');
   ```

### **B. Mobile Deployment**

#### **Progressive Web App Setup**
1. **Service Worker Configuration**
   - Enable offline functionality
   - Configure cache policies
   - Set update procedures

2. **Mobile Testing Procedure**
   - Test on multiple devices
   - Verify touch interactions
   - Check responsive layouts
   - Test offline functionality

---

## 📊 **MONITORING & MAINTENANCE**

### **A. System Health Monitoring**

#### **Daily Health Checks**
1. **Application Status**
   - Check server response times
   - Verify database connectivity
   - Monitor error log entries
   - Test critical user workflows

2. **Performance Metrics**
   - Database query response times
   - Page load performance
   - Mobile responsiveness
   - Accessibility compliance

#### **Weekly Maintenance Tasks**
1. **Database Maintenance**
   - Run database optimization
   - Clear unnecessary log entries
   - Update statistics tables
   - Verify backup integrity

2. **Security Updates**
   - Check for PHP updates
   - Review security patches
   - Update dependencies
   - Review access logs

### **B. Error Management**

#### **Error Monitoring Procedure**
1. **Error Log Review**
   - Check `includes/errorlog.php` outputs
   - Review database error logs
   - Monitor user-reported issues
   - Track error frequency patterns

2. **Error Response Protocol**
   - **Critical Errors**: Immediate response within 1 hour
   - **Major Errors**: Response within 4 hours
   - **Minor Errors**: Response within 24 hours
   - **Cosmetic Issues**: Response within 1 week

---

## 🔒 **SECURITY PROCEDURES**

### **A. Data Protection Protocols**

#### **Patient Data Security**
1. **Access Control**
   - Role-based permissions enforced
   - Session timeout configured
   - Strong password requirements
   - Regular access audits

2. **Data Encryption**
   - HTTPS enforced for all communications
   - Database passwords encrypted
   - Sensitive data field encryption
   - Secure file upload protocols

#### **Security Incident Response**
1. **Incident Detection**
   - Monitor for unusual access patterns
   - Check for data breach indicators
   - Review failed login attempts
   - Monitor error log anomalies

2. **Incident Response Steps**
   - **Immediate**: Isolate affected systems
   - **Within 1 Hour**: Assess scope of incident
   - **Within 4 Hours**: Notify stakeholders
   - **Within 24 Hours**: Implement fixes
   - **Within 72 Hours**: Complete incident report

### **B. HIPAA Compliance Procedures**

#### **Data Handling Standards**
1. **Minimum Necessary Rule**
   - Limit data access to necessary personnel
   - Implement role-based data restrictions
   - Regular access privilege reviews
   - Document all data access requests

2. **Audit Logging**
   - Log all user actions with timestamps
   - Track data access and modifications
   - Monitor file downloads and exports
   - Regular audit log reviews

---

## 💾 **BACKUP & RECOVERY**

### **A. Backup Procedures (NEVER CANCEL)**

#### **Daily Backup Process**
```bash
# Database backup (NEVER CANCEL - 90+ seconds)
mysqldump -u outsrglr_Momcare -p'ethanJ#2015' outsrglr_Momcare > backup_$(date +%Y%m%d).sql

# File system backup
tar -czf files_backup_$(date +%Y%m%d).tar.gz /path/to/care4mom/
```
- **Timeout Setting**: 300+ seconds
- **Schedule**: Daily at 2:00 AM
- **Retention**: 30 days for daily, 12 months for weekly

#### **Recovery Testing**
1. **Monthly Recovery Test**
   - Restore to test environment
   - Verify data integrity
   - Test application functionality
   - Document recovery time

### **B. Disaster Recovery**

#### **Recovery Time Objectives (RTO)**
- **Critical Functions**: 4 hours
- **Full System Restore**: 24 hours
- **Data Recovery**: 8 hours
- **Network Services**: 2 hours

#### **Recovery Point Objectives (RPO)**
- **Patient Data**: 1 hour
- **System Configuration**: 24 hours
- **User Accounts**: 4 hours
- **Application Files**: 24 hours

---

## 🔧 **TROUBLESHOOTING GUIDELINES**

### **A. Common Issues & Solutions**

#### **Database Connection Problems**
**Symptoms**: Database connection errors, login failures
**Solutions**:
1. Verify MySQL service status: `sudo service mysql start`
2. Check database credentials in `includes/db.php`
3. Test connectivity: `php -r "include 'includes/db.php'; echo 'Connected';"`
4. For SQLite: Ensure `sql/` directory is writable (755 permissions)

#### **Permission Issues**
**Symptoms**: File upload failures, session errors
**Solutions**:
1. Set web server write access to `sql/` directory
2. Configure `uploads/` directory to 755 permissions
3. Ensure session directory has write access
4. Check PHP error logs for permission details

#### **Performance Issues**
**Symptoms**: Slow page loads, timeout errors
**Solutions**:
1. Large symptom datasets may slow report generation (normal)
2. Photo uploads over 5MB may timeout - adjust PHP settings
3. Multiple concurrent users may require connection pooling
4. Optimize database queries for large datasets

### **B. Emergency Troubleshooting**

#### **System Down Procedures**
1. **Immediate Response** (0-15 minutes)
   - Check server status and connectivity
   - Verify database service status
   - Review recent error logs
   - Test with known working configuration

2. **Escalation Procedures** (15-30 minutes)
   - Contact hosting provider
   - Check for service outages
   - Implement backup systems if available
   - Notify users of service interruption

3. **Recovery Actions** (30+ minutes)
   - Restore from most recent backup
   - Update stakeholders on status
   - Document incident for future prevention
   - Conduct post-incident review

---

## ✅ **QUALITY ASSURANCE**

### **A. Testing Procedures**

#### **User Workflow Validation** (Manual Testing Required)
**CRITICAL: These workflows must be manually tested after any changes**

1. **Patient Registration and Login** (2-3 minutes per test)
   - Register new patient account with role selection
   - Login with credentials and navigate to dashboard
   - Verify large text mode toggle functionality
   - Test accessibility features

2. **Symptom Logging Complete Workflow** (5-7 minutes per test)
   - Log at least 3 different symptoms (dizziness, hot hands/feet, stomach pain)
   - Set severity levels using 1-10 scale
   - Add voice notes or text descriptions
   - View symptom history and timeline
   - Verify color-coded severity indicators

3. **Medication Management Workflow** (4-6 minutes per test)
   - Add medication entries with dosage information
   - Mark doses as taken with photo confirmation
   - Check compliance statistics and trends
   - Test reminder notification system

4. **AI Advisory System Test** (3-5 minutes per test)
   - Log symptoms that should trigger AI alerts
   - Verify AI advice modal appears correctly
   - Test emergency alert notifications
   - Verify caregiver notifications function

5. **Report Generation and Export** (2-4 minutes per test)
   - Generate doctor-ready reports in JSON, PDF, CSV formats
   - Verify data accuracy and completeness
   - Test export download functionality
   - Validate report formatting

6. **Caregiver Coordination Test** (6-8 minutes per test)
   - Create caregiver account and link to patient
   - Test multi-user access controls
   - Send notifications between users
   - Assign and complete care tasks

#### **Accessibility Validation**
**ALWAYS verify these features work:**
- Large text mode increases font size by 150%+
- High contrast mode for visual impairment support
- Large buttons meet 44px minimum touch targets
- Clear, simple navigation structure
- Voice note recording functionality

### **B. Performance Standards**

#### **Response Time Requirements**
- **Page Load**: Under 3 seconds on mobile devices
- **Database Queries**: Under 2 seconds for standard operations
- **Report Generation**: Under 45 seconds for comprehensive reports
- **Photo Upload**: Under 60 seconds for files up to 5MB

#### **Availability Standards**
- **Uptime Target**: 99.5% monthly availability
- **Planned Maintenance**: Maximum 4 hours monthly
- **Emergency Response**: 1 hour maximum downtime
- **Backup Systems**: 15-minute failover capability

---

## 🚨 **EMERGENCY PROCEDURES**

### **A. Medical Emergency Response**

#### **Patient Emergency Protocols**
1. **Severe Symptom Alerts**
   - AI system detects critical symptom combinations
   - Automatic caregiver notifications sent
   - Emergency contact information displayed
   - 911 calling functionality activated

2. **System Failure During Emergency**
   - Backup communication methods activated
   - Emergency contact phone tree initiated
   - Manual documentation procedures implemented
   - Alternative care coordination methods deployed

### **B. Technical Emergency Response**

#### **Critical System Failure**
1. **Immediate Actions** (0-5 minutes)
   - Assess scope of failure
   - Activate backup systems if available
   - Document failure time and symptoms
   - Begin stakeholder notifications

2. **Short-term Response** (5-30 minutes)
   - Implement emergency workarounds
   - Restore from most recent backup
   - Contact technical support resources
   - Update users on service status

3. **Long-term Recovery** (30+ minutes)
   - Fully restore all system functionality
   - Verify data integrity and completeness
   - Conduct thorough system testing
   - Document lessons learned and prevention measures

### **C. Data Breach Response**

#### **Breach Detection and Response**
1. **Detection Procedures**
   - Monitor unusual access patterns
   - Check for unauthorized data exports
   - Review failed authentication attempts
   - Investigate system anomalies

2. **Response Protocol**
   - **Immediate**: Isolate affected systems (within 15 minutes)
   - **Short-term**: Assess data exposure scope (within 1 hour)
   - **Notification**: Contact stakeholders (within 4 hours)
   - **Remediation**: Implement security fixes (within 24 hours)
   - **Documentation**: Complete incident report (within 72 hours)

---

## 📋 **APPENDICES**

### **Appendix A: Contact Information**
- **Development Team Lead**: [Contact Information]
- **Database Administrator**: [Contact Information]
- **System Administrator**: [Contact Information]
- **Security Officer**: [Contact Information]
- **Emergency Support**: [24/7 Contact Information]

### **Appendix B: System Passwords and Access**
- **Admin Tools Access**: TOOLS.php (Password: 079777)
- **Database Credentials**: outsrglr_Momcare / ethanJ#2015
- **Demo Accounts**: admin123, patient123, caregiver123 (Password: "password")

### **Appendix C: Compliance Documentation**
- **HIPAA Compliance Checklist**: [Reference Document]
- **Security Audit Reports**: [Quarterly Reports]
- **Data Protection Impact Assessment**: [Current Assessment]
- **Privacy Policy Documentation**: [Patient-facing Documentation]

---

## 📝 **DOCUMENT REVISION HISTORY**

| Version | Date | Changes | Author |
|---------|------|---------|--------|
| 1.0 | December 2024 | Initial SOP creation | Care4Mom Development Team |

---

**Document Approval:**

**Prepared By**: Care4Mom Development Team  
**Reviewed By**: [Quality Assurance Manager]  
**Approved By**: [Project Manager]  
**Effective Date**: December 2024  

**Next Review Date**: March 2025

---

*This Standard Operating Procedure is a living document and should be updated regularly to reflect changes in the Care4Mom system, regulatory requirements, and operational procedures. All team members are responsible for following these procedures and suggesting improvements.*