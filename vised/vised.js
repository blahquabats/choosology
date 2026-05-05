
    //stage.scale({x:2,y:2});
    function loadAdv(advid)
    {   
        //alert("hey"+advid);
        $("#visedloader").show();
        layers['lines'].destroyChildren();
        layers['labels'].destroyChildren();
        layers['boxes'].destroyChildren();
        
        connto = [];
        connfrom = [];
        lines = [];
        boxes = [];
        boxgroups = [];
        //currinfo = [];
        //currinfo['adv'] = [];
        //currinfo['screens'] = [];
        skip = 0;
        modename = "normal";
        mode = modes[modename];
        menubox;
        stopmenuonce = 0;
        
        $.ajax({
            type: "POST",
            url: "ajax/loadadvstructure.php",
            data: {
                    advid: advid
                }
        }).done(function(response) {

            response = $.parseJSON(response);
            if(response[0] == "0") 
            {
                showAlert(response[1], "error");
                $("#visedload").hide();
                return false;
            }
            currinfo['adv'] = response[0];
            currinfo['screens'] = response[1];
            var begin = currinfo['adv']['begin'];
                //showAlert(begin, "error");
            var beginscreen = currinfo['screens'][begin];
            
            buildFrom(beginscreen['id']); 
            for(var i in currinfo['screens'])
            {
                if(boxgroups["box_"+i] || currinfo['screens'][i]["deleted"] == 1) continue;
                buildFrom(i);
            }
            $("#visedloader").hide();
        });    
    }
    
    function buildFrom(id)
    {
        var thisscreen = currinfo['screens'][id];
        if(thisscreen['xpos']) var x = parseInt(thisscreen['xpos']);
        else var x = 100;
        if(thisscreen['ypos']) var y = parseInt(thisscreen['ypos']);
        else var y = 100;
        var box1 = 'box_'+id;
        // check to see if this box exists yet; if not, make it
        if(!boxgroups[box1]) makeRect(box1, x, y, thisscreen['name']); 
        for (var count = 1; count <= 8; count++)
        {
            if(thisscreen['choice'+count] != "") // if there's a choice with this number
            {
                var choicescreen = thisscreen['choice'+count].split("|Q-D-|"); // split to link and id
                var linktext = choicescreen[0];
                if (currinfo['screens'][choicescreen[1]]) choicescreen = currinfo['screens'][choicescreen[1]];
                else continue;
                if(choicescreen['deleted'] == "1") continue;
                var cstext=decodeEntities(choicescreen['name']);
                var cx = choicescreen['xpos'] || x+220;
                var cy = choicescreen['ypos'] || (count*60)-60+y;
                
                var box2 = 'box_'+choicescreen['id'];
                    
                if(!boxgroups[box2]) // if you haven't already made this connection's endpoint
                {
                    makeRect(box2, cx, cy, cstext);    
                    buildFrom(choicescreen['id']);
                }
                    connto[box1][box2] = [box2, linktext];
                    connfrom[box2][box1] = [box1, linktext];
                    lineOrPath(box1,box2);
            }
        }
           layers['lines'].draw();
           layers['boxes'].draw();
        //updateConnections(box1);
    }
    
    function countConns(box)
    {
        var c = 0;
        for (var key in connto[box])
        {
            c++;
        }
        return c;
    }
    
    function nextSlot(box)
    {
        var search = [];
        search["x"] = boxgroups[box].x() + 220;
        search["y"] = boxgroups[box].y() - 150;
        var int = "";
        if (search["y"] < 10) search["y"] = 10;
        var beginsearchy = search["y"];
        //alert(search["x"]+" "+search["y"]);
        var bc = 0;
        for(var c = 0; true; c++)
        {
           // alert(bc+ " " +c+" "+search["x"]+" "+search["y"]);
            search["y"] += 50;  
            var int1 = stage.getIntersection({x: search["x"]+30,y:search["y"]+25}); // check two anchor points for a decent idea of what might be open
            if (int1.name() =="bgrect") 
            {
                var int2 = stage.getIntersection({x: search["x"]+120,y:search["y"]+25});
                if (int2.name() =="bgrect") return search; // no prob
            }
            if (c > 7)
            {
                if(bc > 2) break; // let's not go crazy
                bc++;
                c = -1;
                search["x"] += 100;
                search["y"] = beginsearchy;
            }
        }
        // otherwise give up
        search["x"] = boxgroups[box].x()+220;
        search["y"] = boxgroups[box].y();
        return search;
    }

    function makeRect(name, x, y, title)
    {
       // layers[name] = new Konva.Layer({
        //    name: name,
        //});

        x = parseInt(x);
        y = parseInt(y);

        var text = prototext.clone({
            id: name+"_text",
            text: title,
        });
       // var bgh = text.height()+10 > 34 ? text.height()+10 : 34; 
        boxes[name] = protobox.clone({
            id: name,
            name: name
            //height: bgh
            
        });
        if(!title) title = "New Screen";
        boxgroups[name] = protoboxgroup.clone({
           id: name+"_group",    
           name: name,
           x: x,
           y: y
          // height:boxes[name].height()
        });
        boxgroups[name].title = title;
/*
        boxgroups[name].dragBoundFunc(function(pos) {
            x = pos.x;
            y = pos.y
            if (x < 25) x = 25;
            if (x > 1615) x = 1615;
            if (y < 25) y = 25;
            if (y > 740) y = 740;
            return {x: x, y: y};
        });*/
        

       // boxgroups[name].on('dragstart', function() {
            //this.cache();
         //   });

            //this.clearCache();
            //layers['labels'].draw();


        
        text.cache();
        connto[name] = [];
        connfrom[name] = [];
        lines[name] = [];

        //boxes[name] = nc.getLayer(name);
        //cs[name] = nc;
        //$("#visedcontainer").append(nc);
        //nc.drawLayers();
        boxgroups[name].add(boxes[name]);
        boxgroups[name].add(text);
        layers['boxes'].add(boxgroups[name]);
        /*boxgroups[name].cache({
  x: 0,
  y: 0,
  width: 100,
  height: 200,
  drawBorder: true
}).offset({x: x, y: y});*/
        //stage.add(layers[name]);
    }
    
    function createChild(box)
    {
        resetView();
        var box1 = box.name();
        var newcoords = nextSlot(box1);
        var existing = countConns(box1);
        //alert(existing);
        if(existing==8) 
        {
            showAlert("You cannot create more than eight children of one screen!", "error");
            return false;
        }
        var newtitle = prompt("Name the new screen:");
        var newname = "new"+existing+"_"+box1;
        var box2 = newname;
        var linktext = newtitle;
        makeRect(newname, box.x(), box.y(), newtitle);    
        boxgroups[newname].opacity(.5);

        //alert(newcoords["x"]+" "+newcoords["y"]);
        var newtween = new Konva.Tween({
            node: boxgroups[newname],
            x: newcoords["x"],
            y: newcoords["y"],
            duration: .5,
            //opacity: 0,
            easing: Konva.Easings.EaseOut,
            onFinish: function(){
                connto[box1][box2] = [box2, linktext];
                connfrom[box2][box1] = [box1, linktext];
                        saveAdventure();
                //layers['lines'].draw();
                
            }
        });
        newtween.play();

        
    }
    
    function editScreen(box)
    {
        
        var es = $("#editscreenwindow");
        var id = box.id();
        var idstuff = id.split("_");
        if(idstuff[0]!="box") return false;
        
        //alert(id);
        es.css("left", box.x());
        es.css("top", box.y());
        es.show();//dClass("editscreenopen");
        
       // alert(es.parent().attr("class"));
        es.switchClass("editscreenclosed", "editscreenopen", 400, "linear", function(){
            es.load("vised/screeneditor.php?screenid="+idstuff[1]);
                 resetView();
        });
        es.animate({
            top: "0",
            left: "0"
            }, {
             duration: 400,
             easing: "linear",
             queue: false
            });
   
        /*es.effect("size",  {to: { width: 800, height: 400 }}, 1000, function(){
            es.addClass("editscreenopen");
        } );*/
        
    }
    
    function closeScreenEditor(boxid)
    {
        var es = $("#editscreenwindow");
        var bg = boxgroups["box_"+boxid];
        var by = bg.y();
        var bx = bg.x();
        $("#editorcontents").empty();  
        es.animate({
            top: by,
            left: bx
            }, {
             duration: 400,
             easing: "linear",
             queue: false
            });
        es.switchClass("editscreenopen", "editscreenclosed", 400, "linear", function(){
            $("#screenal").show();
            es.hide();
        });

        resetView();
    }
    
    function deleteBox(box)
    {
        var yn = confirm("Deleting this node will also remove all connections to other nodes! Continue?");
        if(!yn) return false;
        
        var bn = box.name();
        boxgroups[bn].deleted = 1;
        boxgroups[bn].opacity(.5);
        resetView();
        
        //updateConnections(bn);
        
        delete connfrom[bn];
        delete connto[bn];
        for(var b in connto)
        {
            if(connto[b][bn])
            {
                deleteLine(b,bn, 1);
            }
        }
        for(var b in connfrom)
        {
            if(connfrom[b][bn])
            {
                deleteLine(bn, b, 1)
                /*
                delete connfrom[b][bn];
                //if(connfrom[b].length === 0) delete connfrom[b];
                //layers['lines'].remove(lines[b][bn]);
                if(lines[bn][b]) 
                {   
                    lines[bn][b].destroy();
                    delete lines[bn][b];
                }
                updateConnections(b);*/
            }
        }


        //for(var a in lines[bn])
//        layers['lines'].remove
        delete lines[bn];
        saveAdventure();
        
        //layers['lines'].draw();
        //updateConnections(bn);

        
    }
    function switchTo(mn)
    {
        var oldmode = modename;
        modename = mn;
        if(modes[modename]) mode = modes[modename];
        else 
        {
            alert("not a mode, sorry!");
            return false;
        }
        $('html').css("cursor", mode['cursor']);
        var mb = layers['bg'].find("#topbar_"+modename);
        var omb = layers['bg'].find("#topbar_"+oldmode);
        //ct.opacity(1);
        omb.setOpacity(0);
        mb.setOpacity(1);
        
        layers['bg'].drawScene();
        
        return true;
    }
    
    function startConnect(box)
    {
        switchTo("connect_to");
        boxMouseout(box);
        closeMenu();
        stage.on("mouseup", figureConnectToClick);
    }
    function startDisconnect(box)
    {
        switchTo("disconnect_from");
        boxMouseout(box);
        closeMenu();
        stage.on("mouseup", figureDisconnectClick);
    }
    
    function figureConnectToClick(e)
    {
        var targ = e.target.id();
        //alert(targ);
        if (targ.substr(0,4) !== "box_") return false;
        // check that box isn't already in connto
        connectTo(menubox.name(), targ);
        //boxgroups[targ].on("mouseout", resetBox);
        stopmenuonce = 1;
        resetView();
    }
    
    function figureDisconnectClick(e)
    {
        var targ = e.target.id();
        //alert(targ);
        if (targ.substr(0,4) !== "box_") return false;
        // check that box isn't already in connto
        disconnectFrom(menubox.name(), targ);
        //boxgroups[targ].on("mouseout", resetBox);
        stopmenuonce = 1;
        resetView();
    }
    
    function connectTo(b1, b2)
    {
        if (b1 == b2) return false;
        if(connto[b1][b2]) 
        {
                showAlert('These screens are already connected!', "error");
                return false;
        }
        if(count(connto[b1]) >= 8) 
        {
            showAlert("This screen already has eight outgoing connections!", "error");
            return false;
        }
        
        connto[b1][b2] = [b2, boxgroups[b2].title];
        connfrom[b2][b1] = [b1, boxgroups[b2].title];
        lineOrPath(b1, b2);
        layers['lines'].draw();
        return false;
        // add to connto/connfrom
        
    }
    
    function disconnectFrom(b1, b2)
    {
        if (b1 == b2) return false;
        var c = 0;
        if(!connto[b1][b2] && !connfrom[b1][b2]) 
        {
                //showAlert("These screens aren't connected!", "warning");
                return false;
        }
        if(connto[b1][b2])
        {
            deleteLine(b1, b2);
        }
        if(connfrom[b1][b2])
        {
            deleteLine(b2,b1);
        }
        updateConnections(b1);
        updateConnections(b2);
        layers['lines'].draw();
        return true;
        // add to connto/connfrom
        
    }
    // function to do everything necessary to destroy a particular connection. optional redrawing.
    function deleteLine(b1, b2, update)
    {
        
        if (connto[b1] && connto[b1][b2]) delete connto[b1][b2];
        //if(connto[b].length === 0) delete connto[b];
        //layers['lines'].remove(lines[b][bn]);
        if (lines[b1][b2])
        {
            lines[b1][b2].destroy();
            delete lines[b1][b2];
            
        }
        if (connfrom[b2] && connfrom[b2][b1]) delete connfrom[b2][b1];
        if(!update) return true;
        updateConnections(b1);
        updateConnections(b2);
        return true;
    }
    
    
    
    function resetBox(e)
    {
        //e.target.off("mouseout", resetBox);
        resetView();
    }
    
    function openMenu(box)
    {
        if(mode.nomenu == 1) return false;
        if(stopmenuonce == 1)
        {
            stopmenuonce = 0;
            return false;
        }
        box = boxgroups[box];
        menubox = box;
        box.setDraggable(false);
        var xmid = parseInt(box.x()+box.width()/2);
        var ymid = parseInt(box.y()+box.height()/2);
        var mg = layers['menu'];//.find("#menugroup2");
        var mgt = layers['menutext'].find("#menutextgroup");
        mg.x(xmid);
        mg.y(ymid);
        mgt.x(xmid);
        mgt.y(ymid);
        layers['menu'].show().draw();
        layers['menutext'].show().draw();
        //var d = new Date();
        menuup = 3;
        //d.getMilliseconds();
        //alert(xmid);
        
    }
    
    function closeMenu()
    {
        if(menubox) menubox.setDraggable(true);
        layers['menu'].hide().draw();
        layers['menutext'].hide().draw();
       // alert(menuup);
        //menuup.setDraggable(true);
        menuup = false;
    }
    
    function resetView(tomode)
    {
        if(!tomode) tomode = "normal"
        closeMenu();
        for (var i in dirtyboxes)
        {
            boxMouseout(boxgroups[i]); 
            boxgroups[i].setDraggable(true);
        }
        dirtyboxes = [];
        switchTo(tomode);
        stage.off("mouseup");
        
    }
    
    
    function boxMouseover(bg)
    {

        if(menuup && modename !== "connect_to") return false;
        layers['labels'].clearCache();
        //bg = e.target;
        boxid = bg.name();
        dirtyboxes[boxid] = 1;
        if(boxid.substr(-5,5) == "_text") boxid = boxid.slice(0,-5);
        //$(bg).css('cursor', 'move');
        boxes[boxid].stroke(config.box_border_highlight);
        //boxes[boxid].fill(config.box_color_highlight);
        boxes[boxid].fillPatternImage(config.box_bg_highlight);
        boxgroups[boxid].draw();
        //});   
        if(modename != "normal")
        {
            return false;
        }
        $('html').css("cursor", "pointer");
        
        //c.drawLayer(name);
        if(connto[boxid])
        {

            var toname = "";
            var linename = "";
            var thisline = "";
            var tocolor, tobg;
            for (var i in connto[boxid])
            {
                toname = connto[boxid][i][0];
                var tolink = decodeEntities(strip_tags(connto[boxid][i][1]));

                thisline = lines[boxid][toname];
                thisline.stroke(config.to_color);
                //boxes[toname].stroke(config.to_color).fill(config.to_box_color);
                var center = lineCenter(thisline);
                if(connto[toname][boxid])
                {
                    center[1]-=10;
                    tocolor = config.to_from_color;
                    tobg = config.to_from_box_bg;
                }
                else
                {
                    tocolor = config.to_color;
                    tobg = config.to_box_bg;
                }
                boxes[toname].stroke(tocolor).fillPatternImage(tobg);
                var label = protolabel.clone({
                    x: center[0],
                    y: center[1],
                    name: thisline.name(),
                    opacity: 1
                });
                label.getText().setText(tolink);
                
                 label.cache();
                 layers['labels'].add(label);
                 

                
            }
        }
        if(connfrom[boxid])
        {

            var fromname = "";
            var linename = "";
            var thisline = "";
            for (var i in connfrom[boxid])
            {
                fromname = connfrom[boxid][i][0];
                var fromlink = decodeEntities(strip_tags(connfrom[boxid][i][1]));

                thisline = lines[fromname][boxid];
                thisline.stroke(config.from_color);
                //boxes[fromname].stroke(config.from_color).fill(config.from_box_color);
                var center = lineCenter(thisline);
                if(connfrom[fromname][boxid])
                {
                    center[1]+=10;
                    fromcolor = config.to_from_color;
                    frombg = config.to_from_box_bg;
                }
                else
                {
                    fromcolor = config.from_color;
                    frombg = config.from_box_bg;
                }
                boxes[fromname].stroke(fromcolor).fillPatternImage(frombg);
                //boxgroups[fromname].draw();
                
                
                var label = protolabel.clone({
                    x: center[0],
                    y: center[1],
                    name: thisline.name(),
                    opacity: 1
                });
                label.getText().setText(fromlink);
                label.getTag().fill(config.from_box_color);
                label.getTag().stroke(config.from_color);
                 label.cache();
                 layers['labels'].add(label);
            }
        }
        layers['lines'].draw()
        layers['boxes'].draw()
       // layers['labels'].moveToTop();
        layers['labels'].draw();
        
    }
    
    function boxMouseout(bg)
    {
        if(menuup!==false && modename !== "connect_to") return false;
        
        //bg = e.target;
        boxid = bg.name();
        delete dirtyboxes[boxid];
        if(boxid.substr(-5,5) == "_text") boxid = boxid.slice(0,-5);
       // alert(boxid);
        //$(bg).css('cursor', 'default');
        boxes[boxid].stroke(config.box_border);
        //boxes[boxid].fill(config.box_color);
        boxes[boxid].fillPatternImage(texPurple);
        boxgroups[boxid].draw();

            layers['labels'].destroyChildren();
            
        if(connto[boxid])
        {
            var toname = "";
            var linename = "";
            for (var i in connto[boxid])
            {
                toname = connto[boxid][i][0];
                lines[boxid][toname].stroke(config.std_color);
                //boxes[toname].stroke(config.box_border).fill(config.box_color);
                boxes[toname].stroke(config.box_border).fillPatternImage(config.box_bg);
                //boxgroups[toname].draw();
                
            }
        }
        if(connfrom[boxid])
        {
            var fromname = "";
            var linename = "";
            for (var i in connfrom[boxid])
            {
                fromname = connfrom[boxid][i][0];
                lines[fromname][boxid].stroke(config.std_color);
                //boxes[fromname].stroke(config.box_border).fill(config.box_color);
                boxes[fromname].stroke(config.box_border).fillPatternImage(config.box_bg);
                //boxgroups[fromname].draw();
            }
        }
        layers['lines'].draw();
        layers['boxes'].draw();
            layers['labels'].draw();
        if(modename == "connect_to")
        {
            return true;
        }
        $('html').css("cursor", "default");
        return true;
    }
    
    function lineCenter(thisline)
    {
       
        var lp = [];
        if(thisline.getAttr("lineorpath") == "line")
        {
            lp = thisline.points();
        }
        else
        {
            lp[0] = thisline.getAttr("xstart");
            lp[1] = thisline.getAttr("ystart");
            lp[2] = thisline.getAttr("xend");
            lp[3] = thisline.getAttr("yend");
        }
          //  centerx = parseInt((lp[0]+lp[2])/2) - 30;
          //  centery = parseInt((lp[1]+lp[3])/2) - 10;
        return findCenter(lp);
    }
    
    function findCenter(coords)
    {
        var centerx, centery;
        centerx = parseInt((coords[0]+coords[2])/2) - 30;
        centery = parseInt((coords[1]+coords[3])/2) - 10;
        return [centerx, centery];
    }

    
    function updateConnections(box1, snap)
    {
        if(!snap) snap = false;
        skip++;
        if (skip < 2) return false;
        skip = 0;
        
        if (!connto[box1] || !connfrom[box1])
        {
           return false;
        }
        for (var i in connto[box1])
        {
            //alert(i);
            lineOrPath(box1, i);
        }

        for (var i in connfrom[box1])
        {
            lineOrPath(i, box1);
        }
        layers['lines'].draw();
        layers['labels'].draw();
        
        //skip++;
        //if (skip < 10) return true;
        //skip = 0;
        //layers['lines'].draw();
        return true;
    }
    
    function lineOrPath(box1,box2,which)
    {
        
        box1 = boxgroups[box1];
        box2 = boxgroups[box2];
        //alert("lineorpath "+box1.name()+" "+box2.name());
        var b1x=parseInt(box1.getAbsolutePosition().x);
        var b2x=parseInt(box2.getAbsolutePosition().x);
        var b1w=parseInt(box1.width());
        if(b1x+b1w>=b2x+3) 
        {
            
            drawPath(box1, box2);
        }
        else
        {
            drawLine(box1, box2);
        }
    }
    
    
      function drawLine(box1,box2)
      {
        var r=[];
        r[0]=parseInt(box1.getAbsolutePosition().x)+box1.width(); // x1
        r[1]=parseInt(box1.getAbsolutePosition().y)+(box1.height()*.5); //y1
        r[2]=parseInt(box2.getAbsolutePosition().x)-1;          // x2
        r[3]=parseInt(box2.getAbsolutePosition().y)+(box2.height()*.5); // y2
        var linename = box1.name()+"to"+box2.name();
        var stroke = config.std_color;
        
        if(lines[box1.name()][box2.name()]) 
        {
            var thisl = lines[box1.name()][box2.name()];
            stroke = thisl.getAttr('stroke');
            if(thisl.getAttr('lineorpath')  == 'path') thisl.destroy();  // if it has svg data, we're switching from bezier to line; remove and draw a new one
            else
            {        
                thisl.points([r[0], r[1],r[2], r[3]]);
                    var newc = findCenter(r);
                    var tag = layers['labels'].find("."+thisl.name());
                    if(lines[box2.name()][box1.name()])
                    {
                        if(stroke == config.from_color) newc[1]+=10;     // offset tags if there are two 
                        else newc[1]-=10;  
                    }
                    tag = tag[0];
                    if(!tag) return;
                    tag.x(newc[0]);
                    tag.y(newc[1]);
                return;
            }
        }


        lines[box1.name()][box2.name()] = new Konva.Line({
            x: 0,
            y: 0,
          //  dash: [8,4],
            points: [r[0], r[1], r[2], r[3]],
            id: linename,
            name: linename,
            stroke: stroke,
            strokeWidth: 2,
            linecap: 'round',
            lineorpath: "line"
        });
        layers['lines'].add(lines[box1.name()][box2.name()]);
        
        //line.draw();
        
        //c.moveLayer(linename, 0);
        //return true;
        
      }
    
