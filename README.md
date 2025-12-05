# PuzzlingCRM

> A complete CRM and Project Management solution for Social Marketing agencies

![Version](https://img.shields.io/badge/version-2.1.13-blue)
![License](https://img.shields.io/badge/license-GPL%20v2%2B-green)
![WordPress](https://img.shields.io/badge/WordPress-Compatible-brightgreen)

## 📋 Overview

**PuzzlingCRM** is an enterprise-grade WordPress plugin that combines powerful CRM capabilities with comprehensive project management tools, specifically designed for social marketing agencies. It provides a complete solution to manage clients, projects, teams, and communication all within your WordPress dashboard.

## ✨ Features

### CRM Management
- **Client Management** - Organize and track all client information in one place
- **Contact Management** - Maintain detailed contact information and communication history
- **Activity Timeline** - Track all interactions and activities with clients
- **User Profiles** - Comprehensive user profile management with role-based access

### Project Management
- **Project Dashboard** - Overview of all projects with key metrics
- **Kanban Board** - Visual project management with drag-and-drop task management
- **Agile/Scrum Board** - Sprint planning and agile workflow management
- **Task Templates** - Pre-built templates for consistent task management
- **Time Tracking** - Monitor time spent on tasks and projects

### Communication & Collaboration
- **Team Chat** - Built-in messaging system for team collaboration
- **Email Handler** - Integrated email management and communication
- **SMS Service** - SMS notification and communication capabilities
- **Smart Reminders** - Intelligent reminder system for tasks and deadlines
- **Activity Tracking** - Monitor team activities and progress

### Advanced Features
- **Advanced Analytics** - In-depth reporting and analytics dashboard
- **Data Encryption** - Secure data storage with encryption support
- **PDF Generation** - Generate reports and documents in PDF format
- **Elasticsearch Integration** - High-performance search capabilities
- **PWA Support** - Progressive Web App functionality for offline access
- **White Label** - Full white-label customization options
- **WebSocket Handler** - Real-time communication and updates
- **Automation** - Workflow automation and task automation
- **License Management** - Comprehensive license key management system
- **Session Management** - Secure session handling
- **Cache Optimization** - Performance optimization through intelligent caching
- **Database Optimization** - Automatic database optimization
- **Document Management** - Upload, organize, and manage documents

### Customization & Integration
- **Custom Post Types** - Flexible custom post type management
- **Shortcode Manager** - Easy shortcode integration throughout your site
- **Field Security** - Advanced field-level security controls
- **Role-Based Access** - Granular role and permission management
- **Frontend Dashboard** - Custom frontend dashboard for clients
- **Elasticsearch Integration** - Enterprise search capabilities
- **Form Handler** - Flexible form building and submission handling

## 🚀 Installation

### Requirements
- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.7 or higher

### Steps

1. **Download the Plugin**
   - Clone the repository or download as ZIP
   ```bash
   git clone https://github.com/arsalanarghavan/puzzlingcrm.git
   ```

2. **Upload to WordPress**
   - Upload the `puzzlingcrm` folder to `/wp-content/plugins/`
   - Or use WordPress admin: Plugins → Add New → Upload Plugin

3. **Activate the Plugin**
   - Go to WordPress admin dashboard
   - Navigate to Plugins
   - Find "PuzzlingCRM" and click "Activate"

4. **Initial Setup**
   - Navigate to the PuzzlingCRM menu in your WordPress admin
   - Follow the setup wizard
   - Configure your settings and preferences

## 📖 Documentation

### Quick Start Guide

#### Accessing PuzzlingCRM
- All PuzzlingCRM features are accessible from the main menu in WordPress admin
- The frontend dashboard is available for client access

#### Creating Your First Project
1. Navigate to PuzzlingCRM → Projects
2. Click "New Project"
3. Fill in project details
4. Add team members
5. Create tasks and set deadlines

#### Managing Clients
1. Go to PuzzlingCRM → Clients
2. Click "Add New Client"
3. Enter client information
4. Assign projects and team members
5. Track communications and activities

#### Using the Kanban Board
1. Open any project
2. Switch to "Kanban" view
3. Drag tasks between columns
4. Update task status with simple drag-and-drop
5. View task details by clicking on cards

### Plugin Structure

```
puzzlingcrm/
├── assets/              # Stylesheets and JavaScript
│   ├── css/            # CSS files and styling
│   ├── js/             # JavaScript functionality
│   ├── images/         # Image assets
│   └── libs/           # Third-party libraries
├── includes/           # Core plugin classes
│   ├── class-*.php     # Individual feature classes
│   ├── ajax/           # AJAX handlers
│   ├── components/     # Reusable components
│   └── helpers/        # Helper functions
├── templates/          # HTML templates
│   ├── dashboard/      # Dashboard templates
│   └── components/     # Component templates
├── languages/          # Localization files
├── puzzlingcrm.php     # Main plugin file
└── README.md           # This file
```

## 🔧 Configuration

### Settings

Access plugin settings through **PuzzlingCRM → Settings**:

- **General Settings** - Basic plugin configuration
- **Email Settings** - Configure email notifications
- **SMS Settings** - Set up SMS service credentials
- **API Settings** - Configure API keys and integrations
- **Security Settings** - Set security options and encryption
- **White Label Settings** - Customize branding

### User Roles

PuzzlingCRM includes the following default roles:
- **Administrator** - Full access to all features
- **Manager** - Can manage projects and team
- **Team Member** - Can view and update assigned tasks
- **Client** - Limited access to view own projects

## 🔐 Security

- **Data Encryption** - All sensitive data is encrypted
- **Field-Level Security** - Control access to specific fields
- **Role-Based Access Control** - Granular permission management
- **Session Management** - Secure session handling
- **Data Validation** - All inputs are validated
- **HTTPS Support** - Full SSL/TLS support

## 📦 Dependencies

The plugin uses several third-party libraries:
- **SweetAlert2** - Beautiful alerts and modals
- **Elasticsearch** - High-performance search
- **WebSocket** - Real-time communication
- **PDF Libraries** - Document generation

## 🌍 Localization

PuzzlingCRM supports multiple languages:
- **English** (en_US)
- **Persian/Farsi** (fa_IR)

To add more languages, create new `.po` and `.mo` files in the `/languages` directory.

## 🤝 Contributing

We welcome contributions! Here's how you can help:

1. **Report Bugs** - Create an issue with detailed information
2. **Suggest Features** - Share your ideas for improvements
3. **Submit Code** - Fork, make changes, and create a pull request

### Development Setup

```bash
# Clone the repository
git clone https://github.com/arsalanarghavan/puzzlingcrm.git

# Navigate to the directory
cd puzzlingcrm

# Make your changes
# Follow WordPress coding standards

# Test your changes
# Submit a pull request
```

## 📝 Changelog

### Version 2.1.13
- Latest stable release
- All 16 Enterprise Features complete
- Enhanced performance and stability
- Improved user interface

For detailed changelog, check the [releases page](https://github.com/arsalanarghavan/puzzlingcrm/releases).

## 🆘 Support

- **Documentation** - Visit [Puzzlingco.com](https://Puzzlingco.com/)
- **Author Website** - [ArsalanArghavan.ir](https://ArsalanArghavan.ir/)
- **GitHub Issues** - Report bugs and request features on [GitHub Issues](https://github.com/arsalanarghavan/puzzlingcrm/issues)
- **Email Support** - Contact through official website

## 📄 License

This plugin is licensed under the GNU General Public License v2.0 or later. See the [LICENSE](LICENSE) file for details.

```
PuzzlingCRM - A complete CRM and Project Management solution
Copyright (C) 2024 Arsalan Arghavan

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## 👨‍💻 Author

**Arsalan Arghavan**
- Website: [ArsalanArghavan.ir](https://ArsalanArghavan.ir/)
- GitHub: [@arsalanarghavan](https://github.com/arsalanarghavan)

## 🙏 Acknowledgments

Special thanks to:
- The WordPress community
- All contributors and supporters
- Our users for their valuable feedback

---

<div align="center">

**[Official Website](https://Puzzlingco.com/) • [Author](https://ArsalanArghavan.ir/) • [GitHub](https://github.com/arsalanarghavan/puzzlingcrm) • [Issues](https://github.com/arsalanarghavan/puzzlingcrm/issues)**

Made with ❤️ for social marketing agencies

</div>
