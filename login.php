<?php
session_start();
require_once 'config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Validasi input
    if (empty($username) || empty($password)) {
        $error = "Username dan password harus diisi!";
    } else {
        // Cek user di database
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];

            // Redirect berdasarkan role
            switch ($user['role']) {
                case 'admin':
                    header('Location: modules/admin/dashboard.php');
                    break;
                case 'guru':
                    header('Location: modules/guru/dashboard.php');
                    break;
                case 'wali_murid':
                    header('Location: modules/wali/dashboard.php');
                    break;
                default:
                    header('Location: login.php');
            }
            exit();
        } else {
            $error = "Username atau password salah!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Absensi SMP</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="assets/images/favicon.ico">

    <!-- Memanggil Bootstrap 5 dari CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./assets/css/login.css">

</head>

<style>
    body {
        margin: 0;
        font-family: Arial, sans-serif;
    }

    .container-fluid {
        height: 100vh;
        display: flex;
        padding: 0;
        flex-direction: row;
    }

    .left-panel {
        background-color: #007bff;
        width: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        text-align: center;
        padding: 0px;
    }

    .left-panel h1 {
        font-size: 3em;
        font-weight: 500;
        /* color: #007bff; */
    }

    .left-panel p {
        font-size: 1.2em;
        color: #333;
    }

    .right-panel {
        width: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 20px;
    }

    .login-box {
        background: #fff;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 400px;
    }

    .login-box .logo {
        width: 100px;
        display: block;
        margin: 0 auto 20px;
    }

    .login-box h3 {
        text-align: center;
        margin-bottom: 20px;
    }

    .login-box input[type="text"],
    .login-box input[type="password"] {
        width: 100%;
        padding: 10px;
        margin: 10px 0;
        border: 1px solid #ccc;
        border-radius: 5px;
    }

    .login-box button {
        width: 100%;
        padding: 10px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
    }

    .login-box button:hover {
        background-color: #0056b3;
    }

    .error {
        color: red;
        font-size: 0.9em;
        margin-bottom: 10px;
    }

    .copyright {
        text-align: center;
        font-size: 0.9em;
        margin-top: 20px;
    }

    /* Media Queries untuk Responsif */
    @media (max-width: 768px) {
        .container-fluid {
            flex-direction: column;
        }

        .left-panel,
        .right-panel {
            width: 100%;
            padding: 15px;
        }

        .left-panel h1 {
            font-size: 2.5em;
        }

        .left-panel p {
            font-size: 1em;
        }

        .login-box {
            padding: 20px;
            max-width: 100%;
        }
    }

    @media (max-width: 480px) {
        .left-panel h1 {
            font-size: 2em;
        }

        .login-box h3 {
            font-size: 1.2em;
        }

        .login-box button {
            font-size: 14px;
            padding: 8px;
        }

        .left-panel p {
            font-size: 0.9em;
        }
    }
</style>


<body>
    <div class="container-fluid">
        <div class="left-panel">
            <div>

                <h2 class="text-white">SMP NEGERI 2 CIKATOMAS</h2>
                <p class="text-white">Sistem Informasi Absensi Terintegrasi</p>
            </div>
        </div>
        <div class="right-panel">
            <div class="login-box">
                <img src="./assets/images/Logo.png" alt="Logo" class="logo">
                <h3>Login Sistem Absensi</h3>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="login.php">

                    <label for="username">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required autofocus>



                    <label for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>


                    <button type="submit" class="btn btn-primary btn-block">Login</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Memanggil Bootstrap 5 Bundle JS dari CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>