<?php
include 'includes/db.php';
session_start();

$error_name     = "";
$error_email    = "";
$error_password = "";
$error_confirm  = "";
$error_role     = "";
$success        = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];
    $role     = $_POST['role'];

    // Validate name
    // Validate name
if(empty($name)){
    $error_name = "Full name is required.";
} elseif(strlen($name) < 3){
    $error_name = "Name must be at least 3 characters long.";
} elseif(!preg_match('/^[a-zA-Z\s]+$/', $name)){
    $error_name = "Name can only contain letters and spaces. No numbers or special characters allowed.";
}

    // Validate email
    if(empty($email)){
        $error_email = "Email address is required.";
    } elseif(!preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/', $email)){
        $error_email = "Please use a valid Gmail address ending with @gmail.com.";
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if($stmt->rowCount() > 0){
            $error_email = "This email is already registered. Please login.";
        }
    }

    // Validate password
    if(empty($password)){
        $error_password = "Password is required.";
    } elseif(strlen($password) < 6){
        $error_password = "Password must be at least 6 characters long.";
    }

    // Validate confirm password
    if(empty($confirm)){
        $error_confirm = "Please confirm your password.";
    } elseif($password !== $confirm){
        $error_confirm = "Passwords do not match.";
    }

    // Validate role
    if(empty($role)){
        $error_role = "Please select a role.";
    }

    // Only register if no errors
    if(empty($error_name) && empty($error_email) && empty($error_password) && empty($error_confirm) && empty($error_role)){

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $hashed, $role]);
        $success = "Registration successful! You can now login.";

        // Clear fields after success
        $name = $email = $password = $confirm = $role = "";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - Milestone Hub</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .error-msg {
            font-size: 12px;
            color: #B91C1C;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .input-wrap input.error-input {
            border-color: #B91C1C;
            background: #fff5f5;
        }
    </style>
</head>
<body>

<a href="landing.php" class="back-home">
    <div class="back-home-icon">⊞</div>
    <div class="back-home-text">Milestone Hub</div>
</a>

<div class="auth-container">

    <div class="logo">
        <div class="logo-icon">⊞</div>
        <h2>Create Account</h2>
        <p>Milestone Hub — Freelancer Client Management</p>
    </div>

    <div class="divider"></div>

    <?php if($success): ?>
        <div class="alert alert-success">
            <?= $success ?> <a href="index.php">Login here →</a>
        </div>
    <?php endif; ?>

    <form method="POST">

        <!-- Role -->
        <div class="field">
            <label>Register as</label>
            <div class="roles">
                <button type="button" class="role-btn <?= (($_POST['role'] ?? '') == 'client') ? 'active' : '' ?>" onclick="setRole(this,'client')">♟Client</button>
                <button type="button" class="role-btn <?= (($_POST['role'] ?? '') == 'freelancer' || empty($_POST['role'] ?? '')) ? 'active' : '' ?>" onclick="setRole(this,'freelancer')">⊛ Freelancer</button>
            </div>
            <input type="hidden" name="role" id="role_input" value="<?= htmlspecialchars($_POST['role'] ?? 'freelancer') ?>"/>
            <?php if($error_role): ?>
                <div class="error-msg">⚠ <?= $error_role ?></div>
            <?php endif; ?>
        </div>

        <!-- Name -->
        <div class="field">
            <label>Full Name</label>
            <div class="input-wrap">
                <span class="icon">	&#8258;</span>
                <input
                    type="text"
                    name="name"
                    placeholder="Enter your full name"
                    value="<?= htmlspecialchars($name ?? '') ?>"
                    class="<?= $error_name ? 'error-input' : '' ?>"
                    required
                />
            </div>
            <?php if($error_name): ?>
                <div class="error-msg">⚠ <?= $error_name ?></div>
            <?php endif; ?>
        </div>

        <!-- Email -->
        <div class="field">
            <label>Email Address</label>
            <div class="input-wrap">
                <span class="icon">✉</span>
                <input
                    type="email"
                    name="email"
                    placeholder="Enter your Gmail address"
                    value="<?= htmlspecialchars($email ?? '') ?>"
                    class="<?= $error_email ? 'error-input' : '' ?>"
                    required
                />
            </div>
            <?php if($error_email): ?>
                <div class="error-msg">⚠ <?= $error_email ?></div>
            <?php endif; ?>
        </div>

        <!-- Password -->
        <div class="field">
            <label>Password</label>
            <div class="input-wrap">
                <span class="icon">🔒</span>
                <input
                    type="password"
                    name="password"
                    placeholder="Create a password (min 6 characters)"
                    class="<?= $error_password ? 'error-input' : '' ?>"
                    required
                />
            </div>
            <?php if($error_password): ?>
                <div class="error-msg">⚠ <?= $error_password ?></div>
            <?php endif; ?>
        </div>

        <!-- Confirm Password -->
        <div class="field">
            <label>Confirm Password</label>
            <div class="input-wrap">
                <span class="icon">🔒</span>
                <input
                    type="password"
                    name="confirm_password"
                    placeholder="Re-enter your password"
                    class="<?= $error_confirm ? 'error-input' : '' ?>"
                    required
                />
            </div>
            <?php if($error_confirm): ?>
                <div class="error-msg">⚠ <?= $error_confirm ?></div>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn-submit">Create Account</button>

        <div class="footer-link">Already have an account? <a href="index.php">Login here</a></div>

    </form>
</div>

<script>
function setRole(el, role){
    document.querySelectorAll('.role-btn').forEach(b => b.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('role_input').value = role;
}
</script>

</body>
</html>