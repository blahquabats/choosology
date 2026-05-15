<?php
require_once("connect.php");
require_once("auxfuncs.php");

?>
<div id="wholepage" class="browse-program">
	<div class="browse-program-head">
		<div class="browse-program-head-inner">
			<p class="browse-program-eyebrow">Lab directory <span class="browse-program-eyebrow-tag">public catalog</span></p>
			<h2 class="browse-program-title">Browse experiments</h2>
		</div>
	</div>

	<div class="browse-program-body">
		<div class="browse-program-layout">
			<main class="browse-program-main">
				<section class="browse-section browse-section--intro" aria-labelledby="browse-intro-heading">
					<h3 class="browse-section-heading" id="browse-intro-heading"><span class="browse-section-num" aria-hidden="true">01</span> Participate</h3>
					<div class="browse-intro">
						<p>We are always looking for new subjects on whom to experiment. After you have completed your experience, please rate
						the experiment and perhaps even leave a comment for the Choosologist in charge.</p>
						<p>You may browse the offerings below or search for a particular experiment.</p>
					</div>
				</section>

				<section class="browse-section browse-section--catalog" aria-labelledby="browse-catalog-heading">
					<h3 class="browse-section-heading" id="browse-catalog-heading"><span class="browse-section-num" aria-hidden="true">02</span> Catalog view</h3>
					<label class="browse-label" for="viewselect">Preset</label>
					<select id="viewselect" class="browse-select" name="viewselect">
						<option value="gs">General Survey</option>
						<option value="tr">Top Rated All-Time</option>
						<option value="rp">Recently Published</option>
						<option value="re">Recently Edited</option>
						<option value="fa">From The Archives (random unrated experiments)</option>
						<option value="as">Advanced Search…</option>
					</select>

					<div id="advsearch" class="browse-advanced">
						<label class="browse-label" for="titlesearch">Title contains</label>
						<div class="browse-advanced-row">
							<input type="text" name="titlesearch" id="titlesearch" class="browse-input" maxlength="255" />
							<button type="button" id="advsearchbutton" class="browse-btn fakebutton fgreen">Search</button>
						</div>
					</div>
				</section>
			</main>

			<aside class="browse-program-aside" aria-labelledby="browse-quick-heading">
				<section class="browse-section browse-section--quick">
					<h3 class="browse-section-heading" id="browse-quick-heading"><span class="browse-section-num" aria-hidden="true">03</span> Quick title lookup</h3>
					<label class="browse-label" for="experimentsearch">Type at least three letters</label>
					<div class="browse-quick-row">
						<span class="browse-search-icon" aria-hidden="true"><?php echo icon("search"); ?></span>
						<input type="search" id="experimentsearch" class="browse-input browse-input--quick" placeholder="Experiment title…" autocomplete="off" />
					</div>
					<div id="results" class="browse-live-results"></div>
				</section>
			</aside>
		</div>

		<div id="otherexperiments" class="browse-results">
			<div id="oecont" class="browse-oecont browse-oecont--triptych">
				<div id="browse-column-toolbar" class="browse-column-toolbar" aria-hidden="true"></div>
				<?php
				echo "<div class='advcolumn'></div>";
				echo "<div class='advcolumn'></div>";
				echo "<div class='advcolumn'></div>";
				?>
			</div>
		</div>

		<div id="searchloading" class="browse-loading" aria-live="polite">Fetching experiments…</div>
	</div>
