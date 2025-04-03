<?php
session_start();
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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $name = $_POST['name'];
    $age = $_POST['age'];
    $height = $_POST['height'];
    $weight = $_POST['weight'];
    $goal = $_POST['goal'];

    $sql = "INSERT INTO users (username, email, password, name, age, height, weight, goal) VALUES ('$username', '$email', '$password', '$name', '$age', '$height', '$weight', '$goal')";

    if ($conn->query($sql) === TRUE) {
        $_SESSION['username'] = $username;
        header("Location: dashboard.php");
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

$conn->close();
?>
