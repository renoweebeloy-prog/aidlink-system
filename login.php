<?php

if (file_exists(__DIR__ . '/maintenance.flag')) {
    header('Location: maintenance.php');
    exit;
}

session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/helpers.php';

$error = '';
$registerError = '';
$registerSuccess = '';
$success = isset($_GET['created'])
    ? 'Account created successfully. You may now sign in.'
    : '';

$mode = ($_GET['mode'] ?? '') === 'register'
    ? 'register'
    : 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['register_account'])) {

        $mode = 'register';

        try {

            Auth::register(
                trim($_POST['fullname'] ?? ''),
                trim($_POST['register_email'] ?? ''),
                $_POST['register_password'] ?? '',
                trim($_POST['phone'] ?? '')
            );

            $registerSuccess =
                'Account created successfully. You can now sign in using your new account.';

        } catch (Throwable $exception) {

            $registerError = $exception->getMessage();
        }

    } else {

        $mode = 'login';

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (Auth::login($email, $password)) {

            header('Location: dashboard.php');
            exit;
        }

        $error = 'The email or password you entered is incorrect.';
    }
}

ob_start();
?>

<section class="auth-card auth-slider reveal <?= $mode === 'register' ? 'show-register' : '' ?>" id="authSlider">

    <div class="auth-hero auth-pane">

        <span class="eyebrow auth-eyebrow">
            <span class="auth-label-login">Community Platform</span>
            <span class="auth-label-register">New Recipient</span>
        </span>

        <h1>AidLink</h1>

        <p class="auth-copy auth-copy-login">
            Welcome back. Sign in to manage aid requests,
            messages, queues, and community updates.
        </p>

        <p class="auth-copy auth-copy-register">
            Join AidLink as a recipient and start submitting
            aid requests with real-time updates.
        </p>

        <p class="developer-credit developer-login-only">
            Developed @ Mark Bryan Aguimod
        </p>

    </div>

    <div class="auth-form-panel auth-pane">

        <!-- LOGIN FORM -->

        <form class="auth-form auth-login-form" method="POST" autocomplete="off">

            <h2>Sign in</h2>

            <?php if (current_user()): ?>

                <div class="notice-box">

                    Signed in as <?= e(current_user()['fullname']) ?>.

                    <div class="auth-actions">

                        <a class="small-button" href="dashboard.php">
                            Continue
                        </a>

                        <a class="small-button secondary" href="logout.php">
                            Logout
                        </a>

                    </div>

                </div>

            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-box"><?= e($success) ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="error-box"><?= e($error) ?></div>
            <?php endif; ?>

            <label>Email
                <input type="email" name="email" autocomplete="off" required>
            </label>

            <label>Password
                <input type="password" name="password" autocomplete="new-password" required>
            </label>

            <button class="button" type="submit">
                Sign in
            </button>

            <a class="muted-link" href="forgot_password.php">
                Forgot password?
            </a>

            <button
                class="muted-link auth-switch"
                type="button"
                data-auth-switch="register"
            >
                Create recipient account
            </button>

        </form>

        <!-- REGISTER FORM -->

        <form class="auth-form auth-register-form" method="POST" autocomplete="off">

            <input type="hidden" name="register_account" value="1">

            <h2>Create Account</h2>

            <?php if ($registerSuccess): ?>
                <div class="success-box"><?= e($registerSuccess) ?></div>
            <?php endif; ?>

            <?php if ($registerError): ?>
                <div class="error-box"><?= e($registerError) ?></div>
            <?php endif; ?>

            <label>Full name
                <input type="text" name="fullname" required>
            </label>

            <label>Email
                <input type="email" name="register_email" required>
            </label>

            <label>Mobile number
                <input
                    type="text"
                    name="phone"
                    placeholder="09XXXXXXXXX"
                    required
                >
            </label>

            <label>Password
                <input type="password" name="register_password" required>
            </label>

            <button class="button" type="submit">
                Create Account
            </button>

            <button
                class="muted-link auth-switch"
                type="button"
                data-auth-switch="login"
            >
                Return to sign in
            </button>

        </form>

    </div>

</section>

<script>

document.addEventListener('DOMContentLoaded', function () {

    const slider = document.getElementById('authSlider');

    document.querySelectorAll('[data-auth-switch]').forEach(function (button) {

        button.addEventListener('click', function () {

            const mode = button.getAttribute('data-auth-switch');

            if (mode === 'register') {

                slider.classList.add('show-register');

                history.replaceState(
                    null,
                    '',
                    'login.php?mode=register'
                );

            } else {

                slider.classList.remove('show-register');

                history.replaceState(
                    null,
                    '',
                    'login.php'
                );
            }
        });
    });
});

</script>

<?php

$content = ob_get_clean();

$title = 'Login - AidLink';

require __DIR__ . '/layout.php';

?>
