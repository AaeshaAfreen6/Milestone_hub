<?php
include 'includes/db.php';
session_start();

$error = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role     = $_POST['role'];

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
        $error = "Invalid email, password or role.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - Milestone Hub</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="auth-container">

    <div class="logo">
        <div class="logo-icon">⊞</div>
        <h2>Milestone Hub</h2>
        <p>Freelancer-Client Project Management</p>
    </div>

    <div class="divider"></div>

    <?php if($error): ?>
        <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">

        <label class="field" style="font-size:13px;font-weight:500;color:#444;margin-bottom:8px;display:block">Login as</label>
        <div class="roles">
            <button type="button" class="role-btn" onclick="setRole(this,'admin')">♡ Admin</button>
            <button type="button" class="role-btn active" onclick="setRole(this,'client')">♟ Client</button>
            <button type="button" class="role-btn" onclick="setRole(this,'freelancer')">⊛ Freelancer</button>
        </div>
        <input type="hidden" name="role" id="role_input" value="client"/>

        <div class="field">
            <label>Email address</label>
            <div class="input-wrap">
                <span class="icon">✉</span>
                <input type="email" name="email" placeholder="Enter your email" required/>
            </div>
        </div>

        <div class="field">
            <label>Password</label>
            <div class="input-wrap">
                <span class="icon">🔒</span>
                <input type="password" name="password" placeholder="Enter your password" required/>
            </div>
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