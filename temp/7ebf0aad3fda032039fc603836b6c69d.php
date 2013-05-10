<?php if (!defined('WEB_ROOT')) exit();  
 /*  Template form :Home/Index
*/ ?>
<!DOCTYPE html>
<html>
    <head>
        <title><?php echo (isset($eee)?($eee):"2"); ?></title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <script type="text/javascript" src="/public/js/ajax.base.js"></script>
    </head>
    <body>

        <div>
          <?php echo(url(Home,cookie));?>
            <hr>{HTML_DIR}
            <?php echo ($eee); ?>22222<?php echo ($eee["dqw"]); ?></div>11111111111111111111111111111
        <div><?php if(is_array($array)): $name = 0; $__LIST__ = $array;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$li): $mod = ($name % 2 );++$name;?> 
            <?php echo ($name); ?>=> {$li.key}
            
            <?php endforeach; endif; else: echo "" ;endif; ?>
           <?php dump($_COOKIE);?>
             <a href="<?php echo(url(Home,$url));?>"  >cooki3333e</a>
           
            <?php dump($array);?>
        </div>
        Home
        Index
        
        <?php dump(date('Y/m/d h:i:s'));?>
        <?php if(($eee["swqs"] < 1 && $conf["dw"] ==1)): ?>
       
        1111122
        <?php elseif(($eee["swqs"] > 1)): ?>
        22
        <?php else: ?>2    
        22222
        <?php endif; ?>
        www.farmcode.com/index.php
        <script>
            function rel(state, text) {
                alert(text);
                if (state === 200) {
                    
                }
            }
            ajax.get('/index.php/sa/sad', function(state, text){
                 
            });
        </script>
        <?php if(($eee.swq2s.qw1qd < 1 && $conf["wqd"] ==1)): ?>
        <br><br>
        <?php endif; ?>
        <br>
        <?php echo (isset($eeesw)?($eeesw):'2'); ?>



        <?php echo ($_SERVER['SCRIPT_NAME']); ?>


        <br><br>

       <!-- <button onclick="http()">ddd</button>-->
    </body>

</html>