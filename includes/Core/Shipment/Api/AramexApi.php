<?php

namespace AramexAutomation\Core\Shipment\Api;

/**
 * Aramex API Handler
 */
class AramexApi
{
    /**
     * Create shipment using Aramex API
     */
    public function createShipment($shipment_data)
    {
        // Log that we're starting the shipment creation
        file_put_contents(ARAMEX_AUTOMATION_PLUGIN_PATH . 'debug-api.log', 
            date('Y-m-d H:i:s') . ' - Starting shipment creation...' . "\n", 
            FILE_APPEND);
        
        try {
            // Get Aramex settings
            $aramex_settings = get_option('woocommerce_aramex_settings');
            if (!$aramex_settings) {
                return [
                    'success' => false,
                    'message' => 'Aramex settings not found'
                ];
            }

            // Log the shipment data for debugging
            error_log('Aramex Automation: Shipment data - ' . print_r($shipment_data, true));

            // Get API info
            $api_info = $this->getApiInfo($aramex_settings);
            $client_info = $this->getClientInfo($aramex_settings);

            // Log API info (without sensitive data)
            $log_client_info = $client_info;
            unset($log_client_info['Password']);
            error_log('Aramex Automation: Client info - ' . print_r($log_client_info, true));
            error_log('Aramex Automation: API URL - ' . $api_info['baseUrl']);
            


            // Validate required fields
            $validation_result = $this->validateShipmentData($shipment_data);
            if (!$validation_result['valid']) {
                return [
                    'success' => false,
                    'message' => 'Validation Error: ' . $validation_result['message']
                ];
            }

            // Format shipment data
            $formatted_data = $this->formatShipmentData($shipment_data);
            error_log('Aramex Automation: Formatted data - ' . print_r($formatted_data, true));

            // Create SOAP client
            $client = new \SoapClient($api_info['baseUrl'], [
                'trace' => 1,
                'exceptions' => true
            ]);

            // Prepare SOAP request
            $major_par = [
                'Shipments' => [$formatted_data],
                'ClientInfo' => $client_info,
                'LabelInfo' => [
                    'ReportID' => 9201,
                    'ReportType' => 'URL'
                ]
            ];

            error_log('Aramex Automation: SOAP request - ' . print_r($major_par, true));

            // Make SOAP call
            $response = $client->CreateShipments($major_par);

            // Process response
            return $this->processShipmentResponse($response);

        } catch (\Exception $e) {
            error_log('Aramex Automation API Error: ' . $e->getMessage());
            error_log('Aramex Automation API Error Stack: ' . $e->getTraceAsString());
            return [
                'success' => false,
                'message' => 'API Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Schedule pickup using Aramex API
     */
    public function schedulePickup($order, $tracking_number)
    {
        try {
            // Get Aramex settings
            $aramex_settings = get_option('woocommerce_aramex_settings');
            if (!$aramex_settings) {
                return [
                    'success' => false,
                    'message' => 'Aramex settings not found'
                ];
            }

            // Get API info
            $api_info = $this->getApiInfo($aramex_settings);
            $client_info = $this->getClientInfo($aramex_settings);

            // Prepare pickup data
            $pickup_data = $this->preparePickupData($order, $aramex_settings);
            
            // Log pickup data for debugging
            error_log('Aramex Automation: Pickup data - ' . print_r($pickup_data, true));

            // Create SOAP client
            $client = new \SoapClient($api_info['baseUrl'], [
                'trace' => 1,
                'exceptions' => true
            ]);

            // Prepare SOAP request
            $major_par = [
                'ClientInfo' => $client_info,
                'Transaction' => [
                    'Reference1' => $order->get_id()
                ],
                'Pickup' => $pickup_data
            ];
            
            // Log SOAP request for debugging
            error_log('Aramex Automation: SOAP pickup request - ' . print_r($major_par, true));

            // Make SOAP call
            $response = $client->CreatePickup($major_par);

            // Process response
            return $this->processPickupResponse($response);

        } catch (\Exception $e) {
            error_log('Aramex Automation Pickup Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Pickup Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get API information
     */
    private function getApiInfo($aramex_settings)
    {
        $baseUrl = '';
        if ($aramex_settings['sandbox_flag'] == 1) {
            $baseUrl = 'https://ws.aramex.net/ShippingAPI.V2/Shipping/Service_1_0.svc?wsdl';
        } else {
            $baseUrl = 'https://ws.aramex.net/ShippingAPI.V2/Shipping/Service_1_0.svc?wsdl';
        }
        
        return [
            'baseUrl' => $baseUrl
        ];
    }

    /**
     * Get client information
     */
    private function getClientInfo($aramex_settings)
    {
        return [
            'AccountCountryCode' => $aramex_settings['account_country_code'],
            'AccountEntity' => $aramex_settings['account_entity'],
            'AccountNumber' => $aramex_settings['account_number'],
            'AccountPin' => $aramex_settings['account_pin'],
            'UserName' => $aramex_settings['user_name'],
            'Password' => $aramex_settings['password'],
            'Version' => 'v1.0'
        ];
    }

    /**
     * Validate shipment data
     */
    private function validateShipmentData($shipment_data)
    {
        $required_fields = [
            'aramex_shipment_original_reference',
            'aramex_shipment_shipper_name',
            'aramex_shipment_shipper_email',
            'aramex_shipment_shipper_company',
            'aramex_shipment_shipper_street',
            'aramex_shipment_shipper_city',
            'aramex_shipment_shipper_country',
            'aramex_shipment_shipper_phone',
            'aramex_shipment_receiver_name',
            'aramex_shipment_receiver_email',
            'aramex_shipment_receiver_street',
            'aramex_shipment_receiver_city',
            'aramex_shipment_receiver_country',
            'aramex_shipment_receiver_phone',
            'order_weight',
            'weight_unit',
            'number_pieces',
            'aramex_shipment_description',
            'aramex_shipment_info_product_group',
            'aramex_shipment_info_product_type',
            'aramex_shipment_info_payment_type'
        ];

        $missing_fields = [];
        foreach ($required_fields as $field) {
            if (!isset($shipment_data[$field]) || empty($shipment_data[$field])) {
                $missing_fields[] = $field;
            }
        }

        if (!empty($missing_fields)) {
            return [
                'valid' => false,
                'message' => 'Missing required fields: ' . implode(', ', $missing_fields)
            ];
        }

        return [
            'valid' => true,
            'message' => 'All required fields present'
        ];
    }

    /**
     * Format services according to original plugin logic
     */
    private function formatServices($service_type, $product_type)
    {
        $services = array();
        
        if ($product_type == "CDA") {
            if ($service_type == null || empty($service_type)) {
                array_push($services, "");
            } elseif (!in_array("", $service_type)) {
                $services = array_merge($services, $service_type);
                array_push($services, "");
            } else {
                $services = array_merge($services, $service_type);
            }
        } else {
            if ($service_type == null || empty($service_type)) {
                // For non-CDA, return empty string when no services
                return "";
            }
            $services = array_merge($services, $service_type);
        }
        
        return implode(',', $services);
    }

    /**
     * Format shipment data for API
     */
    private function formatShipmentData($shipment_data)
    {
        // Format the shipment data to match Aramex API requirements
        $formatted = [
            'Reference1' => $shipment_data['aramex_shipment_original_reference'],
            'Reference2' => '',
            'Reference3' => '',
            'ForeignHAWB' => $shipment_data['aramex_shipment_info_foreignhawb'] ?? '',
            'TransportType' => 0,
            'ShippingDateTime' => time(),
            'DueDate' => time() + (7 * 24 * 60 * 60),
            'PickupLocation' => 'Reception',
            'PickupGUID' => '',
            'Comments' => $shipment_data['aramex_shipment_info_comment'],
            'AccountingInstrcutions' => '',
            'OperationsInstructions' => '',
            'Details' => [
                'Dimensions' => [
                    'Length' => 10,
                    'Width' => 10,
                    'Height' => 10,
                    'Unit' => 'cm'
                ],
                'ActualWeight' => [
                    'Value' => $shipment_data['order_weight'],
                    'Unit' => $shipment_data['weight_unit']
                ],
                'ProductGroup' => $shipment_data['aramex_shipment_info_product_group'],
                'ProductType' => $shipment_data['aramex_shipment_info_product_type'],
                'PaymentType' => $shipment_data['aramex_shipment_info_payment_type'],
                'PaymentOptions' => $shipment_data['aramex_shipment_info_payment_option'] ?? '',
                'Services' => $this->formatServices($shipment_data['aramex_shipment_info_service_type'], $shipment_data['aramex_shipment_info_product_type']),
                'NumberOfPieces' => $shipment_data['number_pieces'],
                'DescriptionOfGoods' => $shipment_data['aramex_shipment_description'],
                'GoodsOriginCountry' => $shipment_data['aramex_shipment_shipper_country'],
                'Items' => []
            ],
            'Shipper' => [
                'Reference1' => $shipment_data['aramex_shipment_shipper_reference'],
                'Reference2' => '',
                'AccountNumber' => $shipment_data['aramex_shipment_shipper_account'],
                'PartyAddress' => [
                    'Line1' => $shipment_data['aramex_shipment_shipper_street'],
                    'Line2' => '',
                    'Line3' => '',
                    'City' => $shipment_data['aramex_shipment_shipper_city'],
                    'StateOrProvinceCode' => $shipment_data['aramex_shipment_shipper_state'],
                    'PostCode' => $shipment_data['aramex_shipment_shipper_postal'],
                    'CountryCode' => $shipment_data['aramex_shipment_shipper_country']
                ],
                'Contact' => [
                    'Department' => '',
                    'PersonName' => $shipment_data['aramex_shipment_shipper_name'],
                    'Title' => '',
                    'CompanyName' => $shipment_data['aramex_shipment_shipper_company'],
                    'PhoneNumber1' => $shipment_data['aramex_shipment_shipper_phone'],
                    'PhoneNumber1Ext' => '',
                    'PhoneNumber2' => '',
                    'PhoneNumber2Ext' => '',
                    'FaxNumber' => '',
                    'CellPhone' => $shipment_data['aramex_shipment_shipper_phone'],
                    'EmailAddress' => $shipment_data['aramex_shipment_shipper_email'],
                    'Type' => ''
                ]
            ],
            'Consignee' => [
                'Reference1' => $shipment_data['aramex_shipment_receiver_reference'],
                'Reference2' => '',
                'AccountNumber' => '',
                'PartyAddress' => [
                    'Line1' => $shipment_data['aramex_shipment_receiver_street'],
                    'Line2' => '',
                    'Line3' => '',
                    'City' => $shipment_data['aramex_shipment_receiver_city'],
                    'StateOrProvinceCode' => $shipment_data['aramex_shipment_receiver_state'],
                    'PostCode' => $shipment_data['aramex_shipment_receiver_postal'],
                    'CountryCode' => $shipment_data['aramex_shipment_receiver_country']
                ],
                'Contact' => [
                    'Department' => '',
                    'PersonName' => $shipment_data['aramex_shipment_receiver_name'],
                    'Title' => '',
                    'CompanyName' => $shipment_data['aramex_shipment_receiver_company'],
                    'PhoneNumber1' => $shipment_data['aramex_shipment_receiver_phone'],
                    'PhoneNumber1Ext' => '',
                    'PhoneNumber2' => '',
                    'PhoneNumber2Ext' => '',
                    'FaxNumber' => '',
                    'CellPhone' => $shipment_data['aramex_shipment_receiver_phone'],
                    'EmailAddress' => $shipment_data['aramex_shipment_receiver_email'],
                    'Type' => ''
                ]
            ]
        ];

        return $formatted;
    }

    /**
     * Get next working day based on configurable non-working days
     */
    private function getNextWorkingDay($start_date = null)
    {
        if ($start_date === null) {
            $start_date = time();
        }
        
        // Get configured non-working days (default to Saturday and Sunday)
        $non_working_days = get_option('aramex_automation_non_working_days', ['saturday', 'sunday']);
        
        // Convert day names to numbers (1=Monday, 7=Sunday)
        $day_number_map = [
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
            'sunday' => 7
        ];
        
        $non_working_numbers = [];
        foreach ($non_working_days as $day) {
            if (isset($day_number_map[$day])) {
                $non_working_numbers[] = $day_number_map[$day];
            }
        }
        
        // Check if current day is a non-working day
        $current_day = date('N', $start_date); // 1 (Monday) through 7 (Sunday)
        
        // If current day is non-working, find next working day
        if (in_array($current_day, $non_working_numbers)) {
            $days_to_check = 1;
            while ($days_to_check <= 7) { // Maximum 7 days to avoid infinite loop
                $check_date = $start_date + ($days_to_check * 24 * 60 * 60);
                $check_day = date('N', $check_date);
                
                if (!in_array($check_day, $non_working_numbers)) {
                    return $check_date;
                }
                $days_to_check++;
            }
        }
        
        // If current day is working, check pickup date preference
        $pickup_date = get_option('aramex_automation_pickup_date', 'tomorrow');
        if ($pickup_date === 'today') {
            return $start_date;
        } else {
            // For tomorrow, check if it's a non-working day
            $tomorrow = $start_date + (24 * 60 * 60);
            $tomorrow_day = date('N', $tomorrow);
            
            if (in_array($tomorrow_day, $non_working_numbers)) {
                // Tomorrow is non-working, find next working day
                $days_to_check = 1;
                while ($days_to_check <= 7) {
                    $check_date = $tomorrow + ($days_to_check * 24 * 60 * 60);
                    $check_day = date('N', $check_date);
                    
                    if (!in_array($check_day, $non_working_numbers)) {
                        return $check_date;
                    }
                    $days_to_check++;
                }
            } else {
                return $tomorrow;
            }
        }
        
        // Fallback: return original start date if something goes wrong
        return $start_date;
    }

    /**
     * Prepare pickup data
     */
    private function preparePickupData($order, $aramex_settings)
    {
        $pickup_date = get_option('aramex_automation_pickup_date', 'tomorrow');
        $ready_hour = get_option('aramex_automation_ready_hour', '9');
        $ready_minute = get_option('aramex_automation_ready_minute', '0');
        $latest_hour = get_option('aramex_automation_latest_hour', '17');
        $latest_minute = get_option('aramex_automation_latest_minute', '0');
        $pickup_location = get_option('aramex_automation_pickup_location', 'Reception');

        // Get next working day for pickup
        $pickup_date_timestamp = $this->getNextWorkingDay();
        
        // Create Unix timestamps for time fields (following the original plugin's approach)
        $ready_time = mktime($ready_hour, $ready_minute, 0, date("m", $pickup_date_timestamp), date("d", $pickup_date_timestamp), date("Y", $pickup_date_timestamp));
        $closing_time = mktime($latest_hour, $latest_minute, 0, date("m", $pickup_date_timestamp), date("d", $pickup_date_timestamp), date("Y", $pickup_date_timestamp));

        return [
            'PickupAddress' => [
                'Line1' => $aramex_settings['address'],
                'Line2' => '',
                'Line3' => '',
                'City' => $aramex_settings['city'],
                'StateOrProvinceCode' => $aramex_settings['state'],
                'PostCode' => $aramex_settings['postalcode'],
                'CountryCode' => $aramex_settings['country']
            ],
            'PickupContact' => [
                'Department' => '',
                'PersonName' => $aramex_settings['name'],
                'Title' => '',
                'CompanyName' => $aramex_settings['company'],
                'PhoneNumber1' => $aramex_settings['phone'],
                'PhoneNumber1Ext' => '',
                'PhoneNumber2' => '',
                'PhoneNumber2Ext' => '',
                'FaxNumber' => '',
                'CellPhone' => $aramex_settings['phone'],
                'EmailAddress' => $aramex_settings['email_origin'],
                'Type' => ''
            ],
            'PickupDate' => $ready_time,
            'ReadyTime' => $ready_time,
            'LastPickupTime' => $closing_time,
            'ClosingTime' => $closing_time,
            'Comments' => 'Auto-scheduled pickup for order #' . $order->get_id(),
            'Reference1' => $order->get_id(),
            'Reference2' => '',
            'Vehicle' => 'Van',
            'Shipments' => [
                'Shipment' => []
            ],
            'PickupItems' => [
                'PickupItemDetail' => [
                    'ProductGroup' => 'DOM',
                    'ProductType' => 'OND',
                    'Payment' => 'P',
                    'NumberOfShipments' => 1,
                    'NumberOfPieces' => 1,
                    'ShipmentWeight' => [
                        'Value' => 1,
                        'Unit' => 'KG'
                    ]
                ]
            ],
            'Status' => 'Pending',
            'PickupLocation' => $pickup_location
        ];
    }

    /**
     * Process shipment response
     */
    private function processShipmentResponse($response)
    {
        // Log the full response for debugging
        error_log('Aramex Automation: Full API Response - ' . print_r($response, true));
        
        // Also log to a file for easier debugging
        file_put_contents(ARAMEX_AUTOMATION_PLUGIN_PATH . 'debug-api.log', 
            date('Y-m-d H:i:s') . ' - API Response: ' . print_r($response, true) . "\n", 
            FILE_APPEND);
        
        // Check for errors using the same logic as the original plugin
        if ($response->HasErrors) {
            $error_message = 'Aramex API Error: ';
            
            // Check if Shipments array is empty (general error)
            if (empty($response->Shipments)) {
                if (isset($response->Notifications)) {
                    if (is_array($response->Notifications->Notification) && count($response->Notifications->Notification) > 1) {
                        foreach ($response->Notifications->Notification as $notify_error) {
                            $error_message .= $notify_error->Code . ' - ' . $notify_error->Message . ' ';
                        }
                    } else {
                        $error_message .= $response->Notifications->Notification->Code . ' - ' . $response->Notifications->Notification->Message;
                    }
                }
            } else {
                // Check for errors in the processed shipment
                if (isset($response->Shipments->ProcessedShipment->Notifications)) {
                    if (is_array($response->Shipments->ProcessedShipment->Notifications->Notification) && count($response->Shipments->ProcessedShipment->Notifications->Notification) > 1) {
                        foreach ($response->Shipments->ProcessedShipment->Notifications->Notification as $notification_error) {
                            $error_message .= $notification_error->Code . ' - ' . $notification_error->Message . ' ';
                        }
                    } else {
                        $error_message .= $response->Shipments->ProcessedShipment->Notifications->Notification->Code . ' - ' . $response->Shipments->ProcessedShipment->Notifications->Notification->Message;
                    }
                }
            }
            
            // If no specific error message found, provide generic error
            if (trim($error_message) === 'Aramex API Error: ') {
                $error_message .= 'Unknown API error occurred. Check API credentials and request format.';
            }
            
            return [
                'success' => false,
                'message' => trim($error_message)
            ];
        }

        // Check for successful response using the same logic as the original plugin
        if (isset($response->Shipments->ProcessedShipment->ID)) {
            return [
                'success' => true,
                'tracking' => $response->Shipments->ProcessedShipment->ID,
                'message' => 'Shipment created successfully. Tracking number: ' . $response->Shipments->ProcessedShipment->ID
            ];
        }

        // If we get here, something unexpected happened
        $debug_info = 'Response structure: ' . json_encode($response);
        error_log('Aramex Automation: Unexpected response structure - ' . $debug_info);
        
        return [
            'success' => false,
            'message' => 'No tracking number returned from API. Response structure: ' . $debug_info
        ];
    }

    /**
     * Process pickup response
     */
    private function processPickupResponse($response)
    {
        // Log the full pickup response for debugging
        error_log('Aramex Automation: Full Pickup Response - ' . print_r($response, true));
        
        if (isset($response->HasErrors) && $response->HasErrors) {
            $error_message = 'Pickup Error: ';
            if (isset($response->Notifications)) {
                if (is_array($response->Notifications->Notification) && count($response->Notifications->Notification) > 1) {
                    foreach ($response->Notifications->Notification as $notification) {
                        $error_message .= $notification->Code . ' - ' . $notification->Message . ' ';
                    }
                } else {
                    $error_message .= $response->Notifications->Notification->Code . ' - ' . $response->Notifications->Notification->Message;
                }
            }
            return [
                'success' => false,
                'message' => trim($error_message)
            ];
        }

        // Check for successful response using the same logic as the original plugin
        if (isset($response->ProcessedPickup->ID)) {
            return [
                'success' => true,
                'pickup_id' => $response->ProcessedPickup->ID,
                'message' => 'Pickup scheduled successfully. Pickup ID: ' . $response->ProcessedPickup->ID
            ];
        }

        // If we get here, something unexpected happened
        $debug_info = 'Response structure: ' . json_encode($response);
        error_log('Aramex Automation: Unexpected pickup response structure - ' . $debug_info);
        
        return [
            'success' => false,
            'message' => 'No pickup ID returned from API. Response structure: ' . $debug_info
        ];
    }
} 