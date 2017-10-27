<?

include('../inc/data.php');


//echo "<pre>".print_r($_POST,1)."</pre>";


if($_POST['action'] == 'gettag')
{
	$tag_name = @$_POST['tag_name'];
	$tag_level = @$_POST['tag_level'];
	$tag_value = @$_POST['tag_value'];
	echo getTag($tag_level,$tag_name,$tag_value);
}else if($_POST['action'] == 'gethistory')
{
	$keyword = @$_POST['keyword'];
	echo getHistory($_POST['user'],$keyword);
}else{
	$getPost = $_POST;
	
	$getPost['date'] = date("Y-m-d");
	$getPost['duration'] = strtotime($getPost['end'])  - strtotime($getPost['start']) ;
	
	$col = implode(',', array_keys($getPost));
	$val = implode(',', array_fill(0, count($getPost), '?'));
	$arrVals = array();
	foreach ($getPost as $key => $value) {
		$arrVals[] = $value;
	}
	$sql = "INSERT INTO adhoclog ({$col}) VALUES ($val)";
	
	ildb_update($sql,$arrVals,'ilink');
	
	
	$dbQuery = "SELECT LAST_INSERT_ID() AS ID";
						
	$last_id 	= ildb_retrieve($dbQuery,array(), 'ilink');
	
	if($last_id)
	{
		echo "<tr>
				<td>{$last_id['ID']}</td>
				<td><div id=history_porject>{$getPost['project']}</div></td>
				<td><div id=history_adhoclink>{$getPost['adhoclink']}</div></td>
				<td><div id=history_description>{$getPost['description']}</div></td>
				<td><div id=history_duration>".comtohf($getPost['duration'])."</div></td>
				<td><input type=button id=history_edit value=CONTINUE></td>
			</tr>";
	}else{
		echo 'error';
	}	
}


function getTag($tag_level='',$tag_name='',$tag_value='')
{
	include '../inc/tag.php';
	
	if($tag_level && $tag_name && $tag_value)
	{
		$tag[$tag_level][] = array($tag_name,$tag_value);
	}
	
	$html = '';
	if(empty($tag)) return;
	foreach ($tag as $level => $value) {
		$html .= "<b style='float: left;'>Level {$level}:&nbsp;</b>";
		foreach ($value as $key => $v) {
			$html .= '<div name="tag" value="'.$v[1].'">'.$v[0].'</div>';
		}
		$html .= "<br><br>";
	}
	
	file_put_contents('../inc/tag.php', '<?php $tag = ' . var_export($tag, true) . ';');
	
	echo $html;
	
	
}


function getHistory($user,$keyword='')
{
	$conition = '';
	$arrContion = array($user);
	if($keyword)
	{
		$arrGroup_search = array('project','description','adhoclink');
		foreach ($arrGroup_search as $key => &$value) {
			$value = $value . " LIKE ?";
			$arrContion[] = "%$keyword%";
		}
		$keyword = "AND (".implode(' OR ', $arrGroup_search).")";
		
	}
	$dbQuery = "SELECT * FROM adhoclog WHERE user = ? {$keyword} ORDER BY end DESC LIMIT 50";
	$results = ildb_rsretrieve($dbQuery,$arrContion, 'ilink');	

	$html = "";
	while ($myrow = $results->fetch())	
	{
		$html .=  "<tr>
			<td>{$myrow['id']}</td>
			<td><div id=history_porject>{$myrow['project']}</div></td>
			<td><div id=history_adhoclink>{$myrow['adhoclink']}</div></td>
			<td><div id=history_description>{$myrow['description']}</div></td>
			<td><div id=history_duration>".comtohf($myrow['duration'])."</div></td>
			<td><input type=button id=history_edit value=CONTINUE></td>
		</tr>";
	}
	echo $html;
}


function comtohf($secs)
{
		$units = array(
				"week"   => 7*24*3600,
				"day"    =>   24*3600,
				"hour"   =>      3600,
				"minute" =>        60,
				"second" =>         1,
		);

	// specifically handle zero
		if ( $secs == 0 ) return "0 seconds";

		$s = "";

		foreach ( $units as $name => $divisor ) {
				if ( $quot = intval($secs / $divisor) ) {
						$s .= "$quot $name";
						$s .= (abs($quot) > 1 ? "s" : "") . ", ";
						$secs -= $quot * $divisor;
				}
		}

		return substr($s, 0, -2);
}
?>