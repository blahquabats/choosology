<?php
/**
 * Lite RESTRICTED hub.
 *
 * The full visual guide editor is Classic-only; Lite keeps treasury,
 * credentials, and comment moderation usable with plain forms.
 */
require_once __DIR__ . '/_bootstrap.php';
choosology_lite_bootstrap();
require_once dirname(__DIR__) . '/messagesfunc.php';
require_once dirname(__DIR__) . '/lib/restricted-helpers.php';

global $db;

if (!choosology_restricted_is_admin()) {
	http_response_code(403);
	choosology_lite_header(array('title' => 'RESTRICTED', 'active' => 'restricted'));
	echo '<fieldset class="lite-panel"><legend>Access denied</legend><p>Lab admin authorization is required.</p></fieldset>';
	choosology_lite_footer();
	exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = (string) ($_POST['action'] ?? '');
	$result = array('ok' => false, 'error' => 'Unknown action.');
	if ($action === 'award_degree') {
		$result = choosology_restricted_grant_degree(
			$db,
			(string) $_SESSION['user'],
			(string) ($_POST['to'] ?? ''),
			(string) ($_POST['degree'] ?? '')
		);
	} elseif ($action === 'award_scrip') {
		$result = choosology_datascrip_admin_grant(
			$db,
			(string) $_SESSION['user'],
			(string) ($_POST['to'] ?? ''),
			(int) ($_POST['amount'] ?? 0),
			(string) ($_POST['memo'] ?? ''),
			!empty($_POST['all_users'])
		);
	} elseif ($action === 'approve' || $action === 'delete' || $action === 'approve_all') {
		$result = choosology_restricted_moderate_comment(
			$db,
			$action,
			(int) ($_POST['comment_id'] ?? 0)
		);
	}
	$_SESSION['restricted_flash'] = !empty($result['ok'])
		? 'Action completed.'
		: (string) ($result['error'] ?? 'Action failed.');
	header('Location: ' . choosology_lite_url('restricted.php'));
	exit;
}

$flash = isset($_SESSION['restricted_flash']) ? (string) $_SESSION['restricted_flash'] : '';
unset($_SESSION['restricted_flash']);
$users = choosology_restricted_usernames($db);
$degrees = choosology_achievement_catalog();
$comments = choosology_restricted_pending_comments($db);
$classicGuide = choosology_classic_url('mystuff/restricted');

choosology_lite_header(array('title' => 'RESTRICTED', 'active' => 'restricted'));
?>
<fieldset class="lite-panel lite-restricted">
	<legend>RESTRICTED — Admin terminal</legend>
	<p>Authorized lab administration.</p>
	<?php if ($flash !== '') { ?>
	<p class="lite-alert"><?php echo htmlspecialchars($flash, ENT_QUOTES, 'UTF-8'); ?></p>
	<?php } ?>
	<p><a class="lite-btn" href="<?php echo htmlspecialchars($classicGuide, ENT_QUOTES, 'UTF-8'); ?>">Open mascot and interaction editor in Classic</a></p>
</fieldset>

<datalist id="restricted-users">
	<?php foreach ($users as $name) { ?>
	<option value="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>">
	<?php } ?>
</datalist>

<fieldset class="lite-panel">
	<legend>Grant Degree</legend>
	<p class="lite-muted">Includes the normal DataScrip reward, trophy trinket, and CLIC notice.</p>
	<form method="post" action="<?php echo htmlspecialchars(choosology_lite_url('restricted.php'), ENT_QUOTES, 'UTF-8'); ?>" class="lite-form">
		<input type="hidden" name="action" value="award_degree">
		<label>Recipient <input name="to" list="restricted-users" maxlength="45" required></label>
		<label>Degree
			<select name="degree" required>
				<?php foreach ($degrees as $key => $meta) { ?>
				<option value="<?php echo htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8'); ?>"><?php
					echo htmlspecialchars((string) $meta['label'], ENT_QUOTES, 'UTF-8');
				?></option>
				<?php } ?>
			</select>
		</label>
		<button type="submit" class="lite-btn">Grant Degree</button>
	</form>
</fieldset>

<fieldset class="lite-panel">
	<legend>Award DataScrip</legend>
	<form method="post" action="<?php echo htmlspecialchars(choosology_lite_url('restricted.php'), ENT_QUOTES, 'UTF-8'); ?>" class="lite-form">
		<input type="hidden" name="action" value="award_scrip">
		<label>Recipient <input name="to" list="restricted-users" maxlength="45"></label>
		<label class="lite-radio"><input type="checkbox" name="all_users" value="1"> Award every account</label>
		<label>Amount <input type="number" name="amount" min="1" max="100000" value="10" required></label>
		<label>Memo <input name="memo" maxlength="255"></label>
		<button type="submit" class="lite-btn">Award DataScrip</button>
	</form>
</fieldset>

<fieldset class="lite-panel">
	<legend>Comment review (<?php echo count($comments); ?>)</legend>
	<?php if (!$comments) { ?>
	<p class="lite-muted">Review queue clear.</p>
	<?php } else { ?>
	<form method="post" action="<?php echo htmlspecialchars(choosology_lite_url('restricted.php'), ENT_QUOTES, 'UTF-8'); ?>">
		<input type="hidden" name="action" value="approve_all">
		<button type="submit" class="lite-btn">Approve all</button>
	</form>
	<ol class="lite-list">
		<?php foreach ($comments as $comment) { ?>
		<li>
			<strong><?php echo htmlspecialchars($comment['author'], ENT_QUOTES, 'UTF-8'); ?></strong>
			<span class="lite-muted"> · <?php echo htmlspecialchars($comment['date'], ENT_QUOTES, 'UTF-8'); ?>
				· <?php echo htmlspecialchars($comment['board'], ENT_QUOTES, 'UTF-8'); ?></span>
			<p><?php echo nl2br(htmlspecialchars($comment['text'], ENT_QUOTES, 'UTF-8')); ?></p>
			<form method="post" action="<?php echo htmlspecialchars(choosology_lite_url('restricted.php'), ENT_QUOTES, 'UTF-8'); ?>" class="lite-inline-form">
				<input type="hidden" name="comment_id" value="<?php echo (int) $comment['id']; ?>">
				<button type="submit" class="lite-btn" name="action" value="approve">Approve</button>
				<button type="submit" class="lite-btn" name="action" value="delete">Delete</button>
			</form>
		</li>
		<?php } ?>
	</ol>
	<?php } ?>
</fieldset>
<?php
choosology_lite_footer();
