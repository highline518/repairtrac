
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Notification Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen p-5">
    <div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow-md">
        <h1 class="text-2xl font-bold mb-6">SMS Notification Settings</h1>

        <?php
        require_once 'api/db.php';

        // Handle delete
        if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
            $stmt = $pdo->prepare("DELETE FROM sms_recipients WHERE id = ?");
            $stmt->execute([$_GET['delete']]);
            echo '<div class="bg-green-100 p-3 rounded mb-4">Recipient deleted successfully.</div>';
        }

        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validate input
            $name = trim($_POST['name'] ?? '');
            $phone = trim($_POST['phone_number'] ?? '');
            $notifyNewTasks = isset($_POST['notify_new_tasks']) ? 1 : 0;
            $notifyStatusChanges = isset($_POST['notify_status_changes']) ? 1 : 0;
            
            if (empty($name) || empty($phone)) {
                echo '<div class="bg-red-100 p-3 rounded mb-4">Name and phone number are required.</div>';
            } else {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO sms_recipients (name, phone_number, notify_new_tasks, notify_status_changes) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$name, $phone, $notifyNewTasks, $notifyStatusChanges]);
                    echo '<div class="bg-green-100 p-3 rounded mb-4">New recipient added successfully.</div>';
                } catch (PDOException $e) {
                    echo '<div class="bg-red-100 p-3 rounded mb-4">Error: ' . $e->getMessage() . '</div>';
                }
            }
        }
        ?>

        <div class="mb-6">
            <h2 class="text-xl font-semibold mb-3">Add New Recipient</h2>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-gray-700 mb-1">Name</label>
                    <input type="text" name="name" class="w-full p-2 border rounded" required>
                </div>
                <div>
                    <label class="block text-gray-700 mb-1">Phone Number (with country code)</label>
                    <input type="text" name="phone_number" class="w-full p-2 border rounded" placeholder="+1234567890" required>
                </div>
                <div class="flex items-center space-x-2">
                    <input type="checkbox" name="notify_new_tasks" id="notify_new_tasks" checked>
                    <label for="notify_new_tasks">Notify for new tasks</label>
                </div>
                <div class="flex items-center space-x-2">
                    <input type="checkbox" name="notify_status_changes" id="notify_status_changes" checked>
                    <label for="notify_status_changes">Notify for status changes</label>
                </div>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Add Recipient</button>
            
            <div class="mt-4 text-sm">
                <a href="consent_form.php" class="text-blue-500 hover:underline">Consent Form</a> | 
                <a href="consent_records.php" class="text-blue-500 hover:underline">View Consent Records</a>
            </div>
            </form>
        </div>

        <div>
            <h2 class="text-xl font-semibold mb-3">Current Recipients</h2>
            <table class="min-w-full bg-white">
                <thead>
                    <tr>
                        <th class="py-2 px-4 border-b text-left">Name</th>
                        <th class="py-2 px-4 border-b text-left">Phone Number</th>
                        <th class="py-2 px-4 border-b text-left">New Tasks</th>
                        <th class="py-2 px-4 border-b text-left">Status Changes</th>
                        <th class="py-2 px-4 border-b text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT * FROM sms_recipients ORDER BY name");
                    $recipients = $stmt->fetchAll();
                    
                    if (count($recipients) === 0) {
                        echo '<tr><td colspan="5" class="py-4 text-center text-gray-500">No recipients yet.</td></tr>';
                    } else {
                        foreach ($recipients as $recipient) {
                            echo '<tr>';
                            echo '<td class="py-2 px-4 border-b">' . htmlspecialchars($recipient['name']) . '</td>';
                            echo '<td class="py-2 px-4 border-b">' . htmlspecialchars($recipient['phone_number']) . '</td>';
                            echo '<td class="py-2 px-4 border-b">' . ($recipient['notify_new_tasks'] ? '✅' : '❌') . '</td>';
                            echo '<td class="py-2 px-4 border-b">' . ($recipient['notify_status_changes'] ? '✅' : '❌') . '</td>';
                            echo '<td class="py-2 px-4 border-b"><a href="?delete=' . $recipient['id'] . '" class="text-red-500 hover:underline" onclick="return confirm(\'Are you sure?\')">Delete</a></td>';
                            echo '</tr>';
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
