
<?php
require_once 'api/db.php';
require_once '.env.php';

// Error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Twilio credentials
$accountSid = getenv('TWILIO_ACCOUNT_SID');
$authToken = getenv('TWILIO_AUTH_TOKEN');

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phoneNumber = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    
    if (empty($phoneNumber)) {
        $error = "Please enter a phone number.";
    } else {
        // Format phone number if needed
        if (substr($phoneNumber, 0, 1) !== '+') {
            // Add country code if not present (assuming US)
            $phoneNumber = '+1' . preg_replace('/[^0-9]/', '', $phoneNumber);
        }
        
        // Send verification request to Twilio
        $url = "https://verify.twilio.com/v2/Services/" . getenv('TWILIO_SERVICE_SID') . "/Verifications";
        
        $data = array(
            'To' => $phoneNumber,
            'Channel' => 'sms'
        );
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_USERPWD, "$accountSid:$authToken");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        
        if ($err) {
            $error = "Error sending verification code: " . $err;
        } else {
            $success = "Verification code sent to $phoneNumber. Enter the code below to verify.";
        }
    }
}

// Process verification code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['code'])) {
    $phoneNumber = isset($_POST['verify_phone']) ? trim($_POST['verify_phone']) : '';
    $code = isset($_POST['code']) ? trim($_POST['code']) : '';
    
    if (empty($code)) {
        $verifyError = "Please enter the verification code.";
    } else {
        // Verify the code with Twilio
        $url = "https://verify.twilio.com/v2/Services/" . getenv('TWILIO_SERVICE_SID') . "/VerificationCheck";
        
        $data = array(
            'To' => $phoneNumber,
            'Code' => $code
        );
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_USERPWD, "$accountSid:$authToken");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        
        if ($err) {
            $verifyError = "Error verifying code: " . $err;
        } else {
            $responseData = json_decode($response, true);
            if (isset($responseData['status']) && $responseData['status'] === 'approved') {
                $verifySuccess = "Phone number verified successfully!";
                
                // Add to SMS recipients if not already in the list
                try {
                    $stmt = $pdo->prepare("SELECT * FROM sms_recipients WHERE phone_number = ?");
                    $stmt->execute([$phoneNumber]);
                    
                    if (!$stmt->fetch()) {
                        $stmt = $pdo->prepare("
                            INSERT INTO sms_recipients (name, phone_number, notify_new_tasks, notify_status_changes) 
                            VALUES (?, ?, 1, 1)
                        ");
                        $stmt->execute(["Verified User", $phoneNumber]);
                    }
                } catch (PDOException $e) {
                    // Ignore DB errors here
                }
            } else {
                $verifyError = "Invalid verification code.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Phone Number</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen p-5">
    <div class="max-w-md mx-auto bg-white p-6 rounded-lg shadow-md">
        <h1 class="text-2xl font-bold mb-6">Verify Your Phone Number</h1>
        
        <p class="mb-4">During Twilio's trial period, you need to verify phone numbers before you can send SMS to them.</p>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-100 p-3 rounded mb-4"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="bg-green-100 p-3 rounded mb-4"><?php echo $success; ?></div>
            
            <form method="post" class="mt-4">
                <input type="hidden" name="verify_phone" value="<?php echo htmlspecialchars($phoneNumber); ?>">
                
                <div class="mb-4">
                    <label for="code" class="block text-sm font-medium mb-1">Verification Code:</label>
                    <input type="text" id="code" name="code" class="w-full p-2 border rounded" placeholder="Enter the code sent to your phone">
                </div>
                
                <?php if (isset($verifyError)): ?>
                    <div class="bg-red-100 p-3 rounded mb-4"><?php echo $verifyError; ?></div>
                <?php endif; ?>
                
                <?php if (isset($verifySuccess)): ?>
                    <div class="bg-green-100 p-3 rounded mb-4"><?php echo $verifySuccess; ?></div>
                <?php endif; ?>
                
                <button type="submit" class="bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600">Verify Code</button>
            </form>
        <?php else: ?>
            <form method="post">
                <div class="mb-4">
                    <label for="phone" class="block text-sm font-medium mb-1">Phone Number:</label>
                    <input type="text" id="phone" name="phone" class="w-full p-2 border rounded" placeholder="+1234567890">
                    <p class="text-sm text-gray-500 mt-1">Include country code (e.g., +1 for US)</p>
                </div>
                
                <button type="submit" class="bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600">Send Verification Code</button>
            </form>
        <?php endif; ?>
        
        <div class="mt-6">
            <a href="sms_admin.php" class="text-blue-500 hover:underline">Return to SMS Admin</a>
        </div>
    </div>
</body>
</html>
