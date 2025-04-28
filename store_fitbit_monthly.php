<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['fitbit_access_token'])) {
    echo json_encode(["error" => "Authentication required", "redirect" => "login.html"]);
    exit();
}

// Rest of the file remains the same...

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "health_tracker";

// Connect to database
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get current user ID from database
$user = $_SESSION['username'];
$stmt = $conn->prepare("SELECT id FROM users WHERE username=?");
$stmt->bind_param("s", $user);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$user_id = $user_data['id'];

// Access token from session
$access_token = $_SESSION['fitbit_access_token'];

// Calculate date range for monthly data (last 30 days)
$end_date = date('Y-m-d'); // Today
$start_date = date('Y-m-d', strtotime('-30 days')); // 30 days ago

// Define resources to fetch
$resources = ['steps', 'calories', 'distance', 'minutesSedentary', 'minutesLightlyActive', 
              'minutesFairlyActive', 'minutesVeryActive', 'activityCalories', 'floors', 'elevation'];

$all_data = [];

// Fetch data for each resource
foreach ($resources as $resource) {
    $ch = curl_init();
    $url = "https://api.fitbit.com/1/user/-/activities/$resource/date/$start_date/$end_date.json";
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $access_token",
        "Accept: application/json"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200) {
        $data = json_decode($response, true);
        $all_data[$resource] = $data;
        
        // Store this resource's data in database
        storeResourceData($conn, $user_id, $resource, $data, "activities-$resource");
    } else {
        // Log errors
        error_log("Error fetching $resource data: HTTP $http_code, Response: $response");
    }
    
    // Respect Fitbit's rate limits - add a small delay between requests
    usleep(250000); // 250ms
}

// Also fetch sleep data
$ch = curl_init();
$url = "https://api.fitbit.com/1.2/user/-/sleep/date/$start_date/$end_date.json";

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $access_token",
    "Accept: application/json"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code == 200) {
    $sleep_data = json_decode($response, true);
    $all_data['sleep'] = $sleep_data;
    
    // Process and store sleep data
    processSleepData($conn, $user_id, $sleep_data);
}

// Function to store resource data in database
function storeResourceData($conn, $user_id, $resource, $data, $key) {
    // Check if we have the right key in the data
    if (!isset($data[$key])) {
        error_log("Key $key not found in data for resource $resource");
        return;
    }
    
    // Create table if it doesn't exist
    $table_name = "fitbit_" . strtolower($resource);
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        date DATE NOT NULL,
        value FLOAT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY user_date (user_id, date)
    )";
    
    if (!$conn->query($sql)) {
        error_log("Error creating table $table_name: " . $conn->error);
        return;
    }
    
    // Prepare statement for inserting/updating data
    $stmt = $conn->prepare("INSERT INTO $table_name (user_id, date, value) 
                          VALUES (?, ?, ?) 
                          ON DUPLICATE KEY UPDATE value = ?");
                          
    // Insert each day's data
    foreach ($data[$key] as $entry) {
        if (isset($entry['dateTime']) && isset($entry['value'])) {
            $date = $entry['dateTime'];
            $value = is_numeric($entry['value']) ? $entry['value'] : 0;
            
            $stmt->bind_param("isdd", $user_id, $date, $value, $value);
            $stmt->execute();
            
            if ($stmt->error) {
                error_log("Error inserting data for $resource on $date: " . $stmt->error);
            }
        }
    }
}

// Function to process and store sleep data
function processSleepData($conn, $user_id, $sleep_data) {
    // Create table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS fitbit_sleep (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        date DATE NOT NULL,
        minutes_asleep INT NOT NULL,
        minutes_awake INT NOT NULL,
        efficiency INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY user_date (user_id, date)
    )";
    
    if (!$conn->query($sql)) {
        error_log("Error creating table fitbit_sleep: " . $conn->error);
        return;
    }
    
    // Process sleep data by date
    $sleep_by_date = [];
    
    if (isset($sleep_data['sleep'])) {
        foreach ($sleep_data['sleep'] as $sleep) {
            $date = substr($sleep['dateOfSleep'], 0, 10);
            
            if (!isset($sleep_by_date[$date])) {
                $sleep_by_date[$date] = [
                    'minutes_asleep' => 0,
                    'minutes_awake' => 0,
                    'efficiency' => 0,
                    'count' => 0
                ];
            }
            
            $sleep_by_date[$date]['minutes_asleep'] += isset($sleep['minutesAsleep']) ? $sleep['minutesAsleep'] : 0;
            $sleep_by_date[$date]['minutes_awake'] += isset($sleep['minutesAwake']) ? $sleep['minutesAwake'] : 0;
            $sleep_by_date[$date]['efficiency'] += isset($sleep['efficiency']) ? $sleep['efficiency'] : 0;
            $sleep_by_date[$date]['count']++;
        }
        
        // Average efficiency if there are multiple sleep records per day
        foreach ($sleep_by_date as $date => $data) {
            if ($data['count'] > 0) {
                $sleep_by_date[$date]['efficiency'] = round($data['efficiency'] / $data['count']);
            }
        }
        
        // Store aggregated sleep data
        $stmt = $conn->prepare("INSERT INTO fitbit_sleep (user_id, date, minutes_asleep, minutes_awake, efficiency) 
                              VALUES (?, ?, ?, ?, ?) 
                              ON DUPLICATE KEY UPDATE 
                              minutes_asleep = ?, minutes_awake = ?, efficiency = ?");
                              
        foreach ($sleep_by_date as $date => $data) {
            $minutes_asleep = $data['minutes_asleep'];
            $minutes_awake = $data['minutes_awake'];
            $efficiency = $data['efficiency'];
            
            $stmt->bind_param("isiiiii", $user_id, $date, $minutes_asleep, $minutes_awake, $efficiency, 
                              $minutes_asleep, $minutes_awake, $efficiency);
            $stmt->execute();
            
            if ($stmt->error) {
                error_log("Error inserting sleep data for $date: " . $stmt->error);
            }
        }
    }
}

echo json_encode([
    "status" => "success", 
    "message" => "Stored Fitbit data from $start_date to $end_date",
    "resources_processed" => count($all_data)
]);

$conn->close();
?>