<?php
require_once __DIR__ . '/_bootstrap.php';
choosology_lite_bootstrap();

global $db, $sel;

$error = '';
$info = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = (string) ($_POST['action'] ?? 'login');
	if ($action === 'logout') {
		if (!empty($_SESSION['user']) && function_exists('eatCookies')) {
			eatCookies((string) $_SESSION['user']);
		}
		$_SESSION['user'] = false;
		$_SESSION['usertype'] = false;
		@session_destroy();
		@session_start();
		$dest = choosology_lite_url('index.php');
		header('Location: ' . $dest);
		exit;
	}

	$user = strip_tags(trim((string) ($_POST['logname'] ?? '')));
	$password = (string) ($_POST['logpass'] ?? '');
	$remember = !empty($_POST['rememberlogin']);
	if ($user === '' || $password === '') {
		$error = 'Enter a username and password.';
	} else {
		if (!isset($sel) || $sel === '') {
			$sel = 'cYo';
		}
		$hash = md5($sel . $password);
		$escUser = mysqli_real_escape_string($db, $user);
		$escHash = mysqli_real_escape_string($db, $hash);
		$q = "SELECT * FROM users WHERE (name='$escUser' OR email='$escUser') AND pass='$escHash' LIMIT 1";
		$result = mysqli_query($db, $q);
		$row = $result ? mysqli_fetch_array($result) : false;
		if ($row) {
			$uname = mysqli_real_escape_string($db, (string) $row['name']);
			@mysqli_query($db, "UPDATE users SET lastlogin=NOW() WHERE name='$uname' LIMIT 1");
			session_regenerate_id(true);
			$_SESSION['user'] = $row['name'];
			$_SESSION['usertype'] = $row['usertype'];
			if ($remember && function_exists('makeCookies')) {
				if (function_exists('eatCookies')) {
					eatCookies((string) $row['name']);
				}
				makeCookies((string) $row['name']);
			}
			/* Sync Lite preference when signing in from Lite. */
			choosology_ui_save_preference($db, 'lite');
			$next = trim((string) ($_POST['next'] ?? ''));
			if ($next === '' || strpos($next, '//') !== false) {
				$next = choosology_lite_url('index.php');
			}
			header('Location: ' . $next);
			exit;
		}
		$error = 'Wrong username or password.';
	}
}

$loggedIn = !empty($_SESSION['user']);
choosology_lite_header(array(
	'title' => $loggedIn ? 'Sign out' : 'Sign in',
	'active' => 'login',
));
?>
<fieldset class="lite-panel">
	<legend><?php echo $loggedIn ? 'Sign out' : 'Sign in'; ?></legend>
	<?php if ($loggedIn) { ?>
		<p>Signed in as <strong><?php echo htmlspecialchars((string) $_SESSION['user'], ENT_QUOTES, 'UTF-8'); ?></strong>.</p>
		<form method="post" action="<?php echo htmlspecialchars(choosology_lite_url('login.php'), ENT_QUOTES, 'UTF-8'); ?>">
			<input type="hidden" name="action" value="logout">
			<button type="submit" class="lite-btn">Sign out</button>
		</form>
		<p class="lite-muted" style="margin-top:0.75rem;">New lab access applications remain on the Classic home screen.</p>
	<?php } else { ?>
		<?php if ($error !== '') { ?><p class="lite-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p><?php } ?>
		<form class="lite-form" method="post" action="<?php echo htmlspecialchars(choosology_lite_url('login.php'), ENT_QUOTES, 'UTF-8'); ?>">
			<input type="hidden" name="action" value="login">
			<input type="hidden" name="next" value="<?php echo htmlspecialchars(choosology_lite_url('index.php'), ENT_QUOTES, 'UTF-8'); ?>">
			<label for="logname">User name or email</label>
			<input type="text" id="logname" name="logname" autocomplete="username" required maxlength="100"
				value="<?php echo htmlspecialchars((string) ($_POST['logname'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
			<label for="logpass">Password</label>
			<input type="password" id="logpass" name="logpass" autocomplete="current-password" required>
			<label class="lite-check"><input type="checkbox" name="rememberlogin" value="1"> Remember me</label>
			<p><button type="submit" class="lite-btn">Sign in</button></p>
		</form>
		<p class="lite-muted">Need an account? <a href="<?php echo htmlspecialchars(choosology_classic_url('home'), ENT_QUOTES, 'UTF-8'); ?>">Apply for lab access in Classic</a>.</p>
	<?php } ?>
</fieldset>
<?php
choosology_lite_footer();
