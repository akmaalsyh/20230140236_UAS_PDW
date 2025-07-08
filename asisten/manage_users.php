<?php
$pageTitle = 'Kelola Akun Pengguna';
$activePage = 'manage_users';
require_once 'templates/header_asisten.php';
require_once '../config.php'; // Pastikan path ke config.php benar

$message = '';
$message_type = ''; // 'success' or 'error'

// Handle Create/Update User
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']);

    // Validasi input
    if (empty($nama) || empty($email) || empty($role)) {
        $message = "Nama, email, dan peran harus diisi!";
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Format email tidak valid!";
        $message_type = 'error';
    } elseif (!in_array($role, ['mahasiswa', 'asisten'])) {
        $message = "Peran tidak valid!";
        $message_type = 'error';
    } else {
        // Cek apakah email sudah terdaftar (kecuali untuk user yang sedang diedit)
        $sql_check_email = "SELECT id FROM users WHERE email = ?";
        if ($user_id > 0) {
            $sql_check_email .= " AND id != ?";
        }
        $stmt_check_email = $conn->prepare($sql_check_email);
        if ($stmt_check_email) {
            if ($user_id > 0) {
                $stmt_check_email->bind_param("si", $email, $user_id);
            } else {
                $stmt_check_email->bind_param("s", $email);
            }
            $stmt_check_email->execute();
            $stmt_check_email->store_result();

            if ($stmt_check_email->num_rows > 0) {
                $message = "Email sudah terdaftar. Silakan gunakan email lain.";
                $message_type = 'error';
            } else {
                // Proses password
                $hashed_password = null;
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                }

                if ($user_id > 0) {
                    // Update existing user
                    $sql_update = "UPDATE users SET nama = ?, email = ?, role = ?";
                    if ($hashed_password) {
                        $sql_update .= ", password = ?";
                    }
                    $sql_update .= " WHERE id = ?";
                    $stmt_update = $conn->prepare($sql_update);

                    if ($stmt_update) {
                        if ($hashed_password) {
                            $stmt_update->bind_param("ssssi", $nama, $email, $role, $hashed_password, $user_id);
                        } else {
                            $stmt_update->bind_param("sssi", $nama, $email, $role, $user_id);
                        }
                        
                        if ($stmt_update->execute()) {
                            $message = "Akun pengguna berhasil diperbarui!";
                            $message_type = 'success';
                        } else {
                            $message = "Gagal memperbarui akun pengguna: " . $stmt_update->error;
                            $message_type = 'error';
                        }
                        $stmt_update->close();
                    } else {
                        $message = "Gagal menyiapkan statement update pengguna: " . $conn->error;
                        $message_type = 'error';
                    }
                } else {
                    // Create new user
                    if (empty($password)) {
                        $message = "Password harus diisi untuk akun baru!";
                        $message_type = 'error';
                    } else {
                        $sql_insert = "INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)";
                        $stmt_insert = $conn->prepare($sql_insert);
                        if ($stmt_insert) {
                            $stmt_insert->bind_param("ssss", $nama, $email, $hashed_password, $role);
                            if ($stmt_insert->execute()) {
                                $message = "Akun pengguna berhasil ditambahkan!";
                                $message_type = 'success';
                            } else {
                                $message = "Gagal menambahkan akun pengguna: " . $stmt_insert->error;
                                $message_type = 'error';
                            }
                            $stmt_insert->close();
                        } else {
                            $message = "Gagal menyiapkan statement insert pengguna: " . $conn->error;
                            $message_type = 'error';
                        }
                    }
                }
            }
            $stmt_check_email->close();
        } else {
            $message = "Gagal menyiapkan statement cek email: " . $conn->error;
            $message_type = 'error';
        }
    }
    // Redirect to clear POST data
    header("Location: manage_users.php?message=" . urlencode($message) . "&type=" . urlencode($message_type));
    exit();
}

// Handle Delete User
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $user_id_to_delete = intval($_GET['id']);

    // Pastikan asisten tidak bisa menghapus akunnya sendiri
    if ($user_id_to_delete == $_SESSION['user_id']) {
        $message = "Anda tidak bisa menghapus akun Anda sendiri!";
        $message_type = 'error';
    } else {
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $user_id_to_delete);
            if ($stmt->execute()) {
                $message = "Akun pengguna berhasil dihapus!";
                $message_type = 'success';
            } else {
                $message = "Gagal menghapus akun pengguna: " . $stmt->error;
                $message_type = 'error';
            }
            $stmt->close();
        } else {
            $message = "Gagal menyiapkan statement delete pengguna: " . $conn->error;
            $message_type = 'error';
        }
    }
    // Redirect to clear GET parameters
    header("Location: manage_users.php?message=" . urlencode($message) . "&type=" . urlencode($message_type));
    exit();
}

