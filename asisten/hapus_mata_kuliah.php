<?php
// Include configuration file
require_once __DIR__ . '/../config.php';

// Get course ID from URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Delete query
$query = "DELETE FROM mata_kuliah WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $_SESSION['success'] = 'Mata Kuliah berhasil dihapus.';
} else {
    $_SESSION['error'] = 'Gagal menghapus Mata Kuliah. Silakan coba lagi.';
}

header('Location: mataKuliah.php');
exit();
?>