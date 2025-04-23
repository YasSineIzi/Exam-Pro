<?php
session_start();

// Display session information for debugging
echo '<h2>Session Diagnostic</h2>';

if (!isset($_SESSION) || empty($_SESSION)) {
    echo '<p style="color: red;"><strong>Session empty or not set!</strong></p>';
} else {
    echo '<p style="color: green;"><strong>Session exists and contains data.</strong></p>';
}

echo "<h3>Session Contents:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Authentication Status:</h3>";
if (isset($_SESSION['user_id'])) {
    echo '<p style="color: green;">User ID found: ' . $_SESSION['user_id'] . '</p>';
} else {
    echo '<p style="color: red;">No user ID found in session!</p>';
}

if (isset($_SESSION['user_role'])) {
    echo '<p style="color: green;">Role found: ' . $_SESSION['user_role'] . '</p>';

    if ($_SESSION['user_role'] === 'teacher') {
        echo '<p style="color: green;">User has teacher role ✓</p>';
        echo '<p>You should be able to access teacher features.</p>';
    } else {
        echo '<p style="color: red;">User does not have teacher role! Current role: ' . $_SESSION['user_role'] . '</p>';
        echo '<p>You need teacher permissions to access suspicious activities.</p>';
    }
} else {
    echo '<p style="color: red;">No role found in session! Session may be using different variable name.</p>';
}

echo "<h3>Session Configuration:</h3>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session Name: " . session_name() . "</p>";
echo "<p>Session Cookie Path: " . ini_get('session.cookie_path') . "</p>";
echo "<p>Session Save Path: " . session_save_path() . "</p>";

echo "<h3>Actions:</h3>";
echo '<p><a href="../index.php">Login Page</a></p>';
echo '<p><a href="suspicious_activities.php">Try Suspicious Activities Page</a></p>';
echo '<p><a href="index.php">Teacher Dashboard</a></p>';
?>