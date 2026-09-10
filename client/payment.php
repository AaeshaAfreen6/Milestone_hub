<?php
include '../includes/db.php';
include '../includes/config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'client') {
    header("Location: ../index.php");
    exit();
}

$client_id    = $_SESSION['user_id'];
$milestone_id = $_GET['milestone_id'] ?? null;
$project_id   = $_GET['project_id']   ?? null;

if (!$milestone_id || !$project_id) {
    header("Location: dashboard.php");
    exit();
}

// Fetch dynamic milestone and client details
$stmt = $pdo->prepare("
    SELECT m.*, 
           p.title AS project_title, 
           u.name AS client_name, 
           u.email AS client_email
    FROM milestones m
    JOIN projects p ON m.project_id = p.id
    JOIN users u ON p.client_id = u.id
    WHERE m.id = ? AND p.client_id = ?
");
$stmt->execute([$milestone_id, $client_id]);
$milestone = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$milestone) {
    die("Milestone not found or unauthorized access.");
}

// Store pending session IDs for return logic
$_SESSION['pending_milestone_id'] = $milestone_id;
$_SESSION['pending_project_id']   = $project_id;

// Fix 1: Ensure amount is strictly an integer in Paisa (Minimum 100 Paisa = Rs 1)
$amount_paisa = intval(round($milestone['amount'] * 100));
if ($amount_paisa < 100) {
    $amount_paisa = 10000; // Fallback to Rs 100 if amount is 0 or unassigned
}

// Fix 2: Clean parameters and use fresh test phone number
$payload = array(
    "return_url"          => SITE_URL . "client/khalti_return.php",
    "website_url"         => SITE_URL,
    "amount"              => $amount_paisa, // Integer value, not string
    "purchase_order_id"   => "Milestone_" . $milestone_id . "_" . time(),
    "purchase_order_name" => substr($milestone['title'], 0, 90), // Khalti limits string lengths
    "customer_info"       => array(
        "name"  => !empty($milestone['client_name']) ? $milestone['client_name'] : "Test Bahadur",
        "email" => !empty($milestone['client_email']) ? $milestone['client_email'] : "test@khalti.com",
        "phone" => "9800000005" // Fresh number to fix insufficient balance issue
    )
);

$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL            => 'https://dev.khalti.com/api/v2/epayment/initiate/',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING       => '',
    CURLOPT_MAXREDIRS      => 10,
    CURLOPT_TIMEOUT        => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST  => 'POST',
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_HTTPHEADER     => array(
        'Authorization:key . ' ,
        'Content-Type: application/json',
    ),
));

$response = curl_exec($curl);
curl_close($curl);

$data = json_decode($response, true);

if (isset($data['payment_url'])) {
    header('Location: ' . $data['payment_url']);
    exit();
} else {
    // Debugging printout in case of any remaining validation errors
    echo "<h3>Khalti Initiation Error:</h3>";
    echo "<pre>";
    print_r($data);
    echo "</pre>";
}
?>