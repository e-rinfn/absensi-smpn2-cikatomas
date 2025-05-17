<?php
// Mulai session jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah user sudah login
$is_logged_in = isset($_SESSION['user_id']);
$current_role = $_SESSION['role'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Absensi SMP - <?= $page_title ?? 'Dashboard' ?></title>

    <!-- Bootstrap CSS -->
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">

    <!-- Favicon -->
    <link rel="shortcut icon" href="../assets/images/favicon.ico" type="image/x-icon">
</head>

<body>
    <header class="bg-primary text-white py-3">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h4 mb-0">Sistem Absensi SMP</h1>

                <?php if ($is_logged_in): ?>
                    <div class="dropdown">
                        <button class="btn btn-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown">
                            <?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="../modules/<?= $current_role ?>/profil.php">Profil</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="container-fluid">
        <div class="row">
            <?php if ($is_logged_in): ?>
                <!-- Sidebar Navigation -->
                <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                    <div class="position-sticky pt-3">
                        <?php
                        // Include navigasi sesuai role
                        $nav_file = __DIR__ . '/navigation/' . $current_role . '.php';
                        if (file_exists($nav_file)) {
                            include $nav_file;
                        }
                        ?>
                    </div>
                </nav>
            <?php endif; ?>

            <!-- Main Content -->
            <main class="<?= $is_logged_in ? 'col-md-9 ms-sm-auto col-lg-10 px-md-4' : 'col-12' ?>">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h2 class="h4"><?= $page_title ?? 'Dashboard' ?></h2>
                    <?php if (isset($breadcrumbs)): ?>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <?php foreach ($breadcrumbs as $text => $link): ?>
                                    <?php if ($link): ?>
                                        <li class="breadcrumb-item"><a href="<?= $link ?>"><?= $text ?></a></li>
                                    <?php else: ?>
                                        <li class="breadcrumb-item active"><?= $text ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ol>
                        </nav>
                    <?php endif; ?>
                </div>

                <!-- Notifikasi -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= $_SESSION['success'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= $_SESSION['error'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>