
var sammy_app;
var activemenu;
var pendingMystuffTab = null;
/* True while My Office is sliding in; its show-complete handler owns the first room load. */
var mystuffOpening = false;
/** Bumps on each menu navigation so stale hide/show/load callbacks are ignored. */
var menuNavGen = 0;
var configmenu = {
  duration: 600,
  home: {
      show: "#homewindow",
      id: 'home',
      name: 'Home',
      load: "home.php",
      bgtop: 0,
      bgleft: 0,
      bgcolor: "#d4ded4",
      direction: "up",
      bgcol: 0,
      effect: "blind",
      transfer: 1,
      percent: 50
  },
  news: {
      show: "#newswindow",
      id: 'news',
      name: 'News',
      load: "news.php",
      bgtop: 0,
      bgleft: 0,
      bgcolor: "#dce8dc",
      direction: "up",
      effect: "blind",
      transfer: 1,
      bgcol: 1
  },
  browse : {
      show: "#browsewindow",
      id: 'browse',
      name: 'Experiments',
      load: "browse.php",
      bgtop: 0,
      bgleft: 0,
      bgcolor: "#d8e0eb",
      direction: "up",
      effect: "blind",
      bgcol: 2
  },
  mystuff : {
      show: "#tabswindow",
      id: 'mystuff',
      name: 'My Office',
      load: false,
      bgtop: 0,
      bgleft: 0,
      bgcolor: "#D2DCD3",
      direction: "right",
      effect: "slide",
      bgcol: 1
  },
  viewadv : {
      show: "#viewwindow",
      id: 'viewadv',
      name: 'View Experiment',
      load: "view.php",
      bgtop: 0,
      bgleft: -125,
      bgcolor: "#D2DCD3",
      bgcol: 1,
      effect: "slide",
      direction: "down",
      darken: 1,
      hidefoot: 1,
      showback: 1
  },
  editadv : {
      show: "#editadvwindow",
      id: 'editadv',
      name: 'Edit Experiment',
      load: "vised.php",
      bgtop: 0,
      bgleft: -125,
      bgcolor: "#D2DCD3",
      bgcol: 1,
      effect: "slide",
      direction: "down",
      darken: 1,
      hidefoot: 1,
      showback: 1
    }

};

    // tabs setup
        


function initNewsComments($root) {
    $root = $root || $(document);
    $root.find(".CAcomments[data-ca-board]").each(function(){
        var board = $(this).attr("data-ca-board");
        var screen = $(this).attr("data-screen") || 0;
        if (board && typeof loadComments === "function") {
            loadComments(board, 1, screen);
        }
    });
}

