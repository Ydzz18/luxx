<?php
/**
 * Email Functions using Gmail SMTP or PHP mail()
 * Requires PHPMailer library for SMTP
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer autoloader if using SMTP
if (getSmtpEnabled() && file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

/**
 * Initialize PHPMailer with Gmail SMTP settings
 */
function getMailer() {
    // Check if SMTP is enabled
    if (!getSmtpEnabled()) {
        return null; // Use PHP mail() instead
    }
    
    // Check if PHPMailer is available
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        error_log("PHPMailer not found. Install via: composer require phpmailer/phpmailer");
        return null;
    }
    
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = getSmtpHost();
        $mail->SMTPAuth   = true;
        $mail->Username   = getSmtpEmail();
        $mail->Password   = getSmtpPassword();
        
        // Encryption
        if (getSmtpEncryption() === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }
        
        $mail->Port = getSmtpPort();
        
        // Sender info
        $mail->setFrom(getSmtpEmail(), getSmtpFromName());
        
        // Content settings
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        
        return $mail;
    } catch (Exception $e) {
        error_log("PHPMailer initialization error: " . $e->getMessage());
        return null;
    }
}

/**
 * Send email using either SMTP or PHP mail()
 */
function sendEmail($to, $toName, $subject, $htmlBody, $altBody = '') {
    $mail = getMailer();
    
    // Use PHPMailer if SMTP is enabled and configured
    if ($mail !== null) {
        try {
            $mail->addAddress($to, $toName);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = $altBody ?: strip_tags($htmlBody);
            
            $result = $mail->send();
            if ($result) {
                error_log("Email sent via SMTP to $to: $subject");
            }
            return $result;
        } catch (Exception $e) {
            error_log("SMTP email error: " . $e->getMessage());
            return false;
        }
    }
    
    // Fallback to PHP mail()
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . getSmtpFromName() . ' <' . getSetting('site_email', 'noreply@luxstore.com') . '>'
    ];
    
    $result = mail($to, $subject, $htmlBody, implode("\r\n", $headers));
    if ($result) {
        error_log("Email sent via PHP mail() to $to: $subject");
    } else {
        error_log("Failed to send email to $to: $subject");
    }
    
    return $result;
}

/**
 * Send order confirmation email
 */
function sendOrderConfirmationEmail($order_id, $customer_email, $customer_name, $order_number, $total) {
    // Check if order emails are enabled
    if (!isOrderEmailsEnabled()) {
        error_log("Order confirmation emails are disabled in settings");
        return false;
    }
    
    $subject = "Order Confirmation - $order_number";
    $htmlBody = getOrderConfirmationTemplate($order_number, $customer_name, $total, $order_id);
    $altBody = "Thank you for your order #$order_number. Total: " . formatPrice($total);
    
    $result = sendEmail($customer_email, $customer_name, $subject, $htmlBody, $altBody);
    
}

/**
 * Send order status update email
 */
function sendOrderStatusEmail($order_id, $customer_email, $customer_name, $order_number, $status) {
    // Check if status emails are enabled
    if (!isStatusEmailsEnabled()) {
        error_log("Order status emails are disabled in settings");
        return false;
    }
    
    $status_titles = [
        'processing' => 'Order is Being Processed',
        'shipped' => 'Order Has Been Shipped',
        'delivered' => 'Order Delivered',
        'cancelled' => 'Order Cancelled'
    ];
    
    $subject_prefix = $status_titles[$status] ?? 'Order Status Update';
    $subject = "$subject_prefix - $order_number";
    $htmlBody = getOrderStatusTemplate($order_number, $customer_name, $status, $order_id);
    $altBody = "Your order #$order_number status has been updated to: $status";
    
    $result = sendEmail($customer_email, $customer_name, $subject, $htmlBody, $altBody);
    
}

/**
 * Send payment confirmation email
 */
