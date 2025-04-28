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
$stmt = $conn->prepare("SELECT * FROM users WHERE username=?");
$stmt->bind_param("s", $user);
$stmt->execute();
$result = $stmt->get_result();

$user_data = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $weight = $_POST['weight'];
    $steps = $_POST['steps'];
    $calories = $_POST['calories'];
    $sleep_hours = $_POST['sleep_hours'];
    $food_intake = $_POST['food_intake'];
    $date = date("Y-m-d");

    $stmt = $conn->prepare("INSERT INTO health_metrics (user_id, date, weight, steps, calories, sleep_hours, food_intake) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isiiids", $user_data['id'], $date, $weight, $steps, $calories, $sleep_hours, $food_intake);
    $stmt->execute();
}

$metrics_sql = "SELECT * FROM health_metrics WHERE user_id='$user_data[id]' ORDER BY date DESC";
$metrics_result = $conn->query($metrics_sql);
$metrics = [];
while ($row = $metrics_result->fetch_assoc()) {
    $metrics[] = $row;
}

$avg_weight = $avg_steps = $avg_calories = $avg_sleep = 0;
if (count($metrics) > 0) {
    foreach ($metrics as $metric) {
        $avg_weight += $metric['weight'];
        $avg_steps += $metric['steps'];
        $avg_calories += $metric['calories'];
        $avg_sleep += $metric['sleep_hours'];
    }
    $avg_weight /= count($metrics);
    $avg_steps /= count($metrics);
    $avg_calories /= count($metrics);
    $avg_sleep /= count($metrics);
}



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <script> 
       setTimeout(() => {
        data=JSON.parse(localStorage.getItem("response"));
        console.log(data);
        if (data.activities) {
            console.log(data.summary);
                    let summary = data.summary;
                    let html = `
                        <h3>Today's Activity Summary</h3>
                        <ul>
                            <li><strong>Steps:</strong> ${summary.steps}</li>
                            <li><strong>Calories Burned:</strong> ${summary.caloriesOut}</li>
                            <li><strong>Distance:</strong> ${summary.distances[0].distance} km</li>
                            <li><strong>Very Active Minutes:</strong> ${summary.veryActiveMinutes}</li>
                        </ul>
                    `;
                    document.getElementById('fitbit-data').innerHTML = html;
                } else {
                    document.getElementById('fitbit-data').innerHTML = "<p>Could not fetch data</p>";
                }
       }, 1000);
    </script>
</head>
<body>
    <!-- <?php echo $steps?> -->
    <div class="container">
        <nav class="navbar">
        <div class="nav-logo">Health Monitor</div>
            <ul class="nav-links">
                <li><a href="dashboard.php" class="nav-item">Dashboard</a></li>
                <li><a href="visualization.php" class="nav-item">Visualization</a></li>
                <li><a href="tips.php" class="nav-item">Tips</a></li>
                <li>
                    <?php if (isset($_SESSION['fitbit_access_token'])): ?>
                        <a href="fitbit-auth.php" class="nav-item">Refresh</a>
                    <?php else: ?>
                        <a href="fitbit-auth.php" class="nav-item">Connect to Fitbit</a>
                    <?php endif; ?>
                </li>
                <li><a href="logout.php" class="nav-item logout">Logout</a></li>
            </ul>
        </nav>

        <div class="dashboard">
            <h2>Welcome, <?php echo $user_data['name']; ?>!</h2>
            <p>Goal: <?php echo $user_data['goal']; ?></p>
            <form action="dashboard.php" method="POST">
                <input type="number" name="weight" placeholder="Weight (kg)" required>
                <input type="number" name="steps" placeholder="Steps" required>
                <input type="number" name="calories" placeholder="Calories" required>
                <input type="number" name="sleep_hours" placeholder="Sleep Hours" required>
                <input type="text" name="food_intake" placeholder="Food Intake" required>
                <br><br>
                <button type="submit">Submit</button>
            </form>
        </div>

        <div id="fitbit-data">
    Loading Fitbit data...
</div>  

<div class="dashboard">
    <h2>Fitbit Data Management</h2>
    <button id="storeMonthlyData" class="btn">Store Monthly Fitbit Data</button>
    <div id="storeStatus"></div>
