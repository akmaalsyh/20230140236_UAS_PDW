<?php
// Include configuration file
require_once '../config.php';

// Include database connection
// require_once 'db.php';

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input data
    $nama_mata_kuliah = htmlspecialchars(trim($_POST['nama_mata_kuliah']));
    $deskripsi = htmlspecialchars(trim($_POST['deskripsi']));
    $asisten_id = intval($_POST['asisten_id']);

    // Validate input
    if (empty($nama_mata_kuliah) || empty($deskripsi) || empty($asisten_id)) {
        $_SESSION['error'] = 'Semua field harus diisi.';
        header('Location: addMataKuliah.php'); // Redirect back to the form with error message
        exit();
    }

    // Insert data into the database
    $query = "INSERT INTO mata_kuliah (nama_mata_kuliah, deskripsi, asisten_id) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'ssi', $nama_mata_kuliah, $deskripsi, $asisten_id);

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = 'Mata Kuliah berhasil ditambahkan.';
        header('Location: mataKuliah.php'); // Redirect to the main course list page
        exit();
    } else {
        $_SESSION['error'] = 'Gagal menambahkan Mata Kuliah. Silakan coba lagi.';
        header('Location: addMataKuliah.php'); // Redirect back to the form with error message
        exit();
    }
} else {
    // If accessed directly, redirect to the main page
    header('Location: mataKuliah.php');
    exit();
}
?>