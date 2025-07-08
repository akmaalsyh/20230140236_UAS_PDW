<?php
// Pastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek jika pengguna belum login atau bukan asisten
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'asisten') {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Asisten - <?php echo $pageTitle ?? 'Dashboard'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans">

    <nav class="bg-indigo-700 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <span class="text-white text-2xl font-bold">SIMPRAK Asisten</span>
                    </div>
                    <div class="hidden md:block">
                        <div class="ml-10 flex items-baseline space-x-4">
                            <?php 
                                $activeClass = 'bg-indigo-800 text-white';
                                $inactiveClass = 'text-gray-200 hover:bg-indigo-800 hover:text-white';
                            ?>
                            <a href="dashboard.php" class="<?php echo ($activePage == 'dashboard') ? $activeClass : $inactiveClass; ?> px-3 py-2 rounded-md text-sm font-medium">Dashboard</a>
                            <a href="manage_courses.php" class="<?php echo ($activePage == 'manage_courses') ? $activeClass : $inactiveClass; ?> px-3 py-2 rounded-md text-sm font-medium">Kelola Praktikum</a>
                            <a href="manage_modules.php" class="<?php echo ($activePage == 'manage_modules') ? $activeClass : $inactiveClass; ?> px-3 py-2 rounded-md text-sm font-medium">Kelola Modul</a>
                            <a href="submissions.php" class="<?php echo ($activePage == 'submissions') ? $activeClass : $inactiveClass; ?> px-3 py-2 rounded-md text-sm font-medium">Laporan Masuk</a>
                            <a href="manage_users.php" class="<?php echo ($activePage == 'manage_users') ? $activeClass : $inactiveClass; ?> px-3 py-2 rounded-md text-sm font-medium">Kelola Pengguna</a>
                        </div>
                    </div>
                </div>

                <div class="hidden md:block">
                    <div class="ml-4 flex items-center md:ml-6">
                        <a href="../logout.php" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-md transition-colors duration-300">
                            Logout
                        </a>
                    </div>
                </div>

                <!-- Mobile menu button -->
                <div class="-mr-2 flex md:hidden">
                    <button type="button" class="bg-indigo-700 inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-white hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-indigo-700 focus:ring-white" aria-controls="mobile-menu" aria-expanded="false">
                        <span class="sr-only">Open main menu</span>
                        <!-- Icon when menu is closed. -->
                        <svg class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <!-- Icon when menu is open. -->
                        <svg class="hidden h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile menu, show/hide based on menu state. -->
        <div class="md:hidden" id="mobile-menu">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                <a href="dashboard.php" class="<?php echo ($activePage == 'dashboard') ? $activeClass : $inactiveClass; ?> block px-3 py-2 rounded-md text-base font-medium">Dashboard</a>
                <a href="manage_courses.php" class="<?php echo ($activePage == 'manage_courses') ? $activeClass : $inactiveClass; ?> block px-3 py-2 rounded-md text-base font-medium">Kelola Praktikum</a>
                <a href="manage_modules.php" class="<?php echo ($activePage == 'manage_modules') ? $activeClass : $inactiveClass; ?> block px-3 py-2 rounded-md text-base font-medium">Kelola Modul</a>
                <a href="submissions.php" class="<?php echo ($activePage == 'submissions') ? $activeClass : $inactiveClass; ?> block px-3 py-2 rounded-md text-base font-medium">Laporan Masuk</a>
                <a href="manage_users.php" class="<?php echo ($activePage == 'manage_users') ? $activeClass : $inactiveClass; ?> block px-3 py-2 rounded-md text-base font-medium">Kelola Pengguna</a>
                <a href="../logout.php" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-md transition-colors duration-300 block text-center mt-4">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-6 lg:p-8">
