<?php
session_start();

// Hapus semua variabel session
$_SESSION = array();

// Hancurkan session
session_destroy();

// Redirect ke halaman login menggunakan absolute path
// Anda perlu menyesuaikan BASE_URL ini sesuai dengan konfigurasi server Anda.
// Contoh: Jika proyek Anda diakses melalui http://localhost/pengumpulantugas/, maka BASE_URL adalah '/pengumpulantugas/'
// Jika proyek Anda diakses langsung di root domain seperti http://localhost/, maka BASE_URL adalah '/'
// Berdasarkan screenshot Anda sebelumnya, sepertinya BASE_URL adalah '/20230140236_UAS_PDW/'
define('BASE_URL', '/20230140236_UAS_PDW/'); 

header("Location: " . BASE_URL . "login.php");
exit;
?>
