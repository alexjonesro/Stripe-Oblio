<?php
require_once 'vendor/autoload.php';
ini_set('log_errors', 1);
ini_set('error_log', './error.log');
error_reporting(E_ALL);

// Assuming this is the payload from the Stripe webhook
$stripePayload = json_decode(file_get_contents('php://input'), true);
$stripeInvoice = $stripePayload['data']['object'];

// Extract customer country and VAT status
$customerCountry = $stripeInvoice['customer_address']['country'] ?? '';
$taxExempt = $stripeInvoice['tax_exempt'] ?? 'none';
$vatNumber = '';

if (isset($stripeInvoice['tax_ids']) && is_array($stripeInvoice['tax_ids'])) {
    foreach ($stripeInvoice['tax_ids'] as $tax_id) {
        if ($tax_id['type'] == 'eu_vat') {
            $vatNumber = $tax_id['value'];
            break;
        }
    }
}

// Default VAT settings
$vatPercentage = 19; // Default VAT percentage
$applyVAT = true; // Default to applying VAT

// EU countries array
$euCountries = [
    'AT', // Austria
    'BE', // Belgium
    'BG', // Bulgaria
    'CY', // Cyprus
    'CZ', // Czech Republic
    'DE', // Germany
    'DK', // Denmark
    'EE', // Estonia
    'ES', // Spain
    'FI', // Finland
    'FR', // France
    'GR', // Greece
    'HR', // Croatia
    'HU', // Hungary
    'IE', // Ireland
    'IT', // Italy
    'LT', // Lithuania
    'LU', // Luxembourg
    'LV', // Latvia
    'MT', // Malta
    'NL', // Netherlands
    'PL', // Poland
    'PT', // Portugal
    'RO', // Romania
    'SE', // Sweden
    'SI', // Slovenia
    'SK', // Slovakia
];

// Determine VAT application
if ($customerCountry == 'RO') {
    $applyVAT = true; // Always apply VAT for Romania
} elseif (in_array($customerCountry, $euCountries) && empty($vatNumber)) {
    $applyVAT = $taxExempt == 'none'; // Apply VAT if not tax-exempt and no valid VAT number
} else {
    $applyVAT = false; // Non-EU or valid VAT number
}

if (!$applyVAT) {
    $vatPercentage = 0; // Set VAT to 0% if it's not applied
}

// Convert cents to standard currency format
$amount = $stripeInvoice['total'] / 100;

// Map Stripe data to Oblio invoice fields
$defaultData = [
    'cif' => 'CIF', // Replace with your company's CIF
    'client' => [
        'cif' => $vatNumber, // Map Stripe customer ID to Oblio client CIF
        'name' => $stripeInvoice['customer_name'],
        'address' => $stripeInvoice['customer_address']['line1'] . ' ' . $stripeInvoice['customer_address']['line2'],
        'state' => $stripeInvoice['customer_address']['state'],
        'city' => $stripeInvoice['customer_address']['city'],
        'country' => $stripeInvoice['customer_address']['country'],
        'email' => $stripeInvoice['customer_email'],
        'phone' => '', // Add phone number if available
        'vatPayer' => '', // Specify if the client is a VAT payer
        // Add other client details as needed
    ],
    'issueDate' => date('Y-m-d', $stripeInvoice['created']), 
    'dueDate' => date('Y-m-d', strtotime('+30 days', $stripeInvoice['created'])),
    'deliveryDate' => '', // Specify delivery date if applicable
    'collectDate' => '', // Specify collect date if applicable
    'seriesName' => 'SS', 
    'language' => 'EN', 
    'precision' => 2,
    'currency' => 'USD',
    'collect' => [
        "type" => "Card",
        "documentNumber" => $stripeInvoice['charge']
    ],
    'products' => [
        [
            'name' => $stripeInvoice['lines']['data'][0]['description'],
            'code' => '', // Add product code if available
            'description' => '', // Add product description if needed
            'price' => $stripeInvoice['lines']['data'][0]['amount'] / 100, 
            'measuringUnit' => 'buc', 
            'currency' => 'USD',
            'vatName' => 'Normala', 
            'vatPercentage' => $vatPercentage,
            'vatIncluded' => false, 
            'quantity' => 1, 
            'productType' => 'Serviciu', 
            'management' => '' // Add management type if applicable
        ]
    ],
    'issuerName' => '', // Add issuer name
    'issuerId' => '', // Add issuer ID
    'noticeNumber' => '', // Add notice number if applicable
    'internalNote' => '', // Add any internal notes
    'deputyName' => '', // Add deputy name if applicable
    'deputyIdentityCard' => '', // Add deputy ID card info if applicable
    'deputyAuto' => '', // Add deputy auto info if applicable
    'selesAgent' => '', // Add sales agent if applicable
    'mentions' => '', // Add any mentions if necessary
    'value' => 0, // Add value if necessary
    'workStation' => 'Sediu', // Specify workstation
    'useStock' => 0, // Specify if using stock
];

try {
    $oblioApi = new OblioSoftware\Api('EMAIL', 'APIKEY');
    $result = $oblioApi->createInvoice($defaultData);
    // Handle successful creation
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    // Custom error handler
}
?>
