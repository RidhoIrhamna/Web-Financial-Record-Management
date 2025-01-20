<!-- View Page -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Records - Financial Records</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            background-image: url('bg uang.jpg');
            background-size: cover;
            background-color: rgba(255, 255, 255, 0.8);
            background-blend-mode: overlay;
        }
        .income {
            color: green;
        }
        .expense {
            color: red;
        }
    </style>
    <script>
        // Function to hide alert after 2 seconds
        function hideAlert() {
            setTimeout(function () {
                let alert = document.querySelector(".alert");
                if (alert) {
                    alert.style.display = 'none';
                }
            }, 2000);  // 2 seconds
        }
    </script>
</head>
<body>
    <div class="container">
        <h2 class="mb-4">All Financial Records</h2>

        <?php
        // Database connection
        $conn = new mysqli("localhost", "root", "", "financial_records_db");
        if ($conn->connect_error) {
            die("<div class='alert alert-danger'>Connection failed: {$conn->connect_error}</div>");
        }

        // Handle Delete Per Record
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['delete_id'])) {
                $delete_id = $_POST['delete_id'];
                $stmt = $conn->prepare("DELETE FROM financial_records WHERE id = ?");
                $stmt->bind_param("i", $delete_id);
                if ($stmt->execute()) {
                    echo "<div class='alert alert-success'>Record with ID $delete_id deleted successfully.</div>";
                } else {
                    echo "<div class='alert alert-danger'>Error deleting record: {$stmt->error}</div>";
                }
                $stmt->close();
            }

            // Handle Delete All Records
            if (isset($_POST['delete_all'])) {
                if ($conn->query("DELETE FROM financial_records")) {
                    echo "<div class='alert alert-success'>All records deleted successfully.</div>";
                } else {
                    echo "<div class='alert alert-danger'>Error deleting records: {$conn->error}</div>";
                }
            }

            // Handle Update Per Record
            if (isset($_POST['update_id'])) {
                $update_id = $_POST['update_id'];
                $amount = $_POST['amount'];
                $type = $_POST['type'];
                $date = $_POST['date'];

                $stmt = $conn->prepare("UPDATE financial_records SET amount = ?, type = ?, date = ? WHERE id = ?");
                $stmt->bind_param("dssi", $amount, $type, $date, $update_id);

                if ($stmt->execute()) {
                    echo "<div class='alert alert-success'>Record updated successfully.</div>";
                } else {
                    echo "<div class='alert alert-danger'>Error updating record: {$stmt->error}</div>";
                }
                $stmt->close();
            }
        }

        // Date filter for records
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        $date_filter_query = ($start_date && $end_date) ? "WHERE date BETWEEN '$start_date' AND '$end_date'" : "";

        $result = $conn->query("SELECT * FROM financial_records $date_filter_query ORDER BY date DESC");
        ?>

        <!-- Filter Records -->
        <form method="POST" class="mb-4 row g-3">
            <div class="col-md-4">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" name="start_date" id="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
            </div>
            <div class="col-md-4">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" name="end_date" id="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
            </div>
            <div class="col-md-4 align-self-end">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </form>

        <!-- Alert Notifications -->
        <div id="alert-container">
            <?php if (isset($_POST['delete_id']) || isset($_POST['delete_all']) || isset($_POST['update_id'])): ?>
                <script>
                    window.onload = function() {
                        hideAlert();  // Call the function to hide the alert after 2 seconds
                    };
                </script>
            <?php endif; ?>
        </div>

        <!-- Records Table -->
        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Amount</th>
                    <th>Type</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php $index = 1; ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr class="<?= $row['amount'] > 0 ? 'income' : 'expense' ?>">
                            <td><?= $index++ ?></td>
                            <td><?= number_format($row['amount'], 2) ?></td>
                            <td><?= ucfirst(str_replace('-', ' ', $row['type'])) ?></td>
                            <td><?= htmlspecialchars($row['date']) ?></td>
                            <td>
                                <!-- Form Delete Per Record -->
                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this record?');" style="display:inline-block;">
                                    <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                                
                                <!-- Form Update Per Record -->
                                <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#updateModal<?= $row['id'] ?>">Update</button>

                                <!-- Modal Update -->
                                <div class="modal fade" id="updateModal<?= $row['id'] ?>" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="updateModalLabel">Update Record</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form method="POST">
                                                    <div class="mb-3">
                                                        <label for="amount" class="form-label">Amount</label>
                                                        <input type="number" step="0.01" class="form-control" name="amount" value="<?= htmlspecialchars($row['amount']) ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="type" class="form-label">Type</label>
                                                        <select name="type" class="form-select" required>
                                                            <option value="income" <?= $row['type'] == 'income' ? 'selected' : '' ?>>Income</option>
                                                            <option value="expense" <?= $row['type'] == 'expense' ? 'selected' : '' ?>>Expense</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="date" class="form-label">Date</label>
                                                        <input type="date" class="form-control" name="date" value="<?= htmlspecialchars($row['date']) ?>" required>
                                                    </div>
                                                    <input type="hidden" name="update_id" value="<?= $row['id'] ?>">
                                                    <button type="submit" class="btn btn-primary">Update</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center">No records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- All Buttons -->
        <div class="d-flex gap-2 mt-3">
            <form action="export_records_pdf.php" method="POST" class="d-inline-block">
                <a href="Summary.php" class="btn btn-primary">View Summary Records</a>
            </form>
            <form action="Record.php" method="GET" class="d-inline-block">
                <a href="Record.php" class="btn btn-secondary">Back to Add Records</a>
            </form>
            <form action="export_records_pdf.php" method="POST" class="d-inline-block">
                <input type="hidden" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
                <input type="hidden" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-file-earmark-pdf"></i> Export Records as PDF
                </button>
            </form>
            <!-- Delete All Records -->
            <form method="POST" onsubmit="return confirm('Are you sure you want to delete all records?');" class="d-inline-block">
                <button type="submit" name="delete_all" class="btn btn-danger">Delete All Records</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
</body>
</html>
