<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "health_tracker";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user = $_SESSION['username'];
$sql = "SELECT * FROM users WHERE username='$user'";
$result = $conn->query($sql);
$user_data = $result->fetch_assoc();

$metrics_sql = "SELECT * FROM health_metrics WHERE user_id='$user_data[id]' ORDER BY date DESC";
$metrics_result = $conn->query($metrics_sql);

$metrics = [];
while ($row = $metrics_result->fetch_assoc()) {
    $metrics[] = $row;
}

$avg_weight = 62; // Average human weight in kg (can be sourced from reliable data)
$avg_steps = 5000; // Average daily steps
$avg_calories = 2000; // Average daily calorie intake
$avg_sleep = 8; // Average daily sleep in hours
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Results Comparison</h1>
        <div class="dashboard">
            <h2>Your Health Metrics</h2>
            <table>
                <tr>
                    <th>Date</th>
                    <th>Weight (kg)</th>
                    <th>Steps</th>
                    <th>Calories</th>
                    <th>Sleep Hours</th>
                    <th>Food Intake</th>
                </tr>
                <?php foreach ($metrics as $metric): ?>
                    <tr>
                        <td><?php echo $metric['date']; ?></td>
                        <td><?php echo $metric['weight']; ?></td>
                        <td><?php echo $metric['steps']; ?></td>
                        <td><?php echo $metric['calories']; ?></td>
                        <td><?php echo $metric['sleep_hours']; ?></td>
                        <td><?php echo $metric['food_intake']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="dashboard">
            <h2>Comparison with Average Human Metrics</h2>
            <p>Average Human Weight: <?php echo $avg_weight; ?> kg</p>
            <p>Average Human Daily Steps: <?php echo $avg_steps; ?></p>
            <p>Average Human Daily Calorie Intake: <?php echo $avg_calories; ?></p>
            <p>Average Human Daily Sleep Hours: <?php echo $avg_sleep; ?> hours</p>
        </div>

        <a href="dashboard.php">Back to Dashboard</a>
    </div>
</body>
</html>
