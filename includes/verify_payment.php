<?php
include 'db.php';
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'client'){
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data        = json_decode(file_get_contents('php://input'), true);
$token       = $data['token'];
$amount      = $data['amount'];
$milestone_id = $data['milestone_id'];
$project_id  = $data['project_id'];
$client_id   = $_SESSION['user_id'];

// Verify with Khalti API
$args = http_build_query([
    'token'  => $token,
    'amount' => $amount
]);

$url = "https://khalti.com/api/v2/payment/verify/";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization:live_secret_key_c69f5739fdd34c9e856fe0fa5d2a799b' // Replace with your Khalti test secret key
]);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

if(isset($result['idx'])){
    // Payment verified successfully
    $stmt = $pdo->prepare("
        UPDATE milestones 
        SET status = 'deposited', 
            payment_status = 'deposited',
            khalti_token = ?,
            khalti_idx = ?
        WHERE id = ? AND project_id = ?
    ");
    $stmt->execute([$token, $result['idx'], $milestone_id, $project_id]);

    // Log activity
    $stmt = $pdo->prepare("
        INSERT INTO milestone_logs (milestone_id, changed_by, old_status, new_status, note) 
        VALUES (?, ?, 'pending', 'deposited', 'Payment made via Khalti')
    ");
    $stmt->execute([$milestone_id, $client_id]);

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Payment verification failed']);
}
?>