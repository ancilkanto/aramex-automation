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
            
            // Use WooCommerce email system
            $mailer = WC()->mailer();
            $email_content = $this->getWooCommerceEmailTemplate($order, $tracking_number);
            
            // Send using WooCommerce mailer
            $sent = $mailer->send($order->get_billing_email(), 'Your order #' . $order->get_id() . ' has been shipped', $email_content);
            
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
     * Get WooCommerce email template
     */
    private function getWooCommerceEmailTemplate($order, $tracking_number)
    {
        // Get WooCommerce email template
        $mailer = WC()->mailer();
        $email_heading = 'Your order has been shipped!';
        
        // Start output buffering to capture the email content
        ob_start();
        
        // Include WooCommerce email header
        wc_get_template('emails/email-header.php', [
            'email_heading' => $email_heading,
            'email' => $mailer
        ]);
        
        // Email content
        $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $order_total = $order->get_formatted_order_total();
        $tracking_url = 'https://www.aramex.com/track/' . $tracking_number;
        
        ?>
        <p><?php printf(esc_html__('Hi %s,', 'woocommerce'), esc_html($customer_name)); ?></p>
        
        <p><?php printf(esc_html__('Great news! Your order #%s has been shipped and is on its way to you.', 'aramex-automation'), $order->get_id()); ?></p>
        
        <h2><?php esc_html_e('Tracking Information', 'aramex-automation'); ?></h2>
        
        <table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; margin-bottom: 40px;" border="1">
            <tbody>
                <tr>
                    <td class="td" scope="row" style="text-align: left; vertical-align: middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word; color: #636363; padding: 12px;">
                        <strong><?php esc_html_e('Tracking Number:', 'aramex-automation'); ?></strong>
                    </td>
                    <td class="td" scope="row" style="text-align: left; vertical-align: middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word; color: #636363; padding: 12px;">
                        <a href="<?php echo esc_url($tracking_url); ?>" target="_blank"><?php echo esc_html($tracking_number); ?></a>
                    </td>
                </tr>
                <tr>
                    <td class="td" scope="row" style="text-align: left; vertical-align: middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word; color: #636363; padding: 12px;">
                        <strong><?php esc_html_e('Order Total:', 'woocommerce'); ?></strong>
                    </td>
                    <td class="td" scope="row" style="text-align: left; vertical-align: middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word; color: #636363; padding: 12px;">
                        <?php echo $order_total; ?>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <p><?php esc_html_e('You can track your shipment using the tracking number above through the Aramex website.', 'aramex-automation'); ?></p>
        
        <p><?php esc_html_e('Thank you for your order!', 'woocommerce'); ?></p>
        
        <?php
        // Include WooCommerce email footer
        wc_get_template('emails/email-footer.php', [
            'email' => $mailer
        ]);
        
        $content = ob_get_clean();
        return $content;
    }
} 