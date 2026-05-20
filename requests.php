<?php
session_start();
require_once __DIR__ . '/../app/ServiceRequest.php';
require_once __DIR__ . '/../app/Queue.php';
require_once __DIR__ . '/../app/helpers.php';
require_login();

$user = current_user();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_request'])) {
    ServiceRequest::create(
        (int) $user['id'],
        trim($_POST['category']),
        trim($_POST['quantity']),
        trim($_POST['urgency']),
        trim($_POST['location']),
        trim($_POST['description'])
    );
    $message = 'Your aid request has been submitted for review.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    require_role(['admin', 'staff']);
    ServiceRequest::updateStatus((int) $_POST['request_id'], $_POST['status'], trim($_POST['remarks']));
    $message = 'The aid request record has been updated.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_request'])) {
    ServiceRequest::delete((int) $_POST['request_id'], $user);
    $message = 'The aid request record has been deleted.';
}

$requests = $user['role'] === 'citizen'
    ? ServiceRequest::all((int) $user['id'])
    : ServiceRequest::all(null, $user['role']);

ob_start();
$locations = [
    'Aid Distribution Center',
    'Community Pantry Point',
    'Health Center',
    'Covered Court Relief Desk',
    'Public Market Drop-Off Point',
    'Evacuation Center',
    'Coastal Aid Point',
    'Riverside Delivery Area',
    'Main Road Distribution Point',
    'Chapel Donation Area',
    'Mati City, Davao Oriental',
];
?>
<section class="page-head reveal">
    <div>
        <span class="eyebrow">Aid Coordination</span>
        <h1>Aid Requests</h1>
        <p class="lead">Request assistance, coordinate donations, and monitor aid progress in one place.</p>
    </div>
</section>

<datalist id="requestLocationSuggestions">
    <?php foreach ($locations as $location): ?>
        <option value="<?= e($location) ?>">
    <?php endforeach; ?>
</datalist>

<?php if ($message): ?>
    <div class="success-box reveal"><?= e($message) ?></div>
<?php endif; ?>

<section class="request-layout">
    <?php if ($user['role'] === 'citizen'): ?>
        <article class="panel reveal request-form-card">
            <h2>New Aid Request</h2>
            <form class="grid-form" method="POST">
                <input type="hidden" name="create_request" value="1">
                <label class="wide">Category
                    <select name="category" required>
                        <option value="Food Assistance">Food Assistance</option>
                        <option value="Medicine Assistance">Medicine Assistance</option>
                        <option value="Clothing Assistance">Clothing Assistance</option>
                        <option value="Water Supply">Water Supply</option>
                        <option value="Volunteer Support">Volunteer Support</option>
                        <option value="School Supplies">School Supplies</option>
                        <option value="Emergency Relief">Emergency Relief</option>
                    </select>
                </label>
                <label>Quantity / Need
                    <input type="text" name="quantity" placeholder="Example: 3 food packs or 2 blankets" required>
                </label>
                <label>Urgency
                    <select name="urgency" required>
                        <option>Low</option>
                        <option selected>Medium</option>
                        <option>High</option>
                        <option>Emergency</option>
                    </select>
                </label>
                <label class="wide">Location
                    <input type="text" name="location" data-location-suggest placeholder="Search or type a specific location" required>
                </label>
                <p class="suggestion-hint wide">Start typing to choose a known area, or enter a more specific address.</p>
                <label class="wide">Description
                    <textarea name="description" rows="5" placeholder="Briefly describe the needed assistance." required></textarea>
                </label>
                <button class="button wide" type="submit">Submit Aid Request</button>
            </form>
        </article>
    <?php endif; ?>

    <article class="panel reveal <?= $user['role'] !== 'citizen' ? 'wide-records' : '' ?>">
        <h2>Aid Request Records</h2>
        <div class="table-wrap">
            <table class="records-table request-table <?= $user['role'] !== 'citizen' ? 'has-actions' : 'recipient-view' ?>">
                <thead>
                    <tr>
                        <th>Recipient</th>
                        <th>Category</th>
                        <th>Need</th>
                        <th>Urgency</th>
                        <th>Location</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Remarks</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $request): ?>
                        <tr>
                            <td><?= e($request['fullname']) ?></td>
                            <td><?= e($request['category']) ?></td>
                            <td><?= e($request['quantity'] ?? 'Not specified') ?></td>
                            <td><span class="status urgency"><?= e($request['urgency'] ?? 'Medium') ?></span></td>
                            <td><?= e($request['location']) ?></td>
                            <td class="description-cell"><?= e($request['description'] ?? '') ?></td>
                            <td><span class="status"><?= e($request['status']) ?></span></td>
                            <td><?= e($request['remarks'] ?? 'For review') ?></td>
                            <?php if ($user['role'] !== 'citizen'): ?>
                                <td>
                                    <form class="inline-form" method="POST">
                                        <input type="hidden" name="update_status" value="1">
                                        <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
                                        <select name="status">
                                            <?php foreach (['Pending', 'Approved', 'Preparing', 'Delivering', 'Completed', 'Rejected'] as $statusOption): ?>
                                                <option <?= $request['status'] === $statusOption ? 'selected' : '' ?>><?= e($statusOption) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="text" name="remarks" placeholder="Remarks">
                                        <button class="small-button">Save</button>
                                    </form>
                                    <?php if (in_array($request['status'], ['Completed', 'Rejected'])): ?>
                                        <form class="inline-form compact-action" method="POST" data-confirm="Remove this completed aid request from your account view only?">
                                            <input type="hidden" name="delete_request" value="1">
                                            <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
                                            <button class="small-button danger-button" type="submit">Delete</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="muted">Complete first</span>
                                    <?php endif; ?>
                                </td>
                            <?php else: ?>
                                <td>
                                    <form class="inline-form compact-action" method="POST" data-confirm="Delete this aid request record?">
                                        <input type="hidden" name="delete_request" value="1">
                                        <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
                                        <button class="small-button danger-button" type="submit">Delete</button>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>

<div id="aidMessageModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.65); z-index:9999; align-items:center; justify-content:center; padding:20px;">
    <div style="background:#081a33; color:white; width:min(500px,100%); border-radius:22px; padding:24px; position:relative; box-shadow:0 20px 50px rgba(0,0,0,.45);">
        <button type="button"
            onclick="closeAidMessage()"
            style="position:absolute; top:14px; right:16px; background:none; border:none; color:white; font-size:24px; cursor:pointer;">
            ×
        </button>

        <h3 id="aidMessageTitle" style="margin-bottom:14px;">Aid Message</h3>

        <div id="aidMessageBody"
            style="line-height:1.7; background:rgba(255,255,255,.06); padding:16px; border-radius:16px;">
        </div>
    </div>
</div>

<script>
function showAidMessage(message, status) {
    document.getElementById('aidMessageModal').style.display = 'flex';
    document.getElementById('aidMessageTitle').innerText = status + ' Message';
    document.getElementById('aidMessageBody').innerText = message;
}

function closeAidMessage() {
    document.getElementById('aidMessageModal').style.display = 'none';
}

window.addEventListener('click', function(event) {
    const modal = document.getElementById('aidMessageModal');
    if (event.target === modal) {
        closeAidMessage();
    }
});
</script>

<?php
$content = ob_get_clean();

$title = 'Requests - AidLink';
require __DIR__ . '/layout.php';
