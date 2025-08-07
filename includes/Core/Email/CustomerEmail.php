<?php

namespace AramexAutomation\Core\Email;

/**
 * Customer Email Handler
 */
class CustomerEmail
{
    /**
     * Send tracking information to customer
     */
    public function sendCustomerEmail($order, $tracking_number)
    {
        try {
            // Check if email was already sent for this tracking number
            $email_sent_key = 'aramex_email_sent_' . $order->get_id() . '_' . $tracking_number;
            if (get_transient($email_sent_key)) {
                error_log('Aramex Automation: Email already sent for order #' . $order->get_id() . ' with tracking #' . $tracking_number);
                return true;
            }
            
            $to = $order->get_billing_email();
            $subject = 'Your order #' . $order->get_id() . ' has been shipped';
            
            $message = $this->getEmailTemplate($order, $tracking_number);
            
            $headers = [
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . get_option('blogname') . ' <' . get_option('admin_email') . '>'
            ];
            
            $sent = wp_mail($to, $subject, $message, $headers);
            
            if ($sent) {
                $order->add_order_note('Tracking information email sent to customer');
                // Set transient to prevent duplicate emails (expires in 1 hour)
                set_transient($email_sent_key, true, HOUR_IN_SECONDS);
            } else {
                error_log('Aramex Automation: Failed to send tracking email to customer for order #' . $order->get_id());
            }
            
            return $sent;
            
        } catch (\Exception $e) {
            error_log('Aramex Automation: Email error for order #' . $order->get_id() . ' - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get email template
     */
    private function getEmailTemplate($order, $tracking_number)
    {
        $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $order_total = $order->get_formatted_order_total();
        
        $template = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Order Shipped</title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <h2 style="color: #2c3e50;">Your Order Has Been Shipped!</h2>
                
                <p>Dear ' . esc_html($customer_name) . ',</p>
                
                <p>Great news! Your order <strong>#' . $order->get_id() . '</strong> has been shipped and is on its way to you.</p>
                
                <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
                    <h3 style="margin-top: 0; color: #2c3e50;">Tracking Information</h3>
                    <p><strong>Tracking Number:</strong> ' . esc_html($tracking_number) . '</p>
                    <p><strong>Order Total:</strong> ' . $order_total . '</p>
                </div>
                
                <p>You can track your shipment using the tracking number above through the Aramex website.</p>
                
                <p>Thank you for your order!</p>
                
                <p>Best regards,<br>
                ' . get_option('blogname') . '</p>
            </div>
        </body>
        </html>';
        
        return $template;
    }
} 