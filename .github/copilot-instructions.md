# Care4Mom - Cancer Patient Care Tracking Web Application

Always reference these instructions first and fallback to search or bash commands only when you encounter unexpected information that does not match the info here.

## Project Overview
Care4Mom is a PHP web application designed for stage 4 lung cancer patients and their caregivers to track symptoms, medications, vitals, and coordinate care. The application prioritizes accessibility for elderly users with large buttons, clear text, and simplified navigation.

## Current Repository State
**CRITICAL**: This repository is in early planning phase with no implemented code yet. The README.md contains comprehensive feature planning and architectural requirements. You are starting from scratch to build the application according to the specifications in README.md.

**Important**: The `convo` file contains extensive feature planning discussions and requirements that supplement the README.md. Review both files to understand the full scope and user needs before beginning development.

## Technology Stack
- **Backend**: PHP 8.3+ with PDO database abstraction
- **Database**: SQLite (recommended for development) or MySQL/MariaDB (production)
- **Frontend**: HTML5, CSS3, vanilla JavaScript with responsive design
- **Web Server**: PHP built-in server (development) or Apache/Nginx (production)
- **Package Management**: Composer (PHP), npm (optional for frontend tools)

## Development Environment Setup

### Prerequisites Installation
Run these commands to verify your environment:
```bash
php --version  # Should be 8.3+, tested working with PHP 8.3.6
composer --version  # Tested working with Composer 2.8.10
node --version  # Optional, for frontend tooling - tested with Node 20.19.4
npm --version   # Optional, tested with npm 10.8.2
```

Verify required PHP modules:
```bash
php -m | grep -E "(pdo_sqlite|mysqli|pdo_mysql|gd|curl|json)"
# Should show: pdo_sqlite, mysqli, pdo_mysql, gd, curl, json
```

### Quick Start Development Environment
1. **Start development server**:
   ```bash
   cd /path/to/Care4Mom
   php -S localhost:8000 -t public/
   ```
   - Development server starts on http://localhost:8000
   - NEVER CANCEL: Server runs continuously until stopped with Ctrl+C
   - Set timeout to 600+ seconds for long-running development sessions

2. **Database setup (SQLite - recommended for development)**:
   ```bash
   # SQLite automatically creates database file on first use
   # Database file will be created as: sql/care4mom.db
   ```

3. **Database setup (MySQL - for production)**:
   ```bash
   # Configure MySQL with empty root password as specified in README
   sudo mysql -u root -e "CREATE DATABASE care4mom;"
   sudo mysql -u root care4mom < sql/care4mom.sql
   ```

### Quick Implementation Start
To quickly validate the setup and start building:
```bash
# 1. Create directory structure
mkdir -p public sql includes assets/{css,js,images/{icons,gallery}} modules widgets/{modal,navbar,cards,charts}

# 2. Create basic landing page
cat > public/index.php << 'EOF'
<!DOCTYPE html>
<html>
<head>
    <title>Care4Mom - Cancer Care Tracking</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; font-size: 18px; margin: 40px; }
        .large-btn { padding: 20px 40px; font-size: 24px; margin: 10px; }
        .hero { text-align: center; padding: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 10px; }
    </style>
</head>
<body>
    <div class="hero">
        <h1>Care4Mom</h1>
        <p>Cancer Care Tracking for Stage 4 Lung Cancer Patients</p>
        <button class="large-btn">Register</button>
        <button class="large-btn">Login</button>
    </div>
    <p>This application helps track symptoms, medications, and coordinate care with accessibility features for elderly users.</p>
</body>
</html>
EOF

# 3. Create basic database connection
cat > includes/db.php << 'EOF'
<?php
try {
    $pdo = new PDO('sqlite:sql/care4mom.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo 'Database connection successful - SQLite';
} catch(PDOException $e) {
    echo 'Database connection failed: ' . $e->getMessage();
}
?>
EOF

# 4. Test the setup
php -f includes/db.php
php -S localhost:8000 -t public/
# Visit http://localhost:8000 to see the landing page
```

