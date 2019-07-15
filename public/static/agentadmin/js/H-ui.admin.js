/*H-ui.admin.js v2.3.1 date:15:42 2015.08.19 by:guojunhui*/
/*获取顶部选项卡总长度*/
function tabNavallwidth() {
  var taballwidth = 0,
    $tabNav = $(".acrossTab"),
    $tabNavWp = $(".Hui-tabNav-wp"),
    $tabNavitem = $(".acrossTab li"),
    $tabNavmore = $(".Hui-tabNav-more");
  if (!$tabNav[0]) {
    return }
  $tabNavitem.each(function (index, element) {
    taballwidth += Number(parseFloat($(this).width() + 60))
  });
  $tabNav.width(taballwidth + 25);
  var w = $tabNavWp.width();
  if (taballwidth + 25 > w) {
    $tabNavmore.show()
  } else {
    $tabNavmore.hide();
    $tabNav.css({ left: 0 })
  }
}

/*左侧菜单响应式*/
function Huiasidedisplay() {
  if ($(window).width() >= 768) {
    $(".Hui-aside").show()
  }
}

function getskincookie() {
  var v = getCookie("Huiskin");
  if (v == null || v == "") {
    v = "default";
  }
  $("#skin").attr("href", "skin/" + v + "/skin.css");
}
$(function () {
  getskincookie();
  //layer.config({extend: 'extend/layer.ext.js'});
  Huiasidedisplay();
  var resizeID;
  $(window).resize(function () {
    clearTimeout(resizeID);
    resizeID = setTimeout(function () {
      Huiasidedisplay();
    }, 500);
  });

  $(".Hui-nav-toggle").click(function () {
    $(".Hui-aside").slideToggle();
  });
  $(".Hui-aside").on("click", ".menu_dropdown dd li a", function () {
    if ($(window).width() < 768) {
      $(".Hui-aside").slideToggle();
    }
  });
  /*左侧菜单*/
  $.Huifold(".menu_dropdown dl dt", ".menu_dropdown dl dd", "fast", 1, "click");
  /*选项卡导航*/

  $(".Hui-aside").on("click", ".menu_dropdown a", function () {
    if ($(this).attr('_href')) {
      var bStop = false;
      var bStopIndex = 0;
      var _href = $(this).attr('_href');
      var _titleName = $(this).html();
      var topWindow = $(window.parent.document);
      var show_navLi = topWindow.find("#min_title_list li");
      show_navLi.each(function () {
        if ($(this).find('span').attr("data-href") == _href) {
          bStop = true;
          bStopIndex = show_navLi.index($(this));
          return false;
        }
      });
      if (!bStop) {
        creatIframe(_href, _titleName);
        min_titleList();
      } else {
        show_navLi.removeClass("active").eq(bStopIndex).addClass("active");
        var iframe_box = topWindow.find("#iframe_box");
        iframe_box.find(".show_iframe").hide().eq(bStopIndex).show().find("iframe").attr("src", _href);
      }
    }
  });

  function min_titleList() {
    var topWindow = $(window.parent.document);
    var show_nav = topWindow.find("#min_title_list");
    var aLi = show_nav.find("li");
  };

  function creatIframe(href, titleName) {
    var topWindow = $(window.parent.document);
    var show_nav = topWindow.find('#min_title_list');
    show_nav.find('li').removeClass("active");
    var iframe_box = topWindow.find('#iframe_box');
    show_nav.append('<li class="active"><span data-href="' + href + '">' + titleName + '</span><i></i><em></em></li>');
    tabNavallwidth();
    var iframeBox = iframe_box.find('.show_iframe');
    iframeBox.hide();
    iframe_box.append('<div class="show_iframe"><div class="loading"></div><iframe frameborder="0" src=' + href + '></iframe></div>');
    var showBox = iframe_box.find('.show_iframe:visible');
    showBox.find('iframe').attr("src", href).load(function () {
      showBox.find('.loading').hide();
    });
  }

  var num = 0;
  var oUl = $("#min_title_list");
  var hide_nav = $("#Hui-tabNav");
  $(document).on("click", "#min_title_list li", function () {
    var bStopIndex = $(this).index();
    var iframe_box = $("#iframe_box");
    $("#min_title_list li").removeClass("active").eq(bStopIndex).addClass("active");
    iframe_box.find(".show_iframe").hide().eq(bStopIndex).show();
  });
  $(document).on("click", "#min_title_list li i", function () {
    var aCloseIndex = $(this).parents("li").index();
    $(this).parent().remove();
    $('#iframe_box').find('.show_iframe').eq(aCloseIndex).remove();
    num == 0 ? num = 0 : num--;
    tabNavallwidth();
  });
  $(document).on("dblclick", "#min_title_list li", function () {
    var aCloseIndex = $(this).index();
    var iframe_box = $("#iframe_box");
    if (aCloseIndex > 0) {
      $(this).remove();
      $('#iframe_box').find('.show_iframe').eq(aCloseIndex).remove();
      num == 0 ? num = 0 : num--;
      $("#min_title_list li").removeClass("active").eq(aCloseIndex - 1).addClass("active");
      iframe_box.find(".show_iframe").hide().eq(aCloseIndex - 1).show();
      tabNavallwidth();
    } else {
      return false;
    }
  });
  tabNavallwidth();

  $('#js-tabNav-next').click(function () {
    num == oUl.find('li').length - 1 ? num = oUl.find('li').length - 1 : num++;
    toNavPos();
  });
  $('#js-tabNav-prev').click(function () {
    num == 0 ? num = 0 : num--;
    toNavPos();
  });

  function toNavPos() {
    oUl.stop().animate({ 'left': -num * 100 }, 100);
  }

  /*换肤*/
  $("#Hui-skin .dropDown-menu a").click(function () {
    var v = $(this).attr("data-val");
    setCookie("Huiskin", v);
    $("#skin").attr("href", "skin/" + v + "/skin.css");
  });
});
/*弹出层*/
/*
	参数解释：
	title	标题
	url		请求的url
	id		需要操作的数据id
	w		弹出层宽度（缺省调默认值）
	h		弹出层高度（缺省调默认值）
*/
function layer_show(title, url, w, h) {
  if (title == null || title == '') {
    title = false;
  };
  if (url == null || url == '') {
    url = "404.html";
  };
  if (w == null || w == '') {
    w = 800;
  };
  if (h == null || h == '') {
    h = ($(window).height() - 50);
  };
  layer.open({
    type: 2,
    area: [w + 'px', h + 'px'],
    fix: false, //不固定
    maxmin: true,
    shade: 0.4,
    title: title,
    content: url
  });
}
/*关闭弹出框口*/
function layer_close() {
  var index = parent.layer.getFrameIndex(window.name);
  parent.layer.close(index);
}

