/* setup.js: Create proto-objects, menu and background assets, stage and layer configurations
    
    Konva.pixelRatio = 1;
    /* PROTO-OBJECTS */

    
        var prototext = new Konva.Text({
            fill: config.std_color,
            x: 5,
            y: 5,
            fontSize: 12,
            fontFamily: 'Arial, sans-serif',
            fontStyle: 'bold',
            width: 150,
            align: 'center',
            listening: false
        });
        var protobox = new Konva.Rect({
            stroke: config.box_border,
            strokeWidth: '2',
            fillPatternImage: config.box_bg,
            cornerRadius: 4,
            x: 0,
            y: 0,
            width: 160,
            height: 34,
            padding: 5
        });
        protobox.transformsEnabled = 'position';
        protobox.perfectDrawEnabled(false);
        protobox.on('click', function(evt){
            if(mode.nomenu == 1) return false;
            if(menuup) 
            {
                closeMenu();
                return false;
            }
            openMenu(this.name());

            evt.cancel=true;
            evt.returnValue=false;
            evt.cancelBubble=true;
            if (evt.stopPropagation) evt.stopPropagation();
            if (evt.preventDefault) evt.preventDefault();
            return false;
        });
        protobox.on("dblclick", function (e){
            editScreen(menubox);
        });
        var protoboxgroup = new Konva.Group({
           width: 160,
           height:34,
           draggable:true
           
        });
        protoboxgroup.dragDistance(3);
        protoboxgroup.transformsEnabled = 'position';
        
        protoboxgroup.on('mouseenter', function(e){
            boxMouseover(e.target);
            });
        protoboxgroup.on('mouseleave', function(e){
            boxMouseout(e.target);
            });
        protoboxgroup.on('dragmove', function(e){
            updateConnections(this.name());
        });
        protoboxgroup.on('dragend', function(e){
            skip=5;
            updateConnections(this.name());
        });
        var protolabel = new Konva.Label({
            x: -50,
            y: -50,
            name: "dummy",
            opacity: 1
        });
        protolabel.add(new Konva.Tag({
          fill: config.to_box_color,
          stroke: config.to_color,
          shadowColor: 'black',
          shadowBlur: 10,
          shadowOffset: [10, 10],
          shadowOpacity: 0.2,
          lineJoin: 'round',
          cornerRadius: 5
        }));
        protolabel.add(new Konva.Text({
          text: "dummy",
          fontSize: 10,
          lineHeight: 1.2,
          padding: 3,
          fill: 'black'
         }));
         
        var tb_protolabel = new Konva.Label({
            x: -50,
            y: -50,
            name: "dummy",
            opacity: 0
        });
        tb_protolabel.add(new Konva.Tag({
          fill: "#99ffbb",
          stroke: "#66dd99",
          cornerRadius: 5
        }));
        tb_protolabel.add(new Konva.Text({
          text: "dummy",
          fontSize: 14,
          fontStyle: "bold",
          lineHeight: 1,
          padding: 5,
          fill: config.topbar_bg
         }));
    
    /* MENU PIE */
    
