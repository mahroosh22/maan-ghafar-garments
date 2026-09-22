# Maan Ghafar Garments

### Women's Fashion E-Commerce & Management Web Application

Maan Ghafar Garments is a PHP and MySQL-based women's fashion e-commerce and management web application designed to provide an organized platform for browsing women's clothing, managing products, handling customer accounts, and processing orders.

---

## 📌 Project Overview

The system provides separate functionality for customers and administrators.

### Customer Features

* Customer registration and login
* Browse women's fashion products
* View product details
* Add products to cart
* Update cart quantities
* Checkout and place orders
* View order history
* View order details
* Cancel eligible orders
* Manage customer profile
* Contact the store

### Admin Features

* Secure admin login
* Admin dashboard
* Manage products
* Manage categories
* View customers
* View customer details
* View and manage orders
* Update order status
* View contact messages
* Reply to customer messages

---

## 🛠️ Technologies Used

* **PHP**
* **MySQL**
* **HTML5**
* **CSS3**
* **JavaScript**
* **XAMPP**
* **phpMyAdmin**

---

## 📂 Project Structure

```text
maan-ghafar-garments/
│
├── admin/
├── assets/
│   └── css/
├── customer/
├── uploads/
│   ├── categories/
│   └── products/
├── config/
│   ├── database.php
│   └── database.example.php
│
├── about.php
├── cart.php
├── checkout.php
├── contact.php
├── index.php
├── login.php
├── logout.php
├── my-orders.php
├── place-order.php
├── product-details.php
├── products.php
├── register.php
└── README.md
```

> **Security:** `config/database.php` contains local database configuration and is intentionally excluded from GitHub using `.gitignore`. `database.example.php` is provided as a safe configuration template.

---

## 🗄️ Database

### Database Name

```text
maan_ghafar_garments
```

The application uses MySQL for storing:

* Customer accounts
* Product categories
* Products
* Orders
* Order items
* Payments
* Shipping information
* Contact messages
* Cart-related data
* Reviews
* Wishlist data
* Customer addresses

---

## 🚀 Local Installation

### Requirements

Before running the project, install:

* XAMPP
* PHP
* MySQL
* A web browser

### Setup

1. Start **Apache** and **MySQL** from XAMPP.

2. Copy the project into the XAMPP `htdocs` directory.

3. Create a MySQL database named:

```text
maan_ghafar_garments
```

4. Import the project's database SQL backup through **phpMyAdmin**.

5. Open:

```text
config/database.example.php
```

6. Create a local copy named:

```text
config/database.php
```

7. Configure the database connection according to your local MySQL/XAMPP setup.

8. Open the project in your browser:

```text
http://localhost/mahroosh%20cloths/maan-ghafar-garments/
```

---

## 🔐 Database Configuration

For a default XAMPP installation, the configuration may use:

```text
Host: localhost
Username: root
Password: [your local MySQL password]
Database: maan_ghafar_garments
```

Do not upload real database credentials or passwords to a public repository.

---

## 🧪 Testing

The application has been tested locally for the main customer and administrator workflows.

### Verified Functionality

* Database connection
* Customer registration
* Customer login
* Product display
* Shopping cart
* Checkout
* Order placement
* Order items
* Payment records
* Shipping records
* Admin order visibility
* Admin order status updates
* Customer-side order status updates

Development and testing orders were removed from the clean database backup.

---

## 💾 Backup & Recovery

A separate complete backup package contains:

```text
maan_ghafar_garments_BACKUP/
│
├── PROJECT/
├── DATABASE/
└── README.txt
```

The database SQL backup should be stored separately from the public GitHub repository.

The backup package can be used to restore the project and database on another local XAMPP installation.

---

## 🔒 Security Notes

* Do not commit real database credentials.
* Keep `config/database.php` private.
* Use `database.example.php` as the configuration template.
* Keep database backups in a secure location.
* Do not upload private customer information or production database dumps to a public repository.

---

## 👩‍💻 Developer

**Mahroosh**

Maan Ghafar Garments
Women's Fashion E-Commerce & Management Web Application

---

## 📄 License

This project was developed for educational/project purposes.

© Mahroosh — Maan Ghafar Garments
