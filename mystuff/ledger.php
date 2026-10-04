<?php
require_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/../auxfuncs.php';
require_once __DIR__ . '/../lib/datascrip-helpers.php';

if (empty($_SESSION['user'])) {
	echo "<div class='intabs'><p class='error'>Please sign in to view your DataScrip ledger.</p></div>";
	return;
}

$user = (string) $_SESSION['user'];
$perPage = 50;
$total = choosology_datascrip_ledger_count($db, $user);
$entries = choosology_datascrip_ledger_entries($db, $user, $perPage, 0);
$balance = choosology_datascrip_balance($db, $user);
?>
<div class="intabs ms-ledger-page">
	<header class="ms-ledger-head">
		<p class="ms-ledger-eyebrow">DataScrip</p>
		<h2 class="ms-ledger-title">Ledger</h2>
		<p class="ms-ledger-balance">Balance: <strong><?php echo choosology_datascrip_format_html($balance); ?></strong></p>
		<p class="ms-ledger-note">Full historical transparency for DataScrip movements (<?php echo choosology_datascrip_sign_html(); ?>). Showing the <?php echo (int) min($perPage, max($total, 0)); ?> most recent of <?php echo (int) $total; ?> entries.</p>
	</header>

	<?php if (!$entries) { ?>
		<p class="ms-ledger-empty">No ledger entries yet. Check in daily, earn Degrees, and play experiments to collect DataScrip.</p>
	<?php } else { ?>
		<table class="ms-ledger-table">
			<thead>
				<tr>
					<th scope="col">When</th>
					<th scope="col">Amount</th>
					<th scope="col">Balance</th>
					<th scope="col">Memo</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($entries as $e) {
					$when = $e['created_at'] !== '' ? nicedatetime($e['created_at']) : '';
					$amt = (int) $e['amount'];
					$amtClass = $amt >= 0 ? 'ms-ledger-amt--credit' : 'ms-ledger-amt--debit';
					$amtHtml = ($amt >= 0 ? '+' : '−') . choosology_datascrip_format_html(abs($amt));
					?>
					<tr>
						<td><?php echo htmlspecialchars($when, ENT_QUOTES, 'UTF-8'); ?></td>
						<td class="<?php echo $amtClass; ?>"><?php echo $amtHtml; ?></td>
						<td><?php echo choosology_datascrip_format_html((int) $e['balance_after']); ?></td>
						<td>
							<?php echo htmlspecialchars((string) $e['label'], ENT_QUOTES, 'UTF-8'); ?>
							<?php if (!empty($e['actor'])) { ?>
								<span class="ms-ledger-actor"> · by <?php echo htmlspecialchars((string) $e['actor'], ENT_QUOTES, 'UTF-8'); ?></span>
							<?php } ?>
						</td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	<?php } ?>
</div>
