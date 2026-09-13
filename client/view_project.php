<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../includes/db.php';
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'client'){
    header("Location: ../index.php");
    exit();
}

$client_id  = $_SESSION['user_id'];
$project_id = $_GET['id'] ?? null;
$success    = "";
$error      = "";

if(!$project_id){ header("Location: my_projects.php"); exit(); }

$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ? AND client_id = ?");
$stmt->execute([$project_id, $client_id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$project){ header("Location: my_projects.php"); exit(); }

// Accept freelancer
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accept_freelancer'])){
    $proposal_id   = $_POST['proposal_id'];
    $freelancer_id = $_POST['freelancer_id'];
    $pdo->prepare("UPDATE proposals SET status='accepted' WHERE id=?")->execute([$proposal_id]);
    $pdo->prepare("UPDATE proposals SET status='rejected' WHERE project_id=? AND id!=?")->execute([$project_id,$proposal_id]);
    $pdo->prepare("UPDATE projects SET freelancer_id=?,status='active' WHERE id=?")->execute([$freelancer_id,$project_id]);
    $pdo->prepare("UPDATE milestones SET is_locked=0 WHERE project_id=? AND order_number=1")->execute([$project_id]);
    $stmt2 = $pdo->prepare("SELECT id FROM milestones WHERE project_id=? AND order_number=1");
    $stmt2->execute([$project_id]);
    $m1 = $stmt2->fetch(PDO::FETCH_ASSOC);
    if($m1){ $pdo->prepare("INSERT INTO milestone_logs (milestone_id,changed_by,old_status,new_status,note) VALUES(?,?,'pending','pending','Freelancer accepted, project activated')")->execute([$m1['id'],$client_id]); }
    $success = "Freelancer accepted! Project is now active.";
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id=?"); $stmt->execute([$project_id]); $project = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Deposit milestone
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['deposit_milestone'])){
    $mid = $_POST['milestone_id'];
    $pdo->prepare("UPDATE milestones SET status='deposited',payment_status='deposited' WHERE id=?")->execute([$mid]);
    $pdo->prepare("INSERT INTO milestone_logs (milestone_id,changed_by,old_status,new_status,note) VALUES(?,?,'pending','deposited','Funds deposited by client')")->execute([$mid,$client_id]);
    $success = "Milestone marked as deposited!";
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id=?"); $stmt->execute([$project_id]); $project = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Approve milestone
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['approve_milestone'])){
    $mid = $_POST['milestone_id'];
    $pdo->prepare("UPDATE milestones SET status='approved',payment_status='released' WHERE id=?")->execute([$mid]);
    $stmt2 = $pdo->prepare("SELECT order_number FROM milestones WHERE id=?"); $stmt2->execute([$mid]); $cur = $stmt2->fetch(PDO::FETCH_ASSOC);
    $pdo->prepare("UPDATE milestones SET is_locked=0 WHERE project_id=? AND order_number=?")->execute([$project_id,$cur['order_number']+1]);
    $pdo->prepare("INSERT INTO milestone_logs (milestone_id,changed_by,old_status,new_status,note) VALUES(?,?,'under_review','approved','Approved by client')")->execute([$mid,$client_id]);
    $stmt2 = $pdo->prepare("SELECT COUNT(*) as t, SUM(status='approved') as d FROM milestones WHERE project_id=?"); $stmt2->execute([$project_id]); $mc = $stmt2->fetch(PDO::FETCH_ASSOC);
    if($mc['t']==$mc['d']){ $pdo->prepare("UPDATE projects SET status='completed' WHERE id=?")->execute([$project_id]); $success = "🎉 All milestones approved! Project completed!"; }
    else { $success = "Milestone approved! Next milestone unlocked."; }
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id=?"); $stmt->execute([$project_id]); $project = $stmt->fetch(PDO::FETCH_ASSOC);
}

