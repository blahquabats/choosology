
var sammy_app;
var activemenu;
var pendingMystuffTab = null;
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
      name: 'My Stuff',
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
                }
            });
            // jQuery UI needs real panel URLs here; routing hash is updated separately below.
            $("#tabswindow").tabs("option","event","mousedown");
            
            $("#mystuff-experiments").attr("href", 'mystuff/experiments.php');
            $("#mystuff-office").attr("href", 'mystuff/office.php');
            $("#mystuff-messages").attr("href", 'mystuff/messages.php');
            $("#mystuff-resources").attr("href", 'mystuff/resources.php');
            $("#mystuff-degrees").attr("href", 'mystuff/degrees.php');
            $("#mystuff-account").attr("href", 'mystuff/account.php');
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
                            var newtext = "User Name: <input type='text' name = 'logname' id='loginuser' /><br />"
                                            + "Password: <input type='password' name = 'logpass' id = 'loginpass' /><br />"
                                            + "<div class='rememberme'><input type ='checkbox' name = 'rememberlogin' id = 'rememberlogin'> <label for='rememberlogin'>Remember me?</label></div><button id = 'loginsubmit'>Submit</button>";
                            var box = $("#topbox");
                            box.slideUp("slow","swing", function(){
                                box.html(newtext);
                                box.slideDown("slow");
                            });
                            if($('#commentsexist').length) reloadScreen($('#screenid').val()); // show interactive comments/rating if you're on a last screen
                        }
                });
            });

            $(document).on("submit", "#news-add-form", function(e) {
                e.preventDefault();
                var $form = $(this);
                var $submit = $("#news-add-submit");
                var $status = $("#news-add-status");
                var editId = $.trim($("#news-edit-id").val());
                var payload = {
                    headline: $.trim($("#news-add-headline").val()),
                    by: $.trim($("#news-add-by").val()),
                    body: $.trim($("#news-add-body").val())
                };
                var isEdit = editId !== "";
                if (isEdit) {
                    payload.action = "update";
                    payload.id = editId;
                }

                $submit.prop("disabled", true);
                $status.removeClass("news-admin-status--error").text(isEdit ? "Saving..." : "Adding...");

                $.ajax({
                    type: "POST",
                    url: isEdit ? "ajax/managenews.php" : $form.attr("action"),
                    data: JSON.stringify(payload),
                    contentType: "application/json; charset=utf-8",
                    dataType: "json"
                }).done(function(response) {
                    if (!response || !response.ok) {
                        $status.addClass("news-admin-status--error").text((response && response.error) ? response.error : "Could not add news item.");
                        return;
                    }

                    var $list = $("#newswindow .news-archive-results");
                    $list.find(".news-empty").remove();

                    if (isEdit) {
                        var $existingCard = $("#newswindow a.news-card[href='#/news/" + response.id + "']");
                        $existingCard.find(".news-card-title").text(response.headline || "Untitled");
                        $existingCard.find(".news-card-excerpt").text(response.excerpt || "");
                        $("#newswindow #news-article-mount")
                            .stop(true, true)
                            .hide("blind", { direction: "up" }, 220, function () {
                                $(this).load("news.php?id=" + response.id + "&fragment=article", function () {
                                    $(this).show("blind", { direction: "down" }, 260);
                                });
                            });
                    } else {
                        var $card = $("<a>")
                            .addClass("news-card")
                            .attr("href", "#/news/" + response.id)
                            .append($("<span>").addClass("news-card-title").text(response.headline || "Untitled"))
                            .append($("<span>").addClass("news-card-date").text(response.date || ""));
                        if (response.excerpt) {
                            $card.append($("<span>").addClass("news-card-excerpt").text(response.excerpt));
                        }
                        $list.prepend($card);
                        location.hash = "#/news/" + response.id;
                    }

                    $("#news-add-headline").val("");
                    $("#news-add-body").val("");
                    $("#news-edit-id").val("");
                    $("#news-add-submit").text("Add news item");
                    $("#news-edit-cancel").hide();
                    $status.text(isEdit ? "Saved." : "Added.");
                }).fail(function(xhr, status, err) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : (err || status || "Network error");
                    $status.addClass("news-admin-status--error").text(msg);
                }).always(function() {
                    $submit.prop("disabled", false);
                });
            });

            $(document).on("click", ".news-admin-edit-current", function() {
                var id = $(this).attr("data-id") || "";
                var $article = $(this).closest(".news-article");
                var byline = $.trim($article.find(".news-byline").text()).replace(/^By\s+/i, "");
                $("#news-edit-id").val(id);
                $("#news-add-headline").val($.trim($article.find(".news-article-title").text()));
                $("#news-add-by").val(byline || "The Grasssmith");
                $("#news-add-body").val($.trim($article.find(".news-article-body").html()));
                $("#news-add-submit").text("Save news item");
                $("#news-edit-cancel").show();
                $("#news-add-status").removeClass("news-admin-status--error").text("Editing item #" + id + ".");
                $("#news-add-headline").focus();
            });

            $(document).on("click", "#news-edit-cancel", function() {
                $("#news-edit-id").val("");
                $("#news-add-headline").val("");
                $("#news-add-body").val("");
                $("#news-add-by").val("The Grasssmith");
                $("#news-add-submit").text("Add news item");
                $("#news-edit-cancel").hide();
                $("#news-add-status").removeClass("news-admin-status--error").text("");
            });

            $(document).on("click", ".news-admin-delete-current", function() {
                var id = $(this).attr("data-id") || "";
                if (!id || !window.confirm("Delete this news item?")) {
                    return;
                }
                var $status = $("#news-add-status");
                $status.removeClass("news-admin-status--error").text("Deleting...");
                $.ajax({
                    type: "POST",
                    url: "ajax/managenews.php",
                    data: JSON.stringify({ action: "delete", id: id }),
                    contentType: "application/json; charset=utf-8",
                    dataType: "json"
                }).done(function(response) {
                    if (!response || !response.ok) {
                        $status.addClass("news-admin-status--error").text((response && response.error) ? response.error : "Could not delete news item.");
                        return;
                    }
                    $("#newswindow a.news-card[href='#/news/" + id + "']").remove();
                    $("#news-edit-cancel").trigger("click");
                    $status.text("Deleted.");
                    if (window.location.hash === "#/news" || window.location.hash === "#/news/") {
                        $("#newswindow #news-article-mount")
                            .stop(true, true)
                            .hide("blind", { direction: "up" }, 220, function () {
                                $(this).load("news.php?id=&fragment=article", function () {
                                    $(this).show("blind", { direction: "down" }, 260);
                                    $("#newswindow .news-card").removeClass("news-card--active").first().addClass("news-card--active");
                                });
                            });
                    } else {
                        location.hash = "#/news";
                    }
                }).fail(function(xhr, status, err) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : (err || status || "Network error");
                    $status.addClass("news-admin-status--error").text(msg);
                });
            });

            $(document).on("click", "#newswindow .updates-pager-btn[data-updates-page]", function() {
                var page = parseInt($(this).attr("data-updates-page"), 10);
                if (!page || page < 1) {
                    return;
                }
                var $mount = $("#newswindow #news-updates-mount");
                if (!$mount.length) {
                    return;
                }
                $mount.css("opacity", 0.55);
                $mount.load("news.php?fragment=updates&updates_page=" + page, function(responseText, status) {
                    $mount.css("opacity", 1);
                    if (status === "error") {
                        $mount.html("<p class='news-empty'>Could not load updates.</p>");
                    }
                });
            });

        });

