
<?php
require_once 'db.php';
require_once __DIR__ . '/../.env.php'; // Include Twilio credentials

// Function to send SMS notifications
function sendSMS($to, $message) {
    // Twilio credentials
    $accountSid = getenv('TWILIO_ACCOUNT_SID');
    $authToken = getenv('TWILIO_AUTH_TOKEN');
    $twilioNumber = getenv('TWILIO_PHONE_NUMBER');
    
    // Check if Twilio credentials are set
    if (!$accountSid || !$authToken || !$twilioNumber) {
        error_log("Twilio credentials not set. SMS not sent.");
        return false;
    }
    
    // Prepare the request to Twilio API
    $url = "https://api.twilio.com/2010-04-01/Accounts/$accountSid/Messages.json";
    
    $data = array(
        'From' => $twilioNumber,
        'To' => $to,
        'Body' => $message
    );
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_USERPWD, "$accountSid:$authToken");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log("Twilio API Error: $error");
        return false;
    }
    
    return $response;
}

// Function to notify about task creation
function notifyTaskCreation($task) {
    global $pdo;
    
    // Get all recipients from 'sms_recipients' table
    $stmt = $pdo->query("SELECT phone_number FROM sms_recipients WHERE notify_new_tasks = 1");
    $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($recipients)) {
        return false;
    }
    
    $message = "New task created: {$task['title']} (RO #{$task['roNumber']})";
    
    foreach ($recipients as $recipient) {
        sendSMS($recipient, $message);
    }
    
    return true;
}

// Function to notify about task status change
function notifyTaskStatusChange($task, $statusType, $oldStatus, $newStatus) {
    global $pdo;
    
    // Get all recipients from 'sms_recipients' table
    $stmt = $pdo->query("SELECT phone_number FROM sms_recipients WHERE notify_status_changes = 1");
    $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($recipients)) {
        return false;
    }
    
    $message = "Task status changed: {$task['title']} (RO #{$task['roNumber']}) - $statusType changed from $oldStatus to $newStatus";
    
    foreach ($recipients as $recipient) {
        sendSMS($recipient, $message);
    }
    
    return true;
}
?>
