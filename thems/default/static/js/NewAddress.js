
//设置默认地址
var eclipse_bool=false;
var $eclipse = $(".eclipse");
  $(".eclipse").click(function(){
    let transX;
    let circle=$(".circle");
    let bg_color,bg_circle,border_circle;
    if(eclipse_bool){
      transX=0;
      eclipse_bool=false;
      bg_color="white";
    }else{
      transX=($(this).width()-3)-circle.width();
      eclipse_bool=true;
      bg_color="#DD2727";
      bg_circle='#f3f3f3';
    }
    circle.css("transform","translateX("+transX+"px)");
    circle.css('background-color',bg_circle);
    $eclipse.css("background",bg_color);
  })

