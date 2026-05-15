<?php
require_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/../auxfuncs.php';

$advidRaw = isset($_GET['advid']) ? trim((string) $_GET['advid']) : '';
if ($advidRaw === '' || !ctype_digit($advidRaw)) {
	echo '<div class="error">Invalid adventure.</div>';
	return;
}
$advid = (int) $advidRaw;

if (empty($_SESSION['user'])) {
	echo '<div class="error">Please sign in.</div>';
	return;
}
$user = (string) $_SESSION['user'];
$escUser = mysqli_real_escape_string($db, $user);
$escAdvid = mysqli_real_escape_string($db, (string) $advid);

$advrows = runquery_assoc("SELECT * FROM advs WHERE id = '$advid' AND user = '$escUser' LIMIT 1");
if (!is_array($advrows) || !isset($advrows[0])) {
	echo '<div class="error">Adventure not found or not yours.</div>';
	return;
}
$adv = $advrows[0];

$screens = runquery_assoc(
	"SELECT id, name FROM advscreens WHERE advused = '$escAdvid' AND IFNULL(deleted,0) NOT IN (1, '1') ORDER BY name ASC"
);
if (!is_array($screens)) {
	$screens = array();
}

$title = htmlspecialchars((string) ($adv['title'] ?? ''), ENT_QUOTES, 'UTF-8');
$description = htmlspecialchars((string) ($adv['description'] ?? ''), ENT_QUOTES, 'UTF-8');
$tags = htmlspecialchars((string) ($adv['tags'] ?? ''), ENT_QUOTES, 'UTF-8');
$avail = (string) ($adv['avail'] ?? 'none');
$beginVal = (string) ($adv['begin'] ?? '');
$picVal = trim((string) ($adv['pic'] ?? ''));
$bgpicVal = (int) ($adv['bgpic'] ?? 0);
$bg = htmlspecialchars((string) ($adv['bg'] ?? '#ffffff'), ENT_QUOTES, 'UTF-8');
$box = htmlspecialchars((string) ($adv['box'] ?? '#ccddff'), ENT_QUOTES, 'UTF-8');
$border = htmlspecialchars((string) ($adv['border'] ?? '#9999cc'), ENT_QUOTES, 'UTF-8');
$borderwidth = (int) ($adv['borderwidth'] ?? 2);
$textcolor = htmlspecialchars((string) ($adv['textcolor'] ?? ''), ENT_QUOTES, 'UTF-8');
$linkcolor = htmlspecialchars((string) ($adv['linkcolor'] ?? ''), ENT_QUOTES, 'UTF-8');
$useAutoText = trim((string) ($adv['textcolor'] ?? '')) === '';
$useAutoLink = trim((string) ($adv['linkcolor'] ?? '')) === '';

$iconPreviewUrl = ($picVal !== '' && ctype_digit($picVal)) ? getPicUrl((int) $picVal, true) : '';
$bgPreviewUrl = $bgpicVal > 0 ? getPicUrl($bgpicVal, true) : '';
?>
<div class="advsettings-dialog">
<div class="advsettings-head">
	<div class="advsettings-head-inner">
		<p class="advsettings-head-eyebrow">Specimen <span class="advsettings-head-specid">#<?php echo (int) $advid; ?></span> · lab metadata</p>
		<h2 class="advsettings-head-title">Experiment settings</h2>
	</div>
	<span class="closewindow advsettings-close" title="Close">&times;</span>