$(function() {
            /* View experiment: Map → graph editor (button only exists for owner, multi-screen advs). */
            $(document).on("click", "#mapbutton", function (e) {
                e.preventDefault();
                var advid = $(this).attr("data-advid");
                if (advid) {
                    location.href = "#/edit/" + advid;
                }
            });
            if ($("#tabswindow").length) {
            $( "#tabswindow" ).tabs({
                beforeLoad: function( event, ui ) {
                    ui.jqXHR.error(function() {
                        ui.panel.html(
                                "Couldn't load this tab. We'll try to fix this as soon as possible." );
                    });
                },
                load: function( event, ui ) {
                    /* jQuery UI AJAX strips <script> from fragments — mount Office hub from shell JS. */
                    if (ui.panel && $(ui.panel).find(".ms-room-page").length && window.ChoosologyOffice) {
                        ChoosologyOffice.mount($(ui.panel).find(".ms-room-page")[0]);
                    }
                }
            });
            // jQuery UI needs real panel URLs here; routing hash is updated separately below.
            $("#tabswindow").tabs("option","event","mousedown");
            
            $("#mystuff-experiments").attr("href", 'mystuff/experiments.php');
            $("#mystuff-office").attr("href", 'mystuff/office.php');
            $("#mystuff-messages").attr("href", 'mystuff/messages.php');
            $("#mystuff-resources").attr("href", 'mystuff/resources.php');
            $("#mystuff-ledger").attr("href", 'mystuff/ledger.php');
            $("#mystuff-degrees").attr("href", 'mystuff/degrees.php');
            $("#mystuff-account").attr("href", 'mystuff/account.php');
            $("#mystuff-restricted").attr("href", 'mystuff/restricted.php');
            $(".tabsa").on("mousedown", function(e){
                var loc = $(this).attr("data-loc");
                window.location.hash = "#/mystuff/" + loc;
            });
            }
            $("#fakebackground").cycle({
                speed: 600,
                timeout: 0,
                fx: 'fadeout'
            });
            function choosologyLoginBoxHtml() {
                return "User Name: <input type='text' name='logname' id='loginuser' autocomplete='username' /><br />"
                    + "Password: <input type='password' name='logpass' id='loginpass' autocomplete='current-password' /><br />"
                    + "<div class='rememberme'><input type='checkbox' name='rememberlogin' id='rememberlogin'> <label for='rememberlogin'>Remember me?</label></div>"
                    + "<button type='button' id='loginsubmit'>Submit</button>"
                    + "<div class='login-signup-row'><button type='button' class='login-signup-link' id='opensignup'>Apply for lab access</button></div>";
            }

            function choosologyCloseSignupModal() {
                var $m = $("#signupmodal");
                if (!$m.length) {
                    return;
                }
                $m.addClass("signup-modal--hidden").attr("aria-hidden", "true");
                $("body").removeClass("signup-modal-open");
                $("#signup-status").removeClass("signup-status--error").text("");
            }

            function choosologyOpenSignupModal() {
                var $m = $("#signupmodal");
                if (!$m.length) {
                    return;
                }
                $("#signup-form")[0].reset();
                $("#signup-welcome").prop("checked", true);
                $("#signup-newsletter").prop("checked", false);
                $("#signup-nonce").val("");
                $("#signup-human-label").text("Human verification");
                $("#signup-status").removeClass("signup-status--error").text("Preparing intake form…");
                $m.removeClass("signup-modal--hidden").attr("aria-hidden", "false");
                $("body").addClass("signup-modal-open");
                $.ajax({
                    type: "GET",
                    url: "ajax/signupchallenge.php",
                    dataType: "json",
                    cache: false
                }).done(function(res) {
                    if (!res || !res.ok) {
                        $("#signup-status").addClass("signup-status--error").text((res && res.error) ? res.error : "Could not open intake form.");
                        return;
                    }
                    $("#signup-nonce").val(res.nonce || "");
                    $("#signup-human-label").text(res.prompt || "Human verification");
                    $("#signup-status").removeClass("signup-status--error").text("");
                    $("#signup-name").trigger("focus");
                }).fail(function() {
                    $("#signup-status").addClass("signup-status--error").text("Could not open intake form.");
                });
            }

            $(document).on("click", "#opensignup", function(e) {
                e.preventDefault();
                choosologyOpenSignupModal();
            });
            $(document).on("click", "#signupmodal_close, #signup-cancel, #signupmodal .signup-modal-backdrop", function() {
                choosologyCloseSignupModal();
            });
            $(document).on("keydown", function(e) {
                if (e.key === "Escape" && $("#signupmodal").length && !$("#signupmodal").hasClass("signup-modal--hidden")) {
                    choosologyCloseSignupModal();
                }
            });
            $(document).on("submit", "#signup-form", function(e) {
                e.preventDefault();
                var $submit = $("#signup-submit");
                var $status = $("#signup-status");
                var payload = {
                    name: $.trim($("#signup-name").val()),
                    email: $.trim($("#signup-email").val()),
                    pass1: $("#signup-pass1").val(),
                    pass2: $("#signup-pass2").val(),
                    human_check: $.trim($("#signup-human").val()),
                    nonce: $("#signup-nonce").val(),
                    lab_fax: $("#signup-fax").val(),
                    welcome_email: $("#signup-welcome").is(":checked") ? 1 : 0,
                    newsletter: $("#signup-newsletter").is(":checked") ? 1 : 0
                };
                $submit.prop("disabled", true);
                $status.removeClass("signup-status--error").text("Filing application…");
                $.ajax({
                    type: "POST",
                    url: "ajax/signup.php",
                    data: JSON.stringify(payload),
                    contentType: "application/json; charset=utf-8",
                    dataType: "json"
                }).done(function(res) {
                    if (!res || !res.ok) {
                        $status.addClass("signup-status--error").text((res && res.error) ? res.error : "Application rejected.");
                        $.ajax({
                            type: "GET",
                            url: "ajax/signupchallenge.php",
                            dataType: "json",
                            cache: false
                        }).done(function(ch) {
                            if (ch && ch.ok) {
                                $("#signup-nonce").val(ch.nonce || "");
                                $("#signup-human-label").text(ch.prompt || "Human verification");
                                $("#signup-human").val("");
                            }
                        });
                        return;
                    }
                    choosologyCloseSignupModal();
                    if (window.ChoosologyClipboard && typeof ChoosologyClipboard.markForceAutoOpenOnNextLoad === "function") {
                        ChoosologyClipboard.markForceAutoOpenOnNextLoad();
                    }
                    var newtext = "Logged in as " + (res.name || "researcher");
                    newtext += "<br><span id='logoutsubmit'><a href='#'>log out</a></span>";
                    var box = $("#topbox");
                    box.slideUp("slow", "swing", function() {
                        box.html(newtext);
                        box.slideDown("slow", "swing", function() {
                            location.reload();
                        });
                    });
                }).fail(function(xhr, status, err) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : (err || status || "Network error");
                    $status.addClass("signup-status--error").text(msg);
                }).always(function() {
                    $submit.prop("disabled", false);
                });
            });

            $(document).on("keypress", "#loginpass", function(e){
            		if (e.which == 13) {
            	    $('#loginsubmit').trigger("click");
            		return false;  
              }
            });
            $(document).on("click", "#loginsubmit", function(){
                var btn = this;
                btn.disabled = true;
                $.ajax({
                    type: "POST",
                    url: "ajax/authentajax.php",
                    data: {
                        loginsubmit: 1,
                        logname: $("#loginuser").val(),
                        logpass: $("#loginpass").val(),
                        rememberlogin: $("#rememberlogin").is(":checked")}
                }).done(function(response) {
            
                        response = $.trim(response);
                        if(response != 2)
                        {
                            if (window.ChoosologyClipboard && typeof ChoosologyClipboard.markForceAutoOpenOnNextLoad === "function") {
                                ChoosologyClipboard.markForceAutoOpenOnNextLoad();
                            }
                            var newtext = "Logged in as "+response;
                            newtext += "<br><span id='logoutsubmit'><a href='#'>log out</a></span>";
                            var box = $("#topbox");
                            box.slideUp("slow","swing", function(){
                                box.html(newtext);
                                box.slideDown("slow", "swing", function() {
                                    location.reload();
                                });
                            });
                            if($('#commentsexist').length) reloadScreen($('#screenid').val()); // show interactive comments/rating if you're on a last screen
                        }
                        else
                        {
                            var topbox = $("#topbox");
                            topbox.append("<span style='color:red; font-weight:bold' class='wrongpass'><br />Wrong username or password.</span><br />");
                            topbox.effect("shake", {distance: 5, times: 2});
                            $(".wrongpass").fadeOut(2000, "easeInExpo", function(){
                                this.remove();
                            });
                            $('#loginsubmit').prop('disabled', false);
                            $("#loginpass").val("").focus();
                        }
                    }).fail(function(xhr, status, err) {
                        var msg = (xhr.responseText && xhr.responseText.length < 500) ? xhr.responseText : (err || status || "Network error");
                        $("#topbox").append("<div class='login-ajax-error' style='color:#b00;font-weight:bold;margin-top:6px;'>Login failed (" + xhr.status + "): " + $("<div>").text(msg).html() + "</div>");
                        $('#loginsubmit').prop('disabled', false);
                    });
            });
            $(document).on("click", "#logoutsubmit", function(){
                $.ajax({
                    type: "POST",
                    url: "ajax/authentajax.php",
                    data: {
                        logoutsubmit: 1
                    }
                }).done(function(response) {
            
                        if(response == 1)
                        {
                            $("body").removeAttr("data-logged-in");
                            $("#mystuff_nav").hide();
                            $("#tabswindow").hide();
                            if (window.location.hash.indexOf("mystuff") !== -1) {
                                window.location.hash = "#/home";
                            }
                            var newtext = choosologyLoginBoxHtml();
                            var box = $("#topbox");
                            box.slideUp("slow","swing", function(){
                                box.html(newtext);
                                box.slideDown("slow");
                            });
                            if($('#commentsexist').length) reloadScreen($('#screenid').val()); // show interactive comments/rating if you're on a last screen
                        }
                });
            });

            function choosologyReloadUpdatesFeed(page) {
                var $mount = $("#newswindow #news-updates-mount");
                if (!$mount.length) {
                    return;
                }
                if (!page) {
                    page = parseInt($mount.find(".updates-feed").attr("data-page"), 10) || 1;
                }
                $mount.css("opacity", 0.55);
                $mount.load("news.php?fragment=updates&updates_page=" + page, function(responseText, status) {
                    $mount.css("opacity", 1);
                    if (status === "error") {
                        $mount.html("<p class='news-empty'>Could not load updates.</p>");
                    }
                });
            }

            /* News admin lives in My Office → RESTRICTED; carry the item the admin was reading. */
            $(document).on("click", ".news-admin-desk-link", function() {
                window.ChoosologyRestrictedFocus = {
                    view: "news",
                    kind: $(this).attr("data-desk-kind") || "news",
                    id: parseInt($(this).attr("data-desk-id"), 10) || 0
                };
            });

            $(document).on("click", "#newswindow .updates-pager-btn[data-updates-page]", function() {
                var page = parseInt($(this).attr("data-updates-page"), 10);
                if (!page || page < 1) {
                    return;
                }
                choosologyReloadUpdatesFeed(page);
            });

        });

