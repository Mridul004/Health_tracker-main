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

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user = $_SESSION['username'];
$stmt = $conn->prepare("SELECT id FROM users WHERE username=?");
$stmt->bind_param("s", $user);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$user_id = $user_data['id'];

$metrics_sql = "SELECT date, weight, steps, calories, sleep_hours FROM health_metrics WHERE user_id=? ORDER BY date ASC";
$stmt = $conn->prepare($metrics_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$metrics = [];
while ($row = $result->fetch_assoc()) {
    $metrics[] = $row;
}

header('Content-Type: application/json');
$jsonData = json_encode($metrics);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualization</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <nav class="navbar">
            <div class="nav-logo">Health Monitor</div>
            <ul class="nav-links">
                <li><a href="dashboard.php" class="nav-item">Dashboard</a></li>
                <li><a href="visualization.php" class="nav-item">Visualization</a></li>
                <li><a href="tips.php" class="nav-item">Tips</a></li>
                <li><a href="logout.php" class="nav-item logout">Logout</a></li>
            </ul>
        </nav>

        <div class="dashboard">
            <h2>Health Metrics Visualization</h2>
            <canvas id="healthChart"></canvas>
        </div>
    </div>

    <script>
        const jsonData = <?php echo $jsonData; ?>;
        const dates = jsonData.map(entry => entry.date);
        const weights = jsonData.map(entry => entry.weight);
        const steps = jsonData.map(entry => entry.steps);
        const calories = jsonData.map(entry => entry.calories);
        const sleepHours = jsonData.map(entry => entry.sleep_hours);

        const ctx = document.getElementById('healthChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [
                    {
                        label: 'Weight (kg)',
                        data: weights,
                        borderColor: 'red',
                        fill: false
                    },
                    {
                        label: 'Steps',
                        data: steps,
                        borderColor: 'blue',
                        fill: false
                    },
                    {
                        label: 'Calories',
                        data: calories,
                        borderColor: 'green',
                        fill: false
                    },
                    {
                        label: 'Sleep Hours',
                        data: sleepHours,
                        borderColor: 'purple',
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    x: { title: { display: true, text: 'Date' } },
                    y: { title: { display: true, text: 'Value' } }
                }
            }
        });
    </script>
</body>
</html>