</div>
<form id="advsettingsform" class="advsettings-form" onsubmit="return false;">
	<input type="hidden" id="as_advid" value="<?php echo (int) $advid; ?>" />

	<div class="advsettings-section advsettings-section--basics">
		<h3>Basics</h3>
		<div class="advsettings-basics-cols">
			<div class="advsettings-basics-col">
				<label class="advsettings-label">Title <span class="adv-req">*</span></label>
				<input type="text" id="as_title" maxlength="255" class="advsettings-input adv-title-input" value="<?php echo $title; ?>" />

				<label class="advsettings-label">Description</label>
				<textarea id="as_description" maxlength="275" rows="4" class="advsettings-textarea"><?php echo $description; ?></textarea>
			</div>
			<div class="advsettings-basics-col">
				<label class="advsettings-label" for="as_tags_input">Tags <span class="adv-hint">(Enter to add · max 10 · 50 chars each)</span></label>
				<div class="adv-tags-field" id="as_tags_field">
					<div class="adv-tags-pills" id="as_tags_pills" aria-live="polite"></div>
					<input type="text" id="as_tags_input" class="advsettings-input adv-tags-input" maxlength="50" autocomplete="off" placeholder="Type a tag, then press Enter" />
					<input type="hidden" id="as_tags" value="<?php echo $tags; ?>" />
				</div>

				<label class="advsettings-label">Starting screen</label>
				<select id="as_begin" class="advsettings-input">
					<option value="">(not set)</option>
					<?php foreach ($screens as $sc) {
						$sid = (int) ($sc['id'] ?? 0);
						$sname = htmlspecialchars((string) ($sc['name'] ?? ''), ENT_QUOTES, 'UTF-8');
						$beginTrim = trim($beginVal);
						$sel = ($beginTrim !== '' && (string) $sid === $beginTrim) ? ' selected' : '';
						echo "<option value=\"{$sid}\"{$sel}>#{$sid} — {$sname}</option>";
					} ?>
				</select>
			</div>
		</div>
	</div>

	<div class="advsettings-section advsettings-section--publishing">
		<h3>Publishing</h3>
		<p class="advsettings-label" id="as_avail_legend">Availability</p>
		<div class="adv-avail-rail" role="radiogroup" aria-labelledby="as_avail_legend">
			<label class="adv-avail-option adv-avail-option--none">
				<span class="adv-avail-panel">
					<input type="radio" name="as_avail" value="none" class="adv-avail-input"<?php echo $avail === 'none' ? ' checked' : ''; ?> />
					<span class="adv-avail-main">
						<span class="adv-avail-code">P0</span>
						<span class="adv-avail-name">Unpublished</span>
						<span class="adv-avail-hint">Not listed for browsing</span>
					</span>
				</span>
			</label>
			<label class="adv-avail-option adv-avail-option--private">
				<span class="adv-avail-panel">
					<input type="radio" name="as_avail" value="private" class="adv-avail-input"<?php echo $avail === 'private' ? ' checked' : ''; ?> />
					<span class="adv-avail-main">
						<span class="adv-avail-code">P1</span>
						<span class="adv-avail-name">Private</span>
						<span class="adv-avail-hint">Link or invite only</span>
					</span>
				</span>
			</label>
			<label class="adv-avail-option adv-avail-option--public">
				<span class="adv-avail-panel">
					<input type="radio" name="as_avail" value="public" class="adv-avail-input"<?php echo $avail === 'public' ? ' checked' : ''; ?> />
					<span class="adv-avail-main">
						<span class="adv-avail-code">P2</span>
						<span class="adv-avail-name">Public</span>
						<span class="adv-avail-hint">Discoverable in browse</span>
					</span>
				</span>
			</label>
		</div>
	</div>

	<div class="advsettings-section advsettings-section--look">
		<h3>Look &amp; feel</h3>
		<div class="advsettings-media-pair">
			<div class="advsettings-media-block">
				<label class="advsettings-label" id="as_pic_legend">Experiment icon</label>
				<input type="hidden" id="as_pic" value="<?php echo htmlspecialchars($picVal, ENT_QUOTES, 'UTF-8'); ?>" />
				<div class="adv-media-picker">
					<div class="adv-preview-slot<?php echo $iconPreviewUrl === '' ? ' adv-preview-slot--empty' : ''; ?>" id="as_pic_slot" aria-labelledby="as_pic_legend">
						<button type="button" class="adv-preview-hit adv-picmodal-trigger" data-picmodal-target="pic" aria-labelledby="as_pic_legend" aria-describedby="as_pic_hint" title="Open image library">
							<span class="adv-preview-frame">
								<?php if ($iconPreviewUrl !== '') { ?>
								<img id="as_pic_preview" class="adv-preview-img" src="<?php echo htmlspecialchars($iconPreviewUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="" />
								<?php } else { ?>
								<span id="as_pic_preview" class="adv-preview-empty">No icon</span>
								<?php } ?>
							</span>
						</button>
						<button type="button" class="adv-preview-clear" id="as_pic_clear" title="Clear icon" aria-label="Clear icon">&times;</button>
					</div>
					<p class="adv-preview-tap-hint" id="as_pic_hint">Click image to change</p>
				</div>
			</div>
			<div class="advsettings-media-block">
				<label class="advsettings-label" id="as_bgpic_legend">Background image</label>
				<input type="hidden" id="as_bgpic" value="<?php echo (int) $bgpicVal; ?>" />
				<div class="adv-media-picker">
					<div class="adv-preview-slot<?php echo $bgpicVal < 1 ? ' adv-preview-slot--empty' : ''; ?>" id="as_bgpic_slot" aria-labelledby="as_bgpic_legend">
						<button type="button" class="adv-preview-hit adv-picmodal-trigger" data-picmodal-target="bgpic" aria-labelledby="as_bgpic_legend" aria-describedby="as_bgpic_hint" title="Open image library">
							<span class="adv-preview-frame">
								<?php if ($bgPreviewUrl !== '') { ?>
								<img id="as_bgpic_preview" class="adv-preview-img" src="<?php echo htmlspecialchars($bgPreviewUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="" />
								<?php } else { ?>
								<span id="as_bgpic_preview" class="adv-preview-empty">No background</span>
								<?php } ?>
							</span>
						</button>
						<button type="button" class="adv-preview-clear" id="as_bgpic_clear" title="Clear background image" aria-label="Clear background">&times;</button>
					</div>
					<p class="adv-preview-tap-hint" id="as_bgpic_hint">Click image to change</p>
				</div>
			</div>
		</div>

		<div class="advsettings-livepreview" id="as_livepreview_root" aria-label="Live preview of appearance settings">
			<div class="advsettings-livepreview-caption">Live render · presentation layer</div>
			<div class="advsettings-livepreview-chrome" id="as_livepreview_chrome">
				<div class="advsettings-livepreview-panel" id="as_livepreview_panel">
					<p class="advsettings-livepreview-text" id="as_livepreview_text">Sample narrative body text for this experiment.</p>
					<a href="#" class="advsettings-livepreview-link" id="as_livepreview_link" onclick="return false;">Sample branch link</a>
				</div>
			</div>
		</div>

		<div class="advsettings-colors">
			<div class="adv-lf-slot">
				<div class="adv-lf-slot-head">
					<span class="adv-lf-code" aria-hidden="true">L1</span>
					<label class="advsettings-label adv-lf-label" for="as_bg">Page background</label>
				</div>
				<div class="adv-lf-slot-control">
					<input type="text" id="as_bg" class="advsettings-input adv-color" value="<?php echo $bg; ?>" />
				</div>
			</div>
			<div class="adv-lf-slot">
				<div class="adv-lf-slot-head">
					<span class="adv-lf-code" aria-hidden="true">L2</span>
					<label class="advsettings-label adv-lf-label" for="as_box">Box / panel color</label>
				</div>
				<div class="adv-lf-slot-control">
					<input type="text" id="as_box" class="advsettings-input adv-color" value="<?php echo $box; ?>" />
				</div>
			</div>
			<div class="adv-lf-slot">
				<div class="adv-lf-slot-head">
					<span class="adv-lf-code" aria-hidden="true">L3</span>
					<label class="advsettings-label adv-lf-label" for="as_border">Border color</label>
				</div>
				<div class="adv-lf-slot-control">
					<input type="text" id="as_border" class="advsettings-input adv-color" value="<?php echo $border; ?>" />
				</div>
			</div>
			<div class="adv-lf-slot">
				<div class="adv-lf-slot-head">
					<span class="adv-lf-code" aria-hidden="true">L4</span>
					<label class="advsettings-label adv-lf-label" for="as_borderwidth">Border width (px)</label>
				</div>
				<div class="adv-lf-slot-control">
					<input type="number" id="as_borderwidth" min="0" max="20" class="advsettings-input advsettings-input-narrow" value="<?php echo (int) $borderwidth; ?>" />
				</div>
			</div>
			<div class="adv-lf-slot adv-lf-slot--tl adv-lf-slot--text">
				<div class="adv-lf-slot-head adv-lf-slot-head--tl">
					<span class="adv-lf-code" aria-hidden="true">L5</span>
					<label class="adv-lf-auto-inline" for="as_use_auto_text">
						<input type="checkbox" id="as_use_auto_text"<?php echo $useAutoText ? ' checked' : ''; ?> />
						<span class="adv-lf-auto-inline-label">Use default colors</span>
					</label>
					<label class="advsettings-label adv-lf-label" for="as_textcolor">Text color</label>
				</div>
				<div class="adv-lf-slot-control">
					<input type="text" id="as_textcolor" class="advsettings-input adv-color" value="<?php echo $textcolor; ?>" placeholder="#000000" />
				</div>
			</div>
			<div class="adv-lf-slot adv-lf-slot--tl adv-lf-slot--link">
				<div class="adv-lf-slot-head adv-lf-slot-head--tl">
					<span class="adv-lf-code" aria-hidden="true">L6</span>
					<label class="adv-lf-auto-inline" for="as_use_auto_link">
						<input type="checkbox" id="as_use_auto_link"<?php echo $useAutoLink ? ' checked' : ''; ?> />
						<span class="adv-lf-auto-inline-label">Use default colors</span>
					</label>
					<label class="advsettings-label adv-lf-label" for="as_linkcolor">Link color</label>
				</div>
				<div class="adv-lf-slot-control">
					<input type="text" id="as_linkcolor" class="advsettings-input adv-color" value="<?php echo $linkcolor; ?>" placeholder="#3333ff" />
				</div>
			</div>
		</div>
	</div>

	<div class="advsettings-actions">
		<span class="fakebutton fgreen" id="as_save">Save</span>
		<span class="fakebutton" id="as_cancel">Cancel</span>
	</div>
