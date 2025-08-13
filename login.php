<?php
// login.php
session_start();
include 'db.php'; // contains $conn connection

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $errors[] = "Please fill all fields.";
    } else {
        // Fetch user
        $stmt = $conn->prepare("
            SELECT lc.user_id, lc.password, u.first_name, u.last_name
            FROM login_credentials lc
            JOIN user u ON lc.user_id = u.user_id
            WHERE lc.username = ?
        ");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password'])) {
                // Determine role
                $role = null;

                // Check admin
                $check = $conn->prepare("SELECT 1 FROM admin WHERE user_id = ?");
                $check->bind_param("i", $row['user_id']);
                $check->execute();
                if ($check->get_result()->num_rows > 0) {
                    $role = "admin";
                }
                $check->close();


                // Check Donor
                $check = $conn->prepare("SELECT 1 FROM donor WHERE user_id = ?");
                $check->bind_param("i", $row['user_id']);
                $check->execute();
                if ($check->get_result()->num_rows > 0) $role = "donor";
                $check->close();

                // Check Recipient
                if (!$role) {
                    $check = $conn->prepare("SELECT 1 FROM recipient WHERE user_id = ?");
                    $check->bind_param("i", $row['user_id']);
                    $check->execute();
                    if ($check->get_result()->num_rows > 0) $role = "recipient";
                    $check->close();
                }

                // Check Hospital Rep
                if (!$role) {
                    $check = $conn->prepare("SELECT 1 FROM hospital_representative WHERE user_id = ?");
                    $check->bind_param("i", $row['user_id']);
                    $check->execute();
                    if ($check->get_result()->num_rows > 0) $role = "hospital_rep";
                    
                    $check->close();
                }

                // Store session data
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['username'] = $username;
                $_SESSION['full_name'] = $row['first_name'] . " " . $row['last_name'];
                $_SESSION['role'] = $role;

                // Redirect
                header("Location: dashboard.php");
                exit;
            } else {
                $errors[] = "Invalid password.";
            }
        } else {
            $errors[] = "No account found with that username.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>User Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark" style="background-color:#800000;">
    <div class="container">
        <a class="navbar-brand" href="#">Blood Donation System</a>
    </div>
</nav>

<div class="container mt-5" style="max-width: 450px;">
    <h2 class="mb-4" style="color:#800000;">Login</h2>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="login.php" method="POST" novalidate>
        <div class="mb-3">
            <label for="username" class="form-label">Username (Email)</label>
            <input type="text" id="username" class="form-control" name="username" 
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" id="password" class="form-control" name="password" required>
        </div>
        <button type="submit" 
                class="btn w-100" 
                style="background-color:#800000; border-color:#800000; color:white;">
            Login
        </button>
    </form>

    <p class="mt-3">Don't have an account? <a href="register.php" style="color:#800000;">Register here</a>.</p>
</div>
</body>
</html>
