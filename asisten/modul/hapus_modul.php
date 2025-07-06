<?php
session_start();
require_once __DIR__ . '/../config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Ambil info modul untuk hapus file
$query = "SELECT materi_file, mata_kuliah_id FROM modul WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $_SESSION['error'] = "Modul tidak ditemukan.";
    header("Location: daftar_modul.php?mk_id=" . $_GET['mk_id']);
    exit();
}

$modul = $result->fetch_assoc();

// Hapus file fisik
$file_path = "../uploads/modul/" . $modul['materi_file'];
if (file_exists($file_path)) {
    unlink($file_path); // Hapus file
}

// Hapus data dari database
$delete_query = "DELETE FROM modul WHERE id = ?";
$stmt_delete = $conn->prepare($delete_query);
$stmt_delete->bind_param("i", $id);

if ($stmt_delete->execute()) {
    $_SESSION['success'] = "Modul berhasil dihapus.";
} else {
    $_SESSION['error'] = "Gagal menghapus modul.";
}

header("Location: daftar_modul.php?mk_id=" . $modul['mata_kuliah_id']);
exit();
?>