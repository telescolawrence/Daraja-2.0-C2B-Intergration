<?php
if (isset($_POST['submit'])) {
    date_default_timezone_set('Africa/Nairobi');

    // Access token credentials
    $consumerKey = '..........................'; // Fill with your app Consumer Key
    $consumerSecret = '...............................................'; // Fill with your app Secret

    // Define transaction variables , in my case i was using a baybill and account number
    $BusinessShortCode = '.....'; // Paybill number
    $Passkey = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919'; // Passkey for Paybill 400200 -this passkey is for sandbox testing, for development the passkey can be obtained from the mpesa business portal.
    $PartyA = $_POST['phone']; // Customer's phone number from form
    $AccountReference = '4........'; // Account number for the transaction -this is the account number with respect to the paybill-
    $TransactionDesc = '.............THE NAME.....'; // Purpose of payment- basically  the message that will appear, do you want to pay amount ******  to .. THE NAME ..
    $Amount = 1; // Fixed amount of 1 shilling for testing


// Adjust the phone number to include the '254' prefix
    if (substr($PartyA, 0, 1) === '0') {
        $PartyA = '254' . substr($PartyA, 1);
    }
    
    // Generate timestamp
    $Timestamp = date('YmdHis');

    // Generate password
    $Password = base64_encode($BusinessShortCode . $Passkey . $Timestamp);

    // Set headers for access token request
    $headers = ['Content-Type:application/json; charset=utf8'];

    // Define M-PESA API URLs
    $access_token_url = 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'; // For production
    
    $initiate_url = 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest'; // STK Push URL

    // Callback URL for transaction status
    $CallBackURL = 'https://yourdomain.com/callback_url'; // Replace with your actual callback URL

    // Request access token
    $curl = curl_init($access_token_url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl, CURLOPT_HEADER, FALSE);
    curl_setopt($curl, CURLOPT_USERPWD, $consumerKey . ':' . $consumerSecret);
    $result = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $result = json_decode($result);
    $access_token = $result->access_token;
    curl_close($curl);

    // Prepare STK push headers
    $stkheader = ['Content-Type:application/json', 'Authorization:Bearer ' . $access_token];

    // Initiate STK push
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $initiate_url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $stkheader);

    // Prepare transaction data
    $curl_post_data = array(
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
        'TransactionDesc' => $TransactionDesc
    );

    $data_string = json_encode($curl_post_data);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
    $curl_response = curl_exec($curl);
    curl_close($curl);

    // Display the response
    echo $curl_response;
}
?>
