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
$error      = "";
$success    = "";

if(!$project_id){
    header("Location: dashboard.php");
    exit();
}

// Get project
$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ? AND client_id = ?");
$stmt->execute([$project_id, $client_id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$project){
    header("Location: dashboard.php");
    exit();
}

// Accept freelancer
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accept_freelancer'])){
    $proposal_id   = $_POST['proposal_id'];
    $freelancer_id = $_POST['freelancer_id'];

    // Accept this proposal
    $stmt = $pdo->prepare("UPDATE proposals SET status = 'accepted' WHERE id = ?");
    $stmt->execute([$proposal_id]);

    // Reject all other proposals
    $stmt = $pdo->prepare("UPDATE proposals SET status = 'rejected' WHERE project_id = ? AND id != ?");
    $stmt->execute([$project_id, $proposal_id]);

    // Assign freelancer to project and set active
    $stmt = $pdo->prepare("UPDATE projects SET freelancer_id = ?, status = 'active' WHERE id = ?");
    $stmt->execute([$freelancer_id, $project_id]);

    // Unlock first milestone
    $stmt = $pdo->prepare("UPDATE milestones SET is_locked = 0 WHERE project_id = ? AND order_number = 1");
    $stmt->execute([$project_id]);

    $success = "Freelancer accepted! Project is now active.";

    // Refresh project data
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
}


// Approve milestone
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['approve_milestone'])){
    $milestone_id = $_POST['milestone_id'];

    // Approve current milestone
    $stmt = $pdo->prepare("UPDATE milestones SET status = 'approved', payment_status = 'released' WHERE id = ?");
    $stmt->execute([$milestone_id]);

    // Get current milestone order
    $stmt = $pdo->prepare("SELECT order_number FROM milestones WHERE id = ?");
    $stmt->execute([$milestone_id]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);

    // Unlock next milestone
    $next_order = $current['order_number'] + 1;
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
    }

    $success = "Milestone approved successfully!";
}
// Deposit milestone
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['deposit_milestone'])){
    $milestone_id = $_POST['milestone_id'];

    $stmt = $pdo->prepare("SELECT * FROM milestones WHERE id = ? AND project_id = ?");
    $stmt->execute([$milestone_id, $project_id]);
    $milestone = $stmt->fetch(PDO::FETCH_ASSOC);

    if($milestone){
        $stmt = $pdo->prepare("UPDATE milestones SET status = 'deposited', payment_status = 'deposited' WHERE id = ?");
        $stmt->execute([$milestone_id]);

        $stmt = $pdo->prepare("INSERT INTO milestone_logs (milestone_id, changed_by, old_status, new_status, note) VALUES (?, ?, 'pending', 'deposited', 'Funds deposited by client')");
        $stmt->execute([$milestone_id, $client_id]);

        $success = "Milestone marked as deposited!";
    }

    // Refresh project
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
}



