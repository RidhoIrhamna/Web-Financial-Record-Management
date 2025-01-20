<!-- Record Page -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Financial Record</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            background-image: url('bg uang.jpg');
            background-size: cover;
            background-color: rgba(255, 255, 255, 0.8);
            background-blend-mode: overlay;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="mb-4">Add Financial Record</h2>

        <?php
        // Include database connection file
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "financial_records_db";

        $conn = new mysqli($servername, $username, $password, $dbname);

        if ($conn->connect_error) {
            die("<div class='alert alert-danger'>Connection failed: " . htmlspecialchars($conn->connect_error) . "</div>");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Sanitize and validate input
            $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
            // Gantilah FILTER_SANITIZE_STRING dengan htmlspecialchars()
            $type = htmlspecialchars(trim($_POST['type']));
            $date = htmlspecialchars(trim($_POST['date']));

            $errors = [];

            if ($amount === false) {
                $errors[] = "Amount is required and must be a valid number.";
            }
            if (empty($type)) {
                $errors[] = "Type is required.";
            }
            if (empty($date)) {
                $errors[] = "Date is required.";
            }

            if ($errors) {
                echo '<div class="alert alert-danger"><ul>';
                foreach ($errors as $error) {
                    echo "<li>" . htmlspecialchars($error) . "</li>";
                }
                echo '</ul></div>';
            } else {
                $stmt = $conn->prepare("INSERT INTO financial_records (amount, type, date) VALUES (?, ?, ?)");
                $stmt->bind_param("dss", $amount, $type, $date);

                if ($stmt->execute()) {
                    echo '<div class="alert alert-success" id="successMessage">Record successfully added!</div>';
                    echo "<script>
                            setTimeout(function() {
                                document.getElementById('successMessage').style.display = 'none';
                            }, 1000);
                          </script>";
                } else {
                    echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($stmt->error) . '</div>';
                }

                $stmt->close();
            }
        }

        $conn->close();
        ?>

        <form action="" method="POST" novalidate>
            <div class="mb-3">
                <label for="amount" class="form-label">Amount</label>
                <input 
                    type="number" 
                    class="form-control" 
                    id="amount" 
                    name="amount" 
                    placeholder="e.g., 1000 or -500" 
                    step="0.01"
                    required>
            </div>
            <div class="mb-3">
                <label for="type" class="form-label">Type</label>
                <select class="form-select" id="type" name="type" required>
                    <option value="">-- Select Type --</option>
                    <option value="income">Income</option>
                    <option value="expense-primer">Expense - Primer</option>
                    <option value="expense-sekunder">Expense - Sekunder</option>
                    <option value="expense-tersier">Expense - Tersier</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="date" class="form-label">Date</label>
                <input 
                    type="date" 
                    class="form-control" 
                    id="date" 
                    name="date" 
                    required>
                <div class="form-text">Choose the date for this financial record.</div>
            </div>
            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary">Submit</button>
                <a href="Summary.php" class="btn btn-success">Summary Records</a>
                <a href="View.php" class="btn btn-success">View Records</a>
            </div>
        </form>
    </div>

    <script>
        // Autofill today's date in the date input
        document.getElementById('date').valueAsDate = new Date();

        // Auto-correct amount based on type
        document.getElementById('type').addEventListener('change', function () {
            const type = this.value;
            const amountInput = document.getElementById('amount');
            if (type.startsWith('expense') && amountInput.value > 0) {
                amountInput.value = -Math.abs(amountInput.value);
            } else if (type === 'income' && amountInput.value < 0) {
                amountInput.value = Math.abs(amountInput.value);
            }
        });

        // Prevent submission if input is invalid
        document.querySelector('form').addEventListener('submit', function (event) {
            const amount = parseFloat(document.getElementById('amount').value.trim());
            const type = document.getElementById('type').value.trim();
            const date = document.getElementById('date').value.trim();

            if (!amount || isNaN(amount)) {
                alert('Amount is required and must be a valid number.');
                event.preventDefault();
            } else if (!type) {
                alert('Type is required.');
                event.preventDefault();
            } else if (!date) {
                alert('Date is required.');
                event.preventDefault();
            } else if (type.startsWith('expense') && amount > 0) {
                alert('For expenses, the amount must be negative.');
                event.preventDefault();
            } else {
                // Konfirmasi sebelum submit
                const confirmSubmit = confirm("Are you sure you want to submit this record?");
                if (!confirmSubmit) {
                    event.preventDefault();
                }
            }
        });
    </script>
</body>
</html>
