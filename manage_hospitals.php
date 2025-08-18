<?php
session_start();
include 'db.php';

// Restrict to admin only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $delete_id = intval($_POST['delete_id']);

    // Check for foreign key constraints
    $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM hospital_representative WHERE hospital_id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    $stmt->close();

    if ($count > 0) {
        $errors[] = "Cannot delete hospital: It is associated with one or more hospital representatives.";
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM storage WHERE hospital_id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        $stmt->close();

        if ($count > 0) {
            $errors[] = "Cannot delete hospital: It is associated with one or more storage records.";
        } else {
            $stmt = $conn->prepare("DELETE FROM hospital WHERE hospital_id = ?");
            $stmt->bind_param("i", $delete_id);
            if ($stmt->execute()) {
                $success = "Hospital deleted successfully.";
            } else {
                $errors[] = "Error deleting hospital: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Fetch all hospitals to display
$result = $conn->query("SELECT * FROM hospital ORDER BY NAME ASC");
$hospitals = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <title>Manage Hospitals - Blood Donation System</title>
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
            max-width: 900px;
            margin: 20px auto;
            padding: 20px;
        }
        h2 {
            color: #800000;
            font-size: 24px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #800000;
        }
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            box-sizing: border-box;
        }
        .btn {
            background-color: #800000;
            color: #fff;
            padding: 8px 15px;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #800000;
            color: #fff;
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
        <div style="max-width: 900px; margin: 0 auto;">
            <a href="dashboard.php">Blood Donation System</a>
            <span>Hello, <?= htmlspecialchars($_SESSION['full_name'] ?? 'Admin') ?></span>
            <a href="logout.php" class="btn">Logout</a>
        </div>
    </div>

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
        <form method="POST" action="manage_hospitals.php">
            <input type="hidden" name="action" value="add"/>
            <div class="form-group">
                <label for="name">Hospital Name *</label>
                <input type="text" id="name" name="name" required
                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="city">City *</label>
                <input type="text" id="city" name="city" required
                       value="<?= htmlspecialchars($_POST['city'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="street">Street</label>
                <input type="text" id="street" name="street"
                       value="<?= htmlspecialchars($_POST['street'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="postal_code">Postal Code</label>
                <input type="text" id="postal_code" name="postal_code"
                       value="<?= htmlspecialchars($_POST['postal_code'] ?? '') ?>">
            </div>
            <button type="submit" class="btn">Add Hospital</button>
        </form>

        <h2>Existing Hospitals</h2>
        <?php if (count($hospitals) === 0): ?>
            <p>No hospitals found.</p>
        <?php else: ?>
            <table>
                <thead>
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
                                <a href="edit_hospital.php?hospital_id=<?= $hospital['hospital_id'] ?>" class="btn">Edit</a>
                                <form method="POST" action="manage_hospitals.php" style="display:inline;"
                                      onsubmit="return confirm('Are you sure you want to delete this hospital?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="delete_id" value="<?= $hospital['hospital_id'] ?>">
                                    <button type="submit" class="btn btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <a href="dashboard.php" class="btn">← Back to Dashboard</a>
    </div>

    <footer>
        &copy; <?= date('Y') ?> Blood Donation System. All rights reserved.
    </footer>
</body>
</html>