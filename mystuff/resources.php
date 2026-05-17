<?php
require_once("../connect.php");
require_once("../auxfuncs.php");

if (empty($_SESSION['user'])) {
	echo "<div class='intabs'><p class='error'>Please sign in to manage resources.</p></div>";
	return;
}
?>
<div class="intabs ms-resources-page">
	<div class="ms-resources-paper">
		<header class="ms-resources-head">
			<p class="ms-resources-eyebrow">Resource drawer</p>
			<h2 class="ms-resources-title">Pictures</h2>
			<p class="ms-resources-note">Upload image resources for experiments, then name and categorize them here. Maximum upload size: 1 MB.</p>
		</header>

		<section class="ms-resources-upload" aria-labelledby="ms-resources-upload-heading">
			<h3 id="ms-resources-upload-heading">Add picture</h3>
			<form id="ms_resources_upload_form" enctype="multipart/form-data">
				<label class="ms-resources-dropzone" id="ms_resources_dropzone" for="ms_resource_file">
					<span class="ms-resources-drop-title">Drop pictures here</span>
					<span class="ms-resources-drop-hint">or click to choose files. JPG, PNG, GIF, or WebP; 1 MB each.</span>
				</label>
				<input type="file" id="ms_resource_file" name="images[]" accept="image/*" multiple>
				<div id="ms_resources_pending" class="ms-resources-pending" aria-live="polite"></div>
				<button type="submit" id="ms_resource_upload_submit" disabled>Save uploaded pictures</button>
				<span id="ms_resource_upload_status" class="ms-resources-status" aria-live="polite"></span>
			</form>
		</section>

		<div class="ms-resources-layout">
			<section class="ms-resources-list-card" aria-labelledby="ms-resources-list-heading">
				<h3 id="ms-resources-list-heading">Your pictures</h3>
				<div class="ms-resources-gallery-controls">
					<label for="ms_resources_sort">Show by</label>
					<select id="ms_resources_sort">
						<option value="recent">Recency</option>
						<option value="category">Category</option>
						<option value="name">Name</option>
					</select>
				</div>
				<div id="ms_resources_list" class="ms-resources-list" aria-live="polite"></div>
			</section>

			<section class="ms-resources-detail-card" aria-labelledby="ms-resources-detail-heading">
				<h3 id="ms-resources-detail-heading">Picture info</h3>
				<div id="ms_resources_empty" class="ms-resources-empty">Select a picture to edit its name or category.</div>
				<form id="ms_resources_edit_form" class="ms-resources-edit" style="display:none;">
					<input type="hidden" id="ms_resource_id">
					<div class="ms-resources-preview" id="ms_resource_preview"></div>
					<label class="ms-resources-label" for="ms_resource_name">Name</label>
					<input type="text" id="ms_resource_name" maxlength="75">
					<label class="ms-resources-label" for="ms_resource_cat">Category</label>
					<input type="text" id="ms_resource_cat" maxlength="75">
					<div class="ms-resources-readonly">
						<span>Uploaded</span>
						<strong id="ms_resource_uploaded"></strong>
					</div>
					<div class="ms-resources-actions">
						<button type="submit" id="ms_resource_save">Save info</button>
						<button type="button" id="ms_resource_delete">Delete picture</button>
						<span id="ms_resource_edit_status" class="ms-resources-status" aria-live="polite"></span>
					</div>
				</form>
			</section>
		</div>
	</div>
</div>

