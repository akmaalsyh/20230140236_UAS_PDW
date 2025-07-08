<?php
$pageTitle = 'Dashboard Asisten';
$activePage = 'dashboard';
require_once 'templates/header_asisten.php';
require_once '../config.php'; // Pastikan path ke config.php benar

// --- Ambil Data Statistik dari Database ---

// 1. Total Praktikum
$total_courses = 0;
$sql_courses = "SELECT COUNT(*) AS total FROM courses";
$result_courses = $conn->query($sql_courses);
if ($result_courses && $result_courses->num_rows > 0) {
    $row = $result_courses->fetch_assoc();
    $total_courses = $row['total'];
} else {
    error_log("Failed to fetch total courses: " . $conn->error);
}

// 2. Total Modul
$total_modules = 0;
$sql_modules = "SELECT COUNT(*) AS total FROM modules";
$result_modules = $conn->query($sql_modules);
if ($result_modules && $result_modules->num_rows > 0) {
    $row = $result_modules->fetch_assoc();
    $total_modules = $row['total'];
} else {
    error_log("Failed to fetch total modules: " . $conn->error);
}

// 3. Laporan Belum Dinilai (Submissions dengan status 'submitted')
$total_submissions_pending = 0;
$sql_submissions_pending = "SELECT COUNT(*) AS total FROM submissions WHERE status = 'submitted'";
$result_submissions_pending = $conn->query($sql_submissions_pending);
if ($result_submissions_pending && $result_submissions_pending->num_rows > 0) {
    $row = $result_submissions_pending->fetch_assoc();
    $total_submissions_pending = $row['total'];
} else {
    error_log("Failed to fetch pending submissions: " . $conn->error);
}


// --- Ambil Notifikasi Terbaru dari Database ---
$notifications = [];

// Notifikasi: Laporan baru masuk (ambil 3 laporan terbaru yang belum dinilai)
$sql_new_submissions = "
    SELECT 
        s.submission_date AS date,
        m.module_name,
        c.course_name,
        u.nama AS student_name,
        s.id AS submission_id
    FROM submissions s
    JOIN modules m ON s.module_id = m.id
    JOIN courses c ON m.course_id = c.id
    JOIN users u ON s.user_id = u.id
    WHERE s.status = 'submitted'
    ORDER BY s.submission_date DESC
    LIMIT 3";
$stmt_new_submissions = $conn->prepare($sql_new_submissions);
if ($stmt_new_submissions) {
    $stmt_new_submissions->execute();
    $result_new_submissions = $stmt_new_submissions->get_result();
    while ($row = $result_new_submissions->fetch_assoc()) {
        $notifications[] = [
            'icon' => '📝',
            'message' => 'Laporan baru untuk <span class="font-semibold text-indigo-600">' . htmlspecialchars($row['module_name']) . '</span> dari ' . htmlspecialchars($row['student_name']) . ' pada praktikum ' . htmlspecialchars($row['course_name']) . ' telah masuk.',
            'link' => 'grade_submission.php?id=' . htmlspecialchars($row['submission_id']),
            'timestamp' => strtotime($row['date'])
        ];
    }
    $stmt_new_submissions->close();
} else {
    error_log("Failed to prepare statement for new submissions: " . $conn->error);
}

// Notifikasi: Batas waktu pengumpulan modul yang akan datang (dalam 7 hari ke depan)
$sql_upcoming_due_dates = "
    SELECT 
        m.module_name,
        m.due_date,
        c.course_name,
        m.id AS module_id
    FROM modules m
    JOIN courses c ON m.course_id = c.id
    WHERE m.due_date IS NOT NULL
    AND m.due_date >= NOW()
    AND m.due_date <= DATE_ADD(NOW(), INTERVAL 7 DAY)
    ORDER BY m.due_date ASC
    LIMIT 3";
$stmt_upcoming_due_dates = $conn->prepare($sql_upcoming_due_dates);
if ($stmt_upcoming_due_dates) {
    $stmt_upcoming_due_dates->execute();
    $result_upcoming_due_dates = $stmt_upcoming_due_dates->get_result();
    while ($row = $result_upcoming_due_dates->fetch_assoc()) {
        $notifications[] = [
            'icon' => '⏰',
            'message' => 'Batas waktu pengumpulan untuk <span class="font-semibold text-indigo-600">' . htmlspecialchars($row['module_name']) . '</span> pada praktikum ' . htmlspecialchars($row['course_name']) . ' akan segera berakhir pada ' . date('d M Y H:i', strtotime($row['due_date'])) . '.',
            'link' => 'manage_modules.php?action=edit&id=' . htmlspecialchars($row['module_id']),
            'timestamp' => strtotime($row['due_date'])
        ];
    }
    $stmt_upcoming_due_dates->close();
} else {
    error_log("Failed to prepare statement for upcoming due dates (asisten): " . $conn->error);
}

