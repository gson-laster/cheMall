/**
 *  工具类,单例模式，
 */

var util = (function() {
    "use strict";
    //base64操作
    var Base64 = function() {
        var _keyStr = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
        // 加密算法
        this.encode = function(input) {
                var output = "";
                var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
                var i = 0;
                input = _utf8_encode(input);
                while (i < input.length) {
                    chr1 = input.charCodeAt(i++);
                    chr2 = input.charCodeAt(i++);
                    chr3 = input.charCodeAt(i++);
                    enc1 = chr1 >> 2;
                    enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
                    enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
                    enc4 = chr3 & 63;
                    if (isNaN(chr2)) {
                        enc3 = enc4 = 64;
                    } else if (isNaN(chr3)) {
                        enc4 = 64;
                    }
                    output = output +
                        _keyStr.charAt(enc1) + _keyStr.charAt(enc2) +
                        _keyStr.charAt(enc3) + _keyStr.charAt(enc4);
                }
                return output;
            }
            // 解密算法
        this.decode = function(input) {
            var output = "";
            var chr1, chr2, chr3;
            var enc1, enc2, enc3, enc4;
            var i = 0;
            input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");
            while (i < input.length) {
                enc1 = _keyStr.indexOf(input.charAt(i++));
                enc2 = _keyStr.indexOf(input.charAt(i++));
                enc3 = _keyStr.indexOf(input.charAt(i++));
                enc4 = _keyStr.indexOf(input.charAt(i++));
                chr1 = (enc1 << 2) | (enc2 >> 4);
                chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
                chr3 = ((enc3 & 3) << 6) | enc4;
                output = output + String.fromCharCode(chr1);
                if (enc3 != 64) {
                    output = output + String.fromCharCode(chr2);
                }
                if (enc4 != 64) {
                    output = output + String.fromCharCode(chr3);
                }
            }
            output = _utf8_decode(output);
            return output;
        }

        // 转成utf-8
        var _utf8_encode = function(string) {
            string = string.replace(/\r\n/g, "\n");
            var utftext = "";
            for (var n = 0; n < string.length; n++) {
                var c = string.charCodeAt(n);
                if (c < 128) {
                    utftext += String.fromCharCode(c);
                } else if ((c > 127) && (c < 2048)) {
                    utftext += String.fromCharCode((c >> 6) | 192);
                    utftext += String.fromCharCode((c & 63) | 128);
                } else {
                    utftext += String.fromCharCode((c >> 12) | 224);
                    utftext += String.fromCharCode(((c >> 6) & 63) | 128);
                    utftext += String.fromCharCode((c & 63) | 128);
                }

            }
            return utftext;
        }

        //解密uft-8字符
        var _utf8_decode = function(utftext) {
            var string = "";
            var i = 0;
            var c = 0,
                c1 = 0,
                c2 = 0,
                c3;
            while (i < utftext.length) {
                c = utftext.charCodeAt(i);
                if (c < 128) {
                    string += String.fromCharCode(c);
                    i++;
                } else if ((c > 191) && (c < 224)) {
                    c2 = utftext.charCodeAt(i + 1);
                    string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
                    i += 2;
                } else {
                    c2 = utftext.charCodeAt(i + 1);
                    c3 = utftext.charCodeAt(i + 2);
                    string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
                    i += 3;
                }
            }
            return string;
        }
    }

    var prefix = "www.wvmp360.com"
    var base64 = new Base64();
    //存数据
    var storeSet = function(key, value, pre) {
            prefix = pre ? pre : prefix;
            return window.localStorage.setItem(prefix + key, base64.encode(value));
        }
        //取数据
    var storeGet = function(key, pre) {
            prefix = pre ? pre : prefix;
            return window.localStorage.getItem(prefix + key) ? base64.decode(window.localStorage.getItem(prefix + key)) : false;
        }
        //存session
    var sessionSet = function(key, value, pre) {
            prefix = pre ? pre : prefix;
            return window.SessionStorage.setItem(prefix + key, base64.encode(value));
        }
        //取session
    var sessionGet = function(key, pre) {
        prefix = pre ? pre : prefix;
        return window.SessionStorage.getItem(prefix + key) ? base64.decode(window.localStorage.getItem(prefix + key)) : false;
    }

    //获得coolie 的值
    var cookieGet = function(name) {
            var _cookieStr = document.cookie;

            var cookieArray = _cookieStr.split("; "); //得到分割的cookie名值对
            for (var i = 0; i < cookieArray.length; i++) {
                var arr = cookieArray[i].split("="); //将名和值分开
                if (arr[0] == name) return decodeURIComponent(arr[1]); //如果是指定的cookie，则返回它的值
            }
            return "";
        }
        //设置cookie
    var cookieSet = function(cookiename, cookievalue, hours) {
            var date = new Date();
            date.setTime(date.getTime() + Number(hours) * 3600 * 1000);
            document.cookie = cookiename + "=" + cookievalue + "; path=/;expires = " + date.toGMTString();
        }
        //删除cookie
    var cookieDel = function(name) {
        var date = new Date();
        date.setTime(date.getTime() - 10000);
        document.cookie = name + "=;expires=" + (new Date(0)).toGMTString();
    }

    /*1.用正则表达式实现html转码*/
     function htmlEncodeByRegExp(str) {
        var s = "";
        if (str.length == 0) return "";
        s = str.replace(/&/g, "&amp;");
        s = s.replace(/</g, "&lt;");
        s = s.replace(/>/g, "&gt;");
        //s = s.replace(/ /g,"&nbsp;");
        s = s.replace(/\'/g, "&#39;");
        s = s.replace(/\"/g, "&quot;");
        return s;
    }
    /*2.用正则表达式实现html解码*/
     function htmlDecodeByRegExp (str) {
        var s = "";
        if (str.length == 0) return "";
        s = str.replace(/&amp;/g, "&");
        s = s.replace(/&lt;/g, "<");
        s = s.replace(/&gt;/g, ">");
        s = s.replace(/&nbsp;/g, " ");
        s = s.replace(/&#39;/g, "\'");
        s = s.replace(/&quot;/g, "\"");
        return s;
    }

    //为定义全局ajax对象
    var ajax = {},
        methods = [{
            name: 'html',
            method: 'get',
            async: true,
            dataType: 'html'
        }, {
            name: 'get',
            method: 'get',
            async: true,
            dataType: 'json'
        }, {
            name: 'post',
            method: 'post',
            async: true,
            dataType: 'json'
        }, {
            name: 'syncGet',
            method: 'get',
            async: false,
            dataType: 'json'
        }, {
            name: 'syncPost',
            method: 'post',
            async: false,
            dataType: 'json'
        }];

    //重新封装多个函数方便调用
    for (var i = 0, l = methods.length; i < l; i++) {
        ajax[methods[i].name] = (function(i) {
            return function() {
                /**
                 * 每个实例方法接收三个参数
                 * 第一个表示要请求的地址
                 * 第二个表示要提交到后台的数据，是一个object对象，如{param1: 'value1'}
                 * 第三个表示后台返回的数据类型，最最常用的就是html or json，绝大部分情况下这个参数不用传，会使用methods里面定义的dataType
                 */
                var _url = arguments[0],
                    _data = arguments[1],
                    _dataType = arguments[2] || methods[i].dataType;

                return create(_url, methods[i].method, _data, methods[i].async, _dataType);
            }
        })(i);
    }

    //根据关键的几个参数统一创建ajax对象
    function create(_url, _method, _data, _async, _dataType) {
        //添加随机数
        if (_url.indexOf('?') > -1) {
            _url = _url + '&rnd=' + Math.random();
        } else {
            _url = _url + '?rnd=' + Math.random();
        }

        //为请求添加ajax标识，方便后台区分ajax和非ajax请求
        _url += '&_ajax=1';

        //.done .fail .always回调
        return $.ajax({
            url: _url,
            dataType: _dataType,
            async: _async,
            type: _method,
            data: _data
        });
    }

    /**
     * [ajaxSubmitForm 采用ajax提交表单 基于zepto]
     * @Author   Rukic
     * @DateTime 2016-12-19T11:25:17+0800
     * @param    {[string]}                 form      [表单]
     * @param    {[fn]}                 successFn [成功回调]
     * @param    {[fn]}                 faileFn   [失败回调]
     */
    function ajaxSubmitForm(form, successFn, faileFn) {
        var form = typeof form === 'object' ? form : $(form);
        var method = form.attr('method') ? form.attr('method').toLowerCase() : "post";
        var url = form.attr('action');

        var data = {};
        var inputs = form.find('input');
        var textArea = from.find("textarea");
        for (var i = 0; i < inputs.length; i++) {
            data[inputs[i].attr('name')] = inputs[i].val();
        }
        for (var i = 0; i < textArea.length; i++) {
            data[textArea[i].attr('name')] = textArea[i].val();
        }
        ajax[method](url, data).done(successFn).fail(faileFn);
        form.submit(false);
    }

    /**
     * [dateFormat 日期格式化]
     * @Author   Rukic
     * @DateTime 2016-11-25T09:41:41+0800
     * @param    {[type]}                 format    [时间格式 比如yyyy:MM:dd hh:mm:ss y代表年 M代表月 d代表日 h代表时 m代表分 s代表秒 S代表毫秒]
     * @param    {[type]}                 timestamp [description]
     * @return   {[type]}                           [description]
     */
    function dateFormat(format, timestamp) {
        var dateTime = timestamp ? new Date(timestamp) : new Date(),
            fullYears = dateTime.getFullYear(),
            month = dateTime.getMonth() + 1,
            day = dateTime.getDate(),
            hours = dateTime.getHours(),
            minute = dateTime.getMinutes(),
            seconds = dateTime.getSeconds();

        //根据格式返回

        var o = {
            "M+": month, //月份
            "d+": day, //日
            "h+": hours, //小时
            "m+": minute, //分
            "s+": seconds, //秒
            "q+": Math.floor((month + 3) / 3), //季度
            "S": dateTime.getMilliseconds() //毫秒
        };
        if (/(y+)/.test(format)) format = format.replace(RegExp.$1, (fullYears + "").substr(4 - RegExp.$1.length));
        for (var k in o)
            if (new RegExp("(" + k + ")").test(format)) format = format.replace(RegExp.$1, (RegExp.$1.length == 1) ? (o[k]) : (("00" + o[k]).substr(("" + o[k]).length)));
        return format;
    }
    //判断null
    function isNull(arg1) {
        return !arg1 && arg1 !== 0 && typeof arg1 !== "boolean" ? true : false;
    }

    /**
     * [getUrlParms 获取地址参数]
     * @Author   Rukic
     * @DateTime 2016-11-25T09:40:36+0800
     * @param    {[type]}                 key       [需要获取的key]
     * @param    {[type]}                 urlString [可选，从一条地址中去除某个key的值]
     * @return   {[type]}                           [string]
     */
    function getUrlParms(key, urlString) {
        var url = urlString != undefined ? urlString : window.location.href;
        var paraString = url.substring(url.indexOf("?") + 1, url.length).split("&");
        var paraObj = {};
        for (var i = 0; i < paraString.length; i++) {
            var _stmp = paraString[i].split('=');
            paraObj[_stmp[0].toLowerCase()] = _stmp[1];
        }
        var returnValue = paraObj[key.toLowerCase()];
        if (typeof(returnValue) == "undefined") {
            return '';
        } else {
            return returnValue;
        }
    }
    //判断null
    function isNull(arg1) {
        return !arg1 && arg1 !== 0 && typeof arg1 !== "boolean" ? true : false;
    }

    //生出uuid
    function getUuid() {
        var len = 32; //32长度
        var radix = 16; //16进制
        var chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'.split('');
        var uuid = [],
            i;
        radix = radix || chars.length;
        if (len) {
            for (i = 0; i < len; i++) uuid[i] = chars[0 | Math.random() * radix];
        } else {
            var r;
            uuid[8] = uuid[13] = uuid[18] = uuid[23] = '-';
            uuid[14] = '4';
            for (i = 0; i < 36; i++) {
                if (!uuid[i]) {
                    r = 0 | Math.random() * 16;
                    uuid[i] = chars[(i == 19) ? (r & 0x3) | 0x8 : r];
                }
            }
        }
        return uuid.join('');
    }

    /**
     * [setDataToCache 获取并缓存数据]
     * @Author   Rukic
     * @DateTime 2016-11-27T10:11:57+0800
     * @param    {[string]}                 key     [用于取出的key]
     * @param    {[number]}                 deyTime [过期时间,单位秒 为0为永久缓存]
     * @param    {[object]}                 data    [数据]
     * @param    {[string]}                 url     [用于过期后请求数据的地址]
     * @param    {[string]}                 getType [请求类型 get or post]
     * @param    {[object]}                 parms   [请求数据带的参数]
     * @param    {[function]}               callback[执行成功的回调，参数是data]
     */
    function setDataToCache(key, deyTime, data, url, getType, parms, callback) {
        //判断时间是否是number
        if (isNaN(deyTime) * 1) return;
        //data如果是一个json对象的话，进行转化
        if (data instanceof String) {
            data = JSON.parse(data);
        }
        //parms如果是一个json对象的话，进行转化
        if (parms instanceof String) {
            parms = JSON.parse(parms);
        }

        //重新组装存储数据
        var toLocal = {
            deyTimer: new Date() * 1 + deyTime * 1000,
            deyTime: deyTime,
            data: data,
            url: url,
            getType: getType,
            parms: parms
        }
        console.log(toLocal);
        //调用localstroage设置
        storeSet(key, JSON.stringify(toLocal));
    }

    /**
     * [getDataCache 获取缓存]
     * @Author   Rukic
     * @DateTime 2016-11-27T11:08:27+0800
     * @param    {[type]}                 key      [保存的key]
     * @param    {Function}               callback [回调执行,没有返回false,成功返回data，过期返回接口response，建议过期返回首先设置缓存]
     * @return   {[type]}                          [description]
     */
    function getDataCache(key, callback) {
        var localData = JSON.parse(storeGet(key));
        if (!localData) { callback && callback(false) }
        var deyTime = localData.deyTime,
            deyTimer = localData.deyTimer,
            data = localData.data,
            url = localData.url,
            getType = localData.getType,
            parms = localData.parms;
        if (deyTime === 0) {
            callback && callback(data);
        } else if (new Date() * 1 - deyTimer > 0) {
            //过期
            ajax[getType.toLowerCase()](url, parms).done(function(response) {
                //请求成功的回调
                callback && callback(response);
            }).fail(function(err) {
                callback && callback(err);
            });
        } else {
            callback && callback(data);
        }
    }

    /**
     * [wxShare 微信分享]
     * @Author   Rukic
     * @DateTime 2016-11-28T09:16:26+0800
     * @param    {[object]}                 options [{title，不传则去title，link，不传则请取url，desc，不传则取description，wxImg,不传则取localstroage得logo}]
     * @return   {[type]}                         [description]
     */
    function wxShare(options, successFn, failFn) {
        if (!(typeof options === 'object') || !wx) return;
        var ua = navigator.userAgent.toLowerCase();

        if (!ua.match(/MicroMessenger/i) == 'micromessenger') return;
        var url = location.href.split('#')[0];

        /*var imgObj = new Image();
        imgObj.src = options.img || storeGet('dreamLogo');
        imgObj.style.display == "none";
        imgObj.setAttribute('id', 'wxShare-img');
        $(imgObj).hide().appendTo('body');*/

        var title = options.title || $("title").html();
        var link = options.link || url;
        var desc = options.desc || $("meta[name='description']").attr('content');
        var wxImg = document.getElementById('wxShare-img').src;

        //分享到朋友圈
        wx.onMenuShareTimeline({
            title: title, // 分享标题
            link: link, // 分享链接
            imgUrl: wxImg, // 分享图标
            success: function() {
                // 用户确认分享后执行的回调函数
                successFn.call(null);
            },
            cancel: function() {
                // 用户取消分享后执行的回调函数
                failFn.call(null);
            }
        });

        //分享给朋友
        wx.onMenuShareAppMessage({
            title: title, // 分享标题
            desc: desc, // 分享描述
            link: link, // 分享链接
            imgUrl: wxImg, // 分享图标
            type: '', // 分享类型,music、video或link，不填默认为link
            dataUrl: '', // 如果type是music或video，则要提供数据链接，默认为空
            success: function() {
                // 用户确认分享后执行的回调函数
                successFn.call(null);
            },
            cancel: function() {
                // 用户取消分享后执行的回调函数
                failFn.call(null);
            }
        });

        //分享给qq
        wx.onMenuShareQQ({
            title: title, // 分享标题
            desc: desc, // 分享描述
            link: link, // 分享链接
            imgUrl: wxImg, // 分享图标
            success: function() {
                // 用户确认分享后执行的回调函数
                successFn.call(null);
            },
            cancel: function() {
                // 用户取消分享后执行的回调函数
                failFn.call(null);
            }
        });
        //分享到qq空间
        wx.onMenuShareQZone({
            title: title, // 分享标题
            desc: desc, // 分享描述
            link: link, // 分享链接
            imgUrl: wxImg, // 分享图标
            success: function() {
                // 用户确认分享后执行的回调函数
                successFn.call(null);
            },
            cancel: function() {
                // 用户取消分享后执行的回调函数
                failFn.call(null);
            }
        });
    }
    /**
     * [wxChooseImg 选取图片]
     * @Author   Rukic
     * @DateTime 2016-11-28T09:38:03+0800
     * @param    {[type]}                 num      [选取的图片张数]
     * @param    {Function}               callback [成功之后的返回，参数为选取的localId的集合]
     * @return   {[type]}                          [description]
     */
    function wxChooseImg(num, callback) {
        if (!wx) return;
        wx.chooseImage({
            count: num, // 默认9
            sizeType: ['original', 'compressed'], // 可以指定是原图还是压缩图，默认二者都有
            sourceType: ['album', 'camera'], // 可以指定来源是相册还是相机，默认二者都有
            success: function(res) {
                var localIds = res.localIds; // 返回选定照片的本地ID列表，localId可以作为img标签的src属性显示图片
                callback && callback(localIds);
            }
        });
    }
    /**
     * [wxPreviewImg 微信图片预览]
     * @Author   Rukic
     * @DateTime 2016-11-28T09:40:39+0800
     * @param    {[string]}                 currentSrc [当前显示的图片链接]
     * @param    {[array]}                  imgs       [需要预览的图片集合]
     * @return   {[type]}                            [description]
     */
    function wxPreviewImg(currentSrc, imgs) {
        wx.previewImage({
            current: current, // 当前显示图片的http链接
            urls: imgs // 需要预览的图片http链接列表
        });
    }

    /**
     * [wxUploadImg 微信上传图片接口]
     * @Author   Rukic
     * @DateTime 2016-11-28T09:42:47+0800
     * @param    {[array]}                 localIds [本地选取的图片id]
     * @param    {Function}                callback [上传成功后的回调，参数是成功的serverId]
     * @return   {[type]}                          [description]
     */
    function wxUploadImg(localIds, callback) {
        wx.uploadImage({
            localId: localIds, // 需要上传的图片的本地ID，由chooseImage接口获得
            isShowProgressTips: 1, // 默认为1，显示进度提示
            success: function(res) {
                var serverId = res.serverId; // 返回图片的服务器端ID
                callback && callback(serverId);
            }
        });
    }

    function jsonToUrlStr(json){
      var str = '';
      for (var k in json) {
        str += k +'='+json[k] + '&';
      }

      return str.substring(0, str.length-1);
    }
    //暴露接口供外部使用
    return {
        storeSet: storeSet, //localstroage 设置
        storeGet: storeGet, //localstroage 得到
        sessionSet: sessionSet, //session 设置
        sessionGet: sessionGet, //session 得到
        cookieSet: cookieSet, //cookie设置
        cookieGet: cookieGet, //cookie 得到
        base64Encode: base64.encode, //base64 加密
        base64Decode: base64.decode, //base64 解密
        ajaxPost: ajax.post, //ajax post 方法
        ajaxGet: ajax.get, //ajax get方法
        ajaxSyncGet: ajax.syncGet, //ajax 非异步get加载方法
        ajaxSyncPost: ajax.syncPost, //ajax  非异步post
        dateFormat: dateFormat, //时间格式
        getUrlParms:getUrlParms,
        getuuid: getUuid, //生成uuid
        setCache: setDataToCache, //生成缓存
        getCahe: getDataCache, //获取缓存
        wxShare: wxShare, //微信分享
        wxChooseImg: wxChooseImg, //微信选择图片
        wxPreviewImg: wxPreviewImg, //微信预览
        wxUploadImg: wxUploadImg, //微信上传接口
        isNull: isNull,
        htmlEncodeByRegExp:htmlEncodeByRegExp,
        htmlDecodeByRegExp:htmlDecodeByRegExp,
        jsonToUrlStr: jsonToUrlStr
    }
})();
/**
 * [adminAjax fumanx ajax基类]
 * @param    {[type]}                 type   [description]
 * @param    {[type]}                 data   [description]
 * @param    {[type]}                 url    [description]
 * @param    {[type]}                 succFn [description]
 * @param    {[type]}                 errFn  [description]
 * @return   [type]                   [description]
 * @Author:  slade
 * @DateTime :2017-03-23T11:54:40+080
 */
function adminAjax(type, url, data, load, succFn, errFn) {
  util['ajax'+type](url, data).done(function (res) {
    if(res.code > 0){
      layer.msg(res.msg)
      succFn&&succFn(res)
      if(load){
        setTimeout(()=>window.location.href = res.url, 2000)
      }
    }else{
      layer.msg(res.msg)
    }
  }).fail(function (err) {
    errFn&&errFn(err)
    layer.msg(err.status + '服务器发生错误');
  })
}

/**
 * [deletesAll 删除全部]
 * @param    {[type]}                 obj [description]
 * @return   [type]                   [description]
 * @Author:  slade
 * @DateTime :2017-03-23T15:15:49+080
 */
function deletesAll(obj){
  var idsEle = $("input[name='ids[]']");
  var ids = [];
  console.log(idsEle)
  idsEle.each(function (index, ele) {
    if($(this).prop('checked')){
      ids.push($(this).val())
    }
  })
  adminAjax('Get', $(obj).data('action'), util.jsonToUrlStr({ids: ids.join(',')}), true)
}
/**
 * [删除某个值]
 * @param    {[type]}                 item [description]
 * @return   [type]                   [description]
 * @Author:  slade
 * @DateTime :2017-03-28T20:42:28+080
 */
Array.prototype.remove= function (item) {
      if(arguments.length == 0) return [];
      var len = this.length;
      for (var i = len-1; i >= 0; i--) {
          if (this[i] === item) {
                this.splice(i, 1);
          }
      }
     return this;
}
/**
 * 数组是否包含某个值
 * @param    {[值]}                 val [description]
 * @param    {[从下标]}                 idx [description]
 * @return   [type]                   [description]
 * @Author:  slade
 * @DateTime :2017-04-10T11:34:02+080
 */
Array.prototype.inArray = function (val, idx) {
      var arrLen = this.length;
      idx = idx || 0;
      idx = (idx < 0 || idx > this.length - 1) ? 0 : idx;
      for (; idx < arrLen; idx++) {
      if (this[idx] === val) {
          return idx;
      }
  }
return -1;
}
function msg(msg, time){
  var time = time || 2;
  layer.open({
    content: msg,
    time: time,
    skin: 'msg',
    type: 1
  })
}
function uploadFiles (formData,option, succFn, failFn) {
  var xhr = new XMLHttpRequest();
  xhr.open("post", option.url, true);

  //设置请求超时
  xhr.upload.timeout = 2000;
  xhr.upload.ontimeout = function (event){
    msg('请求超时');
  };
  // //添加progress事件
  // xhr.upload.addEventListener("progress",function(e){
  //     $(".progress-bar").addClass("active");
  //     var howMuch = e.loaded / e.total;
  //     var p = parseFloat((howMuch * 100).toFixed(2))+"%";
  //     $(".progress-bar").css("width",p).html(p);
  // }, false);
  //上传完成
  xhr.upload.addEventListener("load", function(event){
      msg('上传完成');

  }, false);
  xhr.upload.addEventListener("error", function(event){
    // failFn&&failFn(event);
  }, false);
  xhr.upload.addEventListener("abort", function(event){
      // failFn&&failFn(event);
    }, false);
  xhr.onreadystatechange = function (event) {
    if(xhr.readyState == 4 ){
      var res = JSON.parse(event.target.responseText);
      succFn&&succFn(res)
    }else{
      failFn&&failFn(xhr);
    }
  }
  xhr.send(formData);
}
/**
 * 需要验证登录的ajax
 * @param    {[string]}                 type   [请求的类型    Get|Post]
 * @param    {[string]}                 url    [请求的url]
 * @param    {[json]}                   data   [post的数据]
 * @param    {[fn]}                     fnSucc [成功的回调]
 * @param    {[fn]}                     failFn [失败的回调]
 * @return   Boolean                    [description]
 * @Author:  slade
 * @DateTime :2017-04-10T17:58:14+080
 */
function isLoginAjax(url, data, fnSucc, failFn){
  util.ajaxPost('/api/base/islogin',{}).done(function (res) {
    if(res.code == 1002){
      util.ajaxPost(url, data).done(function (response) {
        fnSucc(response)
      }).fail(function (err) {
        failFn(err)
      });
    }else{
      layer.open({
        content: '还没有登录',
        time: 2,
        skin: 'msg'
      });
      setTimeout(()=>window.location.href = res.url, 2000)
    }
  })
}
//alert
window.alert = function (txt, time, url) {
  var _url = url || false;
  var _time = time || 2;
  layer.open({
    content: txt,
    time: _time,
    skin: 'msg'
  });
  if(url)
    setTimeout(()=>window.location.hrf = url, 2000);
}
