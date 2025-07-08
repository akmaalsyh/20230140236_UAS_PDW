<?php
// error_reporting(E_ALL); // Tampilkan semua error - Komentar ini untuk produksi
// ini_set('display_errors', 1); // Tampilkan error di browser - Komentar ini untuk produksi

$pageTitle = 'Beri Nilai Laporan';
$activePage = 'submissions'; // Tetap aktifkan menu 'Laporan Masuk'
require_once 'templates/header_asisten.php';
require_once '../config.php'; // Pastikan path ke config.php benar

$submission_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$asisten_id = $_SESSION['user_id']; // ID asisten yang sedang login

if ($submission_id === 0) {
    header("Location: submissions.php?message=" . urlencode("ID laporan tidak valid.") . "&type=error");
    exit();
}

$submission = null;
$message = '';
$message_type = '';

// Handle grading submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_grade'])) {
    $grade_value = trim($_POST['grade_value']);
    $feedback = trim($_POST['feedback']);

    // Validasi nilai
    if (!is_numeric($grade_value) || $grade_value < 0 || $grade_value > 100) {
        $message = "Nilai harus angka antara 0 dan 100.";
        $message_type = 'error';
    } else {
        // Cek apakah sudah ada nilai untuk submission ini
        $sql_check_grade = "SELECT id FROM grades WHERE submission_id = ?";
        $stmt_check_grade = $conn->prepare($sql_check_grade);
        if ($stmt_check_grade) { // Check if prepare was successful
            $stmt_check_grade->bind_param("i", $submission_id);
            $stmt_check_grade->execute();
            $stmt_check_grade->store_result();

            if ($stmt_check_grade->num_rows > 0) {
                // Update existing grade
                $sql_update_grade = "UPDATE grades SET grade_value = ?, feedback = ?, graded_by = ?, graded_at = CURRENT_TIMESTAMP WHERE submission_id = ?";
                $stmt_update_grade = $conn->prepare($sql_update_grade);
                if ($stmt_update_grade) { // Check if prepare was successful
                    $stmt_update_grade->bind_param("dsii", $grade_value, $feedback, $asisten_id, $submission_id);
                    if ($stmt_update_grade->execute()) {
                        // Update submission status to 'graded'
                        $sql_update_submission_status = "UPDATE submissions SET status = 'graded' WHERE id = ?";
                        $stmt_update_submission_status = $conn->prepare($sql_update_submission_status);
                        if ($stmt_update_submission_status) { // Check if prepare was successful
                            $stmt_update_submission_status->bind_param("i", $submission_id);
                            $stmt_update_submission_status->execute();
                            $stmt_update_submission_status->close();
                        } else {
                            error_log("Failed to prepare statement for updating submission status: " . $conn->error);
                        }

                        $message = "Nilai dan feedback berhasil diperbarui!";
                        $message_type = 'success';
                    } else {
                        $message = "Gagal memperbarui nilai: " . $stmt_update_grade->error;
                        $message_type = 'error';
                    }
                    $stmt_update_grade->close();
                } else {
                    $message = "Gagal menyiapkan statement update nilai: " . $conn->error;
                    $message_type = 'error';
                }
            } else {
                // Insert new grade
                $sql_insert_grade = "INSERT INTO grades (submission_id, grade_value, feedback, graded_by) VALUES (?, ?, ?, ?)";
                $stmt_insert_grade = $conn->prepare($sql_insert_grade);
                if ($stmt_insert_grade) { // Check if prepare was successful
                    $stmt_insert_grade->bind_param("idsi", $submission_id, $grade_value, $feedback, $asisten_id);
                    if ($stmt_insert_grade->execute()) {
                        // Update submission status to 'graded'
                        $sql_update_submission_status = "UPDATE submissions SET status = 'graded' WHERE id = ?";
                        $stmt_update_submission_status = $conn->prepare($sql_update_submission_status);
                        if ($stmt_update_submission_status) { // Check if prepare was successful
                            $stmt_update_submission_status->bind_param("i", $submission_id);
                            $stmt_update_submission_status->execute();
                            $stmt_update_submission_status->close();
                        } else {
                            error_log("Failed to prepare statement for updating submission status: " . $conn->error);
                        }

                        $message = "Nilai dan feedback berhasil disimpan!";
                        $message_type = 'success';
                    } else {
                        $message = "Gagal menyimpan nilai: " . $stmt_insert_grade->error;
                        $message_type = 'error';
                    }
                    $stmt_insert_grade->close();
                } else {
                    $message = "Gagal menyiapkan statement insert nilai: " . $conn->error;
                    $message_type = 'error';
                }
            }
            $stmt_check_grade->close();
        } else {
            $message = "Gagal menyiapkan statement cek nilai: " . $conn->error;
            $message_type = 'error';
        }
    }
    // Redirect to clear POST data
    header("Location: grade_submission.php?id=" . $submission_id . "&message=" . urlencode($message) . "&type=" . urlencode($message_type));
    exit();
}