</div>

<script>
document.getElementById('storeMonthlyData').addEventListener('click', function() {
    document.getElementById('storeStatus').innerHTML = 'Fetching and storing data, please wait...';
    
    fetch('store_fitbit_monthly.php')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('storeStatus').innerHTML = 
                    `<p style="color:green">${data.message}<br>Processed ${data.resources_processed} resources.</p>`;
            } else {
                document.getElementById('storeStatus').innerHTML = 
                    `<p style="color:red">Error: ${data.error || 'Unknown error occurred'}</p>`;
            }
        })
        .catch(error => {
            document.getElementById('storeStatus').innerHTML = 
                `<p style="color:red">Error: ${error.message}</p>`;
        });
});
</script>



<script>
    function fetchData() {
        fetch('fetch_fitbit_data.php')
            .then(response => response.json())
            .then(data => {
                let response=JSON.parse(localStorage.getItem("response"));
                if(response.summary) localStorage.setItem("response",JSON.stringify(data));
                if (data.activities) {
                    let summary = data.summary;
                    let html = `
                        <h3>Today's Activity Summary</h3>
                        <ul>
                            <li><strong>Steps:</strong> ${summary.steps}</li>
                            <li><strong>Calories Burned:</strong> ${summary.caloriesOut}</li>
                            <li><strong>Distance:</strong> ${summary.distances[0].distance} km</li>
                            <li><strong>Very Active Minutes:</strong> ${summary.veryActiveMinutes} minutes</li>
                        </ul>
                    `;
                    document.getElementById('fitbit-data').innerHTML = html;
                } else {
                    document.getElementById('fitbit-data').innerHTML = "<p>Could not fetch data</p>";
                }
            });
    }

    fetchData();
    setInterval(fetchData, 300000); 
</script>
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
                <?php foreach ($metrics as $metric) : ?>
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
        <!-- <div class="dashboard">
            <h2>Average Health Metrics</h2>
            <p>Average Weight: <span id="avgWeight"><?php echo number_format($avg_weight ,1); ?></span> kg</p>
            <p>Average Steps: <span id="avgSteps"><?php echo number_format($avg_steps,1); ?></span></p>
            <p>Average Calories: <span id="avgCalories"><?php echo number_format($avg_calories,0); ?></span></p>
            <p>Average Sleep Hours: <span id="avgSleep"><?php echo number_format($avg_sleep,1); ?></span> hours</p>
        </div>
        <div id="heartRateContainer">Loading...</div> Data will be shown here -->

        <div class="dashboard" style="display: flex; justify-content: space-around; background-color:gold; padding: 30px; border-radius: 20px; color: white; text-align: center; flex-wrap: wrap;">

    <!-- Weight -->
    <div class="metric-box">
        <img src="./Images/weight.jpeg" alt="Weight" style="height: 60px;"><br>
        <strong>Average Weight</strong>
        <p style="font-size: 18px;"><?php echo isset($avg_weight) ? number_format($avg_weight, 1) : "N/A"; ?> kg</p>
    </div>

    <!-- Steps -->
    <div class="metric-box" >
        <img src="./Images/Steps.png" alt="Steps" style="height: 60px;"><br>
        <strong>Average Steps</strong>
        <p style="font-size: 18px;"><?php echo isset($avg_steps) ? number_format($avg_steps, 1) : "0"; ?></p>
    </div>

    <!-- Calories -->
    <div class="metric-box" >
        <img src="./Images/calories.jpeg" alt="Calories" style="height: 60px;"><br>
        <strong>Average Calories</strong>
        <p style="font-size: 18px;"><?php echo isset($avg_calories) ? number_format($avg_calories, 0) : "0"; ?> cal</p>
    </div>

    <!-- Sleep -->
    <div class="metric-box" >
        <img src="./Images/night.jpeg" alt="Sleep" style="height: 60px;"><br>
        <strong>Average Sleep</strong>
        <p style="font-size: 18px;"><?php echo isset($avg_sleep) ? number_format($avg_sleep, 1) : "0"; ?> hrs</p>
    </div>

</div>


        <div>
           
        </div>
        

    </div>
</body>
</html>
