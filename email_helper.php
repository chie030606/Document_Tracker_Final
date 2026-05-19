<?php
/**
 * Email Helper using PHPMailer
 * 
 * Provides reusable email functionality for the Document Tracker application
 * Uses Gmail SMTP with App Password authentication
 */

// Autoload PHPMailer
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Gmail Configuration Constants
 * Replace these with your actual Gmail credentials
 * 
 * IMPORTANT: Use Gmail App Password, not your regular password
 * Generate at: https://myaccount.google.com/apppasswords
 */
define('GMAIL_ADDRESS', 'docutracker.system@gmail.com');
define('GMAIL_APP_PASSWORD', 'gsxa qkej lfpc yhkb');
define('GMAIL_DISPLAY_NAME', 'Document Tracker System');

/**
 * Send Document Complete Email
 * 
 * @param string $toEmail       Recipient's email address
 * @param string $recipientName Recipient's full name
 * 
 * @return bool True if email sent successfully, false otherwise
 */
function sendDocumentCompleteEmail($toEmail, $recipientName) {
    try {
        error_log(">>> Starting email send process");
        error_log("    Recipient: {$recipientName} <{$toEmail}>");
        error_log("    From: " . GMAIL_ADDRESS);
        
        // Create PHPMailer instance
        $mail = new PHPMailer(true);

        // Server settings
        $mail->SMTPDebug = 0;                      // Disable debug output in production
        $mail->isSMTP();                           // Send using SMTP
        $mail->Host       = 'smtp.gmail.com';      // Set the SMTP server
        $mail->SMTPAuth   = true;                  // Enable SMTP authentication
        $mail->Username   = GMAIL_ADDRESS;         // Gmail address
        $mail->Password   = GMAIL_APP_PASSWORD;    // Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;  // Enable TLS encryption
        $mail->Port       = 587;                   // TCP port

        error_log("    SMTP configured: smtp.gmail.com:587 STARTTLS");

        // Recipients
        $mail->setFrom(GMAIL_ADDRESS, GMAIL_DISPLAY_NAME);
        $mail->addAddress($toEmail, $recipientName);

        // Email subject and body
        $mail->Subject = 'Document Request Completed';
        
        // Set email body (HTML format)
        $mail->isHTML(true);
        $mail->Body = buildDocumentCompleteEmailBody($recipientName);
        
        // Plain text alternative
        $mail->AltBody = "Dear {$recipientName},\n\n"
                        . "Your document request has been processed successfully.\n\n"
                        . "Please log in to your account to access your documents.\n\n"
                        . "Best regards,\n"
                        . "Document Tracker System";

        // Send the email
        error_log("    Sending email via SMTP...");
        $mail->send();
        
        error_log("✅ Email sent successfully!");
        return true;

    } catch (Exception $e) {
        // Log detailed error information
        error_log("❌ Email sending FAILED!");
        error_log("    Error: " . $e->getMessage());
        if (isset($mail)) {
            error_log("    PHPMailer Error: " . $mail->ErrorInfo);
            error_log("    PHPMailer Debug: " . print_r($mail->getSMTPInstance(), true));
        }
        error_log("    Troubleshooting steps:");
        error_log("    1. Check Gmail account: " . GMAIL_ADDRESS);
        error_log("    2. Verify 2FA is enabled: https://myaccount.google.com/security");
        error_log("    3. Verify App Password: https://myaccount.google.com/apppasswords");
        error_log("    4. Check recipient email is valid: {$toEmail}");
        error_log("    5. Check SMTP: telnet smtp.gmail.com 587");
        return false;
    }
}

/**
 * Build HTML email body for document completion notification
 * 
 * @param string $recipientName Recipient's full name
 * 
 * @return string HTML formatted email body
 */
function buildDocumentCompleteEmailBody($recipientName) {
    $currentYear = date('Y');
    
    $htmlBody = <<<HTML
    <html>
    <head>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background-color: #f4f4f4;
                margin: 0;
                padding: 0;
            }
            .container {
                max-width: 600px;
                margin: 20px auto;
                background-color: #ffffff;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                overflow: hidden;
            }
            .header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: #ffffff;
                padding: 30px;
                text-align: center;
            }
            .header h1 {
                margin: 0;
                font-size: 28px;
                font-weight: 600;
            }
            .content {
                padding: 30px;
                color: #333333;
                line-height: 1.6;
            }
            .content p {
                margin: 15px 0;
            }
            .status-box {
                background-color: #e8f5e9;
                border-left: 4px solid #4caf50;
                padding: 15px;
                margin: 20px 0;
                border-radius: 4px;
            }
            .status-box strong {
                color: #2e7d32;
            }
            .cta-button {
                display: inline-block;
                background-color: #667eea;
                color: #ffffff;
                padding: 12px 30px;
                text-decoration: none;
                border-radius: 5px;
                margin: 20px 0;
                font-weight: 600;
            }
            .cta-button:hover {
                background-color: #5568d3;
            }
            .footer {
                background-color: #f9f9f9;
                border-top: 1px solid #eeeeee;
                padding: 20px;
                text-align: center;
                font-size: 12px;
                color: #888888;
            }
            .footer p {
                margin: 5px 0;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>✓ Document Request Completed</h1>
            </div>
            
            <div class="content">
                <p>Dear <strong>{$recipientName}</strong>,</p>
                
                <p>We are pleased to inform you that your document request has been processed successfully!</p>
                
                <div class="status-box">
                    <strong>✓ Status: Completed</strong>
                    <p>Your requested documents are now ready and available in your account.</p>
                </div>
                
                <p>You can now log in to your account and access your completed documents from the dashboard.</p>
                
                <p style="text-align: center;">
                    <a href="#" class="cta-button">View Your Documents</a>
                </p>
                
                <p>If you have any questions or need assistance, please don't hesitate to contact our support team.</p>
                
                <p>Best regards,<br>
                <strong>Document Tracker System</strong></p>
            </div>
            
            <div class="footer">
                <p>&copy; {$currentYear} Document Tracker. All rights reserved.</p>
                <p>This is an automated message, please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>
HTML;

    return $htmlBody;
}

/**
 * Generic email sending function for future extensibility
 * 
 * @param string $toEmail       Recipient's email address
 * @param string $recipientName Recipient's name
 * @param string $subject       Email subject
 * @param string $htmlBody      HTML formatted email body
 * @param string $plainTextBody Plain text alternative
 * 
 * @return bool True if email sent successfully, false otherwise
 */
function sendEmail($toEmail, $recipientName, $subject, $htmlBody, $plainTextBody = '') {
    try {
        $mail = new PHPMailer(true);

        // Server settings
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = GMAIL_ADDRESS;
        $mail->Password   = GMAIL_APP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom(GMAIL_ADDRESS, GMAIL_DISPLAY_NAME);
        $mail->addAddress($toEmail, $recipientName);

        // Email content
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body = $htmlBody;
        
        if (!empty($plainTextBody)) {
            $mail->AltBody = $plainTextBody;
        }

        // Send the email
        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}

?>
