```php
<?php

include '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'client') {
    header("Location: ../index.php");
    exit();
}

$client_id = $_SESSION['user_id'];

$milestone_id = $_POST['milestone_id'] ?? null;
$project_id   = $_POST['project_id'] ?? null;

if (!$milestone_id || !$project_id) {
    header("Location: dashboard.php");
    exit();
}


/*
|--------------------------------------------------------------------------
| Get milestone
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT m.*, p.title AS project_title, p.client_id
    FROM milestones m
    JOIN projects p ON m.project_id = p.id
    WHERE m.id = ?
      AND m.project_id = ?
      AND p.client_id = ?
");

$stmt->execute([
    $milestone_id,
    $project_id,
    $client_id
]);

$milestone = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$milestone) {
    die("Milestone not found.");
}


/*
|--------------------------------------------------------------------------
| Amount
|--------------------------------------------------------------------------
|
| Khalti requires amount in paisa.
|
| Example:
| Rs. 100 = 10000 paisa
|
|--------------------------------------------------------------------------
*/

$amount_paisa = (int) round($milestone['amount'] * 100);


/*
|--------------------------------------------------------------------------
| Khalti Test Secret Key
|--------------------------------------------------------------------------
|
| IMPORTANT:
| Put your NEW secret key from Khalti Test Admin here.
|
| Do NOT put this key in payment.php or JavaScript.
|
|--------------------------------------------------------------------------
*/

$secret_key = "c69f5739fdd34c9e856fe0fa5d2a799b";


/*
|--------------------------------------------------------------------------
| Unique order ID
|--------------------------------------------------------------------------
*/

$purchase_order_id =
    "MILESTONE-" .
    $milestone_id .
    "-" .
    time();


/*
|--------------------------------------------------------------------------
| Khalti Sandbox API
|--------------------------------------------------------------------------
*/

$url =
    "https://dev.khalti.com/api/v2/epayment/initiate/";


/*
|--------------------------------------------------------------------------
| Payment information
|--------------------------------------------------------------------------
*/

$payment_data = [

    "return_url" =>
           "http://localhost/Milestone_hub/client/khalti_return.php",

    "website_url" =>
        "http://localhost/Milestone_hub/",

    "amount" =>
        $amount_paisa,

    "purchase_order_id" =>
        $purchase_order_id,

    "purchase_order_name" =>
        $milestone['title'],

    "customer_info" => [

        "name" =>
            $_SESSION['user_name'],

        "email" =>
            "customer@example.com",

        "phone" =>
            "9800000000"
    ]
];


/*
|--------------------------------------------------------------------------
| Send request to Khalti
|--------------------------------------------------------------------------
*/

$ch = curl_init($url);

curl_setopt(
    $ch,
    CURLOPT_RETURNTRANSFER,
    true
);

curl_setopt(
    $ch,
    CURLOPT_POST,
    true
);

curl_setopt(
    $ch,
    CURLOPT_POSTFIELDS,
    json_encode($payment_data)
);

curl_setopt(
    $ch,
    CURLOPT_HTTPHEADER,
    [
        "Authorization: Key " . $secret_key,
        "Content-Type: application/json"
    ]
);


$response = curl_exec($ch);

$curl_error = curl_error($ch);

$http_code = curl_getinfo(
    $ch,
    CURLINFO_HTTP_CODE
);

curl_close($ch);


/*
|--------------------------------------------------------------------------
| Check CURL error
|--------------------------------------------------------------------------
*/

if ($curl_error) {

    die(
        "Khalti connection error: " .
        htmlspecialchars($curl_error)
    );

}


/*
|--------------------------------------------------------------------------
| Decode response
|--------------------------------------------------------------------------
*/

$result = json_decode($response, true);


/*
|--------------------------------------------------------------------------
| Successful request
|--------------------------------------------------------------------------
*/

if (
    $http_code >= 200 &&
    $http_code < 300 &&
    isset($result['payment_url'])
) {

    /*
    | Store information in session temporarily.
    */

    $_SESSION['khalti_pidx'] =
        $result['pidx'] ?? null;

    $_SESSION['khalti_milestone_id'] =
        $milestone_id;

    $_SESSION['khalti_project_id'] =
        $project_id;

    $_SESSION['khalti_amount'] =
        $milestone['amount'];


    /*
    | Redirect to Khalti
    */

    header(
        "Location: " .
        $result['payment_url']
    );

    exit();

}


/*
|--------------------------------------------------------------------------
| Payment initiation failed
|--------------------------------------------------------------------------
*/

echo "<h2>Payment initiation failed</h2>";

echo "<p>HTTP Status: " .
     htmlspecialchars($http_code) .
     "</p>";

echo "<pre>";

print_r($result);

echo "</pre>";

?>
```
