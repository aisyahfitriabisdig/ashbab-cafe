# ASHBAB CAFE - Panduan Instalasi

## Persyaratan Sistem
- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+
- Apache dengan mod_rewrite
- Extension PHP: PDO, PDO_MySQL, GD, fileinfo, mbstring

---

## Langkah Instalasi

### 1. Upload / Clone Project
Letakkan folder `ashbab-cafe` di dalam direktori web server Anda:
- **XAMPP:** `C:/xampp/htdocs/ashbab-cafe/`
- **LAMP:**  `/var/www/html/ashbab-cafe/`

### 2. Import Database
1. Buka **phpMyAdmin** atau MySQL CLI
2. Buat database baru bernama `ashbab_cafe`
3. Import file `database/ashbab_cafe.sql`

```sql
CREATE DATABASE ashbab_cafe CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- Lalu import file SQL
```

### 3. Konfigurasi Database
Edit file `includes/config.php`:

```php
define('DB_HOST', 'localhost');   // Host database
define('DB_USER', 'root');        // Username database
define('DB_PASS', '');            // Password database
define('DB_NAME', 'ashbab_cafe'); // Nama database
define('APP_URL', 'http://localhost/ashbab-cafe'); // URL aplikasi
```

### 4. Izin Direktori (chmod)
Pastikan direktori upload dapat ditulis:

```bash
chmod -R 755 assets/uploads/
chmod -R 755 exports/
```

Atau di Windows (XAMPP), biasanya sudah otomatis.

### 5. Akses Aplikasi
- **Website Utama:**  `http://localhost/ashbab-cafe/`
- **Login Admin/Kasir:**  `http://localhost/ashbab-cafe/login.php`

---

## Akun Default

| Role  | Username | Password |
|-------|----------|----------|
| Admin | `admin`  | `password` |
| Kasir | `kasir`  | `password` |

> ⚠️ **Segera ubah password** setelah login pertama melalui menu Manajemen Pengguna!

---

## Struktur Direktori

```
ashbab-cafe/
├── index.php            ← Landing page
├── menu.php             ← Halaman menu
├── cart.php             ← Keranjang
├── checkout.php         ← Checkout
├── tracking.php         ← Tracking pesanan
├── login.php            ← Halaman login
├── logout.php           ← Logout
├── manifest.json        ← PWA manifest
├── .htaccess            ← Konfigurasi Apache
│
├── admin/               ← Panel Admin
│   ├── dashboard.php
│   ├── categories.php
│   ├── menus.php
│   ├── orders.php
│   ├── expenses.php
│   ├── reports.php
│   ├── settings.php
│   └── users.php
│
├── cashier/             ← Panel Kasir
│   ├── dashboard.php
│   ├── orders.php
│   ├── receipt.php
│   └── notifications.php
│
├── ajax/                ← AJAX Handlers
│   ├── add_cart.php
│   ├── update_cart.php
│   ├── remove_cart.php
│   ├── checkout.php
│   ├── get_menu.php
│   ├── get_order.php
│   ├── track_order.php
│   ├── update_order_status.php
│   ├── notifications.php
│   ├── categories.php
│   ├── menus.php
│   ├── expenses.php
│   ├── users.php
│   └── settings.php
│
├── includes/            ← Core PHP files
│   ├── config.php
│   ├── database.php
│   ├── functions.php
│   ├── auth.php
│   ├── header.php
│   ├── footer.php
│   ├── navbar.php
│   └── sidebar.php
│
├── exports/             ← Laporan
│   ├── export_pdf.php
│   └── export_excel.php
│
├── assets/
│   ├── css/
│   │   ├── style.css
│   │   ├── admin.css
│   │   └── cashier.css
│   ├── js/
│   │   ├── app.js
│   │   ├── cart.js
│   │   ├── tracking.js
│   │   ├── admin.js
│   │   └── cashier.js
│   └── uploads/
│       ├── menu/
│       ├── payments/
│       ├── logo/
│       ├── banner/
│       └── favicon/
│
└── database/
    └── ashbab_cafe.sql
```

---

## Fitur Utama

### 👤 Pelanggan
- Browse menu dengan filter kategori
- Tambah ke keranjang (tanpa login)
- Checkout dengan nama, HP, nomor meja
- Upload bukti pembayaran (Transfer/QRIS)
- Tracking pesanan real-time
- Notifikasi perubahan status (toast + suara)

### 👨‍💼 Admin
- Dashboard statistik + grafik Chart.js
- CRUD: Kategori, Menu, Pengeluaran, Pengguna
- Manajemen semua pesanan
- Konfirmasi / tolak pembayaran
- Laporan keuangan dengan filter periode
- Export PDF & Excel
- Pengaturan cafe (logo, banner, QRIS, rekening)
- Dark mode

### 🧾 Kasir
- Dashboard pesanan aktif real-time
- Update status pesanan
- Konfirmasi pembayaran
- Cetak struk (browser print / PDF)
- Notifikasi pesanan baru (audio + visual)

---

## Keamanan
- Password hashing bcrypt (`password_hash`)
- CSRF Token protection
- PDO Prepared Statements (anti SQL injection)
- XSS Protection (`htmlspecialchars`)
- Session management dengan timeout
- Direktori sensitif diproteksi `.htaccess`

---

## Teknologi
- PHP 8.0+ Native
- MySQL / MariaDB
- Bootstrap 5.3
- Font Awesome 6.5
- Chart.js 4.4
- DataTables 1.13
- SweetAlert2 11
- AOS Animation 2.3
- Google Fonts (Poppins)

---

Dibuat oleh: **ASHBAB CAFE System** | 2026

 
