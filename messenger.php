<?php
session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Messenger.php';
require_once __DIR__ . '/helpers.php';

require_login();

$user = current_user();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_direct'])) {
    try {
        $conversationId = Messenger::findOrCreateDirect(
            (int) $user['id'],
            $_POST['recipient_lookup'] ?? ''
        );

        redirect('messenger.php?conversation_id=' . $conversationId);

    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_conversation'])) {
    try {
        $memberIds = $_POST['members'] ?? [];

        $conversationId = Messenger::createConversation(
            trim($_POST['title'] ?? ''),
            is_array($memberIds) ? $memberIds : [],
            (int) $user['id'],
            true
        );

        redirect('messenger.php?conversation_id=' . $conversationId);

    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_members'])) {
    try {
        Messenger::addMembers(
            (int) $_POST['conversation_id'],
            (int) $user['id'],
            $_POST['members'] ?? []
        );

        redirect('messenger.php?conversation_id=' . (int) $_POST['conversation_id']);

    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    try {
        Messenger::send(
            (int) $_POST['conversation_id'],
            (int) $user['id'],
            $_POST['message'] ?? ''
        );

        redirect('messenger.php?conversation_id=' . (int) $_POST['conversation_id']);

    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_message'])) {
    try {
        Messenger::deleteMessage(
            (int) $_POST['message_id'],
            (int) $user['id']
        );

        redirect('messenger.php?conversation_id=' . (int) $_POST['conversation_id']);

    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

$conversations = Messenger::conversations((int) $user['id']);

$activeId = isset($_GET['conversation_id'])
    ? (int) $_GET['conversation_id']
    : (int) ($conversations[0]['id'] ?? 0);

$activeConversation = $activeId
    ? Messenger::findConversation($activeId, (int) $user['id'])
    : null;

$messages = $activeConversation
    ? Messenger::messages($activeId, (int) $user['id'])
    : [];

$availableUsers = Messenger::availableUsers((int) $user['id']);

$members = $activeConversation
    ? Messenger::members($activeId)
    : [];

ob_start();
?>

<section class="page-head reveal slide-right">
    <div>
        <span class="eyebrow">Messenger</span>
        <h1>Messages</h1>
        <p class="lead">Direct and group conversations for recipients, staff, and administrators.</p>
    </div>
</section>

<?php if ($error): ?>
    <div class="error-box"><?= e($error) ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="success-box"><?= e($success) ?></div>
<?php endif; ?>

<section class="messenger-layout messenger-pro">
    <aside class="panel messenger-sidebar reveal fade-up">
        <h2>Inbox</h2>

        <div class="conversation-list">
            <?php foreach ($conversations as $conversation): ?>
                <a class="conversation-item <?= (int) $conversation['id'] === $activeId ? 'active' : '' ?>" href="messenger.php?conversation_id=<?= (int) $conversation['id'] ?>">
                    <div class="conversation-row">
                        <strong><?= e($conversation['title']) ?></strong>

                        <?php if ((int) $conversation['unread'] > 0): ?>
                            <span class="badge-inline"><?= (int) $conversation['unread'] ?></span>
                        <?php endif; ?>
                    </div>

                    <p class="muted"><?= e($conversation['last_message'] ?? 'No messages yet.') ?></p>
                </a>
            <?php endforeach; ?>

            <?php if (!$conversations): ?>
                <p class="muted">No conversations yet.</p>
            <?php endif; ?>
        </div>

        <div class="section-divider"></div>

        <h2>New direct message</h2>

        <form method="POST" class="stack-form">
            <input type="hidden" name="start_direct" value="1">

            <label>Recipient name, email, or number
                <input type="text" name="recipient_lookup" list="peopleLookup" placeholder="Search a person" required>
            </label>

            <button class="button" type="submit">Open Chat</button>
        </form>

        <div class="section-divider"></div>

        <h2>New group</h2>

        <form method="POST" class="stack-form">
            <input type="hidden" name="create_conversation" value="1">

            <label>Group name
                <input type="text" name="title" placeholder="Example: Food Pack Team" required>
            </label>

            <label>Members
                <select name="members[]" multiple size="5" required>
                    <?php foreach ($availableUsers as $person): ?>
                        <option value="<?= (int) $person['id'] ?>">
                            <?= e($person['fullname']) ?> · <?= e($person['phone']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <button class="button" type="submit">Create Group</button>
        </form>
    </aside>

    <article class="panel chat-window messenger-chat reveal fade-up" data-conversation-id="<?= (int) $activeId ?>">
        <?php if ($activeConversation): ?>

            <header class="chat-header">
                <div>
                    <span class="pill"><?= $activeConversation['is_group'] ? 'Group' : 'Direct' ?></span>

                    <h2><?= e($activeConversation['title']) ?></h2>

                    <p class="muted">
                        <?php foreach ($members as $index => $member): ?>
                            <?= $index ? ', ' : '' ?><?= e($member['fullname']) ?>
                        <?php endforeach; ?>
                    </p>
                </div>
            </header>

            <div class="message-thread" id="messageThread">
                <?php foreach ($messages as $message): ?>
                    <div class="message-bubble <?= (int) $message['sender_id'] === (int) $user['id'] ? 'mine' : '' ?>">
                        <span class="message-meta">
                            <?= e($message['fullname']) ?> · <?= e($message['created_at']) ?>
                        </span>

                        <div class="<?= $message['body'] === 'Message deleted' ? 'muted deleted-message' : '' ?>">
                            <?= nl2br(e($message['body'])) ?>
                        </div>

                        <?php if ((int) $message['sender_id'] === (int) $user['id'] && $message['body'] !== 'Message deleted'): ?>
                            <form class="message-delete-form" method="POST" data-confirm="Delete this message?">
                                <input type="hidden" name="delete_message" value="1">
                                <input type="hidden" name="conversation_id" value="<?= (int) $activeId ?>">
                                <input type="hidden" name="message_id" value="<?= (int) $message['id'] ?>">

                                <button class="message-delete-button" type="submit" title="Delete message">
                                    Delete
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <?php if (!$messages): ?>
                    <p class="muted">Send the first message.</p>
                <?php endif; ?>
            </div>

            <form method="POST" class="message-compose">
                <input type="hidden" name="send_message" value="1">
                <input type="hidden" name="conversation_id" value="<?= (int) $activeId ?>">

                <textarea name="message" rows="2" placeholder="Write a message..." required></textarea>

                <button class="button" type="submit">Send</button>
            </form>

            <?php if ((int) $activeConversation['is_group']): ?>
                <details class="group-tools">
                    <summary>Add members</summary>

                    <form method="POST" class="stack-form">
                        <input type="hidden" name="add_members" value="1">
                        <input type="hidden" name="conversation_id" value="<?= (int) $activeId ?>">

                        <select name="members[]" multiple size="4" required>
                            <?php foreach ($availableUsers as $person): ?>
                                <option value="<?= (int) $person['id'] ?>">
                                    <?= e($person['fullname']) ?> · <?= e($person['phone']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <button class="small-button" type="submit">Add Selected</button>
                    </form>
                </details>
            <?php endif; ?>

        <?php else: ?>

            <h2>No conversation selected</h2>
            <p class="muted">Search a recipient or create a group to begin.</p>

        <?php endif; ?>
    </article>
</section>

<datalist id="peopleLookup">
    <?php foreach ($availableUsers as $person): ?>
        <option value="<?= e($person['fullname']) ?>"><?= e($person['phone']) ?> · <?= e($person['email']) ?></option>
        <option value="<?= e($person['phone']) ?>"><?= e($person['fullname']) ?></option>
        <option value="<?= e($person['email']) ?>"><?= e($person['fullname']) ?></option>
    <?php endforeach; ?>
</datalist>

<script>
    const thread = document.getElementById('messageThread');

    if (thread) {
        thread.scrollTop = thread.scrollHeight;
    }
</script>

<?php
$content = ob_get_clean();
$title = 'Messenger - AidLink';

require __DIR__ . '/layout.php';
?>
