<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

# Layanan Pembayaran - Auctora Collection

Layanan Pembayaran untuk proyek akhir Integrasi Sistem dan Komputasi Awan
(25IF1504). Layanan ini mencatat pembayaran pesanan, mengonfirmasinya, dan
menerbitkan event `PembayaranDikonfirmasi` ke RabbitMQ.

## Anggota dan pembagian tugas

| Nama  | Bagian yang dikerjakan                                              |
| ----- | ------------------------------------------------------------------- |
| [Riko Apriliyawan] | Layanan Pembayaran (repositori ini), kontrak OpenAPI, kontrak event |
| [ISI] | Layanan Pesanan                                                     |
| [ISI] | Layanan Stok                                                        |
| [ISI] | Layanan Pengiriman / dokumen analisis                               |

## Isi repositori

- `openapi.yaml`: kontrak REST (design-first, dicek dengan Spectral)
- `kontrak-event.md`: kontrak event `PembayaranDikonfirmasi`
- `stub-pesanan/`: stub Layanan Pesanan khusus demonstrasi, bukan layanan sebenarnya
- `app/`, `routes/api.php`, `tests/`: kode layanan dan feature test

## Prasyarat

Docker Desktop. Pengguna Windows menjalankan perintah di terminal WSL2 (Ubuntu).

## Menjalankan dari nol

```
git clone [ISI-URL-REPOSITORI]
cd [ISI-NAMA-FOLDER]
cp .env.example .env
```

Pasang dependensi PHP (belum ada folder `vendor` pada salinan baru). Versi
image mengikuti PHP proyek, lihat bagian "Installing Composer Dependencies For
Existing Projects" di dokumentasi Laravel Sail jika perintah ini gagal:

```
docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html laravelsail/php85-composer:latest composer install --ignore-platform-reqs
```

Nyalakan semua container, lalu siapkan aplikasi:

```
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
```

Container yang berjalan: aplikasi Laravel (port 80), PostgreSQL, RabbitMQ,
dan stub Layanan Pesanan. Dashboard RabbitMQ ada di http://localhost:15672
(pengguna `guest`, kata sandi `guest`).

## Menjalankan test

```
./vendor/bin/sail artisan test
```

## Mencoba alur lengkap

```
curl -i -X POST http://localhost/api/v1/pembayaran -H "Content-Type: application/json" -H "Accept: application/json" -d '{"pesanan_id":"5f1c2a6e-8a47-4d52-9b1e-3c7f0d2a9e11","nominal":185000,"metode":"transfer_bank"}'
```

Salin `id` dari respons, lalu konfirmasi:

```
curl -i -X POST http://localhost/api/v1/pembayaran/ID_PEMBAYARAN/konfirmasi -H "Content-Type: application/json" -H "Accept: application/json" -d '{"nominal_diterima":185000}'
```

Event muncul di antrean `stok.pembayaran_dikonfirmasi` dan
`pengiriman.pembayaran_dikonfirmasi` pada dashboard RabbitMQ.

Menjalankan konfirmasi kedua untuk id yang sama menghasilkan galat 409
(Problem Details). Mengirim `nominal_diterima` yang berbeda menghasilkan 422.

## Memeriksa kontrak

```
./vendor/bin/sail npx @stoplight/spectral-cli lint openapi.yaml
```

## Catatan

- Layanan Pesanan pada repositori ini berupa stub (`stub-pesanan/router.php`)
  yang selalu menjawab satu pesanan contoh. Alamatnya diatur lewat `PESANAN_URL`
  di `.env`.
- Autentikasi belum diterapkan (belum dinilai di Checkpoint 1).
