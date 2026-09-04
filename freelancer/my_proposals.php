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

// Get all proposals by this freelancer
$stmt = $pdo->prepare("
    SELECT 
        pr.*,
        p.title as project_title,
        p.description as project_description,
        p.total_budget,
        p.status as project_status,
        u.name as client_name
    FROM proposals pr
    JOIN projects p ON pr.project_id = p.id
    JOIN users u ON p.client_id = u.id
    WHERE pr.freelancer_id = ?
    ORDER BY pr.created_at DESC
");
$stmt->execute([$freelancer_id]);
$proposals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count stats
$total    = count($proposals);
$pending  = 0;
$accepted = 0;
$rejected = 0;

foreach($proposals as $p){
    if($p['status'] == 'pending')  $pending++;
    if($p['status'] == 'accepted') $accepted++;
    if($p['status'] == 'rejected') $rejected++;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Proposals - Milestone Hub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 24px;
        }

        .stat {
            background: #fff;
            border-radius: 10px;
            padding: 16px;
            border: 0.5px solid #e8e6e6;
        }

        .stat-label { font-size: 12px; color: #888780; margin-bottom: 6px; }
        .stat-value { font-size: 26px; font-weight: 600; color: #47928e; }

        .proposal-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            border: 0.5px solid #e8e6e6;
            transition: box-shadow 0.2s;
        }

        .proposal-card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
        }

        .proposal-card.accepted {
            border-left: 4px solid #47928e;
        }

        .proposal-card.rejected {
            border-left: 4px solid #B91C1C;
            opacity: 0.8;
        }

        .proposal-card.pending {
            border-left: 4px solid #92400E;
        }

        .card-head {
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

        .badge {
            display: inline-block;
            font-size: 11px;
            font-weight: 500;
            padding: 4px 12px;
            border-radius: 20px;
            flex-shrink: 0;
        }

        .badge-pending  { background: #FEF3C7; color: #92400E; }
        .badge-accepted { background: #e8f5f5; color: #316461; }
        .badge-rejected { background: #FEE2E2; color: #B91C1C; }
        .badge-open      { background: #e8f5f5; color: #316461; }
        .badge-active    { background: #E1F5EE; color: #0F6E56; }
        .badge-completed { background: #F1EFE8; color: #5F5E5A; }

        .proposal-message {
            font-size: 13px;
            color: #555;
            line-height: 1.6;
            background: #f8f8f8;
            padding: 10px 14px;
            border-radius: 8px;
            margin-bottom: 12px;
        }

        .proposal-meta {
            display: flex;
            gap: 20px;
            font-size: 12px;
            color: #888780;
            margin-bottom: 12px;
        }

        .proposal-meta span {
            font-weight: 500;
            color: #444;
        }

        .card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 12px;
            border-top: 0.5px solid #f0eeee;
        }

        .attachment-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: #47928e;
            background: #e8f5f5;
            padding: 5px 12px;
            border-radius: 6px;
            text-decoration: none;
        }

        .btn-browse {
            padding: 7px 16px;
            background: linear-gradient(135deg, #47928e, #316461);
            border: none;
            border-radius: 8px;
            color: #fff;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            font-family: inherit;
        }

        .accepted-msg {
            background: #e8f5f5;
            color: #316461;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .rejected-msg {
            background: #FEE2E2;
            color: #B91C1C;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
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

        .empty a {
            color: #47928e;
            text-decoration: none;
            font-weight: 500;
        }

        .date { font-size: 12px; color: #B4B2A9; }
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
        <a href="browse_project.php" class="nav-item">Browse projects</a>
        <a href="my_proposals.php" class="nav-item active">My proposals</a>
        <a href="update_milestone.php" class="nav-item">My milestones</a>
    </div>

    <!-- MAIN -->
    <div class="main">
        <h2 class="page-title">My Proposals</h2>

        <!-- STATS -->
        <div class="stats">
            <div class="stat">
                <div class="stat-label">📋 Total Sent</div>
                <div class="stat-value"><?= $total ?></div>
            </div>
            <div class="stat">
                <div class="stat-label">⏳ Pending</div>
                <div class="stat-value" style="color:#92400E"><?= $pending ?></div>
            </div>
            <div class="stat">
                <div class="stat-label">✅ Accepted</div>
                <div class="stat-value" style="color:#316461"><?= $accepted ?></div>
            </div>
            <div class="stat">
                <div class="stat-label">❌ Rejected</div>
                <div class="stat-value" style="color:#B91C1C"><?= $rejected ?></div>
            </div>
        </div>

        <!-- PROPOSALS LIST -->
        <?php if(count($proposals) == 0): ?>
            <div class="empty">
                You have not sent any proposals yet.<br><br>
                <a href="browse_project.php">Browse open projects →</a>
            </div>
        <?php else: ?>

            <?php foreach($proposals as $proposal): ?>
                <div class="proposal-card <?= $proposal['status'] ?>">

                    <div class="card-head">
                        <div>
                            <div class="project-title"><?= htmlspecialchars($proposal['project_title']) ?></div>
                            <div class="project-client">Client: <?= htmlspecialchars($proposal['client_name']) ?></div>
                        </div>
                        <span class="badge badge-<?= $proposal['status'] ?>"><?= ucfirst($proposal['status']) ?></span>
                    </div>

                    <div class="proposal-meta">
                        <div>Budget: <span>Rs. <?= number_format($proposal['total_budget'], 2) ?></span></div>
                        <div>Project status: <span><?= ucfirst($proposal['project_status']) ?></span></div>
                    </div>

                    <div class="proposal-message">
                        "<?= htmlspecialchars($proposal['message']) ?>"
                    </div>

                    <?php if($proposal['attachment']): ?>
                        <a href="../uploads/<?= $proposal['attachment'] ?>" target="_blank" class="attachment-link">
                            📎 View attachment
                        </a>
                    <?php endif; ?>

                    <?php if($proposal['status'] == 'accepted'): ?>
                        <div class="accepted-msg">
                            🎉 Your proposal was accepted! Go to My Milestones to start working.
                        </div>
                    <?php elseif($proposal['status'] == 'rejected'): ?>
                        <div class="rejected-msg">
                            ❌ Your proposal was not accepted this time. Keep applying!
                        </div>
                    <?php endif; ?>

                    <div class="card-footer">
                        <span class="date">Submitted: <?= date('d M Y h:i A', strtotime($proposal['created_at'])) ?></span>
                        <?php if($proposal['status'] == 'accepted'): ?>
                            <a href="update_milestone.php" class="btn-browse">Go to milestones →</a>
                        <?php elseif($proposal['status'] == 'pending'): ?>
                            <span style="font-size:12px;color:#92400E;font-style:italic">⏳ Waiting for client response</span>
                        <?php else: ?>
                            <a href="browse_project.php" class="btn-browse">Browse more projects</a>
                        <?php endif; ?>
                    </div>

                </div>
            <?php endforeach; ?>

        <?php endif; ?>

    </div>
</div>

</body>
</html>