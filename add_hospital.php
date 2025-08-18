<?php
session_start();
include 'db.php';

// Optional: Restrict access to admins only, if you have an admin role
// if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
//     header("Location: login.php");
//     exit;
// }

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $street = trim($_POST['street'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');

    if (empty($name) || empty($city)) {
        $errors[] = "Hospital name and city are required.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO hospital (name, city, street, postal_code) 
                                VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $city, $street, $postal_code);
        if ($stmt->execute()) {
            $success = "Hospital added successfully.";
        } else {
            $errors[] = "Error adding hospital: " . $conn->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Add Hospital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>
<div class="container mt-5" style="max-width: 600px;">
    <h2>Add Hospital</h2>

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

    <form method="POST" action="add_hospital.php" novalidate>
        <div class="mb-3">
            <label for="name" class="form-label">Hospital Name *</label>
            <input type="text" id="name" name="name" class="form-control" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label for="city" class="form-label">City *</label>
            <input type="text" id="city" name="city" class="form-control" required value="<?= htmlspecialchars($_POST['city'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label for="street" class="form-label">Street</label>
            <input type="text" id="street" name="street" class="form-control" value="<?= htmlspecialchars($_POST['street'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label for="postal_code" class="form-label">Postal Code</label>
            <input type="text" id="postal_code" name="postal_code" class="form-control" value="<?= htmlspecialchars($_POST['postal_code'] ?? '') ?>">
        </div>
        <button type="submit" class="btn btn-maroon" style="background-color:#800000; color:#fff;">Add Hospital</button>
    </form>
</div>
</body>
</html>
