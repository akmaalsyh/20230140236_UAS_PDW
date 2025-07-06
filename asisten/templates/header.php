<?php
// Pastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek jika pengguna belum login atau bukan asisten
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'asisten') {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <title>Panel Asisten - <?php echo $pageTitle ?? 'Dashboard'; ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* Menggunakan font Inter secara global jika di-import */
        body {
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased; /* Untuk teks lebih halus di WebKit */
            -moz-osx-font-smoothing: grayscale; /* Untuk teks lebih halus di Firefox */
        }

        /* Custom keyframes untuk animasi jika diperlukan */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes slideInLeft {
            from { transform: translateX(-100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .animate-slide-in-left {
            animation: slideInLeft 0.5s ease-out forwards;
        }

        /* Scrollbar styling (opsional, untuk tampilan yang lebih modern) */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #e0e0e0;
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
</head>
<body class="bg-gray-100 flex min-h-screen"> <aside class="w-64 bg-gray-800 text-white flex flex-col shadow-lg transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out z-40" id="sidebar">
        <div class="p-6 text-center border-b border-gray-700 bg-gray-900">
            <h3 class="text-2xl font-extrabold tracking-wider text-indigo-400">SIMPRAK</h3> <p class="text-sm text-gray-300 mt-2 opacity-80"><?php echo htmlspecialchars($_SESSION['nama']); ?></p>
        </div>
        <nav class="flex-grow py-4 overflow-y-auto"> <ul class="space-y-2 px-4">
                <?php 
                    // Menyiapkan class untuk link aktif dan tidak aktif
                    $baseLinkClass = 'flex items-center px-4 py-3 rounded-lg transition-all duration-200 ease-in-out transform hover:scale-105';
                    $activeClass = $baseLinkClass . ' bg-gradient-to-r from-blue-600 to-indigo-700 text-white shadow-md font-semibold';
                    $inactiveClass = $baseLinkClass . ' text-gray-300 hover:bg-gray-700 hover:text-white';
                ?>
                <li>
                    <a href="dashboard.php" class="<?php echo ($activePage == 'dashboard') ? $activeClass : $inactiveClass; ?>">
                        <i class="fas fa-fw fa-tachometer-alt w-5 h-5 mr-3"></i> <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="mataKuliah.php" class="<?php echo ($activePage == 'modul') ? $activeClass : $inactiveClass; ?>">
                        <i class="fas fa-fw fa-book-open w-5 h-5 mr-3"></i> <span>Manajemen Modul</span>
                    </a>
                </li>
                <li>
                    <a href="laporan.php" class="<?php echo ($activePage == 'laporan') ? $activeClass : $inactiveClass; ?>">
                        <i class="fas fa-fw fa-file-alt w-5 h-5 mr-3"></i> <span>Laporan Masuk</span>
                    </a>
                </li>
                <li>
                    <a href="settings.php" class="<?php echo ($activePage == 'settings') ? $activeClass : $inactiveClass; ?>">
                        <i class="fas fa-fw fa-cog w-5 h-5 mr-3"></i>
                        <span>Pengaturan Akun</span>
                    </a>
                </li>
            </ul>
        </nav>
        <div class="p-4 border-t border-gray-700">
            <a href="../logout.php" class="flex items-center justify-center bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition-all duration-300 transform hover:scale-105 shadow-md">
                <i class="fas fa-fw fa-sign-out-alt mr-2"></i> Logout
            </a>
        </div>
    </aside>

    <div class="flex-1 flex flex-col">
        <header class="bg-white shadow-sm py-4 px-6 md:px-8 flex items-center justify-between sticky top-0 z-30">
            <button id="sidebar-toggle" class="md:hidden text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-300 rounded p-2">
                <i class="fas fa-bars text-xl"></i>
            </button>
            <h1 class="text-2xl md:text-3xl font-extrabold text-gray-800 ml-4 md:ml-0 flex-grow"><?php echo $pageTitle ?? 'Dashboard'; ?></h1>
            <div class="hidden md:block"> <a href="../logout.php" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-5 rounded-full shadow-lg transition-all duration-300 transform hover:scale-105">
                    Logout
                 </a>
            </div>
        </header>

        <main class="flex-1 p-6 lg:p-10 bg-gray-100">
            