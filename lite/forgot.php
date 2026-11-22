<?php
/**
 * Lite: request a password-reset email (Forgot credentials?).
 */
require_once __DIR__ . '/_bootstrap.php';
require_once dirname(__DIR__) . '/lib/password-reset-helpers.php';
choosology_lite_bootstrap();

global $db;

if (!empty($_SESSION['user'])) {
	header('Location: ' . choosology_lite_url('index.php'));
	exit;
}

$error = '';
$info = '';
$debugUrl = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	/* Honeypot */
	if (trim((string) ($_POST['lab_fax'] ?? '')) !== '') {
		$info = CHOOSOLOGY_RESET_GENERIC_OK;
	} else {
		$identity = trim(choosology_undo_connect_string_mutation((string) ($_POST['identity'] ?? '')));
		$result = choosology_password_reset_request($db, $identity);
		if (empty($result['ok']) && !empty($result['error'])) {
			$error = (string) $result['error'];
		} else {
			$info = (string) ($result['message'] ?? CHOOSOLOGY_RESET_GENERIC_OK);
			if (!empty($result['debug_url'])) {
				$debugUrl = (string) $result['debug_url'];
			}
		}
	}
}

choosology_lite_header(array(
	'title' => 'Forgot credentials',
	'active' => 'login',
));
?>
<fieldset class="lite-panel">
	<legend>Forgot credentials?</legend>
	<p class="lite-muted">Enter your lab handle or contact address. If we find a matching account, we email a one-hour reset link.</p>
	<?php if ($error !== '') { ?><p class="lite-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p><?php } ?>
	<?php if ($info !== '') { ?>
		<p class="lite-ok"><?php echo htmlspecialchars($info, ENT_QUOTES, 'UTF-8'); ?></p>
		<?php if ($debugUrl !== '') { ?>
			<p class="lite-muted">Dev link: <a href="<?php echo htmlspecialchars($debugUrl, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($debugUrl, ENT_QUOTES, 'UTF-8'); ?></a></p>
		<?php } ?>
	<?php } ?>
	<form class="lite-form" method="post" action="<?php echo htmlspecialchars(choosology_lite_url('forgot.php'), ENT_QUOTES, 'UTF-8'); ?>">
		<label for="identity">Lab handle or email</label>
		<input type="text" id="identity" name="identity" required maxlength="100" autocomplete="username"
			value="<?php echo htmlspecialchars((string) ($_POST['identity'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
		<div class="signup-honeypot" aria-hidden="true" style="position:absolute;left:-9999px;height:0;overflow:hidden;">
			<label for="lab_fax">Lab fax</label>
			<input type="text" id="lab_fax" name="lab_fax" tabindex="-1" autocomplete="off">
		</div>
		<p><button type="submit" class="lite-btn">Send reset link</button></p>
	</form>
	<p class="lite-muted"><a href="<?php echo htmlspecialchars(choosology_lite_url('login.php'), ENT_QUOTES, 'UTF-8'); ?>">Back to sign in</a></p>
</fieldset>
<?php
choosology_lite_footer();
