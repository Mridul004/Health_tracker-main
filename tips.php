<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
        <!-- <div class="health-benefits">
            <h2>Health Benefits</h2>
            <div>
                <img src="./Images/water.jpg" alt="Drink Water" class="icon">
                <p><strong>Drinking Water:</strong> Helps maintain the balance of bodily fluids, keeps skin looking good, and energizes muscles.</p>
            </div>
            <div>
                <img src="./Images/sleep.jpeg" alt="Sleep" class="icon">
                <p><strong>Sleep:</strong> Supports cognitive function, improves mood, and helps maintain physical health.</p>
            </div>
            <div>
                <img src="./Images/food.jpeg" alt="Healthy Food" class="icon">
                <p><strong>Healthy Food:</strong> Provides essential nutrients, supports immune function, and reduces the risk of chronic diseases.</p>
            </div>
        </div> -->


        
        <div class="container">
            <nav class="navbar">
            <div class="nav-logo">Health Monitor</div>
            
                <ul class="nav-links">
                    <li><a href="dashboard.php" class="nav-item">Dashboard</a></li>
                    <li><a href="visualization.php" class="nav-item">Visualization</a></li>
                    <li><a href="tips.html" class="nav-item">Tips</a></li>
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
        <h2 style="padding-top: 20px; color: rgb(4, 34, 80);">HEALTH BENEFIT TIPS</h2>
        <div class="health-benefits">
           
            
            <div class="card">
              <img src="./Images/water.jpg" alt="Drink Water">
              <h3>Drinking Water</h3>
              <p>Helps maintain the balance of bodily fluids, keeps skin looking good, and energizes muscles.</p>
            </div>
            <div class="card">
              <img src="./Images/sleep.jpeg" alt="Sleep">
              <h3>Sleep</h3>
              <p>Supports cognitive function, improves mood, and helps maintain physical health.</p>
            </div>
            <div class="card">
              <img src="./Images/food.jpeg" alt="Healthy Food">
              <h3>Healthy Food</h3>
              <p>Provides essential nutrients, supports immune function, and reduces chronic disease risk.</p>
            </div>
            <div class="card">
                <img src="./Images/exercise.jpeg" alt="Exercise Daily">
                <h3>Exercise Daily</h3>
                <p> Exercise can significantly improve physical and mental health and boosting overall well-being.</p>
              </div>

              <p class="health-message">
                <b>Take charge of your health today!</b> 
                <br>Stay hydrated, eat nutritious foods, get enough sleep, and move your body regularly. Small daily habits can lead to big changes. 
                <br>Make wellness a priority—your mind and body will thank you. Start now, stay consistent, and enjoy a healthier, happier life.
            </p> 

          </div>

    </div>
          
          
        
</body>
</html>