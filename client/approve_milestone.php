<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../includes/db.php';
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'client'){
    header("Location: ../index.php");
    exit();
}

$client_id = $_SESSION['user_id'];
$success   = "";
$error     = "";

// Handle approve
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['approve_milestone'])){
    $milestone_id = $_POST['milestone_id'];
    $project_id   = $_POST['project_id'];

    // Check milestone belongs to client's project
    $stmt = $pdo->prepare("
        SELECT m.* FROM milestones m
        JOIN projects p ON m.project_id = p.id
        WHERE m.id = ? AND p.client_id = ?
    ");
    $stmt->execute([$milestone_id, $client_id]);
    $milestone = $stmt->fetch(PDO::FETCH_ASSOC);

    if($milestone && $milestone['status'] == 'under_review'){

        // Approve milestone
        $stmt = $pdo->prepare("UPDATE milestones SET status = 'approved', payment_status = 'released' WHERE id = ?");
        $stmt->execute([$milestone_id]);

        // Unlock next milestone
        $next_order = $milestone['order_number'] + 1;
        $stmt = $pdo->prepare("UPDATE milestones SET is_locked = 0 WHERE project_id = ? AND order_number = ?");
        $stmt->execute([$project_id, $next_order]);

        // Log activity
        $stmt = $pdo->prepare("INSERT INTO milestone_logs (milestone_id, changed_by, old_status, new_status, note) VALUES (?, ?, 'under_review', 'approved', 'Approved by client')");
        $stmt->execute([$milestone_id, $client_id]);

        // Check if all milestones approved
        $stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(status='approved') as done FROM milestones WHERE project_id = ?");
        $stmt->execute([$project_id]);
        $ms_count = $stmt->fetch(PDO::FETCH_ASSOC);

        if($ms_count['total'] == $ms_count['done']){
            $stmt = $pdo->prepare("UPDATE projects SET status = 'completed' WHERE id = ?");
            $stmt->execute([$project_id]);
            $success = "All milestones approved! Project is now completed. 🎉";
        } else {
            $success = "Milestone approved! Next milestone has been unlocked.";
        }

    } else {
        $error = "This milestone cannot be approved. It must be Under Review first.";
    }
}

// Get all active projects by this client
$stmt = $pdo->prepare("
    SELECT p.*, u.name as freelancer_name
    FROM projects p
    LEFT JOIN users u ON p.freelancer_id = u.id
    WHERE p.client_id = ? AND p.status IN ('active', 'completed')
    ORDER BY p.created_at DESC
");
$stmt->execute([$client_id]);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Approve Milestones - Milestone Hub</title>
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

        .project-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 6px;
        }

        .project-title {
            font-size: 16px;
            font-weight: 500;
            color: #081c15;
        }

        .project-meta {
            font-size: 13px;
            color: #888780;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 0.5px solid #f0eeee;
        }

        .milestone-row {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 0;
            border-bottom: 0.5px solid #f0eeee;
        }

        .milestone-row:last-child { border-bottom: none; }

        .ms-num {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #47928e;
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

        .ms-actions { text-align: right; min-width: 180px; }

        .badge {
            display: inline-block;
            font-size: 11px;
            font-weight: 500;
            padding: 4px 12px;
            border-radius: 20px;
            margin-bottom: 6px;
        }

        .badge-pending     { background: #F1EFE8; color: #888780; }
        .badge-deposited   { background: #E6F1FB; color: #185FA5; }
        .badge-in-progress { background: #FEF3C7; color: #92400E; }
        .badge-under-review{ background: #EEEDFE; color: #534AB7; }
        .badge-approved    { background: #e8f5f5; color: #316461; }
        .badge-locked      { background: #F1EFE8; color: #aaa; }
        .badge-active      { background: #e8f5f5; color: #316461; }
        .badge-completed   { background: #F1EFE8; color: #5F5E5A; }

        .btn-approve {
            display: block;
            padding: 8px 18px;
            background: linear-gradient(135deg, #47928e, #316461);
            border: none;
            border-radius: 8px;
            color: #fff;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            font-family: inherit;
            text-align: center;
        }

        .btn-approve:hover { opacity: 0.9; }

        .waiting-msg {
            font-size: 12px;
            color: #aaa;
            font-style: italic;
        }

        .payment-badge {
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 20px;
            background: #e8f5f5;
            color: #316461;
            margin-left: 6px;
        }

        .alert {
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 16px;
        }

        .alert-error   { background: #FEE2E2; color: #B91C1C; }
        .alert-success { background: #e8f5f5; color: #316461; }

        .empty {
            text-align: center;
            padding: 60px;
            color: #888780;
            font-size: 14px;
            background: #fff;
            border-radius: 12px;
            border: 0.5px dashed #e0e0e0;
        }

        .review-highlight {
            background: #fffbf0;
            border: 1.5px solid #f0c040;
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 8px;
        }

        .review-tag {
            font-size: 11px;
            font-weight: 600;
            color: #92400E;
            background: #FEF3C7;
            padding: 3px 10px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>

<!-- TOPBAR -->
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

    <!-- SIDEBAR -->
    <div class="sidebar">
        <a href="dashboard.php" class="nav-item">Dashboard</a>
        <a href="create_project.php" class="nav-item">+ Create project</a>
        <a href="approve_milestone.php" class="nav-item active">Approve milestones</a>
         <a href="../landing.php" class="nav-item">← Home</a>
    </div>

    <!-- MAIN -->
    <div class="main">
        <h2 class="page-title">Approve Milestones</h2>

        <?php if($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <?php if(count($projects) == 0): ?>
            <div class="empty">
                No active projects yet. 
                <a href="create_project.php" style="color:#47928e;font-weight:500">Create a project →</a>
            </div>
        <?php else: ?>

            <?php foreach($projects as $project): ?>

                <?php
                $stmt = $pdo->prepare("SELECT * FROM milestones WHERE project_id = ? ORDER BY order_number");
                $stmt->execute([$project['id']]);
                $milestones = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Count under review milestones
                $under_review_count = 0;
                foreach($milestones as $m){
                    if($m['status'] == 'under_review') $under_review_count++;
                }
                ?>

                <div class="project-section">
                    <div class="project-head">
                        <div class="project-title"><?= htmlspecialchars($project['title']) ?></div>
                        <span class="badge badge-<?= $project['status'] ?>"><?= ucfirst($project['status']) ?></span>
                    </div>
                    <div class="project-meta">
                        Freelancer: <?= $project['freelancer_name'] ? htmlspecialchars($project['freelancer_name']) : 'Not assigned' ?>
                        &nbsp;·&nbsp; Budget: Rs. <?= number_format($project['total_budget'], 2) ?>
                        <?php if($under_review_count > 0): ?>
                            &nbsp;·&nbsp; <span style="color:#92400E;font-weight:500">⚠ <?= $under_review_count ?> milestone(s) waiting for your approval</span>
                        <?php endif; ?>
                    </div>

                    <?php foreach($milestones as $m): ?>

                        <?php if($m['status'] == 'under_review'): ?>
                            <!-- Highlight milestones under review -->
                            <div class="review-highlight">
                                <div class="review-tag"> Waiting for your approval</div>
                                <div class="milestone-row" style="padding:0;border:none">
                                    <div class="ms-num"><?= $m['order_number'] ?></div>
                                    <div class="ms-info">
                                        <div class="ms-title"><?= htmlspecialchars($m['title']) ?></div>
                                        <div class="ms-meta">
                                            Rs. <?= number_format($m['amount'], 2) ?>
                                            &nbsp;·&nbsp; Due: <?= date('d M Y', strtotime($m['due_date'])) ?>
                                            <?php if($m['payment_status'] == 'deposited'): ?>
                                                <span class="payment-badge">💰 Deposited</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="ms-actions">
                                        <span class="badge badge-under-review">Under Review</span>
                                        <form method="POST" action="approve_milestone.php">
                                            <input type="hidden" name="milestone_id" value="<?= $m['id'] ?>"/>
                                            <input type="hidden" name="project_id" value="<?= $project['id'] ?>"/>
                                            <button type="submit" name="approve_milestone" class="btn-approve">✓ Approve Milestone</button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                        <?php else: ?>
                            <!-- Normal milestone row -->
                            <div class="milestone-row">
                                <div class="ms-num <?= $m['is_locked'] ? 'locked' : '' ?>">
                                    <?= $m['is_locked'] ? '🔒' : $m['order_number'] ?>
                                </div>
                                <div class="ms-info">
                                    <div class="ms-title <?= $m['is_locked'] ? 'locked' : '' ?>">
                                        <?= htmlspecialchars($m['title']) ?>
                                        <?php if($m['payment_status'] == 'released'): ?>
                                            <span class="payment-badge">✓ Released</span>
                                        <?php elseif($m['payment_status'] == 'deposited'): ?>
                                            <span class="payment-badge">💰 Deposited</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ms-meta">
                                        Rs. <?= number_format($m['amount'], 2) ?>
                                        &nbsp;·&nbsp; Due: <?= date('d M Y', strtotime($m['due_date'])) ?>
                                    </div>
                                </div>
                                <div class="ms-actions">
                                    <?php if($m['is_locked']): ?>
                                        <span class="badge badge-locked">🔒 Locked</span>
                                        <div class="waiting-msg">Previous milestone must be approved</div>
                                    <?php elseif($m['status'] == 'pending'): ?>
                                        <span class="badge badge-pending">Pending</span>
                                        <div class="waiting-msg">Waiting for deposit</div>
                                    <?php elseif($m['status'] == 'deposited'): ?>
                                        <span class="badge badge-deposited">Deposited</span>
                                        <div class="waiting-msg">Freelancer starting work</div>
                                    <?php elseif($m['status'] == 'in_progress'): ?>
                                        <span class="badge badge-in-progress">In Progress</span>
                                        <div class="waiting-msg">Freelancer is working</div>
                                    <?php elseif($m['status'] == 'approved'): ?>
                                        <span class="badge badge-approved">✓ Approved</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                    <?php endforeach; ?>
                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>
</div>

</body>
</html>