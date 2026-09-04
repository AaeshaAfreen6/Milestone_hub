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

 // Check if freelancer already has 3 active projects
$stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE freelancer_id = ? AND status = 'active'");
$stmt->execute([$freelancer_id]);
$active_count = $stmt->fetchColumn();

if($active_count >= 3){
    $error = "You already have 3 active projects. Please complete one before applying for a new one.";
} else {
    $check = $pdo->prepare("SELECT id FROM proposals WHERE project_id = ? AND freelancer_id = ?");
    $check->execute([$project_id, $freelancer_id]);

    if($check->rowCount() > 0){
        $error = "You have already sent a proposal for this project.";
    } else {

        $attachment = null;

        // Handle file upload
        if(isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0){
            $file      = $_FILES['attachment'];
            $file_name = $file['name'];
            $file_size = $file['size'];
            $file_tmp  = $file['tmp_name'];
            $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            // Validate file type
            $allowed = ['pdf', 'doc', 'docx'];
            if(!in_array($file_ext, $allowed)){
                $error = "Only PDF and Word files are allowed.";
            }
            // Validate file size (5MB max)
            elseif($file_size > 5 * 1024 * 1024){
                $error = "File size must be less than 5MB.";
            }
            else {
                // Generate unique file name
                $new_file_name = time() . '_' . $freelancer_id . '.' . $file_ext;
                $upload_path   = '../uploads/' . $new_file_name;

                if(move_uploaded_file($file_tmp, $upload_path)){
                    $attachment = $new_file_name;
                } else {
                    $error = "Failed to upload file. Please try again.";
                }
            }
        }

        // Insert proposal if no error
        if(empty($error)){
            $stmt = $pdo->prepare("INSERT INTO proposals (project_id, freelancer_id, message, status, attachment) VALUES (?, ?, ?, 'pending', ?)");
            $stmt->execute([$project_id, $freelancer_id, $message, $attachment]);
            $success = "Proposal sent successfully!";
        }
    }
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
        .upload-wrap {
    position: relative;
}

.upload-wrap input[type="file"] {
    position: absolute;
    opacity: 0;
    width: 100%;
    height: 100%;
    cursor: pointer;
    z-index: 2;
}

.upload-label {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    border: 1.5px dashed #e8e6e6;
    border-radius: 10px;
    background: #fafafa;
    cursor: pointer;
    font-size: 13px;
    color: #888780;
    transition: all 0.2s;
}

.upload-label:hover {
    border-color: #47928e;
    background: #f0fafa;
    color: #47928e;
}

.upload-icon { font-size: 18px; }
    </style>
</head>
<body>
<script>
function showFileName(input){
    const fileName = input.files[0]?.name || 'Click to attach PDF or Word file';
    document.getElementById('file-name-text').textContent = fileName;

    const label = document.querySelector('.upload-label');
    if(input.files[0]){
        label.style.borderColor = '#47928e';
        label.style.color = '#316461';
        label.style.background = '#f0fafa';
    }
}
</script>

</body>
</html>

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
        <a href="update_milestone.php" class="nav-item">My milestones</a>
        <a href="my_proposals.php" class="nav-item">My proposals</a>
         <a href="../landing.php" class="nav-item">← Home</a>
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
                <form method="POST" enctype="multipart/form-data">
                   <!-- Proposal message -->
<div class="field">
    <label>Your proposal message</label>
    <textarea name="message" placeholder="Write why you are the right person for this project..." required></textarea>
</div>

<!-- File upload -->
<div class="field">
    <label>Attach File <span style="font-size:12px;color:#888;font-weight:400">(optional — PDF or Word only, max 5MB)</span></label>
    <div class="upload-wrap">
        <input type="file" name="attachment" id="attachment" accept=".pdf,.doc,.docx" onchange="showFileName(this)"/>
        <label for="attachment" class="upload-label">
            <span class="upload-icon">📎</span>
            <span id="file-name-text">Click to attach PDF or Word file</span>
        </label>
    </div>
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