// Get proposals
$stmt = $pdo->prepare("
    SELECT pr.*, u.name as freelancer_name, u.email as freelancer_email
    FROM proposals pr
    JOIN users u ON pr.freelancer_id = u.id
    WHERE pr.project_id = ?
    ORDER BY pr.created_at DESC
");
$stmt->execute([$project_id]);
$proposals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get milestones
$stmt = $pdo->prepare("SELECT * FROM milestones WHERE project_id = ? ORDER BY order_number");
$stmt->execute([$project_id]);
$milestones = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Project - Milestone Hub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .project-header {
            background: #fff;
            border-radius: 12px;
            padding: 20px 24px;
            margin-bottom: 20px;
            border: 0.5px solid #e8e6e6;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .project-header h3 {
            font-size: 18px;
            font-weight: 500;
            color: #081c15;
            margin-bottom: 4px;
        }

        .project-header p {
            font-size: 13px;
            color: #888780;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .section-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            border: 0.5px solid #e8e6e6;
        }

        .section-card h4 {
            font-size: 14px;
            font-weight: 500;
            color: #081c15;
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 0.5px solid #f0eeee;
        }

        .proposal-item {
            padding: 12px 0;
            border-bottom: 0.5px solid #f0eeee;
        }

        .proposal-item:last-child { border-bottom: none; }

        .proposal-name {
            font-size: 14px;
            font-weight: 500;
            color: #081c15;
            margin-bottom: 4px;
        }

        .proposal-email {
            font-size: 12px;
            color: #888780;
            margin-bottom: 6px;
        }

        .proposal-msg {
            font-size: 13px;
            color: #555;
            margin-bottom: 10px;
            line-height: 1.5;
            background: #f8f8f8;
            padding: 8px 12px;
            border-radius: 8px;
        }

        .proposal-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .badge {
            font-size: 11px;
            font-weight: 500;
            padding: 3px 10px;
            border-radius: 20px;
            display: inline-block;
        }

        .badge-pending   { background: #FEF3C7; color: #92400E; }
        .badge-accepted  { background: #d8f3dc; color: #1b4332; }
        .badge-rejected  { background: #FEE2E2; color: #B91C1C; }
        .badge-open      { background: #d8f3dc; color: #1b4332; }
        .badge-active    { background: #d8f3dc; color: #1b4332; }
        .badge-completed { background: #F1EFE8; color: #5F5E5A; }

        .btn-accept {
            padding: 6px 16px;
            background: linear-gradient(135deg, #2d6a4f, #1b4332);
            border: none;
            border-radius: 8px;
            color: #fff;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            font-family: inherit;
        }

        .milestone-item {
            padding: 14px 0;
            border-bottom: 0.5px solid #f0eeee;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .milestone-item:last-child { border-bottom: none; }

        .ms-num {
            width: 30px;
            height: 30px;
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

        .ms-status { text-align: right; }

        .badge-pending-ms  { background: #F1EFE8; color: #888780; }
        .badge-deposited   { background: #E6F1FB; color: #185FA5; }
        .badge-in-progress { background: #FEF3C7; color: #92400E; }
        .badge-under-review{ background: #EEEDFE; color: #534AB7; }
        .badge-approved-ms { background: #d8f3dc; color: #1b4332; }
        .badge-locked      { background: #F1EFE8; color: #aaa; }

        .btn-approve {
            padding: 6px 14px;
            background: linear-gradient(135deg, #2d6a4f, #1b4332);
            border: none;
            border-radius: 8px;
            color: #fff;
            font-size: 12px;
            cursor: pointer;
            font-family: inherit;
            margin-top: 6px;
        }

        .alert {
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 16px;
        }

        .alert-error   { background: #FEE2E2; color: #B91C1C; }
        .alert-success { background: #d8f3dc; color: #1b4332; }

        .empty-proposals {
            text-align: center;
            padding: 20px;
            color: #888780;
            font-size: 13px;
        }

        .deposit-form {
            margin-top: 6px;
        }

        .btn-deposit {
            padding: 6px 14px;
            background: #185FA5;
            border: none;
            border-radius: 8px;
            color: #fff;
            font-size: 12px;
            cursor: pointer;
            font-family: inherit;
            margin-top: 6px;
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
        <a href="create_project.php" class="nav-item">+ Create project</a>
        <a href="approve_milestone.php" class="nav-item">Approve milestones</a>
         <a href="../landing.php" class="nav-item">← Home</a>
    </div>

    <div class="main">

        <!-- Project header -->
        <div class="project-header">
            <div>
                <h3><?= htmlspecialchars($project['title']) ?></h3>
                <p><?= htmlspecialchars($project['description']) ?></p>
                <p style="margin-top:6px">Budget: <strong>Rs. <?= number_format($project['total_budget'], 2) ?></strong></p>
            </div>
            <span class="badge badge-<?= $project['status'] ?>"><?= ucfirst($project['status']) ?></span>
        </div>

        <?php if($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <div class="grid-2">

            <!-- PROPOSALS -->
            <div class="section-card">
                <h4>Proposals Received (<?= count($proposals) ?>)</h4>

                <?php if(count($proposals) == 0): ?>
                    <div class="empty-proposals">No proposals yet.</div>
                <?php else: ?>
                    <?php foreach($proposals as $proposal): ?>
                        <div class="proposal-item">
                            <div class="proposal-name"><?= htmlspecialchars($proposal['freelancer_name']) ?></div>
                            <div class="proposal-email"><?= htmlspecialchars($proposal['freelancer_email']) ?></div>
                           <div class="proposal-msg"><?= htmlspecialchars($proposal['message']) ?></div>

<?php if($proposal['attachment']): ?>
    <a href="../uploads/<?= $proposal['attachment'] ?>"
       target="_blank"
       style="
           display: inline-flex;
           align-items: center;
           gap: 6px;
           font-size: 12px;
           color: #316461;
           background: #e8f5f5;
           padding: 5px 12px;
           border-radius: 6px;
           text-decoration: none;
           margin-bottom: 10px;
       ">
        📎 View attachment
    </a>
<?php endif; ?>

<div class="proposal-footer">
                            <div class="proposal-footer">
                                <span class="badge badge-<?= $proposal['status'] ?>"><?= ucfirst($proposal['status']) ?></span>
                                <?php if($proposal['status'] == 'pending' && $project['status'] == 'open'): ?>
                                    <form method="POST">
                                        <input type="hidden" name="proposal_id" value="<?= $proposal['id'] ?>"/>
                                        <input type="hidden" name="freelancer_id" value="<?= $proposal['freelancer_id'] ?>"/>
                                        <button type="submit" name="accept_freelancer" class="btn-accept">Accept</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            

            <!-- MILESTONES -->
            <div class="section-card">
                <h4>Milestones</h4>
                <?php foreach($milestones as $m): ?>
                    <div class="milestone-item">
                        <div class="ms-num <?= $m['is_locked'] ? 'locked' : '' ?>">
                            <?= $m['is_locked'] ? '🔒' : $m['order_number'] ?>
                        </div>
                        <div class="ms-info">
                            <div class="ms-title <?= $m['is_locked'] ? 'locked' : '' ?>">
                                <?= htmlspecialchars($m['title']) ?>
                            </div>
                            <div class="ms-meta">
                                Rs. <?= number_format($m['amount'], 2) ?>
                                &nbsp;·&nbsp; Due: <?= date('d M Y', strtotime($m['due_date'])) ?>
                            </div>

                            <!-- Deposit button -->
         <?php if($m['status'] == 'pending' && !$m['is_locked'] && $project['status'] == 'active'): ?>
    <a href="payment.php?milestone_id=<?= $m['id'] ?>&project_id=<?= $project_id ?>" 
       class="btn-approve" 
       style="background:linear-gradient(135deg,#5C2D91,#4a2475);text-decoration:none;display:inline-block;padding:8px 16px;border-radius:8px;color:#fff;font-size:12px">
        💳 Make Payment
    </a>
<?php endif; ?>

                            <!-- Approve button -->
                            <?php if($m['status'] == 'under_review'): ?>
                              <form method="POST" action="view_project.php?id=<?= $project_id ?>">
                                 <input type="hidden" name="milestone_id" value="<?= $m['id'] ?>"/>
                                 
                                   <button type="submit" name="approve_milestone" class="btn-approve">Approve ✓</button>
                            </form>
                            <?php endif; ?>
                        </div>
                        <div class="ms-status">
                            <?php if($m['is_locked']): ?>
                                <span class="badge badge-locked">Locked</span>
                            <?php else: ?>
                                <span class="badge badge-<?= str_replace('_', '-', $m['status']) ?>-ms">
                                    <?= ucfirst(str_replace('_', ' ', $m['status'])) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</div>

</body>
</html>