function drawPath(box1, box2)
{
    var b1 = box1;
    var b2 = box2;
    var b1x = parseInt(b1.getAbsolutePosition().x),
        b1y = parseInt(b1.getAbsolutePosition().y),
        b1w = parseInt(b1.width()),
        b1h = parseInt(b1.height()),
        b1n = b1.name(),
        b2x = parseInt(b2.getAbsolutePosition().x),
        b2y = parseInt(b2.getAbsolutePosition().y),
        b2w = parseInt(b2.width()),
        b2h = parseInt(b2.height()),
        b2n = b2.name();
    var stroke = config.std_color,
        diff=(b1x+b1w)-(b2x+3);
    var hdiff=diff*.5;
        if(hdiff>66) hdiff=66;
        //hdiff = parseInt(hdiff/2);
        if(b1y<b2y)
        {
            var newyc1 = b1y+b1h+hdiff;
            var newyc2 = b2y-hdiff;
        }
        else
        {
            var newyc1 = b1y-(hdiff);
            var newyc2 = b2y+b2h+(hdiff);
        }
    var hy = b1h*.5;
        
    var newxs = b1x+b1w,
        newys = b1y+hy,
        newxc1 = b1x+b1w+hdiff,
        newxc2 = b2x-hdiff,
        newxe = b2x-1,
        newye = b2y+hy;
        
    var newd="M"+newxs+","+newys+" C"+newxc1+","+newyc1+" "+newxc2+","+newyc2+" "+newxe+","+newye;
        
    var linename = b1n+"to"+b2n;
        //if(lines[b1n][b2n]) ;
        
        if(lines[b1n][b2n]) 
        {          
            stroke = lines[b1n][b2n].stroke();
            var thisl = lines[b1n][b2n];
            if(thisl.getAttr('lineorpath') == "line") thisl.destroy();  // if it has no control point, we're switching from line to bezier; remove and draw a new one
            else
            {
                thisl.data(newd);
                thisl.setAttr("xstart", newxs);
                thisl.setAttr("ystart", newys);
                thisl.setAttr("xend", newxe);
                thisl.setAttr("yend", newye);
                    var newc = findCenter([newxs, newys, newxe, newye]);
                    var tag = layers['labels'].find("."+thisl.name());
                    if(lines[b2n][b1n])
                    {
                        if(stroke == config.from_color) newc[1]+=10;    // offset tags if there are two 
                        else newc[1]-=10;    
                       
                    }
                    tag = tag[0];
                    if(!tag) return;
                    tag.x(newc[0]);
                    tag.y(newc[1]);
                return;
            }
        }

            
            lines[b1n][b2n] = new Konva.Path({
            x: 0,
            y: 0,
          //  dash: [8,4],
            data: newd,
            id: linename,
            name: linename,
            stroke: stroke,
            strokeWidth: 2,
            lineorpath: "path",
            xstart: newxs,
            ystart: newys,
            xend: newxe,
            yend: newye
        });
        layers['lines'].add(lines[b1n][b2n]);

}


    function saveAdventure()
    {
        $("#visedloader").show();
       // return;
        // get positions
        var temparray = {};
        var cid = 0;
        var clabel = 0;
        temparray['advid'] = advid;
        for(var i in boxgroups)
        {    
            temparray[i] = {};
            temparray[i]['connections'] = {};
            if(connto[i])
            {
                var c = 1;
                for (var j in connto[i])
                {
                    cid = j.substr(4);
                    clabel = connto[i][j][1];
                    temparray[i]['connections'][c] = clabel+"|Q-D-|"+cid;
                    c++;
                }
                
            }
            temparray[i]['name'] = boxgroups[i].name();
            temparray[i]['title'] = boxgroups[i].title;
            temparray[i]['x'] = boxgroups[i].x();
            temparray[i]['y'] = boxgroups[i].y();
            if(boxgroups[i].deleted) temparray[i]['deleted'] = boxgroups[i].deleted;
            
        }
        var jsonarray = JSON.stringify(temparray)
       // alert(jsonarray);
        $.ajax({
            type: "POST",
            url: "vised/saveadv.php",
            data: temparray,
            }).error(function(){
                showAlert("error", "error");
                $("#visedloader").hide();
            }).done(function(response){
                
                //anim.start();
                
                
                // play tween
                savetween.play();
                
                loadAdv(advid);
                
                //tween.reverse();

            //    save.fill("red");
                //save.draw();
                //alert(response);
            });
        // get nodes/connections
        // don't worry about text here?
    }


