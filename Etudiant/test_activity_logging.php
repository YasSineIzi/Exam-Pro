<?php
session_start();
require_once '../db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Get user info
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Unknown User';

// Insert test activity if requested
if (isset($_GET['test_activity']) && isset($_GET['exam_id'])) {
    $activity_type = $_GET['test_activity'];
    $exam_id = (int) $_GET['exam_id'];

    // Validate activity type
    $valid_activities = [
        'copy_attempt',
        'cut_attempt',
        'paste_attempt',
        'tab_switch',
        'alt_tab_detected',
        'dev_tools_attempt',
        'exit_fullscreen',
        'attempted_page_exit'
    ];

    if (in_array($activity_type, $valid_activities)) {
        // Insert activity log
        $stmt = $conn->prepare("
            INSERT INTO exam_activity_logs 
            (user_id, exam_id, activity_type, details, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");

        $details = json_encode(['test' => true, 'source' => 'test_activity_logging.php']);
        $ip = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];

        $stmt->bind_param("iissss", $user_id, $exam_id, $activity_type, $details, $ip, $user_agent);
        $success = $stmt->execute();

        $message = $success
            ? "Successfully logged {$activity_type} activity for exam #{$exam_id}"
            : "Failed to log activity: " . $conn->error;
    } else {
        $message = "Invalid activity type: {$activity_type}";
    }
}

// Get available exams for this user for testing
$stmt = $conn->prepare("
    SELECT e.* FROM exams e
    JOIN users u ON u.id = ?
    WHERE u.class_id = e.class_id
    ORDER BY e.start_date DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$exams = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get recent activity logs for verification
$stmt = $conn->prepare("
    SELECT eal.*, e.title, e.name
    FROM exam_activity_logs eal
    JOIN exams e ON e.id = eal.exam_id
    WHERE eal.user_id = ?
    ORDER BY eal.created_at DESC
    LIMIT 10
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Activity Logging - ExamPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <h1>Test Activity Logging</h1>
        <p class="lead">This page helps to test if suspicious activities are being correctly logged.</p>

        <?php if (isset($message)): ?>
            <div class="alert alert-info"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h4>Test Activities</h4>
                    </div>
                    <div class="card-body">
                        <h5>Select an exam:</h5>
                        <form method="get" id="testForm">
                            <div class="mb-3">
                                <select name="exam_id" class="form-select" required>
                                    <option value="">Select an exam</option>
                                    <?php foreach ($exams as $exam): ?>
                                        <option value="<?php echo $exam['id']; ?>">
                                            <?php echo htmlspecialchars($exam['title'] ?: $exam['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <input type="hidden" name="test_activity" id="activity_type" value="">
                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-danger" data-activity="copy_attempt">
                                        Test Copy Attempt
                                    </button>
                                    <button type="button" class="btn btn-warning" data-activity="paste_attempt">
                                        Test Paste Attempt
                                    </button>
                                    <button type="button" class="btn btn-warning" data-activity="exit_fullscreen">
                                        Test Exit Fullscreen
                                    </button>
                                    <button type="button" class="btn btn-danger" data-activity="attempted_page_exit">
                                        Test Page Exit
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h4>Real Activity Test</h4>
                    </div>
                    <div class="card-body">
                        <p>Try to perform these actions to see if they're detected:</p>
                        <ul>
                            <li>Press Ctrl+C on this text <span id="copyText">This is test text that should not be
                                    copied</span></li>
                            <li>Press Ctrl+V somewhere</li>
                            <li>Try to exit this page</li>
                        </ul>
                        <div id="log" class="mt-3 p-3 bg-light">Activity log will appear here...</div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Your Recent Activities</h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($activities)): ?>
                            <p class="text-muted">No activities logged yet.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date/Time</th>
                                            <th>Exam</th>
                                            <th>Activity</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($activities as $activity): ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y H:i:s', strtotime($activity['created_at'])); ?></td>
                                                <td><?php echo htmlspecialchars($activity['title'] ?: $activity['name']); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($activity['activity_type']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <a href="?refresh=1" class="btn btn-sm btn-primary mt-2">Refresh List</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Set up button actions
        document.querySelectorAll('[data-activity]').forEach(button => {
            button.addEventListener('click', function () {
                const activityType = this.getAttribute('data-activity');
                document.getElementById('activity_type').value = activityType;
                document.getElementById('testForm').submit();
            });
        });

        // Set up real anti-cheating test
        class SimpleAntiCheat {
            constructor() {
                this.log = document.getElementById('log');
                this.init();
            }

            init() {
                this.preventCopy();
                this.preventExit();
                this.logMessage('Anti-cheating initialized');
            }

            preventCopy() {
                document.addEventListener('copy', (e) => {
                    this.logMessage('Copy attempt detected!');
                    // For testing, we won't prevent the copy
                });

                document.addEventListener('paste', (e) => {
                    this.logMessage('Paste attempt detected!');
                });
            }

            preventExit() {
                window.addEventListener('beforeunload', (e) => {
                    this.logMessage('Page exit attempt detected!');
                    // We won't prevent for testing purposes
                });
            }

            logMessage(message) {
                const now = new Date().toLocaleTimeString();
                this.log.innerHTML += `<div>[${now}] ${message}</div>`;
            }
        }

        // Initialize the simple anti-cheat
        document.addEventListener('DOMContentLoaded', () => {
            new SimpleAntiCheat();
        });
    </script>
</body>

</html>