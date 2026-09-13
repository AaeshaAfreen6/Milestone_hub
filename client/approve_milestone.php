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

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['approve_milestone'])){
    $milestone_id = $_POST['milestone_id'];
    $project_id   = $_POST['project_id'];

    $stmt = $pdo->prepare("SELECT m.* FROM milestones m JOIN projects p ON m.project_id=p.id WHERE m.id=? AND p.client_id=?");
    $stmt->execute([$milestone_id, $client_id]);
    $milestone = $stmt->fetch(PDO::FETCH_ASSOC);

    if($milestone && $milestone['status'] == 'under_review'){
        $pdo->prepare("UPDATE milestones SET status='approved',payment_status='released' WHERE id=?")->execute([$milestone_id]);
        $next = $milestone['order_number'] + 1;
        $pdo->prepare("UPDATE milestones SET is_locked=0 WHERE project_id=? AND order_number=?")->execute([$project_id,$next]);
        $pdo->prepare("INSERT INTO milestone_logs(milestone_id,changed_by,old_status,new_status,note)VALUES(?,?,'under_review','approved','Approved by client')")->execute([$milestone_id,$client_id]);

        $stmt2 = $pdo->prepare("SELECT COUNT(*) as t,SUM(status='approved') as d FROM milestones WHERE project_id=?");
        $stmt2->execute([$project_id]);
        $mc = $stmt2->fetch(PDO::FETCH_ASSOC);
        if($mc['t']==$mc['d']){
            $pdo->prepare("UPDATE projects SET status='completed' WHERE id=?")->execute([$project_id]);
            $success = "🎉 All milestones approved! Project is now completed!";
        } else {
            $success = "Milestone approved! Next milestone has been unlocked.";
        }
    } else {
        $error = "This milestone cannot be approved. It must be Under Review first.";
    }
}

