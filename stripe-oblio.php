<?php
require_once 'vendor/autoload.php';
ini_set('log_errors', 1);
ini_set('error_log', '/home/error.log'); //replace with your url
error_reporting(E_ALL);

// Assuming this is the payload from the Stripe webhook
$stripePayload = json_decode(file_get_contents('php://input'), true);
$stripeInvoice = $stripePayload['data']['object'];
$paidAtTimestamp = $stripeInvoice['status_transitions']['paid_at'] ?? null;

// Check if paid_at timestamp is available and convert it to date format
$issueDate = $paidAtTimestamp ? date('Y-m-d', $paidAtTimestamp) : date('Y-m-d', $stripeInvoice['created']);


// Extract customer country and VAT status
$customerCountry = $stripeInvoice['customer_address']['country'] ?? '';
$taxExempt = $stripeInvoice['tax_exempt'] ?? 'none';
$vatNumber = '';

if (isset($stripeInvoice['customer_tax_ids']) && is_array($stripeInvoice['customer_tax_ids'])) {
    foreach ($stripeInvoice['customer_tax_ids'] as $tax_id) {
        if ($tax_id['type'] == 'eu_vat') {
            $vatNumber = $tax_id['value'];
            break;
        }
    }
}

$paidAmountStandard = $stripeInvoice['total'] / 100;
$amountExcludingTaxStandard = $stripeInvoice['lines']['data'][0]['amount_excluding_tax'] / 100;

// Initialize vatName with a default value
$vatName = 'Scutita'; // Default value, adjust as necessary

// Calculate VAT percentage
if ($amountExcludingTaxStandard > 0) {
    $vatPercentage = round(($paidAmountStandard - $amountExcludingTaxStandard) / $amountExcludingTaxStandard * 100, 1);
} else {
    $vatPercentage = 0; // Default to 0 if there's no amount excluding tax
}

// Adjust vatName based on VAT percentage
if ($vatPercentage > 0) {
    $vatName = null; // Set to appropriate name or leave null if applicable
}

// Convert cents to standard currency format
$amount = $stripeInvoice['total'] / 100;

$discountAmount = 0;
$discountType = 'valoric'; // Default type

// Check if there are any discounts in the Stripe invoice
if (!empty($stripeInvoice['total_discount_amounts'])) {
    foreach ($stripeInvoice['total_discount_amounts'] as $discount) {
        if (isset($discount['amount'])) {
            $discountAmount += $discount['amount'] / 100; // Convert from cents
        }
    }
}


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
	  // Additional non-EU countries as per your list
    'GB', // United Kingdom
    'CH', // Switzerland
];

// Determine VAT application
// Determine VAT application
$isEUCountry = in_array($customerCountry, $euCountries);

// Determine VAT payer status
$vatPayer = $isEUCountry;

