<?php
error_reporting(E_ALL); // Tampilkan semua error
ini_set('display_errors', 1); // Tampilkan error di browser

$pageTitle = 'Kelola Modul Praktikum';
$activePage = 'manage_modules';
require_once 'templates/header_asisten.php';
require_once '../config.php'; // Pastikan path ke config.php benar

$message = '';
$message_type = ''; // 'success' or 'error'

$course_id_filter = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

// Handle Create/Update Module
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $module_id = isset($_POST['module_id']) ? intval($_POST['module_id']) : 0;
    $course_id = intval($_POST['course_id']);
    $module_name = trim($_POST['module_name']);
    $description = trim($_POST['description']);
    $due_date = trim($_POST['due_date']);

    // Validasi input
    if (empty($module_name) || empty($course_id)) {
        $message = "Nama modul dan praktikum harus diisi!";
        $message_type = 'error';
    } else {
        $material_file_path = null;
        $upload_success = true;

        // Handle file upload
        if (isset($_FILES['material_file']) && $_FILES['material_file']['error'] == UPLOAD_ERR_OK) {
            $target_dir = "../uploads/materials/";
            // Pastikan direktori ada
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $file_name = basename($_FILES["material_file"]["name"]);
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_extensions = ['pdf', 'doc', 'docx']; // Hanya izinkan PDF, DOC, DOCX

            if (!in_array($file_extension, $allowed_extensions)) {
                $message = "Hanya file PDF, DOC, atau DOCX yang diizinkan untuk materi.";
                $message_type = 'error';
                $upload_success = false;
            } elseif ($_FILES["material_file"]["size"] > 10000000) { // Max 10MB
                $message = "Ukuran file materi terlalu besar. Maksimal 10MB.";
                $message_type = 'error';
                $upload_success = false;
            } else {
                // Buat nama file unik
                $new_file_name = uniqid('material_') . '.' . $file_extension;
                $target_file = $target_dir . $new_file_name;

                if (move_uploaded_file($_FILES["material_file"]["tmp_name"], $target_file)) {
                    $material_file_path = $new_file_name;
                } else {
                    $message = "Terjadi kesalahan saat mengunggah file materi.";
                    $message_type = 'error';
                    $upload_success = false;
                }
            }
        }

        if ($upload_success) {
            if ($module_id > 0) {
                // Update existing module
                $sql = "UPDATE modules SET course_id = ?, module_name = ?, description = ?, due_date = ?";
                if ($material_file_path) {
                    // Jika ada file baru diunggah, hapus file lama jika ada
                    $sql_old_file = "SELECT material_file FROM modules WHERE id = ?";
                    $stmt_old_file = $conn->prepare($sql_old_file);
                    if ($stmt_old_file) {
                        $stmt_old_file->bind_param("i", $module_id);
                        $stmt_old_file->execute();
                        $result_old_file = $stmt_old_file->get_result();
                        if ($result_old_file->num_rows > 0) {
                            $old_file = $result_old_file->fetch_assoc()['material_file'];
                            if ($old_file && file_exists($target_dir . $old_file)) {
                                unlink($target_dir . $old_file);
                            }
                        }
                        $stmt_old_file->close();
                    } else {
                        error_log("Failed to prepare statement for old file check: " . $conn->error);
                    }
                    $sql .= ", material_file = ?";
                }
                $sql .= " WHERE id = ?";
                $stmt = $conn->prepare($sql);

                if ($stmt) {
                    if ($material_file_path) {
                        $stmt->bind_param("issssi", $course_id, $module_name, $description, $due_date, $material_file_path, $module_id);
                    } else {
                        $stmt->bind_param("isssi", $course_id, $module_name, $description, $due_date, $module_id);
                    }

                    if ($stmt->execute()) {
                        $message = "Modul berhasil diperbarui!";
                        $message_type = 'success';
                    } else {
                        $message = "Gagal memperbarui modul: " . $stmt->error;
                        $message_type = 'error';
                    }
                    $stmt->close();
                } else {
                    $message = "Gagal menyiapkan statement update modul: " . $conn->error;
                    $message_type = 'error';
                }
            } else {
                // Create new module
                $sql = "INSERT INTO modules (course_id, module_name, description, material_file, due_date) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("issss", $course_id, $module_name, $description, $material_file_path, $due_date);
                    if ($stmt->execute()) {
                        $message = "Modul berhasil ditambahkan!";
                        $message_type = 'success';
                    } else {
                        $message = "Gagal menambahkan modul: " . $stmt->error;
                        $message_type = 'error';
                    }
                    $stmt->close();
                } else {
                    $message = "Gagal menyiapkan statement insert modul: " . $conn->error;
                    $message_type = 'error';
                }
            }
        }
    }
    // Redirect to clear POST data
    header("Location: manage_modules.php?course_id=" . $course_id . "&message=" . urlencode($message) . "&type=" . urlencode($message_type));
    exit();
}