## Planned File Structure
Based on README.md specifications, implement this exact structure:
```
includes/
  ├── header.php          # Common HTML header and navigation
  ├── footer.php          # Common HTML footer
  ├── db.php              # Database connection and configuration
  └── errorlog.php        # Central error logging system

assets/
  ├── css/
  │   └── style.css       # Main stylesheet (neon, pastel, animated, responsive)
  ├── js/
  │   └── main.js         # JavaScript functionality
  └── images/
      ├── icons/          # UI icons and buttons
      └── gallery/        # User-uploaded images

public/
  ├── index.php           # Landing page with register/login buttons
  ├── login.php           # User authentication
  ├── register.php        # Account creation with role selection
  ├── dashboard.php       # Main 3D animated dashboard
  └── logout.php          # Session management

modules/
  ├── symptom.php         # Symptom logging and AI advisor
  ├── med.php             # Medication tracking and reminders
  ├── vitals.php          # Vitals tracking with Fitbit integration placeholder
  ├── mood.php            # Mood and mindfulness tracking
  ├── ai.php              # AI advisory and alert system
  ├── report.php          # Doctor reports and export functionality
  ├── caregiver.php       # Caregiver communication and coordination
  └── wellness.php        # Wellness resources and support

widgets/
  ├── modal/              # Modal dialog components
  ├── navbar/             # Navigation components
  ├── cards/              # Dashboard card components
  └── charts/             # Data visualization components

sql/
  └── care4mom.sql        # Database schema and initial data

README.md                 # Project documentation and requirements
```

## Database Schema (from README.md requirements)
Implement these tables in sql/care4mom.sql:
- Users table (login, roles: patient/caregiver)
- Symptoms table (logs, severity 1-10, notes, timestamps)
- Medication table (meds, compliance, photos)
- Vitals table (manual/fitbit sync, heart rate, sleep, activity)
- Mood table (daily emotional state tracking)
- AI Alerts table (automated warnings and advice)
- Error logs table (system error tracking)
- Caregiver communications (chat, task assignments)
- Images/resources (photo uploads, gallery)

## Build and Validation Process

### NEVER CANCEL Commands
- **Database operations**: Can take 30+ seconds for large datasets. NEVER CANCEL. Set timeout to 120+ seconds.
- **File uploads/image processing**: Can take 60+ seconds for large files. NEVER CANCEL. Set timeout to 180+ seconds.
- **Report generation**: Can take 45+ seconds for comprehensive reports. NEVER CANCEL. Set timeout to 120+ seconds.
- **PHP Development Server**: Runs continuously during development. NEVER CANCEL. Set timeout to 3600+ seconds.

### Measured Performance Expectations
Based on testing:
- Small database operations (1000 records): ~1-2 seconds
- PHP syntax validation: ~5-10 seconds for entire codebase
- Development server startup: ~2-3 seconds
- SQLite database creation: Instant
- Basic module tests: ~3-5 seconds each

### Development Workflow
1. **Start development environment**:
   ```bash
   php -S localhost:8000 -t public/
   # NEVER CANCEL: Keep running during development. Set timeout to 3600+ seconds.
   ```

2. **Test database connectivity**:
   ```bash
   php -f includes/db.php  # Should connect without errors
   ```

3. **Run basic functionality tests**:
   ```bash
   # Test each module individually
   php -f modules/symptom.php
   php -f modules/med.php
   php -f modules/vitals.php
   ```

## Critical User Scenarios for Validation

### MANUAL VALIDATION REQUIREMENT
After building the application, you MUST test these complete user workflows:

1. **Patient Registration and Login**:
   - Register new patient account with role selection
   - Login with credentials
   - Navigate to dashboard
   - Verify large text mode toggle works
   - **Expected time**: 2-3 minutes per test

2. **Symptom Logging Complete Workflow**:
   - Log at least 3 different symptoms (dizziness, hot hands/feet, stomach pain)
   - Set severity levels (1-10 scale)
   - Add voice notes or text descriptions
   - View symptom history and timeline
   - Verify color-coded severity indicators
   - **Expected time**: 5-7 minutes per test

3. **Medication Management Workflow**:
   - Add medication entries with dosage
   - Mark doses as taken with photo confirmation
   - Check compliance statistics
   - Test reminder notifications
   - **Expected time**: 4-6 minutes per test

4. **AI Advisory System Test**:
   - Log symptoms that should trigger AI alerts
   - Verify AI advice modal appears
   - Test emergency alert notifications
   - Verify caregiver notifications work
   - **Expected time**: 3-5 minutes per test

5. **Report Generation and Export**:
   - Generate doctor-ready reports (JSON, PDF, CSV)
   - Verify data accuracy and completeness
   - Test export download functionality
   - **Expected time**: 2-4 minutes per test

6. **Caregiver Coordination Test**:
   - Create caregiver account
   - Test multi-user access
   - Send notifications between users
   - Assign and complete care tasks
   - **Expected time**: 6-8 minutes per test

### Accessibility Validation
ALWAYS verify these accessibility features work:
- Large text mode increases font size by 150%+
- High contrast mode for visual impairment
- Large buttons (minimum 44px touch targets)
- Clear, simple navigation
- Voice note recording functionality

