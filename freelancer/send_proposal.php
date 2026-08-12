<?php
include '../includes/db.php';
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'freelancer'){
    header("Location: ../index.php");
    exit();
}

$freelancer_id = $_SESSION['user_id'];
$project_id    = $_GET['project_id'] ?? null;
$error         = "";
$success       = "";

if(!$project_id){
    header("Location: browse_project.php");
    exit();
}

// Get project details
$stmt = $pdo->prepare("SELECT p.*, u.name as client_name FROM projects p JOIN users u ON p.client_id = u.id WHERE p.id = ?");
$stmt->execute([$project_id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$project || $project['status'] != 'open'){
    header("Location: browse_project.php");
    exit();
}

// Get milestones
$ms = $pdo->prepare("SELECT * FROM milestones WHERE project_id = ? ORDER BY order_number");
$ms->execute([$project_id]);
$milestones = $ms->fetchAll(PDO::FETCH_ASSOC);

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $message = trim($_POST['message']);

    $check = $pdo->prepare("SELECT id FROM proposals WHERE project_id = ? AND freelancer_id = ?");
    $check->execute([$project_id, $freelancer_id]);

    if($check->rowCount() > 0){
        $error = "You have already sent a proposal for this project.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO proposals (project_id, freelancer_id, message, status) VALUES (?, ?, ?, 'pending')");
        $stmt->execute([$project_id, $freelancer_id, $message]);
        $success = "Proposal sent successfully!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Send Proposal - Milestone Hub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .proposal-wrapper {
            max-width: 800px;
        }

        .page-subtitle {
            font-size: 13px;
            color: #888780;
            margin-bottom: 24px;
            margin-top: -14px;
        }

        /* Project details table */
        .detail-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            border: 0.5px solid #e8e6e6;
            margin-bottom: 20px;
        }

        .detail-table thead tr {
            background: #1b4332;
            color: #fff;
        }

        .detail-table thead th {
            padding: 12px 16px;
            font-size: 13px;
            font-weight: 500;
            text-align: left;
        }

        .detail-table tbody tr {
            border-bottom: 0.5px solid #f0eeee;
        }

        .detail-table tbody tr:last-child {
            border-bottom: none;
        }

        .detail-table tbody tr:nth-child(even) {
            background: #f8fffe;
        }

        .detail-table tbody td {
            padding: 12px 16px;
            font-size: 13px;
            color: #333;
        }

        .detail-table tbody td:first-child {
            font-weight: 500;
            color: #081c15;
            width: 180px;
            background: #f8f8f8;
        }

        /* Milestone table */
        .milestone-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            border: 0.5px solid #e8e6e6;
            margin-bottom: 20px;
        }

        .milestone-table thead tr {
            background: #2d6a4f;
            color: #fff;
        }

        .milestone-table thead th {
            padding: 12px 16px;
            font-size: 13px;
            font-weight: 500;
            text-align: left;
        }

        .milestone-table tbody tr {
            border-bottom: 0.5px solid #f0eeee;
        }

        .milestone-table tbody tr:last-child {
            border-bottom: none;
        }

        .milestone-table tbody tr:nth-child(even) {
            background: #f8fffe;
        }

        .milestone-table tbody td {
            padding: 12px 16px;
            font-size: 13px;
            color: #333;
        }

        .ms-order {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #2d6a4f;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
        }

        .lock-badge {
            display: inline-block;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 20px;
            background: #FEF3C7;
            color: #92400E;
        }

        /* Proposal form */
        .form-section {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            border: 0.5px solid #e8e6e6;
        }

        .form-section h4 {
            font-size: 14px;
            font-weight: 500;
            color: #081c15;
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 0.5px solid #f0eeee;
        }

        .field label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #444;
            margin-bottom: 8px;
        }

        .field textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid #e8e6e6;
            border-radius: 10px;
            font-size: 14px;
            color: #1a1a2e;
            background: #fafafa;
            outline: none;
            font-family: inherit;
            resize: vertical;
            min-height: 120px;
        }

        .field textarea:focus {
            border-color: #2d6a4f;
            background: #fff;
        }

        .form-buttons {
            display: flex;
            gap: 10px;
            margin-top: 16px;
        }

        .btn-submit {
            padding: 10px 28px;
            background: linear-gradient(135deg, #2d6a4f, #1b4332);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            font-family: inherit;
        }

        .btn-back {
            padding: 10px 20px;
            background: transparent;
            border: 1.5px solid #e8e6e6;
            border-radius: 10px;
            color: #444;
            font-size: 14px;
            cursor: pointer;
            font-family: inherit;
            text-decoration: none;
            display: inline-block;
        }

        .section-heading {
            font-size: 13px;
            font-weight: 600;
            color: #2d6a4f;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .alert {
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 16px;
        }

        .alert-error   { background: #FEE2E2; color: #B91C1C; }
        .alert-success { background: #d8f3dc; color: #1b4332; }
        .alert-success a { color: #1b4332; font-weight: 600; }
    </style>
</head>
<body>

<div class="topbar">
    <div class="topbar-left">
        <span class="logo">Milestone Hub</span>
    </div>
    <div class="topbar-right">
        <span class="username">Welcome, <?= $_SESSION['user_name'] ?></span>
        <a href="../logout.php" class="btn-logout">Logout</a>
    </div>
</div>

<div class="layout">
    <div class="sidebar">
        <a href="dashboard.php" class="nav-item">Dashboard</a>
        <a href="browse_project.php" class="nav-item active">Browse projects</a>
        <a href="send_proposal.php" class="nav-item">My proposals</a>
        <a href="update_milestone.php" class="nav-item">My milestones</a>
    </div>

    <div class="main">
        <div class="proposal-wrapper">

            <h2 class="page-title">Send Proposal</h2>
            <p class="page-subtitle">Review the project details before sending your proposal</p>

            <?php if($error): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>

            <?php if($success): ?>
                <div class="alert alert-success">
                    <?= $success ?>
                    <a href="browse_project.php">← Back to projects</a>
                </div>
            <?php endif; ?>

            <!-- Project Details Table -->
            <p class="section-heading">Project Details</p>
            <table class="detail-table">
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Project Title</td>
                        <td><?= htmlspecialchars($project['title']) ?></td>
                    </tr>
                    <tr>
                        <td>Client Name</td>
                        <td><?= htmlspecialchars($project['client_name']) ?></td>
                    </tr>
                    <tr>
                        <td>Description</td>
                        <td><?= htmlspecialchars($project['description']) ?></td>
                    </tr>
                    <tr>
                        <td>Total Budget</td>
                        <td><strong>Rs. <?= number_format($project['total_budget'], 2) ?></strong></td>
                    </tr>
                    <tr>
                        <td>Status</td>
                        <td><span style="background:#d8f3dc;color:#1b4332;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:500">Open</span></td>
                    </tr>
                    <tr>
                        <td>Posted On</td>
                        <td><?= date('d M Y', strtotime($project['created_at'])) ?></td>
                    </tr>
                </tbody>
            </table>

            <!-- Milestones Table -->
            <p class="section-heading">Project Milestones</p>
            <table class="milestone-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Milestone Title</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Due Date</th>
                        <th>Lock Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($milestones as $m): ?>
                    <tr>
                        <td>
                            <div class="ms-order"><?= $m['order_number'] ?></div>
                        </td>
                        <td><?= htmlspecialchars($m['title']) ?></td>
                        <td><?= htmlspecialchars($m['description']) ?></td>
                        <td><strong>Rs. <?= number_format($m['amount'], 2) ?></strong></td>
                        <td><?= date('d M Y', strtotime($m['due_date'])) ?></td>
                        <td>
                            <?php if($m['order_number'] == 1): ?>
                                <span style="background:#d8f3dc;color:#1b4332;padding:3px 8px;border-radius:20px;font-size:11px;font-weight:500">Unlocked</span>
                            <?php else: ?>
                                <span class="lock-badge">🔒 Locked</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Proposal Form -->
            <div class="form-section">
                <h4>Write Your Proposal</h4>
                <form method="POST">
                    <div class="field">
                        <label>Your proposal message</label>
                        <textarea name="message" placeholder="Write why you are the right person for this project. Mention your experience, timeline, and approach..." required></textarea>
                    </div>
                    <div class="form-buttons">
                        <a href="browse_project.php" class="btn-back">← Cancel</a>
                        <button type="submit" class="btn-submit">Send Proposal</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

</body>
</html>