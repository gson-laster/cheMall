function item(obj){
	var item_li=$(obj);
	item_li.addClass("border_bottom");
	item_li.siblings().removeClass("border_bottom");
	if (item_li.hasClass("one_item")) {
		$(".one_box").addClass('none');	
		$(".two_box").removeClass('none');	
	}
	else{
		$(".one_box").removeClass('none');	
		$(".two_box").addClass('none');	
	}
}
