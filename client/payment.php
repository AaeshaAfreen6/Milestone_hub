```php
<?php
include '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'client') {
    header("Location: ../index.php");
    exit();
}

$client_id    = $_SESSION['user_id'];
$milestone_id = $_GET['milestone_id'] ?? null;
$project_id   = $_GET['project_id'] ?? null;

if (!$milestone_id || !$project_id) {
    header("Location: dashboard.php");
    exit();
}

// Get milestone details
$stmt = $pdo->prepare("
    SELECT m.*, p.title AS project_title, p.client_id
    FROM milestones m
    JOIN projects p ON m.project_id = p.id
    WHERE m.id = ? AND p.client_id = ?
");

$stmt->execute([$milestone_id, $client_id]);

$milestone = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$milestone) {
    header("Location: dashboard.php");
    exit();
}

$amount = (float) $milestone['amount'];
?>

<!DOCTYPE html>
<html>
<head>

    <title>Pay for Milestone - Milestone Hub</title>

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">

    <style>

        .payment-card {
            background: #fff;
            border-radius: 12px;
            padding: 32px;
            max-width: 500px;
            border: 0.5px solid #e8e6e6;
        }

        .payment-title {
            font-size: 18px;
            font-weight: 500;
            color: #1a3533;
            margin-bottom: 6px;
        }

        .payment-sub {
            font-size: 13px;
            color: #888780;
            margin-bottom: 24px;
        }

        .payment-detail {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 0.5px solid #f0eeee;
            font-size: 13px;
        }

        .payment-label {
            color: #888780;
        }

        .payment-value {
            font-weight: 500;
            color: #1a3533;
        }

        .amount-box {
            background: #e8f5f5;
            border-radius: 10px;
            padding: 16px;
            text-align: center;
            margin: 20px 0;
        }

        .amount-label {
            font-size: 13px;
            color: #47928e;
            margin-bottom: 4px;
        }

        .amount-value {
            font-size: 32px;
            font-weight: 600;
            color: #316461;
        }

        .btn-khalti {
            width: 100%;
            padding: 14px;
            background: #5C2D91;
            border: none;
            border-radius: 10px;
            color: #fff;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            font-family: inherit;

            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-khalti:hover {
            background: #4a2475;
        }

        .khalti-logo {
            background: #fff;
            border-radius: 4px;
            padding: 2px 6px;
            font-size: 12px;
            color: #5C2D91;
            font-weight: 700;
        }

        .test-note {
            background: #FEF3C7;
            color: #92400E;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 12px;
            margin-top: 16px;
            text-align: center;
        }

        .btn-back {
            display: inline-block;
            margin-top: 16px;
            font-size: 13px;
            color: #47928e;
            text-decoration: none;
        }

    </style>

</head>

<body>

<div class="topbar">

    <div class="topbar-left">
        <span class="logo">Milestone Hub</span>
    </div>

    <div class="topbar-right">

        <span class="username">
            Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?>
        </span>

        <a href="../logout.php" class="btn-logout">
            Logout
        </a>

    </div>

</div>


<div class="layout">

    <div class="sidebar">

        <a href="dashboard.php" class="nav-item">
            Dashboard
        </a>

        <a href="create_project.php" class="nav-item">
            + Create project
        </a>

        <a href="view_project.php" class="nav-item active">
            My projects
        </a>

        <a href="approve_milestone.php" class="nav-item">
            Approve milestones
        </a>

    </div>


    <div class="main">

        <h2 class="page-title">
            Make Payment
        </h2>

        <div class="payment-card">

            <div class="payment-title">
                Milestone Payment
            </div>

            <div class="payment-sub">
                You are paying for the following milestone
            </div>


            <div class="payment-detail">

                <span class="payment-label">
                    Project
                </span>

                <span class="payment-value">
                    <?= htmlspecialchars($milestone['project_title']) ?>
                </span>

            </div>


            <div class="payment-detail">

                <span class="payment-label">
                    Milestone
                </span>

                <span class="payment-value">
                    <?= htmlspecialchars($milestone['title']) ?>
                </span>

            </div>


            <div class="payment-detail">

                <span class="payment-label">
                    Milestone #
                </span>

                <span class="payment-value">
                    <?= htmlspecialchars($milestone['order_number']) ?> of 3
                </span>

            </div>


            <div class="payment-detail">

                <span class="payment-label">
                    Due Date
                </span>

                <span class="payment-value">
                    <?= date('d M Y', strtotime($milestone['due_date'])) ?>
                </span>

            </div>


            <div class="amount-box">

                <div class="amount-label">
                    Amount to pay
                </div>

                <div class="amount-value">
                    Rs. <?= number_format($amount, 2) ?>
                </div>

            </div>


            <!-- Khalti Payment -->

            <form method="POST" action="initiate.php">

                <input
                    type="hidden"
                    name="milestone_id"
                    value="<?= htmlspecialchars($milestone_id) ?>"
                >

                <input
                    type="hidden"
                    name="project_id"
                    value="<?= htmlspecialchars($project_id) ?>"
                >

                <button
                    type="submit"
                    class="btn-khalti"
                >

                    <span class="khalti-logo">
                        K
                    </span>

                    Pay with Khalti

                </button>

            </form>


            <div class="test-note">
                🧪 Test mode — No real money will be charged.
            </div>


            <a
                href="view_project.php?id=<?= htmlspecialchars($project_id) ?>"
                class="btn-back"
            >
                ← Back to project
            </a>

        </div>

    </div>

</div>

</body>
</html>
```
