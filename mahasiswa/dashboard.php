<?php

$pageTitle = 'Dashboard';
$activePage = 'dashboard';
require_once 'templates/header_mahasiswa.php';
require_once '../config.php'; // Pastikan path ke config.php benar

$user_id = $_SESSION['user_id'];

// --- Ambil Data Statistik dari Database ---

// 1. Praktikum Diikuti (Total enrollments oleh mahasiswa ini)
$total_praktikum_diikuti = 0;
$sql_praktikum_diikuti = "SELECT COUNT(*) AS total FROM enrollments WHERE user_id = ?";
$stmt_praktikum_diikuti = $conn->prepare($sql_praktikum_diikuti);
if ($stmt_praktikum_diikuti) {
    $stmt_praktikum_diikuti->bind_param("i", $user_id);
    $stmt_praktikum_diikuti->execute();
    $result_praktikum_diikuti = $stmt_praktikum_diikuti->get_result();
    if ($result_praktikum_diikuti && $result_praktikum_diikuti->num_rows > 0) {
        $row = $result_praktikum_diikuti->fetch_assoc();
        $total_praktikum_diikuti = $row['total'];
    }
    $stmt_praktikum_diikuti->close();
} else {
    error_log("Failed to prepare statement for total_praktikum_diikuti: " . $conn->error);
}


// 2. Tugas Selesai (Total submissions dengan status 'graded')
$total_tugas_selesai = 0;
$sql_tugas_selesai = "SELECT COUNT(DISTINCT s.id) AS total 
                      FROM submissions s
                      JOIN modules m ON s.module_id = m.id
                      JOIN enrollments e ON m.course_id = e.course_id AND e.user_id = s.user_id
                      WHERE s.user_id = ? AND s.status = 'graded'";
$stmt_tugas_selesai = $conn->prepare($sql_tugas_selesai);
if ($stmt_tugas_selesai) {
    $stmt_tugas_selesai->bind_param("i", $user_id);
    $stmt_tugas_selesai->execute();
    $result_tugas_selesai = $stmt_tugas_selesai->get_result();
    if ($result_tugas_selesai && $result_tugas_selesai->num_rows > 0) {
        $row = $result_tugas_selesai->fetch_assoc();
        $total_tugas_selesai = $row['total'];
    }
    $stmt_tugas_selesai->close();
} else {
    error_log("Failed to prepare statement for total_tugas_selesai: " . $conn->error);
}


// 3. Tugas Menunggu (Total submissions dengan status 'submitted' atau modul yang belum dikumpulkan dan due_date belum lewat)
$total_tugas_menunggu = 0;
// Submissions yang sudah dikumpulkan tapi belum dinilai
$sql_submitted_pending = "SELECT COUNT(*) AS total FROM submissions WHERE user_id = ? AND status = 'submitted'";
$stmt_submitted_pending = $conn->prepare($sql_submitted_pending);
if ($stmt_submitted_pending) {
    $stmt_submitted_pending->bind_param("i", $user_id);
    $stmt_submitted_pending->execute();
    $result_submitted_pending = $stmt_submitted_pending->get_result();
    if ($result_submitted_pending && $result_submitted_pending->num_rows > 0) {
        $row = $result_submitted_pending->fetch_assoc();
        $total_tugas_menunggu += $row['total'];
    }
    $stmt_submitted_pending->close();
} else {
    error_log("Failed to prepare statement for submitted_pending: " . $conn->error);
}


// Modul yang belum dikumpulkan dan due_date belum lewat
$sql_modules_not_submitted_yet = "
    SELECT COUNT(m.id) AS total
    FROM modules m
    JOIN enrollments e ON m.course_id = e.course_id
    LEFT JOIN submissions s ON m.id = s.module_id AND s.user_id = e.user_id
    WHERE e.user_id = ?
    AND s.id IS NULL -- Belum ada submission
    AND (m.due_date IS NULL OR m.due_date >= NOW()) -- Due date belum lewat atau tidak ada due date
";
$stmt_modules_not_submitted_yet = $conn->prepare($sql_modules_not_submitted_yet);
if ($stmt_modules_not_submitted_yet) {
    $stmt_modules_not_submitted_yet->bind_param("i", $user_id);
    $stmt_modules_not_submitted_yet->execute();
    $result_modules_not_submitted_yet = $stmt_modules_not_submitted_yet->get_result();
    if ($result_modules_not_submitted_yet && $result_modules_not_submitted_yet->num_rows > 0) {
        $row = $result_modules_not_submitted_yet->fetch_assoc();
        $total_tugas_menunggu += $row['total'];
    }
    $stmt_modules_not_submitted_yet->close();
} else {
    error_log("Failed to prepare statement for modules_not_submitted_yet: " . $conn->error);
}


// --- Ambil Notifikasi Terbaru dari Database ---
$notifications = [];

// Notifikasi: Nilai baru diberikan
$sql_new_grades = "
    SELECT 
        g.graded_at AS date,
        'grade' AS type,
        m.module_name,
        c.course_name,
        g.grade_value
    FROM grades g
    JOIN submissions s ON g.submission_id = s.id
    JOIN modules m ON s.module_id = m.id
    JOIN courses c ON m.course_id = c.id
    WHERE s.user_id = ?
    ORDER BY g.graded_at DESC
    LIMIT 3"; // Ambil 3 notifikasi nilai terbaru
