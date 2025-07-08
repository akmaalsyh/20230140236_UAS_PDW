<?php
$pageTitle = 'Cari Praktikum';
$activePage = 'courses';
require_once 'templates/header_mahasiswa.php';
require_once '../config.php'; // Pastikan path ke config.php benar

$message = '';
$message_type = ''; // 'success' or 'error'

// Handle pendaftaran praktikum
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['enroll_course_id'])) {
    $course_id = intval($_POST['enroll_course_id']);
    $user_id = $_SESSION['user_id'];

    // Cek apakah mahasiswa sudah terdaftar di praktikum ini
    $sql_check = "SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $user_id, $course_id);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        $message = "Anda sudah terdaftar di praktikum ini!";
        $message_type = 'error';
    } else {
        // Daftarkan mahasiswa
        $sql_enroll = "INSERT INTO enrollments (user_id, course_id) VALUES (?, ?)";
        $stmt_enroll = $conn->prepare($sql_enroll);
        $stmt_enroll->bind_param("ii", $user_id, $course_id);
        if ($stmt_enroll->execute()) {
            $message = "Berhasil mendaftar ke praktikum!";
            $message_type = 'success';
        } else {
            $message = "Gagal mendaftar ke praktikum: " . $stmt_enroll->error;
            $message_type = 'error';
        }
        $stmt_enroll->close();
    }
    $stmt_check->close();
    // Redirect to clear POST data and show message
    header("Location: courses.php?message=" . urlencode($message) . "&type=" . urlencode($message_type));
    exit();
}

// Fetch message from GET parameters after redirect
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_type = htmlspecialchars($_GET['type']);
}

// Fetch all available courses
$courses = [];
$sql_select = "SELECT id, course_name, description FROM courses ORDER BY course_name ASC";
$result_select = $conn->query($sql_select);
if ($result_select && $result_select->num_rows > 0) {
    while ($row = $result_select->fetch_assoc()) {
        $courses[] = $row;
    }
}
?>

<div class="bg-gradient-to-r from-blue-500 to-cyan-400 text-white p-8 rounded-xl shadow-lg mb-8">
    <h1 class="text-3xl font-bold">Cari Mata Praktikum</h1>
    <p class="mt-2 opacity-90">Temukan dan daftar ke mata praktikum yang tersedia.</p>
</div>

<?php if (!empty($message)): ?>
    <div class="p-3 mb-4 text-sm rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>" role="alert">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (empty($courses)): ?>
        <p class="col-span-full text-center text-gray-600">Belum ada mata praktikum yang tersedia saat ini.</p>
    <?php else: ?>
        <?php foreach ($courses as $course): ?>
            <div class="bg-white p-6 rounded-xl shadow-md flex flex-col">
                <h2 class="text-xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($course['course_name']); ?></h2>
                <p class="text-gray-600 mb-4 flex-grow"><?php echo htmlspecialchars($course['description']); ?></p>
                <form action="courses.php" method="POST">
                    <input type="hidden" name="enroll_course_id" value="<?php echo $course['id']; ?>">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md transition-colors duration-300">
                        Daftar Praktikum
                    </button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
require_once 'templates/footer_mahasiswa.php';
$conn->close();
?>
