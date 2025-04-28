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

// Function to get data from a specific Fitbit table
function getFitbitData($conn, $table, $user_id, $limit = 30) {
    $data = [];
    
    // Check if table exists
    $table_check = $conn->query("SHOW TABLES LIKE '$table'");
    if ($table_check->num_rows == 0) {
        return $data;
    }
    
    $sql = "SELECT date, value FROM $table WHERE user_id = ? ORDER BY date DESC LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $data[$row['date']] = $row['value'];
    }
    
    return $data;
}

// Get sleep data
function getSleepData($conn, $user_id, $limit = 30) {
    $data = [];
    
    // Check if table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'fitbit_sleep'");
    if ($table_check->num_rows == 0) {
        return $data;
    }
    
    $sql = "SELECT date, minutes_asleep, minutes_awake, efficiency FROM fitbit_sleep 
            WHERE user_id = ? ORDER BY date DESC LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $data[$row['date']] = [
            'minutes_asleep' => $row['minutes_asleep'],
            'minutes_awake' => $row['minutes_awake'],
            'efficiency' => $row['efficiency'],
            'hours_asleep' => round($row['minutes_asleep'] / 60, 1)
        ];
    }
    
    return $data;
}

// Get data from all tables
$steps_data = getFitbitData($conn, 'fitbit_steps', $user_id);
$calories_data = getFitbitData($conn, 'fitbit_calories', $user_id);
$distance_data = getFitbitData($conn, 'fitbit_distance', $user_id);
$active_minutes_data = getFitbitData($conn, 'fitbit_minutesveryactive', $user_id);
$sleep_data = getSleepData($conn, $user_id);

// Get dates for display
$dates = [];
if (!empty($steps_data)) {
    $dates = array_keys($steps_data);
} elseif (!empty($calories_data)) {
    $dates = array_keys($calories_data);
} elseif (!empty($sleep_data)) {
    $dates = array_keys($sleep_data);
}

// Calculate averages
function calculateAverage($data) {
    if (empty($data)) return 0;
    return array_sum($data) / count($data);
}

$avg_steps = calculateAverage($steps_data);
$avg_calories = calculateAverage($calories_data);
$avg_distance = calculateAverage($distance_data);
$avg_active_minutes = calculateAverage($active_minutes_data);

// Calculate average sleep
$avg_sleep_hours = 0;
if (!empty($sleep_data)) {
    $sleep_hours = array_map(function($item) {
        return $item['hours_asleep'];
    }, $sleep_data);
    $avg_sleep_hours = calculateAverage($sleep_hours);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fitbit Monthly Data</title>
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
                <li><a href="fitbit_monthly.php" class="nav-item active">Fitbit Monthly</a></li>
                <li><a href="logout.php" class="nav-item logout">Logout</a></li>
            </ul>
        </nav>

        <div class="dashboard">
            <h2>Monthly Fitbit Statistics</h2>
            
            <?php if (empty($dates)): ?>
                <p>No Fitbit data stored yet. Please click "Store Monthly Fitbit Data" on the dashboard first.</p>
            <?php else: ?>
                <div class="stats-summary" style="display: flex; justify-content: space-around; background-color:#4d90fe; padding: 20px; border-radius: 10px; color: white; margin-bottom: 20px;">
                    <div class="metric-box">
                        <strong>Avg Daily Steps</strong>
                        <p style="font-size: 18px;"><?php echo number_format($avg_steps, 0); ?></p>
                    </div>
                    <div class="metric-box">
                        <strong>Avg Daily Calories</strong>
                        <p style="font-size: 18px;"><?php echo number_format($avg_calories, 0); ?> cal</p>
                    </div>
                    <div class="metric-box">
                        <strong>Avg Daily Distance</strong>
                        <p style="font-size: 18px;"><?php echo number_format($avg_distance, 2); ?> km</p>
                    </div>
                    <div class="metric-box">
                        <strong>Avg Active Minutes</strong>
                        <p style="font-size: 18px;"><?php echo number_format($avg_active_minutes, 0); ?> min</p>
                    </div>
                    <div class="metric-box">
                        <strong>Avg Sleep</strong>
                        <p style="font-size: 18px;"><?php echo number_format($avg_sleep_hours, 1); ?> hrs</p>
                    </div>
                </div>
                
                <!-- Charts -->
                <div style="margin-bottom: 30px;">
                    <h3>Steps Data</h3>
                    <canvas id="stepsChart"></canvas>
                </div>
                
                <div style="margin-bottom: 30px;">
                    <h3>Sleep Data</h3>
                    <canvas id="sleepChart"></canvas>
                </div>
                
                <!-- Data Tables -->
                <h3>Detailed Data</h3>
                <table>
                    <tr>
                        <th>Date</th>
                        <th>Steps</th>
                        <th>Calories</th>
                        <th>Distance (km)</th>
                        <th>Active Minutes</th>
                        <th>Sleep (hours)</th>
                    </tr>
                    <?php foreach ($dates as $date): ?>
                    <tr>
                        <td><?php echo $date; ?></td>
                        <td><?php echo isset($steps_data[$date]) ? number_format($steps_data[$date], 0) : 'N/A'; ?></td>
                        <td><?php echo isset($calories_data[$date]) ? number_format($calories_data[$date], 0) : 'N/A'; ?></td>
                        <td><?php echo isset($distance_data[$date]) ? number_format($distance_data[$date], 2) : 'N/A'; ?></td>
                        <td><?php echo isset($active_minutes_data[$date]) ? number_format($active_minutes_data[$date], 0) : 'N/A'; ?></td>
                        <td><?php echo isset($sleep_data[$date]) ? number_format($sleep_data[$date]['hours_asleep'], 1) : 'N/A'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (!empty($dates)): ?>
    <script>
        // Prepare data for charts
        const dates = <?php echo json_encode(array_reverse($dates)); ?>;
        
        // Steps chart
        const stepsData = <?php echo json_encode(array_map(function($date) use ($steps_data) {
            return isset($steps_data[$date]) ? $steps_data[$date] : null;
        }, array_reverse($dates))); ?>;
        
        new Chart(document.getElementById('stepsChart'), {
            type: 'line',
            data: {
                labels: dates,
                datasets: [{
                    label: 'Daily Steps',
                    data: stepsData,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    tension: 0.1,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Sleep chart
        const sleepData = <?php echo json_encode(array_map(function($date) use ($sleep_data) {
            return isset($sleep_data[$date]) ? $sleep_data[$date]['hours_asleep'] : null;
        }, array_reverse($dates))); ?>;
        
        new Chart(document.getElementById('sleepChart'), {
            type: 'line',
            data: {
                labels: dates,
                datasets: [{
                    label: 'Sleep Hours',
                    data: sleepData,
                    borderColor: 'rgba(153, 102, 255, 1)',
                    tension: 0.1,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>