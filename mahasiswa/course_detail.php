<?php
$pageTitle = 'Detail Praktikum & Tugas';
$activePage = 'my_courses'; // Tetap aktifkan menu 'Praktikum Saya'
require_once 'templates/header_mahasiswa.php';
require_once '../config.php'; // Pastikan path ke config.php benar

$course_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];

if ($course_id === 0) {
    // Redirect jika tidak ada ID praktikum yang diberikan
    header("Location: my_courses.php");
    exit();
}

// Cek apakah mahasiswa terdaftar di praktikum ini
$is_enrolled = false;
$sql_check_enrollment = "SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?";
$stmt_check_enrollment = $conn->prepare($sql_check_enrollment);
$stmt_check_enrollment->bind_param("ii", $user_id, $course_id);
$stmt_check_enrollment->execute();
$stmt_check_enrollment->store_result();
if ($stmt_check_enrollment->num_rows > 0) {
    $is_enrolled = true;
}
$stmt_check_enrollment->close();

if (!$is_enrolled) {
    // Redirect jika mahasiswa tidak terdaftar di praktikum ini
    header("Location: my_courses.php?message=" . urlencode("Anda tidak terdaftar di praktikum ini.") . "&type=error");
    exit();
}

$course = null;
$modules = [];
$message = '';
$message_type = '';

// Fetch course details
$sql_course = "SELECT id, course_name, description FROM courses WHERE id = ?";
$stmt_course = $conn->prepare($sql_course);
$stmt_course->bind_param("i", $course_id);
$stmt_course->execute();
$result_course = $stmt_course->get_result();
if ($result_course->num_rows === 1) {
    $course = $result_course->fetch_assoc();
} else {
    // Praktikum tidak ditemukan
    header("Location: my_courses.php");
    exit();
}
$stmt_course->close();

// Handle file upload for submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_report']) && isset($_FILES['report_file'])) {
    $module_id = intval($_POST['module_id']);
    $target_dir = "../uploads/submissions/";
    // Pastikan direktori ada
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_name = basename($_FILES["report_file"]["name"]);
    $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed_extensions = ['pdf', 'doc', 'docx']; // Hanya izinkan PDF, DOC, DOCX

    if (!in_array($file_extension, $allowed_extensions)) {
        $message = "Hanya file PDF, DOC, atau DOCX yang diizinkan.";
        $message_type = 'error';
    } elseif ($_FILES["report_file"]["size"] > 5000000) { // Max 5MB
        $message = "Ukuran file terlalu besar. Maksimal 5MB.";
        $message_type = 'error';
    } else {
        // Buat nama file unik
        $new_file_name = uniqid('submission_') . '.' . $file_extension;
        $target_file = $target_dir . $new_file_name;

        if (move_uploaded_file($_FILES["report_file"]["tmp_name"], $target_file)) {
            // Cek apakah sudah ada submission untuk modul ini dari user ini
            $sql_check_submission = "SELECT id, submission_file FROM submissions WHERE user_id = ? AND module_id = ?";
            $stmt_check_submission = $conn->prepare($sql_check_submission);
            $stmt_check_submission->bind_param("ii", $user_id, $module_id);
            $stmt_check_submission->execute();
            $result_check_submission = $stmt_check_submission->get_result();

            if ($result_check_submission->num_rows > 0) {
                // Update submission
                $existing_submission = $result_check_submission->fetch_assoc();
                // Hapus file lama jika ada
                if (file_exists($target_dir . $existing_submission['submission_file'])) {
                    unlink($target_dir . $existing_submission['submission_file']);
                }

                $sql_update_submission = "UPDATE submissions SET submission_file = ?, submission_date = CURRENT_TIMESTAMP, status = 'submitted' WHERE id = ?";
                $stmt_update_submission = $conn->prepare($sql_update_submission);
                $stmt_update_submission->bind_param("si", $new_file_name, $existing_submission['id']);
                if ($stmt_update_submission->execute()) {
                    $message = "Laporan berhasil diperbarui!";
                    $message_type = 'success';
                } else {
                    $message = "Gagal memperbarui laporan: " . $stmt_update_submission->error;
                    $message_type = 'error';
                }
                $stmt_update_submission->close();
            } else {
                // Insert new submission
                $sql_insert_submission = "INSERT INTO submissions (user_id, module_id, submission_file) VALUES (?, ?, ?)";
                $stmt_insert_submission = $conn->prepare($sql_insert_submission);
                $stmt_insert_submission->bind_param("iis", $user_id, $module_id, $new_file_name);
                if ($stmt_insert_submission->execute()) {
                    $message = "Laporan berhasil dikumpulkan!";
                    $message_type = 'success';
                } else {
                    $message = "Gagal mengumpulkan laporan: " . $stmt_insert_submission->error;
                    $message_type = 'error';
                }
                $stmt_insert_submission->close();
            }
            $stmt_check_submission->close();
        } else {
            $message = "Terjadi kesalahan saat mengunggah file.";
            $message_type = 'error';
        }
    }
    // Redirect to clear POST data
    header("Location: course_detail.php?id=" . $course_id . "&message=" . urlencode($message) . "&type=" . urlencode($message_type));
    exit();
}

// Fetch message from GET parameters after redirect
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_type = htmlspecialchars($_GET['type']);
}