$stmt = $pdo->prepare("SELECT pr.*,u.name as freelancer_name,u.email as freelancer_email FROM proposals pr JOIN users u ON pr.freelancer_id=u.id WHERE pr.project_id=? ORDER BY pr.created_at DESC");
$stmt->execute([$project_id]);
$proposals = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM milestones WHERE project_id=? ORDER BY order_number");
$stmt->execute([$project_id]);
$milestones = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Project - Milestone Hub</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f0f4f0; color: #1a1a2e; }

        .topbar { background: linear-gradient(135deg, #1a3533, #316461); padding: 0 28px; height: 60px; display: flex; align-items: center; justify-content: space-between; position: fixed; top: 0; left: 0; right: 0; z-index: 100; box-shadow: 0 2px 20px rgba(0,0,0,0.15); }
        .topbar-left { display: flex; align-items: center; gap: 10px; }
        .logo-icon { width: 34px; height: 34px; border-radius: 8px; background: rgba(255,255,255,0.15); display: flex; align-items: center; justify-content: center; font-size: 16px; color: #fff; }
        .logo-text { font-size: 16px; font-weight: 600; color: #fff; }
        .logo-sub  { font-size: 10px; color: rgba(255,255,255,0.6); }
        .topbar-right { display: flex; align-items: center; gap: 16px; }
        .user-name { font-size: 13px; font-weight: 500; color: #fff; }
        .user-role { font-size: 11px; color: rgba(255,255,255,0.6); }
        .user-avatar { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #47928e, #95d5b2); display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 600; color: #fff; }
        .btn-logout { padding: 7px 14px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: #fff; font-size: 12px; text-decoration: none; }

        .sidebar { width: 220px; background: #fff; position: fixed; top: 60px; left: 0; bottom: 0; padding: 24px 0; overflow-y: auto; box-shadow: 2px 0 20px rgba(0,0,0,0.05); }
        .sidebar-section { padding: 0 16px; margin-bottom: 8px; }
        .sidebar-label { font-size: 10px; font-weight: 600; color: #aaa; text-transform: uppercase; letter-spacing: 1px; padding: 0 8px; margin-bottom: 6px; margin-top: 16px; }
        .nav-item { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 10px; font-size: 13px; color: #666; text-decoration: none; margin-bottom: 2px; }
        .nav-item:hover { background: #f0f9f7; color: #316461; }
        .nav-item.active { background: linear-gradient(135deg, #e8f5f5, #d0eeec); color: #316461; font-weight: 500; }
        .nav-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 15px; background: #f5f5f5; flex-shrink: 0; }
        .nav-item.active .nav-icon { background: linear-gradient(135deg, #47928e, #316461); color: #fff; }

        .main { margin-left: 220px; margin-top: 60px; padding: 28px; min-height: calc(100vh - 60px); }

        /* PROJECT BANNER */
        .project-banner {
            background: linear-gradient(135deg, #316461, #47928e);
            border-radius: 16px;
            padding: 24px 28px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        .project-banner::before {
            content: '';
            position: absolute;
            width: 180px; height: 180px;
            border-radius: 50%;
            background: rgba(255,255,255,0.06);
            top: -60px; right: 80px;
        }

        .project-banner-info { position: relative; z-index: 1; }
        .project-banner-title { font-size: 20px; font-weight: 700; color: #fff; margin-bottom: 6px; }
        .project-banner-desc  { font-size: 13px; color: rgba(255,255,255,0.8); margin-bottom: 12px; max-width: 500px; }
        .project-banner-meta  { display: flex; gap: 20px; }
        .meta-item { font-size: 12px; color: rgba(255,255,255,0.7); }
        .meta-item strong { color: #fff; }

        .badge {
            display: inline-block; font-size: 12px; font-weight: 500;
            padding: 6px 16px; border-radius: 20px;
        }
        .badge-open      { background: rgba(255,255,255,0.2); color: #fff; }
        .badge-active    { background: rgba(255,255,255,0.2); color: #fff; }
        .badge-completed { background: rgba(255,255,255,0.2); color: #fff; }

        /* GRID */
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

        /* CARDS */
        .card {
            background: #fff;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
        }

        .card-title {
            font-size: 15px; font-weight: 600; color: #1a3533;
            margin-bottom: 16px; padding-bottom: 12px;
            border-bottom: 1.5px solid #f0f0f0;
            display: flex; align-items: center; justify-content: space-between;
        }

        /* PROPOSALS */
        .proposal-item {
            padding: 14px;
            border-radius: 12px;
            background: #f8fffe;
            border: 1px solid #e0f0ee;
            margin-bottom: 12px;
        }

        .proposal-item:last-child { margin-bottom: 0; }

        .proposal-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .proposal-name  { font-size: 14px; font-weight: 600; color: #1a3533; }
        .proposal-email { font-size: 12px; color: #888; }

        .proposal-msg {
            font-size: 13px; color: #555; line-height: 1.6;
            background: #fff; padding: 10px 12px; border-radius: 8px;
            border: 1px solid #f0f0f0; margin-bottom: 10px;
        }

        .proposal-footer {
            display: flex; align-items: center; justify-content: space-between;
        }

        .badge-pending  { background: #FEF3C7; color: #92400E; }
        .badge-accepted { background: #e8f5f5; color: #316461; }
        .badge-rejected { background: #FEE2E2; color: #B91C1C; }

        .btn-accept {
            padding: 7px 18px;
            background: linear-gradient(135deg, #47928e, #316461);
            border: none; border-radius: 8px; color: #fff;
            font-size: 12px; font-weight: 500; cursor: pointer; font-family: inherit;
        }

        /* MILESTONES */
        .milestone-item {
            padding: 16px;
            border-radius: 12px;
            border: 1.5px solid #e8e8e8;
            margin-bottom: 12px;
            position: relative;
            transition: all 0.2s;
        }

        .milestone-item:last-child { margin-bottom: 0; }
        .milestone-item.under-review { border-color: #534AB7; background: #faf9ff; }
        .milestone-item.approved     { border-color: #47928e; background: #f8fffe; }
        .milestone-item.locked       { opacity: 0.6; }

        .ms-head {
            display: flex; align-items: center;
            justify-content: space-between; margin-bottom: 8px;
        }

        .ms-left { display: flex; align-items: center; gap: 10px; }

        .ms-num {
            width: 30px; height: 30px; border-radius: 50%;
            background: linear-gradient(135deg, #47928e, #316461);
            color: #fff; display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 600; flex-shrink: 0;
        }

        .ms-num.locked-num { background: #ddd; color: #888; }

        .ms-title  { font-size: 14px; font-weight: 600; color: #1a3533; }
        .ms-amount { font-size: 12px; color: #47928e; font-weight: 500; }
        .ms-date   { font-size: 11px; color: #aaa; }

        .ms-badge {
            display: inline-block; font-size: 11px; font-weight: 500;
            padding: 4px 10px; border-radius: 20px;
        }

        .ms-badge-pending      { background: #F1EFE8; color: #888; }
        .ms-badge-deposited    { background: #EBF5FB; color: #185FA5; }
        .ms-badge-in-progress  { background: #FEF3C7; color: #92400E; }
        .ms-badge-under-review { background: #EEEDFE; color: #534AB7; }
        .ms-badge-approved     { background: #e8f5f5; color: #316461; }
        .ms-badge-locked       { background: #F1EFE8; color: #aaa; }

        /* Submission box */
        .submission-box {
            background: #f8f7ff;
            border: 1px solid #e0deff;
            border-radius: 10px;
            padding: 14px;
            margin-top: 12px;
        }

        .submission-title {
            font-size: 12px; font-weight: 600; color: #534AB7;
            margin-bottom: 10px; display: flex; align-items: center; gap: 6px;
        }

        .submission-note {
            font-size: 13px; color: #444; line-height: 1.6; margin-bottom: 10px;
        }

        .submission-links { display: flex; gap: 8px; flex-wrap: wrap; }

        .sub-link {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 12px; padding: 5px 12px; border-radius: 6px;
            text-decoration: none; font-weight: 500;
        }

        .sub-link.file { background: #e8f5f5; color: #316461; }
        .sub-link.url  { background: #EBF5FB; color: #185FA5; }

        /* Action buttons */
        .ms-actions { margin-top: 12px; display: flex; gap: 8px; flex-wrap: wrap; }

        .btn-approve {
            padding: 8px 18px;
            background: linear-gradient(135deg, #47928e, #316461);
            border: none; border-radius: 8px; color: #fff;
            font-size: 12px; font-weight: 500; cursor: pointer; font-family: inherit;
        }

        .btn-pay {
            padding: 8px 18px;
            background: linear-gradient(135deg, #5C2D91, #4a2475);
            border: none; border-radius: 8px; color: #fff;
            font-size: 12px; font-weight: 500; text-decoration: none;
            display: inline-block;
        }

        .btn-deposit {
            padding: 8px 18px;
            background: #185FA5;
            border: none; border-radius: 8px; color: #fff;
            font-size: 12px; font-weight: 500; cursor: pointer; font-family: inherit;
        }

        .waiting-msg { font-size: 12px; color: #aaa; font-style: italic; margin-top: 6px; }

        /* Payment badge */
        .payment-badge {
            display: inline-flex; align-items: center; gap: 4px;
            font-size: 11px; padding: 3px 8px; border-radius: 20px;
            background: #e8f5f5; color: #316461; font-weight: 500;
            margin-left: 6px;
        }

        /* Alerts */
        .alert { padding: 12px 16px; border-radius: 10px; font-size: 13px; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
        .alert-error   { background: #FEE2E2; color: #B91C1C; }
        .alert-success { background: #e8f5f5; color: #316461; }

        .empty-proposals { text-align: center; padding: 30px; color: #aaa; font-size: 13px; }
    </style>
</head>
<body>

<div class="topbar">
    <div class="topbar-left">
        <div class="logo-icon">⊞</div>
        <div>
            <div class="logo-text">Milestone Hub</div>
            <div class="logo-sub">Project Management System</div>
        </div>
    </div>
    <div class="topbar-right">
        <div>
            <div class="user-name"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
            <div class="user-role">Client</div>
        </div>
        <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
        <a href="../logout.php" class="btn-logout">Logout</a>
    </div>
</div>

<div class="sidebar">
    <div class="sidebar-section">
        <div class="sidebar-label">Main Menu</div>
        <a href="dashboard.php" class="nav-item"><div class="nav-icon">⊞</div> Dashboard</a>
        <a href="create_project.php" class="nav-item"><div class="nav-icon">⊕</div> Create project</a>
        <a href="my_projects.php" class="nav-item active"><div class="nav-icon">▤</div> My projects</a>
        <a href="approve_milestone.php" class="nav-item"><div class="nav-icon">✓</div> Approve milestones</a>
    </div>
    <div class="sidebar-section">
        <div class="sidebar-label">Account</div>
        <a href="../logout.php" class="nav-item"><div class="nav-icon">⇥</div> Logout</a>
    </div>
</div>

<div class="main">

    <!-- Project Banner -->
    <div class="project-banner">
        <div class="project-banner-info">
            <div class="project-banner-title"><?= htmlspecialchars($project['title']) ?></div>
            <div class="project-banner-desc"><?= htmlspecialchars($project['description']) ?></div>
            <div class="project-banner-meta">
                <div class="meta-item">Budget: <strong>Rs. <?= number_format($project['total_budget'], 2) ?></strong></div>
                <div class="meta-item">Created: <strong><?= date('d M Y', strtotime($project['created_at'])) ?></strong></div>
            </div>
        </div>
        <span class="badge badge-<?= $project['status'] ?>"><?= ucfirst($project['status']) ?></span>
    </div>

    <?php if($error): ?>   <div class="alert alert-error">⚠ <?= $error ?></div>   <?php endif; ?>
    <?php if($success): ?> <div class="alert alert-success">✅ <?= $success ?></div> <?php endif; ?>

    <div class="grid-2">

        <!-- PROPOSALS -->
        <div class="card">
            <div class="card-title">
                📋 Proposals Received
                <span style="font-size:12px;color:#888;font-weight:400"><?= count($proposals) ?> total</span>
            </div>

            <?php if(count($proposals) == 0): ?>
                <div class="empty-proposals">No proposals received yet.</div>
            <?php else: ?>
                <?php foreach($proposals as $proposal): ?>
                    <div class="proposal-item">
                        <div class="proposal-head">
                            <div>
                                <div class="proposal-name"><?= htmlspecialchars($proposal['freelancer_name']) ?></div>
                                <div class="proposal-email"><?= htmlspecialchars($proposal['freelancer_email']) ?></div>
                            </div>
                            <span class="badge badge-<?= $proposal['status'] ?>"><?= ucfirst($proposal['status']) ?></span>
                        </div>

                        <div class="proposal-msg"><?= htmlspecialchars($proposal['message']) ?></div>

                        <?php if($proposal['attachment']): ?>
                            <a href="../uploads/<?= $proposal['attachment'] ?>" target="_blank"
                               style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:#316461;background:#e8f5f5;padding:5px 12px;border-radius:6px;text-decoration:none;margin-bottom:10px">
                               📎 View attachment
                            </a>
                        <?php endif; ?>

                        <div class="proposal-footer">
                            <span style="font-size:11px;color:#aaa"><?= date('d M Y', strtotime($proposal['created_at'])) ?></span>
                            <?php if($proposal['status'] == 'pending' && $project['status'] == 'open'): ?>
                                <form method="POST" action="view_project.php?id=<?= $project_id ?>">
                                    <input type="hidden" name="proposal_id"   value="<?= $proposal['id'] ?>"/>
                                    <input type="hidden" name="freelancer_id" value="<?= $proposal['freelancer_id'] ?>"/>
                                    <button type="submit" name="accept_freelancer" class="btn-accept">✓ Accept</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- MILESTONES -->
        <div class="card">
            <div class="card-title">🎯 Milestones</div>

            <?php foreach($milestones as $m): ?>
                <div class="milestone-item <?= $m['is_locked'] ? 'locked' : ($m['status'] == 'under_review' ? 'under-review' : ($m['status'] == 'approved' ? 'approved' : '')) ?>">

                    <div class="ms-head">
                        <div class="ms-left">
                            <div class="ms-num <?= $m['is_locked'] ? 'locked-num' : '' ?>">
                                <?= $m['is_locked'] ? '⛉' : $m['order_number'] ?>
                            </div>
                            <div>
                                <div class="ms-title">
                                    <?= htmlspecialchars($m['title']) ?>
                                    <?php if($m['payment_status'] == 'released'): ?>
                                        <span class="payment-badge">✓ Released</span>
                                    <?php elseif($m['payment_status'] == 'deposited'): ?>
                                        <span class="payment-badge">💰 Deposited</span>
                                    <?php endif; ?>
                                </div>
                                <div style="display:flex;gap:10px;margin-top:2px">
                                    <span class="ms-amount">Rs. <?= number_format($m['amount'], 2) ?></span>
                                    <span class="ms-date">Due: <?= date('d M Y', strtotime($m['due_date'])) ?></span>
                                </div>
                            </div>
                        </div>

                        <?php if($m['is_locked']): ?>
                            <span class="ms-badge ms-badge-locked">⛉ Locked</span>
                        <?php else: ?>
                            <span class="ms-badge ms-badge-<?= str_replace('_','-',$m['status']) ?>"><?= ucfirst(str_replace('_',' ',$m['status'])) ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Submission box -->
                    <?php if($m['status'] == 'under_review' && ($m['submission_note'] || $m['submission_file'] || $m['submission_link'])): ?>
                        <div class="submission-box">
                            <div class="submission-title">📦 Freelancer Submission</div>
                            <?php if($m['submission_note']): ?>
                                <div class="submission-note"><strong>Description:</strong> <?= htmlspecialchars($m['submission_note']) ?></div>
                            <?php endif; ?>
                            <div class="submission-links">
                                <?php if($m['submission_file']): ?>
                                    <a href="../uploads/<?= $m['submission_file'] ?>" target="_blank" class="sub-link file">📎 View file</a>
                                <?php endif; ?>
                                <?php if($m['submission_link']): ?>
                                    <a href="<?= htmlspecialchars($m['submission_link']) ?>" target="_blank" class="sub-link url">🔗 View link</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Action buttons -->
                    <?php if(!$m['is_locked']): ?>
                        <div class="ms-actions">
                            <?php if($m['status'] == 'pending' && $project['status'] == 'active'): ?>
                                <a href="khalti_pay.php?milestone_id=<?= $m['id'] ?>&project_id=<?= $project_id ?>" class="btn-pay">💜 Make Payment</a>
                            <?php elseif($m['status'] == 'under_review'): ?>
                                <form method="POST" action="view_project.php?id=<?= $project_id ?>">
                                    <input type="hidden" name="milestone_id" value="<?= $m['id'] ?>"/>
                                    <button type="submit" name="approve_milestone" class="btn-approve">✓ Approve Milestone</button>
                                </form>
                            <?php elseif($m['status'] == 'deposited'): ?>
                                <div class="waiting-msg">⏳ Waiting for freelancer to start work</div>
                            <?php elseif($m['status'] == 'in_progress'): ?>
                                <div class="waiting-msg">⚙ Freelancer is working on this milestone</div>
                            <?php elseif($m['status'] == 'approved'): ?>
                                <div style="font-size:12px;color:#316461;font-weight:500">✓Completed and approved</div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="waiting-msg">⛉ Unlock by approving previous milestone</div>
                    <?php endif; ?>

                </div>
            <?php endforeach; ?>
        </div>

    </div>
</div>

</body>
</html>