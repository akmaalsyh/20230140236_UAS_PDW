<?php
$pageTitle = 'Praktikum Saya';
$activePage = 'my_courses';
require_once 'templates/header_mahasiswa.php';
require_once '../config.php'; // Pastikan path ke config.php benar

$user_id = $_SESSION['user_id'];

// Fetch courses the student is enrolled in
$enrolled_courses = [];
$sql_select = "SELECT c.id, c.course_name, c.description 
               FROM enrollments e
               JOIN courses c ON e.course_id = c.id
               WHERE e.user_id = ?
               ORDER BY c.course_name ASC";
$stmt_select = $conn->prepare($sql_select);
$stmt_select->bind_param("i", $user_id);
$stmt_select->execute();
$result_select = $stmt_select->get_result();
if ($result_select && $result_select->num_rows > 0) {
    while ($row = $result_select->fetch_assoc()) {
        $enrolled_courses[] = $row;
    }
}
$stmt_select->close();
?>

<div class="bg-gradient-to-r from-blue-500 to-cyan-400 text-white p-8 rounded-xl shadow-lg mb-8">
    <h1 class="text-3xl font-bold">Praktikum Saya</h1>
    <p class="mt-2 opacity-90">Daftar mata praktikum yang Anda ikuti.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (empty($enrolled_courses)): ?>
        <p class="col-span-full text-center text-gray-600">Anda belum terdaftar di praktikum manapun. Silakan <a href="courses.php" class="text-blue-600 hover:underline">cari praktikum</a> untuk mendaftar.</p>
    <?php else: ?>
        <?php foreach ($enrolled_courses as $course): ?>
            <div class="bg-white p-6 rounded-xl shadow-md flex flex-col">
                <h2 class="text-xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($course['course_name']); ?></h2>
                <p class="text-gray-600 mb-4 flex-grow"><?php echo htmlspecialchars($course['description']); ?></p>
                <a href="course_detail.php?id=<?php echo $course['id']; ?>" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md text-center transition-colors duration-300">
                    Lihat Detail & Tugas
                </a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
require_once 'templates/footer_mahasiswa.php';
$conn->close();
?>
