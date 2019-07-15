/**
 * Created by zzy on 2017/3/28.
 */
/*请求省市县信息*/

function ajaxAddress( cb ) {
  var arrAddress=[];
  $.ajax({
    url: '../../../../data/data.json',
    type: 'GET',
    dataType: 'json',
    async:false,
    success: function (res) {
      var arrContainer = [];
//                console.log(res);
      $.each(res['86'], function (n, value) {
        var objPro = {};
        objPro.id = n;
        objPro.value = value;
        objPro.child = [],
          arrContainer.push(objPro);
//                    console.log(res[n]);
//                    console.log(arrContainer);
//                    console.log(res[n]);
        $.each(res[n], function (n, value) {
          var objCity = {};
          objCity.id = n;
          objCity.value = value;
          objCity.child = [];
          objPro.child.push(objCity);
          $.each(res[n], function (n, value) {
            var objDistrict = {};
            objDistrict.id = n;
            objDistrict.value = value;
            objCity.child.push(objDistrict);
          })
        })
      })
//                console.log(arrContainer);
//                console.log(arrContainer);

      /*选择器函数调用开始*/
      new MultiPicker({
        input: 'new_district',//点击触发插件的input框的id
        container: 'targetContainer',//插件插入的容器id
        jsonData: arrContainer,
        success: function (arr) {
          debugger;
          var textAddress = '';
          arrAddress = arr;
          console.log(arrAddress);
          $.each(arr, function (n, value) {
            textAddress += value.value;
          })
          $("#new_district").attr("value", textAddress);
          cb&&cb(arrAddress)
        }
      });
    }
  })

}

/*layer弹层==>信息框*/
function toast(msg) {
  layer.open({
    content: msg,
    btn: '好',
    time: 1.5
  })
}


/*判断是否选择为默认*/
function isSwitch() {
  //设置默认地址-start
  var eclipse_bool=false;
  $(".eclipse").click(function(){
    var transX;
    var circle=$(".circle");
    var bg_color;
    if(eclipse_bool){
      transX=0;
      eclipse_bool=false;
      bg_color="white";
    }else{
      transX=$(this).width()-circle.width();
      eclipse_bool=true;
      bg_color="red";

    }
    circle.css("transform","translateX("+transX+"px)");
    circle.css("background",bg_color);
  });
  return eclipse_bool;

}

/*设置默认地址*/

function ajaxDefault(id,uid) {
  var address;
  var data={
    table:'user',
    'id_name':'id',
    'id_value':uid,
    field:'address_now',
    value:id
  }
  $.ajax({
    url:'../../../../api/base/changestatu.html',
    type:'GET',
    async:false,
    data:data,
    success:function (res) {
      alert(res.msg);
    },
    error:function () {
    }
  });
  return address;
}

































