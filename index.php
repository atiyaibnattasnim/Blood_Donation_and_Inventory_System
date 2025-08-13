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
        :root {
            --maroon: #800000;
            --maroon-light: #a52a2a;
            --maroon-dark: #4b0000;
            --maroon-gradient: linear-gradient(135deg, #800000 0%, #a52a2a 100%);
            --bg-light: #f9f7f7;
        }

        body {
            background-color: var(--bg-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        nav.navbar {
            background: var(--maroon-gradient);
        }

        nav .navbar-brand {
            color: #fff !important;
            font-weight: 700;
            font-size: 1.5rem;
        }

        .hero-section {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 3rem 1rem;
            text-align: center;
            color: var(--maroon-dark);
        }

        .hero-section h1 {
            font-weight: 800;
            font-size: 3rem;
            color: var(--maroon);
            margin-bottom: 1rem;
        }

        .hero-section p {
            font-size: 1.25rem;
            max-width: 700px;
            margin-bottom: 2rem;
            color: #4a1a1a;
        }

        .btn-maroon {
            background-color: var(--maroon);
            color: white;
            border: none;
            font-weight: 600;
            padding: 0.75rem 2rem;
            margin: 0 0.5rem;
            transition: background-color 0.3s ease;
            border-radius: 0.3rem;
        }

        .btn-maroon:hover, .btn-maroon:focus {
            background-color: var(--maroon-dark);
            color: white;
            text-decoration: none;
        }

        footer {
            background-color: #f0e6e6;
            text-align: center;
            padding: 1rem 0;
            color: #666;
            font-size: 0.9rem;
            border-top: 1px solid var(--maroon-light);
        }

        @media (max-width: 576px) {
            .hero-section h1 {
                font-size: 2rem;
            }
            .hero-section p {
                font-size: 1rem;
            }
            .btn-maroon {
                padding: 0.5rem 1.5rem;
                margin: 0.5rem 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color:#800000;">
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
            <a href="login.php" class="btn btn-maroon">Login</a>
            <a href="register.php" class="btn btn-maroon">Register</a>
        </div>
    </main>

    <footer>
        &copy; <?= date('Y') ?> Blood Donation System. All rights reserved.
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
