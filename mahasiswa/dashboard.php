<?php

$pageTitle = 'Dashboard';
$activePage = 'dashboard';
require_once 'templates/header_mahasiswa.php'; 

?>

<style>
    /* Styling Umum */
    body {
        font-family: 'Inter', sans-serif; /* Font yang lebih modern */
        background-color: #f0f2f5; /* Warna latar belakang lembut */
        color: #333;
    }

    /* Bagian Selamat Datang */
    .hero-banner {
        background: linear-gradient(135deg, #4A00E0, #8E2DE2); /* Gradien ungu-biru */
        color: white;
        padding: 40px; /* Padding lebih besar */
        border-radius: 15px; /* Sudut lebih membulat */
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15); /* Bayangan lebih dramatis */
        margin-bottom: 30px;
        text-align: center; /* Teks di tengah */
        animation: slideInFromLeft 0.8s ease-out; /* Animasi masuk */
    }

    .hero-banner h1 {
        font-size: 2.8rem; /* Ukuran font lebih besar */
        font-weight: 800; /* Lebih tebal */
        margin-bottom: 10px;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.2); /* Bayangan teks */
    }

    .hero-banner p {
        font-size: 1.2rem;
        opacity: 0.95;
        line-height: 1.6;
    }

    /* Statistik Praktikum */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); /* Responsif */
        gap: 25px; /* Jarak antar kartu lebih besar */
        margin-bottom: 30px;
    }

    .stat-card {
        background-color: #ffffff;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08); /* Bayangan kartu */
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        transition: transform 0.3s ease, box-shadow 0.3s ease; /* Transisi halus */
    }

    .stat-card:hover {
        transform: translateY(-5px); /* Efek terangkat saat hover */
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
    }

    .stat-value {
        font-size: 4rem; /* Ukuran angka statistik lebih besar */
        font-weight: 900; /* Sangat tebal */
        margin-bottom: 5px;
        line-height: 1; /* Rapi */
    }

    /* Warna spesifik untuk statistik */
    .text-blue-600 { color: #3b82f6; }
    .text-green-500 { color: #22c55e; }
    .text-yellow-500 { color: #facc15; }
    .text-indigo-600 { color: #4f46e5; } /* Tambahan, jika perlu */

    .stat-label {
        font-size: 1.1rem;
        color: #6b7280; /* Warna teks label */
        font-weight: 500;
    }

    /* Notifikasi Terbaru */
    .notifications-section {
        background-color: #ffffff;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    }

    .notifications-section h3 {
        font-size: 2rem; /* Ukuran judul lebih besar */
        font-weight: 700;
        color: #333;
        margin-bottom: 20px;
        border-bottom: 2px solid #e5e7eb; /* Garis bawah */
        padding-bottom: 10px;
    }

    .notification-list {
        list-style: none; /* Hilangkan bullet default */
        padding: 0;
        margin: 0;
    }

    .notification-item {
        display: flex;
        align-items: flex-start; /* Sejajarkan item dengan ikon */
        padding: 15px 0; /* Padding vertikal */
        border-bottom: 1px solid #edf1f5; /* Garis pemisah antar notifikasi */
        transition: background-color 0.2s ease; /* Transisi hover */
    }

    .notification-item:last-child {
        border-bottom: none; /* Hilangkan border pada item terakhir */
    }

    .notification-item:hover {
        background-color: #f9fafb; /* Warna latar belakang saat hover */
    }

    .notification-icon {
        font-size: 1.8rem; /* Ukuran ikon lebih besar */
        margin-right: 15px;
        line-height: 1; /* Pastikan rapi dengan teks */
    }

    .notification-text {
        font-size: 1.05rem;
        line-height: 1.5;
        color: #4a5568;
    }

    .notification-text a {
        font-weight: 600;
        color: #3f51b5; /* Warna tautan yang lebih menonjol */
        text-decoration: none;
        transition: color 0.2s ease, text-decoration 0.2s ease;
    }

    .notification-text a:hover {
        color: #2c387e; /* Warna tautan lebih gelap saat hover */
        text-decoration: underline;
    }

    /* Animasi Tambahan */
    @keyframes slideInFromLeft {
        from {
            opacity: 0;
            transform: translateX(-50px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    /* Media Queries untuk Responsif */
    @media (max-width: 768px) {
        .hero-banner {
            padding: 30px;
            text-align: left; /* Teks kiri di layar kecil */
        }

        .hero-banner h1 {
            font-size: 2rem;
        }

        .hero-banner p {
            font-size: 1rem;
        }

        .stats-grid {
            grid-template-columns: 1fr; /* Satu kolom di layar kecil */
        }

        .stat-value {
            font-size: 3rem;
        }

        .notifications-section {
            padding: 20px;
        }

        .notifications-section h3 {
            font-size: 1.6rem;
        }

        .notification-item {
            flex-direction: column; /* Ikon di atas teks di layar sangat kecil */
            align-items: flex-start;
        }

        .notification-icon {
            margin-bottom: 8px;
            margin-right: 0;
        }
    }
</style>

<div class="hero-banner">
    <h1 class="text-3xl font-bold">Selamat Datang Kembali, <?php echo htmlspecialchars($_SESSION['nama']); ?>!</h1>
    <p class="mt-2 opacity-90">Terus semangat dalam menyelesaikan semua modul praktikummu.</p>
</div>

<div class="stats-grid">
    
    <div class="stat-card">
        <div class="stat-value text-blue-600">3</div>
        <div class="stat-label">Praktikum Diikuti</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-value text-green-500">8</div>
        <div class="stat-label">Tugas Selesai</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-value text-yellow-500">4</div>
        <div class="stat-label">Tugas Menunggu</div>
    </div>
    
</div>

<div class="notifications-section">
    <h3 class="text-2xl font-bold text-gray-800 mb-4">Notifikasi Terbaru</h3>
    <ul class="notification-list">
        
        <li class="notification-item">
            <span class="notification-icon">🔔</span>
            <div class="notification-text">
                Nilai untuk <a href="#" class="font-semibold text-blue-600 hover:underline">Modul 1: HTML & CSS</a> telah diberikan.
            </div>
        </li>

        <li class="notification-item">
            <span class="notification-icon">⏳</span>
            <div class="notification-text">
                Batas waktu pengumpulan laporan untuk <a href="#" class="font-semibold text-blue-600 hover:underline">Modul 2: PHP Native</a> adalah besok!
            </div>
        </li>

        <li class="notification-item">
            <span class="notification-icon">✅</span>
            <div class="notification-text">
                Anda berhasil mendaftar pada mata praktikum <a href="#" class="font-semibold text-blue-600 hover:underline">Jaringan Komputer</a>.
            </div>
        </li>
        
    </ul>
</div>

<?php
// Panggil Footer
require_once 'templates/footer_mahasiswa.php';
?>