<?php
session_start();
require_once __DIR__ . '/../app/Report.php';
require_once __DIR__ . '/../app/ServiceRequest.php';
require_once __DIR__ . '/../app/helpers.php';
require_login();

$user = current_user();
$isRecipient = $user['role'] === 'citizen';
$link = '';
$actionType = '';
$note = '';

if (isset($_POST['xml'])) {
    $link = Report::exportXml($isRecipient ? (int) $user['id'] : null, $user['role']);
    $actionType = 'xml';
    $note = 'XML aid records were prepared and saved in the exports folder.';
}

if (isset($_POST['html'])) {
    $link = Report::transformToHtml($isRecipient ? (int) $user['id'] : null, $user['role']);
    $actionType = 'html';
    $note = 'A browser-ready report was prepared and saved in the exports folder.';
}

$requests = $isRecipient ? ServiceRequest::all((int) $user['id']) : ServiceRequest::all(null, $user['role']);

ob_start();
?>
<section class="page-head reveal slide-right">
    <div>
        <span class="eyebrow"><?= $isRecipient ? 'My Records' : 'Records Office' ?></span>
        <h1><?= $isRecipient ? 'Aid Summary' : 'Reports' ?></h1>
        <p class="lead">
            <?= $isRecipient
                ? 'View a personal copy of submitted aid requests and their current status.'
                : 'Prepare aid request records for review, filing, and presentation.' ?>
        </p>
    </div>
</section>

<section class="panel reveal fade-up action-panel">
    <form class="report-actions" method="POST">
        <button class="button" name="xml">Export XML</button>
        <button class="button secondary" name="html">Open HTML Report</button>
    </form>

    <?php if ($link): ?>
        <div class="success-box"><?= e($note) ?></div>
        <div class="file-list">
            <?php if ($actionType === 'xml'): ?>
                <a href="exports/xml_report.html" target="_blank">Open formatted XML view</a>
                <a href="exports/requests.xml" target="_blank">Open saved XML source</a>
            <?php endif; ?>
            <?php if ($actionType === 'html'): ?>
                <a href="exports/report.html" target="_blank">Open saved HTML report</a>
            <?php endif; ?>
            <p class="muted export-path">Saved inside: <code>public/exports/</code></p>
        </div>
    <?php endif; ?>
</section>

<section class="panel reveal fade-up">
    <h2><?= $isRecipient ? 'Personal Aid List' : 'Aid Records' ?></h2>
    <div class="table-wrap">
        <table class="records-table report-table">
            <thead>
                <tr>
                    <?php if (!$isRecipient): ?><th>Recipient</th><?php endif; ?>
                    <th>Category</th><th>Need</th><th>Urgency</th><th>Location</th><th>Description</th><th>Status</th><th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $request): ?>
                    <tr>
                        <?php if (!$isRecipient): ?><td><?= e($request['fullname']) ?></td><?php endif; ?>
                        <td><?= e($request['category']) ?></td>
                        <td><?= e($request['quantity'] ?? 'Not specified') ?></td>
                        <td><span class="status urgency"><?= e($request['urgency'] ?? 'Medium') ?></span></td>
                        <td><?= e($request['location']) ?></td>
                        <td class="description-cell"><?= e($request['description'] ?? '') ?></td>
                        <td><span class="status"><?= e($request['status']) ?></span></td>
                        <td><?= e($request['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$requests): ?><tr><td colspan="<?= $isRecipient ? 7 : 8 ?>">No records available.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = ob_get_clean();
$title = 'Reports - AidLink';
require __DIR__ . '/layout.php';
