<!--
* selectDBForImport.php, Shows a dropdown list with all registered databases and shows crosswalk table
after selection, 26-05-2011, by Juan Adriaanse
* @copyright (C) 2005-2010 University of Sydney Digital Innovation Unit.
* @link: http://HeuristScholar.org
* @license http://www.gnu.org/licenses/gpl-3.0.txt
* @package Heurist academic knowledge management system
* @todo
-->
<html>
<head>
<title>Select database for import</title>

<!-- YUI -->
<link rel="stylesheet" type="text/css" href="../../external/yui/2.8.2r1/build/fonts/fonts-min.css" />
<link rel="stylesheet" type="text/css" href="../../external/yui/2.8.2r1/build/paginator/assets/skins/sam/paginator.css">
<link type="text/css" rel="stylesheet" href="../../external/yui/2.8.2r1/build/datatable/assets/skins/sam/datatable.css">
<script type="text/javascript" src="../../external/yui/2.8.2r1/build/yahoo-dom-event/yahoo-dom-event.js"></script>
<script type="text/javascript" src="../../external/yui/2.8.2r1/build/element/element-min.js"></script>
<script type="text/javascript" src="../../external/yui/2.8.2r1/build/json/json-min.js"></script>
<script type="text/javascript" src="../../external/yui/2.8.2r1/build/datasource/datasource-min.js"></script>
<script type="text/javascript" src="../../external/yui/2.8.2r1/build/datatable/datatable-min.js"></script>
<script type="text/javascript" src="../../external/yui/2.8.2r1/build/paginator/paginator-min.js"></script>

<style type="text/css">
.yui-skin-sam .yui-dt-liner { white-space:nowrap; }
</style>

<link rel=stylesheet href="../../common/css/global.css">
<link rel=stylesheet href="../../common/css/admin.css">

</head>
<body class="popup yui-skin-sam" style="overflow: auto;">

<div class="banner"><h2>Select a database for import</h2></div>
<div id="page-inner" style="overflow:auto">

<div id="statusMsg"><img src="../setup/loading.gif" width="16" height="16" /> &nbspDownloading database list...</div>
<div class="markup" id="filterDiv" style="display:none">
	<label for="filter">Filter:</label> <input type="text" id="filter" value="">
	<div id="tbl"></div>
</div>
<div id="topPagination"></div>
<div id="selectDB"></div>
<div id="bottomPagination"></div>

<form id="crosswalkInfo" action="buildCrosswalks.php" method="POST">
<input id="dbID" name="dbID" type="hidden">
<input id="dbURL" name="dbURL" type="hidden">
<input id="dbName" name="dbName" type="hidden">
<input id="dbTitle" name="dbTitle" type="hidden">
</form>
</div>
<script type="text/javascript">
var registeredDBs = [];
<?php
	require_once(dirname(__FILE__).'/../../common/connect/applyCredentials.php');

	if (!is_logged_in()) {
	    header('Location: ' . HEURIST_URL_BASE . 'common/connect/login.php?db='.HEURIST_DBNAME);
	    return;
	}

	// User must be system administrator or admin of the owners group for this database
	if (!is_admin()) {
	    print "<html><body><p>You must be logged in as system administrator to register a database</p><p><a href=" .
	        HEURIST_URL_BASE . "common/connect/login.php?logout=1&amp;db=" . HEURIST_DBNAME .
	        "'>Log out</a></p></body></html>";
	    return;
	}

	require_once(dirname(__FILE__).'/../../common/php/dbMySqlWrappers.php');
	require_once(dirname(__FILE__)."/../../common/config/initialise.php");
	mysql_connection_db_insert(DATABASE); // Connect to the current database

	// Send request to getRegisteredDBs on the master Heurist index server, to get all registered databases and their URLs
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_COOKIEFILE, '/dev/null');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    //return curl_exec output as string
	curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
	curl_setopt($ch, CURLOPT_NOBODY, 0);
	curl_setopt($ch, CURLOPT_HEADER, 0);    //don't include header in output
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);    // follow server header redirects
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);    // don't verify peer cert
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);    // timeout after ten seconds
	curl_setopt($ch, CURLOPT_MAXREDIRS, 5);    // no more than 5 redirections
	$reg_url =  HEURIST_BASE_URL . "admin/structure/getRegisteredDBs.php"; // TODO: Change to HEURIST_INDEX_BASE_URL
	curl_setopt($ch, CURLOPT_URL,$reg_url);
	$data = curl_exec($ch);
	$error = curl_error($ch);
	if(!$error) {
		// If data has been successfully received, write it to a javascript array, leave out own DB if found
		$res = mysql_query("select sys_dbRegisteredID from sysIdentification where `sys_ID`='1'");
		if($res) {
			$row = mysql_fetch_row($res);
			$ownDBID = $row[0];
		} else {
			$ownDBID = 0;
		}
		$data = json_decode($data);
		foreach($data as $registeredDB) {
			if($ownDBID != $registeredDB->rec_ID) {

				$rawURL = $registeredDB->rec_URL;
				$splittedURL = explode("?db=", $rawURL);

				$dbID = $registeredDB->rec_ID;
				$dbURL = $splittedURL[0];
				$dbName = $splittedURL[1];
				$dbTitle = $registeredDB->rec_Title;
				$dbPopularity = $registeredDB->rec_Popularity;

				echo 'if(!registeredDBs['.$dbID.']) {' . "\n";
				echo 'registeredDBs['.$dbID.'] = new Array();' . "\n";
				echo '}' . "\n";
				echo 'var registeredDB = [];' . "\n";
				echo 'registeredDB[0] = "'.$dbID.'";' . "\n";
				echo 'registeredDB[1] = "'.$dbURL.'";' . "\n";
				echo 'registeredDB[2] = "'.$dbName.'";' . "\n";
				echo 'registeredDB[3] = "'.$dbTitle.'";' . "\n";
				echo 'registeredDB[4] = "'.$dbPopularity.'";' . "\n";
				echo 'registeredDBs['.$dbID.'].push(registeredDB);' . "\n";
			}
		}
	}
