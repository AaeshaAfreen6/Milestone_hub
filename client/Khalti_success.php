<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header("Location: ../index.php");
    exit();
}

$project_id     = $_GET['project_id'] ?? null;
$amount         = $_GET['amount'] ?? 0;
$transaction_id = $_GET['txn'] ?? 'N/A';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Payment Successful - Milestone Hub</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .card {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            max-width: 420px;
            width: 100%;
            box-shadow: 0 10px 40px rgba(0,0,0,0.12);
        }
        .card-top {
            background: #5C2D91;
            padding: 36px;
            text-align: center;
        }
        .success-circle {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            margin: 0 auto 14px;
            border: 3px solid rgba(255,255,255,0.4);
        }
        .card-top h2 { color: #fff; font-size: 20px; margin-bottom: 6px; }
        .card-top p  { color: rgba(255,255,255,0.8); font-size: 13px; }
        .card-body   { padding: 24px; }
        .amount-box {
            background: #f8f4ff;
            border-radius: 10px;
            padding: 16px;
            text-align: center;
            margin-bottom: 20px;
        }
        .amount-label { font-size: 12px; color: #5C2D91; margin-bottom: 4px; }
        .amount-value { font-size: 30px; font-weight: 700; color: #4a2475; }
        .khalti-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f8f4ff;
            border-radius: 20px;
            padding: 6px 14px;
            font-size: 12px;
            color: #5C2D91;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .khalti-icon {
            background: #5C2D91;
            color: #fff;
            padding: 2px 7px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
        }
        .divider { height: 0.5px; background: #f0eeee; margin: 16px 0; }
        .info-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            padding: 7px 0;
            color: #555;
            border-bottom: 0.5px solid #f5f5f5;
        }
        .info-row:last-of-type { border-bottom: none; }
        .info-row span:last-child { font-weight: 500; color: #1a1a2e; }
        .btn-primary {
            display: block;
            width: 100%;
            padding: 13px;
            background: #5C2D91;
            border-radius: 10px;
            color: #fff;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            text-align: center;
            margin-top: 20px;
        }
        .btn-primary:hover { background: #4a2475; }
    </style>
</head>
<body>
    <div class="card">

        <div class="card-top">
            <div class="success-circle">✓</div>
            <h2>Payment Successful!</h2>
            <p>Your milestone payment has been verified and processed</p>
        </div>

        <div class="card-body">

            <div class="amount-box">
                <div class="amount-label">Amount Paid</div>
                <div class="amount-value">Rs. <?= number_format($amount, 2) ?></div>
            </div>

            <div style="text-align:center">
                <div class="khalti-badge">
                    <span class="khalti-icon">K</span>
                    Paid via Khalti
                </div>
            </div>

            <div class="divider"></div>

            <div class="info-row">
                <span>Payment Status</span>
                <span style="color:#2e7d32">✓ Completed</span>
            </div>
            <div class="info-row">
                <span>Milestone Status</span>
                <span style="color:#185FA5">Deposited</span>
            </div>
            <div class="info-row">
                <span>Transaction ID</span>
                <span><?= htmlspecialchars($transaction_id) ?></span>
            </div>
            <div class="info-row">
                <span>Date & Time</span>
                <span><?= date('d M Y h:i A') ?></span>
            </div>

            <a href="view_project.php?id=<?= $project_id ?>" class="btn-primary">
                Go back to project →
            </a>

        </div>
    </div>
</body>
</html>