function pieDraw(pie, context) 
{
      context.beginPath();
      context.lineTo(pie.attrs.w1, 0);
      context.lineTo(pie.attrs.w1-pie.attrs.w2, pie.attrs.h1-pie.attrs.h2);
      context.lineTo(0, pie.attrs.h1-pie.attrs.h2);
      context.lineTo(0, 0);
      context.closePath();
      context.fillStrokeShape(pie);
}
function pieHit(pie, context) 
{
      context.beginPath();
      context.lineTo(pie.attrs.w1, 0);
      context.lineTo(pie.attrs.w1-pie.attrs.w2, pie.attrs.h1-pie.attrs.h2);
      context.lineTo(0, pie.attrs.h1-pie.attrs.h2);
      context.lineTo(0, 0);
      context.closePath();
      context.fillStrokeShape(pie);
}
    var stage = new Konva.Stage({
        container: 'visedcontainer',
        name: "allstage",
        width: 1800,
        height: 800
    });
    
    //stage.on('dragstart', function() { this.cache(); });
    //stage.on('dragend', function() { this.clearCache(); }); 

    var c = $('#canvas');
    var layers = [];
    var menuup = false;
    
    var dirtyboxes = [];
    
    
    layers['bg'] = new Konva.Layer();
    layers['lines'] = new Konva.FastLayer({listening: false});
    
    layers['labels'] = new Konva.Layer({listening: false});
    layers['menu'] = new Konva.Layer();
    layers['menutext'] = new Konva.Layer();
    layers['boxes'] = new Konva.Layer();
    
    var bgrect = new Konva.Rect({
            id: "bgrect",
            name: "bgrect",
            stroke: "transparent",
            fill: "transparent",
            strokeWidth: 0,
            x: 0,
            y: 0,
            width: 3000,
            height: 800
            
        });
    bgrect.on('click', function(evt){
        if(menuup==false)
        {
           //alert('sup');
           evt.cancelBubble = true;
           return false; 
        }
        menuup=false;
        resetView();
        
    });
    var menugroup1 = new Konva.Group({
        id: "menugroup1",    
        name: "menugroup1",
        x: 0,
        y: 0,
        width: 200,
        height: 200
    });
    var menugroup2 = new Konva.Group({
        id: "menugroup2",    
        name: "menugroup2",
        x: 0,
        y: 0,
        width: 200,
        height: 200
    });
    var menutextgroup = new Konva.Group({
        id: "menutextgroup",    
        name: "menutextgroup",
        x: 0,
        y: 0,
        width: 200,
        height: 200
    });
    
    var menu1 = prototext.clone({
        width:110,
        x: -10,
        y:-42,
        id: "menu1_text",
        text: "Connect To..."
    });
    var menu2 = prototext.clone({
        width: 110,
        x: -10,
        y: 32,
        id: "menu2_text",
        text: "Create Child"
    });
    var menu3 = prototext.clone({
        width: 110,
        x: -110,
        y: 32,
        id: "menu3_text",
        text: "[none]"
    });
    var menu4 = prototext.clone({
        width: 110,
        x: -110,
        y: -42,
        id: "menu4_text",
        text: "Disconnect..."
    });
    
    var menuleft = prototext.clone({
        width: 30,
        x: -118,
        y: -5,
        id: "menuleft_text",
        text: "DEL"
    });
    var menuright = prototext.clone({
        width: 30,
        x: 86,
        y: -5,
        id: "menuright_text",
        text: "EDIT"
    });
    

    // Connect To...
    var pie1 = new Konva.Shape ({
    x: 2,
    y: -52,
    w1: 113,
    w2: 30,
    h1: 50,
    h2: 20,
    radius1: 60, 
    radius2: 120, 
    start: 0, 
    stop: 90,
    //scaleY: .75,
    opacity: 1,
    strokeWidth: 2,
    stroke: "#444444",
    id: "pie1",
    sceneFunc:function (context)
    {
        pieDraw(this, context);
    },
    hitFunc:function (context)
    {
        pieHit(this, context);
    }
    });

    pie1.on("mouseover", function (e){
        this.opacity(0);
        layers['menu'].draw();
    });
    
    
    pie1.on("mouseout", function (e){
        this.opacity(1);
        layers['menu'].draw();
    });
    var protopie = pie1.clone();
    var pie1hover = protopie.clone ({
        stroke: "#33aaff",
        fill: "#eeeeee",
        listening: false
    });
    pie1.on("click", function (e){
        startConnect(menubox);
    });



    // Create Child
    var pie2 = protopie.clone ({
    x: 2,
    y: 52,
    rotation:180,
    start: 90, 
    stop: 180,
    scaleX: -1,
    id: "pie2",
    sceneFunc:function (context)
    {
        pieDraw(this, context);
    },
    hitFunc:function (context)
    {
        pieHit(this, context);
    }
    });
 
    var pie2hover = pie2.clone ({
        stroke: "#33aaff",
        fill: "#eeeeee",
        listening: false
    });
    pie2.on("click", function (e){
       createChild(menubox);
    });
     

    
    // unknown (rename?)
    var pie3 = protopie.clone ({
    x: -2,
    y: 52,
    rotation: 180,
    start:180, 
    stop: 270,
    id: "pie3",
    sceneFunc:function (context)
    {
        pieDraw(this, context);
    },
    hitFunc:function (context)
    {
        pieHit(this, context);
    }
    });

    var pie3hover = pie3.clone ({
        stroke: "#33aaff",
        fill: "#eeeeee",
        listening: false
    });
    pie3.on("click", function (e){
       //
    });

    
    // Disconnect...
    var pie4 = protopie.clone ({
    x: -2,
    y: -52,
    scaleX: -1,
    start:270, 
    stop: 0,
    id: "pie4",
    sceneFunc:function (context)
    {
        pieDraw(this, context);
    },
    hitFunc:function (context)
    {
        pieHit(this, context);
    }
    });

    var pie4hover = pie4.clone ({
        stroke: "#33aaff",
        fill: "#eeeeee",
        listening: false
    });
    pie4.on("click", function (e){
         startDisconnect(menubox);
    });

    // Edit Icon
    var pieright = new Konva.Shape ({
    x: 87,
    y: -18,
    //scaleY: .75,
    opacity: 1,
    strokeWidth: 2,
    stroke: "#444444",
    id: "pieright",
   // fillPatternImage: config.menu_bg,
   //fill: "#cdcdcd",
    sceneFunc:function (context)
    {
      context.beginPath();
      context.lineTo(0, 36);
      context.lineTo(30, 66);
      context.lineTo(30, -30);
      context.lineTo(0, 0);
      context.closePath();
      context.fillStrokeShape(this);
    },
    hitFunc:function (context)
    {
      context.beginPath();
      context.lineTo(0, 36);
      context.lineTo(30, 66);
      context.lineTo(30, -30);
      context.lineTo(0, 0);
      context.closePath();
      context.fillStrokeShape(this);
    },
   // fill: '#bb44bb'
    });
    pieright.on("mouseover", function (e){
        this.opacity(0);
        layers['menu'].draw();
    });
    
    
    pieright.on("mouseout", function (e){
        this.opacity(1);
        layers['menu'].draw();
    });

    var pierighthover = pieright.clone ({
        stroke: "#33aaff",
        fill: "#eeeeee",
        listening: false
    });
    pieright.on("click", function (e){
        editScreen(menubox);
    });
    
        // Delete Icon
    var pieleft = new Konva.Shape ({
    x: -87,
    y: -18,
    //scaleY: .75,
    opacity: 1,
    strokeWidth: 2,
    stroke: "#444444",
    //fill: "#eeeeee",
    id: "pieleft",
   // fillPatternImage: config.menu_bg,
   //fill: "#cdcdcd",
    sceneFunc:function (context)
    {
      context.beginPath();
      context.lineTo(0, 36);
      context.lineTo(-30, 66);
      context.lineTo(-30, -30);
      context.lineTo(0, 0);
      context.closePath();
      context.fillStrokeShape(this);
    },
    hitFunc:function (context)
    {
      context.beginPath();
      context.lineTo(0, 36);
      context.lineTo(-30, 66);
      context.lineTo(-30, -30);
      context.lineTo(0, 0);
      context.closePath();
      context.fillStrokeShape(this);
    },
   // fill: '#bb44bb'
    });
    pieleft.on("mouseover", function (e){
        this.opacity(0);
        layers['menu'].draw();
    });
    
    pieleft.on("mouseout", function (e){
        this.opacity(1);
        layers['menu'].draw();
    });

    var pielefthover = pieleft.clone ({
        stroke: "#cc0000",
        fill: "#eeaaaa",
        listening: false
    });
    pieleft.on("click", function (e){
        deleteBox(menubox);
    });
    
    
    
    var save = new Konva.Rect({
            id: "save",
            name: "save",
            stroke: "black",
            fill: "white",
            
            strokeWidth: 2,
            x: 50,
            y: 50,
            width: 50,
            height: 20
            
        });
    save.on("click", function(e){
           saveAdventure(); 
        });
        
        var reload = new Konva.Rect({
        id: "reload",
        name: "reload",
        stroke: "black",
        fill: "white",
        strokeWidth: 2,
        x: 110,
        y: 50,
        width: 50,
        height: 20
        
        });
    reload.on("click", function(e){
           loadAdv(advid); 
        });
        
    var topbar = new Konva.Rect({
        id: "topbar",
        name: "topbar",
        stroke: config.topbar_border,
        fill: config.topbar_bg,
        strokeWidth: 2,
        x: 10,
        y: 10,
        width: 250,
        height: 30
        
    });
    
    var topbar_normal = tb_protolabel.clone({
        x: 15,
        y: 13,
        name: "topbar_normal",
        id: "topbar_normal",
        opacity: 1
        
    });
    topbar_normal.getText().setText("NOR");
    topbar_normal.getTag().fill(config.neutral_box_color);
    topbar_normal.getTag().stroke(config.neutral_color);
    
    
    var topbar_connect_to = tb_protolabel.clone({
        x: 65,
        y: 13,
        name: "topbar_connect_to",
        id: "topbar_connect_to",
        opacity: 0
        
    });
    var topbar_disconnect_from = tb_protolabel.clone({
        x: 115,
        y: 13,
        name: "topbar_disconnect_from",
        id: "topbar_disconnect_from",
        opacity: 0
    });
    topbar_connect_to.getText().setText("CON");
    topbar_connect_to.getTag().fill(config.to_box_color);
    topbar_connect_to.getTag().stroke(config.to_color);
    //topbar_connect_to.getTag().setOpacity(0);
    topbar_disconnect_from.getText().setText("DIS");
    topbar_disconnect_from.getTag().fill(config.from_box_color);
    topbar_disconnect_from.getTag().stroke(config.from_color);
    //topbar_disconnect_from.getTag().setOpacity(0);
    
   // topbar_normal.cache();
