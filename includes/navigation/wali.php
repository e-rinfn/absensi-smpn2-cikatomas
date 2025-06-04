<ul class="nav flex-column small gap-1 mt-3">
    <li class="nav-item">
        <a class="mx-2 nav-link d-flex align-items-center px-3 py-2 rounded <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-primary text-white fw-semibold' : 'text-body hover-bg-light' ?>" href="../wali/dashboard.php">
            <i class="bi bi-speedometer2 me-2"></i> Dashboard
        </a>
    </li>
    <li class="nav-item">
        <a class="mx-2 nav-link d-flex align-items-center px-3 py-2 rounded <?= basename($_SERVER['PHP_SELF']) == 'absensi.php' ? 'bg-primary text-white fw-semibold' : 'text-body hover-bg-light' ?>" href="../wali/absensi.php">
            <i class="bi bi-clipboard-check me-2"></i> Absensi Anak
        </a>
    </li>
    <li class="nav-item">
        <a class="mx-2 nav-link d-flex align-items-center px-3 py-2 rounded <?= basename($_SERVER['PHP_SELF']) == 'profil.php' ? 'bg-primary text-white fw-semibold' : 'text-body hover-bg-light' ?>" href="../wali/profil.php">
            <i class="bi bi-person me-2"></i> Profil Anak
        </a>
    </li>
    <li class="nav-item">
        <a class="mx-2 nav-link d-flex align-items-center px-3 py-2 rounded text-danger hover-bg-light" href="<?= $base_url ?>logout.php" onclick="return confirm('Yakin ingin logout?')">
            <i class="bi bi-box-arrow-right me-2"></i> Logout
        </a>
    </li>
</ul>

<hr>

<style>
    .hover-bg-light:hover {
        background-color: #f8f9fa;
    }
</style>