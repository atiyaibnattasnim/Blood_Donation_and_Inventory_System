<?php
session_start();
include 'db.php';

// Restrict to admin only (uncomment if you want)
// if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
//     header("Location: login.php");
//     exit;
// }

$errors = [];
$success = '';

// Handle Add Hospital form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = trim($_POST['name'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $street = trim($_POST['street'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');

    if (empty($name) || empty($city)) {
        $errors[] = "Hospital name and city are required.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO hospital (name, city, street, postal_code) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $city, $street, $postal_code);
        if ($stmt->execute()) {
            $success = "Hospital added successfully.";
        } else {
            $errors[] = "Error adding hospital: " . $conn->error;
        }
        $stmt->close();
    }
}

// Handle Delete Hospital
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM hospital WHERE hospital_id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $success = "Hospital deleted successfully.";
    } else {
        $errors[] = "Error deleting hospital: " . $conn->error;
    }
    $stmt->close();
}

// Fetch all hospitals to display
$result = $conn->query("SELECT * FROM hospital ORDER BY name ASC");
$hospitals = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Manage Hospitals</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        :root {
            --maroon: #800000;
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
            background-color: #4b0000;
            color: white;
        }
        .container {
            max-width: 900px;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg mb-4">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">Blood Donation System</a>
        <div class="navbar-text text-white">
            Manage Hospitals - Admin
        </div>
    </div>
</nav>

<div class="container">

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

    <h2>Add New Hospital</h2>
    <form method="POST" action="manage_hospitals.php" novalidate class="mb-5">
        <input type="hidden" name="action" value="add" />
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
        <button type="submit" class="btn btn-maroon">Add Hospital</button>
    </form>

    <h2>Existing Hospitals</h2>
    <?php if (count($hospitals) === 0): ?>
        <p>No hospitals found.</p>
    <?php else: ?>
        <table class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>Name</th>
                    <th>City</th>
                    <th>Street</th>
                    <th>Postal Code</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($hospitals as $hospital): ?>
                    <tr>
                        <td><?= htmlspecialchars($hospital['name']) ?></td>
                        <td><?= htmlspecialchars($hospital['city']) ?></td>
                        <td><?= htmlspecialchars($hospital['street']) ?></td>
                        <td><?= htmlspecialchars($hospital['postal_code']) ?></td>
                        <td>
    <div style="display: flex; gap: 8px;">
        <a href="edit_hospital.php?hospital_id=<?= $hospital['hospital_id']; ?>" 
   style="background: #4CAF50; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px;">
   Edit
</a>
<a href="delete_hospital.php?hospital_id=<?= $hospital['hospital_id']; ?>" 
   style="background: #f44336; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px;"
   onclick="return confirm('Are you sure you want to delete this hospital?');">
   Delete
</a>

    </div>
</td>

                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
