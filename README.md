To-Do List Web Application
A full-stack, secure task management web application built with native PHP, MySQL, and JavaScript, styled with a modern dark glassmorphism design.

🚀 Quick Start (XAMPP)
Start Services: Open your XAMPP Control Panel and start Apache and MySQL.

Setup Database: Go to http://localhost/phpmyadmin. Create a new database named todolist_db and import the database/schema.sql file.

Deploy Project: Move the entire project folder into your XAMPP server's root directory:

Bash
C:\xampp\htdocs\ToDoList\
Launch Site: Open your web browser and go to: http://localhost/ToDoList/

🔑 Default Credentials
Admin Access: Username: admin | Password: admin123

User Access: Register a new account directly from the login page screen.

📁 Core Architecture
config.php — Central app configuration, database connection via PDO, and security/session helpers.

index.php — System gateway that handles routing based on authentication state.

login.php & register.php — Clean, secure entry points featuring responsive validation and password strength assessment.

dashboard.php — Core workspace where users manage folders and tasks asynchronously via AJAX.

admin_panel.php — Security-guarded system dashboard for monitoring users and overseeing server metrics.

✨ Features
Folder Organization: Group tasks into collapsible containers or keep them unfiled.

No-Reload Experience: Add, delete, rename, or update task states instantly using asynchronous AJAX requests.

Live Telemetry: Tracks application metrics through status counters and real-time completion percentage metrics.

Enterprise Security: Built-in safeguards against SQL injection (PDO prepared statements), Cross-Site Scripting (XSS), Cross-Site Request Forgery (CSRF), and Session Fixation attacks. Passwords are fully hashed via Bcrypt (cost 12).