function loadTab(loc, retryAfterShow)
{
    if (!$("#tabswindow").length) return;
    /* Classic hub: always load the My Office room panel; other locs open as hotspot overlays. */
    var hubLoc = loc || "office";
    var which = 1; /* mystuff/office.php */
    var tabs = $("#tabswindow");
    tabs.addClass("ms-room-hub-mode");

    if (hubLoc && hubLoc !== "office") {
        if (window.ChoosologyOffice && typeof ChoosologyOffice.setPendingHotspot === "function") {
            ChoosologyOffice.setPendingHotspot(hubLoc);
        } else {
            window.__choosologyOfficeHotspot = hubLoc;
        }
    } else {
        window.__choosologyOfficeHotspot = null;
        if (window.ChoosologyOffice && typeof ChoosologyOffice.setPendingHotspot === "function") {
            ChoosologyOffice.setPendingHotspot(null);
        }
    }

    try { tabs.tabs("refresh"); } catch (err) { /* ignore */ }
    var $officePanel = tabs.find(".ui-tabs-panel").eq(which);
    var officeLoaded = $officePanel.find(".ms-room-page").length > 0;
    if (!retryAfterShow && tabs.tabs("option", "active") === which && officeLoaded) {
        if (window.ChoosologyOffice && typeof ChoosologyOffice.mount === "function") {
            ChoosologyOffice.mount($officePanel.find(".ms-room-page")[0]);
        }
        /* "office" closes any open panel, so #/mystuff returns to the bare room. */
        if (window.ChoosologyOffice && typeof ChoosologyOffice.openHotspot === "function") {
            ChoosologyOffice.openHotspot(hubLoc);
            window.__choosologyOfficeHotspot = null;
        }
        return;
    }
    tabs.tabs("option", "active", which);
    try { tabs.tabs("load", which); } catch (err2) { /* ignore */ }

    /* Direct hash loads can select before the My Office window finishes showing; retry once after the show/refresh cycle. */
    if (!retryAfterShow) return;
    window.__choosologyLoadTabRetryToken = (window.__choosologyLoadTabRetryToken || 0) + 1;
    var retryToken = window.__choosologyLoadTabRetryToken;
    window.setTimeout(function () {
        if (retryToken !== window.__choosologyLoadTabRetryToken) return;
        if (!$("#tabswindow").length) return;
        /* A room that already loaded may have a hotspot open; reloading would close it. */
        if (tabs.find(".ui-tabs-panel").eq(which).find(".ms-room-page").length) return;
        try { tabs.tabs("refresh"); } catch (err3) { /* ignore */ }
        tabs.tabs("option", "active", which);
        try { tabs.tabs("load", which); } catch (err4) { /* ignore */ }
    }, configmenu.duration + 450);
}

