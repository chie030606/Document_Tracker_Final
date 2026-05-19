<?php
/**
 * Email System Diagnostic Test
 * 
 * This script tests:
 * 1. PHPMailer is installed
 * 2. Gmail credentials are correct
 * 3. SMTP connection works
 * 4. Email can actually be sent
 * 
 * Access at: http://localhost/Document_Tracker_Final-develop/test_email.php
 */

header('Content-Type: text/html; charset=utf-8');

echo "<h1>📧 Document Tracker - Email System Diagnostic Test</h1>";
echo "<hr>";

// Test 1: Check PHPMailer installation
echo "<h2>Test 1: PHPMailer Installation</h2>";
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "✅ vendor/autoload.php found<br>";
    require_once __DIR__ . '/vendor/autoload.php';
    
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        echo "✅ PHPMailer class loaded<br>";
    } else {
        echo "❌ PHPMailer class NOT found<br>";
        exit;
    }
} else {
    echo "❌ vendor/autoload.php NOT found<br>";
    echo "Run: composer install<br>";
    exit;
}

echo "<br>";

// Test 2: Check email_helper.php
echo "<h2>Test 2: Email Helper Module</h2>";
if (file_exists(__DIR__ . '/email_helper.php')) {
    echo "✅ email_helper.php found<br>";
    require_once __DIR__ . '/email_helper.php';
} else {
    echo "❌ email_helper.php NOT found<br>";
    exit;
}

echo "<br>";

// Test 3: Check Gmail credentials
echo "<h2>Test 3: Gmail Credentials</h2>";
echo "Gmail Address: <code>" . GMAIL_ADDRESS . "</code><br>";
echo "Gmail Display Name: <code>" . GMAIL_DISPLAY_NAME . "</code><br>";
echo "Gmail App Password: <code>***" . substr(GMAIL_APP_PASSWORD, -4) . "</code> (last 4 chars shown)<br>";

if (GMAIL_ADDRESS && GMAIL_APP_PASSWORD) {
    echo "✅ Gmail credentials are configured<br>";
} else {
    echo "❌ Gmail credentials missing<br>";
    exit;
}

echo "<br>";

// Test 4: Test email sending
echo "<h2>Test 4: Send Test Email</h2>";

// Get recipient email from form or use test
$testRecipientEmail = $_POST['recipient_email'] ?? '';
$testRecipientName = $_POST['recipient_name'] ?? 'Test User';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $testRecipientEmail) {
    echo "<p><strong>Attempting to send test email to: <code>$testRecipientEmail</code></strong></p>";
    
    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        
        echo "<p>📋 Configuring SMTP...</p>";
        $mail->SMTPDebug = 2;  // Enable verbose logging
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = GMAIL_ADDRESS;
        $mail->Password   = GMAIL_APP_PASSWORD;
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        echo "<p>✅ SMTP configured: smtp.gmail.com:587 STARTTLS</p>";
        
        echo "<p>📋 Setting email parameters...</p>";
        $mail->setFrom(GMAIL_ADDRESS, GMAIL_DISPLAY_NAME);
        $mail->addAddress($testRecipientEmail, $testRecipientName);
        $mail->Subject = 'TEST: Document Tracker Email System';
        $mail->isHTML(true);
        $mail->Body = "<h2>Email System Test Successful!</h2>"
                    . "<p>This is a test email from Document Tracker.</p>"
                    . "<p><strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . "</p>"
                    . "<p>✅ If you received this email, the email system is working correctly!</p>";
        $mail->AltBody = "Test email from Document Tracker\n"
                       . "Timestamp: " . date('Y-m-d H:i:s');
        
        echo "<p>✅ Email configured</p>";
        
        echo "<p>📋 Connecting to Gmail SMTP and sending...</p>";
        
        if ($mail->send()) {
            echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px;'>";
            echo "<h3 style='color: #155724;'>✅ EMAIL SENT SUCCESSFULLY!</h3>";
            echo "<p style='color: #155724;'>Test email was sent to: <code>$testRecipientEmail</code></p>";
            echo "<p style='color: #155724;'>Please check your inbox (and spam folder) for the email.</p>";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px;'>";
            echo "<h3 style='color: #721c24;'>❌ EMAIL SEND FAILED</h3>";
            echo "<p style='color: #721c24;'>Error: " . $mail->ErrorInfo . "</p>";
            echo "</div>";
        }
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px;'>";
        echo "<h3 style='color: #721c24;'>❌ EXCEPTION: EMAIL SEND FAILED</h3>";
        echo "<p style='color: #721c24;'>" . $e->getMessage() . "</p>";
        echo "</div>";
    }
} else {
    echo "<form method='POST'>";
    echo "<p><strong>Send a test email to verify the system works:</strong></p>";
    echo "<label>Recipient Email: <input type='email' name='recipient_email' required></label><br><br>";
    echo "<label>Recipient Name: <input type='text' name='recipient_name' value='Test User'></label><br><br>";
    echo "<button type='submit' style='padding: 10px 20px; background: #2563eb; color: white; border: none; border-radius: 5px; cursor: pointer;'>Send Test Email</button>";
    echo "</form>";
}

