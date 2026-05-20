<?php
session_start();
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/helpers.php';

$error = '';
$success = '';
$question = null;
$mode = $_POST['mode'] ?? 'previous';
$email = trim($_POST['email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['load_question'])) {
            $question = Auth::getSecurityQuestion($email);

            if (!$question) {
                throw new InvalidArgumentException('No recovery question is available for this email.');
            }

            $mode = 'question';
        } elseif (isset($_POST['reset_previous'])) {
            Auth::resetWithPreviousPassword(
                $email,
                $_POST['previous_password'] ?? '',
                $_POST['new_password'] ?? ''
            );
            $success = 'Password updated. You can now sign in.';
        } elseif (isset($_POST['reset_question'])) {
            Auth::resetWithSecurityAnswer(
                $email,
                $_POST['security_answer'] ?? '',
                $_POST['new_password'] ?? ''
            );
            $success = 'Password updated. You can now sign in.';
        }
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

ob_start();
?>
<section class="auth-card reveal compact-auth recovery-card">
    <div class="auth-hero">
        <span class="eyebrow">Account Recovery</span>
        <h1>Restore Access</h1>
        <p>Choose a recovery method connected to your account.</p>
    </div>

    <div class="auth-form">
        <h2>Forgot Password</h2>
        <?php if ($error): ?><div class="error-box"><?= e($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="success-box"><?= e($success) ?></div><?php endif; ?>

        <div class="auth-actions">
            <form method="POST">
                <input type="hidden" name="mode" value="previous">
                <button class="small-button <?= $mode === 'previous' ? '' : 'secondary' ?>" type="submit">Previous Password</button>
            </form>
            <form method="POST">
                <input type="hidden" name="mode" value="question">
                <button class="small-button <?= $mode === 'question' ? '' : 'secondary' ?>" type="submit">Recovery Question</button>
            </form>
        </div>

        <?php if ($mode === 'question'): ?>
            <?php if (!$question): ?>
                <form method="POST" autocomplete="off">
                    <input type="hidden" name="mode" value="question">
                    <label>Email
                        <input type="email" name="email" value="<?= e($email) ?>" required>
                    </label>
                    <button class="button" name="load_question" type="submit">Continue</button>
                </form>
            <?php else: ?>
                <form method="POST" autocomplete="off">
                    <input type="hidden" name="mode" value="question">
                    <input type="hidden" name="email" value="<?= e($email) ?>">
                    <div class="notice-box"><?= e($question['security_question']) ?></div>
                    <label>Answer
                        <input type="password" name="security_answer" required>
                    </label>
                    <label>New password
                        <input type="password" name="new_password" required>
                    </label>
                    <button class="button" name="reset_question" type="submit">Update Password</button>
                </form>
            <?php endif; ?>
        <?php else: ?>
            <form method="POST" autocomplete="off">
                <input type="hidden" name="mode" value="previous">
                <label>Email
                    <input type="email" name="email" value="<?= e($email) ?>" required>
                </label>
                <label>Previous password
                    <input type="password" name="previous_password" required>
                </label>
                <label>New password
                    <input type="password" name="new_password" required>
                </label>
                <button class="button" name="reset_previous" type="submit">Update Password</button>
            </form>
        <?php endif; ?>

        <a class="muted-link" href="login.php">Return to sign in</a>
    </div>
</section>
<?php
$content = ob_get_clean();
$title = 'Forgot Password - AidLink';
require __DIR__ . '/layout.php';
