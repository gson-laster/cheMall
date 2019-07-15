//商品的数量的加减
function prev(obj){
		var obj=$(obj).next().children("input");
		var count=obj.val();

		if(count>1){
			count--;
		}
		obj.val(count)


}
function next(obj){
		var obj=$(obj).prev().children("input");
		var count=obj.val();
			count++;
			obj.val(count)
}
