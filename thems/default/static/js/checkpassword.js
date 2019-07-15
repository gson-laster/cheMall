var $paycodeInput = $(".paycode_box input")
  function confirmTransfer(){
    var password="";
    $paycodeInput.each(function(){
      password+=$(this).val();
    });
    if (password.length!=6) {
      alert("请正确输入密码");
      return false;
    }
      $.ajax({
        type:"Post",
        url:ajaxurl,
        async:true,
        data:{password:password},
        success:function(data){
          $(".password_box input").val('');
          if (data.code==1) {
            callback && callback();
            canceltransfer();
//          transinput.val('');
          } else {
            click = 1
            layer.open({
              content: '密码验证错误'
              ,skin: 'msg'
              ,time: 2 //2秒后自动关闭
            });
          }
        },
        error:function(){
          $(".password_box input").val('');
          transinput.val('');
          click=1;
        }
      });
    }
  function passwordshow(){
    
      $(".passwordcontent").css('display','block');
      $(".onepayinput").attr('autofocus', 'autofocus')
      $(".onepayinput").focus();
    
  }
  function testlength(event,obj){
    var obj=$(obj);
    var ev=event||window.event;
    if (ev.keyCode==8) {
        obj.parent('li').prev().find('input').val('');
        obj.parent('li').prev().find('input').focus();
    }
    else if (obj.val().length>0) {
        obj.val(obj.val().substr(0,1));
        obj.parent('li').next().find('input').focus();
      }
  }
  function canceltransfer(){
    $(".passwordcontent").css('display','none');
  }
  function cancelpassword(){
    $(".passwordcontent").css('display','none');
    click = 1
  }
  //跳转到商城首页
  function tohome(){
    location.href='{:url("home/index/index")}';
  }
  // 设置哪个input显示密码;
  (function(){
  var $input = $(".paycode_box input");
    $("#pwd-input").on("input", function () {
      var pwd = $(this).val().trim();
      for (var i = 0, len = pwd.length+1; i < len; i++) {
        $input.eq("" + i + "").val(pwd[i]);
      }
      $input.each(function () {
        var index = $(this).index();
        if (index >= len) {
          $(this).val("");
        }
      });
      // 如果长度大于6,就只截取前六位;
      if(len>6){
        var newPwd =pwd.substr(0,6);
        $(this).val(newPwd)
      }
    });

  })()