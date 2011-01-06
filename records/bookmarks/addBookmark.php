<?php

define("SAVE_URI", "disabled");

require_once(dirname(__FILE__)."/../../common/connect/cred.php");
require_once(dirname(__FILE__)."/../../common/connect/db.php");

if (! is_logged_in()) return;

mysql_connection_db_overwrite(DATABASE);

header("Content-type: text/javascript");


/* chase down any "replaced by" indirections */
$usrID = get_user_id();
$rec_id = intval($_REQUEST["bib_id"]);
$res = mysql_query("select * from Records where rec_ID = $rec_id");
$bib = mysql_fetch_assoc($res);
if (! $bib) {
	print "{ error: \"invalid record ID\" }";
	return;
}

/* check workgroup permissions */
if ($bib["rec_OwnerUGrpID"]  &&  $bib["rec_NonOwnerVisibility"] == "Hidden") {
	error_log("select ugl_GroupID from ".USERS_DATABASE.".sysUsrGrpLinks where ugl_UserID=$usrID and ugl_GroupID=" . intval($bib["rec_OwnerUGrpID"]));
	$res = mysql_query("select ugl_GroupID from ".USERS_DATABASE.".sysUsrGrpLinks where ugl_UserID=$usrID and ugl_GroupID=" . intval($bib["rec_OwnerUGrpID"]));
	if (! mysql_num_rows($res)) {
		$res = mysql_query("select grp.ugr_Name from ".USERS_DATABASE.".sysUGrps grp where grp.ugr_ID=" . $bib["rec_OwnerUGrpID"]);
		$grp_name = mysql_fetch_row($res);  $grp_name = $grp_name[0];
		print "{ error: \"record is restricted to workgroup " . slash($grp_name) . "\" }";
		return;
	}
}


/* check -- maybe the user has this bookmarked already ..? */
$res = mysql_query("select * from usrBookmarks where bkm_recID=$rec_id and bkm_UGrpID=$usrID");

if (mysql_num_rows($res) == 0) {
	/* full steam ahead */
	mysql_query("insert into usrBookmarks (bkm_recID, bkm_UGrpID, bkm_Added, bkm_Modified) values (" . $rec_id . ", $usrID, now(), now())");

	$res = mysql_query("select * from usrBookmarks where bkm_ID=last_insert_id()");
	if (mysql_num_rows($res) == 0) {
		print "{ error: \"internal database error while adding bookmark\" }";
		return;
	}
	$bkmk = mysql_fetch_assoc($res);
	$tagString = "";
}
else {
	$bkmk = mysql_fetch_assoc($res);
	$kwds = mysql__select_array("usrRecTagLinks left join usrTags on tag_ID=rtl_TagID", "tag_Text", "rtl_RecID=$rec_id and tag_UGrpID=$usrID order by rtl_Order, rtl_ID");
	$tagString = join(",", $kwds);
}

$record = array(
	"bkmkID" => $bkmk["bkm_ID"],
	"tagString" => $tagString,
	"rating" => $bkmk["bkm_Rating"],
	"reminders" => array(),	// FIXME: should really import these freshly in case the bkmk already exists
	"passwordReminder" => $bkmk["bkm_PwdReminder"],
	"quickNotes" => $bkmk["pers_notes"]? $bkmk["pers_notes"] : ""
);

print json_format($record);

?>