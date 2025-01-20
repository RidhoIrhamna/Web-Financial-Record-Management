<?php
require_once __DIR__ . '/vendor/autoload.php'; // Path mPDF melalui Composer

$conn = new mysqli("localhost", "root", "", "financial_records_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$totals_query = "SELECT 
                    SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) AS total_income,
                    SUM(CASE WHEN amount < 0 THEN amount ELSE 0 END) AS total_expense
                 FROM financial_records";
$totals = $conn->query($totals_query)->fetch_assoc();
$current_balance = $totals['total_income'] + $totals['total_expense'];

$categories = ['expense-primer', 'expense-sekunder', 'expense-tersier'];
$category_totals = [];
foreach ($categories as $category) {
    $stmt = $conn->prepare("SELECT SUM(amount) AS total FROM financial_records WHERE type = ? AND amount < 0");
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $stmt->bind_result($total);
    $stmt->fetch();
    $category_totals[$category] = $total ?? 0;
    $stmt->close();
}

// Generate PDF
$mpdf = new \Mpdf\Mpdf();
$html = "
    <h2>Financial Summary</h2>
    <h4>Total Overall</h4>
    <table border='1' style='width: 100%; border-collapse: collapse;'>
        <thead>
            <tr>
                <th>Total Income</th>
                <th>Total Expense</th>
                <th>Total Money</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style='color: green;'>".number_format($totals['total_income'], 2)."</td>
                <td style='color: red;'>".number_format($totals['total_expense'], 2)."</td>
                <td style='color: blue;'>".number_format($current_balance, 2)."</td>
            </tr>
        </tbody>
    </table>

    <h4>Total Expenses by Category</h4>
    <table border='1' style='width: 100%; border-collapse: collapse;'>
        <thead>
            <tr>
                <th>Category</th>
                <th>Total Expense</th>
            </tr>
        </thead>
        <tbody>";
foreach ($categories as $category) {
    $html .= "
            <tr>
                <td>".ucfirst(str_replace('-', ' ', $category))."</td>
                <td style='color: red;'>".number_format($category_totals[$category], 2)."</td>
            </tr>";
}
$html .= "
        </tbody>
    </table>
";

$mpdf->WriteHTML($html);
$mpdf->Output('Financial_Summary.pdf', 'D');