// Fetch message from GET parameters after redirect
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_type = htmlspecialchars($_GET['type']);
}

// Fetch all users
$users = [];
$sql_select = "SELECT id, nama, email, role FROM users ORDER BY role ASC, nama ASC";
$result_select = $conn->query($sql_select);
if ($result_select && $result_select->num_rows > 0) {
    while ($row = $result_select->fetch_assoc()) {
        $users[] = $row;
    }
}

// Get user data for editing if ID is provided
$edit_user = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $edit_id = intval($_GET['id']);
    $sql_edit = "SELECT id, nama, email, role FROM users WHERE id = ?";
    $stmt_edit = $conn->prepare($sql_edit);
    if ($stmt_edit) {
        $stmt_edit->bind_param("i", $edit_id);
        $stmt_edit->execute();
        $result_edit = $stmt_edit->get_result();
        if ($result_edit->num_rows === 1) {
            $edit_user = $result_edit->fetch_assoc();
        }
        $stmt_edit->close();
    } else {
        error_log("Failed to prepare statement for editing user: " . $conn->error);
        $message = "Terjadi kesalahan saat mengambil data pengguna untuk diedit. Silakan coba lagi.";
        $message_type = 'error';
    }
}
?>

<div class="bg-white p-6 rounded-xl shadow-md mb-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-4"><?php echo $edit_user ? 'Edit Akun Pengguna' : 'Tambah Akun Pengguna Baru'; ?></h1>
    
    <?php if (!empty($message)): ?>
        <div class="p-3 mb-4 text-sm rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>" role="alert">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <form action="manage_users.php" method="POST" class="space-y-4">
        <input type="hidden" name="user_id" value="<?php echo $edit_user ? htmlspecialchars($edit_user['id']) : ''; ?>">
        
        <div>
            <label for="nama" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
            <input type="text" id="nama" name="nama" value="<?php echo $edit_user ? htmlspecialchars($edit_user['nama']) : ''; ?>" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>
        
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input type="email" id="email" name="email" value="<?php echo $edit_user ? htmlspecialchars($edit_user['email']) : ''; ?>" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">Password <?php echo $edit_user ? '(Kosongkan jika tidak ingin mengubah)' : ''; ?></label>
            <input type="password" id="password" name="password" <?php echo $edit_user ? '' : 'required'; ?> class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>
        
        <div>
            <label for="role" class="block text-sm font-medium text-gray-700">Peran</label>
            <select id="role" name="role" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                <option value="mahasiswa" <?php echo ($edit_user && $edit_user['role'] == 'mahasiswa') ? 'selected' : ''; ?>>Mahasiswa</option>
                <option value="asisten" <?php echo ($edit_user && $edit_user['role'] == 'asisten') ? 'selected' : ''; ?>>Asisten</option>
            </select>
        </div>
        
        <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <?php echo $edit_user ? 'Perbarui Pengguna' : 'Tambah Pengguna'; ?>
        </button>
        <?php if ($edit_user): ?>
            <a href="manage_users.php" class="ml-3 inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Batal
            </a>
        <?php endif; ?>
    </form>
</div>

<div class="bg-white p-6 rounded-xl shadow-md">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">Daftar Akun Pengguna</h2>
    <?php if (empty($users)): ?>
        <p class="text-gray-600">Belum ada akun pengguna yang terdaftar.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Peran</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($user['nama']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($user['email']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars(ucfirst($user['role'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="manage_users.php?action=edit&id=<?php echo $user['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</a>
                                <?php if ($user['id'] != $_SESSION['user_id']): // Jangan biarkan asisten menghapus akunnya sendiri ?>
                                    <a href="manage_users.php?action=delete&id=<?php echo $user['id']; ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus akun ini? Ini juga akan menghapus semua data terkait seperti pendaftaran praktikum, laporan, dan nilai.');" class="text-red-600 hover:text-red-900">Hapus</a>
                                <?php else: ?>
                                    <span class="text-gray-400">Tidak dapat dihapus</span>
                                <?php endif; ?>
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
