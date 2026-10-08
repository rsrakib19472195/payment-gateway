<?php
// Include configuration file
require_once 'config.php';
require_once __DIR__ . '/includes/twixo_brand.php';

// Initialize session for security
if (isset($_GET['session'])) {
    // Resume existing session if session ID is provided
    session_id($_GET['session']);
}
session_start();

// Function to sanitize input data
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to generate a unique transaction ID
function generateTransactionId() {
    return 'TXN' . time() . rand(1000, 9999);
}

// Check if this is a payment page request with session ID
if (isset($_GET['session'])) {
    // Session is already initialized at the top of the file
    // Continue to show the payment page
} else if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['payment_method']) && !isset($_POST['verify_payment'])) {
    // Get JSON data from POST request
    $json = file_get_contents('php://input');
    $postData = json_decode($json, true);

    if (!$postData) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 400,
            'message' => 'Invalid JSON data'
        ]);
        exit;
    }

    // Sanitize and prepare data
    $data = [
        'uid' => isset($postData['uid']) ? sanitize($postData['uid']) : '',
        'name' => isset($postData['name']) ? sanitize($postData['name']) : '',
        'number' => isset($postData['number']) ? sanitize($postData['number']) : '0',
        'amount' => isset($postData['amount']) ? sanitize($postData['amount']) : ''
    ];
    
    // Validate required fields
    if (empty($data['uid']) || empty($data['amount']) || empty($data['name'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 400,
            'message' => 'Missing required parameters. uid, amount, and name are required.'
        ]);
        exit;
    }
    
    // Validate amount format
    if (!is_numeric($data['amount']) || floatval($data['amount']) <= 0) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 400,
            'message' => 'Invalid amount. Amount must be a positive number.'
        ]);
        exit;
    }
    
    // Validate phone number format if provided (Bangladesh format)
    if (!empty($data['number']) && !preg_match('/^01[3-9][0-9]{8}$/', $data['number'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 400,
            'message' => 'Invalid phone number format. Must be a valid Bangladesh mobile number.'
        ]);
        exit;
    }
    
    // Store data in session for security
    $_SESSION['payment_data'] = $data;
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    
    // Generate invoice number
    $invoiceNumber = 'INV' . time() . rand(1000, 9999);
    $_SESSION['invoice_number'] = $invoiceNumber;
    
    // Generate and return payment URL
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $uri = rtrim(dirname($_SERVER['PHP_SELF']), '/');
    $paymentUrl = $protocol . $host . $uri . '/index.php?session=' . session_id();
    
    // Return payment URL and session data as JSON
    header('Content-Type: application/json');
    echo json_encode([
                 'status' => 200,
         'payment_url' => $paymentUrl,
         'session_id' => session_id(),
         'invoice_number' => $invoiceNumber
    ]);
    exit;
}

// Handle payment method selection
if (isset($_POST['payment_method'])) {
    $paymentMethod = sanitize($_POST['payment_method']);
    $csrfToken = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    
    // Verify CSRF token
    if (!$csrfToken || $csrfToken !== $_SESSION['csrf_token']) {
        die("Security error: Invalid request");
    }
    
    // Redirect to verification page
    header("Location: verify.php?method=$paymentMethod&session=" . session_id());
    exit;
}

