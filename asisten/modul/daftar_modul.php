<?php
session_start();
require_once __DIR__ . '/../config.php';

// Ambil mk_id dari URL
$mk_id = isset($_GET['mk_id']) ? intval($_GET['mk_id']) : 0;

if ($mk_id <= 0) {
    $_SESSION['error'] = "ID Mata Kuliah tidak valid.";
    header("Location: mataKuliah.php");
    exit();
}

// Ambil nama mata kuliah
$query_mk = "SELECT nama_mata_kuliah FROM mata_kuliah WHERE id = ?";
$stmt_mk = $conn->prepare($query_mk);
$stmt_mk->bind_param("i", $mk_id);
$stmt_mk->execute();
$result_mk = $stmt_mk->get_result();

if ($result_mk->num_rows === 0) {
    $_SESSION['error'] = "Mata kuliah tidak ditemukan.";
    header("Location: mataKuliah.php");
    exit();
}

$row_mk = $result_mk->fetch_assoc();

// Ambil daftar modul
$query_modul = "SELECT * FROM modul WHERE mata_kuliah_id = ?";
$stmt_modul = $conn->prepare($query_modul);
$stmt_modul->bind_param("i", $mk_id);
$stmt_modul->execute();
$result_modul = $stmt_modul->get_result();
?>

<!-- Include header template -->
<?php require_once __DIR__ . '/templates/header.php'; ?>

<!-- Main Content -->
<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-semibold text-gray-800 mb-4">Daftar Modul</h1>
    <p class="text-lg text-gray-700 mb-6">Mata Kuliah: <strong><?= htmlspecialchars($row_mk['nama_mata_kuliah']) ?></strong></p>

    <!-- Tampilkan pesan error/success -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
            <p><?= $_SESSION['success']; unset($_SESSION['success']); ?></p>
        </div>
    <?php elseif (isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
            <p><?= $_SESSION['error']; unset($_SESSION['error']); ?></p>
        </div>
    <?php endif; ?>

    <!-- Tombol Upload -->
    <div class="mb-4">
        <a href="upload_modul.php?mk_id=<?= $mk_id ?>" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
            + Upload Modul
        </a>
    </div>

    <!-- Tabel Daftar Modul -->
    <div class="bg-white shadow-md rounded my-6 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Nama Modul
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Pertemuan
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Deskripsi
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Materi
                    </th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Aksi
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if ($result_modul->num_rows > 0): ?>
                    <?php while ($modul = $result_modul->fetch_assoc()): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($modul['nama_modul']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($modul['pertemuan']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= substr(htmlspecialchars($modul['deskripsi']), 0, 50) ?>...</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="../uploads/modul/<?= htmlspecialchars($modul['materi_file']) ?>" download class="text-indigo-600 hover:text-indigo-900">
                                    <?= htmlspecialchars($modul['materi_file']) ?>
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center space-x-3 flex justify-center">
                                <!-- Preview -->
                                <a href="#" onclick="showPdfPreview('<?= htmlspecialchars($modul['materi_file']) ?>')" title="Pratinjau">
                                    <i class="fas fa-eye text-green-600 hover:text-green-800"></i>
                                </a>

                                <!-- Edit -->
                                <a href="edit_modul.php?id=<?= $modul['id'] ?>" title="Edit">
                                    <i class="fas fa-edit text-yellow-600 hover:text-yellow-900"></i>
                                </a>

                                <!-- Hapus -->
                                <a href="hapus_modul.php?id=<?= $modul['id'] ?>" title="Hapus"
                                   onclick="return confirm('Yakin ingin menghapus modul ini?')">
                                    <i class="fas fa-trash text-red-600 hover:text-red-900"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center py-4">Tidak ada modul tersedia.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal untuk Preview PDF -->
<div id="pdfPreviewModal" class="fixed z-10 inset-0 overflow-y-auto hidden">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"></div>

        <!-- This element is to trick the browser into centering the modal contents. -->
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen"></span>​

        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                Pratinjau Modul
                            </h3>
                            <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closePdfPreview()">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div id="pdfViewer" class="w-full h-96"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include PDF.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.12.3/build/pdf.min.js "></script>

<!-- Script untuk preview PDF -->
<script>
function showPdfPreview(filename) {
    const pdfUrl = '../uploads/modul/' + filename;
    document.getElementById('pdfPreviewModal').classList.remove('hidden');
    loadPdf(pdfUrl);
}

function closePdfPreview() {
    document.getElementById('pdfPreviewModal').classList.add('hidden');
}

function loadPdf(url) {
    const pdfContainer = document.getElementById('pdfViewer');
    pdfContainer.innerHTML = ''; // Bersihkan konten sebelumnya

    // Load PDF.js viewer
    PDFJS.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.12.3/build/pdf.worker.min.js ';
    PDFJS.getDocument(url).then(function (pdfDoc) {
        const numPages = pdfDoc.numPages;
        let pageNumber = 1;

        function renderPage(pageNumber) {
            pdfDoc.getPage(pageNumber).then(function (page) {
                const viewport = page.getViewport({ scale: 1.5 });
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                canvas.height = viewport.height;
                canvas.width = viewport.width;

                pdfContainer.appendChild(canvas);

                const renderContext = {
                    canvasContext: ctx,
                    viewport: viewport
                };
                page.render(renderContext).promise.then(function () {
                    if (pageNumber < numPages) {
                        pageNumber++;
                        renderPage(pageNumber);
                    }
                });
            });
        }

        renderPage(pageNumber);
    });
}
</script>

<!-- Include footer template -->
<?php require_once __DIR__ . '/templates/footer.php'; ?>