//    topbar_connect_to.cache();
 //   topbar_disconnect_from.cache();
        
    menugroup1.add(pie1hover, pie2hover, pie3hover, pie4hover, pierighthover, pielefthover);
    menugroup2.add(pie1, pie2, pie3, pie4, pieright, pieleft);
    /*pie1.moveToTop();
    pie2.moveToTop();
    pie3.moveToTop();
    pie4.moveToTop();
    pie1hover.moveToBottom();
    pie2hover.moveToBottom();
    pie3hover.moveToBottom();
    pie4hover.moveToBottom();*/
    menutextgroup.add(menu1, menu2, menu3, menu4, menuleft, menuright);


    layers['bg'].add(bgrect, save, reload, topbar, topbar_normal, topbar_connect_to, topbar_disconnect_from );

    layers['menu'].add(menugroup1);
    layers['menu'].add(menugroup2);
    layers['menutext'].add(menutextgroup);
    
    stage.add(layers['bg']);
    stage.add(layers['lines']);
    stage.add(layers['boxes']);
    stage.add(layers['labels']);
    stage.add(layers['menu']);
    stage.add(layers['menutext']);
    layers['menu'].hide();
    layers['menutext'].hide();

    /* TWEENS */
    var savetween = new Konva.Tween({
              node: save,
              fill: "green",
              duration: 0.5,
              //opacity: 0,
              easing: Konva.Easings.EaseInOut,
              onFinish: function(){
                  savetween.reverse();
              }
            });