// Handle Delete Module
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $module_id_to_delete = intval($_GET['id']);
    $target_dir = "../uploads/materials/";

    // Ambil nama file materi sebelum menghapus record
    $sql_get_file = "SELECT material_file FROM modules WHERE id = ?";
    $stmt_get_file = $conn->prepare($sql_get_file);
    if ($stmt_get_file) {
        $stmt_get_file->bind_param("i", $module_id_to_delete);
        $stmt_get_file->execute();
        $result_get_file = $stmt_get_file->get_result();
        $file_to_delete = null;
        if ($result_get_file->num_rows > 0) {
            $file_to_delete = $result_get_file->fetch_assoc()['material_file'];
        }
        $stmt_get_file->close();
    } else {
        error_log("Failed to prepare statement for get file before delete: " . $conn->error);
    }


    $sql = "DELETE FROM modules WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $module_id_to_delete);
        if ($stmt->execute()) {
            // Hapus file fisik jika ada
            if ($file_to_delete && file_exists($target_dir . $file_to_delete)) {
                unlink($target_dir . $file_to_delete);
            }
            $message = "Modul berhasil dihapus!";
            $message_type = 'success';
        } else {
            $message = "Gagal menghapus modul: " . $stmt->error;
            $message_type = 'error';
        }
        $stmt->close();
    } else {
        $message = "Gagal menyiapkan statement delete modul: " . $conn->error;
        $message_type = 'error';
    }
    // Redirect to clear GET parameters
    header("Location: manage_modules.php?course_id=" . $course_id_filter . "&message=" . urlencode($message) . "&type=" . urlencode($message_type));
    exit();
}

// Fetch message from GET parameters after redirect
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_type = htmlspecialchars($_GET['type']);
}

// Fetch all courses for dropdown filter and module assignment
$all_courses = [];
$sql_all_courses = "SELECT id, course_name FROM courses ORDER BY course_name ASC";
$result_all_courses = $conn->query($sql_all_courses);
if ($result_all_courses && $result_all_courses->num_rows > 0) {
    while ($row = $result_all_courses->fetch_assoc()) {
        $all_courses[] = $row;
    }
}

// Fetch modules based on selected course filter
$modules = [];
$sql_modules = "SELECT m.id, m.module_name, m.description, m.material_file, m.due_date, c.course_name 
                FROM modules m
                JOIN courses c ON m.course_id = c.id";
$params = [];
$types = "";

if ($course_id_filter > 0) {
    $sql_modules .= " WHERE m.course_id = ?";
    $params[] = $course_id_filter;
    $types .= "i";
}
$sql_modules .= " ORDER BY c.course_name ASC, m.due_date ASC";

$stmt_modules = $conn->prepare($sql_modules);
if ($stmt_modules) { // Check if prepare was successful
    if ($course_id_filter > 0) {
        $stmt_modules->bind_param($types, ...$params);
    }
    $stmt_modules->execute();
    $result_modules = $stmt_modules->get_result();
    if ($result_modules && $result_modules->num_rows > 0) {
        while ($row = $result_modules->fetch_assoc()) {
            $modules[] = $row;
        }
    }
    $stmt_modules->close();
} else {
    error_log("Failed to prepare statement for fetching modules: " . $conn->error);
    $message = "Terjadi kesalahan saat mengambil data modul. Silakan coba lagi.";
    $message_type = 'error';
}


// Get module data for editing if ID is provided
$edit_module = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $edit_id = intval($_GET['id']);
    $sql_edit = "SELECT id, course_id, module_name, description, material_file, due_date FROM modules WHERE id = ?";
    $stmt_edit = $conn->prepare($sql_edit);
    if ($stmt_edit) { // Check if prepare was successful
        $stmt_edit->bind_param("i", $edit_id);
        $stmt_edit->execute();
        $result_edit = $stmt_edit->get_result();
        if ($result_edit->num_rows === 1) {
            $edit_module = $result_edit->fetch_assoc();
        }
        $stmt_edit->close();
    } else {
        error_log("Failed to prepare statement for editing module: " . $conn->error);
        $message = "Terjadi kesalahan saat mengambil data modul untuk diedit. Silakan coba lagi.";
        $message_type = 'error';
    }
}
?>

