
var sammy_app;
var activemenu;
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
      bgcolor: "#DDDCCE",
      direction: "up",
      effect: "blind",
      transfer: 1,
      bgcol: 3
  },
  browse : {
      show: "#browsewindow",
      id: 'browse',
      name: 'Experiments',
      load: "browse.php",
      bgtop: 0,
      bgleft: 0,
      bgcolor: "#D9D1D3",
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
            if ($("#tabswindow").length) {
            $( "#tabswindow" ).tabs({
                beforeLoad: function( event, ui ) {
                    ui.jqXHR.error(function() {
                        ui.panel.html(
                                "Couldn't load this tab. We'll try to fix this as soon as possible." );
                    });
                }
            });
            // do some funky stuff so that the visible href when hovering over the tab matches where we're going and we can set the url 
            $("#tabswindow").tabs("option","event","mousedown");
            
            $("#mystuff-experiments").attr("href", '#/mystuff/experiments');
            $("#mystuff-office").attr("href", '#/mystuff/office');
            $("#mystuff-messages").attr("href", '#/mystuff/messages');
            $("#mystuff-resources").attr("href", '#/mystuff/resources');
            $("#mystuff-degrees").attr("href", '#/mystuff/degrees');
            $("#mystuff-account").attr("href", '#/mystuff/account');
            $(".tabsa").on("mousedown", function(e){
                var loc = $(this).attr("data-loc");
                location.href = "/#/mystuff/"+loc; 
            });
            $(".tabsa").hover(function(e){
                    var loc = $(this).attr("data-loc");
                    $(this).attr("href", 'mystuff/'+loc+'.php');    
                
            }, function(){
                    var loc = $(this).attr("data-loc");
                    $(this).attr("href", '#/mystuff/'+loc);
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

        });

function loadTab(loc)
{
    if (!$("#tabswindow").length) return;
    var which = 0;

    if(loc =="experiments") which = 0;
    if(loc =="office") which = 1;
    if(loc =="messages") which = 2;
    if(loc =="resources") which = 3;
    if(loc =="degrees") which = 4;
    if(loc =="account") which = 5;
   // alert(which);
    $("#tabswindow").tabs("option", "active", which);
}

function showMenuOption(which, param)
{
    if (activemenu == which && !param) return false;
    var conf = configmenu[which];
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
