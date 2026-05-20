AIDLINK
A Messaging-Based Donation and Volunteer Coordination System

SETUP
1. Put the folder in: C:\xampp\htdocs\aidlink
2. Start Apache and MySQL in XAMPP.
3. Open phpMyAdmin and import: database/aidlink.sql
4. Open: http://localhost/aidlink/index.php

DATABASE CONFIG
Edit app/config.php if your MySQL credentials are different.
Current default:
- user: root
- password: access
- port: 3307
- database: aidlink

LOGIN ACCOUNTS
Admin: admin@aidlink.local / admin123
Volunteer Coordinator: staff@aidlink.local / admin123
Recipient: recipient@aidlink.local / admin123

MAIN FEATURES
- Aid request submission for food, water, medicine, clothing, school supplies, emergency relief, and volunteer support.
- Admin and volunteer coordinator dashboard.
- Request workflow: Pending, Approved, Preparing, Delivering, Completed, Rejected.
- Coordination queue for asynchronous request handling.
- Messenger for direct and group coordination.
- Notification bell with separated notification and messenger sounds.
- Profile settings, profile photo upload/remove, password change, recovery Q&A, and dark/light theme.
- XML export and browser-ready HTML reports.
- Regex name scanner and validation utilities.
- Weather API for planning delivery and volunteer activities.
- Local PowerShell/BAT scripts for backup and system report tasks.

SYLLABUS COVERAGE
Week 1: Integrative programming through connected modules.
Week 2: PHP, MySQL, XML, scripting, weather API, and messaging overview.
Week 3: OOP classes in the app folder.
Week 5: Message queue concepts through queued aid request events.
Week 6: Messaging implementation through direct and group chat.
Week 7: Messaging-based application through aid coordination workflow.
Week 9: XML export for aid records.
Week 10: XML parsing through generated XML records.
Week 11: HTML report transformation from structured records.
Week 13: Scripts for file and backup operations.
Week 14: System administration scripts and regex validation.
Week 15: Weather API and regex-based utilities.

SOUND FILES
Replace these anytime to change ringtones:
- public/assets/sounds/messenger.wav
- public/assets/sounds/notification.wav

PROJECT TITLE
AidLink: A Messaging-Based Donation and Volunteer Coordination System
