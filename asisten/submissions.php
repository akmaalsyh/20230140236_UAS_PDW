<?php
$pageTitle = 'Laporan Masuk';
$activePage = 'submissions';
require_once 'templates/header_asisten.php';
require_once '../config.php'; // Pastikan path ke config.php benar

$message = '';
$message_type = ''; // 'success' or 'error'

// Fetch message from GET parameters after redirect (e.g., after grading)
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_type = htmlspecialchars($_GET['type']);
}

// Filter parameters
$filter_module_id = isset($_GET['module_id']) ? intval($_GET['module_id']) : 0;
$filter_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$filter_status = isset($_GET['status']) ? htmlspecialchars($_GET['status']) : '';

// Fetch all modules for filter dropdown
$all_modules = [];
$sql_all_modules = "SELECT m.id, m.module_name, c.course_name 
                    FROM modules m 
                    JOIN courses c ON m.course_id = c.id 
                    ORDER BY c.course_name, m.module_name ASC";
$result_all_modules = $conn->query($sql_all_modules);
if ($result_all_modules && $result_all_modules->num_rows > 0) {
    while ($row = $result_all_modules->fetch_assoc()) {
        $all_modules[] = $row;
    }
}

// Fetch all students (users with role 'mahasiswa') for filter dropdown
$all_students = [];
$sql_all_students = "SELECT id, nama FROM users WHERE role = 'mahasiswa' ORDER BY nama ASC";
$result_all_students = $conn->query($sql_all_students);
if ($result_all_students && $result_all_students->num_rows > 0) {
    while ($row = $result_all_students->fetch_assoc()) {
        $all_students[] = $row;
    }
}

// Fetch submissions based on filters
$submissions = [];
$sql_submissions = "
    SELECT 
        s.id AS submission_id, 
        s.submission_file, 
        s.submission_date, 
        s.status,
        m.module_name,
        m.due_date,
        c.course_name,
        u.nama AS student_name,
        u.email AS student_email,
        g.grade_value,
        g.feedback
    FROM submissions s
    JOIN modules m ON s.module_id = m.id
    JOIN courses c ON m.course_id = c.id
    JOIN users u ON s.user_id = u.id
    LEFT JOIN grades g ON s.id = g.submission_id
    WHERE 1=1"; // Dummy condition to easily append AND clauses

$params = [];
$types = "";

if ($filter_module_id > 0) {
    $sql_submissions .= " AND s.module_id = ?";
    $params[] = $filter_module_id;
    $types .= "i";
}
if ($filter_user_id > 0) {
    $sql_submissions .= " AND s.user_id = ?";
    $params[] = $filter_user_id;
    $types .= "i";
}
if (!empty($filter_status) && in_array($filter_status, ['submitted', 'graded'])) {
    $sql_submissions .= " AND s.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

$sql_submissions .= " ORDER BY s.submission_date DESC";

$stmt_submissions = $conn->prepare($sql_submissions);
if (!empty($params)) {
    $stmt_submissions->bind_param($types, ...$params);
}
$stmt_submissions->execute();
$result_submissions = $stmt_submissions->get_result();
if ($result_submissions && $result_submissions->num_rows > 0) {
    while ($row = $result_submissions->fetch_assoc()) {
        $submissions[] = $row;
    }
}
$stmt_submissions->close();
?>

<div class="bg-white p-6 rounded-xl shadow-md mb-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-4">Laporan Masuk</h1>
    <p class="text-gray-600">Lihat dan kelola laporan yang dikumpulkan oleh mahasiswa.</p>
</div>

<?php if (!empty($message)): ?>
    <div class="p-3 mb-4 text-sm rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>" role="alert">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="bg-white p-6 rounded-xl shadow-md mb-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">Filter Laporan</h2>
    <form action="submissions.php" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label for="filter_module_id" class="block text-sm font-medium text-gray-700">Modul:</label>
            <select id="filter_module_id" name="module_id" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                <option value="0">-- Semua Modul --</option>
                <?php foreach ($all_modules as $module): ?>
                    <option value="<?php echo htmlspecialchars($module['id']); ?>" <?php echo ($filter_module_id == $module['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($module['course_name'] . ' - ' . $module['module_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="filter_user_id" class="block text-sm font-medium text-gray-700">Mahasiswa:</label>
            <select id="filter_user_id" name="user_id" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                <option value="0">-- Semua Mahasiswa --</option>
                <?php foreach ($all_students as $student): ?>
                    <option value="<?php echo htmlspecialchars($student['id']); ?>" <?php echo ($filter_user_id == $student['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($student['nama']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="filter_status" class="block text-sm font-medium text-gray-700">Status:</label>
            <select id="filter_status" name="status" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                <option value="">-- Semua Status --</option>
                <option value="submitted" <?php echo ($filter_status == 'submitted') ? 'selected' : ''; ?>>Belum Dinilai</option>
                <option value="graded" <?php echo ($filter_status == 'graded') ? 'selected' : ''; ?>>Sudah Dinilai</option>
            </select>
        </div>
        <div class="col-span-full flex justify-end">
            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Terapkan Filter
            </button>
            <a href="submissions.php" class="ml-3 inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Reset Filter
            </a>
        </div>
    </form>
</div>

<div class="bg-white p-6 rounded-xl shadow-md">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">Daftar Laporan</h2>
    <?php if (empty($submissions)): ?>
        <p class="text-gray-600">Tidak ada laporan yang ditemukan dengan filter yang dipilih.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mahasiswa</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Praktikum</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Modul</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Kumpul</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nilai</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($submissions as $submission): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($submission['student_name']); ?>
                                <span class="block text-xs text-gray-500"><?php echo htmlspecialchars($submission['student_email']); ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($submission['course_name']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($submission['module_name']); ?>
                                <span class="block text-xs text-gray-500">Batas: <?php echo date('d M Y', strtotime($submission['due_date'])); ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('d M Y H:i', strtotime($submission['submission_date'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?php if ($submission['status'] == 'submitted'): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Belum Dinilai</span>
                                <?php elseif ($submission['status'] == 'graded'): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Sudah Dinilai</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo $submission['grade_value'] ?? '-'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="grade_submission.php?id=<?php echo $submission['submission_id']; ?>" class="text-indigo-600 hover:text-indigo-900">
                                    <?php echo ($submission['status'] == 'graded') ? 'Lihat/Edit Nilai' : 'Beri Nilai'; ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
require_once 'templates/footer_asisten.php';
$conn->close();
?>
