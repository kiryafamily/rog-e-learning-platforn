# RAYS OF GRACE E-Learning Platform

A comprehensive e-learning platform for Rays of Grace Junior School, providing students with access to lessons, quizzes, and learning materials both online and offline.

## Features

- **User Authentication** - Secure registration and login system
- **Class-Based Content** - Lessons organized by class (P1-P7) and subject
- **Interactive Quizzes** - Test knowledge with instant feedback
- **Progress Tracking** - Monitor learning progress across lessons
- **Offline Downloads** - Download lessons for offline access (30-day access)
- **Free Previews** - Try lessons before subscribing
- **Admin Panel** - Upload and manage content
- **Activity Logging** - Track user actions for analytics

## Technology Stack

- **Backend**: PHP (PDO for database interactions)
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript
- **Server**: Apache (XAMPP)
- **Database Management**: phpMyAdmin

## Installation Guide
## 

### Prerequisites
- XAMPP
- Web browser
- Code editor

### Step 1: Install XAMPP
1. Download XAMPP compatible witht the operating system that you are using from [Apache Friends](https://www.apachefriends.org/)
2. Install with default settings
3. Launch XAMPP Control Panel
4. Start **Apache** and **MySQL** services

### Step 2: Set Up the Project
1. Navigate to `C:\xampp\htdocs\`
2. Create folder: `rog-e-learning-platform`
3. Copy all project files into this folder

### Step 3: Create the Database
1. Open browser: `http://localhost/phpmyadmin`
2. Click **New** to create database
3. Database name: `raysofgrace_db`
4. Collation: `utf8mb4_general_ci`
5. Click **Go**

### Step 4: Create Tables

Run these SQL statements in phpMyAdmin:

#### Users Table
Go on and create the followig tables
1. Users table
2. Lessons table
3. quiz_questions table
4. quiz_results table
5. progress table
6. subscripts table
7. downloads table
8. activity_log table

#### Access the system via http://localhost/rog-e-learning-platforn/ on your local host