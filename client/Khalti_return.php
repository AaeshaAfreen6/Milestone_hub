<?php

include '../includes/db.php';
session_start();

// Show errors temporarily while testing
ini_set('display_errors', 1);
error_reporting(E_ALL);


// ===============================
// 1. Get data returned by Khalti
// ===============================

$pidx           = $_GET['pidx'] ?? null;
$status         = $_GET['status'] ?? null;
$transaction_id = $_GET['transaction_id'] ?? null;
$amount         = $_GET['amount'] ?? null;


// ===============================
// 2. Check pidx
// ===============================

if (!$pidx) {
    die("Payment verification failed: pidx not received from Khalti.");
}


// ===============================
// 3. Get data from session
// ===============================

$milestone_id = $_SESSION['khalti_milestone_id'] ?? null;
$project_id   = $_SESSION['khalti_project_id'] ?? null;
$client_id    = $_SESSION['user_id'] ?? null;

if (!$milestone_id || !$project_id || !$client_id) {
    die("Payment session expired. Please try the payment again.");
}


// ===============================
// 4. Verify payment with Khalti
// ===============================

$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://dev.khalti.com/api/v2/epayment/lookup/',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',

    CURLOPT_POSTFIELDS => json_encode([
        'pidx' => $pidx
    ]),

    CURLOPT_HTTPHEADER => array(
        'Authorization: Key .,
        'Content-Type: application/json',
    ),
));

$response = curl_exec($curl);

if ($response === false) {
    $curl_error = curl_error($curl);
    curl_close($curl);

    die("Khalti verification failed: " . $curl_error);
}

curl_close($curl);


// ===============================
// 5. Decode Khalti response
// ===============================

$result = json_decode($response, true);

if (!$result) {
    die("Invalid response received from Khalti: " . htmlspecialchars($response));
}


// ===============================
// 6. Check payment status
// ===============================

if (isset($result['status']) && $result['status'] === 'Completed') {

    // Use the verified amount from Khalti if available
    $verified_amount = $result['total_amount'] ?? $amount;


    // ===============================
    // 7. Update milestone
    // ===============================

    $stmt = $pdo->prepare("
        UPDATE milestones
        SET status = 'deposited',
            payment_status = 'deposited'
        WHERE id = ?
    ");

    $stmt->execute([$milestone_id]);


    // ===============================
    // 8. Log payment activity
    // ===============================

    $stmt = $pdo->prepare("
        INSERT INTO milestone_logs
        (
            milestone_id,
            changed_by,
            old_status,
            new_status,
            note
        )
        VALUES (?, ?, 'pending', 'deposited', ?)
    ");

    $stmt->execute([
        $milestone_id,
        $client_id,
        'Payment completed via Khalti. TXN: ' . ($transaction_id ?? $pidx)
    ]);


    // ===============================
    // 9. Clear Khalti session
    // ===============================

    unset($_SESSION['khalti_milestone_id']);
    unset($_SESSION['khalti_project_id']);
    unset($_SESSION['khalti_amount']);


    // ===============================
    // 10. Redirect to success page
    // ===============================

    header(
        'Location: khalti_success.php?project_id=' .
        urlencode($project_id) .
        '&amount=' .
        urlencode(($verified_amount ?? 0) / 100) .
        '&txn=' .
        urlencode($transaction_id ?? $pidx)
    );

    exit();

} else {

    // ===============================
    // Payment was not completed
    // ===============================

    $failed_status = $result['status'] ?? 'Unknown';

    header(
        'Location: view_project.php?id=' .
        urlencode($project_id) .
        '&error=' .
        urlencode('Payment failed. Status: ' . $failed_status)
    );

    exit();
}
?>