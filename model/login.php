<?

include('../inc/data.php');
session_start();
$cookie_name = "adhoc_user";
if($_POST['action'] == 'logout')
{
	setcookie($cookie_name, "", time() - 3600);
	unset($_COOKIE[$cookie_name]);
// remove all session variables
session_unset(); 

// destroy the session 
session_destroy(); 	


}

if($_POST['action'] == 'login')
{
    $dbQuery = "SELECT `id`
                        FROM `security` 
                        WHERE `session`= ? AND password_sha1= ? AND `status` IN ('active')
                        LIMIT 1";
						
	$result 	= ildb_retrieve($dbQuery,array($_POST['uname'],getHashedPassword($_POST['uname'], $_POST['psw'])), 'ilink');	
	if($result)			
	{
		$_SESSION['adhoc_user'] = $_POST['uname'];
		
		$cookie_value = $_POST['uname'];
		setcookie($cookie_name, $cookie_value, time() + (86400 * 30), "/"); // 86400 = 1 day		
		echo 'done';
	}else{
		echo 'data not found';
	}
	
}



function getHashedPassword($userName, $password){
    $psw = $password . $userName . 'ILINK_HASH_0000009';
    return sha1($psw);
}