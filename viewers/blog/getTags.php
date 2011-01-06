<?php
/**
*This file retruns a json code array of array of tags keyed by record id
*  [rec_id1 => [ tag1,tag2...],
*   rec_id2 =>[..],
*   ...
*  ]
*/
require_once(dirname(__FILE__)."/../../common/connect/db.php");
require_once(dirname(__FILE__)."/../../common/connect/cred.php");

if (! is_logged_in()) return "";

$userID = intval($_REQUEST["u"]);

if (! $userID) return "";

mysql_connection_select(DATABASE);
// get a list of tags linked to any of the 'blog entry' records for this user
$res = mysql_query("select rec_ID, group_concat(tag_Text)
					  from Records, usrRecTagLinks, usrTags
					 where rec_RecTypeID = 137 and rtl_RecID = rec_ID
					   and tag_ID = rtl_TagID
					   and tag_UGrpID= " . $userID . "
					group by rec_ID");

$tags = array();
while ($row = mysql_fetch_array($res)) {
	$tags[$row[0]] = explode(",", $row[1]);
}

print json_format($tags);

?>