$countryNamesInRomanian = [
    'AF' => 'Afganistan',
    'AX' => 'Insulele Aland',
    'AL' => 'Albania',
    'DZ' => 'Algeria',
    'AS' => 'Samoa Americană',
    'AD' => 'Andorra',
    'AO' => 'Angola',
    'AI' => 'Anguilla',
    'AQ' => 'Antarctica',
    'AG' => 'Antigua și Barbuda',
    'AR' => 'Argentina',
    'AM' => 'Armenia',
    'AW' => 'Aruba',
    'AU' => 'Australia',
    'AT' => 'Austria',
    'AZ' => 'Azerbaijan',
    'BS' => 'Bahamas',
    'BH' => 'Bahrain',
    'BD' => 'Bangladesh',
    'BB' => 'Barbados',
    'BY' => 'Belarus',
    'BE' => 'Belgia',
    'BZ' => 'Belize',
    'BJ' => 'Benin',
    'BM' => 'Bermuda',
    'BT' => 'Bhutan',
    'BO' => 'Bolivia',
    'BQ' => 'Bonaire, Sint Eustatius și Saba',
    'BA' => 'Bosnia și Herțegovina',
    'BW' => 'Botswana',
    'BV' => 'Insula Bouvet',
    'BR' => 'Brazilia',
    'IO' => 'Teritoriul Britanic din Oceanul Indian',
    'VG' => 'Insulele Virgine Britanice',
    'BN' => 'Brunei',
    'BG' => 'Bulgaria',
    'BF' => 'Burkina Faso',
    'BI' => 'Burundi',
    'KH' => 'Cambodgia',
    'CM' => 'Camerun',
    'CA' => 'Canada',
    'CV' => 'Capul Verde',
    'KY' => 'Insulele Cayman',
    'CF' => 'Republica Centrafricană',
    'TD' => 'Ciad',
    'CL' => 'Chile',
    'CN' => 'China',
    'CX' => 'Insula Christmas',
    'CC' => 'Insulele Cocos (Keeling)',
    'CO' => 'Colombia',
    'KM' => 'Comore',
    'CG' => 'Congo (Brazzaville)',
    'CD' => 'Congo (Kinshasa)',
    'CK' => 'Insulele Cook',
    'CR' => 'Costa Rica',
    'HR' => 'Croația',
    'CU' => 'Cuba',
    'CW' => 'Curaçao',
    'CY' => 'Cipru',
    'CZ' => 'Cehia',
    'DK' => 'Danemarca',
    'DJ' => 'Djibouti',
    'DM' => 'Dominica',
    'DO' => 'Republica Dominicană',
    'EC' => 'Ecuador',
    'EG' => 'Egipt',
    'SV' => 'El Salvador',
    'GQ' => 'Guineea Ecuatorială',
    'ER' => 'Eritreea',
    'EE' => 'Estonia',
    'ET' => 'Etiopia',
    'FK' => 'Insulele Falkland (Malvine)',
    'FO' => 'Insulele Feroe',
    'FJ' => 'Fiji',
    'FI' => 'Finlanda',
    'FR' => 'Franța',
    'GF' => 'Guiana Franceză',
    'PF' => 'Polinezia Franceză',
    'TF' => 'Teritoriile Franceze de Sud',
    'GA' => 'Gabon',
    'GM' => 'Gambia',
    'GE' => 'Georgia',
    'DE' => 'Germania',
    'GH' => 'Ghana',
    'GI' => 'Gibraltar',
    'GR' => 'Grecia',
    'GL' => 'Groenlanda',
    'GD' => 'Grenada',
    'GP' => 'Guadelupa',
    'GU' => 'Guam',
    'GT' => 'Guatemala',
    'GG' => 'Guernsey',
    'GN' => 'Guineea',
    'GW' => 'Guineea-Bissau',
    'GY' => 'Guyana',
    'HT' => 'Haiti',
    'HM' => 'Insulele Heard și McDonald',
    'HN' => 'Honduras',
    'HK' => 'Hong Kong',
    'HU' => 'Ungaria',
    'IS' => 'Islanda',
    'IN' => 'India',
    'ID' => 'Indonezia',
    'IR' => 'Iran',
    'IQ' => 'Irak',
    'IE' => 'Irlanda',
    'IM' => 'Insula Man',
    'IL' => 'Israel',
    'IT' => 'Italia',
    'CI' => 'Coasta de Fildeș',
    'JM' => 'Jamaica',
    'JP' => 'Japonia',
    'JE' => 'Jersey',
    'JO' => 'Iordania',
    'KZ' => 'Kazahstan',
    'KE' => 'Kenya',
    'KI' => 'Kiribati',
    'KW' => 'Kuweit',
    'KG' => 'Kârgâzstan',
    'LA' => 'Laos',
    'LV' => 'Letonia',
    'LB' => 'Liban',
    'LS' => 'Lesotho',
    'LR' => 'Liberia',
    'LY' => 'Libia',
    'LI' => 'Liechtenstein',
    'LT' => 'Lituania',
    'LU' => 'Luxemburg',
    'MO' => 'Macao',
    'MK' => 'Macedonia de Nord',
    'MG' => 'Madagascar',
    'MW' => 'Malawi',
    'MY' => 'Malaysia',
    'MV' => 'Maldive',
    'ML' => 'Mali',
    'MT' => 'Malta',
    'MH' => 'Insulele Marshall',
    'MQ' => 'Martinica',
    'MR' => 'Mauritania',
    'MU' => 'Mauritius',
    'YT' => 'Mayotte',
    'MX' => 'Mexic',
    'FM' => 'Micronezia',
    'MD' => 'Moldova',
    'MC' => 'Monaco',
    'MN' => 'Mongolia',
    'ME' => 'Muntenegru',
    'MS' => 'Montserrat',
    'MA' => 'Maroc',
    'MZ' => 'Mozambic',
    'MM' => 'Myanmar',
    'NA' => 'Namibia',
    'NR' => 'Nauru',
    'NP' => 'Nepal',
    'NL' => 'Țările de Jos',
    'NC' => 'Noua Caledonie',
    'NZ' => 'Noua Zeelandă',
    'NI' => 'Nicaragua',
    'NE' => 'Niger',
    'NG' => 'Nigeria',
    'NU' => 'Niue',
    'NF' => 'Insula Norfolk',
    'KP' => 'Coreea de Nord',
    'MP' => 'Insulele Mariane de Nord',
    'NO' => 'Norvegia',
    'OM' => 'Oman',
    'PK' => 'Pakistan',
    'PW' => 'Palau',
    'PS' => 'Teritoriile Palestiniene',
    'PA' => 'Panama',
    'PG' => 'Papua Noua Guinee',
    'PY' => 'Paraguay',
    'PE' => 'Peru',
    'PH' => 'Filipine',
    'PN' => 'Insulele Pitcairn',
    'PL' => 'Polonia',
    'PT' => 'Portugalia',
    'PR' => 'Puerto Rico',
    'QA' => 'Qatar',
    'RO' => 'România',
    'RU' => 'Rusia',
    'RW' => 'Rwanda',
    'RE' => 'Reunion',
    'BL' => 'Saint Barthélemy',
    'SH' => 'Sfânta Elena',
    'KN' => 'Saint Kitts și Nevis',
    'LC' => 'Sfânta Lucia',
    'MF' => 'Sfântul Martin (partea franceză)',
    'PM' => 'Sfântul Pierre și Miquelon',
    'VC' => 'Saint Vincent și Grenadine',
    'WS' => 'Samoa',
    'SM' => 'San Marino',
    'ST' => 'Sao Tome și Principe',
    'SA' => 'Arabia Saudită',
    'SN' => 'Senegal',
    'RS' => 'Serbia',
    'SC' => 'Seychelles',
    'SL' => 'Sierra Leone',
    'SG' => 'Singapore',
    'SX' => 'Sint Maarten (partea olandeză)',
    'SK' => 'Slovacia',
    'SI' => 'Slovenia',
    'SB' => 'Insulele Solomon',
    'SO' => 'Somalia',
    'ZA' => 'Africa de Sud',
    'GS' => 'Georgia de Sud și Insulele Sandwich de Sud',
    'KR' => 'Coreea de Sud',
    'SS' => 'Sudanul de Sud',
    'ES' => 'Spania',
    'LK' => 'Sri Lanka',
    'SD' => 'Sudan',
    'SR' => 'Surinam',
    'SJ' => 'Svalbard și Jan Mayen',
    'SE' => 'Suedia',
    'CH' => 'Elveția',
    'SY' => 'Siria',
    'TW' => 'Taiwan',
    'TJ' => 'Tajikistan',
    'TZ' => 'Tanzania',
    'TH' => 'Thailanda',
    'TL' => 'Timorul de Est',
    'TG' => 'Togo',
    'TK' => 'Tokelau',
    'TO' => 'Tonga',
    'TT' => 'Trinidad și Tobago',
    'TN' => 'Tunisia',
    'TR' => 'Turcia',
    'TM' => 'Turkmenistan',
    'TC' => 'Insulele Turks și Caicos',
    'TV' => 'Tuvalu',
    'UG' => 'Uganda',
    'UA' => 'Ucraina',
    'AE' => 'Emiratele Arabe Unite',
    'GB' => 'Marea Britanie',
    'US' => 'Statele Unite',
    'UM' => 'Insulele Minore Îndepărtate ale Statelor Unite',
    'UY' => 'Uruguay',
    'UZ' => 'Uzbekistan',
    'VU' => 'Vanuatu',
    'VA' => 'Statul Vatican',
    'VE' => 'Venezuela',
    'VN' => 'Vietnam',
    'WF' => 'Wallis și Futuna',
    'EH' => 'Sahara de Vest',
    'YE' => 'Yemen',
    'ZM' => 'Zambia',
    'ZW' => 'Zimbabwe',
];

