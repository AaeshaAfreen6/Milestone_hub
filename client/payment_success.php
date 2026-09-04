```php
<?php

include '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'client') {
    header("Location: ../index.php");
    exit();
}

$client_id = $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| Get pidx returned by Khalti
|--------------------------------------------------------------------------
*/

$pidx = $_GET['pidx'] ?? null;

if (!$pidx) {
    die("Payment information was not received.");
}


/*
|--------------------------------------------------------------------------
| Get stored payment information
|--------------------------------------------------------------------------
*/

$milestone_id =
    $_SESSION['khalti_milestone_id'] ?? null;

$project_id =
    $_SESSION['khalti_project_id'] ?? null;

$amount =
    $_SESSION['khalti_amount'] ?? 0;

if (!$milestone_id || !$project_id) {
    die("Payment session expired.");
}


/*
|--------------------------------------------------------------------------
| Khalti Test Secret Key
|--------------------------------------------------------------------------
*/

$secret_key = "c69f5739fdd34c9e856fe0fa5d2a799b";


/*
|--------------------------------------------------------------------------
| Verify payment with Khalti
|--------------------------------------------------------------------------
*/

$url =
    "https://dev.khalti.com/api/v2/epayment/lookup/";


$data = [
    "pidx" => $pidx
];


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
    json_encode($data)
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

curl_close($ch);


if ($curl_error) {
    die("Khalti verification error.");
}


$result = json_decode($response, true);


/*
|--------------------------------------------------------------------------
| Check payment status
|--------------------------------------------------------------------------
*/

if (
    !isset($result['status']) ||
    $result['status'] !== 'Completed'
) {

    echo "<h2>Payment was not completed.</h2>";

    echo "<p>Status: " .
         htmlspecialchars(
             $result['status'] ?? 'Unknown'
         ) .
         "</p>";

    echo '<p><a href="payment.php?milestone_id=' .
         htmlspecialchars($milestone_id) .
         '&project_id=' .
         htmlspecialchars($project_id) .
         '">Try again</a></p>';

    exit();
}


/*
|--------------------------------------------------------------------------
| Verify milestone belongs to logged-in client
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT m.*, p.client_id
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
    die("Unauthorized payment.");
}


/*
|--------------------------------------------------------------------------
| Update milestone
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    UPDATE milestones
    SET
        status = 'deposited',
        payment_status = 'deposited',
        khalti_token = ?,
        khalti_idx = ?
    WHERE id = ?
      AND project_id = ?
");

$stmt->execute([
    $pidx,
    $pidx,
    $milestone_id,
    $project_id
]);


/*
|--------------------------------------------------------------------------
| Log payment
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    INSERT INTO milestone_logs
    (
        milestone_id,
        changed_by,
        old_status,
        new_status,
        note
    )
    VALUES
    (
        ?,
        ?,
        ?,
        'deposited',
        'Payment made via Khalti'
    )
");

$stmt->execute([
    $milestone_id,
    $client_id,
    $milestone['status']
]);


/*
|--------------------------------------------------------------------------
| Remove temporary session data
|--------------------------------------------------------------------------
*/

unset($_SESSION['khalti_pidx']);
unset($_SESSION['khalti_milestone_id']);
unset($_SESSION['khalti_project_id']);
unset($_SESSION['khalti_amount']);

?>

<!DOCTYPE html>
<html>

<head>

    <title>Payment Successful - Milestone Hub</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {

            font-family:
                'Segoe UI',
                Arial,
                sans-serif;

            background: #f0f0f0;

            display: flex;

            align-items: center;

            justify-content: center;

            min-height: 100vh;
        }

        .card {

            background: #fff;

            border-radius: 16px;

            padding: 48px 40px;

            text-align: center;

            max-width: 420px;

            width: 100%;

            box-shadow:
                0 10px 40px
                rgba(0,0,0,0.1);
        }

        .success-icon {

            width: 80px;

            height: 80px;

            border-radius: 50%;

            background: #e8f5f5;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 40px;

            margin:
                0 auto 20px;
        }

        h2 {

            font-size: 22px;

            color: #316461;

            margin-bottom: 8px;
        }

        .sub {

            font-size: 14px;

            color: #888;

            margin-bottom: 24px;
        }

        .amount-box {

            background: #e8f5f5;

            border-radius: 10px;

            padding: 16px;

            margin-bottom: 24px;
        }

        .amount-label {

            font-size: 12px;

            color: #47928e;

            margin-bottom: 4px;
        }

        .amount-value {

            font-size: 28px;

            font-weight: 700;

            color: #316461;
        }

        .khalti-badge {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            background: #f8f4ff;

            border-radius: 20px;

            padding: 6px 14px;

            font-size: 12px;

            color: #5C2D91;

            margin-bottom: 24px;
        }

        .divider {

            height: 0.5px;

            background: #f0eeee;

            margin: 20px 0;
        }

        .info-row {

            display: flex;

            justify-content:
                space-between;

            font-size: 13px;

            padding: 6px 0;

            color: #555;
        }

        .info-row span:last-child {

            font-weight: 500;

            color: #1a1a2e;
        }

        .btn-primary {

            display: block;

            width: 100%;

            padding: 13px;

            background:
                linear-gradient(
                    135deg,
                    #47928e,
                    #316461
                );

            border-radius: 10px;

            color: #fff;

            text-decoration: none;

            font-size: 14px;

            font-weight: 500;

            margin-top: 24px;
        }

    </style>

</head>

<body>

<div class="card">

    <div class="success-icon">
        ✅
    </div>

    <h2>
        Payment Successful!
    </h2>

    <p class="sub">
        Your milestone payment has been
        processed successfully.
    </p>


    <div class="amount-box">

        <div class="amount-label">
            Amount Paid
        </div>

        <div class="amount-value">
            Rs. <?= number_format($amount, 2) ?>
        </div>

    </div>


    <div class="khalti-badge">

        <span style="font-weight:700;color:#5C2D91">
            K
        </span>

        Paid via Khalti

    </div>


    <div class="divider"></div>


    <div class="info-row">

        <span>
            Payment Status
        </span>

        <span style="color:#316461">
            ✓ Completed
        </span>

    </div>


    <div class="info-row">

        <span>
            Milestone Status
        </span>

        <span style="color:#185FA5">
            Deposited
        </span>

    </div>


    <div class="info-row">

        <span>
            Date
        </span>

        <span>
            <?= date('d M Y h:i A') ?>
        </span>

    </div>


    <a
        href="view_project.php?id=<?= htmlspecialchars($project_id) ?>"
        class="btn-primary"
    >
        Go back to project →
    </a>

</div>

</body>

</html>
```
