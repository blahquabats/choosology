    $(document).ready(function(){

        (function($) {
      
        sammy_app = $.sammy('#fakebackground', function() {
      
        this.get('#/', function(context) {
                showMenuOption("home");
        });
        this.get(/\#(.*)\/logout/, function(context) {
                // splat is built-in regex-grabbing 
               // window.location.href="https://www.choosology.com?project_lazarus=go";
                //showMenuOption("home");
        });
        this.get('#/home', function(context) {
                showMenuOption("home");
        });
        this.get('#/about', function(context) {
                showMenuOption("about");
        });
        this.get('#/terms', function(context) {
                showMenuOption("terms");
        });
        this.get('#/privacy', function(context) {
                showMenuOption("privacy");
        });
        this.get(/#\/author\/(.*)/, function() {
                var raw = this.params['splat'];
                raw = Array.isArray(raw) ? raw[0] : raw;
                raw = String(raw || '');
                try { raw = decodeURIComponent(raw); } catch (e) { /* keep the raw segment */ }
                showMenuOption("author", "user=" + encodeURIComponent(raw));
        });
        this.get('#/try/play', function(context) {
                if ($("body").attr("data-logged-in") === "1") {
                    window.location.hash = "#/home";
                    return;
                }
                showMenuOption("tryplay", "try=1");
        });
        this.get('#/try', function(context) {
                if ($("body").attr("data-logged-in") === "1") {
                    window.location.hash = "#/home";
                    return;
                }
                showMenuOption("tryedit", "try=1");
        });
      
        this.get(/\#\/news\/?(.*)/, function(context) {
                var splat = this.params['splat'];
                if (splat === undefined || splat === null) {
                    splat = '';
                }
                showMenuOption("news", "id=" + splat);
        });
        
        this.get(/\#\/mystuff\/?(.*)/, function(context) {
                if ($("body").attr("data-logged-in") !== "1") {
                    window.location.hash = "#/home";
                    return;
                }
                var splat = this.params['splat'];
                if (splat === undefined || splat === null) {
                    splat = '';
                }
                /* Sammy hands regex captures over as an array; hotspot names are compared as strings. */
                splat = String(splat);
                /* Legacy deep link: admin tools moved out of My Office to #/admin. */
                var mystuffTab = String(splat || "").split("?")[0].split("/")[0];
                if (mystuffTab === "restricted") {
                    window.location.hash = "#/admin";
                    return;
                }
                /* Normalize empty / office / results / etc. into the Classic room hub. */
                pendingMystuffTab = splat !== '' ? splat : 'office';
                showMenuOption("mystuff");
                /* showMenuOption marks My Office active before its window has slid in.
                   Loading here as well made the show-complete handler reload the plain
                   room a second later, closing the hotspot this route asked for. */
                if (activemenu === "mystuff" && !mystuffOpening) {
                    loadTab(pendingMystuffTab);
                    pendingMystuffTab = null;
                }
                if (mystuffTab === "ledger" && window.ChoosologyGuide) {
                    window.ChoosologyGuide.signal("ledger");
                }
        });
        this.get('#/admin', function(context) {
                if ($("body").attr("data-logged-in") !== "1") {
                    window.location.hash = "#/home";
                    return;
                }
                if (!$("#adminwindow").length || !$("#admin_nav").length) {
                    window.location.hash = "#/home";
                    return;
                }
                showMenuOption("admin");
        });
        this.get('#/search', function(context) {
                showMenuOption("browse");
        });
        this.get('#/browse', function(context) {
                showMenuOption("browse");
        });
        this.get('#/viewadv/:id', function(context) {
            $('#backgrounddark').fadeIn(1000);
                showMenuOption("viewadv", "id="+this.params['id']);
        });
        this.get('#/view/:id', function(context) {
            $('#backgrounddark').fadeIn(1000);
                showMenuOption("viewadv", "id="+this.params['id']);
        });
        this.get('#/edit/:id', function(context) {
                showMenuOption("editadv", "id="+this.params['id']);
                if (window.ChoosologyGuide) {
                    window.ChoosologyGuide.signal("graph_editor");
                }
        });
      
      
        });
      
        $(function() {
          sammy_app.run('#/');
        });
              
      })(jQuery);// JavaScript Document
});