function loadTab(loc, retryAfterShow)
{
    if (!$("#tabswindow").length) return;
    var which = 0;

    if(loc =="experiments") which = 0;
    if(loc =="office") which = 1;
    if(loc =="messages") which = 2;
    if(loc =="resources") which = 3;
    if(loc =="degrees") which = 4;
    if(loc =="account") which = 5;
    var tabs = $("#tabswindow");
    try { tabs.tabs("refresh"); } catch (err) { /* ignore */ }
    if (!retryAfterShow && tabs.tabs("option", "active") === which && tabs.find(".ui-tabs-panel").eq(which).children().length) {
        return;
    }
    tabs.tabs("option", "active", which);
    try { tabs.tabs("load", which); } catch (err2) { /* ignore */ }

    /* Direct hash loads can select before the My Stuff window finishes showing; retry once after the show/refresh cycle. */
    if (!retryAfterShow) return;
    window.__choosologyLoadTabRetryToken = (window.__choosologyLoadTabRetryToken || 0) + 1;
    var retryToken = window.__choosologyLoadTabRetryToken;
    window.setTimeout(function () {
        if (retryToken !== window.__choosologyLoadTabRetryToken) return;
        if (!$("#tabswindow").length) return;
        try { tabs.tabs("refresh"); } catch (err3) { /* ignore */ }
        tabs.tabs("option", "active", which);
        try { tabs.tabs("load", which); } catch (err4) { /* ignore */ }
    }, configmenu.duration + 450);
}

