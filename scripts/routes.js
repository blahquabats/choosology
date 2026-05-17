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
                if(this.params['splat'] !== "")
                {
                    pendingMystuffTab = this.params['splat'];
                }
                showMenuOption("mystuff");
                if(this.params['splat'] !== "" && activemenu === "mystuff")
                {
                    loadTab(this.params['splat']);
                }
                
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
        });
      
      
        });
      
        $(function() {
          sammy_app.run('#/');
        });
              
      })(jQuery);// JavaScript Document
});