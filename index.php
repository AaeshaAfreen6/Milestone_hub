<?php
include 'includes/db.php';
session_start();

$error_email    = "";
$error_password = "";
$error_role     = "";
$error_login    = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $role     = $_POST['role'];

    // Validate email
    if(empty($email)){
        $error_email = "Email address is required.";
    } elseif(!preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/', $email)){
        $error_email = "Please use a valid Gmail address ending with @gmail.com.";
    }

    // Validate password
    if(empty($password)){
        $error_password = "Password is required.";
    } elseif(strlen($password) < 6){
        $error_password = "Password must be at least 6 characters long.";
    }

    // Validate role
    if(empty($role)){
        $error_role = "Please select a role.";
    }

    // Only login if no errors
    if(empty($error_email) && empty($error_password) && empty($error_role)){

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
        $stmt->execute([$email, $role]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if($user && password_verify($password, $user['password'])){

            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            if($user['role'] == 'admin'){
                header("Location: admin/dashboard.php");
            } elseif($user['role'] == 'client'){
                header("Location: client/dashboard.php");
            } else {
                header("Location: freelancer/dashboard.php");
            }
            exit();

        } else {
            $error_login = "Invalid email, password or role. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - Milestone Hub</title>
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
        <h2>Milestone Hub</h2>
        <p>Freelancer-Client Project Management</p>
    </div>

    <div class="divider"></div>

    <?php if($error_login): ?>
        <div class="alert alert-error"><?= $error_login ?></div>
    <?php endif; ?>

    <form method="POST">

        <label style="font-size:13px;font-weight:500;color:#444;margin-bottom:8px;display:block">Login as</label>
        <div class="roles">
            <button type="button" class="role-btn" onclick="setRole(this,'admin')">♡ Admin</button>
            <button type="button" class="role-btn active" onclick="setRole(this,'client')">♟ Client</button>
            <button type="button" class="role-btn" onclick="setRole(this,'freelancer')">⊛ Freelancer</button>
        </div>
        <input type="hidden" name="role" id="role_input" value="client"/>

        <?php if($error_role): ?>
            <div class="error-msg">⚠ <?= $error_role ?></div>
        <?php endif; ?>

        <div class="field" style="margin-top:16px">
            <label>Email address</label>
            <div class="input-wrap">
                <span class="icon">✉</span>
                <input
                    type="email"
                    name="email"
                    placeholder="Enter your Gmail address"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    required
                />
            </div>
            <?php if($error_email): ?>
                <div class="error-msg">⚠ <?= $error_email ?></div>
            <?php endif; ?>
        </div>

        <div class="field">
            <label>Password</label>
            <div class="input-wrap">
                <span class="icon">🔒</span>
                <input
                    type="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                />
            </div>
            <?php if($error_password): ?>
                <div class="error-msg">⚠ <?= $error_password ?></div>
            <?php endif; ?>
        </div>

        <div class="forgot"><a href="#">Forgot password?</a></div>

        <button type="submit" class="btn-submit">Login to Milestone Hub</button>

        <div class="footer-link">Don't have an account? <a href="register.php">Register here</a></div>

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