<?php
$pageTitle = 'Kelola Mata Praktikum';
$activePage = 'manage_courses';
require_once 'templates/header_asisten.php';
require_once '../config.php'; // Pastikan path ke config.php benar

$message = '';
$message_type = ''; // 'success' or 'error'

// Handle Create/Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $course_name = trim($_POST['course_name']);
    $description = trim($_POST['description']);
    $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;

    if (empty($course_name)) {
        $message = "Nama praktikum tidak boleh kosong!";
        $message_type = 'error';
    } else {
        if ($course_id > 0) {
            // Update existing course
            $sql = "UPDATE courses SET course_name = ?, description = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $course_name, $description, $course_id);
            if ($stmt->execute()) {
                $message = "Mata praktikum berhasil diperbarui!";
                $message_type = 'success';
            } else {
                $message = "Gagal memperbarui mata praktikum: " . $stmt->error;
                $message_type = 'error';
            }
            $stmt->close();
        } else {
            // Create new course
            $sql = "INSERT INTO courses (course_name, description) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $course_name, $description);
            if ($stmt->execute()) {
                $message = "Mata praktikum berhasil ditambahkan!";
                $message_type = 'success';
            } else {
                $message = "Gagal menambahkan mata praktikum: " . $stmt->error;
                $message_type = 'error';
            }
            $stmt->close();
        }
    }
}

// Handle Delete
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $course_id = intval($_GET['id']);
    $sql = "DELETE FROM courses WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $course_id);
    if ($stmt->execute()) {
        $message = "Mata praktikum berhasil dihapus!";
        $message_type = 'success';
    } else {
        $message = "Gagal menghapus mata praktikum: " . $stmt->error;
        $message_type = 'error';
    }
    $stmt->close();
    // Redirect to clear GET parameters
    header("Location: manage_courses.php?message=" . urlencode($message) . "&type=" . urlencode($message_type));
    exit();
}

// Fetch message from GET parameters after redirect
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_type = htmlspecialchars($_GET['type']);
}

// Fetch all courses
$courses = [];
$sql_select = "SELECT id, course_name, description FROM courses ORDER BY course_name ASC";
$result_select = $conn->query($sql_select);
if ($result_select && $result_select->num_rows > 0) {
    while ($row = $result_select->fetch_assoc()) {
        $courses[] = $row;
    }
}

// Get course data for editing if ID is provided
$edit_course = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $edit_id = intval($_GET['id']);
    $sql_edit = "SELECT id, course_name, description FROM courses WHERE id = ?";
    $stmt_edit = $conn->prepare($sql_edit);
    $stmt_edit->bind_param("i", $edit_id);
    $stmt_edit->execute();
    $result_edit = $stmt_edit->get_result();
    if ($result_edit->num_rows === 1) {
        $edit_course = $result_edit->fetch_assoc();
    }
    $stmt_edit->close();
}
?>

<div class="bg-white p-6 rounded-xl shadow-md mb-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-4"><?php echo $edit_course ? 'Edit Mata Praktikum' : 'Tambah Mata Praktikum Baru'; ?></h1>
    
    <?php if (!empty($message)): ?>
        <div class="p-3 mb-4 text-sm rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>" role="alert">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <form action="manage_courses.php" method="POST" class="space-y-4">
        <input type="hidden" name="course_id" value="<?php echo $edit_course ? htmlspecialchars($edit_course['id']) : ''; ?>">
        
        <div>
            <label for="course_name" class="block text-sm font-medium text-gray-700">Nama Praktikum</label>
            <input type="text" id="course_name" name="course_name" value="<?php echo $edit_course ? htmlspecialchars($edit_course['course_name']) : ''; ?>" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>
        
        <div>
            <label for="description" class="block text-sm font-medium text-gray-700">Deskripsi</label>
            <textarea id="description" name="description" rows="3" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"><?php echo $edit_course ? htmlspecialchars($edit_course['description']) : ''; ?></textarea>
        </div>
        
        <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <?php echo $edit_course ? 'Perbarui Praktikum' : 'Tambah Praktikum'; ?>
        </button>
        <?php if ($edit_course): ?>
            <a href="manage_courses.php" class="ml-3 inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Batal
            </a>
        <?php endif; ?>
    </form>
</div>

<div class="bg-white p-6 rounded-xl shadow-md">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">Daftar Mata Praktikum</h2>
    <?php if (empty($courses)): ?>
        <p class="text-gray-600">Belum ada mata praktikum yang ditambahkan.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Praktikum</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($courses as $course): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($course['course_name']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($course['description']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="manage_modules.php?course_id=<?php echo $course['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-4">Kelola Modul</a>
                                <a href="manage_courses.php?action=edit&id=<?php echo $course['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</a>
                                <a href="manage_courses.php?action=delete&id=<?php echo $course['id']; ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus praktikum ini? Ini juga akan menghapus semua modul dan pendaftaran terkait.');" class="text-red-600 hover:text-red-900">Hapus</a>
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
