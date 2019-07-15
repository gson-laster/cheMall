//检验时间时：分||昨天||年月日
	function aaa(second){
			 	  var show_time="";
			 	  var tamp=second*1000;
			 	  var time=new Date(tamp);
			 	  var time_=new Date();
			 	  var ss="";//显示的时间
          var year=time.getFullYear();
          var month=time.getMonth();
          var day=time.getDate();
          var year_=time_.getFullYear();
          var month_=time_.getMonth();
          var day_=time_.getDate();
          //今天得消息判断 //消息隔了10分钟则输出时间
	          if(year==year_&&month==month_&&day==day_){
	          			show_time=test(time.getHours())+":"+test(time.getMinutes());
	          			msg_timebetween=second;
	          }
	          else if(year==year_&&month==month_&&day+1==day_&&send_timeold){
	          			show_time="昨天";
	          			send_timeold=false;//昨天只显示一次
	          }
	          else if(!(year==before_time[0]&&month==before_time[1]&&day==before_time[2])){
		          	var mon=month+1;
		          	mon=test(mon);
		          	show_time=year+"/"+mon+"/"+day;
		          	before_time=[];
		          	before_time.push(year,month,day);
		          }
	          if(show_time){
	          	 ss='<div class="time"><span>'+show_time+'</span></div>';
	          }
	          return ss;
	}
	function test(num){
		return num<10?"0"+num:num;
	}