<?php
session_start();
require_once __DIR__ . '/../config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Ambil data modul
$query = "SELECT * FROM modul WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $_SESSION['error'] = "Modul tidak ditemukan.";
    header("Location: daftar_modul.php?mk_id=" . $_GET['mk_id']);
    exit();
}

$modul = $stmt->fetch_assoc();

// Handle submit form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_modul = htmlspecialchars(trim($_POST['nama_modul']));
    $pertemuan = intval($_POST['pertemuan']);
    $deskripsi = htmlspecialchars(trim($_POST['deskripsi']));

    // Update database
    $update_query = "UPDATE modul SET nama_modul = ?, pertemuan = ?, deskripsi = ? WHERE id = ?";
    $stmt_update = $conn->prepare($update_query);
    $stmt_update->bind_param("sisi", $nama_modul, $pertemuan, $deskripsi, $id);

    if ($stmt_update->execute()) {
        $_SESSION['success'] = "Modul berhasil diperbarui.";
        header("Location: daftar_modul.php?mk_id=" . $modul['mata_kuliah_id']);
        exit();
    } else {
        $_SESSION['error'] = "Gagal memperbarui modul.";
        header("Location: edit_modul.php?id=" . $id);
        exit();
    }
}
?>

<!-- Include header -->
<?php require_once __DIR__ . '/templates/header.php'; ?>

<!-- Form Edit Modul -->
<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-semibold text-gray-800 mb-4">Edit Modul</h1>

    <form action="" method="post" class="bg-white shadow-md rounded p-6 space-y-4">
        <div>
            <label for="nama_modul" class="block text-gray-700 font-medium mb-2">Nama Modul</label>
            <input type="text" name="nama_modul" id="nama_modul"
                   value="<?= htmlspecialchars($modul['nama_modul']) ?>"
                   required class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:border-indigo-500">
        </div>

        <div>
            <label for="pertemuan" class="block text-gray-700 font-medium mb-2">Pertemuan</label>
            <input type="number" name="pertemuan" id="pertemuan"
                   value="<?= htmlspecialchars($modul['pertemuan']) ?>"
                   min="1" required class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:border-indigo-500">
        </div>

        <div>
            <label for="deskripsi" class="block text-gray-700 font-medium mb-2">Deskripsi</label>
            <textarea name="deskripsi" id="deskripsi" rows="4"
                      class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:border-indigo-500"><?= htmlspecialchars($modul['deskripsi']) ?></textarea>
        </div>

        <button type="submit"
                class="mt-4 bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded w-full">
            Simpan Perubahan
        </button>
    </form>
</div>

<!-- Include footer -->
<?php require_once __DIR__ . '/templates/footer.php'; ?>