</form>

<div id="as_picmodal" class="adv-picmodal adv-picmodal--hidden" aria-hidden="true">
	<div class="adv-picmodal-backdrop" tabindex="-1"></div>
	<div class="adv-picmodal-panel" role="dialog" aria-modal="true" aria-labelledby="as_picmodal_title">
		<div class="adv-picmodal-header">
			<h3 class="adv-picmodal-title" id="as_picmodal_title">Choose image</h3>
			<button type="button" class="adv-picmodal-x" id="as_picmodal_close" aria-label="Close">&times;</button>
		</div>
		<div class="adv-picbrowser adv-picbrowser--modal" id="as_picmodal_browser" data-target="pic">
			<div class="adv-picbrowser-toolbar">
				<input type="search" class="advsettings-input adv-picbrowser-q" placeholder="Search by title or filename…" autocomplete="off" />
				<select class="advsettings-input adv-picbrowser-tag" aria-label="Filter by category or tag">
					<option value="">All tags &amp; categories</option>
				</select>
			</div>
			<p class="adv-picbrowser-hint adv-hint">Search matches title, filename, and category text. The filter lists tokens from each image’s category field (comma-separated values); richer tag organization can plug in here later.</p>
			<div class="adv-picbrowser-status" aria-live="polite"></div>
			<div class="adv-picbrowser-scroll">
				<div class="adv-picbrowser-grid"></div>
			</div>
		</div>
	</div>
