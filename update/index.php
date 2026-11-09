<?php
// Configuration
define('CURRENCIES_FILE', '../currencies.xml');
define('BASE_CURRENCY', 'GBP');
// Fixer.io API key: loaded from config.php (not committed) or the FIXER_API_KEY env variable.
// If no key is set, the service falls back to its built-in default rates.
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
}
if (!defined('API_KEY')) {
    define('API_KEY', getenv('FIXER_API_KEY') ?: '');
}
define('REFRESH_INTERVAL', 8 * 3600); // 8 hours in seconds

// Override HTTP method based on action parameter
if (isset($_GET['action'])) {
    switch($_GET['action']) {
        case 'put':
            $_SERVER['REQUEST_METHOD'] = 'PUT';
            break;
        case 'post':
            $_SERVER['REQUEST_METHOD'] = 'POST';
            break;
        case 'del':
            $_SERVER['REQUEST_METHOD'] = 'DELETE';
            break;
    }
}

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
    $api_url = "http://data.fixer.io/api/latest?access_key=" . API_KEY . "&base=GBP";
    $api_rates = [];
    
    // Try to get API rates
    try {
        $response = @file_get_contents($api_url);
        if ($response !== false) {
            $data = json_decode($response, true);
            if ($data && isset($data['rates'])) {
                $api_rates = $data['rates'];
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

//error handles 
function handle_error($code, $format) {
    $errors = [
        2000 => 'Action not recognized or is missing',
        2100 => 'Currency code in wrong format or is missing',
        2200 => 'Currency code not found for update',
        2300 => 'No rate listed for this currency',
        2400 => 'Cannot update base currency',
        2500 => 'Error in service'
    ];

    $response = [
        'error' => [
            'code' => $code,
            'msg' => $errors[$code]
        ]
    ];
    
    output_response($response, $format);
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
        if ($key === '@type') {
            $xml->addAttribute('type', $value);
        } else if (is_array($value)) {
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

// PUT implementation - Update currency rate
function handle_put($currency_code, $format) {
    if ($currency_code === BASE_CURRENCY) {
        handle_error(2400, $format);
    }

    $currencies = get_currencies();
    $currency = find_currency($currency_code, $currencies);

    if (!$currency) {
        handle_error(2200, $format);
    }

    if ((string)$currency->active !== 'true') {
        handle_error(2300, $format);
    }

    $old_rate = (float)$currency->rate;
    $new_rate = $old_rate * (1 + (rand(-5, 5) / 100));
    $currency->rate = number_format($new_rate, 5);
    
    // Format and save XML
    $dom = new DOMDocument('1.0');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($currencies->asXML());
    $dom->save(CURRENCIES_FILE);

    // Get the last refresh time
    $last_refresh = $currencies['last_refresh'] ?? date('Y-m-d H:i:s');

    $response = [
        'action' => [
            '@type' => 'put',
            'at' => (string)$last_refresh,
            'rate' => $new_rate,
            'old_rate' => $old_rate,
            'curr' => [
                'code' => (string)$currency->code,
                'name' => (string)$currency->name,
                'loc' => (string)$currency->location
            ]
        ]
    ];

    output_response($response, $format);
}

// POST implementation - Reactivate currency
function handle_post($currency_code, $format) {
    $currencies = get_currencies();
    $currency = find_currency($currency_code, $currencies);

    if (!$currency) {
        handle_error(2200, $format);
    }

    if ((string)$currency->active === 'true') {
        handle_error(2200, $format);
    }

    // Reactivate the currency
    $currency->active = 'true';
    
    // Format and save XML
    $dom = new DOMDocument('1.0');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($currencies->asXML());
    $dom->save(CURRENCIES_FILE);

    // Get the last refresh time
    $last_refresh = $currencies['last_refresh'] ?? date('Y-m-d H:i:s');

    $response = [
        'action' => [
            '@type' => 'post',
            'at' => (string)$last_refresh,
            'curr' => [
                'code' => (string)$currency->code,
                'name' => (string)$currency->name,
                'loc' => (string)$currency->location,
                'rate' => (float)$currency->rate,
                'status' => 'reactivated'
            ]
        ]
    ];

    output_response($response, $format);
}

// DELETE implementation - Deactivate currency
function handle_delete($currency_code, $format) {
    if ($currency_code === BASE_CURRENCY) {
        handle_error(2400, $format);
    }

    $currencies = get_currencies();
    $currency = find_currency($currency_code, $currencies);

    if (!$currency) {
        handle_error(2200, $format);
    }

    if ((string)$currency->active !== 'true') {
        handle_error(2300, $format);
    }

    // Deactivate the currency
    $currency->active = 'false';
    
    // Format and save XML
    $dom = new DOMDocument('1.0');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($currencies->asXML());
    $dom->save(CURRENCIES_FILE);

    // Get the last refresh time
    $last_refresh = $currencies['last_refresh'] ?? date('Y-m-d H:i:s');

    $response = [
        'action' => [
            '@type' => 'del',
            'at' => (string)$last_refresh,
            'curr' => [
                'code' => (string)$currency->code,
                'name' => (string)$currency->name,
                'loc' => (string)$currency->location,
                'status' => 'deactivated'
            ]
        ]
    ];

    output_response($response, $format);
}

// Main execution logic
$method = $_SERVER['REQUEST_METHOD'];
$currency = strtoupper($_GET['cur'] ?? '');
$action = strtolower($_GET['action'] ?? '');
$format = strtolower($_GET['format'] ?? 'xml');

// Validate action
if (!in_array($action, ['put', 'post', 'del'])) {
    handle_error(2000, $format);
}

// Validate currency format
if (empty($currency) || strlen($currency) !== 3 || !ctype_alpha($currency)) {
    handle_error(2100, $format);
}

// Validate format
if (!in_array($format, ['xml', 'json'])) {
    handle_error(2500, $format);
}

// Route to appropriate handler
switch ($method) {
    case 'PUT':
        if ($action !== 'put') {
            handle_error(2000, $format);
        }
        handle_put($currency, $format);
        break;

    case 'POST':
        if ($action !== 'post') {
            handle_error(2000, $format);
        }
        handle_post($currency, $format);
        break;

    case 'DELETE':
        if ($action !== 'del') {
            handle_error(2000, $format);
        }
        handle_delete($currency, $format);
        break;

    default:
        handle_error(2500, $format);
}
?>