$(function () {
  $('.skin-minimal input').iCheck({
    checkboxClass: 'icheckbox-blue',
    radioClass: 'iradio-blue',
    increaseArea: '20%'
  });

  $list = $("#fileList"),
    $btn = $("#btn-star"),
    state = "pending",
    uploader;

var uploader = WebUploader.create({
    auto: true,
    swf: 'lib/webuploader/0.1.5/Uploader.swf',

    // 文件接收服务端。
    server: '/api/UploadFiles/uploadAjax',

    // 选择文件的按钮。可选。
    // 内部根据当前运行是创建，可能是input元素，也可能是flash.
    pick: '#filePicker',

    // 不压缩image, 默认如果是jpeg，文件上传前会压缩一把再上传！
    resize: false,
    // 只允许选择图片文件。
    accept: {
      title: 'Images',
      extensions: 'gif,jpg,jpeg,bmp,png',
      mimeTypes: 'image/*'
    }
  });
  uploader.on('fileQueued', function (file) {
    var $li = $(
        '<div id="' + file.id + '" class="item">' +
        '<div class="pic-box"><img></div>' +
        '<div class="info">' + file.name + '</div>' +
        '<p class="state">等待上传...</p>' +
        '</div>'
      ),
      $img = $li.find('img');
    $list.append($li);

    // 创建缩略图
    // 如果为非图片文件，可以不用调用此方法。
    // thumbnailWidth x thumbnailHeight 为 100 x 100
    uploader.makeThumb(file, function (error, src) {
      if (error) {
        $img.replaceWith('<span>不能预览</span>');
        return;
      }

      $img.attr('src', src);
    }, thumbnailWidth, thumbnailHeight);
  });
  // 文件上传过程中创建进度条实时显示。
  uploader.on('uploadProgress', function (file, percentage) {
    var $li = $('#' + file.id),
      $percent = $li.find('.progress-box .sr-only');

    // 避免重复创建
    if (!$percent.length) {
      $percent = $('<div class="progress-box"><span class="progress-bar radius"><span class="sr-only" style="width:0%"></span></span></div>').appendTo($li).find('.sr-only');
    }
    $li.find(".state").text("上传中");
    $percent.css('width', percentage * 100 + '%');
  });

  // 文件上传成功，给item添加成功class, 用样式标记上传成功。
  uploader.on('uploadSuccess', function (file) {
    $('#' + file.id).addClass('upload-state-success').find(".state").text("已上传");
  });

  // 文件上传失败，显示上传出错。
  uploader.on('uploadError', function (file) {
    $('#' + file.id).addClass('upload-state-error').find(".state").text("上传出错");
  });

  // 完成上传完了，成功或者失败，先删除进度条。
  uploader.on('uploadComplete', function (file) {
    $('#' + file.id).find('.progress-box').fadeOut();
  });
  uploader.on('all', function (type) {
    if (type === 'startUpload') {
      state = 'uploading';
    } else if (type === 'stopUpload') {
      state = 'paused';
    } else if (type === 'uploadFinished') {
      state = 'done';
    }

    if (state === 'uploading') {
      $btn.text('暂停上传');
    } else {
      $btn.text('开始上传');
    }
  });

  $btn.on('click', function () {
    if (state === 'uploading') {
      uploader.stop();
    } else {
      uploader.upload();
    }
  });

});
