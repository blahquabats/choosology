<?php
/**
 * RESTRICTED — admin tools and lab-guide configuration.
 */
require_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/../auxfuncs.php';
require_once __DIR__ . '/../lib/restricted-helpers.php';

if (!choosology_restricted_is_admin()) {
	http_response_code(403);
	echo "<div class='intabs'><p class='error'>RESTRICTED: lab admin access required.</p></div>";
	return;
}
?>
<div class="intabs restricted-page" id="restricted_center">
	<header class="restricted-head">
		<div>
			<p class="restricted-eyebrow">My Office · authorization level <?php echo (int) $_SESSION['usertype']; ?></p>
			<h2 class="restricted-title">RESTRICTED</h2>
			<p class="restricted-lede">Lab administration, credentials, treasury, moderation, and guide programming.</p>
		</div>
		<span class="restricted-stamp" aria-hidden="true">AUTHORIZED<br>PERSONNEL</span>
	</header>

	<nav class="restricted-nav" aria-label="Restricted tools">
		<button type="button" class="restricted-nav-btn is-active" data-restricted-view="mascots">Mascot Packs</button>
		<button type="button" class="restricted-nav-btn" data-restricted-view="beats">Dialog Beats</button>
		<button type="button" class="restricted-nav-btn" data-restricted-view="degrees">Grant Degrees</button>
		<button type="button" class="restricted-nav-btn" data-restricted-view="scrip">Award DataScrip</button>
		<button type="button" class="restricted-nav-btn" data-restricted-view="comments">Comment Review <span id="restricted_comment_badge"></span></button>
	</nav>

	<p class="restricted-global-status" id="restricted_global_status" aria-live="polite"></p>
	<datalist id="restricted_users"></datalist>

	<section class="restricted-view is-active" data-restricted-panel="mascots" aria-labelledby="restricted_mascot_heading">
		<div class="restricted-section-head">
			<div>
				<p class="restricted-kicker">Illustrated packs</p>
				<h3 id="restricted_mascot_heading">Mascot packs</h3>
			</div>
		</div>
		<p class="restricted-note">A pack owns colors, voice, button labels, and availability. Pose art is read from <code>images/mascots/{key}/{pose}.png</code>. The corner shows this pack’s idle pose and dialog styling.</p>
		<form class="restricted-card restricted-form" id="restricted_mascot_form" onsubmit="return false;">
			<div class="restricted-card-head">
				<h4>Pack</h4>
				<div class="restricted-card-picker">
					<select id="restricted_mascot_select" aria-label="Mascot pack"></select>
					<button type="button" class="restricted-btn" id="restricted_mascot_new">New</button>
				</div>
			</div>
			<div class="restricted-mascot-layout">
				<fieldset class="restricted-fieldset">
					<legend>Pose images</legend>
					<div id="restricted_pose_gallery" class="restricted-pose-gallery"></div>
				</fieldset>
				<div>
					<div class="restricted-fields restricted-fields--two">
						<label>Stable key <input id="restricted_mascot_key" maxlength="64" pattern="[a-z0-9_]+"></label>
						<label>Display name <input id="restricted_mascot_name" maxlength="80"></label>
						<label>Sort order <input id="restricted_mascot_sort" type="number" value="0"></label>
						<label>Availability
							<select id="restricted_mascot_availability">
								<option value="global">Global</option>
								<option value="purchased">Purchased</option>
								<option value="earned">Earned</option>
							</select>
						</label>
						<span class="restricted-checks">
							<label><input id="restricted_mascot_enabled" type="checkbox" checked> Enabled</label>
							<label><input id="restricted_mascot_default" type="checkbox"> Default</label>
						</span>
					</div>
					<fieldset class="restricted-fieldset">
						<legend>Theme tokens</legend>
						<div class="restricted-fields restricted-fields--three">
							<label>Accent <input id="restricted_theme_accent" value="#c4a35a" maxlength="7"></label>
							<label>Paper <input id="restricted_theme_paper" value="#f4ecd8" maxlength="7"></label>
							<label>Ink <input id="restricted_theme_ink" value="#1c1a16" maxlength="7"></label>
						</div>
					</fieldset>
					<fieldset class="restricted-fieldset">
						<legend>Dialog lines (one variation per line)</legend>
						<div class="restricted-fields">
							<label>Offer help <textarea id="restricted_lines_offer" rows="3"></textarea></label>
							<label>Explain <textarea id="restricted_lines_explain" rows="3"></textarea></label>
							<label>Done <textarea id="restricted_lines_done" rows="3"></textarea></label>
						</div>
					</fieldset>
					<fieldset class="restricted-fieldset">
						<legend>Choice labels</legend>
						<div class="restricted-fields restricted-fields--two">
							<label>Accept <input id="restricted_choice_accept"></label>
							<label>Decline <input id="restricted_choice_decline"></label>
							<label>Later <input id="restricted_choice_later"></label>
							<label>Done <input id="restricted_choice_done"></label>
							<label>Next <input id="restricted_choice_next"></label>
						</div>
					</fieldset>
				</div>
			</div>
			<div class="restricted-actions">
				<button type="button" class="restricted-btn restricted-btn--primary" id="restricted_mascot_save">Save mascot</button>
				<button type="button" class="restricted-btn" id="restricted_mascot_reset">Reset built-in</button>
				<button type="button" class="restricted-btn restricted-btn--danger" id="restricted_mascot_delete">Delete custom</button>
			</div>
		</form>
	</section>

	<section class="restricted-view" data-restricted-panel="beats" aria-labelledby="restricted_beats_heading" hidden>
		<div class="restricted-section-head">
			<div>
				<p class="restricted-kicker">Global behavior</p>
				<h3 id="restricted_beats_heading">Dialog beats</h3>
			</div>
			<button type="button" class="restricted-btn" id="restricted_reset_progress">Reset my guide progress</button>
		</div>
		<p class="restricted-note">These interactions are shared by every mascot. At runtime the site default pack speaks them. Choose a pack only when previewing.</p>
		<div class="restricted-actions restricted-preview-bar">
			<label>Preview mascot <select id="restricted_preview_mascot" aria-label="Preview mascot"></select></label>
			<button type="button" class="restricted-btn" id="restricted_interaction_preview">Preview</button>
		</div>
		<form class="restricted-card restricted-form" id="restricted_interaction_form" onsubmit="return false;">
			<div class="restricted-card-head">
				<h4>Interaction</h4>
				<div class="restricted-card-picker">
					<select id="restricted_interaction_select" aria-label="Interaction"></select>
					<button type="button" class="restricted-btn" id="restricted_interaction_new">New</button>
				</div>
			</div>
			<div class="restricted-fields restricted-fields--two">
				<label>Stable key <input id="restricted_interaction_key" maxlength="64" pattern="[a-z0-9_]+"></label>
				<label>Title <input id="restricted_interaction_title" maxlength="120"></label>
				<label>Event <select id="restricted_interaction_event"></select></label>
				<label>Feature <select id="restricted_interaction_feature"></select></label>
				<label>Surface
					<select id="restricted_interaction_surface">
						<option value="classic">Classic</option>
						<option value="lite">Lite</option>
						<option value="both">Both</option>
					</select>
				</label>
				<label>Audience
					<select id="restricted_interaction_audience">
						<option value="both">Everyone</option>
						<option value="logged_in">Signed in</option>
						<option value="anonymous">Anonymous</option>
					</select>
				</label>
				<label>Priority <input id="restricted_interaction_priority" type="number" value="100"></label>
				<label class="restricted-check"><input id="restricted_interaction_enabled" type="checkbox" checked> Enabled</label>
			</div>
			<div class="restricted-beats-head">
				<h5>Beats</h5>
				<button type="button" class="restricted-btn" id="restricted_beat_add">Add beat</button>
			</div>
			<div id="restricted_beats" class="restricted-beats"></div>
			<div class="restricted-actions">
				<button type="button" class="restricted-btn restricted-btn--primary" id="restricted_interaction_save">Save interaction</button>
				<button type="button" class="restricted-btn" id="restricted_interaction_reset">Reset built-in</button>
				<button type="button" class="restricted-btn restricted-btn--danger" id="restricted_interaction_delete">Delete custom</button>
			</div>
		</form>
	</section>

	<div id="restricted_dock" class="restricted-dock">
		<div id="restricted_mascot_preview" aria-live="polite"></div>
		<div id="restricted_preview" aria-live="polite"></div>
	</div>

	<section class="restricted-view" data-restricted-panel="degrees" aria-labelledby="restricted_degrees_heading" hidden>
		<div class="restricted-card restricted-card--compact">
			<p class="restricted-kicker">Credentials office</p>
			<h3 id="restricted_degrees_heading">Grant a Degree</h3>
			<p>Manual grants use the normal award path: the recipient receives its DataScrip reward, trophy-room trinket, and a CLIC notice.</p>
			<form id="restricted_degree_form" class="restricted-form restricted-fields" onsubmit="return false;">
				<label>Recipient <input id="restricted_degree_user" list="restricted_users" maxlength="45" autocomplete="off"></label>
				<label>Degree <select id="restricted_degree_key"></select></label>
				<div class="restricted-actions">
					<button type="submit" class="restricted-btn restricted-btn--primary">Grant Degree</button>
				</div>
			</form>
		</div>
	</section>

	<section class="restricted-view" data-restricted-panel="scrip" aria-labelledby="restricted_scrip_heading" hidden>
		<div class="restricted-card restricted-card--compact">
			<p class="restricted-kicker">Treasury authorization</p>
			<h3 id="restricted_scrip_heading">Award DataScrip</h3>
			<form id="restricted_scrip_form" class="restricted-form restricted-fields" onsubmit="return false;">
				<div class="restricted-checks">
					<label><input type="radio" name="restricted_scrip_scope" value="one" checked> Individual</label>
					<label><input type="radio" name="restricted_scrip_scope" value="all"> All users</label>
				</div>
				<label>Recipient <input id="restricted_scrip_user" list="restricted_users" maxlength="45" autocomplete="off"></label>
				<label>Amount <input id="restricted_scrip_amount" type="number" min="1" max="100000" value="10"></label>
				<label>Memo <input id="restricted_scrip_memo" maxlength="255"></label>
				<div class="restricted-actions">
					<button type="submit" class="restricted-btn restricted-btn--primary">Award DataScrip</button>
				</div>
			</form>
		</div>
	</section>

	<section class="restricted-view" data-restricted-panel="comments" aria-labelledby="restricted_comments_heading" hidden>
		<div class="restricted-section-head">
			<div>
				<p class="restricted-kicker">Moderation queue</p>
				<h3 id="restricted_comments_heading">Comment Review</h3>
			</div>
			<button type="button" class="restricted-btn restricted-btn--primary" id="restricted_comments_approve_all">Approve all</button>
		</div>
		<div id="restricted_comments" class="restricted-comments"></div>
	</section>
</div>
<script>
(function () {
	if (window.ChoosologyRestricted && typeof ChoosologyRestricted.init === "function") {
		ChoosologyRestricted.init(document.getElementById("restricted_center"));
	}
})();
</script>
