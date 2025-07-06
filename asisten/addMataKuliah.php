<?php
// Include configuration file
require_once 'config.php';

// Include database connection
// require_once 'db.php';

// Include header template
require_once 'templates/header.php';
?>

<!-- Main Content -->
<div class="min-h-screen bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <!-- Breadcrumb -->
        <nav class="flex items-center mb-4" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-2">
                <li class="inline-flex items-center">
                    <a href="dashboard.php" class="text-gray-700 hover:text-gray-900">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293-.293a1 1 0 000-1.414l-7-7z" clip-rule="evenodd" fill-rule="evenodd"></path></svg>
                        Dashboard
                    </a>
                </li>
                <li class="inline-flex items-center">
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mx-1 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                        <a href="mata_kuliah.php" class="ml-1 text-gray-700 hover:text-gray-900 md:ml-2">Mata Kuliah</a>
                    </div>
                </li>
                <li class="inline-flex items-center">
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mx-1 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                        <span class="ml-1 text-gray-500 md:ml-2">Tambah Mata Kuliah</span>
                    </div>
                </li>
            </ol>
        </nav>

        <!-- Page Header -->
        <h1 class="text-2xl font-semibold text-gray-800 mb-4">Tambah Mata Kuliah</h1>

        <!-- Form Tambah Mata Kuliah -->
        <form action="prosesMataKuliah.php" method="POST" class="bg-white shadow-md rounded p-6">
            <div class="mb-4">
                <label for="nama_mata_kuliah" class="block text-gray-700 font-medium mb-2">Nama Mata Kuliah</label>
                <input type="text" name="nama_mata_kuliah" id="nama_mata_kuliah" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-indigo-500">
            </div>

            <div class="mb-4">
                <label for="deskripsi" class="block text-gray-700 font-medium mb-2">Deskripsi</label>
                <textarea name="deskripsi" id="deskripsi" rows="4" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-indigo-500"></textarea>
            </div>

            <div class="mb-4">
                <label for="asisten_id" class="block text-gray-700 font-medium mb-2">Asisten</label>
                <select name="asisten_id" id="asisten_id" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-indigo-500">
                    <option value="">Pilih Asisten</option>
                    <?php
                    // Query to fetch all assistants (users with role 'asisten')
                    $query = "SELECT id, nama FROM users WHERE role = 'asisten'";
                    $result = mysqli_query($conn, $query);

                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo '<option value="' . $row['id'] . '">' . $row['nama'] . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>

            <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded w-full">
                Simpan Mata Kuliah
            </button>
        </form>
    </div>
</div>

<!-- Include footer template -->
<?php require_once 'templates/footer.php'; ?>