function showMenuOption(which, param)
{
    if (activemenu == which && !param) return false;
    var conf = configmenu[which];

    /* News: same tab, different item — swap only the article mount (Browse-style partial update). */
    if (which === "news" && activemenu === "news" && param && $("#newswindow").is(":visible") && $("#newswindow #news-article-mount").length) {
        var loadUrl = conf.load + "?" + param + "&fragment=article";
        var $mount = $("#newswindow #news-article-mount");
        var m = typeof param === "string" && param.match(/(?:^|[&?])id=(\d*)/);
        var rid = m ? m[1] : "";
        $mount.stop(true, true).hide("blind", { direction: "up" }, 220, function () {
            $mount.load(loadUrl, function (responseText, status) {
                if (status === "error") {
                    $mount.html("<p class='news-empty'>Could not load this item.</p>").show();
                    return;
                }
                $mount.show("blind", { direction: "down" }, 260);
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

    if(activemenu)
    {
        var hideconf = configmenu[activemenu];
        var tohide = $(hideconf.show);
        var hideeffect = hideconf.effect ? hideconf.effect : "slide";
   /* if(hideconf.transfer == "1")
        {
            tohide.effect({
                effect: "transfer",
                duration: configmenu.duration,
                easing: "easeOutCirc",
                to:  $("#"+activemenu+"_nav"),
                className: "navbutton",
                queue: 0,
                complete: function() {
                tohide.hide();
                }
            });
        }
        else 
        {*/
            tohide.hide({
                effect: hideeffect,
                direction: hideconf.direction,
                percent: hideconf.percent,
                duration: configmenu.duration,
                easing: "easeOutCirc",
                queue: 0,
                complete: function(){
                    if(hideconf.id != "mystuff") 
                    {
                        tohide.empty();                
                        tohide.html("<div class='ajaxloader'></div>");
                    }
                }
            });
        //}
     //   alert(activemenu);
    
    }

   /* $("#"+activemenu+"_nav").removeClass("navdisabled").animate({ left: "-=22", easing:"easeInCirc", queue: 0}, 400);
    $("#"+which+"_nav").addClass("navdisabled").animate({ left: "+=22", easing:"easeInCirc", queue: 0}, 400);;
    */
    $("#"+activemenu+"_nav").addClass("navdisabled").animate({ left: "-=22", easing:"easeInCirc", queue: 0}, 400);
    $("#"+which+"_nav").removeClass("navdisabled").animate({ left: "+=22", easing:"easeInCirc", queue: 0}, 400);
    activemenu = which;


    window.setTimeout(function(){
    
       /* if(conf.transfer == "1")
        {
            $("#"+which+"_nav").effect({
                effect: "transfer",
                duration: configmenu.duration,
                easing: "easeOutCirc",
                //to:  $("#"+activemenu+"_nav"),
                to:  $("#fakebackground"),
                className: "navbutton",
                queue: 0,
                complete: function() {
                     toshow.show();
                }
            });
        }
        else 
        {*/

            toshow.show({
                effect: toeffect,
                percent: conf.percent,
                direction: conf.direction,
                duration: configmenu.duration,
                easing: "easeOutCirc",
                queue: 0,
                complete: function()
                {
                    if (conf.load) 
                        {
                            var url = conf.load;
                            if(param) url = url+"?"+param;
                            toshow.load(url);
                        }
                    if (which === "mystuff") {
                        try { $("#tabswindow").tabs("refresh"); } catch (err) { /* ignore */ }
                        if (pendingMystuffTab) {
                            loadTab(pendingMystuffTab, true);
                            pendingMystuffTab = null;
                        }
                    }
                }
            });
        //}
        
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
        //$("#fakebackground").html("here is a sentence");
        /*$("body").animate({
        	backgroundColor: conf.bgcolor
        }, 600);*/

        if(conf.darken) $('#backgrounddark').fadeIn(1000);
        else $('#backgrounddark').fadeOut(1000);
        if(conf.hidefoot) $(".footer").slideUp("slow","swing");
        else $(".footer").slideDown("slow","swing");
        
        }, 400);
}
