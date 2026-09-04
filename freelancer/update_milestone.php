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
    $milestone_id    = $_POST['milestone_id'];
    $new_status      = $_POST['new_status'];
    $old_status      = $_POST['old_status'];
    $submission_note = trim($_POST['submission_note'] ?? '');
    $submission_link = trim($_POST['submission_link'] ?? '');

    $stmt = $pdo->prepare("
        SELECT m.* FROM milestones m
        JOIN projects p ON m.project_id = p.id
        WHERE m.id = ? AND p.freelancer_id = ?
    ");
    $stmt->execute([$milestone_id, $freelancer_id]);
    $milestone = $stmt->fetch(PDO::FETCH_ASSOC);

    if($milestone){
        if($milestone['is_locked']){
            $error = "This milestone is locked. Previous milestone must be approved first.";
        } else {

            $submission_file = null;

            // Handle file upload
            if(isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] == 0){
                $file     = $_FILES['submission_file'];
                $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed  = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'zip'];

                if(in_array($file_ext, $allowed) && $file['size'] <= 10 * 1024 * 1024){
                    $new_name = 'submission_' . $milestone_id . '_' . time() . '.' . $file_ext;
                    if(move_uploaded_file($file['tmp_name'], '../uploads/' . $new_name)){
                        $submission_file = $new_name;
                    }
                }
            }

            // Update milestone with submission details
            $stmt = $pdo->prepare("
                UPDATE milestones 
                SET status = ?, 
                    submission_file = ?,
                    submission_link = ?,
                    submission_note = ?
                WHERE id = ?
            ");
            $stmt->execute([$new_status, $submission_file, $submission_link, $submission_note, $milestone_id]);

            // Log activity
            $stmt = $pdo->prepare("INSERT INTO milestone_logs (milestone_id, changed_by, old_status, new_status, note) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$milestone_id, $freelancer_id, $old_status, $new_status, 'Submitted for review by freelancer']);

            $success = "Milestone submitted for review successfully!";
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
            background: #2d6a60;
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
        .badge-approved   { background: #d8f3dc; color: #1b433d; }
        .badge-locked     { background: #F1EFE8; color: #aaa; }

        .btn-update {
            display: block;
            width: 100%;
            padding: 7px 14px;
            background: linear-gradient(135deg, #2d6a66, #1b4342);
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
        .alert-success { background: #d8f3dc; color: #1b433e; }

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
            color: #1b433e;
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
        <a href="update_milestone.php" class="nav-item active">My milestones</a>
        <a href="my_proposals.php" class="nav-item">My proposals</a>
         <a href="../landing.php" class="nav-item">← Home</a>
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
    <button class="btn-update" onclick="showSubmitForm(<?= $m['id'] ?>)">Submit for Review</button>

    <!-- Submission form -->
    <div id="submit-form-<?= $m['id'] ?>" style="display:none;margin-top:12px;background:#f8f8f8;padding:14px;border-radius:8px;border:1px solid #e0e0e0">
        <form method="POST" action="update_milestone.php" enctype="multipart/form-data">
            <input type="hidden" name="milestone_id" value="<?= $m['id'] ?>"/>
            <input type="hidden" name="old_status" value="in_progress"/>
            <input type="hidden" name="new_status" value="under_review"/>

            <div style="margin-bottom:10px">
                <label style="font-size:12px;font-weight:500;color:#444;display:block;margin-bottom:5px">Work description</label>
                <textarea name="submission_note" rows="3" placeholder="Describe what you have completed in this milestone..." style="width:100%;padding:8px 12px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:13px;font-family:inherit;resize:vertical;outline:none"></textarea>
            </div>

            <div style="margin-bottom:10px">
                <label style="font-size:12px;font-weight:500;color:#444;display:block;margin-bottom:5px">Attach file <span style="color:#aaa;font-weight:400">(optional — PDF, Word, Image, ZIP)</span></label>
                <input type="file" name="submission_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip" style="width:100%;padding:8px;border:1.5px dashed #e0e0e0;border-radius:8px;font-size:13px;background:#fff"/>
            </div>

            <div style="margin-bottom:12px">
                <label style="font-size:12px;font-weight:500;color:#444;display:block;margin-bottom:5px">Or paste a link <span style="color:#aaa;font-weight:400">(Google Drive, GitHub, etc.)</span></label>
                <input type="text" name="submission_link" placeholder="https://drive.google.com/..." style="width:100%;padding:8px 12px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:13px;font-family:inherit;outline:none"/>
            </div>

            <div style="display:flex;gap:8px">
                <button type="submit" name="update_status" class="btn-update" style="flex:1">Submit for Review</button>
                <button type="button" onclick="hideSubmitForm(<?= $m['id'] ?>)" style="padding:7px 14px;border:1.5px solid #e0e0e0;border-radius:8px;background:#fff;font-size:12px;cursor:pointer;font-family:inherit">Cancel</button>
            </div>
        </form>
    </div>

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
<script>
    function showSubmitForm(id){
    document.getElementById('submit-form-' + id).style.display = 'block';
}

function hideSubmitForm(id){
    document.getElementById('submit-form-' + id).style.display = 'none';
}
</script>

</body>
</html>