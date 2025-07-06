<?php
session_start();
require_once 'config.php';

// Jika sudah login, redirect ke halaman yang sesuai
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'asisten') {
        header("Location: asisten/dashboard.php");
        exit();
    } elseif ($_SESSION['role'] == 'mahasiswa') {
        header("Location: mahasiswa/dashboard.php");
        exit();
    }
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $message = "Email dan password harus diisi!";
    } else {
        $sql = "SELECT id, nama, email, password, role FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();

                // Verifikasi password
                if (password_verify($password, $user['password'])) {
                    // Password benar, simpan semua data penting ke session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['nama'] = $user['nama'];
                    $_SESSION['role'] = $user['role'];

                    // Logika untuk mengarahkan pengguna berdasarkan peran (role)
                    if ($user['role'] == 'asisten') {
                        header("Location: asisten/dashboard.php");
                        exit();
                    } elseif ($user['role'] == 'mahasiswa') {
                        header("Location: mahasiswa/dashboard.php");
                        exit();
                    } else {
                        $message = "Peran pengguna tidak valid.";
                    }
                } else {
                    $message = "Password yang Anda masukkan salah.";
                }
            } else {
                $message = "Akun dengan email tersebut tidak ditemukan.";
            }
            $stmt->close();
        } else {
            $message = "Terjadi kesalahan pada server. Silakan coba lagi.";
        }
    }
}
$conn->close();
?>
// ...existing HTML code...