// Get the Romanian name of the country
$countryNameInRomanian = $countryNamesInRomanian[$customerCountry] ?? 'Unknown';

$products = [
    [
        'name' => $stripeInvoice['lines']['data'][0]['description'],
        'code' => '',
        'description' => '',
        'price' => $stripeInvoice['lines']['data'][0]['amount'] / 100,
        'measuringUnit' => 'buc',
        'vatName' => $vatName,
        'vatPercentage' => $vatPercentage,
        'vatIncluded' => false,
        'quantity' => $stripeInvoice['lines']['data'][0]['quantity'],
        'productType' => 'Serviciu',
        'management' => ''
    ]
];

// Add discount information only if there is a discount
if ($discountAmount > 0) {
    $products[] = [
        'name' => 'Discount',
        'discount' => $discountAmount,
        'discountType' => $discountType
    ];
}




// Map Stripe data to Oblio invoice fields
$defaultData = [
    'cif' => 'CIF', // Replace with your company's CIF
    'client' => [
        'cif' => $vatNumber, // Map Stripe customer ID to Oblio client CIF
        'name' => $stripeInvoice['customer_name'],
        'address' => $stripeInvoice['customer_address']['line1'] . ' ' . $stripeInvoice['customer_address']['line2'],
        'state' => $stripeInvoice['customer_address']['state'],
        'city' => $stripeInvoice['customer_address']['city'],
        'country' => $countryNameInRomanian,
        'email' => $stripeInvoice['customer_email'],
        'phone' => '', // Add phone number if available
        'vatPayer' => $vatPayer, // Specify if the client is a VAT payer
        // Add other client details as needed
    ],
    'issueDate' => $issueDate, 
    'dueDate' => date('Y-m-d', strtotime('+30 days', $stripeInvoice['created'])),
    'deliveryDate' => '', // Specify delivery date if applicable
    'collectDate' => '', // Specify collect date if applicable
    'seriesName' => 'FD', 
    'language' => 'EN', 
    'precision' => 2,
    'currency' => 'USD',
	'exchangeRate' => "0.000000",
    'collect' => [
        "type" => "Card",
        "documentNumber" => $stripeInvoice['charge'],
		"value" => $stripeInvoice['total'] / 100,
		"issueDate" => $issueDate, 
    ],
    'products' => $products,
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
    $oblioApi = new OblioSoftware\Api('email', 'apikey');
    $result = $oblioApi->createInvoice($defaultData);
    // Handle successful creation
} catch (\Exception $e) {
    error_log("Error: " . $e->getMessage());
    if ($e instanceof \GuzzleHttp\Exception\ClientException) {
        // GuzzleHttp exception handling
        $responseBody = $e->getResponse()->getBody(true);
        error_log("API Response: " . $responseBody);
    }
    // other error handling...
}

?>