echo "<br><br>";

// Test 5: Gmail account requirements
echo "<h2>Test 5: Gmail Account Requirements</h2>";
echo "<p><strong>For this system to work, your Gmail account needs:</strong></p>";
echo "<ul>";
echo "<li>✅ 2-Factor Authentication (2FA) ENABLED</li>";
echo "<li>✅ App Password generated (NOT your regular password)</li>";
echo "<li>✅ Account actively accessible</li>";
echo "<li>✅ SMTP access enabled</li>";
echo "</ul>";
echo "<p><strong>To generate/check App Password:</strong></p>";
echo "<ol>";
echo "<li>Go to: <a href='https://myaccount.google.com/apppasswords' target='_blank'>https://myaccount.google.com/apppasswords</a></li>";
echo "<li>Select 'Mail' and 'Windows Computer'</li>";
echo "<li>Google will generate a 16-character password</li>";
echo "<li>Update <code>email_helper.php</code> line 20 with this password</li>";
echo "</ol>";

echo "<br><br>";

// Test 6: Check database
echo "<h2>Test 6: Database - Email Field</h2>";
require_once __DIR__ . '/app_init.php';

if ($mysqli instanceof mysqli) {
    $res = $mysqli->query("SHOW COLUMNS FROM requests LIKE 'email'");
    if ($res && $res->num_rows > 0) {
        echo "✅ Database 'requests' table has 'email' column<br>";
        $res->free();
    } else {
        echo "❌ Database 'requests' table MISSING 'email' column<br>";
        if ($res) $res->free();
    }
} else {
    echo "❌ Database connection failed<br>";
}

echo "<br><br>";

// Test 7: Check recent document requests with email
echo "<h2>Test 7: Document Requests with Email</h2>";
if ($mysqli instanceof mysqli) {
    $res = $mysqli->query("SELECT tracking_id, COALESCE(fullname, full_name) AS name, email FROM requests WHERE email IS NOT NULL AND email != '' LIMIT 5");
    if ($res && $res->num_rows > 0) {
        echo "✅ Found " . $res->num_rows . " document requests with email:<br>";
        echo "<ul>";
        while ($row = $res->fetch_assoc()) {
            echo "<li><strong>" . $row['tracking_id'] . "</strong> - " . $row['name'] . " (" . $row['email'] . ")</li>";
        }
        echo "</ul>";
        $res->free();
    } else {
        echo "⚠️ No document requests found with email<br>";
        echo "You need to create a document request with an email address first<br>";
        if ($res) $res->free();
    }
}

echo "<hr>";
echo "<p style='color: #666; font-size: 12px;'>";
echo "📝 <strong>Troubleshooting Tips:</strong><br>";
echo "• Check Gmail account: " . GMAIL_ADDRESS . "<br>";
echo "• 2FA must be enabled for App Passwords to work<br>";
echo "• If email fails, check 'Recipient Email' field for valid email<br>";
echo "• Gmail might filter automated emails to spam<br>";
echo "• Try whitelisting " . GMAIL_ADDRESS . " in your Gmail settings<br>";
echo "</p>";

?>
