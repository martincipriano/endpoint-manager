# Changelog

## Version 1.0.0 - Initial Release

### Features
- Manage WordPress core REST API endpoints
- Static endpoints support
- Simple toggle interface for enabling/disabling endpoints
- Endpoints organized by namespace
- Security enhancement by disabling unused endpoints

### Differences from Pro Version

**Removed Features (Pro Only):**
- Endpoint filtering (by status, type, namespace)
- Security logs and blocked request tracking
- Export logs to CSV
- Endpoint preview functionality
- Endpoint summary statistics
- Support for all namespaces (plugin/theme endpoints)
- Dynamic/regex endpoint support
- License management system

**Kept Features (Free Version):**
- WordPress core endpoint management
- Static endpoint support
- Basic toggle interface
- Namespace organization

### Technical Changes
- Removed `class-license-manager.php`
- Removed `class-license-validator.php`
- Removed `class-license-page.php`
- Simplified admin interface (no sidebar, no filters)
- Reduced JavaScript complexity (removed filtering logic)
- Simplified CSS (removed pro-feature styling)
- Main plugin file reduced from 1,221 lines to 493 lines (~60% reduction)

### File Structure
```
rest-api-manager/
├── assets/
│   ├── css/
│   │   └── admin.css
│   ├── images/
│   │   ├── arrow-drop-down.svg
│   │   ├── arrow-drop-up.svg
│   │   └── (other SVGs)
│   └── js/
│       └── admin.js
├── includes/
│   └── class-feature-manager.php
├── .gitignore
├── CHANGELOG.md
├── readme.md
├── readme.txt
├── rest-api-manager.php
└── uninstall.php
```