</div>
</div>
<script>
(function () {
	function choosologyUrlSafe(path) {
		if (typeof choosologyUrl === "function") {
			return choosologyUrl(path);
		}
		path = String(path || "").replace(/^\//, "");
		return path ? ("/" + path) : "/";
	}

	var advTlMinicolorsSilent = false;

	var advPicLibraryResponse = null;
	var advPicLibraryAdvid = null;

	function advPicNorm(s) {
		return String(s || "").toLowerCase();
	}

	function advPicFilterItems(items, q, tag) {
		if (!Array.isArray(items)) {
			return [];
		}
		var qn = advPicNorm(q).trim();
		var tn = String(tag || "").trim();
		var i;
		var j;
		var out = [];
		for (i = 0; i < items.length; i++) {
			var it = items[i];
			if (tn !== "") {
				var okTag = false;
				var tags = Array.isArray(it.tags) ? it.tags : [];
				for (j = 0; j < tags.length; j++) {
					if (String(tags[j]).toLowerCase() === tn.toLowerCase()) {
						okTag = true;
						break;
					}
				}
				if (!okTag && advPicNorm(it.cat) !== tn.toLowerCase()) {
					continue;
				}
			}
			if (qn !== "") {
				var blob = advPicNorm(it.title) + "\n" + advPicNorm(it.filename) + "\n" + advPicNorm(it.cat);
				if (blob.indexOf(qn) === -1) {
					continue;
				}
			}
			out.push(it);
		}
		return out;
	}

	function refreshAdvPicBrowserSelection() {
		var $roots = $("#as_picmodal_browser");
		if (!$roots.length) {
			return;
		}
		$roots.each(function () {
			var $root = $(this);
			var target = $root.attr("data-target") || "pic";
			var sel = target === "bgpic" ? String(parseInt($("#as_bgpic").val(), 10) || 0) : String($("#as_pic").val() || "").trim();
			$root.find(".adv-picbrowser-tile").each(function () {
				var pid = String($(this).attr("data-pic-id") || "");
				var isSel = target === "bgpic" ? (pid !== "0" && pid === sel) : (sel !== "" && pid === sel);
				$(this).toggleClass("adv-picbrowser-tile--selected", isSel);
			});
		});
	}

	var advPicModalReturnFocus = null;

	function closeAdvPicModal() {
		var $m = $("#as_picmodal");
		if (!$m.length || $m.hasClass("adv-picmodal--hidden")) {
			return;
		}
		$m.addClass("adv-picmodal--hidden").attr("aria-hidden", "true");
		$("body").removeClass("adv-picmodal-open");
		if (advPicModalReturnFocus && advPicModalReturnFocus.focus) {
			try {
				advPicModalReturnFocus.focus();
			} catch (e1) {
				/* ignore */
			}
		}
		advPicModalReturnFocus = null;
	}

	function openAdvPicModal(target) {
		var $m = $("#as_picmodal");
		var $browser = $("#as_picmodal_browser");
		if (!$m.length || !$browser.length) {
			return;
		}
		advPicModalReturnFocus = document.activeElement;
		$browser.attr("data-target", target === "bgpic" ? "bgpic" : "pic");
		$("#as_picmodal_title").text(target === "bgpic" ? "Choose background image" : "Choose experiment icon");
		$browser.find(".adv-picbrowser-q").val("");
		$browser.find(".adv-picbrowser-tag").val("");
		var redraw = $browser.data("advPicRedraw");
		if (typeof redraw === "function") {
			redraw();
		}
		$m.removeClass("adv-picmodal--hidden").attr("aria-hidden", "false");
		$("body").addClass("adv-picmodal-open");
		window.setTimeout(function () {
			$browser.find(".adv-picbrowser-q").trigger("focus");
		}, 0);
	}

	function initAdvPicBrowsers() {
		var advid = String($("#as_advid").val() || "");
		if (!advid) {
			return;
		}
		if (advPicLibraryAdvid !== null && advPicLibraryAdvid !== advid) {
			advPicLibraryResponse = null;
		}
		function wireLibrary(res) {
			if (!res || !res.ok || !Array.isArray(res.items)) {
				return;
			}
			advPicLibraryResponse = res;
			advPicLibraryAdvid = advid;
			var allItems = res.items;
			var tagOpts = Array.isArray(res.tagOptions) ? res.tagOptions : [];
			$(".adv-picbrowser").each(function () {
				var $root = $(this);
				if ($root.data("advPicBrowserInit")) {
					if (typeof $root.data("advPicRedraw") === "function") {
						$root.data("advPicRedraw")();
					}
					return;
				}
				$root.data("advPicBrowserInit", true);
				var $tag = $root.find(".adv-picbrowser-tag");
				$tag.find("option:not(:first)").remove();
				var oi;
				for (oi = 0; oi < tagOpts.length; oi++) {
					$tag.append($("<option/>").val(tagOpts[oi]).text(tagOpts[oi]));
				}
				$tag.prop("disabled", tagOpts.length < 1);
				function redraw() {
					var q = String($root.find(".adv-picbrowser-q").val() || "");
					var tag = String($root.find(".adv-picbrowser-tag").val() || "");
					var list = advPicFilterItems(allItems, q, tag);
					var $grid = $root.find(".adv-picbrowser-grid");
					var $st = $root.find(".adv-picbrowser-status");
					$grid.empty();
					if (list.length === 0) {
						$st.text(allItems.length === 0 ? "No images in your library yet." : "No images match this search or filter.");
					} else {
						$st.text(list.length + " of " + allItems.length + " shown");
					}
					var bi;
					for (bi = 0; bi < list.length; bi++) {
						var it = list[bi];
						var cap = String(it.title || ("#" + it.id));
						var $tile = $("<div/>", {
							class: "adv-picbrowser-tile",
							"data-pic-id": String(it.id),
							title: cap,
							role: "button",
							tabindex: 0
						});
						$tile.append(
							$("<div/>", { class: "adv-picbrowser-tile-img" }).append(
								$("<img/>", { src: it.thumbUrl || "", alt: "", loading: "lazy" })
							)
						);
						$tile.append($("<div/>", { class: "adv-picbrowser-tile-cap", text: cap }));
						$grid.append($tile);
					}
					refreshAdvPicBrowserSelection();
				}
				$root.data("advPicRedraw", redraw);
				$root.find(".adv-picbrowser-q").on("input", function () {
					window.clearTimeout($root.data("advPicQTimer"));
					$root.data("advPicQTimer", window.setTimeout(redraw, 100));
				});
				$root.find(".adv-picbrowser-tag").on("change", redraw);
				redraw();
			});
		}
		if (advPicLibraryAdvid === advid && advPicLibraryResponse && Array.isArray(advPicLibraryResponse.items)) {
			wireLibrary(advPicLibraryResponse);
			return;
		}
		$.getJSON(choosologyUrlSafe("ajax/listuserpics.php?advid=" + encodeURIComponent(advid))).done(wireLibrary);
	}

	function hexToRgb(hex) {
		hex = String(hex || "").trim().replace(/^#/, "");
		if (hex.length === 3) {
			hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
		}
		if (!/^[0-9a-fA-F]{6}$/.test(hex)) {
			return null;
		}
		return {
			r: parseInt(hex.slice(0, 2), 16),
			g: parseInt(hex.slice(2, 4), 16),
			b: parseInt(hex.slice(4, 6), 16)
		};
	}

	function rgbToHex(rgb) {
		function h(n) {
			var s = Math.max(0, Math.min(255, n | 0)).toString(16);
			return s.length === 1 ? "0" + s : s;
		}
		return "#" + h(rgb.r) + h(rgb.g) + h(rgb.b);
	}

	function relativeLuminance(rgb) {
		function f(c) {
			c = c / 255;
			return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
		}
		return 0.2126 * f(rgb.r) + 0.7152 * f(rgb.g) + 0.0722 * f(rgb.b);
	}

	function contrastRatio(rgb1, rgb2) {
		var L1 = relativeLuminance(rgb1);
		var L2 = relativeLuminance(rgb2);
		var hi = Math.max(L1, L2);
		var lo = Math.min(L1, L2);
		return (hi + 0.05) / (lo + 0.05);
	}

	function computeAutoTextColor(boxHex) {
		var rgb = hexToRgb(boxHex) || { r: 204, g: 221, b: 255 };
		var L = relativeLuminance(rgb);
		if (L < 0.38) {
			var t = 0.88;
			return rgbToHex({
				r: Math.round(rgb.r * (1 - t) + 255 * t),
				g: Math.round(rgb.g * (1 - t) + 255 * t),
				b: Math.round(rgb.b * (1 - t) + 255 * t)
			});
		}
		return "#101010";
	}

	function computeAutoLinkColor(boxHex, textColorHex) {
		var boxRgb = hexToRgb(boxHex) || { r: 204, g: 221, b: 255 };
		var textRgb = hexToRgb(textColorHex);
		if (!textRgb) {
			textRgb = hexToRgb(computeAutoTextColor(boxHex)) || { r: 16, g: 16, b: 16 };
		}
		var textIsLight = relativeLuminance(textRgb) > 0.42;
		var candidatesLightPanel = [
			"#2563eb",
			"#1d4ed8",
			"#1e40af",
			"#0369a1",
			"#0f766e",
			"#7c3aed",
			"#c2410c",
			"#60a5fa",
			"#334155",
			"#f59e0b",
			"#93c5fd",
			"#e0e7ff"
		];
		var candidatesDarkPanel = [
			"#38bdf8",
			"#22d3ee",
			"#2dd4bf",
			"#4ade80",
			"#86efac",
			"#fde047",
			"#fbbf24",
			"#fb923c",
			"#f472b6",
			"#c084fc",
			"#a78bfa",
			"#60a5fa",
			"#2563eb",
			"#93c5fd"
		];
		var list = textIsLight ? candidatesDarkPanel : candidatesLightPanel;
		var minVsText = textIsLight ? 2.35 : 1.05;
		var i;
		var lr;
		var cBox;
		var cText;
		for (i = 0; i < list.length; i++) {
			lr = hexToRgb(list[i]);
			if (!lr) {
				continue;
			}
			cBox = contrastRatio(lr, boxRgb);
			if (cBox < 4.5) {
				continue;
			}
			cText = contrastRatio(lr, textRgb);
			if (cText < minVsText) {
				continue;
			}
			return list[i];
		}
		if (textIsLight) {
			var floor = 2.0;
			while (floor >= 1.12) {
				for (i = 0; i < list.length; i++) {
					lr = hexToRgb(list[i]);
					if (!lr) {
						continue;
					}
					if (contrastRatio(lr, boxRgb) < 4.5) {
						continue;
					}
					if (contrastRatio(lr, textRgb) < floor) {
						continue;
					}
					return list[i];
				}
				floor -= 0.18;
			}
		}
		return textIsLight ? "#38bdf8" : (relativeLuminance(boxRgb) < 0.35 ? "#1e3a8a" : "#2563eb");
	}

	function getBoxHexLive() {
		var el = document.getElementById("as_box");
		return el && String(el.value || "").trim() ? String(el.value).trim() : "#ccddff";
	}

	function resolveLiveTextColor(box) {
		if ($("#as_use_auto_text").is(":checked")) {
			return computeAutoTextColor(box);
		}
		var el = document.getElementById("as_textcolor");
		var v = el && String(el.value || "").trim();
		return v || "#222222";
	}

	function resolveLiveLinkColor(box, textHex) {
		if ($("#as_use_auto_link").is(":checked")) {
			return computeAutoLinkColor(box, textHex || resolveLiveTextColor(box));
		}
		var el = document.getElementById("as_linkcolor");
		var v = el && String(el.value || "").trim();
		return v || "#3333ff";
	}

	function syncAutoTlMinicolorsSwatches() {
		var useText = $("#as_use_auto_text").is(":checked");
		var useLink = $("#as_use_auto_link").is(":checked");
		if (!useText && !useLink) {
			return;
		}
		var box = getBoxHexLive();
		var tc = resolveLiveTextColor(box);
		var lc = resolveLiveLinkColor(box, tc);
		if (typeof $.fn.minicolors !== "function") {
			return;
		}
		var $t = $("#as_textcolor");
		var $l = $("#as_linkcolor");
		advTlMinicolorsSilent = true;
		try {
			if (useText && $t.data("minicolors-initialized")) {
				$t.minicolors("value", tc);
			}
			if (useLink && $l.data("minicolors-initialized")) {
				$l.minicolors("value", lc);
			}
		} finally {
			advTlMinicolorsSilent = false;
		}
	}

	function setTlPickerLocked(fieldId, locked) {
		var $inp = $("#" + fieldId);
		$inp.prop("disabled", !!locked);
		$inp.closest(".minicolors").toggleClass("adv-minicolors-locked", !!locked);
		var slotSel = fieldId === "as_textcolor" ? ".adv-lf-slot--text" : ".adv-lf-slot--link";
		$(slotSel).toggleClass("adv-lf-slot-locked", !!locked);
	}

	function refreshAutoTlLockUI() {
		setTlPickerLocked("as_textcolor", $("#as_use_auto_text").is(":checked"));
		setTlPickerLocked("as_linkcolor", $("#as_use_auto_link").is(":checked"));
	}

	function setPicPreview(which, url) {
		var id = which === "bgpic" ? "as_bgpic_preview" : "as_pic_preview";
		var slotId = which === "bgpic" ? "as_bgpic_slot" : "as_pic_slot";
		var $el = $("#" + id);
		var emptyLabel = which === "bgpic" ? "No background" : "No icon";
		if (url) {
			if ($el.is("img")) {
				$el.attr("src", url);
			} else {
				$el.replaceWith($("<img/>", { "class": "adv-preview-img", id: id, src: url, alt: "" }));
			}
			$("#" + slotId).removeClass("adv-preview-slot--empty");
		} else {
			if ($el.is("img")) {
				$el.replaceWith($("<span/>", { "class": "adv-preview-empty", id: id, text: emptyLabel }));
			} else {
				$el.text(emptyLabel);
			}
			$("#" + slotId).addClass("adv-preview-slot--empty");
		}
		applyAdvSettingsLivePreview();
		refreshAdvPicBrowserSelection();
	}

	function applyAdvSettingsLivePreview() {
		function pickColor(id, fallback) {
			var el = document.getElementById(id);
			if (!el) {
				return fallback;
			}
			var v = String(el.value || "").trim();
			return v || fallback;
		}
		var bg = pickColor("as_bg", "#ffffff");
		var box = pickColor("as_box", "#ccddff");
		var border = pickColor("as_border", "#9999cc");
		var bw = parseInt(String($("#as_borderwidth").val() || "2"), 10);
		if (isNaN(bw) || bw < 0) {
			bw = 2;
		}
		if (bw > 40) {
			bw = 40;
		}
		var tc = resolveLiveTextColor(box);
		var lc = resolveLiveLinkColor(box, tc);
		var $chrome = $("#as_livepreview_chrome");
		var $panel = $("#as_livepreview_panel");
		var $link = $("#as_livepreview_link");
		if (!$chrome.length || !$panel.length) {
			return;
		}
		var chrome = $chrome[0];
		chrome.style.setProperty("--adv-live-page-bg", bg);
		var bgpic = parseInt(String($("#as_bgpic").val() || "0"), 10) || 0;
		var $bgImg = $("#as_bgpic_preview");
		if (bgpic > 0 && $bgImg.is("img")) {
			var src = $bgImg.attr("src") || "";
			if (src) {
				var esc = src.replace(/\\/g, "\\\\").replace(/"/g, '\\"');
				chrome.style.setProperty("--adv-live-page-img", 'url("' + esc + '")');
				chrome.style.backgroundSize = "cover";
				chrome.style.backgroundPosition = "center center";
				chrome.style.backgroundRepeat = "no-repeat";
			} else {
				chrome.style.removeProperty("--adv-live-page-img");
				chrome.style.removeProperty("background-size");
				chrome.style.removeProperty("background-position");
				chrome.style.removeProperty("background-repeat");
			}
		} else {
			chrome.style.removeProperty("--adv-live-page-img");
			chrome.style.removeProperty("background-size");
			chrome.style.removeProperty("background-position");
			chrome.style.removeProperty("background-repeat");
		}
		$panel.css({
			backgroundColor: box,
			border: bw + "px solid " + border,
			color: tc
		});
		$link.css("color", lc);
		if ($("#as_use_auto_text").is(":checked") || $("#as_use_auto_link").is(":checked")) {
			syncAutoTlMinicolorsSwatches();
		}
	}

	$(".advsettings-close, #as_cancel").on("click", function () {
		closeAdvPicModal();
		if (typeof closeAdvSettings === "function") {
			closeAdvSettings();
		}
	});

	$("#advsettingsform").off("click.advPicTrigger").on("click.advPicTrigger", ".adv-picmodal-trigger", function () {
		openAdvPicModal($(this).attr("data-picmodal-target") || "pic");
	});

	$("#as_picmodal_close, #as_picmodal .adv-picmodal-backdrop").off("click.advPicModal").on("click.advPicModal", function () {
		closeAdvPicModal();
	});

	$(document).off("keydown.advPicModalEsc").on("keydown.advPicModalEsc", function (e) {
		if (e.key !== "Escape") {
			return;
		}
		var $m = $("#as_picmodal");
		if ($m.length && !$m.hasClass("adv-picmodal--hidden")) {
			e.preventDefault();
			closeAdvPicModal();
		}
	});

	$("#as_picmodal").off("click.advPicPick").on("click.advPicPick", ".adv-picbrowser-tile", function () {
		var $t = $(this);
		var $root = $t.closest(".adv-picbrowser");
		var target = $root.attr("data-target") || "pic";
		var pid = $t.attr("data-pic-id");
		if (!pid) {
			return;
		}
		var url = $t.find("img").attr("src") || "";
		if (target === "bgpic") {
			$("#as_bgpic").val(pid);
			setPicPreview("bgpic", url);
		} else {
			$("#as_pic").val(pid);
			setPicPreview("pic", url);
		}
		closeAdvPicModal();
	});

	$("#as_picmodal").off("keydown.advPicPick").on("keydown.advPicPick", ".adv-picbrowser-tile", function (e) {
		if (e.key === "Enter" || e.key === " ") {
			e.preventDefault();
			$(this).trigger("click");
		}
	});

	$("#as_pic_clear").on("click", function () {
		$("#as_pic").val("");
		setPicPreview("pic", "");
	});
	$("#as_bgpic_clear").on("click", function () {
		$("#as_bgpic").val("0");
		setPicPreview("bgpic", "");
	});

	if (typeof $.fn.minicolors === "function") {
		$("#advsettingsform .adv-color").minicolors({
			change: function () {
				if (advTlMinicolorsSilent) {
					return;
				}
				applyAdvSettingsLivePreview();
			},
			theme: "default"
		});
	}
	$("#as_use_auto_text, #as_use_auto_link").on("change", function () {
		refreshAutoTlLockUI();
		applyAdvSettingsLivePreview();
	});
	refreshAutoTlLockUI();
	$("#as_borderwidth").on("input change", applyAdvSettingsLivePreview);
	$("#advsettingsform").on("input change", "#as_bg, #as_box, #as_border, #as_textcolor, #as_linkcolor", applyAdvSettingsLivePreview);
	setTimeout(function () {
		applyAdvSettingsLivePreview();
	}, 0);
	setTimeout(function () {
		applyAdvSettingsLivePreview();
	}, 80);
	initAdvPicBrowsers();

	var advTagsFlushPending = function () {};

	(function initAdvTagsPills() {
		var $hidden = $("#as_tags");
		var $pills = $("#as_tags_pills");
		var $input = $("#as_tags_input");
		if (!$hidden.length || !$pills.length || !$input.length) {
			return;
		}
		var maxTagLen = 50;
		var maxTagCount = 10;
		var maxSerialized = 1024;

		function getTagListFromDom() {
			var tags = [];
			$pills.find(".adv-tags-pill").each(function () {
				var t = $(this).data("tagText");
				if (t !== undefined && t !== null && String(t).trim() !== "") {
					tags.push(String(t).trim());
				}
			});
			return tags;
		}

		function syncHidden() {
			$hidden.val(getTagListFromDom().join(","));
		}

		function tagExists(t) {
			var want = String(t || "").trim().toLowerCase();
			if (!want) {
				return true;
			}
			var dup = false;
			$pills.find(".adv-tags-pill").each(function () {
				if (String($(this).data("tagText") || "").trim().toLowerCase() === want) {
					dup = true;
					return false;
				}
			});
			return dup;
		}

		function renderPill(text) {
			var t = String(text || "").trim();
			if (t.length > maxTagLen) {
				t = t.slice(0, maxTagLen);
			}
			if (!t) {
				return false;
			}
			if (getTagListFromDom().length >= maxTagCount) {
				return false;
			}
			if (tagExists(t)) {
				return false;
			}
			var trial = getTagListFromDom().concat([t]).join(",");
			if (trial.length > maxSerialized) {
				return false;
			}
			var $pill = $("<span/>", { class: "adv-tags-pill" });
			$pill.data("tagText", t);
			$pill.append($("<span/>", { class: "adv-tags-pill-text", text: t }));
			var $rm = $("<button/>", {
				type: "button",
				class: "adv-tags-pill-remove",
				"aria-label": "Remove tag: " + t
			});
			$rm.text("\u00D7");
			$pill.append($rm);
			$pills.append($pill);
			return true;
		}

		function loadFromHidden() {
			$pills.empty();
			var raw = String($hidden.val() || "");
			var parts = raw.split(",");
			var i;
			for (i = 0; i < parts.length; i++) {
				if (getTagListFromDom().length >= maxTagCount) {
					break;
				}
				var chunk = String(parts[i] || "").trim();
				if (chunk.length > maxTagLen) {
					chunk = chunk.slice(0, maxTagLen);
				}
				if (chunk) {
					renderPill(chunk);
				}
			}
			syncHidden();
		}

		advTagsFlushPending = function () {
			var v = String($input.val() || "").trim();
			if (!v) {
				return;
			}
			if (renderPill(v)) {
				$input.val("");
				syncHidden();
			}
		};

		$input.on("keydown", function (e) {
			if (e.key === "Enter") {
				e.preventDefault();
				advTagsFlushPending();
				return;
			}
			if (e.key === "Backspace" && String($input.val() || "") === "") {
				var $last = $pills.find(".adv-tags-pill").last();
				if ($last.length) {
					$last.remove();
					syncHidden();
				}
			}
		});

		$pills.on("click", ".adv-tags-pill-remove", function (ev) {
			ev.preventDefault();
			ev.stopPropagation();
			$(this).closest(".adv-tags-pill").remove();
			syncHidden();
			$input.trigger("focus");
		});

		loadFromHidden();
	})();

	$("#as_save").on("click", function () {
		advTagsFlushPending();
		var $btn = $(this);
		$btn.removeClass("fgreen");
		var payload = {
			advid: parseInt($("#as_advid").val(), 10) || 0,
			title: $("#as_title").val(),
			description: $("#as_description").val(),
			tags: $("#as_tags").val(),
			avail: (function () {
				var $r = $("input[name='as_avail']:checked");
				return $r.length ? String($r.val()) : "none";
			})(),
			begin: $("#as_begin").val(),
			pic: $("#as_pic").val(),
			bgpic: parseInt($("#as_bgpic").val(), 10) || 0,
			bg: $("#as_bg").val(),
			box: $("#as_box").val(),
			border: $("#as_border").val(),
			borderwidth: (function () {
				var n = parseInt($("#as_borderwidth").val(), 10);
				return isNaN(n) ? 2 : n;
			})(),
			textcolor: $("#as_use_auto_text").is(":checked") ? "" : String($("#as_textcolor").val() || "").trim(),
			linkcolor: $("#as_use_auto_link").is(":checked") ? "" : String($("#as_linkcolor").val() || "").trim()
		};
		$.ajax({
			type: "POST",
			url: choosologyUrlSafe("ajax/saveadvmeta.php"),
			contentType: "application/json; charset=utf-8",
			dataType: "json",
			data: JSON.stringify(payload)
		}).done(function (res) {
			$btn.addClass("fgreen");
			if (res && res.ok) {
				if (typeof showAlert === "function") {
					showAlert("Experiment settings saved.", "success");
				}
				if (typeof loadAdv === "function" && typeof advid !== "undefined") {
					loadAdv(String(advid));
				}
				if (typeof closeAdvSettings === "function") {
					closeAdvSettings();
				}
			} else if (typeof showAlert === "function") {
				showAlert((res && res.error) ? res.error : "Save failed.", "error");
			}
		}).fail(function (xhr) {
			$btn.addClass("fgreen");
			var msg = "Save failed.";
			if (xhr.responseJSON && xhr.responseJSON.error) {
				msg = xhr.responseJSON.error;
			}
			if (typeof showAlert === "function") {
				showAlert(msg, "error");
			}
		});
	});
})();
</script>
