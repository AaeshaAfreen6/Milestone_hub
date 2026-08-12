<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../includes/db.php';
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'freelancer'){
    header("Location: ../index.php");
    exit();
}

$freelancer_id = $_SESSION['user_id'];
$success = "";
$error   = "";

// Handle status update
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])){
    $milestone_id = $_POST['milestone_id'];
    $new_status   = $_POST['new_status'];
    $old_status   = $_POST['old_status'];

    // Check milestone belongs to freelancer's project
    $stmt = $pdo->prepare("
        SELECT m.* FROM milestones m
        JOIN projects p ON m.project_id = p.id
        WHERE m.id = ? AND p.freelancer_id = ?
    ");
    $stmt->execute([$milestone_id, $freelancer_id]);
    $milestone = $stmt->fetch(PDO::FETCH_ASSOC);

    if($milestone){
        // Check locking rule
        if($milestone['is_locked']){
            $error = "This milestone is locked. Previous milestone must be approved first.";
        } else {
            // Update status
            $stmt = $pdo->prepare("UPDATE milestones SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $milestone_id]);

            // Log activity
            $stmt = $pdo->prepare("INSERT INTO milestone_logs (milestone_id, changed_by, old_status, new_status, note) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$milestone_id, $freelancer_id, $old_status, $new_status, 'Status updated by freelancer']);

            $success = "Milestone status updated to " . ucfirst(str_replace('_', ' ', $new_status)) . "!";
        }
    }
}

// Get all projects assigned to this freelancer
$stmt = $pdo->prepare("
    SELECT p.*, u.name as client_name
    FROM projects p
    JOIN users u ON p.client_id = u.id
    WHERE p.freelancer_id = ? AND p.status = 'active'
    ORDER BY p.created_at DESC
");
$stmt->execute([$freelancer_id]);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Milestones - Milestone Hub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .project-section {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            border: 0.5px solid #e8e6e6;
            margin-bottom: 20px;
        }

        .project-section h3 {
            font-size: 16px;
            font-weight: 500;
            color: #081c15;
            margin-bottom: 4px;
        }

        .project-meta {
            font-size: 13px;
            color: #888780;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 0.5px solid #f0eeee;
        }

        .milestone-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 0;
            border-bottom: 0.5px solid #f0eeee;
        }

        .milestone-item:last-child { border-bottom: none; }

        .ms-num {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #2d6a4f;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 600;
            flex-shrink: 0;
        }

        .ms-num.locked {
            background: #e0e0e0;
            color: #888;
        }

        .ms-info { flex: 1; }

        .ms-title {
            font-size: 14px;
            font-weight: 500;
            color: #081c15;
            margin-bottom: 3px;
        }

        .ms-title.locked { color: #aaa; }

        .ms-meta {
            font-size: 12px;
            color: #888780;
        }

        .ms-actions { text-align: right; min-width: 160px; }

        .badge {
            font-size: 11px;
            font-weight: 500;
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 8px;
        }

        .badge-pending    { background: #F1EFE8; color: #888780; }
        .badge-deposited  { background: #E6F1FB; color: #185FA5; }
        .badge-in-progress{ background: #FEF3C7; color: #92400E; }
        .badge-under-review{ background: #EEEDFE; color: #534AB7; }
        .badge-approved   { background: #d8f3dc; color: #1b4332; }
        .badge-locked     { background: #F1EFE8; color: #aaa; }

        .btn-update {
            display: block;
            width: 100%;
            padding: 7px 14px;
            background: linear-gradient(135deg, #2d6a4f, #1b4332);
            border: none;
            border-radius: 8px;
            color: #fff;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            font-family: inherit;
            text-align: center;
        }

        .btn-update:hover { opacity: 0.9; }

        .locked-msg {
            font-size: 12px;
            color: #aaa;
            font-style: italic;
        }

        .alert {
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 16px;
        }

        .alert-error   { background: #FEE2E2; color: #B91C1C; }
        .alert-success { background: #d8f3dc; color: #1b4332; }

        .empty {
            text-align: center;
            padding: 60px;
            color: #888780;
            font-size: 14px;
            background: #fff;
            border-radius: 12px;
            border: 0.5px dashed #e0e0e0;
        }

        .payment-badge {
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 20px;
            background: #d8f3dc;
            color: #1b4332;
            margin-left: 6px;
        }
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
        <a href="browse_project.php" class="nav-item">Browse projects</a>
        <a href="send_proposal.php" class="nav-item">My proposals</a>
        <a href="update_milestone.php" class="nav-item active">My milestones</a>
    </div>

    <div class="main">
        <h2 class="page-title">My Milestones</h2>

        <?php if($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <?php if(count($projects) == 0): ?>
            <div class="empty">
                No active projects yet. Browse projects and send a proposal!
                <br><br>
                <a href="browse_project.php" style="color:#2d6a4f;font-weight:500">Browse projects →</a>
            </div>
        <?php else: ?>

            <?php foreach($projects as $project): ?>

                <?php
                // Get milestones for this project
                $stmt = $pdo->prepare("SELECT * FROM milestones WHERE project_id = ? ORDER BY order_number");
                $stmt->execute([$project['id']]);
                $milestones = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>

                <div class="project-section">
                    <h3><?= htmlspecialchars($project['title']) ?></h3>
                    <div class="project-meta">
                        Client: <?= htmlspecialchars($project['client_name']) ?>
                        &nbsp;·&nbsp; Budget: Rs. <?= number_format($project['total_budget'], 2) ?>
                    </div>

                    <?php foreach($milestones as $m): ?>
                        <div class="milestone-item">

                            <div class="ms-num <?= $m['is_locked'] ? 'locked' : '' ?>">
                                <?= $m['is_locked'] ? '🔒' : $m['order_number'] ?>
                            </div>

                            <div class="ms-info">
                                <div class="ms-title <?= $m['is_locked'] ? 'locked' : '' ?>">
                                    <?= htmlspecialchars($m['title']) ?>
                                    <?php if($m['payment_status'] == 'deposited'): ?>
                                        <span class="payment-badge">💰 Deposited</span>
                                    <?php elseif($m['payment_status'] == 'released'): ?>
                                        <span class="payment-badge">✓ Released</span>
                                    <?php endif; ?>
                                </div>
                                <div class="ms-meta">
                                    Rs. <?= number_format($m['amount'], 2) ?>
                                    &nbsp;·&nbsp; Due: <?= date('d M Y', strtotime($m['due_date'])) ?>
                                </div>
                            </div>

                            <div class="ms-actions">
                                <?php if($m['is_locked']): ?>
                                    <span class="badge badge-locked">Locked</span>
                                    <div class="locked-msg">Previous milestone must be approved</div>

                                <?php elseif($m['status'] == 'deposited'): ?>
                                    <span class="badge badge-deposited">Deposited</span>
                                    <form method="POST" action="update_milestone.php">
                                        <input type="hidden" name="milestone_id" value="<?= $m['id'] ?>"/>
                                        <input type="hidden" name="old_status" value="deposited"/>
                                        <input type="hidden" name="new_status" value="in_progress"/>
                                        <button type="submit" name="update_status" class="btn-update">Start Working</button>
                                    </form>

                                <?php elseif($m['status'] == 'in_progress'): ?>
                                    <span class="badge badge-in-progress">In Progress</span>
                                    <form method="POST" action="update_milestone.php">
                                        <input type="hidden" name="milestone_id" value="<?= $m['id'] ?>"/>
                                        <input type="hidden" name="old_status" value="in_progress"/>
                                        <input type="hidden" name="new_status" value="under_review"/>
                                        <button type="submit" name="update_status" class="btn-update">Submit for Review</button>
                                    </form>

                                <?php elseif($m['status'] == 'under_review'): ?>
                                    <span class="badge badge-under-review">Under Review</span>
                                    <div class="locked-msg">Waiting for client approval</div>

                                <?php elseif($m['status'] == 'approved'): ?>
                                    <span class="badge badge-approved">✓ Approved</span>

                                <?php else: ?>
                                    <span class="badge badge-pending">Pending</span>
                                    <div class="locked-msg">Waiting for client to deposit</div>
                                <?php endif; ?>
                            </div>

                        </div>
                    <?php endforeach; ?>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>
</div>

</body>
</html>