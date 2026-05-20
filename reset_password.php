<?php
session_start();
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/helpers.php';

$error = '';
$success = '';
$email = trim($_GET['email'] ?? $_POST['email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        Auth::resetPassword($email, trim($_POST['code'] ?? ''), $_POST['password'] ?? '');
        $success = 'Password updated. You can now sign in.';
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

ob_start();
?>
<section class="auth-card reveal compact-auth">
    <form class="auth-form" method="POST" autocomplete="off">
        <h2>Set New Password</h2>
        <?php if ($error): ?><div class="error-box"><?= e($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="success-box"><?= e($success) ?></div><?php endif; ?>
        <label>Email
            <input type="email" name="email" value="<?= e($email) ?>" required>
        </label>
        <label>Verification code
            <input type="text" name="code" required>
        </label>
        <label>New password
            <input type="password" name="password" required>
        </label>
        <button class="button" type="submit">Update Password</button>
        <a class="muted-link" href="login.php">Return to sign in</a>
    </form>
</section>
<?php
$content = ob_get_clean();
$title = 'Reset Password - AidLink';
require __DIR__ . '/layout.php';
