<?php
// Include configuration file
require_once __DIR__ . '/../config.php';

// Get course ID from URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch course details
$query = "SELECT mk.id, mk.nama_mata_kuliah, mk.deskripsi, mk.asisten_id
          FROM mata_kuliah mk
          WHERE mk.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Mata kuliah tidak ditemukan.");
}

$row = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_mata_kuliah = htmlspecialchars(trim($_POST['nama_mata_kuliah']));
    $deskripsi = htmlspecialchars(trim($_POST['deskripsi']));
    $asisten_id = intval($_POST['asisten_id']);

    // Update query
    $updateQuery = "UPDATE mata_kuliah SET nama_mata_kuliah = ?, deskripsi = ?, asisten_id = ? WHERE id = ?";
    $stmtUpdate = $conn->prepare($updateQuery);
    $stmtUpdate->bind_param("ssii", $nama_mata_kuliah, $deskripsi, $asisten_id, $id);

    if ($stmtUpdate->execute()) {
        $_SESSION['success'] = 'Mata Kuliah berhasil diperbarui.';
        header('Location: mataKuliah.php');
        exit();
    } else {
        $_SESSION['error'] = 'Gagal memperbarui Mata Kuliah. Silakan coba lagi.';
    }
}
?>

<!-- Include header template -->
<?php require_once __DIR__ . '/templates/header.php'; ?>

<!-- Main Content -->
<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-semibold text-gray-800 mb-4">Edit Mata Kuliah</h1>

    <form action="" method="POST" class="bg-white shadow-md rounded p-6">
        <div class="mb-4">
            <label for="nama_mata_kuliah" class="block text-gray-700 font-medium mb-2">Nama Mata Kuliah</label>
            <input type="text" name="nama_mata_kuliah" id="nama_mata_kuliah" value="<?= htmlspecialchars($row['nama_mata_kuliah']) ?>" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-indigo-500">
        </div>

        <div class="mb-4">
            <label for="deskripsi" class="block text-gray-700 font-medium mb-2">Deskripsi</label>
            <textarea name="deskripsi" id="deskripsi" rows="4" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-indigo-500"><?= htmlspecialchars($row['deskripsi']) ?></textarea>
        </div>

        <div class="mb-4">
            <label for="asisten_id" class="block text-gray-700 font-medium mb-2">Asisten</label>
            <select name="asisten_id" id="asisten_id" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-indigo-500">
                <option value="">Pilih Asisten</option>
                <?php
                // Query to fetch all assistants (users with role 'asisten')
                $queryAssistants = "SELECT id, nama FROM users WHERE role = 'asisten'";
                $resultAssistants = mysqli_query($conn, $queryAssistants);

                if (mysqli_num_rows($resultAssistants) > 0) {
                    while ($assistant = mysqli_fetch_assoc($resultAssistants)) {
                        echo '<option value="' . htmlspecialchars($assistant['id']) . '"' . ($assistant['id'] == $row['asisten_id'] ? ' selected' : '') . '>' . htmlspecialchars($assistant['nama']) . '</option>';
                    }
                }
                ?>
            </select>
        </div>

        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded w-full">
            Simpan Perubahan
        </button>
    </form>
</div>

<!-- Include footer template -->
<?php require_once __DIR__ . '/templates/footer.php'; ?>