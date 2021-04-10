<?php

require_once('vendor/autoload.php');

$everyaction_username = ''; // Use an EveryAction Mobilize API key
$everyaction_password = ''; // Should end with |1 or |0

// Create Guzzle HTTP client for EveryAction API
use GuzzleHttp\Client;
$client = new Client([
    'base_uri' => 'https://api.securevan.com',
    'timeout'  => 2.0,
]);

// Get the input data from Action Network
$orig = file_get_contents('php://input');
$input = json_decode($orig, true);
if (in_array('osdi:signature',$input[0])) {
    $input = $input[0]['osdi:signature'];
}
elseif (in_array('osdi:attendance',$input[0])) {
    $input = $input[0]['osdi:attendance'];
    
}

// Compile data needed for EveryAction API call from Action Network data
$data = new stdClass();
$data->firstName = $input['person']['given_name'];
$data->lastName = $input['person']['family_name'];

// Cycle through Action Network email addresses
foreach ($input['person']['email_addresses'] as $email) {
    $emailInput = new stdClass();
    $emailInput->email = $email['address'];
    $emailInput->type = 'P';
    if ($email['primary']) {
        $emailInput->isPreferred = true;
    }
    else {
        $emailInput->isPreferred = false;
    }
    if ((isset($email['status']) && $email['status'] == 'subscribed') || !isset($email['status'])) {
        $emailInput->subscriptionStatus = 'S';
    }
    else {
        $emailInput->subscriptionStatus = 'N';
    }
    $data->emails[] = $emailInput;
}

// Cycle through Action Network postal addresses
foreach ($input['person']['postal_addresses'] as $address) {
    $addressInput = new stdClass();
    if (isset($address['postal_code'])) $addressInput->zipOrPostalCode = $address['postal_code'];
    if (isset($address['country'])) $addressInput->countryCode = $address['country'];
    if ($address['primary']) {
        $addressInput->isPreferred = true;
    }
    else {
        $addressInput->isPreferred = false;
    }
    $data->addresses[] = $addressInput;
}

// EveryAction API call
$response = $client->request('POST', '/v4/people/findOrCreate', [
    'json' => $data,
    'auth' => [$everyaction_username, $everyaction_password],
]);
