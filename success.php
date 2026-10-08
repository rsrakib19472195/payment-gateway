<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/twixo_brand.php';

// Initialize session for security
if (isset($_GET['session'])) {
    // Resume existing session if session ID is provided
    session_id($_GET['session']);
}
session_start();

// Get transaction ID from URL
$transactionId = isset($_GET['transaction_id']) ? htmlspecialchars($_GET['transaction_id']) : '';

// If no transaction ID, redirect to index
if (empty($transactionId)) {
    header("Location: index.php");
    exit;
}

// Get payment method from URL (fallback to session if not available)
$method = isset($_GET['method']) ? $_GET['method'] : '';

// Check if payment data exists in session
if (!isset($_SESSION['payment_data'])) {
    header("Location: index.php");
    exit;
}

$paymentData = $_SESSION['payment_data'];
$amount = isset($paymentData['amount']) ? $paymentData['amount'] : '0.00';
$name = isset($paymentData['name']) ? $paymentData['name'] : 'Customer';

// If method not in URL, try to get from session or use default
if (empty($method)) {
    $method = isset($paymentData['method']) ? $paymentData['method'] : 'Payment Method';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success — Twixo</title>
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
            --success-color: #28a745;
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
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            max-width: 500px;
            width: 100%;
            margin: 0 auto;
            padding: 20px;
        }
        
        .success-card {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 40px;
            text-align: center;
            box-shadow: var(--box-shadow);
            position: relative;
            overflow: hidden;
        }
        
        .success-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--success-color), var(--primary-color));
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            background-color: var(--success-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            animation: scaleIn 0.5s ease-out;
        }
        
        .success-icon svg {
            width: 40px;
            height: 40px;
            color: white;
        }
        
        .success-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--success-color);
            margin-bottom: 16px;
            animation: slideUp 0.5s ease-out 0.2s both;
        }
        
        .success-message {
            font-size: 16px;
            color: var(--text-light);
            margin-bottom: 32px;
            animation: slideUp 0.5s ease-out 0.4s both;
        }
        
        .payment-details {
            background-color: var(--secondary-color);
            border-radius: var(--border-radius);
            padding: 24px;
            margin-bottom: 32px;
            text-align: left;
            animation: slideUp 0.5s ease-out 0.6s both;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .detail-row:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 500;
            color: var(--text-light);
        }
        
        .detail-value {
            font-weight: 600;
            color: var(--text-color);
        }
        
        .amount-value {
            color: var(--primary-color);
            font-size: 20px;
        }
        
        .transaction-id {
            font-family: 'Courier New', monospace;
            background-color: white;
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            font-size: 14px;
        }
        
        .action-buttons {
            display: flex;
            gap: 16px;
            animation: slideUp 0.5s ease-out 0.8s both;
        }
        
        .btn {
            flex: 1;
            padding: 16px;
            border: none;
            border-radius: var(--border-radius);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background-color: var(--secondary-color);
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }
        
        .btn-secondary:hover {
            background-color: var(--border-color);
        }
        
        @keyframes scaleIn {
            from {
                transform: scale(0);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        @keyframes slideUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        /* Responsive adjustments */
        @media (max-width: 480px) {
            .container {
                padding: 16px;
            }
            
            .success-card {
                padding: 24px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-card">
            <div class="success-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                </svg>
            </div>
            
            <h1 class="success-title">Payment Successful!</h1>
            <p class="success-message">Your payment has been verified and processed successfully.</p>
            
            <div class="payment-details">
                <div class="detail-row">
                    <span class="detail-label">Customer Name:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($name); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Amount:</span>
                    <span class="detail-value amount-value"><?php echo number_format((float)$amount, 2); ?> BDT</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Payment Method:</span>
                    <span class="detail-value"><?php echo ucfirst(htmlspecialchars($method)); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Transaction ID:</span>
                    <span class="detail-value transaction-id"><?php echo $transactionId; ?></span>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="index.php" class="btn btn-secondary">Make Another Payment</a>
                <a href="index.php" class="btn btn-primary">Go to Home</a>
            </div>
            <div class="twixo-brand-badge">
                <img src="img/twixo.png" alt="Twixo">
                <span>Processed with Twixo</span>
            </div>
        </div>
    </div>
    <?php twixo_brand_render(); ?>
</body>
</html>
