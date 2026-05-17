<?php
require_once("../connect.php");
require_once("../auxfuncs.php");

if (empty($_SESSION['user'])) {
	echo "<div class='intabs'><p class='error'>Please sign in to view your information.</p></div>";
	return;
}

$user = (string) $_SESSION['user'];
$escUser = mysqli_real_escape_string($db, $user);
$rows = runquery_assoc("SELECT name, email, about, pic, view_restricted FROM users WHERE name = '$escUser' LIMIT 1");
if (!is_array($rows) || !isset($rows[0])) {
	echo "<div class='intabs'><p class='error'>Could not load your account.</p></div>";
	return;
}

$account = $rows[0];
$email = htmlspecialchars((string) ($account['email'] ?? ''), ENT_QUOTES, 'UTF-8');
$about = htmlspecialchars((string) ($account['about'] ?? ''), ENT_QUOTES, 'UTF-8');
$pic = trim((string) ($account['pic'] ?? ''));
$picUrl = ($pic !== '' && ctype_digit($pic)) ? getPicUrl((int) $pic, true) : '';
$viewRestricted = (int) ($account['view_restricted'] ?? 0) === 1;
?>
<div class="intabs ms-account-page">
	<form id="ms-account-form" class="ms-account-paper" onsubmit="return false;">
		<div class="ms-account-head">
			<p class="ms-account-eyebrow">Profile folder</p>
			<h2 class="ms-account-title">My Information</h2>
		</div>

		<div class="ms-account-layout">
			<section class="ms-account-note ms-account-note--photo" aria-labelledby="ms-account-photo-heading">
				<h3 id="ms-account-photo-heading">Profile image</h3>
				<input type="hidden" id="acct_pic" value="<?php echo htmlspecialchars($pic, ENT_QUOTES, 'UTF-8'); ?>">
				<button type="button" class="acct-preview-hit" id="acct_pic_open" title="Open image library">
					<span class="acct-preview-frame<?php echo $picUrl === '' ? ' acct-preview-frame--empty' : ''; ?>" id="acct_pic_frame">
						<?php if ($picUrl !== '') { ?>
						<img id="acct_pic_preview" src="<?php echo htmlspecialchars($picUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="">
						<?php } else { ?>
						<span id="acct_pic_preview" class="acct-preview-empty">No image</span>
						<?php } ?>
					</span>
				</button>
				<div class="ms-account-inline-actions">
					<button type="button" id="acct_pic_clear">Clear image</button>
					<span class="ms-account-hint">Click the image area to choose from your library.</span>
				</div>
			</section>

			<section class="ms-account-note ms-account-note--bio" aria-labelledby="ms-account-bio-heading">
				<h3 id="ms-account-bio-heading">Bio blurb</h3>
				<textarea id="acct_about" rows="8"><?php echo $about; ?></textarea>
				<p class="ms-account-hint"><span id="acct_about_count">0</span>/400 characters, not counting formatting.</p>
			</section>

			<section class="ms-account-note ms-account-note--settings" aria-labelledby="ms-account-settings-heading">
				<h3 id="ms-account-settings-heading">Account settings</h3>
				<label class="ms-account-check">
					<input type="checkbox" id="acct_view_restricted"<?php echo $viewRestricted ? ' checked' : ''; ?>>
					<span>Show restricted adventures when browsing</span>
				</label>

				<label class="ms-account-label" for="acct_email">Email address</label>
				<input type="email" id="acct_email" value="<?php echo $email; ?>" readonly>
			</section>

			<section class="ms-account-note ms-account-note--password" aria-labelledby="ms-account-password-heading">
				<h3 id="ms-account-password-heading">Update password</h3>
				<label class="ms-account-label" for="acct_current_password">Current password</label>
				<input type="password" id="acct_current_password" autocomplete="current-password">
				<label class="ms-account-label" for="acct_new_password">New password</label>
				<input type="password" id="acct_new_password" autocomplete="new-password">
				<label class="ms-account-label" for="acct_confirm_password">Confirm new password</label>
				<input type="password" id="acct_confirm_password" autocomplete="new-password">
				<p class="ms-account-hint">Leave password fields blank to keep your current password.</p>
			</section>
		</div>

		<div class="ms-account-actions">
			<button type="button" id="acct_save">Save information</button>
			<span id="acct_status" aria-live="polite"></span>
		</div>
	</form>

	<div id="acct_picmodal" class="acct-picmodal acct-picmodal--hidden" aria-hidden="true">
		<div class="acct-picmodal-backdrop" tabindex="-1"></div>
		<div class="acct-picmodal-panel" role="dialog" aria-modal="true" aria-labelledby="acct_picmodal_title">
			<div class="acct-picmodal-header">
				<h3 id="acct_picmodal_title">Choose profile image</h3>
				<button type="button" class="acct-picmodal-x" id="acct_picmodal_close" aria-label="Close">&times;</button>
			</div>
			<div class="acct-picbrowser">
				<div class="acct-picbrowser-toolbar">
					<input type="search" id="acct_pic_q" placeholder="Search by title, filename, or tag" autocomplete="off">
					<select id="acct_pic_tag" aria-label="Filter by tag">
						<option value="">All tags &amp; categories</option>
					</select>
				</div>
				<div id="acct_pic_status" class="acct-picbrowser-status" aria-live="polite"></div>
				<div class="acct-picbrowser-scroll">
					<div id="acct_pic_grid" class="acct-picbrowser-grid"></div>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