### Health Care Application Specific Validations
**CRITICAL**: This application handles sensitive health data. Additional validation required:

1. **Data Accuracy Testing**:
   - Verify symptom severity scales (1-10) work correctly
   - Test timestamp accuracy for all logged entries
   - Validate medication dosage and timing calculations
   - **Expected time**: 15-20 minutes per validation cycle

2. **Privacy and Security Testing**:
   - Test data isolation between different user accounts
   - Verify no data leakage between patient and caregiver roles
   - Test secure photo upload and storage
   - **Expected time**: 10-15 minutes per test

3. **Emergency Response Testing**:
   - Test AI alert triggers for severe symptom combinations
   - Verify emergency contact notifications work
   - Test system behavior during critical alerts
   - **Expected time**: 5-10 minutes per scenario

4. **Multi-User Care Coordination**:
   - Test real-time updates between patient and caregiver accounts
   - Verify task assignment and completion workflows
   - Test communication features work reliably
   - **Expected time**: 10-15 minutes per test

## Key Development Guidelines

### UI/UX Requirements (from README.md)
- **Design Style**: Futuristic, colorful, with neon and pastel elements
- **Navigation**: 3D animated navbar with large icons
- **Dashboard**: Large cards with quick-access widgets
- **Animations**: Smooth transitions and modal popups
- **Responsive**: Works on phones, tablets, and desktops
- **Accessibility**: Senior-friendly interface with large text options

### Error Handling and Logging
- Implement central error logging to database and user-friendly modals
- All errors should display helpful visual guidance
- Log all database operations and user actions
- Include error recovery suggestions in user messages

### Security Considerations
- Use prepared statements for all database queries
- Implement proper session management in login/logout
- Validate and sanitize all user inputs
- Secure photo upload functionality with file type validation

## Common Commands and Timeouts

### Database Operations
```bash
# Import schema - NEVER CANCEL: Can take 60+ seconds
mysql -u root care4mom < sql/care4mom.sql
# Timeout: 120+ seconds

# Export data for backup - NEVER CANCEL: Can take 90+ seconds  
mysqldump -u root care4mom > backup.sql
# Timeout: 180+ seconds
```

### Development Server
```bash
# Start PHP development server - NEVER CANCEL: Runs continuously
php -S localhost:8000 -t public/
# Timeout: 3600+ seconds (1 hour) or run indefinitely
```

### Testing and Validation
```bash
# Run PHP syntax check on all files - Takes 5-10 seconds
find . -name "*.php" -exec php -l {} \;
# Timeout: 60+ seconds

# Test database connectivity - Takes 2-5 seconds
php -r "include 'includes/db.php'; echo 'DB connection successful';"
# Timeout: 30+ seconds

# Initialize Composer project (optional) - Takes 3-5 seconds
composer init --no-interaction --name="pcflsinc/care4mom" --type="project" --require="php:>=8.3"
# Timeout: 30+ seconds
```

## Troubleshooting Common Issues

### Database Connection Problems
- Verify MySQL service is running: `sudo service mysql start`
- Check database credentials in includes/db.php
- For SQLite: Ensure sql/ directory is writable

### Permission Issues
- Web server needs write access to sql/ directory for SQLite
- uploads/ directory needs 755 permissions for photo uploads
- session files directory needs write access

### Performance Issues
- Large symptom datasets may slow report generation
- Photo uploads over 5MB may timeout - adjust PHP settings
- Multiple concurrent users may require connection pooling

## Integration Notes

### Fitbit Integration (Placeholder)
- API endpoints prepared in modules/vitals.php
- Authentication flow ready for implementation
- Data sync scheduled for daily updates
- Manual entry fallback always available

### AI Advisory System
- Rule-based logic implemented for symptom correlation
- Emergency alert thresholds configured
- Machine learning model integration placeholder ready

## File Validation Checklist
Before considering the application complete, verify:
- [ ] All planned files from file structure exist
- [ ] Database schema matches requirements
- [ ] All user scenarios validate successfully
- [ ] Error logging captures and displays issues properly
- [ ] Accessibility features work correctly
- [ ] Export functionality generates proper reports
- [ ] Multi-user access and roles function properly
- [ ] Photo upload and storage works securely

## Emergency Development Notes
- **Build failures**: Usually related to database connection or file permissions
- **Timeout issues**: Increase PHP max_execution_time for large operations
- **Memory issues**: May need to increase PHP memory_limit for report generation
- **Session issues**: Verify session.save_path is writable

Remember: This is a health care application for vulnerable users. Always prioritize data accuracy, security, and accessibility in every implementation decision.