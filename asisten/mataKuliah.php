<?php
// Include configuration file
require_once __DIR__ . '/../config.php';

// Include header template
require_once __DIR__ . '/templates/header.php';
?>

<!-- Load Iconify Script -->
<script src="https://code.iconify.design/iconify-icon/2.1.0/iconify-icon.min.js "></script>

<!-- Main Content -->
<div class="min-h-screen bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <!-- Breadcrumb -->
        <nav class="flex items-center mb-4" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-2">
                <li class="inline-flex items-center">
                    <a href="dashboard.php" class="text-gray-700 hover:text-gray-900">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293-.293a1 1 0 000-1.414l-7-7z"
                                clip-rule="evenodd" fill-rule="evenodd"></path>
                        </svg>
                        Dashboard
                    </a>
                </li>
                <li class="inline-flex items-center">
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mx-1 text-gray-400" fill="currentColor" viewBox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd"
                                d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                clip-rule="evenodd"></path>
                        </svg>
                        <span class="ml-1 text-gray-500 md:ml-2">Mata Kuliah</span>
                    </div>
                </li>
            </ol>
        </nav>

        <!-- Page Header -->
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-semibold text-gray-800">Daftar Mata Kuliah</h1>
            <a href="addMataKuliah.php" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded">
                <span class="mr-2">+</span> Tambah Mata Kuliah
            </a>
        </div>

        <!-- Table -->
        <div class="bg-white shadow-md rounded my-6 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            ID
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Nama Mata Kuliah
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Asisten
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Deskripsi
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php
                    // Query to fetch all courses
                    $query = "SELECT mk.id, mk.nama_mata_kuliah, mk.deskripsi, u.nama AS nama_asisten
                              FROM mata_kuliah mk
                              JOIN users u ON mk.asisten_id = u.id";
                    $result = mysqli_query($conn, $query);

                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo '<tr>';
                            echo '<td class="px-6 py-4 whitespace-nowrap">' . htmlspecialchars($row['id']) . '</td>';
                            echo '<td class="px-6 py-4 whitespace-nowrap">' . htmlspecialchars($row['nama_mata_kuliah']) . '</td>';
                            echo '<td class="px-6 py-4 whitespace-nowrap">' . htmlspecialchars($row['nama_asisten']) . '</td>';
                            echo '<td class="px-6 py-4 whitespace-nowrap">' . substr(htmlspecialchars($row['deskripsi']), 0, 50) . '...' . '</td>';
                            
                            // Kolom Aksi - letakkan kode ini di sini
                            echo '<td class="px-6 py-4 whitespace-nowrap text-center space-x-3 flex justify-center">';
                            
                            echo '<a href="upload_modul.php?mk_id=' . htmlspecialchars($row['id']) . '" title="Upload Modul">';
                            echo '<iconify-icon icon="material-symbols:upload" style="font-size: 20px; color: #3b82f6;"></iconify-icon>';
                            echo '</a>';

                            echo '<a href="edit_mata_kuliah.php?id=' . htmlspecialchars($row['id']) . '" title="Edit">';
                            echo '<i class="fas fa-edit text-yellow-600 hover:text-yellow-900"></i>';
                            echo '</a>';

                            echo '<a href="hapus_mata_kuliah.php?id=' . htmlspecialchars($row['id']) . '" title="Hapus" onclick="return confirm(\'Yakin ingin menghapus mata kuliah ini?\')">';
                            echo '<i class="fas fa-trash text-red-600 hover:text-red-900"></i>';
                            echo '</a>';

                            echo '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="5" class="text-center py-4">Tidak ada data mata kuliah.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Include footer template -->
<?php require_once __DIR__ . '/templates/footer.php'; ?>