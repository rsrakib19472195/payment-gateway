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

// Check if payment method is provided
if (!isset($_GET['method'])) {
    header("Location: index.php");
    exit;
}

$method = sanitize($_GET['method']);

// Validate method
if (!in_array($method, ['bKash', 'Nagad', 'Rocket', 'Upay'])) {
    header("Location: index.php");
    exit;
}

// Check if payment data exists in session
if (!isset($_SESSION['payment_data'])) {
    header("Location: index.php");
    exit;
}

// Get payment data from session
$paymentData = $_SESSION['payment_data'];
$amount = isset($paymentData['amount']) ? $paymentData['amount'] : '0.00';
$name = isset($paymentData['name']) ? $paymentData['name'] : 'Customer';

// Handle verification submission via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Validate CSRF token
    $csrfToken = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!$csrfToken || $csrfToken !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'Security error: Invalid request']);
        exit;
    }
    
    $transactionId = isset($_POST['transaction_id']) ? sanitize($_POST['transaction_id']) : '';
    
    if (empty($transactionId)) {
        echo json_encode(['success' => false, 'message' => 'Transaction ID is required']);
        exit;
    }
    
            // Call the callback API for verification
        try {
            // Prepare data for callback API
            $callbackData = [
                'transaction_id' => $transactionId,
                'uid' => $paymentData['uid'],
                'number' => $paymentData['number'],
                'amount' => $paymentData['amount'],
                'name' => $paymentData['name'],
                'payment_method' => $method,
                'status' => '1', // Assuming successful payment
                'gateway' => $method
            ];
            
            // Log the data being sent for debugging
            error_log('Sending to callback API: ' . json_encode($callbackData));
            
            // Make request to callback API
            $ch = curl_init(CALLBACK_API_URL);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($callbackData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            
            // Execute the request
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            // Check for cURL errors
            if (curl_errno($ch)) {
                $curlError = curl_error($ch);
                curl_close($ch);
                error_log('cURL Error: ' . $curlError);
                throw new Exception('cURL Error: ' . $curlError);
            }
            
            curl_close($ch);
            
            // Log the response for debugging
            error_log('Callback API Response: ' . $response);
            error_log('Callback API HTTP Code: ' . $httpCode);
        
        // Process the response
        if ($httpCode >= 200 && $httpCode < 300) {
            $apiResponse = json_decode($response, true);
            if (!$apiResponse) {
                $apiResponse = [
                    'success' => false,
                    'message' => 'Invalid response from callback API'
                ];
            }
            
            // Check if callback was successful
            if (isset($apiResponse['status']) && $apiResponse['status'] === true) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Payment verified successfully',
                    'reference' => $transactionId
                ]);
            } else {
                // Show the actual message from the callback API response
                $errorMessage = $apiResponse['message'] ?? 'Payment verification failed';
                echo json_encode([
                    'success' => false,
                    'message' => $errorMessage
                ]);
            }
        } else {
            // For HTTP errors, try to parse the response for error details
            $errorResponse = json_decode($response, true);
            if ($errorResponse && isset($errorResponse['message'])) {
                echo json_encode([
                    'success' => false,
                    'message' => $errorResponse['message']
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Callback API error: ' . $httpCode
                ]);
            }
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error connecting to callback API: ' . $e->getMessage()
        ]);
    }
    
    exit;
}

// Get method details
$methodName = '';
$methodNumber = '';
$methodInstructions = [];

