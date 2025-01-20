<!-- Summary Page -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Summary - Financial Records</title>
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
</head>
<body>
    <div class="container">
        <h2 class="mb-4">Financial Summary</h2>

        <?php
        $conn = new mysqli("localhost", "root", "", "financial_records_db");
        if ($conn->connect_error) {
            die("<div class='alert alert-danger'>Connection failed: {$conn->connect_error}</div>");
        }

        // Get the start and end date from the form
        $start_date = $_POST['start_date'] ?? null;
        $end_date = $_POST['end_date'] ?? null;

        // Prepare the date filter for SQL query if both dates are provided
        $date_filter_query = "";
        if ($start_date && $end_date) {
            $date_filter_query = "date BETWEEN '$start_date' AND '$end_date'";
        }

        // Total Income and Expense Calculation with Date Filter
        $totals_query = "SELECT 
                            SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) AS total_income,
                            SUM(CASE WHEN amount < 0 THEN amount ELSE 0 END) AS total_expense
                         FROM financial_records";

        if ($date_filter_query) {
            $totals_query .= " WHERE " . $date_filter_query;  // Add the date filter if available
        }

        $totals = $conn->query($totals_query)->fetch_assoc();
        $current_balance = $totals['total_income'] + $totals['total_expense'];

        // Category Totals
        $categories = ['expense-primer', 'expense-sekunder', 'expense-tersier'];
        $category_totals = [];
        foreach ($categories as $category) {
            $stmt = $conn->prepare("SELECT SUM(amount) AS total FROM financial_records WHERE type = ? " . ($date_filter_query ? "AND " . $date_filter_query : ""));
            $stmt->bind_param("s", $category);
            $stmt->execute();
            $stmt->bind_result($total);
            $stmt->fetch();
            $category_totals[$category] = $total ?? 0;
            $stmt->close();
        }
        ?>

        <!-- Date Range Filter Form -->
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

        <!-- Overall Totals -->
        <h4>Total Overall</h4>
        <table class="table table-bordered mb-4">
            <thead class="table-dark">
                <tr>
                    <th>Total Income</th>
                    <th>Total Expense</th>
                    <th>Total Money</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="income"><?= number_format($totals['total_income'] ?? 0, 2) ?></td>
                    <td class="expense"><?= number_format($totals['total_expense'] ?? 0, 2) ?></td>
                    <td class="text-primary"><?= number_format($current_balance ?? 0, 2) ?></td>
                </tr>
            </tbody>
        </table>

        <!-- Totals by Category -->
        <h4>Total Expenses by Category</h4>
        <table class="table table-bordered mb-4">
            <thead class="table-dark">
                <tr>
                    <th>Category</th>
                    <th>Total Expense</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td><?= ucfirst(str_replace('-', ' ', $category)) ?></td>
                        <td class="expense"><?= number_format($category_totals[$category], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Navigation to Records Page -->
        <a href="View.php" class="btn btn-primary mt-3">View All Records</a>
        <a href="Record.php" class="btn btn-secondary mt-3">Back to Add Records</a>
        <a href="export_summary_pdf.php" class="btn btn-success mt-3"><i class="bi bi-file-earmark-pdf"></i> Export Summary as PDF</a>
    </div>
</body>
</html>