// Fetch submission details along with module, course, student, and existing grade info
$sql_submission_detail = "
    SELECT 
        s.id AS submission_id, 
        s.submission_file, 
        s.submission_date, 
        s.status,
        m.module_name,
        m.description AS module_description,
        m.due_date,
        c.course_name,
        c.description AS course_description,
        u.id AS student_id,
        u.nama AS student_name,
        u.email AS student_email,
        g.grade_value,
        g.feedback,
        ga.nama AS graded_by_name
    FROM submissions s
    JOIN modules m ON s.module_id = m.id
    JOIN courses c ON m.course_id = c.id
    JOIN users u ON s.user_id = u.id
    LEFT JOIN grades g ON s.id = g.submission_id
    LEFT JOIN users ga ON g.graded_by = ga.id
    WHERE s.id = ?";
$stmt_submission_detail = $conn->prepare($sql_submission_detail);
if ($stmt_submission_detail) { // Check if prepare was successful
    $stmt_submission_detail->bind_param("i", $submission_id);
    $stmt_submission_detail->execute();
    $result_submission_detail = $stmt_submission_detail->get_result();
    if ($result_submission_detail->num_rows === 1) {
        $submission = $result_submission_detail->fetch_assoc();
    } else {
        // Laporan tidak ditemukan
        header("Location: submissions.php?message=" . urlencode("Laporan tidak ditemukan.") . "&type=error");
        exit();
    }
    $stmt_submission_detail->close();
} else {
    error_log("Failed to prepare statement for submission detail: " . $conn->error);
    $message = "Terjadi kesalahan saat mengambil detail laporan. Silakan coba lagi.";
    $message_type = 'error';
}


// Fetch message from GET parameters after redirect
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_type = htmlspecialchars($_GET['type']);
}
?>

<div class="bg-white p-6 rounded-xl shadow-md mb-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-4">Detail Laporan & Penilaian</h1>
    <p class="text-gray-600">Laporan dari: <span class="font-semibold"><?php echo htmlspecialchars($submission['student_name']); ?> (<?php echo htmlspecialchars($submission['student_email']); ?>)</span></p>
    <p class="text-gray-600">Praktikum: <span class="font-semibold"><?php echo htmlspecialchars($submission['course_name']); ?></span></p>
    <p class="text-gray-600">Modul: <span class="font-semibold"><?php echo htmlspecialchars($submission['module_name']); ?></span></p>
    <p class="text-gray-600">Tanggal Kumpul: <span class="font-semibold"><?php echo date('d M Y H:i', strtotime($submission['submission_date'])); ?></span></p>
    <p class="text-gray-600">Batas Waktu Modul: <span class="font-semibold"><?php echo date('d M Y H:i', strtotime($submission['due_date'])); ?></span></p>

    <div class="mt-4">
        <a href="../uploads/submissions/<?php echo htmlspecialchars($submission['submission_file']); ?>" download class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md transition-colors duration-300">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            Unduh File Laporan
        </a>
    </div>
</div>

<?php if (!empty($message)): ?>
    <div class="p-3 mb-4 text-sm rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>" role="alert">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="bg-white p-6 rounded-xl shadow-md">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">Form Penilaian</h2>
    <form action="grade_submission.php?id=<?php echo $submission_id; ?>" method="POST" class="space-y-4">
        <div>
            <label for="grade_value" class="block text-sm font-medium text-gray-700">Nilai (0-100)</label>
            <input type="number" id="grade_value" name="grade_value" min="0" max="100" step="0.01" value="<?php echo htmlspecialchars($submission['grade_value'] ?? ''); ?>" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>
        <div>
            <label for="feedback" class="block text-sm font-medium text-gray-700">Feedback (Opsional)</label>
            <textarea id="feedback" name="feedback" rows="5" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"><?php echo htmlspecialchars($submission['feedback'] ?? ''); ?></textarea>
        </div>
        <button type="submit" name="submit_grade" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            Simpan Nilai
        </button>
        <a href="submissions.php" class="ml-3 inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            Kembali ke Daftar Laporan
        </a>
    </form>
</div>

<?php
require_once 'templates/footer_asisten.php';
$conn->close();
?>