switch($method) {
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
    case 'Upay':
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
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify <?php echo htmlspecialchars($methodName); ?> — Twixo</title>
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

        .payment-logo {
            position: absolute;
            top: 16px;
            right: 16px;
            height: 32px;
            width: auto;
            object-fit: contain;
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
        
        .verification-steps {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 16px;
            box-shadow: var(--box-shadow);
        }
        
        .step {
            display: flex;
            align-items: flex-start;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .step:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .step-number {
            width: 24px;
            height: 24px;
            background-color: rgba(0, 163, 163, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-weight: 600;
            margin-right: 12px;
            flex-shrink: 0;
            font-size: 13px;
        }
        
        .step-content {
            flex: 1;
            font-size: 13px;
            line-height: 1.4;
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
        
        /* Custom Error Popup */
        .error-popup {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            animation: fadeIn 0.3s ease-out;
        }
        
        .error-popup.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .error-popup-content {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            max-width: 400px;
            width: 90%;
            animation: slideIn 0.3s ease-out;
        }
        
        .error-popup-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px 16px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .error-popup-header h3 {
            margin: 0;
            color: var(--error-color);
            font-size: 18px;
            font-weight: 600;
        }
        
        .error-popup-close {
            background: none;
            border: none;
            font-size: 24px;
            color: var(--text-light);
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s;
        }
        
        .error-popup-close:hover {
            background-color: var(--border-color);
            color: var(--text-color);
        }
        
        .error-popup-body {
            padding: 20px 24px;
        }
        
        .error-popup-body p {
            margin: 0;
            color: var(--text-color);
            line-height: 1.5;
            white-space: pre-line;
            word-wrap: break-word;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .error-popup-footer {
            padding: 16px 24px 20px;
            border-top: 1px solid var(--border-color);
            text-align: right;
        }
        
        .error-popup-btn {
            background-color: var(--error-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .error-popup-btn:hover {
            background-color: #c82333;
            transform: translateY(-1px);
        }
        
        /* Custom Success Popup */
        .success-popup {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            animation: fadeIn 0.3s ease-out;
        }
        
        .success-popup.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .success-popup-content {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            max-width: 400px;
            width: 90%;
            animation: slideIn 0.3s ease-out;
        }
        
        .success-popup-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px 16px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .success-popup-header h3 {
            margin: 0;
            color: var(--success-color);
            font-size: 18px;
            font-weight: 600;
        }
        
        .success-popup-close {
            background: none;
            border: none;
            font-size: 24px;
            color: var(--text-light);
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s;
        }
        
        .success-popup-close:hover {
            background-color: var(--border-color);
            color: var(--text-color);
        }
        
        .success-popup-body {
            padding: 32px 24px;
            text-align: center;
        }
        
        .success-popup-body .success-icon {
            width: 64px;
            height: 64px;
            background-color: var(--success-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            animation: scaleIn 0.5s ease-out;
        }
        
        .success-popup-body .success-icon svg {
            width: 32px;
            height: 32px;
            color: white;
        }
        
        .success-popup-body h4 {
            margin: 0 0 12px;
            color: var(--success-color);
            font-size: 24px;
            font-weight: 600;
            animation: slideUp 0.5s ease-out 0.2s both;
        }
        
        .success-popup-body p {
            margin: 0;
            color: var(--text-color);
            line-height: 1.5;
            animation: slideUp 0.5s ease-out 0.4s both;
        }
        
        .success-popup-footer {
            padding: 16px 24px 20px;
            border-top: 1px solid var(--border-color);
            text-align: right;
        }
        
        .success-popup-btn {
            background-color: var(--success-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .success-popup-btn:hover {
            background-color: #218838;
            transform: translateY(-1px);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
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
            
            .verification-steps {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="javascript:void(0)" onclick="goBack()" class="close-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
            </a>
            <a href="<?php echo htmlspecialchars(TWIXO_URL); ?>" target="_blank" rel="noopener noreferrer" style="margin-left:auto;display:flex;align-items:center;gap:6px;text-decoration:none;color:var(--text-light);font-size:13px;font-weight:600;">
                <img src="img/twixo.png" alt="Twixo" height="28" width="28">
                <span>Twixo</span>
            </a>
        </div>
        
        <div class="payment-info">
            <img src="img/<?php echo strtolower($method); ?>.png" alt="<?php echo ucfirst($method); ?>" class="payment-logo">
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
        
        <form id="verification-form">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="transaction-id-input" style="margin-top: 20px;">
                <label for="transaction_id">Transaction ID</label>
                <input type="text" id="transaction_id" name="transaction_id" class="transaction-input" required>
            </div>
            
            <button type="submit" class="pay-button">Verify</button>
        </form>
        
        <div id="result-message" style="margin-top: 20px; padding: 15px; border-radius: 5px; display: none;"></div>
    </div>

    <div id="toast" class="toast"></div>
    
    <!-- Custom Error Popup -->
    <div id="error-popup" class="error-popup">
        <div class="error-popup-content">
            <div class="error-popup-body">
                <p id="error-message"></p>
            </div>
            <div class="error-popup-footer">
                <button class="error-popup-btn" onclick="closeErrorPopup()">OK</button>
            </div>
        </div>
    </div>
    
    <!-- Custom Success Popup -->
    <div id="success-popup" class="success-popup">
        <div class="success-popup-content">
            <div class="success-popup-header">
                <h3>Success</h3>
                <button class="success-popup-close" onclick="closeSuccessPopup()">&times;</button>
            </div>
            <div class="success-popup-body">
                <div class="success-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                    </svg>
                </div>
                <h4 id="success-title">Payment Successful!</h4>
                <p id="success-message"></p>
            </div>
            <div class="success-popup-footer">
                <button class="success-popup-btn" onclick="closeSuccessPopup()">Continue</button>
            </div>
        </div>
    </div>
    
         <script>
         // Function to handle back button
         function goBack() {
             window.history.back();
         }
         
         // Function to show toast notification
         function showToast(message, duration = 2000) {
             const toast = document.getElementById('toast');
             toast.textContent = message;
             toast.classList.add('show');
             
             setTimeout(() => {
                 toast.classList.remove('show');
             }, duration);
         }
         
         // Function to show error popup
         function showErrorPopup(message, details = null) {
             const errorPopup = document.getElementById('error-popup');
             const errorMessage = document.getElementById('error-message');
             
             let displayMessage = message;
             
             errorMessage.textContent = displayMessage;
             errorPopup.classList.add('show');
         }
         
         // Function to close error popup
         function closeErrorPopup() {
             const errorPopup = document.getElementById('error-popup');
             errorPopup.classList.remove('show');
         }
         
         // Function to show success popup
         function showSuccessPopup(title, message, onContinue = null) {
             const successPopup = document.getElementById('success-popup');
             const successTitle = document.getElementById('success-title');
             const successMessage = document.getElementById('success-message');
             
             successTitle.textContent = title;
             successMessage.textContent = message;
             
             if (onContinue) {
                 const continueBtn = successPopup.querySelector('.success-popup-btn');
                 continueBtn.onclick = () => {
                     closeSuccessPopup();
                     onContinue();
                 };
             }
             
             successPopup.classList.add('show');
         }
         
         // Function to close success popup
         function closeSuccessPopup() {
             const successPopup = document.getElementById('success-popup');
             successPopup.classList.remove('show');
         }
        
        // Function to copy text to clipboard
        function copyToClipboard(text) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            showToast('Copied: ' + text);
        }
        
        // Handle form submission
        document.getElementById('verification-form').addEventListener('submit', function(event) {
            event.preventDefault();
            
            const form = this;
            const formData = new FormData(form);
            const resultMessage = document.getElementById('result-message');
            const submitButton = form.querySelector('button[type="submit"]');
            
            // Show loading state
            submitButton.disabled = true;
            submitButton.innerHTML = '<span style="display: inline-block; width: 16px; height: 16px; border: 2px solid #fff; border-radius: 50%; border-top-color: transparent; animation: spin 1s linear infinite; margin-right: 8px;"></span> Verifying...';
            
            // Send AJAX request
            fetch('verify.php?method=<?php echo $method; ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Log the full response for debugging
                console.log('API Response:', data);
                
                if (data.success) {
                    // Show success animation on button
                    submitButton.innerHTML = 'Verified ✓';
                    submitButton.style.backgroundColor = 'var(--success-color)';
                    
                    // Show success popup
                    showSuccessPopup(
                        'Payment Successful!',
                        'Your payment has been verified successfully.\nReference: ' + data.reference,
                        () => {
                            // Redirect to success page when Continue is clicked
                            window.location.href = 'success.php?transaction_id=' + encodeURIComponent(data.reference) + '&method=<?php echo $method; ?>&session=<?php echo session_id(); ?>';
                        }
                    );
                } else {
                    // Show error in custom popup with the actual message from API
                    const errorDetails = data.details || data.error || JSON.stringify(data);
                    showErrorPopup(data.message || 'Unknown error occurred', errorDetails);
                    
                    // Reset button
                    submitButton.disabled = false;
                    submitButton.innerHTML = 'Verify';
                }
                
                resultMessage.style.display = 'block';
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorPopup('An error occurred during verification.', error.message || error.toString());
                
                // Reset button
                submitButton.disabled = false;
                submitButton.innerHTML = 'Verify';
            });
        });
        
                 // Add keypress event for transaction ID input
         document.getElementById('transaction_id').addEventListener('keypress', function(event) {
             if (event.key === 'Enter') {
                 event.preventDefault();
                 document.getElementById('verification-form').dispatchEvent(new Event('submit'));
             }
         });
         
         // Close error popup when clicking outside
         document.getElementById('error-popup').addEventListener('click', function(event) {
             if (event.target === this) {
                 closeErrorPopup();
             }
         });
         
         // Close success popup when clicking outside
         document.getElementById('success-popup').addEventListener('click', function(event) {
             if (event.target === this) {
                 closeSuccessPopup();
             }
         });
         
         // Close popups with Escape key
         document.addEventListener('keydown', function(event) {
             if (event.key === 'Escape') {
                 closeErrorPopup();
                 closeSuccessPopup();
             }
         });
    </script>
    
    <style>
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
    <?php twixo_brand_render(); ?>
</body>
</html>