function showMenuOption(which, param)
{
    if (activemenu == which && !param) return false;
    var conf = configmenu[which];
    var gen = ++menuNavGen;

    if (activemenu === "viewadv" && which !== "viewadv" && typeof exitTheaterMode === "function") {
        exitTheaterMode();
    }

    /* News: same tab, different item — swap only the article mount (Browse-style partial update). */
    if (which === "news" && activemenu === "news" && param && $("#newswindow").is(":visible") && $("#newswindow #news-article-mount").length) {
        var loadUrl = conf.load + "?" + param + "&fragment=article";
        var $mount = $("#newswindow #news-article-mount");
        var m = typeof param === "string" && param.match(/(?:^|[&?])id=(\d*)/);
        var rid = m ? m[1] : "";
        $mount.stop(true, true).hide("blind", { direction: "up" }, 220, function () {
            if (gen !== menuNavGen) return;
            $mount.load(loadUrl, function (responseText, status) {
                if (gen !== menuNavGen) return;
                if (status === "error") {
                    $mount.html("<p class='news-empty'>Could not load this item.</p>").show();
                    return;
                }
                $mount.show("blind", { direction: "down" }, 260);
                initNewsComments($mount);
                $("#newswindow .news-card").removeClass("news-card--active");
                if (rid !== "") {
                    $("#newswindow a.news-card[href='#/news/" + rid + "']").addClass("news-card--active");
                } else {
                    $("#newswindow a.news-card").first().addClass("news-card--active");
                }
            });
        });
        return false;
    }

    var toshow = $(conf.show);
    var toeffect = conf.effect ? conf.effect : "slide";
    var hideconf = null;
    mystuffOpening = which === "mystuff";

    if(activemenu)
    {
        hideconf = configmenu[activemenu];
        var tohide = $(hideconf.show);
        var hideeffect = hideconf.effect ? hideconf.effect : "slide";
            tohide.hide({
                effect: hideeffect,
                direction: hideconf.direction,
                percent: hideconf.percent,
                duration: configmenu.duration,
                easing: "easeOutCirc",
                queue: 0,
                complete: function(){
                    if (gen !== menuNavGen) return;
                    if(hideconf.id != "mystuff") 
                    {
                        tohide.empty();                
                        tohide.html("<div class='ajaxloader'></div>");
                    }
                }
            });
    
    }

    $("#"+activemenu+"_nav").addClass("navdisabled").animate({ left: "-=22", easing:"easeInCirc", queue: 0}, 400);
    $("#"+which+"_nav").removeClass("navdisabled").animate({ left: "+=22", easing:"easeInCirc", queue: 0}, 400);
    activemenu = which;


    window.setTimeout(function(){
            if (gen !== menuNavGen) return;

            toshow.show({
                effect: toeffect,
                percent: conf.percent,
                direction: conf.direction,
                duration: configmenu.duration,
                easing: "easeOutCirc",
                queue: 0,
                complete: function()
                {
                    if (gen !== menuNavGen) return;
                    if (conf.load) 
                        {
                            var url = conf.load;
                            if(param) url = url+"?"+param;
                            toshow.load(url, function () {
                                if (gen !== menuNavGen) return;
                                if (which === "news") {
                                    initNewsComments(toshow);
                                }
                            });
                        }
                    if (which === "mystuff") {
                        mystuffOpening = false;
                        try { $("#tabswindow").tabs("refresh"); } catch (err) { /* ignore */ }
                        $("#tabswindow").addClass("ms-room-hub-mode");
                        if (pendingMystuffTab) {
                            loadTab(pendingMystuffTab, true);
                            pendingMystuffTab = null;
                        } else {
                            loadTab("office", true);
                        }
                    }
                }
            });
        
        if(conf.showback) 
        {
            if (!hideconf || hideconf.name == conf.name)
            {
                var backname = "Home";
                var backid= "home";
            }
            else
            {
                var backname = hideconf.name;
                var backid= hideconf.id;   
            }
            $("#contextlink").html("Back to "+backname);
            $("#contextlink").off();
            $("#contextlink").on("click", function() {
                location.href='#/'+backid;
            });
            $("#contextlink").slideDown("slow");
        }
        else $("#contextlink").slideUp("slow")
        $("#fakebackground").cycle(parseInt(conf.bgcol, 10));

        if(conf.darken) $('#backgrounddark').fadeIn(1000);
        else $('#backgrounddark').fadeOut(1000);
        if(conf.hidefoot) $(".footer").slideUp("slow","swing");
        else $(".footer").slideDown("slow","swing");
        
        }, 400);
}
