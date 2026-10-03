<?php
require_once __DIR__ . '/_bootstrap.php';
choosology_lite_bootstrap();

global $db;

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id < 1) {
	http_response_code(404);
	choosology_lite_header(array('title' => 'Structure', 'active' => 'browse'));
	echo '<fieldset class="lite-panel"><legend>Error</legend><p>No experiment specified.</p></fieldset>';
	choosology_lite_footer();
	exit;
}

$advRows = runquery_assoc("SELECT id, title, user, avail, `begin` FROM advs WHERE id = '$id' LIMIT 1");
$adv = is_array($advRows) && $advRows ? $advRows[0] : null;
if (!$adv) {
	http_response_code(404);
	choosology_lite_header(array('title' => 'Structure', 'active' => 'browse'));
	echo '<fieldset class="lite-panel"><legend>Error</legend><p>Experiment not found.</p></fieldset>';
	choosology_lite_footer();
	exit;
}

$owner = (string) ($adv['user'] ?? '');
$sessionUser = (string) ($_SESSION['user'] ?? '');
$avail = (string) ($adv['avail'] ?? '');
$canView = ($avail === 'public') || ($sessionUser !== '' && $sessionUser === $owner);
if (!$canView) {
	http_response_code(403);
	choosology_lite_header(array('title' => 'Structure', 'active' => 'browse'));
	echo '<fieldset class="lite-panel"><legend>Private</legend><p>Structure is not available for this experiment.</p></fieldset>';
	choosology_lite_footer();
	exit;
}

$title = trim(decode((string) ($adv['title'] ?? ''), 1));
if ($title === '') {
	$title = 'Experiment #' . $id;
}
$ascii = choosology_lite_ascii_structure($db, $id);
$viewUrl = choosology_lite_url('view.php?id=' . $id);
$classicEdit = choosology_classic_url('edit/' . $id);

choosology_lite_header(array('title' => 'Structure · ' . $title, 'active' => 'browse'));
?>
<fieldset class="lite-panel">
	<legend>Structure (read-only)</legend>
	<p class="lite-meta"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?> · ASCII map of active screens</p>
	<pre class="lite-ascii"><?php echo htmlspecialchars($ascii, ENT_QUOTES, 'UTF-8'); ?></pre>
	<p class="lite-muted">Editing the graph requires Classic.</p>
	<p>
		<a class="lite-btn" href="<?php echo htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8'); ?>">← Back to play</a>
		<?php if ($sessionUser !== '' && $sessionUser === $owner) { ?>
			<a class="lite-btn" href="<?php echo htmlspecialchars($classicEdit, ENT_QUOTES, 'UTF-8'); ?>">Open graph editor in Classic →</a>
		<?php } else { ?>
			<a class="lite-btn" href="<?php echo htmlspecialchars(choosology_classic_url('view/' . $id), ENT_QUOTES, 'UTF-8'); ?>">Open in Classic →</a>
		<?php } ?>
	</p>
</fieldset>
<?php
choosology_lite_footer();