</div>
<script>
	$(document).ready(function(){
		$("#wholepage").on("click", ".browse-nav-prev, .browse-nav-next", function(e) {
			e.preventDefault();
			var el = this;
			var which = el.getAttribute("data-which") || "";
			var limit = parseInt(el.getAttribute("data-limit"), 10) || 4;
			var page = parseInt(el.getAttribute("data-page"), 10) || 1;
			if (which) {
				triggerSearch(which, limit, 1, page);
			}
		});

		$("#viewselect").on("change", function() {
			var val = $("#viewselect option:selected").val();
			if (!val) return false;
			if(val == "as")
			{
				$("#advsearch").show("blind", 400);
				return false;
			}
			$("#advsearch").hide("blind", 400);
			triggerSearch(val, 4, 1);
		});

		$("#advsearchbutton").click(function(){
			triggerSearch("as", 4, 1);
		});
		$("#experimentsearch").keyup(function() {
			var val = $(this).val();
			if (val.length < 3)
			{
				$("#results").html('').css("display", "none");
				return true;
			}
			$.ajax({
				type: "POST",
				url: "searchadv.php",
				data: {
					limit: 4,
					searchval: val
				}
			}).done(function(response) {
				if (!response) return true;
				if(response != 2)
				{
					$("#results").html(response).css("display", "block");
					$("#wholepage").click(function(){
						$("#results").html('').css("display", "none");
						$("#wholepage").off("click");
					});
				}
			});
		});
	});

	function applyBrowseColumnResults(ponse)
	{
		var $oe = $("#oecont");
		var $tb = $("#browse-column-toolbar");
		var isUnified = ponse.length >= 4 && ponse[0] && String(ponse[0]).indexOf("columntitle--unified") !== -1;

		if (isUnified)
		{
			if ($oe.hasClass("browse-oecont--unified"))
			{
				$(".advcolumn").eq(0).html(ponse[1] || "");
				$(".advcolumn").eq(1).html(ponse[2] || "");
				$(".advcolumn").eq(2).html(ponse[3] || "");
				$tb.html(ponse[0] || "");
				$tb.attr("aria-hidden", "false");
				if (!$tb.hasClass("browse-column-toolbar--open")) {
					$tb.addClass("browse-column-toolbar--open");
				}
				return;
			}
			$tb.removeClass("browse-column-toolbar--open");
			$(".advcolumn").eq(0).html(ponse[1] || "");
			$(".advcolumn").eq(1).html(ponse[2] || "");
			$(".advcolumn").eq(2).html(ponse[3] || "");
			$tb.html(ponse[0] || "");
			$tb.attr("aria-hidden", "false");
			$oe.removeClass("browse-oecont--triptych").addClass("browse-oecont--unified");
			if ($tb[0]) {
				void $tb[0].offsetWidth;
			}
			requestAnimationFrame(function() {
				requestAnimationFrame(function() {
					$tb.addClass("browse-column-toolbar--open");
				});
			});
			return;
		}

		function finishTriptych() {
			$tb.removeClass("browse-column-toolbar--open");
			$tb.html("");
			$tb.attr("aria-hidden", "true");
			$oe.removeClass("browse-oecont--unified").addClass("browse-oecont--triptych");
			$(".advcolumn").eq(0).html(ponse[0] || "");
			$(".advcolumn").eq(1).html(ponse[1] || "");
			$(".advcolumn").eq(2).html(ponse[2] || "");
		}

		if ($oe.hasClass("browse-oecont--unified") && $tb.hasClass("browse-column-toolbar--open"))
		{
			$tb.removeClass("browse-column-toolbar--open");
			var done = false;
			var finalize = function() {
				if (done) return;
				done = true;
				finishTriptych();
			};
			$tb.one("transitionend", function(ev) {
				var pn = ev.originalEvent && ev.originalEvent.propertyName;
				if (pn && pn !== "max-height" && pn !== "opacity") return;
				finalize();
			});
			window.setTimeout(finalize, 520);
			return;
		}
		finishTriptych();
	}

	function triggerSearch(which, limit, allthree, page)
	{
		$("#viewselect").val(which);
		if(!$("#viewselect").val()) $("#viewselect").val("as");

		$("#oecont").hide("blind", 400);
		$("#searchloading").show();

		var params = "";
		if(!page) page = 1;
		if(which == "as")
		{
			var titleval = $("#titlesearch").val();
			params = {title: titleval};
		}
		else if(which.substring(0,7) == "a.title")
		{
			var parts = which.split(";;");
			parts[1];
			$("#titlesearch").val(parts[1]);
			var titleval = $("#titlesearch").val();
			params = {title: titleval};
		}
		$.ajax({
			type: "POST",
			url: "searchadv.php",
			data: {
				which: which,
				limit: limit,
				allthree: allthree,
				page: page,
				params: params
			}
		}).done(function(response) {
			if (!response) return true;
			if(response != 2)
			{
				var ponse = response.split("!@!@!");
				applyBrowseColumnResults(ponse);
				$("#searchloading").hide();
				$("#oecont").show("blind", 400);

				$("#oecont").off("click.browseview").on("click.browseview", ".miniflag", function(){
					var $id = $(this).attr("data-viewid");
					location.href = "#/view/"+$id;
				});
				listenToEdit();
			}
		});
	}
	triggerSearch("gs",4,1);
</script>
