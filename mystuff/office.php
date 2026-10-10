<?php
/**
 * My Office — Classic graphical hub (layered room + hotspots).
 */
require_once("../connect.php");
require_once("../auxfuncs.php");
require_once("../lib/office-helpers.php");
require_once("../lib/clipboard-helpers.php");

if (empty($_SESSION['user'])) {
	echo "<div class='intabs'><p class='error'>Please sign in to open your office.</p></div>";
	return;
}

$user = (string) $_SESSION['user'];
choosology_office_ensure_schema($db);
$state = choosology_office_state($db, $user);
$layers = $state['layers'];
$pedestals = $state['pedestals'];

function choosology_office_layer_url(array $layers, string $slot): string
{
	$url = $layers[$slot]['asset_url'] ?? '';
	return htmlspecialchars((string) $url, ENT_QUOTES, 'UTF-8');
}
?>
<div class="intabs ms-room-page" id="ms_room_page" data-office-hub="1">
	<div class="ms-room-chrome">
		<div class="ms-room-head">
			<p class="ms-room-eyebrow">Workstation <span class="ms-room-eyebrow-tag">classic</span></p>
			<h2 class="ms-room-title">My Office</h2>
			<p class="ms-room-lede">Furnish your lab quarters. Hotspots open your folders; the door leads to the Trophy Room.</p>
		</div>
		<div class="ms-room-toolbar" role="toolbar" aria-label="Office tools">
			<span class="ms-room-balance" id="ms_room_balance"><?php echo $state['balance_html']; ?></span>
			<button type="button" class="ms-room-btn" id="ms_room_shop" data-office-panel="shop">Shop</button>
			<button type="button" class="ms-room-btn" id="ms_room_inventory" data-office-panel="inventory">Inventory</button>
			<button type="button" class="ms-room-btn ms-room-btn--accent" id="ms_room_trophy" data-office-panel="trophy">Trophy Room</button>
		</div>
	</div>

	<div class="ms-room-stage-wrap">
		<div class="ms-room-stage" id="ms_room_stage" aria-label="My Office scene">
			<div class="ms-room-layer ms-room-layer--window" data-slot="window">
				<img src="<?php echo choosology_office_layer_url($layers, 'window'); ?>" alt="" draggable="false">
			</div>
			<div class="ms-room-layer ms-room-layer--wallpaper" data-slot="wallpaper">
				<img src="<?php echo choosology_office_layer_url($layers, 'wallpaper'); ?>" alt="" draggable="false">
			</div>
			<div class="ms-room-layer ms-room-layer--flooring" data-slot="flooring">
				<img src="<?php echo choosology_office_layer_url($layers, 'flooring'); ?>" alt="" draggable="false">
			</div>
			<div class="ms-room-layer ms-room-layer--bookshelf" data-slot="bookshelf">
				<img src="<?php echo choosology_office_layer_url($layers, 'bookshelf'); ?>" alt="" draggable="false">
			</div>
			<div class="ms-room-layer ms-room-layer--corner" data-slot="corner">
				<img src="<?php echo choosology_office_layer_url($layers, 'corner'); ?>" alt="" draggable="false">
			</div>
			<div class="ms-room-layer ms-room-layer--desk" data-slot="desk">
				<img src="<?php echo choosology_office_layer_url($layers, 'desk'); ?>" alt="" draggable="false">
			</div>
			<div class="ms-room-layer ms-room-layer--computer" data-slot="computer">
				<img src="<?php echo choosology_office_layer_url($layers, 'computer'); ?>" alt="" draggable="false">
			</div>
			<div class="ms-room-layer ms-room-layer--lighting" data-slot="lighting">
				<img src="<?php echo choosology_office_layer_url($layers, 'lighting'); ?>" alt="" draggable="false">
			</div>

			<button type="button" class="ms-room-hotspot ms-room-hotspot--experiments" data-hotspot="experiments" title="Experiments">Experiments</button>
			<button type="button" class="ms-room-hotspot ms-room-hotspot--messages" data-hotspot="messages" title="CLIC">CLIC</button>
			<button type="button" class="ms-room-hotspot ms-room-hotspot--resources" data-hotspot="resources" title="Resources">Resources</button>
			<button type="button" class="ms-room-hotspot ms-room-hotspot--ledger" data-hotspot="ledger" title="Ledger">Ledger</button>
			<button type="button" class="ms-room-hotspot ms-room-hotspot--degrees" data-hotspot="degrees" title="Degrees">Degrees</button>
			<button type="button" class="ms-room-hotspot ms-room-hotspot--account" data-hotspot="account" title="My Information">Profile</button>
			<button type="button" class="ms-room-hotspot ms-room-hotspot--results" data-hotspot="results" title="Experiment Results">Results</button>
			<button type="button" class="ms-room-hotspot ms-room-hotspot--trophy" data-hotspot="trophy" title="Trophy Room">Trophy Room</button>

			<?php foreach ($pedestals as $ped) {
				$idx = (int) $ped['index'];
				$has = !empty($ped['item_key']);
				$src = htmlspecialchars((string) ($ped['asset_url'] ?? ''), ENT_QUOTES, 'UTF-8');
				$label = htmlspecialchars((string) ($ped['label'] ?? 'Empty pedestal'), ENT_QUOTES, 'UTF-8');
				?>
			<button type="button" class="ms-room-pedestal ms-room-pedestal--<?php echo $idx; ?><?php echo $has ? ' is-filled' : ''; ?>"
				data-pedestal="<?php echo $idx; ?>" title="<?php echo $label; ?>">
				<?php if ($has) { ?>
				<img src="<?php echo $src; ?>" alt="<?php echo $label; ?>" draggable="false">
				<?php } else { ?>
				<span class="ms-room-pedestal-empty">+</span>
				<?php } ?>
			</button>
			<?php } ?>
		</div>
	</div>

	<div class="ms-room-overlay ms-room-overlay--hidden" id="ms_room_overlay" aria-hidden="true">
		<div class="ms-room-overlay-backdrop" data-office-close="1"></div>
		<div class="ms-room-overlay-panel" role="dialog" aria-modal="true" aria-labelledby="ms_room_overlay_title">
			<div class="ms-room-overlay-head">
				<h3 class="ms-room-overlay-title" id="ms_room_overlay_title">Panel</h3>
				<button type="button" class="ms-room-overlay-close" data-office-close="1" aria-label="Close">&times;</button>
			</div>
			<div class="ms-room-panel-body" id="ms_room_panel_body"></div>
		</div>
	</div>

	<div class="ms-trophy-view ms-trophy-view--hidden" id="ms_trophy_view" aria-hidden="true">
		<div class="ms-trophy-bar">
			<button type="button" class="ms-room-btn" id="ms_trophy_back">← Back to Office</button>
			<p class="ms-trophy-title">Trophy Room</p>
			<p class="ms-trophy-hint">Scroll sideways to browse your collection. Display up to three in the office.</p>
		</div>
		<div class="ms-trophy-scroll" id="ms_trophy_scroll">
			<div class="ms-trophy-strip" id="ms_trophy_strip" style="background-image:url('<?php echo htmlspecialchars((string) $state['trophy_bay_url'], ENT_QUOTES, 'UTF-8'); ?>')"></div>
		</div>
	</div>
</div>

<script>
(function () {
	if (window.ChoosologyOffice && typeof ChoosologyOffice.mount === "function") {
		ChoosologyOffice.mount(document.getElementById("ms_room_page"));
	}
})();
</script>
