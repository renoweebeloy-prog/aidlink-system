<?php
session_start();
require_once __DIR__ . '/../app/User.php';
require_once __DIR__ . '/../app/helpers.php';
require_login();

$user = User::find((int) current_user()['id']) ?: current_user();
$error = '';
$success = '';
$revealedAnswer = $_SESSION['reveal_answer_once'] ?? '';
$revealMessage = $_SESSION['reveal_message_once'] ?? '';
unset($_SESSION['reveal_answer_once'], $_SESSION['reveal_message_once']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['profile'])) {
    try {
        User::updateProfile(
            (int) $user['id'],
            trim($_POST['fullname'] ?? ''),
            trim($_POST['phone'] ?? ''),
            $_POST['theme'] ?? 'dark'
        );
        User::updateAvatar((int) $user['id'], $_FILES['avatar'] ?? []);
        setcookie('aidlink_theme', $_POST['theme'] ?? 'dark', time() + 31536000, '/');
        refresh_current_user();
        $user = User::find((int) current_user()['id']) ?: current_user();
        $success = 'Account details saved.';
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_avatar'])) {
    try {
        User::removeAvatar((int) $user['id']);
        refresh_current_user();
        $user = User::find((int) current_user()['id']) ?: current_user();
        $success = 'Profile photo removed.';
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    try {
        User::changePassword(
            (int) $user['id'],
            $_POST['current_password'] ?? '',
            $_POST['new_password'] ?? ''
        );
        $success = 'Password changed successfully.';
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reveal_security'])) {
    try {
        $_SESSION['reveal_answer_once'] = User::revealSecurityAnswer(
            (int) $user['id'],
            $_POST['reveal_password'] ?? ''
        );
        $_SESSION['reveal_message_once'] = 'Recovery answer unlocked for this page view.';
        header('Location: settings.php#recovery');
        exit;
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['security'])) {
    try {
        User::updateSecurityQuestion(
            (int) $user['id'],
            trim($_POST['security_question'] ?? ''),
            trim($_POST['security_answer'] ?? ''),
            $_POST['security_password'] ?? ''
        );
        refresh_current_user();
        $user = User::find((int) current_user()['id']) ?: current_user();
        $success = 'Recovery question saved.';
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

ob_start();
?>
<section class="page-head reveal">
    <div>
        <span class="eyebrow">Account Center</span>
        <h1>Settings</h1>
        <p class="lead">Keep your personal details, display preference, and recovery options current.</p>
    </div>
</section>

<?php if ($error): ?><div class="error-box reveal"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="success-box reveal"><?= e($success) ?></div><?php endif; ?>

<section class="settings-wrap settings-clean">
    <article class="panel profile-banner reveal scale-in">
        <div class="avatar-removable">
            <div class="settings-avatar-large">
                <?php if (!empty($user['avatar'])): ?>
                    <img src="<?= e($user['avatar']) ?>" alt="Profile photo">
                <?php else: ?>
                    <?= e(initials($user['fullname'])) ?>
                <?php endif; ?>
            </div>
            <?php if (!empty($user['avatar'])): ?>
                <form method="POST" class="avatar-remove-overlay-form">
                    <input type="hidden" name="remove_avatar" value="1">
                    <button
                        type="button"
                        class="avatar-remove-x"
                        data-avatar-remove-open
                        aria-label="Remove profile photo"
                        title="Remove profile photo"
                    >
                        ×
                    </button>
                </form>
            <?php endif; ?>
        </div>
        <div>
            <span class="pill"><?= e(role_label($user['role'])) ?></span>
            <h2><?= e($user['fullname']) ?></h2>
            <p class="muted"><?= e($user['email']) ?> · <?= e($user['phone'] ?? 'No mobile number') ?></p>
        </div>
    </article>

    <div class="settings-card-grid">
        <article class="panel preference-card reveal fade-up">
            <div class="card-title-row">
                <div>
                    <h2>Profile</h2>
                    <p class="muted">Update your display details and preferred appearance.</p>
                </div>
            </div>
            <form class="grid-form" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="profile" value="1">
                <label class="wide file-drop">Profile photo
                    <input type="file" name="avatar" accept="image/png,image/jpeg,image/webp">
                </label>
                <label>Full name
                    <input type="text" name="fullname" value="<?= e($user['fullname']) ?>" required>
                </label>
                <label>Email
                    <input type="email" value="<?= e($user['email']) ?>" readonly title="Email address is used as the account sign-in identity.">
                    <span class="readonly-note">Email is used for sign-in and cannot be edited here.</span>
                </label>
                <label>Mobile number
                    <input type="text" name="phone" value="<?= e($user['phone'] ?? '') ?>" required>
                </label>
                <label>Theme
                    <select name="theme">
                        <option value="dark" <?= ($user['theme'] ?? 'dark') === 'dark' ? 'selected' : '' ?>>Dark</option>
                        <option value="light" <?= ($user['theme'] ?? '') === 'light' ? 'selected' : '' ?>>Light</option>
                    </select>
                </label>
                <button class="button wide" type="submit">Save Changes</button>
            </form>
        </article>

        <article class="panel security-card reveal fade-up">
            <div class="card-title-row">
                <div>
                    <h2>Security</h2>
                    <p class="muted">Keep account access protected with a strong password.</p>
                </div>
            </div>
            <form class="grid-form" method="POST" autocomplete="off">
                <input type="hidden" name="password" value="1">
                <label class="wide">Current password
                    <input type="password" name="current_password" required>
                </label>
                <label class="wide">New password
                    <input type="password" name="new_password" required>
                </label>
                <button class="button wide" type="submit">Change Password</button>
            </form>

            <details class="recovery-details" id="recovery" open>
                <summary>
                    <span>Recovery question</span>
                    <small><?= !empty($user['security_question']) ? 'Configured' : 'Not set' ?></small>
                </summary>
                <?php if (!empty($user['security_question'])): ?>
                    <div class="notice-box recovery-preview">
                        <strong><?= e($user['security_question']) ?></strong>
                        <span>Answer: <?= $revealedAnswer !== '' ? e($revealedAnswer) : '••••••••' ?></span>
                    </div>
                    <?php if ($revealMessage): ?><div class="success-box compact-message"><?= e($revealMessage) ?></div><?php endif; ?>
                    <form class="grid-form reveal-answer-form" method="POST" autocomplete="off">
                        <input type="hidden" name="reveal_security" value="1">
                        <label class="wide">Confirm password to view answer
                            <input type="password" name="reveal_password" placeholder="Required before showing the saved answer" required>
                        </label>
                        <button class="small-button" type="submit">View Saved Answer</button>
                    </form>
                <?php endif; ?>
                <form class="grid-form" method="POST" autocomplete="off">
                    <input type="hidden" name="security" value="1">
                    <label class="wide">Current password
                        <input type="password" name="security_password" placeholder="Required before saving changes" required>
                    </label>
                    <label class="wide">Question
                        <input type="text" name="security_question" value="<?= e($user['security_question'] ?? '') ?>" placeholder="Example: What is your recovery word?" required>
                    </label>
                    <label class="wide">Answer
                        <input type="password" name="security_answer" placeholder="Enter a private answer" required>
                    </label>
                    <button class="button wide" type="submit">Save Recovery Option</button>
                </form>
            </details>
        </article>
    </div>
</section>
<?php if (!empty($user['avatar'])): ?>
<div class="modal-backdrop" id="avatarRemoveModal" aria-hidden="true">
    <div class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="avatarRemoveTitle">
        <div class="confirm-icon">×</div>
        <h2 id="avatarRemoveTitle">Remove profile photo?</h2>
        <p class="muted">Your account will use the default initials avatar again. You can upload a new photo anytime.</p>
        <div class="confirm-actions">
            <button type="button" class="small-button ghost-button" data-avatar-remove-close>No</button>
            <button type="button" class="small-button danger-button" data-avatar-remove-confirm>Yes, remove</button>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
$title = 'Settings - AidLink';
require __DIR__ . '/layout.php';