$stmt_new_grades = $conn->prepare($sql_new_grades);
if ($stmt_new_grades) {
    $stmt_new_grades->bind_param("i", $user_id);
    $stmt_new_grades->execute();
    $result_new_grades = $stmt_new_grades->get_result();
    while ($row = $result_new_grades->fetch_assoc()) {
        $notifications[] = [
            'icon' => '🔔',
            'message' => 'Nilai untuk <a href="course_detail.php?id=' . htmlspecialchars($row['course_id'] ?? '') . '" class="font-semibold text-blue-600 hover:underline">' . htmlspecialchars($row['module_name']) . '</a> pada praktikum ' . htmlspecialchars($row['course_name']) . ' telah diberikan: <span class="font-bold">' . htmlspecialchars($row['grade_value']) . '</span>.',
            'timestamp' => strtotime($row['date'])
        ];
    }
    $stmt_new_grades->close();
} else {
    error_log("Failed to prepare statement for new grades: " . $conn->error);
}


// Notifikasi: Batas waktu pengumpulan akan datang (dalam 7 hari ke depan)
$sql_upcoming_due_dates = "
    SELECT 
        m.module_name,
        m.due_date,
        c.course_name,
        c.id AS course_id
    FROM modules m
    JOIN enrollments e ON m.course_id = e.course_id
    LEFT JOIN submissions s ON m.id = s.module_id AND s.user_id = e.user_id
    WHERE e.user_id = ?
    AND s.id IS NULL -- Belum dikumpulkan
    AND m.due_date IS NOT NULL
    AND m.due_date >= NOW()
    AND m.due_date <= DATE_ADD(NOW(), INTERVAL 7 DAY)
    ORDER BY m.due_date ASC
    LIMIT 3"; // Ambil 3 notifikasi batas waktu terbaru
$stmt_upcoming_due_dates = $conn->prepare($sql_upcoming_due_dates);
if ($stmt_upcoming_due_dates) {
    $stmt_upcoming_due_dates->bind_param("i", $user_id);
    $stmt_upcoming_due_dates->execute();
    $result_upcoming_due_dates = $stmt_upcoming_due_dates->get_result();
    while ($row = $result_upcoming_due_dates->fetch_assoc()) {
        $notifications[] = [
            'icon' => '⏳',
            'message' => 'Batas waktu pengumpulan laporan untuk <a href="course_detail.php?id=' . htmlspecialchars($row['course_id']) . '" class="font-semibold text-blue-600 hover:underline">' . htmlspecialchars($row['module_name']) . '</a> pada praktikum ' . htmlspecialchars($row['course_name']) . ' adalah ' . date('d M Y H:i', strtotime($row['due_date'])) . '!',
            'timestamp' => strtotime($row['due_date'])
        ];
    }
    $stmt_upcoming_due_dates->close();
} else {
    error_log("Failed to prepare statement for upcoming due dates: " . $conn->error);
}


// Notifikasi: Pendaftaran praktikum baru (contoh, bisa diambil dari log atau event pendaftaran)
// Untuk saat ini, kita bisa menggunakan data statis atau mengambil dari tabel enrollments terbaru
$sql_new_enrollments = "
    SELECT 
        e.enrolled_at AS date,
        c.course_name,
        c.id AS course_id
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    WHERE e.user_id = ?
    ORDER BY e.enrolled_at DESC
    LIMIT 3"; // Ambil 3 notifikasi pendaftaran terbaru
$stmt_new_enrollments = $conn->prepare($sql_new_enrollments);
if ($stmt_new_enrollments) {
    $stmt_new_enrollments->bind_param("i", $user_id);
    $stmt_new_enrollments->execute();
    $result_new_enrollments = $stmt_new_enrollments->get_result();
    while ($row = $result_new_enrollments->fetch_assoc()) {
        $notifications[] = [
            'icon' => '✅',
            'message' => 'Anda berhasil mendaftar pada mata praktikum <a href="course_detail.php?id=' . htmlspecialchars($row['course_id']) . '" class="font-semibold text-blue-600 hover:underline">' . htmlspecialchars($row['course_name']) . '</a>.',
            'timestamp' => strtotime($row['date'])
        ];
    }
    $stmt_new_enrollments->close();
} else {
    error_log("Failed to prepare statement for new enrollments: " . $conn->error);
}


// Urutkan notifikasi berdasarkan timestamp (terbaru di atas)
usort($notifications, function($a, $b) {
    return $b['timestamp'] <=> $a['timestamp'];
});

// Ambil hanya 5 notifikasi terbaru
$notifications = array_slice($notifications, 0, 5);


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
        transition: background-color 0.2s ease; /* Transisi halus */
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
        <div class="stat-value text-blue-600"><?php echo $total_praktikum_diikuti; ?></div>
        <div class="stat-label">Praktikum Diikuti</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-value text-green-500"><?php echo $total_tugas_selesai; ?></div>
        <div class="stat-label">Tugas Selesai</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-value text-yellow-500"><?php echo $total_tugas_menunggu; ?></div>
        <div class="stat-label">Tugas Menunggu</div>
    </div>
    
</div>

<div class="notifications-section">
    <h3 class="text-2xl font-bold text-gray-800 mb-4">Notifikasi Terbaru</h3>
    <ul class="notification-list">
        <?php if (!empty($notifications)): ?>
            <?php foreach ($notifications as $notification): ?>
                <li class="notification-item">
                    <span class="notification-icon"><?php echo $notification['icon']; ?></span>
                    <div class="notification-text">
                        <?php echo $notification['message']; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <li class="notification-item">
                <div class="notification-text text-gray-500">Tidak ada notifikasi baru.</div>
            </li>
        <?php endif; ?>
    </ul>
</div>

<?php
// Panggil Footer
require_once 'templates/footer_mahasiswa.php';
$conn->close();