function sendPaymentConfirmationEmail($order_id, $customer_email, $customer_name, $order_number, $amount, $payment_method) {
    if (!isOrderEmailsEnabled()) {
        return false;
    }
    
    $subject = "Payment Confirmed - $order_number";
    $htmlBody = getPaymentConfirmationTemplate($order_number, $customer_name, $amount, $payment_method);
    $altBody = "Payment confirmed for order #$order_number. Amount: " . formatPrice($amount);
    
    return sendEmail($customer_email, $customer_name, $subject, $htmlBody, $altBody);
}

/**
 * Send shipping notification email
 */
function sendShippingNotificationEmail($order_id, $customer_email, $customer_name, $order_number, $tracking_number = null) {
    if (!isStatusEmailsEnabled()) {
        return false;
    }
    
    $subject = "Your Order Has Shipped - $order_number";
    $htmlBody = getShippingNotificationTemplate($order_number, $customer_name, $tracking_number);
    $altBody = "Your order #$order_number has been shipped!" . ($tracking_number ? " Tracking: $tracking_number" : "");
    
    return sendEmail($customer_email, $customer_name, $subject, $htmlBody, $altBody);
}

/**
 * Send welcome email to new customer
 */
function sendWelcomeEmail($customer_email, $customer_name) {
    if (!isWelcomeEmailsEnabled()) {
        return false;
    }
    
    $subject = "Welcome to " . SITE_NAME . "!";
    $htmlBody = getWelcomeEmailTemplate($customer_name);
    $altBody = "Welcome to " . SITE_NAME . "! Thank you for joining us.";
    
    return sendEmail($customer_email, $customer_name, $subject, $htmlBody, $altBody);
}

/**
 * Email Templates
 */

