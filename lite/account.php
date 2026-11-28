<?php
require_once __DIR__ . '/_bootstrap.php';
choosology_lite_bootstrap();

if (empty($_SESSION['user'])) {
	header('Location: ' . choosology_lite_url('login.php'));
	exit;
}

choosology_lite_header(array('title' => 'My Information', 'active' => 'home'));
?>
<fieldset class="lite-panel">
	<legend>My Information</legend>
	<p>Signed in as <strong><?php echo htmlspecialchars((string) $_SESSION['user'], ENT_QUOTES, 'UTF-8'); ?></strong>.</p>
</fieldset>
<fieldset class="lite-panel lite-account-remove">
	<legend>Remove profile and submitted content</legend>
	<p class="lite-account-remove-warn">This cannot be undone. Your account, login, uploaded images, ratings, and experiments will be removed from browse, search, and play. A placeholder stays so other people&rsquo;s activity still has a target. Your comments stay visible as &ldquo;[deleted]&rdquo; by &ldquo;[deleted user]&rdquo;.</p>
	<form id="lite-remove-form" method="post" action="<?php echo htmlspecialchars(choosology_lite_url('account.php'), ENT_QUOTES, 'UTF-8'); ?>">
		<label for="lite_remove_password">Type your current password</label>
		<input type="password" id="lite_remove_password" autocomplete="current-password">
		<label class="lite-radio"><input type="checkbox" id="lite_remove_confirm"> I understand this cannot be undone.</label>
		<p><button type="button" class="lite-btn" id="lite_remove">Remove my profile and content</button></p>
		<p id="lite_remove_status" role="status"></p>
	</form>
</fieldset>
<script>
(function () {
	var btn = document.getElementById("lite_remove");
	if (!btn) return;
	btn.addEventListener("click", function () {
		var password = (document.getElementById("lite_remove_password") || {}).value || "";
		var status = document.getElementById("lite_remove_status");
		var checked = document.getElementById("lite_remove_confirm") && document.getElementById("lite_remove_confirm").checked;
		status.textContent = "";
		if (!checked) {
			status.textContent = "Check the box. This cannot be undone.";
			return;
		}
		if (!password) {
			status.textContent = "Type your current password.";
			return;
		}
		if (!window.confirm("Remove your profile and submitted content? This cannot be undone.")) {
			return;
		}
		btn.disabled = true;
		fetch(<?php echo json_encode(choosology_site_url('ajax/removeaccount.php')); ?>, {
			method: "POST",
			credentials: "same-origin",
			headers: { "Content-Type": "application/json" },
			body: JSON.stringify({ password: password, confirm: 1 })
		}).then(function (res) { return res.json(); }).then(function (data) {
			if (data && data.ok) {
				window.location.href = <?php echo json_encode(choosology_lite_url('index.php')); ?>;
				return;
			}
			status.textContent = (data && data.error) ? data.error : "Nothing was removed.";
			btn.disabled = false;
		}).catch(function () {
			status.textContent = "Nothing was removed.";
			btn.disabled = false;
		});
	});
})();
</script>
<?php
choosology_lite_footer();
