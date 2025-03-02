
<?php
require_once 'api/db.php';

// Check if the table exists
$tableExists = $pdo->query("SHOW TABLES LIKE 'sms_consent'")->rowCount() > 0;

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="consent_records_' . date('Y-m-d') . '.csv"');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Add CSV headers
    fputcsv($output, ['ID', 'Name', 'Phone Number', 'IP Address', 'Consent Date', 'Consent Text']);
    
    if ($tableExists) {
        $stmt = $pdo->query("SELECT * FROM sms_consent ORDER BY consent_date DESC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['id'],
                $row['name'],
                $row['phone_number'],
                $row['ip_address'],
                $row['consent_date'],
                $row['consent_text']
            ]);
        }
    }
    
    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Consent Records</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen p-5">
    <div class="max-w-6xl mx-auto bg-white p-6 rounded-lg shadow-md">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">SMS Consent Records</h1>
            <div>
                <a href="?export=csv" class="bg-green-500 text-white py-2 px-4 rounded hover:bg-green-600">
                    Export CSV
                </a>
                <a href="sms_admin.php" class="ml-2 bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600">
                    SMS Admin
                </a>
            </div>
        </div>
        
        <?php if (!$tableExists): ?>
            <div class="bg-yellow-100 p-4 rounded mb-4">
                No consent records found. The consent tracking system has not been initialized yet.
                <a href="consent_form.php" class="text-blue-500 hover:underline">Set up the consent form</a> to start collecting consent.
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead>
                        <tr>
                            <th class="py-2 px-4 border-b text-left">Name</th>
                            <th class="py-2 px-4 border-b text-left">Phone Number</th>
                            <th class="py-2 px-4 border-b text-left">IP Address</th>
                            <th class="py-2 px-4 border-b text-left">Consent Date</th>
                            <th class="py-2 px-4 border-b text-left">Consent Text</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("SELECT * FROM sms_consent ORDER BY consent_date DESC");
                        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (count($records) === 0): ?>
                            <tr>
                                <td colspan="5" class="py-4 text-center text-gray-500">No consent records found.</td>
                            </tr>
                        <?php else:
                            foreach ($records as $record): ?>
                                <tr>
                                    <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($record['name']); ?></td>
                                    <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($record['phone_number']); ?></td>
                                    <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($record['ip_address']); ?></td>
                                    <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($record['consent_date']); ?></td>
                                    <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($record['consent_text']); ?></td>
                                </tr>
                            <?php endforeach;
                        endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-8 text-sm text-gray-600">
                <h2 class="text-lg font-semibold mb-2">About Consent Records</h2>
                <p>These records serve as proof of consent for regulatory compliance. Each record contains:</p>
                <ul class="list-disc pl-8 mt-2">
                    <li>The recipient's name and phone number</li>
                    <li>IP address from which consent was provided</li>
                    <li>Date and time when consent was given</li>
                    <li>The exact text of the consent agreement</li>
                </ul>
                <p class="mt-2">
                    Maintain these records to demonstrate compliance with telecommunications regulations such as the TCPA (Telephone Consumer Protection Act).
                </p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
