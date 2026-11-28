<?php
/**
 * High-friction removal of a profile and the content that should disappear with it.
 * Classic and Lite both call choosology_account_remove().
 */

function choosology_account_password_matches(string $storedHash, string $password): bool
{
	if (!function_exists('choosology_legacy_password_hash')) {
		require_once __DIR__ . '/choosology-core.php';
	}
	$storedHash = trim($storedHash);
	if ($storedHash === '' || $password === '') {
		return false;
	}
	return hash_equals($storedHash, choosology_legacy_password_hash($password));
}

/**
 * @return array{ok:int,error:string}
 */
function choosology_account_remove(mysqli $db, string $username, string $password): array
{
	$username = trim($username);
	if ($username === '' || $username === '[deleted user]') {
		return array('ok' => 0, 'error' => 'Not signed in.');
	}
	$esc = mysqli_real_escape_string($db, $username);
	$rowRes = mysqli_query($db, "SELECT name, pass FROM users WHERE name='$esc' LIMIT 1");
	$row = $rowRes ? mysqli_fetch_assoc($rowRes) : null;
	if (!is_array($row)) {
		return array('ok' => 0, 'error' => 'Account not found.');
	}
	if (!choosology_account_password_matches((string) ($row['pass'] ?? ''), $password)) {
		return array('ok' => 0, 'error' => 'That password does not match. Nothing was removed.');
	}

	if (!function_exists('choosology_adventure_review_ensure_schema')) {
		require_once __DIR__ . '/adventure-review-helpers.php';
	}
	choosology_adventure_review_ensure_schema($db);

	mysqli_begin_transaction($db);
	try {
		choosology_account_remove_pics($db, $username);
		if (!mysqli_query(
			$db,
			"UPDATE comments SET text='[deleted]', author='[deleted user]' WHERE author='$esc'"
		)) {
			throw new RuntimeException(mysqli_error($db));
		}
		if (!mysqli_query($db, "DELETE FROM ratings WHERE who='$esc'")) {
			throw new RuntimeException(mysqli_error($db));
		}
		choosology_account_remove_experiments($db, $username);
		$deadPass = mysqli_real_escape_string($db, bin2hex(random_bytes(16)));
		if (!mysqli_query(
			$db,
			"UPDATE users SET pass='$deadPass', email='', about='', pic=0, hint='', authent='' WHERE name='$esc' LIMIT 1"
		)) {
			throw new RuntimeException(mysqli_error($db));
		}
		mysqli_query($db, "DELETE FROM oven WHERE user='$esc'");
		mysqli_commit($db);
	} catch (Throwable $e) {
		mysqli_rollback($db);
		return array('ok' => 0, 'error' => 'Could not remove the account. Nothing was changed.');
	}

	if (function_exists('eatCookies')) {
		eatCookies($username);
	}
	$_SESSION['user'] = false;
	$_SESSION['usertype'] = false;
	$_SESSION['view_restricted'] = 0;
	return array('ok' => 1, 'error' => '');
}

function choosology_account_remove_pics(mysqli $db, string $username): void
{
	$esc = mysqli_real_escape_string($db, $username);
	$res = mysqli_query($db, "SELECT id, user, filename FROM pics WHERE user='$esc'");
	if ($res) {
		while ($pic = mysqli_fetch_assoc($res)) {
			if (!is_array($pic)) {
				continue;
			}
			foreach (array(true, false) as $thumb) {
				if (!function_exists('choosology_pic_filesystem_path')) {
					break;
				}
				$path = choosology_pic_filesystem_path($pic, $thumb);
				if (is_string($path) && $path !== '' && is_file($path)) {
					@unlink($path);
				}
			}
		}
	}
	if (!mysqli_query($db, "DELETE FROM pics WHERE user='$esc'")) {
		throw new RuntimeException(mysqli_error($db));
	}
}

function choosology_account_remove_experiments(mysqli $db, string $username): void
{
	$esc = mysqli_real_escape_string($db, $username);
	$res = mysqli_query($db, "SELECT id FROM advs WHERE user='$esc'");
	if (!$res) {
		throw new RuntimeException(mysqli_error($db));
	}
	while ($adv = mysqli_fetch_assoc($res)) {
		$id = (int) ($adv['id'] ?? 0);
		if ($id < 1) {
			continue;
		}
		if (!mysqli_query(
			$db,
			"UPDATE advs SET title='[deleted]', description='', avail='removed', pic='', review_hold=1 WHERE id=$id AND user='$esc' LIMIT 1"
		)) {
			throw new RuntimeException(mysqli_error($db));
		}
		if (!mysqli_query(
			$db,
			"UPDATE advscreens SET name='[deleted]', title='', text='' WHERE advused='$id'"
		)) {
			throw new RuntimeException(mysqli_error($db));
		}
	}
}
