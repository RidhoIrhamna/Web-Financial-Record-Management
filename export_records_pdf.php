<?php
require_once __DIR__ . '/vendor/autoload.php'; // Path mPDF melalui Composer

// Database connection
$conn = new mysqli("localhost", "root", "", "financial_records_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Retrieve date filters
$start_date = $_POST['start_date'] ?? null;
$end_date = $_POST['end_date'] ?? null;

// Build query based on filters
$date_filter_query = ($start_date && $end_date) ? "WHERE date BETWEEN '$start_date' AND '$end_date'" : "";

$result = $conn->query("SELECT * FROM financial_records $date_filter_query ORDER BY date DESC");

// Generate PDF using mPDF
$mpdf = new \Mpdf\Mpdf();

// Add heading
$html = "
    <h2>Financial Records</h2>";

// Show date range only if filters are applied
if ($start_date && $end_date) {
    $html .= "<h4>Records from: $start_date to $end_date</h4>";
}

// Start building the table
$html .= "
    <table border='1' style='width: 100%; border-collapse: collapse;'>
        <thead>
            <tr>
                <th>#</th>
                <th>Amount</th>
                <th>Type</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>";

// Check if there are records
if ($result->num_rows > 0) {
    $index = 1;
    while ($row = $result->fetch_assoc()) {
        $html .= "
            <tr>
                <td>{$index}</td>
                <td>".number_format($row['amount'], 2)."</td>
                <td>".ucfirst(str_replace('-', ' ', $row['type']))."</td>
                <td>{$row['date']}</td>
            </tr>";
        $index++;
    }
} else {
    $html .= "
            <tr>
                <td colspan='4' style='text-align: center;'>No records found.</td>
            </tr>";
}

$html .= "
        </tbody>
    </table>";

// Output PDF
$mpdf->WriteHTML($html);
$mpdf->Output('Financial_Records.pdf', 'D');
