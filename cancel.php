<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/twixo_brand.php';

// Initialize session for security
session_start();

// Clear the session data
$_SESSION = array();

// If session cookie is used, destroy it
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Get referrer URL if available
$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
$backUrl = !empty($referrer) ? $referrer : 'index.php';

// Prepare message for display
$message = "Payment has been cancelled.";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Cancelled — Twixo</title>
    <meta name="generator" content="Twixo">
    <meta name="author" content="Twixo — https://twixo.sweez.xyz">
    <link rel="icon" href="img/twixo.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #00A3A3;
            --primary-dark: #008F8F;
            --secondary-color: #F8F9FA;
            --text-color: #333333;
            --text-light: #666666;
            --border-color: #E5E5E5;
            --error-color: #dc3545;
            --border-radius: 12px;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: var(--text-color);
            line-height: 1.6;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            justify-content: center;
        }
        
        .container {
            max-width: 500px;
            width: 100%;
            margin: 0 auto;
            padding: 20px;
        }
        
        .card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 40px;
            text-align: center;
        }
        
        .icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: rgba(220, 53, 69, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
        }
        
        .icon svg {
            stroke: var(--error-color);
        }
        
        h1 {
            color: var(--error-color);
            font-size: 24px;
            margin-bottom: 16px;
            font-weight: 600;
        }
        
        p {
            color: var(--text-light);
            margin-bottom: 32px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 16px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: var(--primary-dark);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>
            </div>
            <h1>Payment Cancelled</h1>
            <p><?php echo $message; ?></p>
            <div class="twixo-brand-badge">
                <img src="img/twixo.png" alt="Twixo">
                <span>Powered by Twixo</span>
            </div>
        </div>
    </div>
    
    <?php twixo_brand_render(); ?>
    <script>
        // Auto redirect after 5 seconds
        setTimeout(function() {
            window.location.href = 'index.php';
        }, 5000);
    </script>
</body>
</html>
