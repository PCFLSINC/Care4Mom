# Care4Mom - cPanel Server Deployment Guide

## 🚀 Complete Deployment Guide for cPanel Hosting

This guide will walk you through deploying the Care4Mom application on a cPanel-based hosting server from your iPhone or any mobile device.

---

## 📋 Pre-Deployment Checklist

### ✅ Required Information
- [ ] cPanel login credentials (username/password)
- [ ] Domain name or subdomain for the application
- [ ] Database credentials provided: `outsrglr_Momcare` / `ethanJ#2015`
- [ ] FTP/File Manager access through cPanel

### ✅ Application Status
- [x] **Core functionality tested and working**
- [x] **Database schema ready for production**
- [x] **CSS/JS paths fixed for subdirectory deployment**
- [x] **User authentication system functional**
- [x] **Symptom tracking fully operational**

---

## 📱 Step 1: Access cPanel from iPhone

### Using Safari on iPhone:
1. **Open Safari** and navigate to your hosting provider's cPanel
2. **Login URL format**: `https://your-domain.com:2083` or `https://your-domain.com/cpanel`
3. **Enter credentials** provided by your hosting provider
4. **Enable "Request Desktop Site"** in Safari for better cPanel experience

### Alternative: cPanel Mobile App
1. **Download "cPanel"** app from App Store
2. **Add server** with your hosting details
3. **Login** with your cPanel credentials

---

## 📁 Step 2: Prepare File Structure

### Required Directory Structure:
```
public_html/care4mom/          <- Main application folder
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── main.js
│   └── images/
│       ├── icons/
│       └── gallery/
├── includes/
│   ├── header.php
│   ├── footer.php
│   ├── db.php
│   └── errorlog.php
├── modules/
│   ├── symptom.php
│   ├── med.php
│   ├── vitals.php
│   ├── mood.php
│   ├── ai.php
│   ├── report.php
│   ├── caregiver.php
│   └── wellness.php
├── public/
│   ├── login.php
│   ├── register.php
│   └── logout.php
├── sql/
│   └── care4mom.sql
├── index.php
├── dashboard.php
└── TOOLS.php
```

---

## 🗄️ Step 3: Database Setup

### A. Create Database in cPanel
1. **Navigate to "MySQL Databases"** in cPanel
2. **Create New Database**: `outsrglr_Momcare` (if not exists)
3. **Create User**: `outsrglr_Momcare` with password `ethanJ#2015`
4. **Add User to Database** with ALL PRIVILEGES

### B. Import Database Schema
1. **Go to phpMyAdmin** in cPanel
2. **Select database** `outsrglr_Momcare`
3. **Click "Import" tab**
4. **Upload** `sql/care4mom.sql` file
5. **Execute** to create all tables

### C. Verify Tables Created
Expected tables:
- `users` (3 demo accounts)
- `symptoms`
- `medications`
- `vitals`
- `mood_logs`
- `ai_alerts`
- `error_logs`
- `caregiver_communications`
- `user_images`
- `medication_schedule`

---

## 📤 Step 4: File Upload Methods

### Method A: File Manager (Recommended for iPhone)
1. **Open cPanel File Manager**
2. **Navigate to** `public_html/`
3. **Create folder** `care4mom`
4. **Upload files** by dragging from your device or using "Upload" button
5. **Extract** if uploading as ZIP file

### Method B: Using GitHub (If Available)
1. **Access terminal** in cPanel (if available)
2. **Navigate to** `public_html/`
3. **Clone repository**: `git clone https://github.com/PCFLSINC/Care4Mom.git care4mom`
4. **Set permissions** for web server access

### Method C: FTP Client on iPhone
1. **Download FTP app** (like "FTP Manager" or "Documents by Readdle")
2. **Setup FTP connection** with your hosting details
3. **Upload all files** to `public_html/care4mom/`

---

## ⚙️ Step 5: Configuration

### A. Update Database Configuration
Edit `includes/db.php`:
```php
// Production MySQL database
define('DB_TYPE', 'mysql');
define('DB_HOST', 'localhost');
define('DB_USER', 'outsrglr_Momcare');
define('DB_PASS', 'ethanJ#2015');
define('DB_NAME', 'outsrglr_Momcare');
```

### B. Set File Permissions
In cPanel File Manager:
- **Folders**: Set to `755` (rwxr-xr-x)
- **PHP files**: Set to `644` (rw-r--r--)
- **sql/ directory**: Set to `755` for database file storage
- **assets/ directory**: Set to `755` for uploads

### C. Configure Error Logging
1. **Create** `logs/` directory in your care4mom folder
2. **Set permissions** to `777` for error logging
3. **Test error logging** by accessing a non-existent page

---

## 🌐 Step 6: Access & Testing

