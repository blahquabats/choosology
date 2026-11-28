<?php
require_once __DIR__ . '/_bootstrap.php';
choosology_lite_bootstrap();
require_once dirname(__DIR__) . '/lib/try-draft.php';

if (choosology_try_visitor_is_logged_in()) {
	header('Location: ' . choosology_lite_url('index.php'));
	exit;
}

$editor = choosology_site_url('index.php?stay=1') . '#/try';
$preview = choosology_lite_url('view.php?try=1');
$classicHome = choosology_classic_url('home');
choosology_lite_header(array('title' => 'Try it yourself', 'active' => 'home'));
?>
<fieldset class="lite-panel">
	<legend>Try it yourself</legend>
	<p>Build a small adventure without an account. The draft stays in this browser session. It is not written to the lab.</p>
	<p>Open the visual editor, add a few screens, and connect them. Then preview the draft in the normal play view. Eight screens is the ceiling — the same limit as one screen’s outgoing choices.</p>
	<p><?php echo htmlspecialchars(choosology_try_account_message(), ENT_QUOTES, 'UTF-8'); ?></p>
	<p>
		<a class="lite-btn" href="<?php echo htmlspecialchars($editor, ENT_QUOTES, 'UTF-8'); ?>">Open the visual editor</a>
		<a class="lite-btn" href="<?php echo htmlspecialchars($preview, ENT_QUOTES, 'UTF-8'); ?>">Preview this draft</a>
	</p>
	<p class="lite-muted"><a href="<?php echo htmlspecialchars($classicHome, ENT_QUOTES, 'UTF-8'); ?>">Apply for lab access in Classic</a> when you want to keep an experiment.</p>
</fieldset>
<?php
choosology_lite_footer();
