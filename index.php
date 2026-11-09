<?php
// Configuration
define('CURRENCIES_FILE', 'currencies.xml');
define('BASE_CURRENCY', 'GBP');
// Fixer.io API key: loaded from config.php (not committed) or the FIXER_API_KEY env variable.
// If no key is set, the service falls back to its built-in default rates.
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}
if (!defined('API_KEY')) {
    define('API_KEY', getenv('FIXER_API_KEY') ?: '');
}
define('REFRESH_INTERVAL', 8 * 3600); // 8 hours in seconds

// Helper function: Check if rates need updating
function should_update_rates() {
    $currencies = simplexml_load_file(CURRENCIES_FILE);
    if (!$currencies) {
        return true;
    }
    
    // Check last update timestamp
    $last_update = isset($currencies['timestamp']) ? (int)$currencies['timestamp'] : 0;
    return (time() - $last_update) >= REFRESH_INTERVAL;
}

// Helper function: Handle errors with exact messages
function handle_error($code, $format) {
    $errors = [
        1000 => 'Required parameter is missing',
        1100 => 'Parameter not recognized',
        1200 => 'Currency type not recognized',
        1300 => 'Currency amount must be a decimal number',
        1400 => 'Format must be xml or json',
        1500 => 'Error in service'
    ];

    $response = [
        'error' => [
            'code' => $code,
            'msg' => $errors[$code]
        ]
    ];

    output_response($response, $format);
}

// Helper function: Load currencies with GBP as base
function initialize_currencies() {
    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><currencies></currencies>');
    $xml->addAttribute('timestamp', time());
    $xml->addAttribute('last_refresh', date('Y-m-d H:i:s'));
    
    $default_currencies = [
        'AUD' => [
            'name' => 'Australian Dollar',
            'rate' => 1.94,
            'location' => 'Australia'
        ],
        'BRL' => [
            'name' => 'Brazilian Real',
            'rate' => 6.31,
            'location' => 'Brazil'
        ],
        'CAD' => [
            'name' => 'Canada Dollar',
            'rate' => 1.71,
            'location' => 'Canada'
        ],
        'CHF' => [
            'name' => 'Swiss Franc',
            'rate' => 1.12,
            'location' => 'Switzerland'
        ],
        'CNY' => [
            'name' => 'Chinese Yuan Renminbi',
            'rate' => 9.15,
            'location' => 'China'
        ],
        'DKK' => [
            'name' => 'Danish Krone',
            'rate' => 8.71,
            'location' => 'Denmark'
        ],
        'EUR' => [
            'name' => 'Euro',
            'rate' => 1.17,
            'location' => 'European Union'
        ],
        'GBP' => [
            'name' => 'Pound Sterling',
            'rate' => 1.00,
            'location' => 'United Kingdom'
        ],
        'HKD' => [
            'name' => 'Hong Kong Dollar',
            'rate' => 9.89,
            'location' => 'Hong Kong'
        ],
        'HUF' => [
            'name' => 'Hungarian Forint',
            'rate' => 444.23,
            'location' => 'Hungary'
        ],
        'INR' => [
            'name' => 'Indian Rupee',
            'rate' => 105.32,
            'location' => 'India'
        ],
        'JPY' => [
            'name' => 'Japanese Yen',
            'rate' => 188.68,
            'location' => 'Japan'
        ],
        'MXN' => [
            'name' => 'Mexican Peso',
            'rate' => 21.73,
            'location' => 'Mexico'
        ],
        'MYR' => [
            'name' => 'Malaysian Ringgit',
            'rate' => 5.92,
            'location' => 'Malaysia'
        ],
        'NOK' => [
            'name' => 'Norwegian Krone',
            'rate' => 13.45,
            'location' => 'Norway'
        ],
        'NZD' => [
            'name' => 'New Zealand Dollar',
            'rate' => 2.08,
            'location' => 'New Zealand'
        ],
        'PHP' => [
            'name' => 'Philippine Peso',
            'rate' => 71.21,
            'location' => 'Philippines'
        ],
        'RUB' => [
            'name' => 'Russian Ruble',
            'rate' => 116.84,
            'location' => 'Russia'
        ],
        'SEK' => [
            'name' => 'Swedish Krona',
            'rate' => 13.25,
            'location' => 'Sweden'
        ],
        'SGD' => [
            'name' => 'Singapore Dollar',
            'rate' => 1.71,
            'location' => 'Singapore'
        ],
        'THB' => [
            'name' => 'Thai Baht',
            'rate' => 44.97,
            'location' => 'Thailand'
        ],
        'TRY' => [
            'name' => 'Turkish Lira',
            'rate' => 32.18,
            'location' => 'Turkey'
        ],
        'USD' => [
            'name' => 'US Dollar',
            'rate' => 1.27,
            'location' => 'United States'
        ],
        'XCD' => [
            'name' => 'Eastern Caribbean Dollar',
            'rate' => 3.43,
            'location' => 'Eastern Caribbean'
        ],
        'ZAR' => [
            'name' => 'South African Rand',
            'rate' => 23.89,
            'location' => 'South Africa'
        ]
    ];
    
    // API endpoint
    // Fixer's free plan only supports EUR as the base currency, so rates are
    // requested against EUR and converted to a GBP base below.
    $api_url = "http://data.fixer.io/api/latest?access_key=" . urlencode(API_KEY);
    $api_rates = [];
    
    // Try to get API rates
    try {
        $response = API_KEY !== '' ? @file_get_contents($api_url) : false;
        if ($response !== false) {
            $data = json_decode($response, true);
            if ($data && !empty($data['success']) && !empty($data['rates'][BASE_CURRENCY])) {
                $base_rate = $data['rates'][BASE_CURRENCY];
                foreach ($data['rates'] as $code => $eur_rate) {
                    $api_rates[$code] = $eur_rate / $base_rate;
                }
            } elseif (isset($data['error'])) {
                error_log("Fixer API error: " . json_encode($data['error']));
            }
        }
    } catch (Exception $e) {
        error_log("API Error: " . $e->getMessage());
    }
    
    // Always create currencies using either API or default rates
    foreach ($default_currencies as $code => $data) {
        $currency = $xml->addChild('currency');
        $currency->addChild('code', $code);
        $currency->addChild('name', $data['name']);
        
        // Use API rate if available, otherwise use default
        $rate = isset($api_rates[$code]) ? $api_rates[$code] : $data['rate'];
        $currency->addChild('rate', number_format($rate, 5));
        
        //make the activision start point of XCD flase
        $currency->addChild('location', $data['location']);
        $currency->addChild('active', $code === 'XCD' ? 'false' : 'true');
    }
    
    // Format and save XML
    $dom = new DOMDocument('1.0');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($xml->asXML());
    $dom->save(CURRENCIES_FILE);
    
    return $xml;
}

