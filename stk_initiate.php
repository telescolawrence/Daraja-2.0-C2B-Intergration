<?php
if (isset($_POST['submit'])) {
    date_default_timezone_set('Africa/Nairobi');

    // Secure credentials (recommend storing in environment variables)
    $consumerKey = getenv('CONSUMER_KEY') ?: 'YourConsumerKey';
    $consumerSecret = getenv('CONSUMER_SECRET') ?: 'YourConsumerSecret';
    $BusinessShortCode = '174379';
    $Passkey = getenv('PASSKEY') ?: 'YourPassKey';

    // User inputs
    $PartyA = $_POST['phone'];
    $Amount = $_POST['amount'];

    // Validate inputs
    if (!preg_match('/^2547\d{8}$/', $PartyA)) {
        die('Invalid phone number. Please use the format 2547XXXXXXXX.');
    }
    if (!is_numeric($Amount) || $Amount <= 0) {
        die('Invalid amount. Please enter a positive number.');
    }

    $AccountReference = '2255';
    $TransactionDesc = 'Test Payment';
    $Timestamp = date('YmdHis');
    $Password = base64_encode($BusinessShortCode . $Passkey . $Timestamp);

    // URLs
    $access_token_url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
    $initiate_url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
    $CallBackURL = 'https://yourdomain.com/callback'; // Use your actual callback URL

    // Get access token
    $curl = curl_init($access_token_url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_USERPWD, $consumerKey . ':' . $consumerSecret);
    $result = curl_exec($curl);

    if (curl_errno($curl)) {
        die('Curl error: ' . curl_error($curl));
    }

    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $result = json_decode($result);
    curl_close($curl);

    if ($status != 200 || !isset($result->access_token)) {
        die('Failed to get access token. Please check your credentials.');
    }

    $access_token = $result->access_token;

    // Prepare STK Push
    $stkheader = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $access_token,
    ];

    $curl_post_data = [
        'BusinessShortCode' => $BusinessShortCode,
        'Password' => $Password,
        'Timestamp' => $Timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => $Amount,
        'PartyA' => $PartyA,
        'PartyB' => $BusinessShortCode,
        'PhoneNumber' => $PartyA,
        'CallBackURL' => $CallBackURL,
        'AccountReference' => $AccountReference,
        'TransactionDesc' => $TransactionDesc,
    ];

    $curl = curl_init($initiate_url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $stkheader);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($curl_post_data));
    $curl_response = curl_exec($curl);

    if (curl_errno($curl)) {
        die('Curl error: ' . curl_error($curl));
    }

    $response = json_decode($curl_response, true);
    curl_close($curl);

    // Display response
    if (isset($response['ResponseCode']) && $response['ResponseCode'] == '0') {
        echo 'STK Push sent successfully. Please check your phone to complete the transaction.';
    } else {
        echo 'Failed to initiate STK Push: ' . ($response['errorMessage'] ?? 'Unknown error');
    }
}
?>
