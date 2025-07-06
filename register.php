<?php
require_once 'config.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']);

    // Validasi sederhana
    if (empty($nama) || empty($email) || empty($password) || empty($role)) {
        $message = "Semua kolom harus diisi!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Format email tidak valid!";
    } elseif (!in_array($role, ['mahasiswa', 'asisten'])) {
        $message = "Peran tidak valid!";
    } else {
        // Cek apakah email sudah terdaftar
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = "Email sudah terdaftar. Silakan gunakan email lain.";
        } else {
            // Hash password untuk keamanan
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // Simpan ke database
            $sql_insert = "INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("ssss", $nama, $email, $hashed_password, $role);

            if ($stmt_insert->execute()) {
                header("Location: login.php?status=registered");
                exit();
            } else {
                $message = "Terjadi kesalahan. Silakan coba lagi.";
            }
            $stmt_insert->close();
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Registrasi Pengguna</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(to right, #6a11cb, #2575fc); /* Latar belakang gradien */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh; /* Pastikan mengisi seluruh tinggi viewport */
            margin: 0;
            color: #333;
        }

        .container {
            background-color: #ffffff;
            padding: 30px 40px;
            border-radius: 12px; /* Sudut lebih membulat */
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15); /* Bayangan lebih jelas */
            width: 380px;
            max-width: 90%; /* Responsif untuk layar kecil */
            animation: fadeIn 0.8s ease-out; /* Animasi masuk */
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 25px;
            font-size: 28px;
            font-weight: 600; /* Font lebih tebal */
        }

        .form-group {
            margin-bottom: 20px; /* Jarak antar grup form */
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-size: 15px;
            font-weight: 500;
        }

        .form-group input,
        .form-group select {
            width: calc(100% - 20px); /* Menyesuaikan padding */
            padding: 12px 10px;
            border: 1px solid #cdd;
            border-radius: 6px;
            box-sizing: border-box; /* Memastikan padding termasuk dalam lebar */
            font-size: 15px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease; /* Transisi halus saat fokus */
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #2575fc; /* Warna border saat fokus */
            box-shadow: 0 0 0 3px rgba(37, 117, 252, 0.2); /* Bayangan saat fokus */
            outline: none; /* Hilangkan outline default */
        }

        .btn {
            background-color: #28a745; /* Warna hijau */
            color: white;
            padding: 14px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
            font-size: 17px;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.2s ease; /* Transisi dan efek transform */
            margin-top: 10px;
        }

        .btn:hover {
            background-color: #218838; /* Warna hijau lebih gelap saat hover */
            transform: translateY(-2px); /* Efek sedikit terangkat saat hover */
        }

        .btn:active {
            transform: translateY(0); /* Kembali ke posisi semula saat di-klik */
        }

        .message {
            color: #d9534f; /* Warna teks merah */
            background-color: #f8d7da; /* Latar belakang merah muda */
            border: 1px solid #f5c6cb; /* Border merah muda */
            padding: 10px;
            border-radius: 6px;
            text-align: center;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .login-link {
            text-align: center;
            margin-top: 25px;
            font-size: 15px;
            color: #666;
        }

        .login-link a {
            color: #007bff; /* Warna tautan biru */
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .login-link a:hover {
            color: #0056b3; /* Warna biru lebih gelap saat hover */
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Registrasi</h2>
        <?php if (!empty($message)): ?>
            <p class="message"><?php echo $message; ?></p>
        <?php endif; ?>
        <form action="register.php" method="post">
            <div class="form-group">
                <label for="nama">Nama Lengkap</label>
                <input type="text" id="nama" name="nama" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Kata Sandi</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="role">Daftar Sebagai</label>
                <select id="role" name="role" required>
                    <option value="mahasiswa">Mahasiswa</option>
                    <option value="asisten">Asisten</option>
                </select>
            </div>
            <button type="submit" class="btn">Daftar</button>
        </form>
        <div class="login-link">
            <p>Sudah punya akun? <a href="login.php">Masuk di sini</a></p>
        </div>
    </div>
</body>
</html>