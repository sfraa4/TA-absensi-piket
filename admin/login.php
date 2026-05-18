<?php
require_once 'header.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();
    
    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        header("Location: index.php");
        exit;
    } else {
        $error = 'Email atau password salah!';
    }
}
?>

<div class="row justify-content-center align-items-center vh-100 bg-light">
    <div class="col-md-4">
        <div class="card shadow-lg border-0 rounded-4">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <h3 class="fw-bold text-primary">Admin Login</h3>
                </div>
                <?php if($error): ?>
                    <div class="alert alert-danger rounded-3"><?= $error ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" name="email" class="form-control form-control-lg rounded-3" required autofocus>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Password</label>
                        <input type="password" name="password" class="form-control form-control-lg rounded-3" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-100 rounded-3">Login</button>
                    <div class="text-center mt-3">
                        <a href="../index.php" class="text-decoration-none">Kembali ke Halaman Utama</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
