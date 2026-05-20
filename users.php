<?php
session_start();
require_once __DIR__ . '/app/User.php';
require_once __DIR__ . '/app/helpers.php';
require_role(['admin']);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        User::createByAdmin(
            trim($_POST['fullname'] ?? ''),
            trim($_POST['email'] ?? ''),
            trim($_POST['phone'] ?? ''),
            $_POST['password'] ?? '',
            $_POST['role'] ?? 'citizen'
        );
        $success = 'Account created successfully.';
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

$users = User::all();

ob_start();
?>
<section class="page-head reveal">
    <div>
        <span class="eyebrow">Administration</span>
        <h1>Accounts</h1>
        <p class="lead">Create coordinator or volunteer accounts and review registered recipients.</p>
    </div>
</section>

<?php if ($error): ?><div class="error-box reveal"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="success-box reveal"><?= e($success) ?></div><?php endif; ?>

<section class="panel reveal">
    <h2>Create Account</h2>
    <form class="grid-form" method="POST" autocomplete="off">
        <label>Full name
            <input type="text" name="fullname" required>
        </label>
        <label>Email
            <input type="email" name="email" required>
        </label>
        <label>Mobile number
            <input type="text" name="phone" placeholder="09XXXXXXXXX" required>
        </label>
        <label>Role
            <select name="role">
                <option value="citizen">Recipient</option>
                <option value="staff">Volunteer Coordinator</option>
                <option value="admin">Administrator</option>
            </select>
        </label>
        <label>Temporary password
            <input type="password" name="password" required>
        </label>
        <button class="button create-account-button" type="submit">Create Account</button>
    </form>
</section>

<section class="panel reveal">
    <h2>Account Directory</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Created</th></tr></thead>
            <tbody>
                <?php foreach ($users as $account): ?>
                    <tr>
                        <td><?= e($account['fullname']) ?></td>
                        <td><?= e($account['email']) ?></td>
                        <td><?= e($account['phone']) ?></td>
                        <td><span class="pill role-badge"><?= e(role_label($account['role'])) ?></span></td>
                        <td><?= e($account['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = ob_get_clean();
$title = 'Users - AidLink';
require __DIR__ . '/layout.php';
