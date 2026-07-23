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

    // Check if already sent
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
        .proposal-card {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            border: 0.5px solid #e8e6e6;
            max-width: 700px;
        }

        .project-info {
            background: #d8f3dc;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 20px;
        }

        .project-info h3 {
            font-size: 15px;
            font-weight: 500;
            color: #1b4332;
            margin-bottom: 6px;
        }

        .project-info p {
            font-size: 13px;
            color: #2d6a4f;
        }

        .milestones-list {
            margin-bottom: 20px;
        }

        .ms-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 0;
            border-bottom: 0.5px solid #f0eeee;
            font-size: 13px;
            color: #444;
        }

        .ms-num {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #2d6a4f;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 600;
            flex-shrink: 0;
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

        .btn-submit {
            padding: 12px 32px;
            background: linear-gradient(135deg, #2d6a4f, #1b4332);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            font-family: inherit;
            margin-top: 8px;
        }

        .btn-back {
            padding: 12px 20px;
            background: transparent;
            border: 1.5px solid #e8e6e6;
            border-radius: 10px;
            color: #444;
            font-size: 14px;
            cursor: pointer;
            font-family: inherit;
            text-decoration: none;
            margin-right: 10px;
        }

        .alert {
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 16px;
        }

        .alert-error   { background: #FEE2E2; color: #B91C1C; }
        .alert-success { background: #d8f3dc; color: #1b4332; }
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
        <h2 class="page-title">Send Proposal</h2>

        <?php if($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="alert alert-success">
                <?= $success ?> <a href="browse_project.php" style="color:#1b4332;font-weight:600">Back to projects →</a>
            </div>
        <?php endif; ?>

        <div class="proposal-card">

            <!-- Project info -->
            <div class="project-info">
                <h3><?= htmlspecialchars($project['title']) ?></h3>
                <p>Client: <?= htmlspecialchars($project['client_name']) ?> &nbsp;·&nbsp; Budget: Rs. <?= number_format($project['total_budget'], 2) ?></p>
            </div>

            <!-- Milestones -->
            <p style="font-size:13px;font-weight:500;color:#444;margin-bottom:10px">Project milestones:</p>
            <div class="milestones-list">
                <?php foreach($milestones as $m): ?>
                    <div class="ms-item">
                        <div class="ms-num"><?= $m['order_number'] ?></div>
                        <div>
                            <strong><?= htmlspecialchars($m['title']) ?></strong>
                            &nbsp;·&nbsp; Rs. <?= number_format($m['amount'], 2) ?>
                            &nbsp;·&nbsp; Due: <?= date('d M Y', strtotime($m['due_date'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Proposal form -->
            <form method="POST">
                <div class="field" style="margin-bottom:20px">
                    <label>Your proposal message</label>
                    <textarea name="message" placeholder="Write why you are the right person for this project. Mention your experience, timeline, and approach..." required></textarea>
                </div>
                <a href="browse_project.php" class="btn-back">Cancel</a>
                <button type="submit" class="btn-submit">Send Proposal</button>
            </form>

        </div>
    </div>
</div>

</body>
</html>