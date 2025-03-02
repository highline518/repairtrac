
<?php
require_once 'api/db.php';
require_once '.env.php';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if this is a consent submission
    if (isset($_POST['consent_action']) && $_POST['consent_action'] === 'provide_consent') {
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        
        if (empty($phone) || empty($name)) {
            $error = "Both name and phone number are required.";
        } else {
            // Format phone number
            if (substr($phone, 0, 1) !== '+') {
                $phone = '+1' . preg_replace('/[^0-9]/', '', $phone);
            }
            
            try {
                // First check if we already have a consent table
                $tableExists = $pdo->query("SHOW TABLES LIKE 'sms_consent'")->rowCount() > 0;
                
                if (!$tableExists) {
                    // Create consent table
                    $pdo->exec("
                        CREATE TABLE sms_consent (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            name VARCHAR(100) NOT NULL,
                            phone_number VARCHAR(20) NOT NULL,
                            ip_address VARCHAR(45),
                            consent_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            consent_text TEXT NOT NULL
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                    ");
                }
                
                // Store consent in database
                $consentText = "I consent to receive SMS notifications from Task Manager regarding task updates, status changes, and other relevant information.";
                $ipAddress = $_SERVER['REMOTE_ADDR'];
                
                $stmt = $pdo->prepare("
                    INSERT INTO sms_consent (name, phone_number, ip_address, consent_text)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$name, $phone, $ipAddress, $consentText]);
                
                // Also add to recipients if not already there
                $stmt = $pdo->prepare("SELECT id FROM sms_recipients WHERE phone_number = ?");
                $stmt->execute([$phone]);
                
                if (!$stmt->fetch()) {
                    $stmt = $pdo->prepare("
                        INSERT INTO sms_recipients (name, phone_number, notify_new_tasks, notify_status_changes)
                        VALUES (?, ?, 1, 1)
                    ");
                    $stmt->execute([$name, $phone]);
                }
                
                $success = "Thank you! Your consent has been recorded and you will now receive SMS notifications.";
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Function to generate a consent record PDF
function generateConsentPDF($consentId) {
    // In a real implementation, you would generate a PDF with consent details
    // This would require a PDF library like FPDF or TCPDF
    return true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Consent Form - Task Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen p-5">
    <div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow-md">
        <h1 class="text-2xl font-bold mb-6">SMS Consent Form</h1>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-100 p-4 rounded mb-4"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="bg-green-100 p-4 rounded mb-6">
                <p><?php echo $success; ?></p>
                <p class="mt-2">Your consent has been recorded for compliance purposes.</p>
                <p class="mt-4">
                    <a href="sms_admin.php" class="text-blue-500 hover:underline">Go to SMS Admin Panel</a>
                </p>
            </div>
        <?php else: ?>
            <div class="mb-6">
                <p class="mb-2">By completing this form, you agree to receive text messages from our Task Manager system.</p>
                <p class="mb-2">We will send you notifications about:</p>
                <ul class="list-disc pl-8 mb-4">
                    <li>New task creation</li>
                    <li>Changes to task status</li>
                    <li>Important task updates</li>
                </ul>
                <p class="text-sm text-gray-600">
                    Message and data rates may apply. You can opt out at any time by visiting the SMS Admin page.
                    Your phone number and consent information will be stored securely for compliance purposes.
                </p>
            </div>
            
            <form method="POST" class="space-y-4">
                <input type="hidden" name="consent_action" value="provide_consent">
                
                <div>
                    <label for="name" class="block text-sm font-medium mb-1">Full Name:</label>
                    <input type="text" id="name" name="name" class="w-full p-2 border rounded" required>
                </div>
                
                <div>
                    <label for="phone" class="block text-sm font-medium mb-1">Phone Number:</label>
                    <input type="text" id="phone" name="phone" class="w-full p-2 border rounded" placeholder="+1 (123) 456-7890" required>
                    <p class="text-xs text-gray-500 mt-1">Include country code (e.g., +1 for US)</p>
                </div>
                
                <div class="pt-4">
                    <label class="flex items-start">
                        <input type="checkbox" required class="mt-1 mr-2">
                        <span class="text-sm">
                            I consent to receive SMS notifications from Task Manager. I understand that 
                            I can withdraw my consent at any time by visiting the SMS Admin page.
                        </span>
                    </label>
                </div>
                
                <div class="pt-2">
                    <button type="submit" class="bg-blue-500 text-white py-2 px-6 rounded hover:bg-blue-600">
                        Provide Consent
                    </button>
                </div>
            </form>
            
            <div class="mt-8 pt-6 border-t border-gray-200">
                <h2 class="text-lg font-semibold mb-2">Privacy and Compliance Information</h2>
                <p class="text-sm text-gray-600">
                    This consent record is maintained in compliance with telecommunications regulations.
                    We store your name, phone number, IP address, date and time of consent, and the specific 
                    terms you agreed to. This information may be used to demonstrate your consent 
                    was properly obtained if required by regulatory authorities.
                </p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
