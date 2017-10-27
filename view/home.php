
<!DOCTYPE html>
<html>
	<head>
		<style>
			div#info {
			    position: absolute;
			    right: 27px;
			}
			table {
			    border-collapse: collapse;
			    width: 100%;
			}
			
			th, td {
			    padding: 8px;
			    text-align: left;
			    border-bottom: 1px solid #ddd;
			}			
			.timedetail{
				float: left;
   				padding: 0 10px;
			}
			div[name=tag] {
			    float: left;
			    padding: 3px 9px;
			    border: 1px solid #d2d1d1;
			    border-radius: 15px;
			    margin-right: 5px;
			    color: #6b6969;
			    font-family: monospace;
			    cursor: pointer;
			}	
					
		</style>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
		<script>
			$(document).ready(function(){
				
				$.ajax({
				  method: "POST",
				  url: "./model/adhoc.php",
				  data: {action:'gethistory',user:'<?=$_SESSION['adhoc_user']?>' }
				})
				  .done(function( msg ) {
						 $('#history tbody').append(msg);
				  });	
				$.ajax({
				  method: "POST",
				  url: "./model/adhoc.php",
				  data: {action:'gettag',user:'<?=$_SESSION['adhoc_user']?>' }
				})
				  .done(function( msg ) {
						 $('#tag_detail').prepend(msg);
				  });			
				  
				  $("#tag_submit").click(function(){
				  	var tag_level = $('#tag_level').val();
				  	var tag_name = $('#tag_name').val();
				  	var tag_value = $('#tag_value').val();
				  	
				  	if(tag_name && tag_level && tag_value)
				  	{
						$.ajax({
						  method: "POST",
						  url: "./model/adhoc.php",
						  data: {action:'gettag',user:'<?=$_SESSION['adhoc_user']?>' ,tag_name:tag_name,tag_level:tag_level,tag_value:tag_value}
						})
						  .done(function( msg ) {
								 location.reload();
						  });
				  	}else{
				  		alert('incorrect data');
				  	}
				  	
				  });
			    $("#submit").click(function(){
			    	
			    	var start = $('#hidden_timerecord_start').val();
			    	var end = $('#hidden_timerecord_end').val();
			    	
			    	var user = $('#txt0').val();
			    	var project = $('#txt1').val();
			    	var adhoclink = $('#txt2').val();
			    	var description = $('#txt3').val();
			    	var duration = $('#txt5').val();
					$.ajax({
					  method: "POST",
					  url: "./model/adhoc.php",
					  data: { user:user, start: start, end: end, project: project, adhoclink: adhoclink, description: description, duration: duration }
					})
					  .done(function( msg ) {
					  	if(msg == 'error')
					  	{
					  		 alert( msg );
					  	}else{
						    alert( "Data Saved!" );
				    		$('#hidden_timerecord_start').val('');
				    		$('#hidden_timerecord_end').val('');
				    		$('#txt1').val('');
				    		$('#txt2').val('');
				    		$('#txt3').val('');
				    		$('#txt4').val('00:00:00');
				    		$('#txt5').val('00:00:00');
				    		$('#timerecord').html('');
				    		$('#switch').val('start').removeAttr('disabled');
						    $('#submit').attr('disabled','disabled');
						    timer = 0;
						    $('#history tbody').prepend(msg);
						    location.reload();
					  	}

					  });
			    });
			    
				function setCookie(cname, cvalue, exMins) {
				    var d = new Date();
				    d.setTime(d.getTime() + (exMins*60*1000));
				    var expires = "expires="+d.toUTCString();  
				    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
				}		
			    $('#logout').click(function(){
					$.ajax({
					  method: "POST",
					  url: "./model/login.php",
					  data: {action:'logout'  }
					})
					  .done(function( msg ) {
					  	setCookie('adhoc_user','',0);
							location.reload();
						
					  });
			    });
			    
			    $('#switch').click(function(){
			    	var date = new Date();
					var n = date.getFullYear() + '-' + (date.getMonth() + 1) + '-' +  date.getDate() + " " + date.getHours() + ":" + date.getMinutes() + ":" + date.getSeconds();;
			    	$('#hidden_timerecord_'+$(this).val()).val(n);
			    	$('#timerecord').append("<div class='timedetail'>" + $(this).val().toUpperCase() + ": " + n + "</div>");
			    	if($(this).val() == 'start')
		    		{
		    			$(this).val('end');
		    		}else{
		    			$(this).attr('disabled','disabled');
		    			$(this).val('start');
		    			$('#submit').removeAttr('disabled');
		    		}
			    });
			    var timer = 0;
			    setInterval(function(){
			    	
			    	if($('#switch').val() != 'start')
			    	{
			    		timer = timer + 1;
			    		$('#txt4').val(toHHMMSS(timer));
			    		$('#txt5').val(timer);
			    	}
			    }, 1000);
			    
				$(document).on("click", '#history_edit', function(event) { 
				    $('#txt1').val($(this).parent().parent().find('#history_porject').html());
				    $('#txt2').val($(this).parent().parent().find('#history_adhoclink').html());
				    $('#txt3').val($(this).parent().parent().find('#history_description').html());
				});
				$(document).on("click", 'div[name=tag]', function(event) { 
					$('#txt3').prepend('[#'+$(this).attr('value')+']');
					//$(this).prop('checked', false);
				});	
				$(document).on("keyup", '#search', function(event) { 
					var keyword = $(this).val();
					$.ajax({
					  method: "POST",
					  url: "./model/adhoc.php",
					  data: {action:'gethistory',user:'<?=$_SESSION['adhoc_user']?>',keyword:keyword }
					})
					  .done(function( msg ) {
							 $('#history tbody').html(msg);
					  });					
					
				});									
				function toHHMMSS(time) {
				    var sec_num = parseInt(time, 10); // don't forget the second param
				    var hours   = Math.floor(sec_num / 3600);
				    var minutes = Math.floor((sec_num - (hours * 3600)) / 60);
				    var seconds = sec_num - (hours * 3600) - (minutes * 60);
				
				    if (hours   < 10) {hours   = "0"+hours;}
				    if (minutes < 10) {minutes = "0"+minutes;}
				    if (seconds < 10) {seconds = "0"+seconds;}
				    return hours+':'+minutes+':'+seconds;
				}			    
			});
		</script>		
		<title>Adhoc Time Recorder - trial version</title>
		<link rel="shortcut icon" href="./favicon.ico" type="image/x-icon">
		<link rel="icon" href="./favicon.ico" type="image/x-icon">		
	</head>
