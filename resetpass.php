<?php
/**
 * Password reset landing page (email link target).
 * Works for Classic and Lite; no SPA shell required.
 */
require_once __DIR__ . '/connect.php';
require_once __DIR__ . '/auxfuncs.php';
require_once __DIR__ . '/lib/password-reset-helpers.php';
require_once __DIR__ . '/lib/lite-helpers.php';

choosology_password_reset_ensure_schema($db);

$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
if (function_exists('choosology_undo_connect_string_mutation')) {
	$token = choosology_undo_connect_string_mutation($token);
}

$error = '';
$done = false;
$name = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$pass1 = (string) ($_POST['pass1'] ?? '');
	$pass2 = (string) ($_POST['pass2'] ?? '');
	if (function_exists('choosology_undo_connect_string_mutation')) {
		$pass1 = choosology_undo_connect_string_mutation($pass1);
		$pass2 = choosology_undo_connect_string_mutation($pass2);
	}
	$result = choosology_password_reset_confirm($db, $token, $pass1, $pass2);
	if (!empty($result['ok'])) {
		$done = true;
		$name = (string) ($result['name'] ?? '');
	} else {
		$error = (string) ($result['error'] ?? 'Could not reset the password.');
	}
} else {
	$check = choosology_password_reset_validate_token($db, $token);
	if (empty($check['ok'])) {
		$error = (string) ($check['error'] ?? 'This reset link is invalid or has expired.');
	} else {
		$name = (string) ($check['name'] ?? '');
	}
}

$classicHome = htmlspecialchars(choosology_site_url('index.php?stay=1'), ENT_QUOTES, 'UTF-8');
$liteLogin = htmlspecialchars(choosology_lite_url('login.php'), ENT_QUOTES, 'UTF-8');
$self = htmlspecialchars(choosology_site_url('resetpass.php'), ENT_QUOTES, 'UTF-8');
$tokenAttr = htmlspecialchars($token, ENT_QUOTES, 'UTF-8');
$nameAttr = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$errorAttr = htmlspecialchars($error, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Password reset — Choosology</title>
	<link rel="stylesheet" href="<?php echo htmlspecialchars(choosology_site_url('style/choosology.css?v=reset1'), ENT_QUOTES, 'UTF-8'); ?>">
	<style>
		body.resetpass-page { margin: 0; min-height: 100vh; background: #d8e0d4; font-family: Trebuchet MS, Verdana, sans-serif; }
		.resetpass-wrap { max-width: 420px; margin: 48px auto; padding: 0 16px; }
		.resetpass-card { background: #f7f3ea; border: 2px solid #242a2f; box-shadow: 4px 4px 0 #242a2f; padding: 1.25rem 1.35rem; }
		.resetpass-card h1 { margin: 0 0 0.35rem; font-size: 1.35rem; }
		.resetpass-card p { margin: 0.5rem 0; line-height: 1.4; }
		.resetpass-card label { display: block; margin-top: 0.75rem; font-size: 0.9rem; font-weight: 700; }
		.resetpass-card input[type=password] { width: 100%; box-sizing: border-box; margin-top: 0.25rem; padding: 0.45rem 0.5rem; border: 1px solid #666; }
		.resetpass-actions { margin-top: 1rem; display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap; }
		.resetpass-actions button { padding: 0.4rem 0.9rem; font-weight: 700; cursor: pointer; }
		.resetpass-error { color: #7c2d34; font-weight: 700; }
		.resetpass-ok { color: #1f5a2e; font-weight: 700; }
		.resetpass-links a { color: #1c5a7a; }
	</style>
</head>
<body class="resetpass-page">
	<div class="resetpass-wrap">
		<div class="resetpass-card">
			<p class="restricted-eyebrow" style="margin:0;letter-spacing:0.08em;color:#7c2d34;font-size:0.75rem;font-weight:700;">CREDENTIALS</p>
			<h1>Password reset</h1>
			<?php if ($done) { ?>
				<p class="resetpass-ok">Password updated<?php echo $nameAttr !== '' ? ' for ' . $nameAttr : ''; ?>.</p>
				<p>You can sign in with the new password.</p>
				<p class="resetpass-links"><a href="<?php echo $classicHome; ?>">Classic sign in</a> · <a href="<?php echo $liteLogin; ?>">Lite sign in</a></p>
			<?php } elseif ($error !== '' && $_SERVER['REQUEST_METHOD'] !== 'POST' && $name === '') { ?>
				<p class="resetpass-error"><?php echo $errorAttr; ?></p>
				<p class="resetpass-links"><a href="<?php echo $classicHome; ?>">Back to Classic</a> · <a href="<?php echo $liteLogin; ?>">Lite sign in</a></p>
			<?php } else { ?>
				<p>Choose a new password<?php echo $nameAttr !== '' ? ' for <strong>' . $nameAttr . '</strong>' : ''; ?>.</p>
				<?php if ($error !== '') { ?><p class="resetpass-error"><?php echo $errorAttr; ?></p><?php } ?>
				<form method="post" action="<?php echo $self; ?>" autocomplete="off">
					<input type="hidden" name="token" value="<?php echo $tokenAttr; ?>">
					<label for="pass1">New password</label>
					<input type="password" id="pass1" name="pass1" required minlength="6" maxlength="72" autocomplete="new-password">
					<label for="pass2">Confirm password</label>
					<input type="password" id="pass2" name="pass2" required minlength="6" maxlength="72" autocomplete="new-password">
					<div class="resetpass-actions">
						<button type="submit">Save new password</button>
						<a href="<?php echo $classicHome; ?>">Cancel</a>
					</div>
				</form>
			<?php } ?>
		</div>
	</div>
</body>
</html>
