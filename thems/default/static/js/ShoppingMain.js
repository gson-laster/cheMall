$(".three_item li").each(function(){
	var item_li=$(this);
	item_li.click(function(){
		var div_1='<div class="bor_bottom"></div>';
		item_li.find("span").append(div_1);
		item_li.css("color","#DF3F3F")
		item_li.siblings().children('span').children('.bor_bottom').remove();
		item_li.siblings().css("color","black");
	})
})