// Fetch modules for this course, along with submission status and grade
$sql_modules = "
    SELECT 
        m.id AS module_id, 
        m.module_name, 
        m.description AS module_description, 
        m.material_file, 
        m.due_date,
        s.id AS submission_id,
        s.submission_file,
        s.submission_date,
        s.status AS submission_status,
        g.grade_value,
        g.feedback
    FROM modules m
    LEFT JOIN submissions s ON m.id = s.module_id AND s.user_id = ?
    LEFT JOIN grades g ON s.id = g.submission_id
    WHERE m.course_id = ?
    ORDER BY m.due_date ASC, m.id ASC";
$stmt_modules = $conn->prepare($sql_modules);
$stmt_modules->bind_param("ii", $user_id, $course_id);
$stmt_modules->execute();
$result_modules = $stmt_modules->get_result();
if ($result_modules && $result_modules->num_rows > 0) {
    while ($row = $result_modules->fetch_assoc()) {
        $modules[] = $row;
    }
}
$stmt_modules->close();
?>

<div class="bg-gradient-to-r from-blue-500 to-cyan-400 text-white p-8 rounded-xl shadow-lg mb-8">
    <h1 class="text-3xl font-bold"><?php echo htmlspecialchars($course['course_name']); ?></h1>
    <p class="mt-2 opacity-90"><?php echo htmlspecialchars($course['description']); ?></p>
</div>

<?php if (!empty($message)): ?>
    <div class="p-3 mb-4 text-sm rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>" role="alert">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="bg-white p-6 rounded-xl shadow-md mb-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">Daftar Modul & Tugas</h2>
    <?php if (empty($modules)): ?>
        <p class="text-gray-600">Belum ada modul yang ditambahkan untuk praktikum ini.</p>
    <?php else: ?>
        <div class="space-y-6">
            <?php foreach ($modules as $module): ?>
                <div class="border border-gray-200 rounded-lg p-4">
                    <h3 class="text-xl font-semibold text-gray-800 mb-2"><?php echo htmlspecialchars($module['module_name']); ?></h3>
                    <p class="text-gray-600 mb-2"><?php echo htmlspecialchars($module['module_description']); ?></p>
                    <?php if ($module['due_date']): ?>
                        <p class="text-sm text-gray-500 mb-2">Batas Waktu: <?php echo date('d M Y H:i', strtotime($module['due_date'])); ?></p>
                    <?php endif; ?>

                    <!-- Unduh Materi -->
                    <?php if ($module['material_file']): ?>
                        <div class="mb-3">
                            <a href="../uploads/materials/<?php echo htmlspecialchars($module['material_file']); ?>" download class="inline-flex items-center text-blue-600 hover:underline">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                Unduh Materi
                            </a>
                        </div>
                    <?php else: ?>
                        <p class="text-sm text-gray-500 mb-3">Materi belum tersedia.</p>
                    <?php endif; ?>

                    <!-- Status Laporan & Nilai -->
                    <div class="mb-3">
                        <p class="text-sm text-gray-700">Status Laporan: 
                            <?php if ($module['submission_status'] === 'submitted'): ?>
                                <span class="font-semibold text-yellow-600">Sudah Dikumpulkan</span>
                            <?php elseif ($module['submission_status'] === 'graded'): ?>
                                <span class="font-semibold text-green-600">Sudah Dinilai</span>
                            <?php else: ?>
                                <span class="font-semibold text-red-600">Belum Dikumpulkan</span>
                            <?php endif; ?>
                        </p>
                        <?php if ($module['submission_status'] === 'graded'): ?>
                            <p class="text-sm text-gray-700">Nilai: <span class="font-bold text-lg text-blue-700"><?php echo htmlspecialchars($module['grade_value']); ?></span></p>
                            <?php if (!empty($module['feedback'])): ?>
                                <p class="text-sm text-gray-700">Feedback: <span class="italic"><?php echo nl2br(htmlspecialchars($module['feedback'])); ?></span></p>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if ($module['submission_file']): ?>
                            <a href="../uploads/submissions/<?php echo htmlspecialchars($module['submission_file']); ?>" download class="inline-flex items-center text-purple-600 hover:underline text-sm mt-1">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                Lihat Laporan Anda
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Form Pengumpulan Laporan -->
                    <form action="course_detail.php?id=<?php echo $course_id; ?>" method="POST" enctype="multipart/form-data" class="mt-4 p-4 border border-blue-200 rounded-md bg-blue-50">
                        <h4 class="font-semibold text-blue-800 mb-2">Unggah Laporan untuk Modul Ini</h4>
                        <input type="hidden" name="module_id" value="<?php echo $module['module_id']; ?>">
                        <div class="mb-3">
                            <label for="report_file_<?php echo $module['module_id']; ?>" class="block text-sm font-medium text-gray-700">Pilih File Laporan (PDF/DOCX, maks 5MB)</label>
                            <input type="file" id="report_file_<?php echo $module['module_id']; ?>" name="report_file" accept=".pdf,.doc,.docx" required class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none">
                        </div>
                        <button type="submit" name="submit_report" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <?php echo ($module['submission_status'] === 'submitted' || $module['submission_status'] === 'graded') ? 'Perbarui Laporan' : 'Kumpulkan Laporan'; ?>
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
require_once 'templates/footer_mahasiswa.php';
$conn->close();
?>
