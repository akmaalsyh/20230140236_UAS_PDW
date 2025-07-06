<?php
// 1. Definisi Variabel untuk Template
$pageTitle = 'Dashboard Asisten'; // Ubah judul halaman agar lebih spesifik
$activePage = 'dashboard';

// 2. Panggil Header
require_once 'templates/header.php'; 
?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-10"> <div class="bg-white p-8 rounded-xl shadow-lg flex items-center space-x-6 transform transition-transform duration-300 hover:scale-105 hover:shadow-xl cursor-pointer animate-fade-in-down">
        <div class="bg-blue-100 p-4 rounded-full flex-shrink-0">
            <svg class="w-8 h-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" /></svg>
        </div>
        <div>
            <p class="text-md text-gray-600 font-medium">Total Modul Diajarkan</p>
            <p class="text-4xl font-extrabold text-gray-900 mt-1">12</p>
        </div>
    </div>

    <div class="bg-white p-8 rounded-xl shadow-lg flex items-center space-x-6 transform transition-transform duration-300 hover:scale-105 hover:shadow-xl cursor-pointer animate-fade-in-down delay-100">
        <div class="bg-green-100 p-4 rounded-full flex-shrink-0">
            <svg class="w-8 h-8 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        </div>
        <div>
            <p class="text-md text-gray-600 font-medium">Total Laporan Masuk</p>
            <p class="text-4xl font-extrabold text-gray-900 mt-1">152</p>
        </div>
    </div>

    <div class="bg-white p-8 rounded-xl shadow-lg flex items-center space-x-6 transform transition-transform duration-300 hover:scale-105 hover:shadow-xl cursor-pointer animate-fade-in-down delay-200">
        <div class="bg-yellow-100 p-4 rounded-full flex-shrink-0">
            <svg class="w-8 h-8 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        </div>
        <div>
            <p class="text-md text-gray-600 font-medium">Laporan Belum Dinilai</p>
            <p class="text-4xl font-extrabold text-gray-900 mt-1">18</p>
        </div>
    </div>
</div>

<div class="bg-white p-8 rounded-xl shadow-lg animate-fade-in-up"> <h3 class="text-2xl font-bold text-gray-800 mb-6 border-b-2 border-gray-200 pb-3">Aktivitas Laporan Terbaru</h3> <div class="space-y-6"> <div class="flex items-center p-4 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors duration-200 cursor-pointer">
            <div class="w-12 h-12 rounded-full bg-indigo-500 text-white flex items-center justify-center mr-4 text-lg font-bold flex-shrink-0">
                <span>BS</span>
            </div>
            <div>
                <p class="text-gray-800 text-lg"><strong>Budi Santoso</strong> mengumpulkan laporan untuk <span class="text-indigo-600 font-semibold">Modul 2: PHP Native</span></p>
                <p class="text-sm text-gray-500 mt-1">10 menit lalu</p>
            </div>
        </div>

        <div class="flex items-center p-4 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors duration-200 cursor-pointer">
            <div class="w-12 h-12 rounded-full bg-purple-500 text-white flex items-center justify-center mr-4 text-lg font-bold flex-shrink-0">
                <span>CL</span>
            </div>
            <div>
                <p class="text-gray-800 text-lg"><strong>Citra Lestari</strong> mengumpulkan laporan untuk <span class="text-purple-600 font-semibold">Modul 2: PHP Native</span></p>
                <p class="text-sm text-gray-500 mt-1">45 menit lalu</p>
            </div>
        </div>

        <div class="flex items-center p-4 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors duration-200 cursor-pointer">
            <div class="w-12 h-12 rounded-full bg-green-500 text-white flex items-center justify-center mr-4 text-lg font-bold flex-shrink-0">
                <span>AD</span>
            </div>
            <div>
                <p class="text-gray-800 text-lg"><strong>Andi Darmawan</strong> mengumpulkan laporan untuk <span class="text-green-600 font-semibold">Modul 1: HTML & CSS</span></p>
                <p class="text-sm text-gray-500 mt-1">2 jam lalu</p>
            </div>
        </div>
    </div>
</div>


<?php
// 3. Panggil Footer
require_once 'templates/footer.php';
?>