$stmt = $pdo->prepare("SELECT p.*,u.name as freelancer_name FROM projects p LEFT JOIN users u ON p.freelancer_id=u.id WHERE p.client_id=? AND p.status IN ('active','completed') ORDER BY p.created_at DESC");
$stmt->execute([$client_id]);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Approve Milestones - Milestone Hub</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f0f4f0; color: #1a1a2e; }

        .topbar { background: linear-gradient(135deg, #1a3533, #316461); padding: 0 28px; height: 80px; display: flex; align-items: center; justify-content: space-between; position: fixed; top: 0; left: 0; right: 0; z-index: 100; box-shadow: 0 2px 20px rgba(0,0,0,0.15); }
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

        .main { margin-left: 220px; margin-top: 60px; padding: 28px; }

        .page-header { margin-bottom: 24px; }
        .page-title { font-size: 22px; font-weight: 700; color: #1a3533; }
        .page-sub   { font-size: 13px; color: #888; margin-top: 3px; }

        .project-section {
            background: #fff;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
        }

        .project-head {
            display: flex; align-items: center;
            justify-content: space-between; margin-bottom: 6px;
        }

        .project-title { font-size: 16px; font-weight: 600; color: #1a3533; }

        .project-meta {
            font-size: 13px; color: #888; margin-bottom: 16px;
            padding-bottom: 14px; border-bottom: 1px solid #f0f0f0;
        }

        .badge { display: inline-block; font-size: 11px; font-weight: 500; padding: 4px 12px; border-radius: 20px; }
        .badge-active    { background: #e8f5f5; color: #316461; }
        .badge-completed { background: #F1EFE8; color: #5F5E5A; }

        /* Milestone items */
        .milestone-item {
            border-radius: 12px; padding: 16px;
            border: 1.5px solid #e8e8e8; margin-bottom: 12px;
        }
        .milestone-item:last-child { margin-bottom: 0; }
        .milestone-item.needs-review { border-color: #534AB7; background: #faf9ff; }
        .milestone-item.approved-ms  { border-color: #47928e; background: #f8fffe; }
        .milestone-item.locked-ms    { opacity: 0.55; }

        .ms-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; }
        .ms-left { display: flex; align-items: center; gap: 10px; }

        .ms-num { width: 30px; height: 30px; border-radius: 50%; background: linear-gradient(135deg, #47928e, #316461); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 600; flex-shrink: 0; }
        .ms-num.locked-num { background: #ddd; color: #888; }

        .ms-title  { font-size: 14px; font-weight: 600; color: #1a3533; }
        .ms-meta   { font-size: 12px; color: #888; margin-top: 2px; }

        .ms-badge { display: inline-block; font-size: 11px; font-weight: 500; padding: 4px 10px; border-radius: 20px; }
        .ms-badge-pending      { background: #F1EFE8; color: #888; }
        .ms-badge-deposited    { background: #EBF5FB; color: #185FA5; }
        .ms-badge-in-progress  { background: #FEF3C7; color: #92400E; }
        .ms-badge-under-review { background: #EEEDFE; color: #534AB7; }
        .ms-badge-approved     { background: #e8f5f5; color: #316461; }
        .ms-badge-locked       { background: #F1EFE8; color: #aaa; }

        .review-tag {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 11px; font-weight: 600; color: #534AB7;
            background: #EEEDFE; padding: 4px 12px; border-radius: 20px;
            margin-bottom: 10px;
        }

        .submission-box { background: #f8f7ff; border: 1px solid #e0deff; border-radius: 10px; padding: 14px; margin: 10px 0; }
        .submission-title { font-size: 12px; font-weight: 600; color: #534AB7; margin-bottom: 8px; }
        .submission-note  { font-size: 13px; color: #444; line-height: 1.6; margin-bottom: 8px; }
        .sub-link { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; padding: 5px 12px; border-radius: 6px; text-decoration: none; font-weight: 500; margin-right: 6px; }
        .sub-link.file { background: #e8f5f5; color: #316461; }
        .sub-link.url  { background: #EBF5FB; color: #185FA5; }

        .btn-approve { padding: 9px 20px; background: linear-gradient(135deg, #47928e, #316461); border: none; border-radius: 8px; color: #fff; font-size: 13px; font-weight: 500; cursor: pointer; font-family: inherit; }
        .payment-badge { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; padding: 3px 8px; border-radius: 20px; background: #e8f5f5; color: #316461; font-weight: 500; margin-left: 6px; }

        .waiting-msg { font-size: 12px; color: #aaa; font-style: italic; margin-top: 8px; }

        .alert { padding: 12px 16px; border-radius: 10px; font-size: 13px; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
        .alert-error   { background: #FEE2E2; color: #B91C1C; }
        .alert-success { background: #e8f5f5; color: #316461; }

        .empty { text-align: center; padding: 60px 20px; background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.05); }
        .empty-icon  { font-size: 52px; margin-bottom: 16px; }
        .empty-title { font-size: 18px; font-weight: 600; color: #1a3533; margin-bottom: 8px; }
        .empty-sub   { font-size: 13px; color: #888; }
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
        <a href="my_projects.php" class="nav-item"><div class="nav-icon">▤</div> My projects</a>
        <a href="approve_milestone.php" class="nav-item active"><div class="nav-icon">✓</div> Approve milestones</a>
    </div>
    <div class="sidebar-section">
        <div class="sidebar-label">Account</div>
        <a href="../logout.php" class="nav-item"><div class="nav-icon">⇥</div> Logout</a>
    </div>
</div>

<div class="main">
    <div class="page-header">
        <div class="page-title">Approve Milestones</div>
        <div class="page-sub">Review freelancer submissions and approve completed milestones</div>
    </div>

    <?php if($error):   ?> <div class="alert alert-error">⚠ <?= $error ?></div>     <?php endif; ?>
    <?php if($success): ?> <div class="alert alert-success">✅ <?= $success ?></div> <?php endif; ?>

    <?php if(count($projects) == 0): ?>
        <div class="empty">
            <div class="empty-icon">✅</div>
            <div class="empty-title">No active projects</div>
            <div class="empty-sub">Active projects with milestones to approve will appear here.</div>
        </div>
    <?php else: ?>

        <?php foreach($projects as $project): ?>
            <?php
            $stmt = $pdo->prepare("SELECT * FROM milestones WHERE project_id=? ORDER BY order_number");
            $stmt->execute([$project['id']]);
            $milestones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $ur_count = 0;
            foreach($milestones as $m){ if($m['status']=='under_review') $ur_count++; }
            ?>

            <div class="project-section">
                <div class="project-head">
                    <div class="project-title"><?= htmlspecialchars($project['title']) ?></div>
                    <span class="badge badge-<?= $project['status'] ?>"><?= ucfirst($project['status']) ?></span>
                </div>
                <div class="project-meta">
                    Freelancer: <strong><?= $project['freelancer_name'] ? htmlspecialchars($project['freelancer_name']) : 'Not assigned' ?></strong>
                    &nbsp;·&nbsp; Budget: <strong>Rs. <?= number_format($project['total_budget'], 2) ?></strong>
                    <?php if($ur_count > 0): ?>
                        &nbsp;·&nbsp; <span style="color:#534AB7;font-weight:600">⚠ <?= $ur_count ?> milestone(s) waiting for approval</span>
                    <?php endif; ?>
                </div>

                <?php foreach($milestones as $m): ?>
                    <div class="milestone-item <?= $m['is_locked'] ? 'locked-ms' : ($m['status']=='under_review' ? 'needs-review' : ($m['status']=='approved' ? 'approved-ms' : '')) ?>">

                        <?php if($m['status'] == 'under_review'): ?>
                            <div class="review-tag">👀 Waiting for your approval</div>
                        <?php endif; ?>

                        <div class="ms-head">
                            <div class="ms-left">
                                <div class="ms-num <?= $m['is_locked'] ? 'locked-num' : '' ?>"><?= $m['is_locked'] ? '⛉' : $m['order_number'] ?></div>
                                <div>
                                    <div class="ms-title">
                                        <?= htmlspecialchars($m['title']) ?>
                                        <?php if($m['payment_status']=='released'): ?><span class="payment-badge">✓ Released</span><?php endif; ?>
                                        <?php if($m['payment_status']=='deposited' && $m['status']!='approved'): ?><span class="payment-badge">💰 Deposited</span><?php endif; ?>
                                    </div>
                                    <div class="ms-meta">Rs. <?= number_format($m['amount'],2) ?> &nbsp;·&nbsp; Due: <?= date('d M Y', strtotime($m['due_date'])) ?></div>
                                </div>
                            </div>
                            <?php if($m['is_locked']): ?>
                                <span class="ms-badge ms-badge-locked">⛉ Locked</span>
                            <?php else: ?>
                                <span class="ms-badge ms-badge-<?= str_replace('_','-',$m['status']) ?>"><?= ucfirst(str_replace('_',' ',$m['status'])) ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if($m['status']=='under_review' && ($m['submission_note']||$m['submission_file']||$m['submission_link'])): ?>
                            <div class="submission-box">
                                <div class="submission-title">📦 Freelancer Submission</div>
                                <?php if($m['submission_note']): ?>
                                    <div class="submission-note"><strong>Description:</strong> <?= htmlspecialchars($m['submission_note']) ?></div>
                                <?php endif; ?>
                                <?php if($m['submission_file']): ?>
                                    <a href="../uploads/<?= $m['submission_file'] ?>" target="_blank" class="sub-link file">📎 View file</a>
                                <?php endif; ?>
                                <?php if($m['submission_link']): ?>
                                    <a href="<?= htmlspecialchars($m['submission_link']) ?>" target="_blank" class="sub-link url">🔗 View link</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if($m['status']=='under_review'): ?>
                            <form method="POST" action="approve_milestone.php" style="margin-top:12px">
                                <input type="hidden" name="milestone_id" value="<?= $m['id'] ?>"/>
                                <input type="hidden" name="project_id"   value="<?= $project['id'] ?>"/>
                                <button type="submit" name="approve_milestone" class="btn-approve">✓ Approve Milestone</button>
                            </form>
                        <?php elseif($m['status']=='approved'): ?>
                            <div style="font-size:12px;color:#316461;font-weight:500;margin-top:8px">✅ Approved and completed</div>
                        <?php elseif($m['is_locked']): ?>
                            <div class="waiting-msg">⛉ Locked — approve previous milestone first</div>
                        <?php elseif($m['status']=='in_progress'): ?>
                            <div class="waiting-msg">⚙ Freelancer is working on this milestone</div>
                        <?php elseif($m['status']=='deposited'): ?>
                            <div class="waiting-msg">⏳ Waiting for freelancer to start</div>
                        <?php elseif($m['status']=='pending'): ?>
                            <div class="waiting-msg">💳 Payment not made yet</div>
                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>
            </div>

        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>