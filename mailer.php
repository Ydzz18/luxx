<?php
/**
 * LuxStore Email Notification System
 * Sends order confirmations, status updates, etc.
 */

require_once 'config.php';

class Mailer {
    private $siteName;
    private $siteEmail;
    private $sitePhone;
    private $currency;
    
    public function __construct() {
        $this->siteName = getSetting('site_name', 'LuxStore');
        $this->siteEmail = getSetting('site_email', 'luxstore@gmail.com');
        $this->sitePhone = getSetting('site_phone', '+63 912 345 6789');
        $this->currency = getSetting('currency', '₱');
    }
    
    /**
     * Send email using Gmail SMTP or PHP mail()
     */
    private function send($to, $subject, $htmlBody) {
        if ($this->isSmtpEnabled()) {
            return $this->sendViaSMTP($to, $subject, $htmlBody);
        }
        
        return $this->sendViaPhpMail($to, $subject, $htmlBody);
    }
    
    /**
     * Check if SMTP is enabled
     */
    private function isSmtpEnabled() {
        return getSetting('email_method', 'php_mail') === 'smtp';
    }
    
    /**
     * Get SMTP configuration
     */
    private function getSmtpConfig() {
        return [
            'host' => getSetting('smtp_host', 'smtp.gmail.com'),
            'port' => getSetting('smtp_port', '587'),
            'encryption' => getSetting('smtp_encryption', 'tls'),
            'email' => getSetting('gmail_sender_email', ''),
            'password' => getSetting('gmail_app_password', ''),
            'from_name' => getSetting('site_name', 'LuxStore')
        ];
    }
    
    /**
     * Send email via Gmail SMTP
     */
    private function sendViaSMTP($to, $subject, $htmlBody) {
        try {
            $config = $this->getSmtpConfig();
            
            // Validate SMTP config
            if (empty($config['email']) || empty($config['password'])) {
                throw new Exception('SMTP email or password not configured');
            }
            
            // Build SMTP connection string
            $host = $config['host'];
            $port = $config['port'];
            $encryption = $config['encryption']; // 'tls' or 'ssl'
            $email = $config['email'];
            $password = $config['password'];
            $fromName = $config['from_name'];
            
            // Open socket connection
            $socket = fsockopen(
                ($encryption === 'ssl' ? 'ssl://' : '') . $host,
                $port,
                $errno,
                $errstr,
                10
            );
            
            if (!$socket) {
                throw new Exception("Failed to connect to SMTP: $errstr ($errno)");
            }
            
            // Read greeting
            $this->getResponse($socket);
            
            // Send EHLO
            $this->sendCommand($socket, "EHLO " . $_SERVER['SERVER_NAME']);
            $this->getResponse($socket);
            
            // Start TLS if needed
            if ($encryption === 'tls') {
                $this->sendCommand($socket, "STARTTLS");
                $this->getResponse($socket);
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                
                // Send EHLO again after TLS
                $this->sendCommand($socket, "EHLO " . $_SERVER['SERVER_NAME']);
                $this->getResponse($socket);
            }
            
            // Authenticate
            $this->sendCommand($socket, "AUTH LOGIN");
            $this->getResponse($socket);
            
            $this->sendCommand($socket, base64_encode($email));
            $this->getResponse($socket);
            
            $this->sendCommand($socket, base64_encode($password));
            $this->getResponse($socket);
            
            // Send email
            $this->sendCommand($socket, "MAIL FROM:<" . $email . ">");
            $this->getResponse($socket);
            
            $this->sendCommand($socket, "RCPT TO:<" . $to . ">");
            $this->getResponse($socket);
            
            $this->sendCommand($socket, "DATA");
            $this->getResponse($socket);
            
            // Build email
            $message = "From: " . $fromName . " <" . $email . ">\r\n";
            $message .= "To: " . $to . "\r\n";
            $message .= "Subject: " . $subject . "\r\n";
            $message .= "MIME-Version: 1.0\r\n";
            $message .= "Content-type: text/html; charset=UTF-8\r\n";
            $message .= "Reply-To: " . $email . "\r\n";
            $message .= "X-Mailer: LuxStore SMTP\r\n";
            $message .= "\r\n";
            $message .= $htmlBody . "\r\n\r\n";
            
            fwrite($socket, $message . "\r\n.\r\n");
            $this->getResponse($socket);
            
            // Close connection
            $this->sendCommand($socket, "QUIT");
            fclose($socket);
            
            return true;
        } catch (Exception $e) {
            // Log SMTP error and fall back to PHP mail
            error_log("SMTP Error: " . $e->getMessage());
            return $this->sendViaPhpMail($to, $subject, $htmlBody);
        }
    }
    
