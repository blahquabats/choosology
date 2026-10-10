<?php
require_once __DIR__ . '/_bootstrap.php';
choosology_lite_bootstrap();
require_once dirname(__DIR__) . '/lib/datascrip-helpers.php';

global $db;

if (empty($_SESSION['user'])) {
	choosology_lite_header(array('title' => 'Ledger', 'active' => 'ledger'));
	echo '<fieldset class="lite-panel"><legend>Sign in required</legend>';
	echo '<p>Sign in to view your DataScrip ledger.</p>';
	echo '<p><a class="lite-btn" href="' . htmlspecialchars(choosology_lite_url('login.php'), ENT_QUOTES, 'UTF-8') . '">Sign in</a></p>';
	echo '</fieldset>';
	choosology_lite_footer();
	exit;
}

$user = (string) $_SESSION['user'];
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 25;
$total = choosology_datascrip_ledger_count($db, $user);
$totalPages = max(1, (int) ceil($total / $perPage));
if ($page > $totalPages) {
	$page = $totalPages;
}
$offset = ($page - 1) * $perPage;
$entries = choosology_datascrip_ledger_entries($db, $user, $perPage, $offset);
$balance = choosology_datascrip_balance($db, $user);

choosology_lite_header(array('title' => 'Ledger', 'active' => 'ledger'));
choosology_lite_guide_panel('ledger', 'ledger.php' . ($page > 1 ? ('?page=' . $page) : ''));
?>
<fieldset class="lite-panel">
	<legend>DataScrip Ledger</legend>
	<p class="lite-meta">Balance: <strong><?php echo choosology_datascrip_format_html($balance); ?></strong>
		· <?php echo (int) $total; ?> entries · page <?php echo (int) $page; ?> / <?php echo (int) $totalPages; ?></p>
	<?php if (!$entries) { ?>
		<p class="lite-muted">No transactions yet. Daily check-ins, Degrees, and play earn DataScrip.</p>
	<?php } else { ?>
		<table class="lite-ledger-table">
			<thead>
				<tr><th>When</th><th>Amount</th><th>Balance</th><th>Memo</th></tr>
			</thead>
			<tbody>
				<?php foreach ($entries as $e) {
					$when = $e['created_at'] !== '' ? nicedatetime($e['created_at']) : '';
					$amt = (int) $e['amount'];
					$amtHtml = ($amt >= 0 ? '+' : '−') . choosology_datascrip_format_html(abs($amt));
					?>
					<tr>
						<td><?php echo htmlspecialchars($when, ENT_QUOTES, 'UTF-8'); ?></td>
						<td><?php echo $amtHtml; ?></td>
						<td><?php echo choosology_datascrip_format_html((int) $e['balance_after']); ?></td>
						<td><?php echo htmlspecialchars((string) $e['label'], ENT_QUOTES, 'UTF-8'); ?></td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
		<?php if ($totalPages > 1) {
			$mk = static function (int $p): string {
				return choosology_lite_url('ledger.php?page=' . $p);
			};
			?>
			<nav class="lite-pager" aria-label="Ledger pages">
				<?php if ($page > 1) { ?>
					<a class="lite-btn" href="<?php echo htmlspecialchars($mk($page - 1), ENT_QUOTES, 'UTF-8'); ?>">← Newer</a>
				<?php } ?>
				<span class="lite-muted">Page <?php echo (int) $page; ?> / <?php echo (int) $totalPages; ?></span>
				<?php if ($page < $totalPages) { ?>
					<a class="lite-btn" href="<?php echo htmlspecialchars($mk($page + 1), ENT_QUOTES, 'UTF-8'); ?>">Older →</a>
				<?php } ?>
			</nav>
		<?php } ?>
	<?php } ?>
</fieldset>
<?php
choosology_lite_date_format_form('ledger.php' . ($page > 1 ? ('?page=' . $page) : ''));
choosology_lite_guide_form('ledger.php' . ($page > 1 ? ('?page=' . $page) : ''));
choosology_lite_footer();
