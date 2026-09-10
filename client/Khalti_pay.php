<?php
include '../includes/db.php';
session_start();

$milestone_id = $_GET['milestone_id'];
$project_id   = $_GET['project_id'];

// Get milestone details
$stmt = $pdo->prepare("SELECT * FROM milestones WHERE id = ?");
$stmt->execute([$milestone_id]);
$milestone = $stmt->fetch(PDO::FETCH_ASSOC);

// Get client details
$stmt2 = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt2->execute([$_SESSION['user_id']]);
$client = $stmt2->fetch(PDO::FETCH_ASSOC);

// Store in session
$_SESSION['khalti_milestone_id'] = $milestone_id;
$_SESSION['khalti_project_id']   = $project_id;
$_SESSION['khalti_amount']       = $milestone['amount'];

// Amount in paisa
$amount_paisa = intval($milestone['amount'] * 100);

$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://dev.khalti.com/api/v2/epayment/initiate/',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => '{
        "return_url": "http://localhost:8080/Milestone_hub/client/khalti_return.php",
        "website_url": "http://localhost:8080/Milestone_hub/",
        "amount": "' . $amount_paisa . '",
        "purchase_order_id": "milestone_' . $milestone_id . '_' . time() . '",
        "purchase_order_name": "' . addslashes($milestone['title']) . '",
        "customer_info": {
            "name": "' . addslashes($client['name']) . '",
            "email": "' . $client['email'] . '",
            "phone": "' . ($client['phone'] ?? '9800000001') . '"
        }
    }',
    CURLOPT_HTTPHEADER => array(
        'Authorization: Key .',
        'Content-Type: application/json',
    ),
));

$response = curl_exec($curl);
curl_close($curl);

// json decode and redirect to payment url
$url = json_decode($response, true)['payment_url'];
header('location:' . $url);
echo $response;
?>