    /**
     * Send email using PHP mail() function
     */
    private function sendViaPhpMail($to, $subject, $htmlBody) {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $this->siteName . ' <' . $this->siteEmail . '>',
            'Reply-To: ' . $this->siteEmail,
            'X-Mailer: PHP/' . phpversion()
        ];
        
        return mail($to, $subject, $htmlBody, implode("\r\n", $headers));
    }
    
    /**
     * Send SMTP command
     */
    private function sendCommand($socket, $cmd) {
        fwrite($socket, $cmd . "\r\n");
    }
    
    /**
     * Get SMTP response
     */
    private function getResponse($socket) {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        
        // Check for errors (response codes starting with 5 or 4)
        $code = substr($response, 0, 3);
        if ($code[0] === '4' || $code[0] === '5') {
            throw new Exception("SMTP Error: " . trim($response));
        }
        
        return $response;
    }
    
    /**
     * Log email to file (for testing without actual email)
     */
    private function logEmail($to, $subject, $body) {
        $logDir = 'logs/';
        if (!is_dir($logDir)) mkdir($logDir, 0755, true);
        
        $log = "=== EMAIL LOG ===\n";
        $log .= "Date: " . date('Y-m-d H:i:s') . "\n";
        $log .= "To: $to\n";
        $log .= "Subject: $subject\n";
        $log .= "Body:\n$body\n";
        $log .= "=================\n\n";
        
        file_put_contents($logDir . 'emails.log', $log, FILE_APPEND);
        return true;
    }
    
    /**
     * Get email template wrapper
     */
    public function getTemplate($content) {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
        </head>
        <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f5f5f5;">
            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 20px;">
                <tr>
                    <td align="center">
                        <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                            <!-- Header -->
                            <tr>
                                <td style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); padding: 30px; text-align: center;">
                                    <h1 style="color: #d4af37; margin: 0; font-size: 28px;">👑 ' . $this->siteName . '</h1>
                                    <p style="color: #888; margin: 10px 0 0 0;">Premium Luxury E-Commerce</p>
                                </td>
                            </tr>
                            <!-- Content -->
                            <tr>
                                <td style="padding: 30px;">
                                    ' . $content . '
                                </td>
                            </tr>
                            <!-- Footer -->
                            <tr>
                                <td style="background-color: #1a1a2e; padding: 20px; text-align: center;">
                                    <p style="color: #888; margin: 0; font-size: 14px;">
                                        📞 ' . $this->sitePhone . ' | ✉️ ' . $this->siteEmail . '
                                    </p>
                                    <p style="color: #666; margin: 10px 0 0 0; font-size: 12px;">
                                        © ' . date('Y') . ' ' . $this->siteName . '. All Rights Reserved.
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>';
    }
    
    /**
     * Send order confirmation to customer
     */
    public function sendOrderConfirmation($order, $items) {
        $itemsHtml = '';
        foreach ($items as $item) {
            $itemsHtml .= '
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #eee;">' . htmlspecialchars($item['product_name']) . '</td>
                <td style="padding: 10px; border-bottom: 1px solid #eee; text-align: center;">' . $item['quantity'] . '</td>
                <td style="padding: 10px; border-bottom: 1px solid #eee; text-align: right;">' . $this->currency . number_format($item['total'], 2) . '</td>
            </tr>';
        }
        
        $content = '
        <h2 style="color: #1a1a2e; margin-top: 0;">Thank You for Your Order! 🎉</h2>
        <p style="color: #666; line-height: 1.6;">
            Hi ' . htmlspecialchars($order['shipping_name']) . ',<br><br>
            Your order has been received and is being processed. Here are your order details:
        </p>
        
        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td><strong>Order Number:</strong></td>
                    <td style="text-align: right; color: #d4af37; font-weight: bold;">' . $order['order_number'] . '</td>
                </tr>
                <tr>
                    <td><strong>Order Date:</strong></td>
                    <td style="text-align: right;">' . date('F j, Y', strtotime($order['created_at'])) . '</td>
                </tr>
                <tr>
                    <td><strong>Payment Method:</strong></td>
                    <td style="text-align: right;">' . ucfirst($order['payment_method']) . '</td>
                </tr>
            </table>
        </div>
        
        <h3 style="color: #1a1a2e; border-bottom: 2px solid #d4af37; padding-bottom: 10px;">Items Ordered</h3>
        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 20px;">
            <tr style="background: #f8f9fa;">
                <th style="padding: 10px; text-align: left;">Product</th>
                <th style="padding: 10px; text-align: center;">Qty</th>
                <th style="padding: 10px; text-align: right;">Total</th>
            </tr>
            ' . $itemsHtml . '
        </table>
        
        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 20px;">
            <tr>
                <td style="padding: 8px 0;"><strong>Subtotal:</strong></td>
                <td style="padding: 8px 0; text-align: right;">' . $this->currency . number_format($order['subtotal'], 2) . '</td>
            </tr>
            <tr>
                <td style="padding: 8px 0;"><strong>Shipping:</strong></td>
                <td style="padding: 8px 0; text-align: right;">' . ($order['shipping_fee'] == 0 ? 'FREE' : $this->currency . number_format($order['shipping_fee'], 2)) . '</td>
            </tr>
            <tr style="font-size: 18px; color: #d4af37;">
                <td style="padding: 15px 0; border-top: 2px solid #d4af37;"><strong>Total:</strong></td>
                <td style="padding: 15px 0; border-top: 2px solid #d4af37; text-align: right;"><strong>' . $this->currency . number_format($order['total'], 2) . '</strong></td>
            </tr>
        </table>
        
        <h3 style="color: #1a1a2e; border-bottom: 2px solid #d4af37; padding-bottom: 10px;">Shipping Address</h3>
        <p style="color: #666; line-height: 1.6;">
            ' . htmlspecialchars($order['shipping_name']) . '<br>
            ' . nl2br(htmlspecialchars($order['shipping_address'])) . '<br>
            ' . htmlspecialchars($order['shipping_city']) . ' ' . htmlspecialchars($order['shipping_postal']) . '<br>
            📞 ' . htmlspecialchars($order['shipping_phone']) . '
        </p>
        
        <div style="background: linear-gradient(135deg, #d4af37 0%, #b8860b 100%); padding: 20px; border-radius: 8px; text-align: center; margin-top: 30px;">
            <p style="color: #000; margin: 0; font-weight: bold;">Need Help?</p>
            <p style="color: #000; margin: 10px 0 0 0;">Contact us at ' . $this->siteEmail . '</p>
        </div>';
        
        $subject = "Order Confirmed - " . $order['order_number'] . " | " . $this->siteName;
        
        return $this->send($order['shipping_email'], $subject, $this->getTemplate($content));
    }
    
    /**
     * Send order notification to admin
     */
    public function sendAdminOrderNotification($order, $items) {
        $itemsList = '';
        foreach ($items as $item) {
            $itemsList .= '• ' . $item['product_name'] . ' (x' . $item['quantity'] . ') - ' . $this->currency . number_format($item['total'], 2) . '<br>';
        }
        
        $content = '
        <h2 style="color: #1a1a2e; margin-top: 0;">🛒 New Order Received!</h2>
        
        <div style="background: #27ae60; color: #fff; padding: 15px; border-radius: 8px; text-align: center; margin-bottom: 20px;">
            <h3 style="margin: 0;">Order #' . $order['order_number'] . '</h3>
            <p style="margin: 5px 0 0 0; font-size: 24px; font-weight: bold;">' . $this->currency . number_format($order['total'], 2) . '</p>
        </div>
        
        <table width="100%" cellpadding="10" style="background: #f8f9fa; border-radius: 8px;">
            <tr>
                <td><strong>Customer:</strong></td>
                <td>' . htmlspecialchars($order['shipping_name']) . '</td>
            </tr>
            <tr>
                <td><strong>Email:</strong></td>
                <td>' . htmlspecialchars($order['shipping_email']) . '</td>
            </tr>
            <tr>
                <td><strong>Phone:</strong></td>
                <td>' . htmlspecialchars($order['shipping_phone']) . '</td>
            </tr>
            <tr>
                <td><strong>Payment:</strong></td>
                <td>' . ucfirst($order['payment_method']) . '</td>
            </tr>
            <tr>
                <td><strong>Address:</strong></td>
                <td>' . htmlspecialchars($order['shipping_address']) . ', ' . htmlspecialchars($order['shipping_city']) . '</td>
            </tr>
        </table>
        
        <h3 style="color: #1a1a2e; margin-top: 20px;">Items:</h3>
        <p style="color: #666; line-height: 1.8;">' . $itemsList . '</p>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="' . SITE_URL . '/admin/orders.php?id=' . $order['id'] . '" style="background: linear-gradient(135deg, #d4af37 0%, #b8860b 100%); color: #000; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">View Order in Admin</a>
        </div>';
        
        $subject = "🛒 New Order #" . $order['order_number'] . " - " . $this->currency . number_format($order['total'], 2);
        
        return $this->send($this->siteEmail, $subject, $this->getTemplate($content));
    }
    
    /**
     * Send order status update to customer
     */
    public function sendStatusUpdate($order, $newStatus) {
        $statusMessages = [
            'processing' => [
                'icon' => '⚙️',
                'title' => 'Your Order is Being Processed',
                'message' => 'Great news! We\'ve started preparing your order for shipment.'
            ],
            'shipped' => [
                'icon' => '🚚',
                'title' => 'Your Order Has Been Shipped!',
                'message' => 'Your order is on its way! You should receive it within 3-5 business days.'
            ],
            'delivered' => [
                'icon' => '✅',
                'title' => 'Your Order Has Been Delivered!',
                'message' => 'Your order has been delivered. We hope you love your purchase!'
            ],
            'cancelled' => [
                'icon' => '❌',
                'title' => 'Your Order Has Been Cancelled',
                'message' => 'Your order has been cancelled. If you have any questions, please contact us.'
            ]
        ];
        
        $status = $statusMessages[$newStatus] ?? [
            'icon' => '📦',
            'title' => 'Order Status Update',
            'message' => 'Your order status has been updated to: ' . ucfirst($newStatus)
        ];
        
        $content = '
        <div style="text-align: center; margin-bottom: 30px;">
            <span style="font-size: 60px;">' . $status['icon'] . '</span>
            <h2 style="color: #1a1a2e; margin: 20px 0 10px 0;">' . $status['title'] . '</h2>
            <p style="color: #666;">' . $status['message'] . '</p>
        </div>
        
        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
            <table width="100%">
                <tr>
                    <td><strong>Order Number:</strong></td>
                    <td style="text-align: right; color: #d4af37;">' . $order['order_number'] . '</td>
                </tr>
                <tr>
                    <td><strong>New Status:</strong></td>
                    <td style="text-align: right;"><span style="background: #d4af37; color: #000; padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: bold;">' . strtoupper($newStatus) . '</span></td>
                </tr>
                <tr>
                    <td><strong>Order Total:</strong></td>
                    <td style="text-align: right;">' . $this->currency . number_format($order['total'], 2) . '</td>
                </tr>
            </table>
        </div>
        
        <p style="color: #666; margin-top: 30px; text-align: center;">
            Questions about your order? Reply to this email or contact us at ' . $this->sitePhone . '
        </p>';
        
        $subject = $status['icon'] . ' ' . $status['title'] . ' - ' . $order['order_number'];
        
        return $this->send($order['shipping_email'], $subject, $this->getTemplate($content));
    }
    
    /**
     * Send welcome email to new users
     */
    public function sendWelcomeEmail($user) {
        $content = '
        <div style="text-align: center; margin-bottom: 30px;">
            <span style="font-size: 60px;">👋</span>
            <h2 style="color: #1a1a2e; margin: 20px 0 10px 0;">Welcome to ' . $this->siteName . '!</h2>
            <p style="color: #666;">Hi ' . htmlspecialchars($user['first_name']) . ', thanks for creating an account!</p>
        </div>
        
        <p style="color: #666; line-height: 1.6;">
            You now have access to:
        </p>
        <ul style="color: #666; line-height: 2;">
            <li>🛒 Faster checkout</li>
            <li>📦 Order tracking</li>
            <li>💝 Exclusive deals and promotions</li>
            <li>⭐ Wishlist and favorites</li>
        </ul>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="' . SITE_URL . '/index.html#categories" style="background: linear-gradient(135deg, #d4af37 0%, #b8860b 100%); color: #000; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">Start Shopping</a>
        </div>';
        
        $subject = "Welcome to " . $this->siteName . "! 👑";
        
        return $this->send($user['email'], $subject, $this->getTemplate($content));
    }
}
?>