<body>
<div id='info'>Welcome <?=ucfirst($_SESSION['adhoc_user'])?> <input type="button" id="logout" value="Logout"></div>

<form action=""> 
	<table>
		<tbody>
			<tr><td>Project:</td><td><input type="text" id="txt1" ></td></tr>
			<tr><td>Ticket Number:</td><td><input type="number" id="txt2" ></td></tr>
			<tr><td>Description:</td><td><textarea id="txt3" rows="10" cols="100"></textarea></td></tr>
			<tr><td>Tag:</td><td id='tag_detail'>	
								<div>
									<b>New tag: &nbsp;</b>
									Level: <input type="text" maxlength="3" oninput="this.value=this.value.replace(/[^0-9]/g,'');" style="width: 30px;" id='tag_level'/>
									Name: <input type="text"  id='tag_name'/> 
									Value: <input type="text"  id='tag_value'/> 
									<input type="button" id='tag_submit' value='submit'/>
								</div>
							</td></tr>
			<tr><td><input type="button" id="switch" value="start" ></td><td><div id='timerecord'></div><input type="hidden" id="hidden_timerecord_start" ><input type="hidden" id="hidden_timerecord_end" ></td></tr>
			<tr><td>Duration:</td><td><input disabled type="text" id="txt4" value="00:00:00"><input type="hidden" id="txt5" value=""></td></tr>
			<tr><td>&nbsp;</td><td><input disabled type="button" id="submit"  value="submit" > </td></tr>
		</tbody>
	</table>
<input type="hidden" id="txt0" value="<?=$_SESSION['adhoc_user']?>">
</form>


<h3>History:</h3>
<div>Keyword: <input type="text" id="search" ></div>
<br>
<table id="history">
	
	<thead>
		<tr>
			<th>ID</th>
			<th>PROJECT</th>
			<th>TICKET NUMBER</th>
			<th>DESCRIPTION</th>
			<th>DURATION</th>
			<th>EDIT</th>
		</tr>
	</thead>
	<tbody>
		
	</tbody>
</table>

</body>
</html>