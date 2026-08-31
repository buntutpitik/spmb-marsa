# SPMB MARSA

SPMB MARSA adalah aplikasi Sistem Penerimaan Murid Baru berbasis Laravel untuk mengelola proses penerimaan siswa secara terintegrasi, mulai dari pendaftaran publik hingga penerimaan, daftar ulang, keuangan, pelaporan, dan analitik antar periode.

## Fitur Utama

- Pendaftaran calon siswa secara publik tanpa akun.
- Nomor pendaftaran otomatis.
- Kartu pendaftaran PDF.
- Cek status pendaftaran menggunakan secure public token.
- Manajemen data pendaftar.
- Workflow penerimaan dan perubahan status.
- Daftar ulang dan pencatatan pembayaran.
- Bukti pembayaran PDF.
- Rekap dan laporan PDF/Excel.
- Analitik dan perbandingan antar periode.
- Pengelolaan jurusan, jalur penerimaan, asal sekolah, program khusus, dan keringanan.
- Manajemen pengguna berbasis role.
- Activity log.
- Integrasi notifikasi WhatsApp melalui Meta Cloud API.
- Pengaturan halaman publik dari panel administrator.
- Dukungan data historis multi-periode.

## Teknologi

- PHP 8.3+
- Laravel 13
- Livewire 4
- MySQL / MariaDB
- Tailwind CSS
- Vite
- Laravel DomPDF
- Laravel Excel

## Instalasi

Clone repository:

```bash
git clone https://github.com/buntutpitik/spmb-marsa.git
cd spmb-marsa
```

Install dependency:

```bash
composer install
npm install
```

Buat file environment.

Linux/macOS:

```bash
cp .env.example .env
```

Windows:

```bat
copy .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

Sesuaikan konfigurasi database pada `.env`, kemudian:

```bash
php artisan migrate
npm run build
```

Untuk menjalankan development environment:

```bash
composer run dev
```

## Testing

Jalankan test suite:

```bash
php artisan test
```

Jika diperlukan memory limit lebih besar:

```bash
php -d memory_limit=512M vendor/phpunit/phpunit/phpunit
```

## WhatsApp

Integrasi notifikasi WhatsApp menggunakan Meta Cloud API.

Credential WhatsApp tidak disertakan dalam repository. Konfigurasikan credential melalui file `.env` pada masing-masing environment.

Untuk development dan testing tersedia fake WhatsApp provider sehingga pengujian tidak memerlukan pengiriman pesan nyata.

## Keamanan

Jangan commit file `.env`, database production, dump SQL, access token, API key, password, atau credential lainnya.

Untuk pelaporan kerentanan keamanan, lihat [SECURITY.md](SECURITY.md).

## Data

Repository ini tidak menyertakan database production maupun data pribadi calon siswa.

Gunakan database dan data development/testing sendiri ketika menjalankan aplikasi.

## Lisensi

SPMB MARSA dirilis sebagai perangkat lunak open source di bawah [MIT License](LICENSE).

## Credits

SPMB MARSA dibangun menggunakan Laravel dan berbagai package open-source yang tercantum dalam `composer.json`.
