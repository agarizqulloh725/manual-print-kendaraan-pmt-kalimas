# Panduan Hosting — Cetak Timbangan Manual RORO

Panduan memasang aplikasi ini di **shared hosting cPanel** (folder `public_html`).
Aplikasi dibuat dengan Laravel 13 (PHP 8.3+), MySQL, dan aset front-end yang sudah di-build dengan Vite.

> **Ringkasnya:** kode aplikasi diletakkan **di luar** `public_html`, lalu hanya isi folder `public/` yang masuk ke `public_html`.
> Jangan menaruh seluruh proyek di dalam `public_html`, karena file `.env` (berisi password database) bisa ikut terbuka ke internet.

---

## Daftar Isi

1. [Kebutuhan hosting](#1-kebutuhan-hosting)
2. [Persiapan di komputer lokal](#2-persiapan-di-komputer-lokal)
3. [Buat database MySQL](#3-buat-database-mysql)
4. [Upload & susun folder](#4-upload--susun-folder)
5. [Konfigurasi `.env`](#5-konfigurasi-env)
6. [Perintah setelah upload](#6-perintah-setelah-upload)
7. [Pengaturan PHP](#7-pengaturan-php)
8. [Setelah online: akun & pengecekan](#8-setelah-online-akun--pengecekan)
9. [Update aplikasi di kemudian hari](#9-update-aplikasi-di-kemudian-hari)
10. [Troubleshooting](#10-troubleshooting)

---

## 1. Kebutuhan hosting

| Kebutuhan | Keterangan |
|---|---|
| **PHP 8.3 atau lebih baru** | Pilih di cPanel → *MultiPHP Manager* / *Select PHP Version*. |
| Ekstensi PHP | `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`. `fileinfo` wajib untuk upload foto. |
| **MySQL 8 / MariaDB 10.6+** | Aplikasi sudah diuji di MySQL 8.4. |
| **Terminal / SSH** | Dibutuhkan untuk menjalankan perintah `php artisan`. Di cPanel biasanya ada menu *Terminal*. Jika tidak ada, minta akses SSH ke penyedia hosting. |
| **HTTPS (SSL)** | Aktifkan *AutoSSL* / Let's Encrypt di cPanel. **Wajib untuk kamera:** browser hanya mengizinkan akses kamera di `https://` (atau `localhost`). Lewat `http://` tombol Kamera hanya bisa membuka pemilih file. |
| Koneksi keluar ke `ptosr.pelindo.co.id` | Server harus boleh mengakses `https://ptosr.pelindo.co.id` untuk mengambil daftar kapal. Beberapa hosting memblokir koneksi keluar. Cek di [bagian 8](#8-setelah-online-akun--pengecekan). |
| Ruang disk | ±150 MB untuk aplikasi, ditambah ruang untuk foto (±200–400 KB per foto). |

Tidak perlu cron job maupun queue worker. Aplikasi ini tidak memakai scheduler atau antrian.

---

## 2. Persiapan di komputer lokal

Hosting biasanya tidak punya Node.js, jadi **aset front-end di-build di komputer sendiri**.

```bash
# 1. Install dependency PHP tanpa paket development
composer install --no-dev --optimize-autoloader

# 2. Install dependency JS & build aset (hasilnya di public/build)
npm ci
npm run build
```

Pastikan file `public/hot` **tidak ada**. File ini muncul saat `npm run dev` berjalan, dan membuat halaman mencari aset ke server Vite lokal.

Lalu buat file ZIP dari folder proyek **tanpa** isi berikut:

| Jangan ikut di-upload | Alasan |
|---|---|
| `node_modules/` | Tidak dipakai di server (aset sudah di-build). |
| `.git/` | Tidak diperlukan. |
| `.env` | `.env` produksi dibuat baru di server. |
| `database/database.sqlite` | Database lokal untuk development. |
| `storage/logs/*.log` | Log lokal. |
| `storage/app/public/tickets/` | Foto uji coba dari komputer lokal. |
| `public/hot` | Lihat penjelasan di atas. |
| `tests/` | Tidak dipakai di server. |

> Setelah upload selesai, di komputer lokal jalankan lagi `composer install` (tanpa `--no-dev`) agar tools development kembali lengkap.

---

## 3. Buat database MySQL

Di cPanel → **MySQL® Databases**:

1. Buat database, misalnya `cpuser_roro`.
2. Buat user database, misalnya `cpuser_roro` dengan password yang kuat.
3. *Add User To Database*, lalu centang **ALL PRIVILEGES**.

Catat nama database, username, dan password untuk `.env`.

---

## 4. Upload & susun folder

### Opsi A — Domain utama memakai `public_html` (paling umum)

Struktur akhir di server (`cpuser` = username cPanel Anda):

```
/home/cpuser/
├── roro-app/              ← seluruh isi proyek (di LUAR public_html)
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── public/            ← biarkan tetap ada
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   ├── .env               ← dibuat di langkah 5
│   └── artisan
└── public_html/           ← hanya ISI dari roro-app/public
    ├── build/
    ├── .htaccess
    ├── favicon.ico
    ├── index.php          ← diedit (lihat di bawah)
    ├── robots.txt
    └── storage            ← symlink, dibuat di langkah 6
```

Langkah:

1. Upload ZIP ke `/home/cpuser/` lewat *File Manager*, lalu **Extract** ke folder `roro-app`.
2. **Salin** isi `roro-app/public/` ke `public_html/`, yaitu folder `build/`, `.htaccess`, `favicon.ico`, `index.php`, dan `robots.txt`.
   Nyalakan *Show Hidden Files* di File Manager supaya `.htaccess` terlihat.
3. Edit **`public_html/index.php`** dan ganti seluruh isinya dengan kode berikut:

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Lokasi folder aplikasi (di luar public_html)
$appPath = __DIR__.'/../roro-app';

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $appPath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $appPath.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $appPath.'/bootstrap/app.php';

// public_html adalah folder public aplikasi ini (dipakai untuk membaca build/manifest.json)
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
```

> Baris `usePublicPath(__DIR__)` penting. Tanpa baris ini, Laravel mencari aset di `roro-app/public/build`, bukan di `public_html/build`.

### Opsi B — Subdomain / addon domain (lebih sederhana, jika bisa)

Jika aplikasi dipasang di subdomain (misalnya `timbangan.domainanda.com`), di cPanel → *Domains*, atur **Document Root** subdomain ke:

```
/home/cpuser/roro-app/public
```

Dengan opsi ini **tidak perlu** menyalin isi `public/` dan **tidak perlu** mengedit `index.php`. Upload & extract ke `roro-app`, lalu lanjut ke langkah 5.

---

## 5. Konfigurasi `.env`

Di folder `roro-app`, salin `.env.example` menjadi `.env`, lalu ubah nilainya seperti berikut:

```dotenv
APP_NAME="Timbangan Manual RORO"
APP_ENV=production
APP_KEY=                       # diisi otomatis di langkah 6
APP_DEBUG=false
APP_URL=https://domainanda.com # WAJIB sama persis dengan alamat yang dibuka operator
APP_TIMEZONE=Asia/Jakarta      # WAJIB, supaya jam tiket sesuai WIB

LOG_CHANNEL=daily
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=cpuser_roro
DB_USERNAME=cpuser_roro
DB_PASSWORD="password-database-anda"

SESSION_DRIVER=database
SESSION_LIFETIME=720
SESSION_SECURE_COOKIE=true      # hanya jika sudah HTTPS

CACHE_STORE=database
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=local

# Sumber data kapal (PTOS-R)
PTOSR_SCHEDULE_URL=https://ptosr.pelindo.co.id/ScheduleBoard/GetData
PTOSR_BRANCH_CODE=61
PTOSR_TERMINAL_CODE=601
PTOSR_CACHE_SECONDS=120
```

Penjelasan pengaturan yang paling penting:

| Variabel | Kenapa penting |
|---|---|
| `APP_DEBUG=false` | Jika `true`, pesan error menampilkan detail server, termasuk konfigurasi. **Jangan pernah `true` di produksi.** |
| `APP_URL` | Dipakai untuk link foto dan link di file export CSV. Jika salah, foto tidak tampil. Pakai `https://` dan tanpa garis miring di akhir. |
| `APP_TIMEZONE=Asia/Jakarta` | Aplikasi memakai WIB. Nilai bawaannya sudah `Asia/Jakarta`, tapi tetap tulis di `.env` supaya jelas. **Jangan diubah setelah ada data**, karena jam data lama akan bergeser. |
| `SESSION_LIFETIME` | Lama login bertahan (menit). `720` = 12 jam, cukup untuk satu shift. |
| `PTOSR_BRANCH_CODE` / `PTOSR_TERMINAL_CODE` | Kode cabang/terminal yang dipakai untuk mengambil daftar kapal. `61` / `601` = Tanjung Perak. |
| `PTOSR_CACHE_SECONDS` | Daftar kapal disimpan sementara selama sekian detik, supaya server PTOS-R tidak dipanggil terus-menerus. |

> Beri izin file `.env` **600** (hanya pemilik yang bisa membaca).

---

## 6. Perintah setelah upload

Buka cPanel → **Terminal** (atau SSH), lalu jalankan:

```bash
cd ~/roro-app

# 1. Buat APP_KEY (sekali saja, JANGAN diulang setelah aplikasi dipakai)
php artisan key:generate --force

# 2. Buat tabel database
php artisan migrate --force

# 3. Buat akun awal (1 admin + 2 operator contoh)
php artisan db:seed --force

# 4. Izin folder yang harus bisa ditulis
chmod -R 775 storage bootstrap/cache
```

**Link folder foto (`storage`):**

- **Opsi A** (public_html). Buat symlink secara manual, karena `php artisan storage:link` akan membuat link di tempat yang salah:

  ```bash
  ln -s ~/roro-app/storage/app/public ~/public_html/storage
  ```

- **Opsi B** (subdomain):

  ```bash
  php artisan storage:link
  ```

**Terakhir, optimasi untuk produksi:**

```bash
php artisan optimize
```

> `php artisan optimize` menyimpan cache konfigurasi. **Setiap kali `.env` diubah**, jalankan lagi `php artisan optimize`. Tanpa itu, perubahan `.env` tidak terbaca.

> Jika perintah `php` di Terminal bukan versi 8.3, pakai path lengkapnya, misalnya `/opt/cpanel/ea-php83/root/usr/bin/php artisan ...`. Cek versinya dengan `php -v`.

---

## 7. Pengaturan PHP

Di cPanel → **MultiPHP INI Editor** (pilih domain), atur:

| Pengaturan | Nilai | Alasan |
|---|---|---|
| `upload_max_filesize` | `16M` | Foto kendaraan / tiket dari HP. |
| `post_max_size` | `32M` | Satu form bisa berisi 2 foto + gambar barcode. |
| `memory_limit` | `256M` | Export CSV & dashboard. |
| `max_execution_time` | `60` | Export data yang banyak. |

Foto sudah diperkecil otomatis di browser (maksimal 1600 px) sebelum di-upload, jadi batas di atas cukup longgar.

---

## 8. Setelah online: akun & pengecekan

### Akun awal

Seeder membuat akun berikut, **semuanya dengan password `password`**:

| Nomor HP | Peran |
|---|---|
| `081200000001` | Administrator |
| `081200000002` | Operator Shift 1 |
| `081200000003` | Operator Shift 2 |

**Segera setelah online:**

1. Login dengan `081200000001` / `password`. Anda masuk ke dashboard `/admin`.
2. Buka **👥 User** → edit akun Administrator, lalu **ganti nomor HP & password** dengan milik admin sebenarnya.
3. Hapus atau nonaktifkan dua akun operator contoh.
4. Buat akun untuk setiap operator (menu **＋ Tambah User**). Operator tidak bisa mendaftar sendiri.

### Checklist uji coba

- [ ] Halaman login tampil dengan gaya (CSS) yang benar.
- [ ] Tab **INPUT**: daftar **Kapal Beroperasi** terisi dari PTOS-R.
- [ ] Simpan tiket → halaman cetak terbuka dan dialog print muncul.
- [ ] Upload foto kendaraan & foto tiket → foto tampil di halaman detail kendaraan.
- [ ] Foto tiket berisi barcode → barcode terdeteksi dan nilainya terisi.
- [ ] Jam pada tiket sesuai WIB.
- [ ] **REKAP** → Export CSV terunduh dan bisa dibuka di Excel.
- [ ] Dashboard admin → *Status API Jadwal Kapal* menunjukkan **✔ Terhubung**.

Untuk mengecek apakah server bisa mengakses PTOS-R, jalankan di Terminal:

```bash
curl -sI "https://ptosr.pelindo.co.id/ScheduleBoard/GetData?kd_cabang=61&kd_terminal=601" | head -1
```

Hasil yang benar adalah `HTTP/1.1 200 OK`. Jika gagal atau timeout, minta penyedia hosting membuka koneksi keluar ke domain tersebut.

---

## 9. Update aplikasi di kemudian hari

1. Di lokal: `composer install --no-dev --optimize-autoloader` dan `npm run build`.
2. Di server, aktifkan mode perawatan:
   ```bash
   cd ~/roro-app && php artisan down
   ```
3. Upload file yang berubah ke `roro-app/`. **Jangan menimpa** `.env` dan folder `storage/`.
4. **Opsi A:** salin ulang `roro-app/public/build/` ke `public_html/build/`. Hapus dulu folder `build` yang lama.
5. Jalankan:
   ```bash
   php artisan migrate --force
   php artisan optimize
   php artisan up
   ```

> **Backup** database (cPanel → *Backup* / phpMyAdmin → Export) dan folder `roro-app/storage/app/public/` secara berkala. Folder itu berisi semua foto.

---

## 10. Troubleshooting

| Gejala | Penyebab & solusi |
|---|---|
| **Error 500** (halaman putih) | Lihat `roro-app/storage/logs/laravel-*.log`. Penyebab umum: `APP_KEY` kosong (jalankan `php artisan key:generate --force`), izin folder `storage` / `bootstrap/cache` (jalankan `chmod -R 775`), atau versi PHP di bawah 8.3. |
| *Vite manifest not found* / tampilan tanpa CSS | Folder `build/` belum ada di `public_html` (Opsi A), baris `usePublicPath` belum ditambahkan di `index.php`, atau file `public_html/hot` masih ada (hapus). |
| **Foto tidak tampil (404)** | Symlink `public_html/storage` belum dibuat (lihat langkah 6), atau `APP_URL` tidak sama dengan alamat yang dibuka. Setelah mengubah `.env`, jalankan `php artisan optimize`. |
| *"Gagal mengambil data kapal dari PTOS-R"* | Server tidak bisa mengakses `ptosr.pelindo.co.id`. Cek dengan perintah `curl` di bagian 8, dan lihat status di dashboard admin. |
| **Jam tiket selisih 7 jam** | `APP_TIMEZONE=Asia/Jakarta` belum diisi atau cache konfigurasi lama. Isi, lalu jalankan `php artisan optimize`. |
| *419 Page Expired* saat login / simpan | Sesi habis atau cookie ditolak. Jika situs belum HTTPS, set `SESSION_SECURE_COOKIE=false`. Pastikan tabel `sessions` ada (`php artisan migrate --force`). |
| Upload foto gagal / *"The foto ... failed to upload"* | Naikkan `upload_max_filesize` & `post_max_size` (bagian 7). |
| **Tombol Kamera membuka pemilih file**, bukan kamera | Situs dibuka lewat `http://`. Aktifkan SSL dan buka lewat `https://`. |
| *"Izin kamera ditolak"* | Klik ikon 🔒 / 📷 di address bar, lalu izinkan **Kamera** untuk situs ini, kemudian muat ulang halaman. |
| *"Kamera sedang dipakai aplikasi lain"* | Tutup aplikasi lain yang memakai webcam (Zoom, Teams, aplikasi kamera). |
| Barcode tidak terbaca otomatis | Pastikan `public_html/.htaccess` berasal dari versi terbaru (berisi `AddType application/wasm .wasm`), dan folder `build/` lengkap (ada file `zxing_reader-*.wasm`). Nilai barcode tetap bisa diketik manual. |
| Perubahan `.env` tidak berpengaruh | Jalankan `php artisan optimize` (atau `php artisan config:clear`). |
| Lupa password admin | Di Terminal: `php artisan tinker --execute 'App\Models\User::where("phone","08xxxxxxxxxx")->first()->update(["password" => "PasswordBaru123"]);'` |