### A. Initial Access
1. **Open browser** and navigate to: `https://your-domain.com/care4mom/`
2. **You should see** the Care4Mom landing page
3. **Test registration/login** with demo accounts

### B. Demo Account Testing
**Patient Account:**
- Username: `patient1`
- Password: `password`

**Doctor Account:**
- Username: `admin`
- Password: `password`

**Caregiver Account:**
- Username: `caregiver1`
- Password: `password`

### C. Core Functionality Testing
1. **Login** with patient account
2. **Navigate to Symptoms** module
3. **Log a test symptom** (e.g., "Dizziness", severity 5)
4. **Verify** symptom appears in history
5. **Test** emergency contact features
6. **Check** all navigation links work

---

## 🔧 Step 7: Production Optimizations

### A. Security Hardening
1. **Remove or secure** `TOOLS.php` (admin tools)
2. **Update** default passwords for demo accounts
3. **Enable HTTPS** if available
4. **Set up** regular database backups

### B. Performance Optimization
1. **Enable compression** in cPanel
2. **Configure caching** if available
3. **Optimize images** in assets/images/
4. **Monitor database** performance

### C. SSL Certificate Setup
1. **Go to "SSL/TLS"** in cPanel
2. **Enable "Let's Encrypt"** for free SSL
3. **Verify HTTPS** access works
4. **Update links** to use HTTPS

---

## 📱 Step 8: Mobile Testing from iPhone

### Complete Mobile Workflow Test:
1. **Open Safari** on iPhone
2. **Navigate to** your deployed Care4Mom site
3. **Test responsive design**:
   - Large text toggle works
   - High contrast mode functions
   - Navigation is touch-friendly
   - Forms are easy to fill
4. **Test core features**:
   - User registration
   - Login process
   - Symptom logging
   - History viewing
   - Emergency contacts (one-tap calling)

---

## 🆘 Troubleshooting Guide

### Common Issues & Solutions:

#### Database Connection Errors
**Problem**: "Connection failed" message
**Solution**: 
- Verify database credentials in `includes/db.php`
- Check if database exists in cPanel
- Ensure user has privileges

#### CSS/JS Not Loading
**Problem**: Styling broken or JavaScript not working
**Solution**:
- Check file permissions (should be 644)
- Verify relative paths in `includes/header.php`
- Clear browser cache

#### File Upload Issues
**Problem**: Cannot upload files through cPanel
**Solution**:
- Check available disk space
- Verify file size limits
- Use ZIP upload for multiple files

#### PHP Errors
**Problem**: White screen or PHP errors
**Solution**:
- Check PHP error logs in cPanel
- Verify PHP version (needs 8.0+)
- Check file permissions

---

## 📞 Support & Maintenance

### Admin Tools Access
- **URL**: `https://your-domain.com/care4mom/TOOLS.php`
- **Password**: `079777`
- **Features**: Database monitoring, health checks, error logs

### Regular Maintenance Tasks:
1. **Weekly**: Check error logs and database size
2. **Monthly**: Update user accounts and clean old data
3. **Quarterly**: Review security and backup database

### Backup Strategy:
1. **Database**: Export via phpMyAdmin weekly
2. **Files**: Download entire care4mom folder monthly
3. **Store backups** in cloud storage (iCloud, Google Drive)

---

## ✅ Deployment Checklist

### Pre-Launch Verification:
- [ ] Database created and schema imported
- [ ] All files uploaded to correct directories
- [ ] File permissions set correctly
- [ ] Database configuration updated
- [ ] Demo accounts working
- [ ] Core symptom tracking functional
- [ ] Mobile responsiveness confirmed
- [ ] SSL certificate enabled
- [ ] Error logging working
- [ ] Admin tools secured

### Post-Launch Tasks:
- [ ] Test all user workflows
- [ ] Set up monitoring/backups
- [ ] Update demo account passwords
- [ ] Train users on the system
- [ ] Plan regular maintenance schedule

---

## 🎯 Success Criteria

### Application is Successfully Deployed When:
✅ **Landing page** loads without errors  
✅ **User registration** creates new accounts  
✅ **Login system** authenticates correctly  
✅ **Symptom tracking** saves and displays data  
✅ **Navigation** works on all devices  
✅ **Emergency contacts** function properly  
✅ **Database** stores all information correctly  
✅ **Mobile experience** is touch-friendly  
✅ **SSL certificate** secures all communications  

---

## 📧 Final Notes

This deployment guide ensures the Care4Mom application will be fully functional on your cPanel hosting environment. The application has been thoroughly tested and all core features are working correctly.

**Support Contact**: For technical issues during deployment, refer to the troubleshooting section or contact your hosting provider's support team.

**Application Version**: 1.0 - Production Ready  
**Last Updated**: August 16, 2025  
**Compatible Hosting**: Any cPanel hosting with PHP 8.0+ and MySQL 5.7+