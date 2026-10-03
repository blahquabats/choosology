/**
 * Classic → Lite suggest banner (viewport + latency). Never shown inside Lite.
 * Dismiss persists in sessionStorage for the tab; cookie tracks 7-day soft dismiss.
 */
(function () {
	if (window.__choosologyLiteSuggestInit) {
		return;
	}
	window.__choosologyLiteSuggestInit = true;

	function cookieGet(name) {
		var m = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()[\]\\/+^])/g, '\\$1') + '=([^;]*)'));
		return m ? decodeURIComponent(m[1]) : '';
	}

	function cookieSet(name, value, days) {
		var max = days ? (';Max-Age=' + (days * 86400)) : '';
		document.cookie = name + '=' + encodeURIComponent(value) + '; path=/; SameSite=Lax; ' + max;
	}

	function preferredIsLite() {
		return cookieGet('choosology_ui') === 'lite';
	}

	function dismissed() {
		try {
			if (sessionStorage.getItem('choosology_lite_suggest_dismiss') === '1') {
				return true;
			}
		} catch (e) {}
		return cookieGet('choosology_ui_suggest_dismiss') === '1';
	}

	function markDismiss() {
		try {
			sessionStorage.setItem('choosology_lite_suggest_dismiss', '1');
		} catch (e) {}
		cookieSet('choosology_ui_suggest_dismiss', '1', 7);
	}

	function smallViewport() {
		return window.matchMedia && window.matchMedia('(max-width: 720px)').matches;
	}

	function probeLatency(cb) {
		var start = (window.performance && performance.now) ? performance.now() : Date.now();
		var url = (typeof choosologyUrl === 'function' ? choosologyUrl('images/logo_horizontal_sm.png') : '/images/logo_horizontal_sm.png');
		url += (url.indexOf('?') >= 0 ? '&' : '?') + '_liteprobe=' + Date.now();
		var img = new Image();
		var done = false;
		function finish(slow) {
			if (done) return;
			done = true;
			cb(!!slow);
		}
		img.onload = function () {
			var end = (window.performance && performance.now) ? performance.now() : Date.now();
			finish((end - start) > 1200);
		};
		img.onerror = function () {
			finish(true);
		};
		setTimeout(function () { finish(true); }, 2500);
		img.src = url;
	}

	function showBanner() {
		if (document.getElementById('lite_suggest_banner')) {
			return;
		}
		var switchUrl = (typeof choosologyUrl === 'function' ? choosologyUrl('lite/switch.php?to=lite') : '/lite/switch.php?to=lite');
		var el = document.createElement('div');
		el.id = 'lite_suggest_banner';
		el.className = 'lite-suggest';
		el.setAttribute('role', 'region');
		el.setAttribute('aria-label', 'Lite mode suggestion');
		el.innerHTML =
			'<strong>Try Choosology Lite</strong> — the authentic lean terminal UI of the 1986 cohort. Better on small screens and slow links.' +
			'<div class="lite-suggest-actions">' +
			'<a href="' + switchUrl + '">Switch to Lite</a>' +
			'<a href="#" id="lite_suggest_dismiss">Dismiss</a>' +
			'</div>';
		var anchor = document.querySelector('.contentcontainer') || document.body;
		anchor.insertBefore(el, anchor.firstChild);
		var dismiss = document.getElementById('lite_suggest_dismiss');
		if (dismiss) {
			dismiss.addEventListener('click', function (e) {
				e.preventDefault();
				markDismiss();
				el.parentNode && el.parentNode.removeChild(el);
			});
		}
	}

	function maybeSuggest() {
		if (preferredIsLite() || dismissed()) {
			return;
		}
		if (smallViewport()) {
			showBanner();
			return;
		}
		probeLatency(function (slow) {
			if (slow && !dismissed() && !preferredIsLite()) {
				showBanner();
			}
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', maybeSuggest);
	} else {
		maybeSuggest();
	}
})();