<div class="bg-white p-6 rounded-xl shadow-md mb-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-4"><?php echo $edit_module ? 'Edit Modul Praktikum' : 'Tambah Modul Praktikum Baru'; ?></h1>
    
    <?php if (!empty($message)): ?>
        <div class="p-3 mb-4 text-sm rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>" role="alert">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <form action="manage_modules.php" method="POST" enctype="multipart/form-data" class="space-y-4">
        <input type="hidden" name="module_id" value="<?php echo $edit_module ? htmlspecialchars($edit_module['id']) : ''; ?>">
        
        <div>
            <label for="course_id" class="block text-sm font-medium text-gray-700">Pilih Praktikum</label>
            <select id="course_id" name="course_id" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                <option value="">-- Pilih Praktikum --</option>
                <?php foreach ($all_courses as $course): ?>
                    <option value="<?php echo htmlspecialchars($course['id']); ?>" 
                        <?php echo ($edit_module && $edit_module['course_id'] == $course['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($course['course_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="module_name" class="block text-sm font-medium text-gray-700">Nama Modul</label>
            <input type="text" id="module_name" name="module_name" value="<?php echo $edit_module ? htmlspecialchars($edit_module['module_name']) : ''; ?>" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>
        
        <div>
            <label for="description" class="block text-sm font-medium text-gray-700">Deskripsi Modul</label>
            <textarea id="description" name="description" rows="3" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"><?php echo $edit_module ? htmlspecialchars($edit_module['description']) : ''; ?></textarea>
        </div>

        <div>
            <label for="material_file" class="block text-sm font-medium text-gray-700">File Materi (PDF/DOCX, maks 10MB)</label>
            <input type="file" id="material_file" name="material_file" accept=".pdf,.doc,.docx" class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none">
            <?php if ($edit_module && $edit_module['material_file']): ?>
                <p class="mt-2 text-sm text-gray-500">File saat ini: <a href="../uploads/materials/<?php echo htmlspecialchars($edit_module['material_file']); ?>" download class="text-blue-600 hover:underline"><?php echo htmlspecialchars($edit_module['material_file']); ?></a></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="due_date" class="block text-sm font-medium text-gray-700">Batas Waktu Pengumpulan (Opsional)</label>
            <input type="datetime-local" id="due_date" name="due_date" value="<?php echo $edit_module && $edit_module['due_date'] ? date('Y-m-d\TH:i', strtotime($edit_module['due_date'])) : ''; ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>
        
        <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <?php echo $edit_module ? 'Perbarui Modul' : 'Tambah Modul'; ?>
        </button>
        <?php if ($edit_module): ?>
            <a href="manage_modules.php?course_id=<?php echo htmlspecialchars($edit_module['course_id']); ?>" class="ml-3 inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Batal
            </a>
        <?php endif; ?>
    </form>
</div>

<div class="bg-white p-6 rounded-xl shadow-md">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">Daftar Modul</h2>

    <div class="mb-4">
        <form action="manage_modules.php" method="GET" class="flex items-center space-x-2">
            <label for="filter_course_id" class="text-sm font-medium text-gray-700">Filter Praktikum:</label>
            <select id="filter_course_id" name="course_id" onchange="this.form.submit()" class="mt-1 block px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                <option value="0">-- Semua Praktikum --</option>
                <?php foreach ($all_courses as $course): ?>
                    <option value="<?php echo htmlspecialchars($course['id']); ?>" 
                        <?php echo ($course_id_filter == $course['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($course['course_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if (empty($modules)): ?>
        <p class="text-gray-600">Belum ada modul yang ditambahkan untuk praktikum ini atau filter yang dipilih.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Praktikum</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Modul</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batas Waktu</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Materi</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($modules as $module): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($module['course_name']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($module['module_name']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $module['due_date'] ? date('d M Y H:i', strtotime($module['due_date'])) : '-'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php if ($module['material_file']): ?>
                                    <a href="../uploads/materials/<?php echo htmlspecialchars($module['material_file']); ?>" download class="text-blue-600 hover:underline">Unduh</a>
                                <?php else: ?>
                                    Tidak ada
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="manage_modules.php?action=edit&id=<?php echo $module['id']; ?>&course_id=<?php echo htmlspecialchars($course_id_filter); ?>" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</a>
                                <a href="manage_modules.php?action=delete&id=<?php echo $module['id']; ?>&course_id=<?php echo htmlspecialchars($course_id_filter); ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus modul ini? Ini juga akan menghapus semua laporan dan nilai terkait.');" class="text-red-600 hover:text-red-900">Hapus</a>
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
