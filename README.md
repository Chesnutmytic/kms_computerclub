<div align="center">

# 🖥️ KMS Computer Club

**Knowledge Management System** untuk organisasi Computer Club — kelola materi, event, kepengurusan, dan catatan anggota dalam satu platform modern berbasis web.

[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)](LICENSE)
[![Status](https://img.shields.io/badge/Status-Active-brightgreen?style=for-the-badge)]()

</div>

---

## 📋 Daftar Isi

- [✨ Fitur](#-fitur)
- [🗂️ Struktur Proyek](#️-struktur-proyek)
- [⚙️ Prasyarat](#️-prasyarat)
- [🚀 Instalasi](#-instalasi)
  - [Menggunakan XAMPP](#-menggunakan-xampp)
  - [Menggunakan Laragon](#-menggunakan-laragon)
- [🗄️ Konfigurasi Database](#️-konfigurasi-database)
- [🔧 Konfigurasi Environment](#-konfigurasi-environment)
- [🌐 Akses Lokal](#-akses-lokal)
- [🤝 Kontribusi](#-kontribusi)

---

## ✨ Fitur

| Modul | Deskripsi |
|-------|-----------|
| 🏠 **Landing Page** | Halaman publik dengan informasi organisasi |
| 🔐 **Autentikasi** | Login & Register anggota dengan sesi aman |
| 🛠️ **Panel Admin** | Kelola seluruh data organisasi dari satu dasbor |
| 📚 **Arsip Materi** | Upload, kelola, dan akses materi pembelajaran |
| 📅 **Manajemen Event** | Jadwalkan dan publikasikan kegiatan klub |
| 🏢 **Kepengurusan** | Data struktur organisasi dan jabatan anggota |
| 📝 **Catatan Anggota** | Sistem catatan pribadi per anggota |
| 🗺️ **Alur Belajar** | Roadmap pembelajaran terstruktur |
| 🤖 **RAG Chat (AI)** | Tanya-jawab berbasis dokumen dengan LLM |
| 📢 **Pengumuman** | Publikasi informasi terkini kepada anggota |

---

## 🗂️ Struktur Proyek

```
kms_computerclub/
├── 📁 admin/          # Panel administrasi (kelola data, user, event, dll)
├── 📁 assets/         # Aset statis (gambar, media, uploads)
├── 📁 config/         # Konfigurasi koneksi database
├── 📁 includes/       # Komponen reusable (header, footer, sidebar)
├── 📁 portal/         # Halaman portal anggota
├── 📁 rag/            # Modul AI (RAG / vector search)
├── 📄 index.html      # Landing page utama
├── 📄 login.html      # Halaman login
├── 📄 register.html   # Halaman registrasi
├── 📄 proses_login.php
├── 📄 proses_register.php
├── 📄 logout.php
├── 📄 composer.json   # Dependency PHP
└── 📄 .env.example    # Template konfigurasi environment
```

---

## ⚙️ Prasyarat

Pastikan sistem kamu memiliki:

- **PHP** `>= 8.2`
- **MySQL** `>= 8.0` atau **MariaDB** `>= 10.4`
- **Composer** `>= 2.x`
- **Web Server**: XAMPP / Laragon / Apache

---

## 🚀 Instalasi

### 1️⃣ Clone Repositori

```bash
git clone https://github.com/Chesnutmytic/kms_computerclub.git
```

---

### 🟠 Menggunakan XAMPP

<details>
<summary><strong>Klik untuk melihat langkah instalasi XAMPP</strong></summary>

**a. Download & Install XAMPP**

> Download XAMPP dari: https://www.apachefriends.org/

Pilih versi **PHP 8.2** dan ikuti wizard instalasi.

**b. Pindahkan Folder Proyek**

Salin atau pindahkan folder `kms_computerclub` ke dalam direktori:

```
C:\xampp\htdocs\
```

Sehingga menjadi:

```
C:\xampp\htdocs\kms_computerclub\
```

**c. Jalankan Apache & MySQL**

Buka **XAMPP Control Panel**, lalu klik **Start** pada:
- ✅ Apache
- ✅ MySQL

**d. Install Dependency Composer**

Buka terminal di dalam folder proyek:

```bash
cd C:\xampp\htdocs\kms_computerclub
composer install
```

</details>

---

### 🟢 Menggunakan Laragon

<details>
<summary><strong>Klik untuk melihat langkah instalasi Laragon</strong></summary>

**a. Download & Install Laragon**

> Download Laragon dari: https://laragon.org/download/

Pilih versi **Full** untuk mendapatkan Apache, MySQL, dan PHP sekaligus.

**b. Pindahkan Folder Proyek**

Salin atau pindahkan folder `kms_computerclub` ke:

```
C:\laragon\www\
```

Sehingga menjadi:

```
C:\laragon\www\kms_computerclub\
```

**c. Jalankan Laragon**

Buka **Laragon**, lalu klik **Start All**. Laragon akan otomatis membuat virtual host:

```
http://kms_computerclub.test
```

**d. Install Dependency Composer**

Buka **Terminal** dari Laragon (klik kanan → Terminal), lalu:

```bash
cd C:\laragon\www\kms_computerclub
composer install
```

</details>

---

## 🗄️ Konfigurasi Database

### 1. Buat Database Baru

Buka **phpMyAdmin** di browser:
- XAMPP: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
- Laragon: [http://localhost/phpmyadmin](http://localhost/phpmyadmin) atau klik **Database** di Laragon

Buat database baru:

```sql
CREATE DATABASE km_computerclub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. Import File SQL

- Pilih database `km_computerclub`
- Klik tab **Import**
- Upload file SQL yang tersedia *(diminta terpisah dari repository)*

> **💡 Catatan:** File SQL tidak disertakan di repository demi keamanan. Hubungi maintainer untuk mendapatkan file `km_computerclub.sql`.

---

## 🔧 Konfigurasi Environment

### 1. Salin File `.env.example`

```bash
cp .env.example .env
```

Atau di Windows:

```cmd
copy .env.example .env
```

### 2. Edit File `.env`

Buka file `.env` dan sesuaikan dengan konfigurasi lokal kamu:

```env
DB_HOST=localhost
DB_NAME=km_computerclub
DB_USER=root
DB_PASS=

APP_ENV=local
APP_DEBUG=true
```

| Variable | Keterangan | Default |
|----------|-----------|---------|
| `DB_HOST` | Host database | `localhost` |
| `DB_NAME` | Nama database | `km_computerclub` |
| `DB_USER` | Username MySQL | `root` |
| `DB_PASS` | Password MySQL | *(kosong untuk XAMPP/Laragon default)* |
| `APP_ENV` | Mode aplikasi | `local` |
| `APP_DEBUG` | Mode debug | `true` |

---

## 🌐 Akses Lokal

Setelah semua konfigurasi selesai, buka browser dan akses:

| Halaman | URL (XAMPP) | URL (Laragon) |
|---------|-------------|---------------|
| 🏠 Landing Page | http://localhost/kms_computerclub | http://kms_computerclub.test |
| 🔐 Login | http://localhost/kms_computerclub/login.html | http://kms_computerclub.test/login.html |
| 📋 Register | http://localhost/kms_computerclub/register.html | http://kms_computerclub.test/register.html |
| 🛠️ Admin Panel | http://localhost/kms_computerclub/admin/dashboard.php | http://kms_computerclub.test/admin/dashboard.php |
| 👤 Portal Anggota | http://localhost/kms_computerclub/portal | http://kms_computerclub.test/portal |

---

## 🔍 Troubleshooting

<details>
<summary><strong>❌ Error: "No such file or directory" saat composer install</strong></summary>

Pastikan Composer sudah terinstall. Cek dengan:
```bash
composer --version
```
Jika belum, download di: https://getcomposer.org/download/

</details>

<details>
<summary><strong>❌ Error koneksi database</strong></summary>

1. Pastikan MySQL sudah berjalan di XAMPP/Laragon
2. Cek kembali isi file `.env` — pastikan `DB_USER` dan `DB_PASS` sesuai
3. Pastikan database `km_computerclub` sudah dibuat

</details>

<details>
<summary><strong>❌ Halaman tidak ditemukan (404)</strong></summary>

1. Pastikan folder proyek berada di direktori yang benar (`htdocs` / `www`)
2. Pastikan Apache sudah berjalan
3. Cek apakah modul `mod_rewrite` aktif di XAMPP (Apache → httpd.conf)

</details>

---

## 🤝 Kontribusi

Pull request sangat diterima! Untuk perubahan besar, mohon buka **Issue** terlebih dahulu untuk mendiskusikan yang ingin diubah.

1. Fork repositori ini
2. Buat branch fitur baru (`git checkout -b feature/fitur-baru`)
3. Commit perubahan (`git commit -m 'feat: tambah fitur baru'`)
4. Push ke branch (`git push origin feature/fitur-baru`)
5. Buka **Pull Request**

---

<div align="center">

Made with ❤️ by **KMS Computer Club Team**

</div>