// init
    $(document).ready(function(){
        pie1.setZIndex(1);
        pie2.setZIndex(2);
        pie3.setZIndex(3);
        pie4.setZIndex(4);
        texOrange.onload = function() { // apparently we gotta do this for first load, and outside of onload for later loads
            pie1.fillPatternImage(config.menu_bg);
            pie2.fillPatternImage(config.menu_bg);
           // pie2hover.fillPatternImage(texRed);
            pie3.fillPatternImage(config.menu_bg);
            pie4.fillPatternImage(config.menu_bg);
            pieleft.fillPatternImage(config.menu_bg);
            pieright.fillPatternImage(config.menu_bg);
            $('#newswindow').css('background-image', 'url(images/tex-white.png)');
        };
        pie1.fillPatternImage(config.menu_bg);
        pie2.fillPatternImage(config.menu_bg);
        pie3.fillPatternImage(config.menu_bg);
        pie4.fillPatternImage(config.menu_bg);
        pieleft.fillPatternImage(config.menu_bg);
            pieright.fillPatternImage(config.menu_bg);
        $('#newswindow').css('background-image', 'url(images/tex-white.png)');

      // $('#newswindow').css('background-image', 'url(images/tex-teal.png)');

        /*var patt = c.createPattern({
          source: $("#heximg")[0],
          repeat: 'repeat'
         // load: drawBack
        });*/
        
    loadAdv(advid);
   // doStuff();
    // c.drawLayers();
    
    });

// c.drawBezier({
//  strokeStyle: '#000',
//  strokeWidth: 5,
 // x1: 25, y1: 50, // Start point
 // cx1: 175, cy1: 50, // Control point
 // cx2: 25, cy2: 150, // Control point
 // x2: 375, y2: 150 // Start/end point
//});
    