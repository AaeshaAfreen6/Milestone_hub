<?php
include '../includes/db.php';
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'client'){
    header("Location: ../index.php");
    exit();
}

$client_id    = $_SESSION['user_id'];
$milestone_id = $_SESSION['khalti_milestone_id'] ?? null;
$project_id   = $_SESSION['khalti_project_id'] ?? null;

// Get data from Khalti redirect
$pidx   = $_GET['pidx'] ?? null;
$status = $_GET['status'] ?? null;
$amount = $_GET['amount'] ?? null;

if($status == 'Completed' && $pidx){

    // Verify payment with Khalti
$secret_key = "c69f5739fdd34c9e856fe0fa5d2a799b";

$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://dev.khalti.com/api/v2/epayment/lookup/',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode([
        'pidx' => $pidx
    ]),
    CURLOPT_HTTPHEADER => array(
        'Authorization: Key c69f5739fdd34c9e856fe0fa5d2a799b,
        'Content-Type: application/json',
    ),
));

$response = curl_exec($curl);

if ($response === false) {
    die("Khalti verification connection failed: " . curl_error($curl));
}

curl_close($curl);

$data = json_decode($response, true);

    if(isset($data['status']) && $data['status'] == 'Completed'){

        // Update milestone status
        $stmt = $pdo->prepare("UPDATE milestones SET status = 'deposited', payment_status = 'deposited' WHERE id = ?");
        $stmt->execute([$milestone_id]);

        // Log activity
        $stmt = $pdo->prepare("INSERT INTO milestone_logs (milestone_id, changed_by, old_status, new_status, note) VALUES (?, ?, 'pending', 'deposited', 'Payment verified via Khalti')");
        $stmt->execute([$milestone_id, $client_id]);

        // Clear session
        unset($_SESSION['khalti_milestone_id']);
        unset($_SESSION['khalti_project_id']);

        // Redirect to success page
        header("Location: payment_success.php?project_id=$project_id&amount=" . ($amount/100));
        exit();

    } else {
        $payment_error = "Payment verification failed. Please contact support.";
    }

} else {
    $payment_error = "Payment was not completed. Status: " . htmlspecialchars($status ?? 'Unknown');
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Payment Failed - Milestone Hub</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f0f0f0; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { background: #fff; border-radius: 16px; padding: 40px; text-align: center; max-width: 400px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
        .icon { font-size: 60px; margin-bottom: 16px; }
        h2 { color: #B91C1C; margin-bottom: 10px; }
        p { color: #666; font-size: 14px; margin-bottom: 24px; }
        a { display: inline-block; padding: 12px 24px; background: #5C2D91; color: #fff; border-radius: 8px; text-decoration: none; font-size: 14px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">❌</div>
        <h2>Payment Failed</h2>
        <p><?= htmlspecialchars($payment_error ?? 'Something went wrong') ?></p>
        <a href="view_project.php?id=<?= $project_id ?>">← Go back to project</a>
    </div>
</body>
</html>