<script>
(function () {
	function resourceUrl(path) {
		if (typeof choosologyUrl === "function") {
			return choosologyUrl(path);
		}
		path = String(path || "").replace(/^\//, "");
		return path ? ("/" + path) : "/";
	}

	var resources = [];
	var selectedId = null;
	var pendingFiles = [];
	var resourceCleanEditState = null;
	var resourceGuardMessage = "You have unsaved resource changes. Leave without saving?";

	function setStatus($el, message, isError) {
		$el.toggleClass("ms-resources-status--error", !!isError).text(message || "");
	}

	function resourceEditState() {
		return JSON.stringify({
			id: String($("#ms_resource_id").val() || ""),
			name: $("#ms_resource_name").val() || "",
			cat: $("#ms_resource_cat").val() || ""
		});
	}

	function markResourceEditClean() {
		resourceCleanEditState = resourceEditState();
	}

	function resourcesHaveUnsavedChanges() {
		if (!$(".ms-resources-page").length) {
			return false;
		}
		var editDirty = resourceCleanEditState !== null && resourceEditState() !== resourceCleanEditState;
		return pendingFiles.length > 0 || editDirty;
	}

	function resourcesConfirmLeave() {
		return !resourcesHaveUnsavedChanges() || window.confirm(resourceGuardMessage);
	}

	if (window.__choosologyResourcesGuardCapture) {
		document.removeEventListener("mousedown", window.__choosologyResourcesGuardCapture, true);
	}
	window.__choosologyResourcesGuardCapture = function (e) {
		var target = e.target && e.target.closest ? e.target.closest("a[href], .tabsa, .navbutton, #contextlink") : null;
		if (!target || target.closest(".ms-resources-page")) {
			return;
		}
		if (!resourcesConfirmLeave()) {
			e.preventDefault();
			e.stopPropagation();
			e.stopImmediatePropagation();
		}
	};
	document.addEventListener("mousedown", window.__choosologyResourcesGuardCapture, true);

	$(window).off("beforeunload.resources").on("beforeunload.resources", function (e) {
		if (!resourcesHaveUnsavedChanges()) {
			return undefined;
		}
		e.preventDefault();
		e.returnValue = "";
		return "";
	});

	function findResource(id) {
		id = String(id || "");
		for (var i = 0; i < resources.length; i++) {
			if (String(resources[i].id) === id) {
				return resources[i];
			}
		}
		return null;
	}

	function renderList() {
		var $list = $("#ms_resources_list").empty();
		if (!resources.length) {
			$list.append($("<p/>", { class: "ms-resources-empty", text: "No pictures uploaded yet." }));
			return;
		}
		var sortMode = $("#ms_resources_sort").val() || "recent";
		var sorted = resources.slice();
		if (sortMode === "category") {
			sorted.sort(function (a, b) {
				var ac = String(a.cat || "").toLowerCase();
				var bc = String(b.cat || "").toLowerCase();
				if (ac === bc) return String(a.imagename || a.filename || "").localeCompare(String(b.imagename || b.filename || ""));
				if (ac === "") return 1;
				if (bc === "") return -1;
				return ac.localeCompare(bc);
			});
		} else if (sortMode === "name") {
			sorted.sort(function (a, b) {
				return String(a.imagename || a.filename || "").localeCompare(String(b.imagename || b.filename || ""));
			});
		}
		var currentGroup = null;
		sorted.forEach(function (pic) {
			if (sortMode === "category") {
				var group = pic.cat || "Uncategorized";
				if (group !== currentGroup) {
					currentGroup = group;
					$list.append($("<div/>", { class: "ms-resource-group-heading", text: group }));
				}
			}
			var $item = $("<button/>", {
				type: "button",
				class: "ms-resource-tile" + (String(pic.id) === String(selectedId) ? " ms-resource-tile--selected" : ""),
				"data-id": pic.id
			});
			$item.append($("<span/>", { class: "ms-resource-tile-img" }).append($("<img/>", { src: pic.thumbUrl || pic.imageUrl || "", alt: "", loading: "lazy" })));
			$item.append($("<span/>", { class: "ms-resource-tile-name", text: pic.imagename || pic.filename || ("#" + pic.id) }));
			if (pic.cat) {
				$item.append($("<span/>", { class: "ms-resource-tile-cat", text: pic.cat }));
			}
			if (pic.uploadedDate) {
				$item.append($("<span/>", { class: "ms-resource-tile-date", text: pic.uploadedDate }));
			}
			$list.append($item);
		});
	}

	function selectResource(id) {
		selectedId = id;
		var pic = findResource(id);
		renderList();
		setStatus($("#ms_resource_edit_status"), "");
		if (!pic) {
			$("#ms_resources_empty").show();
			$("#ms_resources_edit_form").hide();
			$("#ms_resource_id, #ms_resource_name, #ms_resource_cat").val("");
			markResourceEditClean();
			return;
		}
		$("#ms_resources_empty").hide();
		$("#ms_resources_edit_form").show();
		$("#ms_resource_id").val(pic.id);
		$("#ms_resource_preview").html($("<img/>", { src: pic.imageUrl || pic.thumbUrl || "", alt: "" }));
		$("#ms_resource_name").val(pic.imagename || "");
		$("#ms_resource_cat").val(pic.cat || "");
		$("#ms_resource_uploaded").text(pic.uploaded || "");
		markResourceEditClean();
	}

	function loadResources(keepSelection) {
		$.getJSON(resourceUrl("ajax/listresources.php")).done(function (res) {
			if (!res || !res.ok) {
				$("#ms_resources_list").html($("<p/>", { class: "ms-resources-empty", text: (res && res.error) ? res.error : "Could not load pictures." }));
				return;
			}
			resources = Array.isArray(res.items) ? res.items : [];
			var old = keepSelection ? selectedId : null;
			renderList();
			if (old && findResource(old)) {
				selectResource(old);
			} else {
				selectResource(resources.length ? resources[0].id : null);
			}
		});
	}

	$("#ms_resources_list").on("click", ".ms-resource-tile", function () {
		selectResource($(this).attr("data-id"));
	});
	$("#ms_resources_sort").on("change", renderList);

	function defaultName(file) {
		return String(file && file.name ? file.name : "").replace(/\.[^.]+$/, "").slice(0, 75);
	}

	function renderPendingFiles() {
		var $pending = $("#ms_resources_pending").empty();
		$("#ms_resource_upload_submit").prop("disabled", pendingFiles.length < 1);
		if (!pendingFiles.length) {
			return;
		}
		pendingFiles.forEach(function (file, idx) {
			var tooLarge = file.size > 1024 * 1024;
			var $row = $("<div/>", { class: "ms-resource-pending-row" + (tooLarge ? " ms-resource-pending-row--error" : "") });
			$row.append($("<div/>", { class: "ms-resource-pending-file", text: file.name + (tooLarge ? " (over 1 MB)" : "") }));
			$row.append($("<label/>", { text: "Name" }).append($("<input/>", {
				type: "text",
				class: "ms-resource-pending-name",
				maxlength: 75,
				value: defaultName(file),
				"data-idx": idx
			})));
			$row.append($("<label/>", { text: "Category" }).append($("<input/>", {
				type: "text",
				class: "ms-resource-pending-cat",
				maxlength: 75,
				placeholder: "optional",
				"data-idx": idx
			})));
			$row.append($("<button/>", {
				type: "button",
				class: "ms-resource-pending-remove",
				"data-idx": idx,
				text: "Remove"
			}));
			$pending.append($row);
		});
	}

	function addPendingFiles(fileList) {
		var files = Array.prototype.slice.call(fileList || []);
		var rejected = 0;
		files.forEach(function (file) {
			if (!file || !/^image\//i.test(file.type || "")) {
				rejected++;
				return;
			}
			pendingFiles.push(file);
		});
		renderPendingFiles();
		setStatus($("#ms_resource_upload_status"), rejected ? (rejected + " non-image file(s) ignored.") : "");
	}

	$("#ms_resource_file").on("change", function () {
		addPendingFiles(this.files);
		this.value = "";
	});

	$("#ms_resources_dropzone").on("dragenter dragover", function (e) {
		e.preventDefault();
		e.stopPropagation();
		$(this).addClass("ms-resources-dropzone--active");
	}).on("dragleave dragend drop", function (e) {
		e.preventDefault();
		e.stopPropagation();
		$(this).removeClass("ms-resources-dropzone--active");
	});

	$("#ms_resources_dropzone").on("drop", function (e) {
		addPendingFiles(e.originalEvent && e.originalEvent.dataTransfer ? e.originalEvent.dataTransfer.files : []);
	});

	$("#ms_resources_pending").on("click", ".ms-resource-pending-remove", function () {
		pendingFiles.splice(parseInt($(this).attr("data-idx"), 10), 1);
		renderPendingFiles();
	});

	$("#ms_resources_upload_form").on("submit", function (e) {
		e.preventDefault();
		if (!pendingFiles.length) {
			setStatus($("#ms_resource_upload_status"), "Choose or drop images first.", true);
			return;
		}
		for (var i = 0; i < pendingFiles.length; i++) {
			if (pendingFiles[i].size > 1024 * 1024) {
				setStatus($("#ms_resource_upload_status"), "Remove files over 1 MB before saving.", true);
				return;
			}
		}
		var fd = new FormData();
		pendingFiles.forEach(function (file, idx) {
			fd.append("images[]", file);
			fd.append("imagename[]", $(".ms-resource-pending-name[data-idx='" + idx + "']").val() || defaultName(file));
			fd.append("cat[]", $(".ms-resource-pending-cat[data-idx='" + idx + "']").val() || "");
		});
		$("#ms_resource_upload_submit").prop("disabled", true);
		setStatus($("#ms_resource_upload_status"), "Uploading " + pendingFiles.length + " picture(s)...");
		$.ajax({
			type: "POST",
			url: resourceUrl("ajax/uploadresource.php"),
			data: fd,
			contentType: false,
			processData: false,
			dataType: "json"
		}).done(function (res) {
			if (!res || !res.ok) {
				setStatus($("#ms_resource_upload_status"), (res && res.error) ? res.error : "Upload failed.", true);
				return;
			}
			setStatus($("#ms_resource_upload_status"), "Uploaded " + ((res.ids && res.ids.length) || 1) + " picture(s).");
			$("#ms_resources_upload_form")[0].reset();
			pendingFiles = [];
			renderPendingFiles();
			selectedId = (res.ids && res.ids.length) ? res.ids[0] : res.id;
			loadResources(true);
		}).fail(function () {
			setStatus($("#ms_resource_upload_status"), "Upload failed.", true);
		}).always(function () {
			$("#ms_resource_upload_submit").prop("disabled", pendingFiles.length < 1);
		});
	});

	$("#ms_resources_edit_form").on("submit", function (e) {
		e.preventDefault();
		var id = $("#ms_resource_id").val();
		setStatus($("#ms_resource_edit_status"), "Saving...");
		$.ajax({
			type: "POST",
			url: resourceUrl("ajax/saveresource.php"),
			contentType: "application/json; charset=utf-8",
			dataType: "json",
			data: JSON.stringify({
				id: id,
				imagename: $("#ms_resource_name").val(),
				cat: $("#ms_resource_cat").val()
			})
		}).done(function (res) {
			if (!res || !res.ok) {
				setStatus($("#ms_resource_edit_status"), (res && res.error) ? res.error : "Save failed.", true);
				return;
			}
			setStatus($("#ms_resource_edit_status"), "Saved.");
			selectedId = id;
			markResourceEditClean();
			loadResources(true);
		}).fail(function () {
			setStatus($("#ms_resource_edit_status"), "Save failed.", true);
		});
	});

	$("#ms_resource_delete").on("click", function () {
		var id = $("#ms_resource_id").val();
		var pic = findResource(id);
		if (!id || !window.confirm("Delete \"" + ((pic && pic.imagename) || "this picture") + "\"?")) {
			return;
		}
		setStatus($("#ms_resource_edit_status"), "Deleting...");
		$.ajax({
			type: "POST",
			url: resourceUrl("ajax/deleteresource.php"),
			contentType: "application/json; charset=utf-8",
			dataType: "json",
			data: JSON.stringify({ id: id })
		}).done(function (res) {
			if (!res || !res.ok) {
				setStatus($("#ms_resource_edit_status"), (res && res.error) ? res.error : "Delete failed.", true);
				return;
			}
			selectedId = null;
			loadResources(false);
		}).fail(function () {
			setStatus($("#ms_resource_edit_status"), "Delete failed.", true);
		});
	});

	loadResources(false);
})();
</script>