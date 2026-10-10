/**
 * Classic lab guide. Paints server-rendered HTML; the script does not know
 * which mascot is speaking.
 */
(function (window, $) {
	"use strict";

	function apiUrl() {
		if (typeof choosologyUrl === "function") {
			return choosologyUrl("ajax/guide.php");
		}
		return "ajax/guide.php";
	}

	function post(payload) {
		return $.ajax({
			type: "POST",
			url: apiUrl(),
			contentType: "application/json; charset=utf-8",
			dataType: "json",
			data: JSON.stringify(payload)
		});
	}

	function enabled() {
		return window.CHOOSOLOGY_GUIDE !== 0 && window.CHOOSOLOGY_GUIDE !== "0";
	}

	function paint(html) {
		$("#guide_panel").remove();
		if (!html) {
			return;
		}
		$("body").append(html);
	}

	function clearFeature(feature) {
		var $panel = $("#guide_panel");
		if ($panel.length && String($panel.attr("data-feature") || "") === String(feature || "")) {
			$panel.remove();
		}
	}

	function signal(feature) {
		feature = String(feature || "");
		if (!feature) {
			return;
		}
		if (!enabled()) {
			$("#guide_panel").remove();
			return;
		}
		post({ action: "signal", feature: feature, surface: "classic" }).done(function (res) {
			if (!res || !res.ok) {
				return;
			}
			if (res.offer && res.offer.html) {
				paint(res.offer.html);
				return;
			}
			clearFeature(res.feature || feature);
		});
	}

	$(document).on("click", "#guide_panel [data-guide-choice]", function () {
		var $btn = $(this);
		var $panel = $("#guide_panel");
		var choice = String($btn.attr("data-guide-choice") || "");
		var key = String($panel.attr("data-interaction") || "");
		if (!choice || !key) {
			return;
		}
		$panel.find("[data-guide-choice]").prop("disabled", true);
		post({ action: "respond", interaction_key: key, choice: choice }).done(function (res) {
			if (res && res.ok && res.offer && res.offer.html) {
				paint(res.offer.html);
				return;
			}
			$panel.remove();
		}).fail(function () {
			$panel.find("[data-guide-choice]").prop("disabled", false);
		});
	});

	window.ChoosologyGuide = {
		signal: signal,
		close: function () {
			$("#guide_panel").remove();
		}
	};
})(window, jQuery);