(function () {
	function acctUrl(path) {
		if (typeof choosologyUrl === "function") {
			return choosologyUrl(path);
		}
		path = String(path || "").replace(/^\//, "");
		return path ? ("/" + path) : "/";
	}

	var tinyBase = "https://cdnjs.cloudflare.com/ajax/libs/tinymce/7.6.1";
	var initialAboutHtml = <?php echo json_encode((string) ($account['about'] ?? ''), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
	var accountCleanState = null;
	var accountTouched = false;
	var accountGuardMessage = "You have unsaved changes on My Information. Leave without saving?";

	function accountAboutHtml() {
		if (window.tinymce && tinymce.get("acct_about")) {
			return tinymce.get("acct_about").getContent();
		}
		return $("#acct_about").val() || "";
	}

	function accountState() {
		return JSON.stringify({
			pic: String($("#acct_pic").val() || ""),
			about: accountAboutHtml(),
			viewRestricted: $("#acct_view_restricted").is(":checked") ? "1" : "0",
			currentPassword: $("#acct_current_password").val() || "",
			newPassword: $("#acct_new_password").val() || "",
			confirmPassword: $("#acct_confirm_password").val() || ""
		});
	}

	function markAccountClean() {
		accountCleanState = accountState();
	}

	function accountHasUnsavedChanges() {
		if (!$(".ms-account-page").length) {
			return false;
		}
		return accountCleanState !== null && accountState() !== accountCleanState;
	}

	function accountConfirmLeave() {
		return !accountHasUnsavedChanges() || window.confirm(accountGuardMessage);
	}

	if (window.__choosologyAccountGuardCapture) {
		document.removeEventListener("mousedown", window.__choosologyAccountGuardCapture, true);
	}
	window.__choosologyAccountGuardCapture = function (e) {
		var target = e.target && e.target.closest ? e.target.closest("a[href], .tabsa, .navbutton, #contextlink") : null;
		if (!target || target.closest(".ms-account-page")) {
			return;
		}
		if (!accountConfirmLeave()) {
			e.preventDefault();
			e.stopPropagation();
			e.stopImmediatePropagation();
		}
	};
	document.addEventListener("mousedown", window.__choosologyAccountGuardCapture, true);

	$(window).off("beforeunload.account").on("beforeunload.account", function (e) {
		if (!accountHasUnsavedChanges()) {
			return undefined;
		}
		e.preventDefault();
		e.returnValue = "";
		return "";
	});
	markAccountClean();

	function ensureAccountTiny(callback) {
		if (window.tinymce && typeof tinymce.init === "function") {
			callback();
			return;
		}
		if (window.__choosologyAccountTinyLoading) {
			window.__choosologyAccountTinyQueue = window.__choosologyAccountTinyQueue || [];
			window.__choosologyAccountTinyQueue.push(callback);
			return;
		}
		window.__choosologyAccountTinyLoading = true;
		window.__choosologyAccountTinyQueue = [callback];
		var s = document.createElement("script");
		s.src = tinyBase + "/tinymce.min.js";
		s.async = true;
		s.crossOrigin = "anonymous";
		s.onload = function () {
			var q = window.__choosologyAccountTinyQueue || [];
			window.__choosologyAccountTinyQueue = [];
			window.__choosologyAccountTinyLoading = false;
			q.forEach(function (cb) { cb(); });
		};
		document.head.appendChild(s);
	}

	function plainAboutText() {
		var html = "";
		if (window.tinymce && tinymce.get("acct_about")) {
			html = tinymce.get("acct_about").getContent({ format: "text" });
		} else {
			html = $("#acct_about").val();
		}
		return String(html || "").replace(/\s+/g, " ").trim();
	}

	function updateAboutCount() {
		$("#acct_about_count").text(plainAboutText().length);
		$("#acct_about_count").toggleClass("ms-account-count--over", plainAboutText().length > 400);
	}

	ensureAccountTiny(function () {
		if (tinymce.get("acct_about")) {
			tinymce.get("acct_about").remove();
		}
		tinymce.init({
			selector: "#acct_about",
			base_url: tinyBase,
			suffix: ".min",
			menubar: false,
			branding: false,
			height: 230,
			plugins: "lists link autoresize",
			toolbar: "bold italic forecolor backcolor | bullist numlist | link | removeformat",
			setup: function (editor) {
				editor.on("init", function () {
					editor.setContent(initialAboutHtml || "");
					updateAboutCount();
					if (!accountTouched) {
						markAccountClean();
					}
				});
				editor.on("init keyup change input undo redo", updateAboutCount);
				editor.on("keyup change input undo redo", function () {
					accountTouched = true;
				});
			}
		});
	});
	window.setTimeout(updateAboutCount, 300);

	var picLibrary = null;
	function normalize(s) {
		return String(s || "").toLowerCase();
	}
	function filterPics(items) {
		var q = normalize($("#acct_pic_q").val()).trim();
		var tag = String($("#acct_pic_tag").val() || "").toLowerCase();
		return (items || []).filter(function (it) {
			if (tag) {
				var tags = Array.isArray(it.tags) ? it.tags : [];
				var hasTag = tags.some(function (t) { return String(t).toLowerCase() === tag; });
				if (!hasTag && normalize(it.cat) !== tag) return false;
			}
			if (q) {
				var blob = normalize(it.title) + "\n" + normalize(it.filename) + "\n" + normalize(it.cat);
				if (blob.indexOf(q) === -1) return false;
			}
			return true;
		});
	}
	function redrawPics() {
		if (!picLibrary || !Array.isArray(picLibrary.items)) return;
		var list = filterPics(picLibrary.items);
		var selected = String($("#acct_pic").val() || "");
		var $grid = $("#acct_pic_grid").empty();
		$("#acct_pic_status").text(list.length + " of " + picLibrary.items.length + " shown");
		list.forEach(function (it) {
			var $tile = $("<button/>", {
				type: "button",
				class: "acct-pic-tile" + (String(it.id) === selected ? " acct-pic-tile--selected" : ""),
				"data-pic-id": String(it.id),
				"data-thumb-url": it.thumbUrl || ""
			});
			$tile.append($("<span/>", { class: "acct-pic-tile-img" }).append($("<img/>", { src: it.thumbUrl || "", alt: "", loading: "lazy" })));
			$tile.append($("<span/>", { class: "acct-pic-tile-cap", text: it.title || ("#" + it.id) }));
			$grid.append($tile);
		});
	}
	function loadPics(callback) {
		if (picLibrary) {
			callback();
			return;
		}
		$.getJSON(acctUrl("ajax/listaccountpics.php")).done(function (res) {
			if (!res || !res.ok) {
				$("#acct_pic_status").text((res && res.error) ? res.error : "Could not load images.");
				return;
			}
			picLibrary = res;
			var tags = Array.isArray(res.tagOptions) ? res.tagOptions : [];
			tags.forEach(function (tag) {
				$("#acct_pic_tag").append($("<option/>").val(tag).text(tag));
			});
			$("#acct_pic_tag").prop("disabled", tags.length < 1);
			callback();
		});
	}
	function openPicModal() {
		$("#acct_picmodal").removeClass("acct-picmodal--hidden").attr("aria-hidden", "false");
		loadPics(redrawPics);
	}
	function closePicModal() {
		$("#acct_picmodal").addClass("acct-picmodal--hidden").attr("aria-hidden", "true");
	}
	$("#acct_pic_open").on("click", openPicModal);
	$("#acct_picmodal_close, #acct_picmodal .acct-picmodal-backdrop").on("click", closePicModal);
	$("#acct_pic_q").on("input", redrawPics);
	$("#acct_pic_tag").on("change", redrawPics);
	$("#acct_pic_grid").on("click", ".acct-pic-tile", function () {
		var id = $(this).attr("data-pic-id") || "";
		var url = $(this).attr("data-thumb-url") || "";
		accountTouched = true;
		$("#acct_pic").val(id);
		$("#acct_pic_frame").removeClass("acct-preview-frame--empty").html($("<img/>", { id: "acct_pic_preview", src: url, alt: "" }));
		closePicModal();
	});
	$("#acct_pic_clear").on("click", function () {
		accountTouched = true;
		$("#acct_pic").val("");
		$("#acct_pic_frame").addClass("acct-preview-frame--empty").html($("<span/>", { id: "acct_pic_preview", class: "acct-preview-empty", text: "No image" }));
	});
	$("#ms-account-form").on("input change keyup", "input, textarea", function () {
		accountTouched = true;
	});

	$("#acct_save").on("click", function () {
		updateAboutCount();
		if (plainAboutText().length > 400) {
			$("#acct_status").addClass("ms-account-status--error").text("Bio is over 400 characters.");
			return;
		}
		if (window.tinymce && tinymce.get("acct_about")) {
			tinymce.get("acct_about").save();
		}
		var payload = {
			pic: $("#acct_pic").val(),
			about: $("#acct_about").val(),
			view_restricted: $("#acct_view_restricted").is(":checked") ? 1 : 0,
			current_password: $("#acct_current_password").val(),
			new_password: $("#acct_new_password").val(),
			confirm_password: $("#acct_confirm_password").val()
		};
		$("#acct_save").prop("disabled", true);
		$("#acct_status").removeClass("ms-account-status--error").text("Saving...");
		$.ajax({
			type: "POST",
			url: acctUrl("ajax/saveaccount.php"),
			contentType: "application/json; charset=utf-8",
			dataType: "json",
			data: JSON.stringify(payload)
		}).done(function (res) {
			if (res && res.ok) {
				$("#acct_current_password, #acct_new_password, #acct_confirm_password").val("");
				$("#acct_status").text(res.passwordChanged ? "Saved. Password updated." : "Saved.");
				if (window.tinymce && tinymce.get("acct_about")) {
					tinymce.get("acct_about").save();
				}
				accountTouched = false;
				markAccountClean();
				return;
			}
			$("#acct_status").addClass("ms-account-status--error").text((res && res.error) ? res.error : "Could not save.");
		}).fail(function (xhr) {
			var msg = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : "Could not save.";
			$("#acct_status").addClass("ms-account-status--error").text(msg);
		}).always(function () {
			$("#acct_save").prop("disabled", false);
		});
	});
})();
</script>