?>

// Create a YUI DataTable
var myDataTable;
YAHOO.util.Event.addListener(window, "load", function() {
	YAHOO.example.Basic = function() {
		var myColumnDefs = [
			{key:"id", label:"ID" , formatter:YAHOO.widget.DataTable.formatNumber, sortable:true, resizeable:true},
			{key:"name", label:"Name" , sortable:true, resizeable:true},
			{key:"description", label:"Description", sortable:true, resizeable:true},
			{key:"popularity", label:"Popularity", formatter:YAHOO.widget.DataTable.formatNumber, sortable:true, resizeable:true},
			{key:"crosswalk", label:"Crosswalk", resizeable:true}
		];

		// Add databases to an array that YUI DataTable can use. Do not show URL for safety
		dataArray = [];
		for(dbID in registeredDBs) {
			db = registeredDBs[dbID];
			dataArray.push([db[0][0],db[0][2],db[0][3],db[0][4],'<a href="#" onClick="doCrosswalk('+dbID+')"><img src="crosswalk_icon.png" width="25" height="16" /></a>']);
		}

		var myDataSource = new YAHOO.util.LocalDataSource(dataArray,{
			responseType : YAHOO.util.DataSource.TYPE_JSARRAY,
			responseType : YAHOO.util.DataSource.TYPE_JSARRAY,
			responseSchema : {
				fields: [ "id", "active", "icon", "name", "description", "status", "grp_id", "info"]
			},
			responseSchema : {
				fields: ["id","name","description","popularity","crosswalk"]
			},
			// This is the filter function
			doBeforeCallback : function (req,raw,res,cb) {
				var data = res.results || [],
				filtered = [],
				i,l;
				if (req) {
					req = req.toLowerCase();
				// Do a wildcard search for both name and description
				for (i = 0, l = data.length; i < l; ++i) {
						if (data[i].description.toLowerCase().indexOf(req) >= 0) {
							filtered.push(data[i]);
						} else if (data[i].name.toLowerCase().indexOf(req) >= 0) {
							filtered.push(data[i]);
						}
					}
					res.results = filtered;
				}
				return res;
			}
		});
		// Use pages. Show max 50 databases per page
		myDataTable = new YAHOO.widget.DataTable("selectDB", myColumnDefs, myDataSource, {
		paginator: new YAHOO.widget.Paginator({
			rowsPerPage:50,
			containers:['topPagination','bottomPagination']
		}),

		sortedBy: { key:'id' }
		}),

		// Updates the datatable when filtering
		filterTimeout = null;
		updateFilter  = function () {
			// Reset timeout
			filterTimeout = null;

			// Reset sort
			var state = myDataTable.getState();
			state.sortedBy = {key:'id', dir:YAHOO.widget.DataTable.CLASS_ASC};

			// Get filtered data
			myDataSource.sendRequest(YAHOO.util.Dom.get('filter').value,{
			success : myDataTable.onDataReturnInitializeTable,
			failure : myDataTable.onDataReturnInitializeTable,
			scope   : myDataTable,
			argument: state
			});
		},

		YAHOO.util.Event.on('filter','keyup',function (e) {
			clearTimeout(filterTimeout);
			setTimeout(updateFilter,600);
		});

		myDataTable.subscribe("rowMouseoverEvent", myDataTable.onEventHighlightRow);
		myDataTable.subscribe("rowMouseoutEvent", myDataTable.onEventUnhighlightRow);

		return {
			oDS: myDataSource,
			oDT: myDataTable
		};
	}();
	document.getElementById("statusMsg").innerHTML = "";
	document.getElementById("filterDiv").style.display = "block";
});

// Enter information about the selected database to an invisible form, and submit to the crosswalk page, to start crosswalking
function doCrosswalk(dbID) {
	db = registeredDBs[dbID];
	document.getElementById("dbID").value = db[0][0];
	document.getElementById("dbURL").value = db[0][1];
	document.getElementById("dbName").value = db[0][2];
	document.getElementById("dbTitle").value = db[0][3];
	document.forms["crosswalkInfo"].submit();
}
</script>
</body>
</html>