<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Welcome to Blood Donation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f8f8f8;
            margin: 0;
        }
        .navbar {
            background-color: #800000;
        }
        .navbar-brand {
            color: #fff;
        }
        .hero-section {
            text-align: center;
            padding: 50px 20px;
        }
        .hero-section h1 {
            color: #800000;
            font-size: 24px;
            margin-bottom: 10px;
        }
        .hero-section p {
            font-size: 16px;
            margin-bottom: 20px;
        }
        .hero-section .btn {
            background-color: #800000;
            color: #fff;
            margin: 5px;
        }
        footer {
            background-color: #f0f0f0;
            text-align: center;
            padding: 10px;
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="#">Blood Donation System</a>
        </div>
    </nav>

    <main class="hero-section">
        <h1>Welcome to the Blood Donation System</h1>
        <p>
            Our mission is to connect donors and recipients efficiently, saving lives through timely blood donations. 
            Whether you want to donate blood, request blood, or manage hospital inventory, our platform is here to help.
        </p>
        <div>
            <a href="login.php" class="btn">Login</a>
            <a href="register.php" class="btn">Register</a>
        </div>
    </main>

    <footer>
        &copy; <?= date('Y') ?> Blood Donation System. All rights reserved.
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>