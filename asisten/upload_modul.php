<?php
session_start(); // Harus ada di awal
require_once __DIR__ . '/../config.php';

// Ambil mk_id dari URL
$mk_id = isset($_GET['mk_id']) ? intval($_GET['mk_id']) : 0;

// Validasi apakah mk_id valid
if ($mk_id <= 0) {
    $_SESSION['error'] = "ID Mata Kuliah tidak valid.";
    header("Location: mataKuliah.php");
    exit();
}

// Query untuk ambil nama mata kuliah (untuk ditampilkan di halaman)
$query_mk = "SELECT nama_mata_kuliah FROM mata_kuliah WHERE id = ?";
$stmt = $conn->prepare($query_mk);
$stmt->bind_param("i", $mk_id);
$stmt->execute();
$result_mk = $stmt->get_result();

if ($result_mk->num_rows === 0) {
    $_SESSION['error'] = "Mata kuliah tidak ditemukan.";
    header("Location: mataKuliah.php");
    exit();
}

$row_mk = $result_mk->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_modul = htmlspecialchars(trim($_POST['nama_modul']));
    $pertemuan = intval($_POST['pertemuan']);
    $deskripsi = htmlspecialchars(trim($_POST['deskripsi']));

    // Upload file
    if (isset($_FILES['materi_file']) && $_FILES['materi_file']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $file_name = $_FILES['materi_file']['name'];
        $file_tmp = $_FILES['materi_file']['tmp_name'];
        $file_type = mime_content_type($file_tmp);

        if (!in_array($file_type, $allowed_types)) {
            $_SESSION['error'] = "Hanya file PDF atau DOC/DOCX yang diperbolehkan.";
            header("Location: upload_modul.php?mk_id=" . $mk_id);
            exit();
        }

        // Generate nama unik untuk file
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $new_file_name = uniqid('modul_', true) . "." . $file_ext;
        $upload_dir = "../uploads/modul/";

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
            // Simpan ke database
            $insert_query = "INSERT INTO modul (nama_modul, pertemuan, deskripsi, materi_file, mata_kuliah_id)
                             VALUES (?, ?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($insert_query);
            $stmt_insert->bind_param("sissi", $nama_modul, $pertemuan, $deskripsi, $new_file_name, $mk_id);

            if ($stmt_insert->execute()) {
                $_SESSION['success'] = "Modul berhasil diunggah.";
                header("Location: mataKuliah.php");
                exit();
            } else {
                $_SESSION['error'] = "Gagal menyimpan data modul.";
                header("Location: upload_modul.php?mk_id=" . $mk_id);
                exit();
            }
        } else {
            $_SESSION['error'] = "Gagal mengunggah file.";
            header("Location: upload_modul.php?mk_id=" . $mk_id);
            exit();
        }
    } else {
        $_SESSION['error'] = "File materi harus diupload.";
        header("Location: upload_modul.php?mk_id=" . $mk_id);
        exit();
    }
}
?>

<?php require_once __DIR__ . '/templates/header.php'; ?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-extrabold text-gray-900 mb-2 animate-fade-in-down">Upload Modul</h1>
    <p class="text-xl text-gray-700 mb-6 border-b-2 border-indigo-300 pb-2 animate-fade-in-down delay-100">Untuk Mata Kuliah: <strong class="text-indigo-600"><?= htmlspecialchars($row_mk['nama_mata_kuliah']) ?></strong></p>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm animate-fade-in" role="alert">
            <p class="font-medium"><?= $_SESSION['error']; unset($_SESSION['error']); ?></p>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm animate-fade-in" role="alert">
            <p class="font-medium"><?= $_SESSION['success']; unset($_SESSION['success']); ?></p>
        </div>
    <?php endif; ?>

    <form action="" method="post" enctype="multipart/form-data"
        class="bg-white shadow-lg rounded-xl p-8 space-y-6 animate-fade-in-up">
        
        <div>
            <label for="nama_modul" class="block text-gray-700 font-semibold mb-2 text-lg">Nama Modul</label>
            <input type="text" name="nama_modul" id="nama_modul" required
                class="w-full px-5 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-gray-800 transition duration-200 ease-in-out placeholder-gray-400"
                placeholder="Mis: Pengantar HTML dan CSS">
        </div>

        <div>
            <label for="pertemuan" class="block text-gray-700 font-semibold mb-2 text-lg">Pertemuan ke-</label>
            <input type="number" name="pertemuan" id="pertemuan" min="1" required
                class="w-full px-5 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-gray-800 transition duration-200 ease-in-out"
                placeholder="Mis: 1">
        </div>

        <div>
            <label for="deskripsi" class="block text-gray-700 font-semibold mb-2 text-lg">Deskripsi</label>
            <textarea name="deskripsi" id="deskripsi" rows="5" required
                class="w-full px-5 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-gray-800 transition duration-200 ease-in-out resize-y"
                placeholder="Jelaskan isi modul secara singkat..."></textarea>
        </div>

        <div>
            <label for="materi_file" class="block text-gray-700 font-semibold mb-2 text-lg">Upload Materi (PDF/DOC/DOCX)</label>
            <input type="file" name="materi_file" id="materi_file" required
                class="w-full text-gray-800 border border-gray-300 rounded-lg py-2 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition duration-200 ease-in-out file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
            <p class="text-sm text-gray-500 mt-1">Ukuran file maksimal: 5MB</p> </div>

        <div class="mt-8">
            <button type="submit"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-lg transition-all duration-300 ease-in-out transform hover:scale-105 shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                Upload Modul
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>