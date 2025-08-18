<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? null;
$full_name = $_SESSION['full_name'] ?? 'User';

// CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $csrf_token) {
        $errors[] = "Invalid CSRF token.";
    } else {
        // Check for dependencies
        $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM donation WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $count = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();
        if ($count > 0) {
            $errors[] = "Cannot delete profile: You have associated donation records.";
        }

        $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM request WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $count = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();
        if ($count > 0) {
            $errors[] = "Cannot delete profile: You have associated blood request records.";
        }

        if (empty($errors)) {
            // Begin transaction
            $conn->begin_transaction();
            try {
                // Delete from role-specific table
                if ($role === 'donor') {
                    $stmt = $conn->prepare("DELETE FROM donor WHERE user_id = ?");
                } elseif ($role === 'recipient') {
                    $stmt = $conn->prepare("DELETE FROM recipient WHERE user_id = ?");
                } elseif ($role === 'hospital_rep') {
                    $stmt = $conn->prepare("DELETE FROM hospital_representative WHERE user_id = ?");
                } elseif ($role === 'admin') {
                    $stmt = $conn->prepare("DELETE FROM admin WHERE user_id = ?");
                }
                if ($role) {
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $stmt->close();
                }

                // Delete from user_phone_no
                $stmt = $conn->prepare("DELETE FROM user_phone_no WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->close();

                // Delete from login_credentials
                $stmt = $conn->prepare("DELETE FROM login_credentials WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->close();

                // Delete from user
                $stmt = $conn->prepare("DELETE FROM user WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->close();

                $conn->commit();

                // Clear session and redirect
                session_unset();
                session_destroy();
                $success = "Profile deleted successfully. You will be redirected to the login page.";
                header("Refresh:3;url=login.php");
            } catch (Exception $e) {
                $conn->rollback();
                $errors[] = "Error deleting profile: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <title>Delete Profile - Blood Donation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        }

        .navbar {
            background: var(--maroon-gradient);
        }

        .navbar-brand, .navbar-text {
            font-weight: 700;
            color: #fff !important;
        }

        .btn-maroon {
            background-color: var(--maroon);
            color: white;
            border: none;
            transition: background-color 0.3s ease;
            font-weight: 600;
        }

        .btn-maroon:hover, .btn-maroon:focus {
            background-color: var(--maroon-dark);
            color: white;
        }

        .btn-danger-maroon {
            background-color: #f44336;
            color: white;
            border: none;
            transition: background-color 0.3s ease;
        }

        .btn-danger-maroon:hover, .btn-danger-maroon:focus {
            background-color: #d32f2f;
            color: white;
        }

        .btn-outline-maroon {
            color: var(--maroon);
            border-color: var(--maroon);
        }

        .btn-outline-maroon:hover {
            background-color: var(--maroon);
            color: white;
        }

        .container {
            max-width: 600px;
        }

        .form-section {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 4px 8px rgba(128, 0, 0, 0.1);
            padding: 2rem;
        }

        .form-section h2 {
            color: var(--maroon);
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">Blood Donation System</a>
        <div class="d-flex align-items-center gap-3">
            <span class="navbar-text">Hello, <?= htmlspecialchars($full_name) ?></span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <div class="form-section">
        <h2>Delete Profile</h2>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($errors): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <p class="text-danger">Warning: Deleting your profile will permanently remove all your data, including your account and associated records. This action cannot be undone.</p>
        <form method="POST" onsubmit="return confirm('Are you sure you want to delete your profile? This action cannot be undone.');">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <button type="submit" class="btn btn-danger-maroon w-100">Delete My Profile</button>
        </form>
        <a href="dashboard.php" class="btn btn-outline-maroon mt-3 w-100">Back to Dashboard</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>