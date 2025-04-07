<?php
require_once '../../Backend/connection.php';
require_once '../../Backend/log_helper.php'; // Include log_helper.php
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: /Frontend/index.php");
    exit();
}

$email = $_SESSION['email'];

// Log admin's access to the featured card page
$stmt = $conn->prepare("SELECT id FROM user WHERE email = ?"); // Corrected table name from 'admin' to 'user'
$stmt->bind_param("s", $adminEmail);
$stmt->execute();
$stmt->bind_result($admin_id);
if ($stmt->fetch()) {
    $stmt->close(); // Ensure the result set is closed before calling insertLog
    insertLog($admin_id, 'View', 'Admin accessed the featured card page', 'info'); // Log admin action
} else {
    $stmt->close(); // Close the statement even if no result is fetched
}

// Get featured cards
$cards = $conn->query("SELECT * FROM featured_cards ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
$cardCount = count($cards);

// Toast Message Flag
$toastMessage = "";

// Add Card
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_card']) && $cardCount < 3) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $details = $_POST['details'];
    $image = "";

    if (!empty($_FILES['image']['name'])) {
        $image = basename($_FILES['image']['name']);
        $target = "/opt/bitnami/apache2/htdocs/Kaluppa/Frontend/admin_dashboard/uploads/featured/" . $image;
        move_uploaded_file($_FILES['image']['tmp_name'], $target);
    }

    $stmt = $conn->prepare("INSERT INTO featured_cards (title, description, details, image) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $title, $description, $details, $image);
    $stmt->execute();
    $stmt->close();

    $_SESSION['toast'] = "Card added successfully!";
    header("Location: featured_card.php");
    exit();
}

// Update Card
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_card'])) {
    $id = $_POST['card_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $details = $_POST['details'];
    $image = $_POST['old_image'];

    if (!empty($_FILES['image']['name'])) {
        $image = basename($_FILES['image']['name']);
        $target = "/opt/bitnami/apache2/htdocs/Kaluppa/Frontend/admin_dashboard/uploads/featured/" . $image;
        move_uploaded_file($_FILES['image']['tmp_name'], $target);
    }

    $stmt = $conn->prepare("UPDATE featured_cards SET title = ?, description = ?, details = ?, image = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $title, $description, $details, $image, $id);
    $stmt->execute();
    $stmt->close();

    $_SESSION['toast'] = "Card updated successfully!";
    header("Location: featured_card.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Featured Cards</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../CSS/admin_css/cards.css">
    
</head>
<body>

<?php include 'sidebar.php'; ?>
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoutModalLabel" style="color:black;">Confirm Logout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="color:black;">
                Are you sure you want to log out?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="/Kaluppa/Frontend/logout.php" class="btn btn-theme">Logout</a>

            </div>
        </div>
    </div>
</div>

<div class="container mt-4" style="margin-left: 250px;">
    <h3 class="mb-4">Featured Card Manager</h3>

    <?php if ($cardCount < 3): ?>
    <!-- Add Card Button -->
    <div class="mb-4">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCardModal">
            Add New Card (Max: 3)
        </button>
    </div>

    <!-- Add Card Modal -->
    <div class="modal fade" id="addCardModal" tabindex="-1" aria-labelledby="addCardModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content text-white" style="background-color: #1c1c1c;">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCardModalLabel">Add New Featured Card</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control" name="title" placeholder="Enter title..." required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" placeholder="Enter description..." required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Details</label>
                            <textarea class="form-control" name="details" rows="3" placeholder="Enter details..." required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Upload Image</label>
                            <input type="file" class="form-control" name="image" accept="image/*" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="add_card" class="btn btn-success w-100">Add Card</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-warning">You have reached the maximum limit of 3 featured cards.</div>
<?php endif; ?>


    <!-- Display Existing Cards -->
    <div class="row">
    <?php foreach ($cards as $card): ?>
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100 border-0" style="background-color: #f9f9f9; border-radius: 20px;">
            <img src="/opt/bitnami/apache2/htdocs/Kaluppa/Frontend/admin_dashboard/uploads/featured/<?php echo htmlspecialchars($card['image']); ?>"
     class="card-img-top"
     alt="Image"
     style="border-top-left-radius: 20px; border-top-right-radius: 20px; object-fit: cover; height: 220px;">

                <div class="card-body">
                    <h5 class="card-title fw-bold text-dark"><?php echo htmlspecialchars($card['title']); ?></h5>
                    <p class="card-text text-muted"><?php echo htmlspecialchars($card['description']); ?></p>
                    <p class="card-text text-muted"><?php echo htmlspecialchars($card['details']); ?></p>
                    <hr>

                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="card_id" value="<?php echo $card['id']; ?>">
                        <input type="hidden" name="old_image" value="<?php echo $card['image']; ?>">

                        <div class="mb-2">
                            <label class="form-label fw-semibold text-dark">Edit Title</label>
                            <input type="text" name="title" class="form-control border-warning" value="<?php echo htmlspecialchars($card['title']); ?>" required>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-semibold text-dark">Edit Description</label>
                            <textarea name="description" class="form-control border-warning" rows="3" required><?php echo htmlspecialchars($card['description']); ?></textarea>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-semibold text-dark">Edit Details</label>
                            <textarea name="details" class="form-control border-warning" rows="5" required><?php echo htmlspecialchars($card['details']); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Change Image</label>
                            <input type="file" class="form-control border-warning" name="image" accept="image/*">
                        </div>

                        <button type="submit" name="update_card" class="btn btn-warning w-100 fw-bold">
                            <i class="bi bi-pencil-square me-1"></i> Update
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<!-- Toast Notification -->
<?php if (isset($_SESSION['toast'])): ?>
    <div class="position-fixed bottom-0 end-0 p-4" style="z-index: 9999;">
        <div class="toast align-items-center text-white bg-success border-0 show" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <?php echo $_SESSION['toast']; unset($_SESSION['toast']); ?>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
