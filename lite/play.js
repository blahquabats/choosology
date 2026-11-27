/**
 * After a Lite choice is selected, lock the choices and show Loading
 * with a dot count that grows from 1 to 4 and back while the next screen loads.
 */
(function () {
    var root = document.querySelector(".lite-read");
    if (!root) return;

    root.addEventListener("click", function (e) {
        var link = e.target && e.target.closest ? e.target.closest("a.lite-read-choice") : null;
        if (!link || !root.contains(link)) return;
        if (document.body.getAttribute("data-lite-choice-loading") === "1") {
            e.preventDefault();
            return;
        }
        var list = link.closest(".lite-read-choice-list");
        if (!list) return;
        e.preventDefault();
        document.body.setAttribute("data-lite-choice-loading", "1");
        list.classList.add("is-choice-pending");
        var anchors = list.querySelectorAll("a.lite-read-choice");
        for (var i = 0; i < anchors.length; i++) {
            anchors[i].setAttribute("aria-disabled", "true");
            anchors[i].setAttribute("tabindex", "-1");
        }
        var placeholder = document.createElement("p");
        placeholder.className = "lite-loading";
        placeholder.setAttribute("role", "status");
        placeholder.setAttribute("aria-live", "polite");
        placeholder.appendChild(document.createTextNode("Loading"));
        var dots = document.createElement("span");
        dots.className = "lite-loading-dots";
        dots.setAttribute("aria-hidden", "true");
        placeholder.appendChild(dots);
        list.parentNode.insertBefore(placeholder, list);
        var href = link.href;
        window.requestAnimationFrame(function () {
            window.requestAnimationFrame(function () {
                window.location.assign(href);
            });
        });
    });
})();
