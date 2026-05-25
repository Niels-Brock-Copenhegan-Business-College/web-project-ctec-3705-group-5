# University Course Portal
### Built with Slim 4 + PHP + MySQL (XAMPP)

---

## Folder Structure

```
university_course_portal/
├── database/
│   └── schema.sql          ← Run this first in phpMyAdmin
├── public/
│   ├── index.php           ← Entry point
│   └── .htaccess           ← URL rewriting
├── src/
│   ├── container.php       ← DI container (DB + Twig)
│   ├── routes.php          ← All application routes
│   ├── Middleware/
│   │   └── AuthMiddleware.php
│   ├── Models/
│   │   ├── Programme.php
│   │   ├── Module.php
│   │   └── Models.php      ← Staff, Interest, Admin
│   └── Controllers/
│       ├── Student/
│       │   ├── HomeController.php
│       │   └── StudentControllers.php  ← Programme + Interest
│       └── Admin/
│           ├── AuthController.php
│           └── AdminControllers.php    ← Dashboard, Programmes, Modules, Staff, Mailing
├── templates/
│   ├── student/
│   │   ├── base.twig
│   │   ├── home.twig
│   │   └── programme.twig
│   └── admin/
│       ├── base.twig
│       ├── login.twig
│       ├── dashboard.twig
│       ├── programmes/  (index.twig, form.twig)
│       ├── modules/     (index.twig, form.twig)
│       ├── staff/       (index.twig, form.twig)
│       └── mailing/     (index.twig)
├── .env                    ← DB credentials
└── composer.json
```

---

## Setup Steps

### 1. Place files in XAMPP

Copy the entire `university_course_portal/` folder into:
```
C:\xampp\htdocs\university_course_portal\
```

### 2. Install dependencies

Open a terminal in the project folder and run:
```bash
composer install
```
> If you don't have Composer: https://getcomposer.org/download/

### 3. Create the database

- Start XAMPP (Apache + MySQL)
- Open **phpMyAdmin** → http://localhost/phpmyadmin
- Click **Import** → choose `database/schema.sql` → Go

### 4. Configure .env

Open `.env` and update if needed:
```
DB_USER=root
DB_PASS=          ← leave blank for default XAMPP
DB_NAME=university_course_portal
```

### 5. Enable mod_rewrite in XAMPP

In `C:\xampp\apache\conf\httpd.conf`, find:
```
#LoadModule rewrite_module modules/mod_rewrite.so
```
Remove the `#` to uncomment it.

Also ensure `AllowOverride All` is set for your htdocs directory.

### 6. Access the application

| URL | Description |
|-----|-------------|
| http://localhost/university_course_portal/public/ | Student-facing site |
| http://localhost/university_course_portal/public/admin/login | Admin login |

### Default Admin Credentials
- **Username:** `admin`
- **Password:** `Admin@1234`

> ⚠️ Change this password after first login!

---

## Features Implemented

### Student Interface
- Browse all published programmes with search & filter by level
- View programme details: description, modules per year, staff
- Register interest (with XSS sanitization + email validation)
- Duplicate interest prevention
- WCAG2 compliant: skip links, ARIA labels, keyboard navigation, focus indicators
- Fully responsive / mobile-friendly

### Admin Interface
- Secure login with session-based authentication
- Role-based access (admin/superadmin)
- Dashboard with live stats
- Full CRUD: Programmes, Modules, Staff
- Publish / unpublish programmes (draft system)
- Assign modules to programmes with year of study
- Assign programme/module leaders from staff
- View interest registrations, filter by programme
- Export mailing list as CSV
- Remove invalid/duplicate registrations

### Security
- Passwords hashed with `password_hash()` (bcrypt)
- Session regeneration on login
- XSS prevention via `htmlspecialchars()` on all inputs
- Email sanitization via `FILTER_SANITIZE_EMAIL`
- Auth middleware on all `/admin/*` routes
- SQL injection prevention via Eloquent ORM (parameterized queries)
