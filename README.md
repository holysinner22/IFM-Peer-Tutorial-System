
````md
# 🎓 **IFM Peer Tutoring System ** 
A role-based academic support platform built for the **Institute of Finance Management (IFM)** to connect **students**, **tutors**, and **admins**.  
The system enables learners to request sessions, tutors to host academic help, and admins to manage the overall learning workflow.

This README includes: installation, database structure, features, folder layout, credentials, and troubleshooting.

---

# 📌 Features (Based on Database Design)
### 👨‍🎓 **Students**
- Register, login, and verify account  
- Request tutoring sessions  
- Join available sessions  
- Receive notifications  
- Rate tutors after sessions

### 👨‍🏫 **Tutors**
- Register and configure subjects they teach  
- Accept or reject session requests  
- Host tutoring sessions  
- Manage session capacity  
- Receive system notifications  
- View feedback from learners

### 🛡️ **Admin**
- Full user management  
- Activate / suspend / deactivate users  
- Assign roles (student / tutor / admin)  
- Monitor tutoring activities  
- Handle session moderation

---

# 🛠 Technologies Used
| Component | Technology |
|----------|------------|
| Backend  | **PHP (Native PHP, mysqli)** |
| Frontend | HTML, CSS, Bootstrap, JavaScript |
| Database | **MySQL / MariaDB** |
| Server   | Apache (XAMPP, LAMPP, MAMP) |
| Authentication | Hashed passwords (bcrypt) |
| User Roles | student, tutor, admin |

---

# 🚀 Installation Guide (How to Run the System)

## **1️⃣ Clone/Download the Project**
```bash
git clone https://github.com/holysinner22/IFM-Peer-Tutorial-System.git
````

Or download ZIP → extract.

---

## **2️⃣ Move Project to Server Directory**

### **Windows (XAMPP)**

```
C:/xampp/htdocs/IFM-Peer-Tutorial-System
```

### **Linux (LAMPP)**

```bash
sudo cp -r IFM-Peer-Tutorial-System /opt/lampp/htdocs/
```

### **macOS (MAMP)**

```
/Applications/MAMP/htdocs/
```

---

## **3️⃣ Create the Database**

1. Start Apache + MySQL
2. Open phpMyAdmin
3. Create database:

```
peer_tutoring
```

4. Import the provided SQL file (your database):

* Contains tables: users, tutors, subjects, sessions, notifications, roles, feedback, etc.
* Includes real sample data.

---

## **4️⃣ Configure Database Connection**

Your system uses:

### 📄 `config.php`

```php
<?php
$host = "localhost";
$user = "root";
$pass = "";   // set your MySQL password
$db   = "peer_tutoring";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
?>
```

If your MySQL password is NOT empty → update `$pass`.

---

## **5️⃣ Run the System**

Open browser and visit:

```
http://localhost/IFM-Peer-Tutorial-System/
```

Common entry pages:

* `/login.php`
* `/register.php`
* `/admin/`
* `/tutor/`
* `/student/`

---

# 📂 Database Structure (Accurate to your SQL dump)

## 🧑‍🎓 `users` table

Stores personal info + hashed password.

| Column           | Type    | Notes                                      |
| ---------------- | ------- | ------------------------------------------ |
| id               | INT     | Primary key                                |
| first_name       | VARCHAR |                                            |
| last_name        | VARCHAR |                                            |
| email            | VARCHAR | **Unique**                                 |
| phone            | VARCHAR |                                            |
| degree_programme | VARCHAR |                                            |
| year_of_study    | INT     |                                            |
| password_hash    | VARCHAR | bcrypt                                     |
| status           | ENUM    | pending / active / suspended / deactivated |
| profile_pic      | VARCHAR | file name                                  |

---

## 🛂 `user_roles`

* A user can have multiple roles (student + tutor)
* Unique constraint: `(user_id, role)`

---

## 📚 `tutor_subjects`

Tutors can attach:

* subject
* year_of_study
* degree_programme

---

## 🗓️ `sessions`

Stores tutoring sessions.

| Status Options |
| -------------- |
| requested      |
| assigned       |
| accepted       |
| rejected       |
| cancelled      |
| completed      |

Includes:

* tutor_id
* learner_id
* capacity
* timestamps

---

## 📝 `session_registrations`

Stores students who join a session.
Unique constraint prevents double registration.

---

## 🔔 `notifications`

Stores messages for users.

---

## ⭐ `feedback`

Students can rate tutors (1–5 stars) + comment.

---

# 🌐 Folder Structure (Typical)

```
IFM-Peer-Tutorial-System/
│── admin/
│── tutor/
│── student/
│── config.php
│── login.php
│── register.php
│── assets/
│── uploads/
│── README.md
│── peer_tutoring.sql
```

---

# 🔑 Default Accounts (From Your SQL Dump)

| Role    | Email                                     | Status |
| ------- | ----------------------------------------- | ------ |
| Admin   | [admin@ifm.ac.tz](mailto:admin@ifm.ac.tz) | active |
| Tutor   | [eugen@ifm.ac.tz](mailto:eugen@ifm.ac.tz) | active |
| Student | [kemmy@ifm.ac.tz](mailto:kemmy@ifm.ac.tz) | active |
| Student | [dave@ifm.ac.tz](mailto:dave@ifm.ac.tz)   | active |

Password hashes are bcrypt — use your known passwords.

---

# 🧪 Testing the System

1. Try registering a new IFM student
2. Login as tutor and accept a session
3. Login as learner and join a session
4. Admin can activate/suspend accounts
5. Leave feedback after session completion

---

# 🛠 Troubleshooting

### "Database Connection Failed"

Check:

* Database name = `peer_tutoring`
* MySQL user = root
* Password = (blank for XAMPP)

---

### CSS/JS not loading

You must visit via:

✔ `http://localhost/IFM-Peer-Tutorial-System/`
NOT by opening PHP files directly.

---

### 500 Internal Server Error

Enable debugging:

```php
ini_set("display_errors", 1);
error_reporting(E_ALL);
```

---

# 🤝 Contributing

1. Fork repo
2. Create branch
3. Commit changes
4. Open pull request

---

# 👨‍💻 Author

** (Holysinner)**
GitHub: [https://github.com/holysinner22](https://github.com/holysinner22)

---

# 📜 License

This project is proprietary and intended for academic use at IFM.



