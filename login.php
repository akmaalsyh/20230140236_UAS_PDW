<?php
session_start();
require_once 'config.php'; // Pastikan file config.php sudah ada dan berisi koneksi database yang benar

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

$message = ''; // Inisialisasi variabel pesan

// Cek apakah ada pesan dari halaman lain (misal dari register.php)
if (isset($_GET['status']) && $_GET['status'] == 'registered') {
    $message = "Registrasi berhasil! Silakan login.";
    $message_class = 'success'; // Menambahkan kelas untuk styling sukses
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $message = "Email dan password harus diisi!";
        $message_class = 'error';
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
                        $message_class = 'error';
                    }
                } else {
                    $message = "Password yang Anda masukkan salah.";
                    $message_class = 'error';
                }
            } else {
                $message = "Akun dengan email tersebut tidak ditemukan.";
                $message_class = 'error';
            }
            $stmt->close();
        } else {
            $message = "Terjadi kesalahan pada server. Silakan coba lagi.";
            $message_class = 'error';
        }
    }
}
$conn->close(); // Tutup koneksi database setelah semua operasi
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Praktikum</title>
    <style>
        /* General Body Styling */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(to right, #6a11cb 0%, #2575fc 100%); /* Nice gradient background */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            color: #333;
        }

        /* Login Container Styling */
        .login-container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); /* Softer, larger shadow */
            width: 100%;
            max-width: 450px; /* Slightly wider for better spacing */
            text-align: center;
            animation: fadeIn 0.8s ease-out; /* Simple fade-in animation */
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-container h2 {
            margin-bottom: 30px;
            color: #333;
            font-size: 2em; /* Larger title */
            position: relative;
            padding-bottom: 10px;
        }

        .login-container h2::after {
            content: '';
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            bottom: 0;
            width: 60px;
            height: 3px;
            background-color: #2575fc;
            border-radius: 5px;
        }

        /* Form Group Styling */
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: #555;
            font-weight: 600; /* Slightly bolder labels */
            font-size: 0.95em;
        }

        .form-group input[type="email"],
        .form-group input[type="password"] {
            width: calc(100% - 24px); /* Account for padding and border */
            padding: 12px;
            border: 1px solid #e0e0e0;
            border-radius: 8px; /* More rounded corners */
            font-size: 1.1em; /* Larger text inside input */
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .form-group input[type="email"]:focus,
        .form-group input[type="password"]:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 4px rgba(0, 123, 255, 0.15); /* Softer focus shadow */
        }

        /* Button Styling */
        .btn-login {
            width: 100%;
            padding: 14px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.2em;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            margin-top: 10px;
        }

        .btn-login:hover {
            background-color: #0056b3;
            transform: translateY(-2px); /* Slight lift on hover */
        }

        .btn-login:active {
            transform: translateY(0); /* Press effect */
        }

        /* Message Styling */
        .message {
            margin-top: 20px;
            padding: 12px;
            border-radius: 6px;
            font-size: 0.95em;
            font-weight: 500;
            text-align: center;
        }

        .message.error {
            background-color: #ffe0e0;
            color: #d32f2f;
            border: 1px solid #d32f2f;
        }

        .message.success {
            background-color: #e6ffe6;
            color: #388e3c;
            border: 1px solid #388e3c;
        }

        /* Register Link Styling */
        .register-link {
            margin-top: 25px;
            font-size: 0.9em;
            color: #666;
        }

        .register-link a {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s ease;
        }

        .register-link a:hover {
            color: #0056b3;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Login</h2>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo htmlspecialchars($message_class ?? ''); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn-login">Login</button>
        </form>

        <p class="register-link">Belum punya akun? <a href="register.php">Daftar di sini</a></p>
    </div>
</body>
</html>