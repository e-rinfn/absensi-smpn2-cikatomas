<?php
require_once '../../../includes/auth.php';
if (!isAdmin()) {
    header('Location: /sis-absensi-smp/login.php');
    exit;
}

$user_id = $_GET['id'] ?? 0;

// Cek apakah user ada
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error'] = "User tidak ditemukan!";
    header('Location: index.php');
    exit;
}

// Cek apakah user sedang digunakan di tabel lain
$stmt = $pdo->prepare("SELECT COUNT(*) FROM murid WHERE wali_murid_id = ?");
$stmt->execute([$user_id]);
$used_as_wali = $stmt->fetchColumn() > 0;

$stmt = $pdo->prepare("SELECT COUNT(*) FROM kelas WHERE wali_kelas_id = ?");
$stmt->execute([$user_id]);
$used_as_wali_kelas = $stmt->fetchColumn() > 0;

if ($used_as_wali || $used_as_wali_kelas) {
    $_SESSION['error'] = "User tidak dapat dihapus karena masih digunakan di data lain!";
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);

        $_SESSION['success'] = "User berhasil dihapus!";
        header('Location: index.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal menghapus user: " . $e->getMessage();
        header('Location: index.php');
        exit;
    }
}
?>

<?php include '../../../includes/header.php'; ?>
<?php include '../../../includes/navigation/admin.php'; ?>

<div class="container">
    <h1>Hapus User</h1>

    <div class="alert alert-warning">
        <p>Anda yakin ingin menghapus user berikut?</p>
        <ul>
            <li>Username: <?= htmlspecialchars($user['username']) ?></li>
            <li>Nama: <?= htmlspecialchars($user['full_name']) ?></li>
            <li>Role: <?= ucfirst(str_replace('_', ' ', $user['role'])) ?></li>
        </ul>
    </div>

    <form method="POST">
        <button type="submit" class="btn btn-danger">Ya, Hapus</button>
        <a href="index.php" class="btn btn-secondary">Batal</a>
    </form>
</div>

<?php include '../../../includes/footer.php'; ?>