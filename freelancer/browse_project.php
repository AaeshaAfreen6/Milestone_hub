<?php
include '../includes/db.php';
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'freelancer'){
    header("Location: ../index.php");
    exit();
}

$freelancer_id = $_SESSION['user_id'];

// Get all open projects
$stmt = $pdo->prepare("
    SELECT p.*, u.name as client_name,
    (SELECT COUNT(*) FROM proposals WHERE project_id = p.id) as proposal_count
    FROM projects p
    JOIN users u ON p.client_id = u.id
    WHERE p.status = 'open'
    ORDER BY p.created_at DESC
");
$stmt->execute();
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Browse Projects - Milestone Hub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .project-card {
            background: #fff;
            border: 0.5px solid #e8e6e6;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
        }

        .project-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .project-title {
            font-size: 16px;
            font-weight: 500;
            color: #081c15;
            margin-bottom: 4px;
        }

        .project-client {
            font-size: 13px;
            color: #888780;
        }

        .project-desc {
            font-size: 13px;
            color: #555;
            margin-bottom: 14px;
            line-height: 1.6;
        }

        .project-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 14px;
        }

        .meta-item {
            font-size: 12px;
            color: #888780;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .meta-item span {
            font-weight: 500;
            color: #081c15;
        }

        .milestones-preview {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
        }

        .ms-pill {
            background: #d8f3dc;
            color: #1b4332;
            font-size: 11px;
            font-weight: 500;
            padding: 4px 12px;
            border-radius: 20px;
        }

        .project-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 14px;
            border-top: 0.5px solid #f0eeee;
        }

        .btn-proposal {
            padding: 8px 20px;
            background: linear-gradient(135deg, #2d6a4f, #1b4332);
            border: none;
            border-radius: 8px;
            color: #fff;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            font-family: inherit;
        }

        .btn-proposal:hover { opacity: 0.9; }

        .already-sent {
            display: inline-block;
            background: #d8f3dc;
            color: #1b4332;
            font-size: 12px;
            font-weight: 500;
            padding: 6px 16px;
            border-radius: 8px;
        }

        .badge-open {
            background: #d8f3dc;
            color: #1b4332;
            font-size: 11px;
            font-weight: 500;
            padding: 4px 12px;
            border-radius: 20px;
        }

        .empty {
            text-align: center;
            padding: 60px;
            color: #888780;
            font-size: 14px;
            background: #fff;
            border-radius: 12px;
            border: 0.5px dashed #e0e0e0;
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
        <a href="browse_project.php" class="nav-item active">Browse projects</a>
        <a href="update_milestone.php" class="nav-item">My milestones</a>
         <a href="../landing.php" class="nav-item">← Home</a>
    </div>

    <!-- MAIN -->
    <div class="main">
        <h2 class="page-title">Browse Open Projects</h2>

        <?php if(count($projects) == 0): ?>
            <div class="empty">
                No open projects available right now. Check back later!
            </div>
        <?php else: ?>
            <?php foreach($projects as $project): ?>

                <?php
                // Check if freelancer already sent proposal
                $check = $pdo->prepare("SELECT id FROM proposals WHERE project_id = ? AND freelancer_id = ?");
                $check->execute([$project['id'], $freelancer_id]);
                $already_sent = $check->rowCount() > 0;

                // Get milestones
                $ms = $pdo->prepare("SELECT title FROM milestones WHERE project_id = ? ORDER BY order_number");
                $ms->execute([$project['id']]);
                $milestones = $ms->fetchAll(PDO::FETCH_ASSOC);
                ?>

                <div class="project-card">
                    <div class="project-head">
                        <div>
                            <div class="project-title"><?= htmlspecialchars($project['title']) ?></div>
                            <div class="project-client">Posted by: <?= htmlspecialchars($project['client_name']) ?></div>
                        </div>
                        <span class="badge-open">Open</span>
                    </div>

                    <div class="project-desc">
                        <?= htmlspecialchars(substr($project['description'], 0, 150)) ?>...
                    </div>

                    <div class="project-meta">
                        <div class="meta-item">Budget: <span>Rs. <?= number_format($project['total_budget'], 2) ?></span></div>
                        <div class="meta-item">Proposals: <span><?= $project['proposal_count'] ?></span></div>
                        <div class="meta-item">Posted: <span><?= date('d M Y', strtotime($project['created_at'])) ?></span></div>
                    </div>

                    <div class="milestones-preview">
                        <?php foreach($milestones as $m): ?>
                            <span class="ms-pill"><?= htmlspecialchars($m['title']) ?></span>
                        <?php endforeach; ?>
                    </div>

                    <div class="project-footer">
                        <span style="font-size:12px;color:#888780"><?= $project['proposal_count'] ?> proposal(s) received</span>
                        <?php if($already_sent): ?>
                            <span class="already-sent">✓ Proposal sent</span>
                        <?php else: ?>
                            <a href="send_proposal.php?project_id=<?= $project['id'] ?>" class="btn-proposal">Send Proposal</a>
                        <?php endif; ?>
                    </div>
                </div>

            <?php endforeach; ?>
        <?php endif; ?>

    </div>
</div>

</body>
</html>