function getOrderConfirmationTemplate($order_number, $customer_name, $total, $order_id) {
    $site_url = SITE_URL;
    $order_url = $site_url . "/orders.php?order_id=" . $order_id;
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #d4af37 0%, #b8860b 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .order-details { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
            .button { display: inline-block; background: #d4af37; color: #000; padding: 12px 30px; text-decoration: none; border-radius: 6px; margin: 20px 0; font-weight: bold; }
            .footer { text-align: center; color: #666; font-size: 12px; margin-top: 30px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>✅ Order Confirmed!</h1>
            </div>
            <div class='content'>
                <p>Hi <strong>$customer_name</strong>,</p>
                <p>Thank you for your order! We've received your order and it's being processed.</p>
                
                <div class='order-details'>
                    <h2>Order Details</h2>
                    <p><strong>Order Number:</strong> $order_number</p>
                    <p><strong>Total Amount:</strong> " . formatPrice($total) . "</p>
                </div>
                
                <p>You can track your order status at any time:</p>
                <a href='$order_url' class='button'>View Order Details</a>
                
                <p>We'll send you another email when your order ships.</p>
                
                <p>Thank you for shopping with " . SITE_NAME . "!</p>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " " . SITE_NAME . ". All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

function getOrderStatusTemplate($order_number, $customer_name, $status, $order_id) {
    $site_url = SITE_URL;
    $order_url = $site_url . "/orders.php?order_id=" . $order_id;
    
    $status_messages = [
        'processing' => ['emoji' => '⚙️', 'title' => 'Order is Being Processed', 'message' => 'Your order is now being prepared for shipment.'],
        'shipped' => ['emoji' => '📦', 'title' => 'Order Has Been Shipped', 'message' => 'Your order is on its way to you!'],
        'delivered' => ['emoji' => '✅', 'title' => 'Order Delivered', 'message' => 'Your order has been successfully delivered.'],
        'cancelled' => ['emoji' => '❌', 'title' => 'Order Cancelled', 'message' => 'Your order has been cancelled.']
    ];
    
    $status_info = $status_messages[$status] ?? ['emoji' => '📋', 'title' => 'Order Status Update', 'message' => 'Your order status has been updated.'];
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #d4af37 0%, #b8860b 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .status-box { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center; }
            .button { display: inline-block; background: #d4af37; color: #000; padding: 12px 30px; text-decoration: none; border-radius: 6px; margin: 20px 0; font-weight: bold; }
            .footer { text-align: center; color: #666; font-size: 12px; margin-top: 30px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>{$status_info['emoji']} {$status_info['title']}</h1>
            </div>
            <div class='content'>
                <p>Hi <strong>$customer_name</strong>,</p>
                
                <div class='status-box'>
                    <h2>Order #$order_number</h2>
                    <p style='font-size: 18px; color: #d4af37;'><strong>" . ucfirst($status) . "</strong></p>
                    <p>{$status_info['message']}</p>
                </div>
                
                <a href='$order_url' class='button'>View Order Details</a>
                
                <p>If you have any questions, please don't hesitate to contact us.</p>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " " . SITE_NAME . ". All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

function getPaymentConfirmationTemplate($order_number, $customer_name, $amount, $payment_method) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #d4af37 0%, #b8860b 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .payment-details { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
            .footer { text-align: center; color: #666; font-size: 12px; margin-top: 30px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>💳 Payment Confirmed</h1>
            </div>
            <div class='content'>
                <p>Hi <strong>$customer_name</strong>,</p>
                <p>Your payment has been successfully processed!</p>
                
                <div class='payment-details'>
                    <h2>Payment Details</h2>
                    <p><strong>Order Number:</strong> $order_number</p>
                    <p><strong>Amount Paid:</strong> " . formatPrice($amount) . "</p>
                    <p><strong>Payment Method:</strong> $payment_method</p>
                </div>
                
                <p>Your order will be processed shortly.</p>
                
                <p>Thank you for your business!</p>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " " . SITE_NAME . ". All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

function getShippingNotificationTemplate($order_number, $customer_name, $tracking_number) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #d4af37 0%, #b8860b 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .shipping-box { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
            .tracking { background: #f0f0f0; padding: 15px; border-radius: 6px; font-size: 18px; font-weight: bold; text-align: center; margin: 15px 0; }
            .footer { text-align: center; color: #666; font-size: 12px; margin-top: 30px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🚚 Your Order Has Shipped!</h1>
            </div>
            <div class='content'>
                <p>Hi <strong>$customer_name</strong>,</p>
                <p>Great news! Your order has been shipped and is on its way to you.</p>
                
                <div class='shipping-box'>
                    <h2>Shipping Details</h2>
                    <p><strong>Order Number:</strong> $order_number</p>
                    " . ($tracking_number ? "<p><strong>Tracking Number:</strong></p><div class='tracking'>$tracking_number</div>" : "<p>You will receive a tracking number soon.</p>") . "
                </div>
                
                <p>Your order should arrive within 3-7 business days.</p>
                
                <p>Thank you for shopping with " . SITE_NAME . "!</p>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " " . SITE_NAME . ". All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

function getWelcomeEmailTemplate($customer_name) {
    $site_url = SITE_URL;
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #d4af37 0%, #b8860b 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .button { display: inline-block; background: #d4af37; color: #000; padding: 12px 30px; text-decoration: none; border-radius: 6px; margin: 20px 0; font-weight: bold; }
            .footer { text-align: center; color: #666; font-size: 12px; margin-top: 30px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎉 Welcome to " . SITE_NAME . "!</h1>
            </div>
            <div class='content'>
                <p>Hi <strong>$customer_name</strong>,</p>
                <p>Thank you for joining " . SITE_NAME . "! We're excited to have you as part of our community.</p>
                
                <p>Here's what you can do now:</p>
                <ul>
                    <li>Browse our latest products</li>
                    <li>Add items to your wishlist</li>
                    <li>Enjoy exclusive member benefits</li>
                    <li>Track your orders easily</li>
                </ul>
                
                <a href='$site_url' class='button'>Start Shopping</a>
                
                <p>If you have any questions, feel free to contact us at " . getSetting('site_email', 'support@luxstore.com') . "</p>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " " . SITE_NAME . ". All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}