// Helper function: Get currencies with update check
function get_currencies() {
    if (should_update_rates()) {
        return initialize_currencies();
    }
    return simplexml_load_file(CURRENCIES_FILE) ?: initialize_currencies();
}

// Helper function: Output response in XML or JSON
function output_response($data, $format) {
    if (ob_get_length()) ob_end_clean();

    if ($format === 'json') {
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT);
    } else {
        header('Content-Type: application/xml');
        $xml = new SimpleXMLElement('<conv/>');
        array_to_xml($data, $xml);
        echo $xml->asXML();
    }
    exit;
}

// Helper function: Convert array to XML
function array_to_xml($data, &$xml) {
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $subnode = $xml->addChild($key);
            array_to_xml($value, $subnode);
        } else {
            $xml->addChild($key, htmlspecialchars((string)$value));
        }
    }
}

// Helper function: Find currency in XML
function find_currency($code, $currencies) {
    foreach ($currencies->currency as $currency) {
        if ((string)$currency->code === $code) {
            return $currency;
        }
    }
    return null;
}

// Helper function: Check if currency is active
function is_currency_active($currency) {
    return ((string)$currency->active === 'true');
}

// Function to perform currency conversion
function convert_currency($from_code, $to_code, $amount, $format) {
    $currencies = get_currencies();
    
    // Find currencies
    $from_curr = find_currency($from_code, $currencies);
    $to_curr = find_currency($to_code, $currencies);
    
    // Validate currencies exist
    if (!$from_curr || !$to_curr) {
        handle_error(1200, $format);
    }
    
    // Validate currencies are active
    if (!is_currency_active($from_curr) || !is_currency_active($to_curr)) {
        handle_error(1200, $format);
    }

    // Calculate conversion
    $from_rate = (float)$from_curr->rate;
    $to_rate = (float)$to_curr->rate;
    
    // Convert through GBP
    $in_gbp = $amount / $from_rate;
    $converted_amount = $in_gbp * $to_rate;
    $rate = $to_rate / $from_rate;

    // Get the last refresh time
    $last_refresh = $currencies['last_refresh'] ?? date('Y-m-d H:i:s');

    // Prepare response
    $response = [
        'at' => (string)$last_refresh,
        'rate' => round($rate, 5),
        'from' => [
            'code' => (string)$from_curr->code,
            'curr' => (string)$from_curr->name,
            'loc' => (string)$from_curr->location,
            'amnt' => $amount
        ],
        'to' => [
            'code' => (string)$to_curr->code,
            'curr' => (string)$to_curr->name,
            'loc' => (string)$to_curr->location,
            'amnt' => round($converted_amount, 2)
        ]
    ];

    return $response;
}

// Main execution logic
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get and validate parameters
    $from = strtoupper($_GET['from'] ?? '');
    $to = strtoupper($_GET['to'] ?? '');
    $amount = $_GET['amnt'] ?? '';
    $format = strtolower($_GET['format'] ?? 'xml');

    // Validate required parameters
    if (!$from || !$to || !$amount) {
        handle_error(1000, $format);
    }

    // Validate amount is numeric
    if (!is_numeric($amount)) {
        handle_error(1300, $format);
    }

    // Validate format
    if (!in_array($format, ['xml', 'json'])) {
        handle_error(1400, $format);
    }

    // Validate no extra parameters
    foreach ($_GET as $key => $value) {
        if (!in_array($key, ['from', 'to', 'amnt', 'format'])) {
            handle_error(1100, $format);
        }
    }

    // Perform conversion and output response
    $result = convert_currency($from, $to, (float)$amount, $format);
    output_response($result, $format);
} else {
    handle_error(1500, 'xml');
}
?>