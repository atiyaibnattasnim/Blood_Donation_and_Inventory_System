<?php
session_start();
include 'db.php';

$errors = [];
$success = '';
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $errors[] = "Invalid or missing reset token.";
} else {
    // Verify token
    $stmt = $conn->prepare("SELECT email, created_at FROM password_resets WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $email = $row['email'];
        $created_at = strtotime($row['created_at']);
        
        // Check if token is within 1 hour
        if ((time() - $created_at) > 3600) {
            $errors[] = "This reset token has expired.";
        }
    } else {
        $errors[] = "Invalid reset token.";
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($password) || empty($confirm_password)) {
        $errors[] = "Both password fields are required.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    } else {
        // Update password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE login_credentials SET password = ? WHERE username = ?");
        $stmt->bind_param("ss", $hashed_password, $email);
        if ($stmt->execute()) {
            // Delete the reset token
            $stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->close();
            
            $success = "Password reset successfully. You will be redirected to the login page.";
            header("Refresh:3;url=login.php");
        } else {
            $errors[] = "Error resetting password: " . $conn->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <title>Reset Password - Blood Donation System</title>
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

        .navbar-brand {
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

        .container {
            max-width: 450px;
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

        a {
            color: var(--maroon);
            text-decoration: none;
        }

        a:hover {
            color: var(--maroon-dark);
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="login.php">Blood Donation System</a>
    </div>
</nav>

<div class="container mt-5">
    <div class="form-section">
        <h2>Reset Password</h2>
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
        <?php if (empty($errors)): ?>
            <form method="POST" novalidate>
                <div class="mb-3">
                    <label for="password" class="form-label">New Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-maroon w-100">Reset Password</button>
            </form>
        <?php endif; ?>
        <div class="mt-3 text-center">
            <p>Return to <a href="login.php">Login</a>.</p>
        </div>
    </div>
</div>
</body>
</html>