// Notifikasi: Pendaftaran mahasiswa baru (ambil 3 pendaftaran terbaru)
$sql_new_enrollments = "
    SELECT 
        e.enrolled_at AS date,
        u.nama AS student_name,
        c.course_name,
        u.id AS user_id
    FROM enrollments e
    JOIN users u ON e.user_id = u.id
    JOIN courses c ON e.course_id = c.id
    ORDER BY e.enrolled_at DESC
    LIMIT 3";
$stmt_new_enrollments = $conn->prepare($sql_new_enrollments);
if ($stmt_new_enrollments) {
    $stmt_new_enrollments->execute();
    $result_new_enrollments = $stmt_new_enrollments->get_result();
    while ($row = $result_new_enrollments->fetch_assoc()) {
        $notifications[] = [
            'icon' => '👨‍🎓',
            'message' => '<span class="font-semibold text-indigo-600">' . htmlspecialchars($row['student_name']) . '</span> baru saja mendaftar ke praktikum <span class="font-semibold text-indigo-600">' . htmlspecialchars($row['course_name']) . '</span>.',
            'link' => 'manage_users.php?action=edit&id=' . htmlspecialchars($row['user_id']),
            'timestamp' => strtotime($row['date'])
        ];
    }
    $stmt_new_enrollments->close();
} else {
    error_log("Failed to prepare statement for new enrollments (asisten): " . $conn->error);
}


// Urutkan notifikasi berdasarkan timestamp (terbaru di atas)
usort($notifications, function($a, $b) {
    return $b['timestamp'] <=> $a['timestamp'];
});

// Ambil hanya 5 notifikasi terbaru (gabungan dari semua jenis)
$notifications = array_slice($notifications, 0, 5);

?>

<div class="bg-gradient-to-r from-indigo-600 to-purple-500 text-white p-8 rounded-xl shadow-lg mb-8">
    <h1 class="text-3xl font-bold">Selamat Datang Kembali, <?php echo htmlspecialchars($_SESSION['nama']); ?>!</h1>
    <p class="mt-2 opacity-90">Panel Asisten untuk mengelola praktikum.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    
    <!-- Card Total Praktikum -->
    <div class="bg-white p-6 rounded-xl shadow-md flex flex-col items-center justify-center">
        <div class="text-5xl font-extrabold text-indigo-600"><?php echo $total_courses; ?></div>
        <div class="mt-2 text-lg text-gray-600">Total Praktikum</div>
    </div>
    
    <!-- Card Total Modul -->
    <div class="bg-white p-6 rounded-xl shadow-md flex flex-col items-center justify-center">
        <div class="text-5xl font-extrabold text-green-500"><?php echo $total_modules; ?></div>
        <div class="mt-2 text-lg text-gray-600">Total Modul</div>
    </div>
    
    <!-- Card Laporan Belum Dinilai -->
    <div class="bg-white p-6 rounded-xl shadow-md flex flex-col items-center justify-center">
        <div class="text-5xl font-extrabold text-yellow-500"><?php echo $total_submissions_pending; ?></div>
        <div class="mt-2 text-lg text-gray-600">Laporan Belum Dinilai</div>
    </div>
    
</div>

<div class="bg-white p-6 rounded-xl shadow-md">
    <h3 class="text-2xl font-bold text-gray-800 mb-4">Notifikasi Terbaru</h3>
    <ul class="space-y-4">
        <?php if (!empty($notifications)): ?>
            <?php foreach ($notifications as $notification): ?>
                <li class="flex items-start p-3 border-b border-gray-100 last:border-b-0">
                    <span class="text-xl mr-4"><?php echo $notification['icon']; ?></span>
                    <div>
                        <?php echo $notification['message']; ?>
                        <?php if (isset($notification['link'])): ?>
                            <a href="<?php echo $notification['link']; ?>" class="font-semibold text-blue-600 hover:underline">Lihat Detail</a>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <li class="p-3 text-gray-500">Tidak ada notifikasi baru.</li>
        <?php endif; ?>
    </ul>
</div>

<?php
require_once 'templates/footer_asisten.php';
$conn->close();
?>
