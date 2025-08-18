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
    <style>
        body {
            background-color: #f8f8f8;
            margin: 0;
            font-family: Arial, sans-serif;
        }
        .navbar {
            background-color: #800000;
            padding: 10px;
        }
        .navbar a, .navbar span {
            color: #fff;
            text-decoration: none;
            margin-right: 10px;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
        }
        h2 {
            color: #800000;
            font-size: 24px;
            margin-bottom: 20px;
        }
        .form-section {
            padding: 20px;
            border: 1px solid #ddd;
            background-color: #fff;
        }
        .btn {
            background-color: #800000;
            color: #fff;
            padding: 8px 15px;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
            width: 100%;
            text-align: center;
        }
        .btn-danger {
            background-color: #dc3545;
            color: #fff;
        }
        .alert {
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            font-size: 14px;
        }
        .alert-success {
            background-color: #dff0d8;
            border-color: #d4edda;
            color: #155724;
        }
        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        .alert ul {
            margin: 0;
            padding-left: 20px;
        }
        .text-danger {
            color: #dc3545;
            font-size: 14px;
            margin-bottom: 20px;
        }
        a {
            color: #800000;
            text-decoration: none;
        }
        footer {
            background-color: #f0f0f0;
            text-align: center;
            padding: 10px;
            font-size: 14px;
            color: #666;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div style="max-width: 600px; margin: 0 auto;">
            <a href="dashboard.php">Blood Donation System</a>
            <span>Hello, <?= htmlspecialchars($full_name) ?></span>
            <a href="logout.php" class="btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="form-section">
            <h2>Delete Profile</h2>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <p class="text-danger">Warning: Deleting your profile will permanently remove all your data, including your account and associated records. This action cannot be undone.</p>
            <form method="POST" onsubmit="return confirm('Are you sure you want to delete your profile? This action cannot be undone.');">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <button type="submit" class="btn btn-danger">Delete My Profile</button>
            </form>
            <a href="dashboard.php" class="btn">Back to Dashboard</a>
        </div>
    </div>

    <footer>
        &copy; <?= date('Y') ?> Blood Donation System. All rights reserved.
    </footer>
</body>
</html>