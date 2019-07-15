/**
 * Created by zzy on 2017/4/1.
 */


/*发送ajax请求获取省市县信息*/
function ajaxAddress(cb) {
  $.ajax({
    url:'/public/data/data.json',
    type:'GET',
    dataType:'json',
    async:false,
    success:function (res) {
      /*调用选择器函数*/
      fnMultiPicker(res,'new_district','targetContainer',function (res) {
         cb&&cb(res);
      });

    },
    error:function () {
      alert("返回错误");
    }
  })
}

/*选择器函数*/
function fnMultiPicker(data,triggerId,insertId,cb) {
  var arrAddress = [];
  new MultiPicker({
    input:triggerId,
    container:insertId,
    jsonData:data,
    async:false,
    success:function (res) {
      var textArea='';
      /*返回的数据是数组,按下标分别对应省、市、区/县*/
      // console.log(res);

      /*用数组保存返回来的数据*/
      arrAddress =res;
      /*遍历返回后的数据以字符串形式保存到textArea*/
      $.each(res, function (i,n) {
        textArea+=n.name;
      })

      /*input的值设置为返回后的信息*/
      var oInput =document.getElementById(triggerId);
      $(oInput).val(textArea);
      cb&&cb(arrAddress);
    }
  })
}

function toast(msg) {
  layer.open({
    content:msg,
    skin:'msg',
    time:2
  })
}

/*设置默认地址*/

function ajaxDefault(id,uid) {
  var address;
  var data={
    'table':'user',
    'id_name':'id',
    'id_value':uid,
    'field':'address_now',
    'value':id
  }
  $.ajax({
    url:'/api/base/changeStatu',
    type:'get',
    async:false,
    data:data,
    success:function (res) {
      // console.log(res);
    },
    error:function () {
    }
  });
  return address;
}

