// Handle verification submission
if (isset($_POST['verify_payment'])) {
    $transactionId = sanitize($_POST['transaction_id']);
    $paymentMethod = sanitize($_POST['payment_method']);
    $csrfToken = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    
    // Verify CSRF token
    if (!$csrfToken || $csrfToken !== $_SESSION['csrf_token']) {
        die("Security error: Invalid request");
    }
    
    // Get payment data from session
    $paymentData = $_SESSION['payment_data'];
    
    // Prepare data for API call
    $apiData = [
        'uid' => $paymentData['uid'],
        'number' => $paymentData['number'],
        'amount' => $paymentData['amount'],
        'name' => $paymentData['name'],
        'payment_method' => $paymentMethod,
        'transaction_id' => $transactionId
    ];
    
    // If payment_url is provided, use it for verification
    if (!empty($paymentData['payment_url'])) {
        try {
            // Set up cURL request to the payment URL
            $ch = curl_init($paymentData['payment_url']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($apiData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            
            // Execute the request
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            // Process the response
            if ($httpCode >= 200 && $httpCode < 300) {
                $apiResponse = json_decode($response, true);
                if (!$apiResponse) {
                    $apiResponse = [
                        'success' => false,
                        'message' => 'Invalid response from payment server'
                    ];
                }
            } else {
                $apiResponse = [
                    'success' => false,
                    'message' => 'Payment server error: ' . $httpCode
                ];
            }
        } catch (Exception $e) {
            $apiResponse = [
                'success' => false,
                'message' => 'Error connecting to payment server: ' . $e->getMessage()
            ];
        }
    } else {
        // Mock API call (when payment_url is not provided)
        $apiResponse = [
            'success' => true,
            'message' => 'Payment verified successfully',
            'reference' => generateTransactionId()
        ];
    }
    
    // Display result
    echo json_encode($apiResponse);
    exit;
}

// Get payment data from session
$paymentData = isset($_SESSION['payment_data']) ? $_SESSION['payment_data'] : null;
$amount = isset($paymentData['amount']) ? $paymentData['amount'] : '0.00';
$name = isset($paymentData['name']) ? $paymentData['name'] : 'Customer';
$invoiceNumber = isset($_SESSION['invoice_number']) ? $_SESSION['invoice_number'] : 'N/A';

// Check if we're showing the verification page
$showVerification = isset($_GET['method']) ? sanitize($_GET['method']) : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(APP_NAME); ?> — <?php echo htmlspecialchars(APP_TAGLINE); ?></title>
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
        }
        
        .container {
            max-width: 500px;
            width: 100%;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            display: flex;
            align-items: center;
            margin-bottom: 24px;
        }
        
        .close-btn {
            color: var(--primary-color);
            text-decoration: none;
            margin-right: 20px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: rgba(0, 163, 163, 0.1);
        }
        
        .close-btn:hover {
            transform: translateX(-3px);
            background-color: rgba(0, 163, 163, 0.2);
        }
        
        .close-btn svg {
            stroke: var(--primary-color);
        }
        
        .payment-info {
            background-color: white;
            padding: 16px;
            border-radius: var(--border-radius);
            margin-bottom: 16px;
            box-shadow: var(--box-shadow);
            position: relative;
        }
        
        .payment-info h2 {
            font-size: 16px;
            font-weight: 500;
            color: var(--text-light);
            margin: 0 0 4px 0;
        }
        
        .amount {
            color: var(--primary-color);
            font-size: 28px;
            font-weight: 700;
            margin: 0;
            line-height: 1.2;
        }
        
        .invoice {
            color: var(--text-light);
            font-size: 13px;
            margin-top: 4px;
        }
        
        .tabs {
            display: flex;
            border-bottom: 2px solid var(--border-color);
            margin-bottom: 24px;
        }
        
        .tab {
            padding: 12px 20px;
            cursor: pointer;
            color: var(--text-light);
            font-weight: 600;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .tab.active {
            color: var(--primary-color);
        }
        
        .tab.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: var(--primary-color);
            border-radius: 3px 3px 0 0;
        }
        
        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 16px;
        }
        
        .payment-method {
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 16px;
            text-align: center;
            cursor: pointer;
            background-color: white;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 120px;
            text-decoration: none;
        }
        
        .payment-method:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-color);
        }
        
        .payment-method img {
            height: 50px;
            margin-bottom: 12px;
            transition: transform 0.2s;
        }
        
        .payment-method:hover img {
            transform: scale(1.05);
        }
        
        .payment-method-name {
            font-size: 14px;
            color: var(--text-color);
            font-weight: 500;
        }
        
        .pay-button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            padding: 16px;
            width: 100%;
            font-size: 16px;
            font-weight: 600;
            margin-top: 24px;
            cursor: pointer;
            transition: background-color 0.3s;
            box-shadow: 0 4px 8px rgba(0, 163, 163, 0.2);
        }
        
        .pay-button:hover {
            background-color: var(--primary-dark);
        }
        
        .security-note {
            text-align: center;
            color: var(--text-light);
            margin-top: 24px;
            font-size: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .security-note::before {
            content: '🔒';
            margin-right: 6px;
        }
        
        .verification-steps {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 24px;
            box-shadow: var(--box-shadow);
        }
        
        .step {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .step:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .step-number {
            width: 32px;
            height: 32px;
            background-color: rgba(0, 163, 163, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-weight: 600;
            margin-right: 16px;
            flex-shrink: 0;
        }
        
        .step-content {
            flex: 1;
        }
        
        .highlight {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .copy-btn {
            background-color: rgba(0, 163, 163, 0.1);
            color: var(--primary-color);
            border: none;
            border-radius: 4px;
            padding: 4px 12px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
            margin-left: 8px;
        }
        
        .copy-btn:hover {
            background-color: rgba(0, 163, 163, 0.2);
        }
        
        .transaction-input {
            width: 100%;
            padding: 16px;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            margin-top: 12px;
            font-size: 16px;
            transition: border-color 0.3s;
            font-family: 'Poppins', sans-serif;
        }
        
        .transaction-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 163, 163, 0.1);
        }
        
        .hidden {
            display: none;
        }
        
        /* Responsive adjustments */
        @media (max-width: 480px) {
            .container {
                padding: 16px;
            }
            
            .payment-info {
                padding: 20px;
            }
            
            .amount {
                font-size: 28px;
            }
            
            .payment-methods {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                gap: 12px;
            }
            
            .payment-method {
                padding: 12px;
                height: 100px;
            }
            
            .payment-method img {
                height: 40px;
            }
            
            .verification-steps {
                padding: 20px;
            }
        }
        
        /* Toast notification */
        .toast {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #333;
            color: white;
            padding: 12px 20px;
            border-radius: 4px;
            font-size: 14px;
            opacity: 0;
            transition: opacity 0.3s;
            z-index: 1000;
        }
        
        .toast.show {
            opacity: 1;
        }
        
        /* Modal Dialog Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .modal.show {
            opacity: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background-color: #fff;
            margin: 20px;
            max-width: 400px;
            width: 100%;
            border-radius: var(--border-radius);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s ease;
            overflow: hidden;
        }
        
        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .modal-header h3 {
            margin: 0;
            color: var(--text-color);
            font-size: 18px;
            font-weight: 600;
        }
        
        .modal-body {
            padding: 20px;
            text-align: center;
        }
        
        .modal-icon {
            margin: 20px auto;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: rgba(220, 53, 69, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-icon svg {
            stroke: #dc3545;
        }
        
        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .btn-secondary {
            padding: 10px 16px;
            background-color: #f8f9fa;
            color: var(--text-color);
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .btn-secondary:hover {
            background-color: #e9ecef;
        }
        
        .btn-danger {
            padding: 10px 16px;
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!$showVerification): ?>
        <!-- Payment Method Selection Page -->
        <div class="header">
            <a href="javascript:void(0)" onclick="confirmCancel()" class="close-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </a>
            <a href="<?php echo htmlspecialchars(TWIXO_URL); ?>" target="_blank" rel="noopener noreferrer" style="margin-left:auto;display:flex;align-items:center;gap:6px;text-decoration:none;color:var(--text-light);font-size:13px;font-weight:600;">
                <img src="img/twixo.png" alt="Twixo" height="28" width="28">
                <span>Twixo</span>
            </a>
        </div>
        
        <div class="payment-info">
            <h2><?php echo htmlspecialchars($name); ?></h2>
            <div class="amount"><?php echo number_format((float)$amount, 2); ?> BDT</div>
            <div class="invoice">Invoice: <?php echo htmlspecialchars($invoiceNumber); ?></div>
        </div>
        
        <div class="tabs">
            <div class="tab active">Mobile Banking</div>
        </div>
        
        <div class="payment-methods">
            <a href="verify.php?method=bKash&session=<?php echo session_id(); ?>" class="payment-method">
                <img src="img/bkash.png" alt="bKash">
                <div class="payment-method-name">বিকাশ</div>
            </a>
            
            <a href="verify.php?method=Nagad&session=<?php echo session_id(); ?>" class="payment-method">
                <img src="img/nagad.png" alt="Nagad">
                <div class="payment-method-name">নগদ</div>
            </a>
            
            <a href="verify.php?method=Rocket&session=<?php echo session_id(); ?>" class="payment-method">
                <img src="img/rocket.png" alt="Rocket">
                <div class="payment-method-name">রকেট</div>
            </a>
            
            <a href="verify.php?method=Upay&session=<?php echo session_id(); ?>" class="payment-method">
                <img src="img/upay.png" alt="Upay">
                <div class="payment-method-name">উপায়</div>
            </a>
        </div>
        
        <div class="security-note">
            Your payment is secured with 256-bit encryption
        </div>
        
        <?php else: ?>
        <!-- Verification Page -->
        <?php
        // Get payment method details
        $methodName = '';
        $methodNumber = '';
        $methodInstructions = [];
        
        switch($showVerification) {
            case 'bKash':
                $methodName = 'bKash';
                $methodNumber = BKASH_NUMBER;
                $methodInstructions = [
                    'Dial *247# or open the bKash app.',
                    'Choose: Send Money',
                    'Enter the Number: ' . $methodNumber,
                    'Enter the Amount: ' . number_format((float)$amount, 2) . ' BDT',
                    'Now enter your bKash PIN to confirm.',
                    'Put the Transaction ID in the box below and press Verify'
                ];
                break;
            case 'Nagad':
                $methodName = 'Nagad';
                $methodNumber = NAGAD_NUMBER;
                $methodInstructions = [
                    'Open the Nagad app.',
                    'Choose: Send Money',
                    'Enter the Number: ' . $methodNumber,
                    'Enter the Amount: ' . number_format((float)$amount, 2) . ' BDT',
                    'Now enter your Nagad PIN to confirm.',
                    'Put the Transaction ID in the box below and press Verify'
                ];
                break;
            case 'Rocket':
                $methodName = 'Rocket';
                $methodNumber = ROCKET_NUMBER;
                $methodInstructions = [
                    'Dial *322# from your Rocket registered number.',
                    'Choose: Send Money',
                    'Enter the Number: ' . $methodNumber,
                    'Enter the Amount: ' . number_format((float)$amount, 2) . ' BDT',
                    'Now enter your Rocket PIN to confirm.',
                    'Put the Transaction ID in the box below and press Verify'
                ];
                break;
            case 'upay':
                $methodName = 'Upay';
                $methodNumber = UPAY_NUMBER;
                $methodInstructions = [
                    'Open the Upay app.',
                    'Choose: Send Money',
                    'Enter the Number: ' . $methodNumber,
                    'Enter the Amount: ' . number_format((float)$amount, 2) . ' BDT',
                    'Now enter your Upay PIN to confirm.',
                    'Put the Transaction ID in the box below and press Verify'
                ];
                break;
            default:
                header("Location: index.php");
                exit;
        }
        ?>
        
        <div class="header">
            <a href="index.php" class="close-btn">Ã—</a>
            <img src="img/<?php echo strtolower($showVerification); ?>.png" alt="<?php echo $methodName; ?>" height="40">
        </div>
        
        <div class="payment-info">
            <h2><?php echo htmlspecialchars($name); ?></h2>
            <div class="amount"><?php echo number_format((float)$amount, 2); ?> BDT</div>
        </div>
        
        <div class="verification-steps">
            <?php foreach($methodInstructions as $index => $instruction): ?>
                <div class="step">
                    <div class="step-number"><?php echo $index + 1; ?></div>
                    <div class="step-content">
                        <?php 
                        // Highlight special text
                        $instruction = preg_replace('/(Send Money|bKash|Nagad|Rocket|Upay)/', '<span class="highlight">$1</span>', $instruction);
                        
                        // Add copy button for number and amount
                        if (strpos($instruction, 'Number:') !== false) {
                            echo str_replace($methodNumber, '<span class="highlight">' . $methodNumber . '</span> <button class="copy-btn" onclick="copyToClipboard(\'' . $methodNumber . '\')">Copy</button>', $instruction);
                        } elseif (strpos($instruction, 'Amount:') !== false) {
                            echo str_replace(number_format((float)$amount, 2) . ' BDT', '<span class="highlight">' . number_format((float)$amount, 2) . ' BDT</span> <button class="copy-btn" onclick="copyToClipboard(\'' . number_format((float)$amount, 2) . '\')">Copy</button>', $instruction);
                        } else {
                            echo $instruction;
                        }
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <form method="post" id="verification-form" onsubmit="verifyPayment(event)">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="payment_method" value="<?php echo $showVerification; ?>">
            
            <div class="transaction-id-input" style="margin-top: 20px;">
                <label for="transaction_id">Transaction ID</label>
                <input type="text" id="transaction_id" name="transaction_id" class="transaction-input" required>
            </div>
            
            <button type="submit" class="pay-button" name="verify_payment">Verify</button>
        </form>
        <?php endif; ?>
    </div>

    <!-- Custom Confirmation Dialog -->
    <div id="confirmDialog" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Cancel Payment</h3>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to cancel this payment?</p>
                <div class="modal-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                </div>
            </div>
            <div class="modal-footer">
                <button id="cancelBtn" class="btn-secondary">No, Continue</button>
                <button id="confirmBtn" class="btn-danger">Yes, Cancel Payment</button>
            </div>
        </div>
    </div>

    <?php twixo_brand_render(); ?>

    <script>
        // Get modal elements
        const modal = document.getElementById('confirmDialog');
        const confirmBtn = document.getElementById('confirmBtn');
        const cancelBtn = document.getElementById('cancelBtn');
        
        // Function to handle cancel button with custom confirmation dialog
        function confirmCancel() {
            // Show the modal
            modal.classList.add('show');
            
            // Add event listeners for buttons
            confirmBtn.addEventListener('click', function() {
                window.location.href = "cancel.php";
            });
            
            cancelBtn.addEventListener('click', function() {
                closeModal();
            });
            
            // Close modal if clicked outside
            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    closeModal();
                }
            });
            
            // Add escape key listener
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    closeModal();
                }
            });
        }
        
        // Function to close the modal
        function closeModal() {
            modal.classList.remove('show');
        }
        
        // Add click event to payment methods
        document.querySelectorAll('.payment-method').forEach(method => {
            method.addEventListener('click', function() {
                // Uncheck all
                document.querySelectorAll('.payment-method input').forEach(input => {
                    input.checked = false;
                });
                
                // Check selected
                this.querySelector('input').checked = true;
            });
        });
        
        // Function to copy text to clipboard
        function copyToClipboard(text) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            alert('Copied: ' + text);
        }
        
        // Function to verify payment
        function verifyPayment(event) {
            event.preventDefault();
            
            const form = document.getElementById('verification-form');
            const formData = new FormData(form);
            
            // Send AJAX request
            fetch('index.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Payment verified successfully! Reference: ' + data.reference);
                    window.location.href = 'index.php';
                } else {
                    alert('Verification failed: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred during verification.');
            });
        }
    </script>
</body>
</html>
