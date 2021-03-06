<?php

/*
* Copyright (C) 2005-2013 University of Sydney
*
* Licensed under the GNU License, Version 3.0 (the "License"); you may not use this file except
* in compliance with the License. You may obtain a copy of the License at
*
* http://www.gnu.org/licenses/gpl-3.0.txt
*
* Unless required by applicable law or agreed to in writing, software distributed under the License
* is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express
* or implied. See the License for the specific language governing permissions and limitations under
* the License.
*/

/**
* brief description of file
*
* @author      Tom Murtagh
* @author      Kim Jackson
* @author      Ian Johnson   <ian.johnson@sydney.edu.au>
* @author      Stephen White   <stephen.white@sydney.edu.au>
* @author      Artem Osmakov   <artem.osmakov@sydney.edu.au>
* @copyright   (C) 2005-2013 University of Sydney
* @link        http://Sydney.edu.au/Heurist
* @version     3.1.0
* @license     http://www.gnu.org/licenses/gpl-3.0.txt GNU License 3.0
* @package     Heurist academic knowledge management system
* @subpackage  !!!subpackagename for file such as Administration, Search, Edit, Application, Library
*/



/**
 * filename, brief description, date of creation, by whom
 * @copyright (C) 2005-2010 University of Sydney Digital Innovation Unit.
 * @link: http://HeuristScholar.org
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @package Heurist academic knowledge management system
 * @todo
 **/



$legalMethods = array(
	"loadSearch",
	"saveRecord",
	"saveRecords",
	"deleteRecord",

	"findSimilarRecords",

	"getFileMetadata",
	"saveFile",

	"retrievePersistentJS.php",
	"storePersistentJS.php",

	"reportUploadProgress",

	"fetchResultsFromSession"
);

function outputAsRedirect($text) {
	global $baseURL, $rxlen;

	$val = base64_encode($text);

	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past

	if (strlen($val) > 8000 || ($rxlen  &&  strlen($val) > $rxlen)) {
		$token = sprintf("data%08x%08x", rand(), rand());

		session_start();
		$_SESSION[$token] = $text;
/*****DEBUG****///		error_log("Location: " . $baseURL . "common/html/blank.html#token=" . $token);
		header("Location: " . $baseURL . "common/html/blank.html#token=" . $token);
	}
	else {
/*****DEBUG****/// error_log("Location: " . $baseURL . "common/html/blank.html#data=" . urlencode($val));
		header("Location: " . $baseURL . "common/html/blank.html#data=" . urlencode($val));
	}

	return "";
}

function outputAsScript($text) {
	global $callback;

	// Split into 1024 character chunks, ensuring that we don't split multi-byte characters.  Thanks Tom!
	preg_match_all('/.{1,1024}(?=[\x0-\x7F]|[\xC0-\xDF][\x80-\xBF]|[\xE0-\xEF][\x80-\xBF][\x80-\xBF]|$)/', $text, $matches, PREG_PATTERN_ORDER);
	$bits = $matches[0];

	$output = $callback . "(";
	$output .= json_encode($bits[0]);
	for ($i=1; $i < count($bits); ++$i) {
		$output .= "+\n" . json_encode($bits[$i]);
	}
	$output .= ");\n";

	return $output;
}


$rxlen = intval(@$_REQUEST["rxlen"]);
$callback = @$_REQUEST["cb"];
if ($callback  &&  preg_match('/^cb[0-9]+$/', $callback)) {
	ob_start("outputAsScript");
} else {
	ob_start("outputAsRedirect");
}

$method = @$_REQUEST['method'];
if (!@$_REQUEST['method']) $method = preg_replace('!.*/([-a-z]+)$!', '$1', $_SERVER['PATH_INFO']);
//$key = @$_REQUEST["key"];

/*****DEBUG****///error_log("hapi dispatch method = ".$method);

define('ISSERVICE',1);

//set database parameter from HAPI


require_once(dirname(__FILE__)."/../../common/connect/applyCredentials.php");
//require_once(dirname(__FILE__)."/../../common/php/dbMySqlWrappers.php");
//require_once("validateKeyedAccess.php");

/*if (! ($auth = get_location($key))) {
	print "{\"error\":\"unknown API key\"}";
	return;
}
*/
/*****DEBUG****///error_log(print_r($auth, 1));
$baseURL = HEURIST_BASE_URL;
//$baseURL = $auth["hl_location"];

//define_constants($auth["hl_instance"]);

/*****DEBUG****///error_log("hapi xss baseURL = ".$baseURL." Heurist base = ".HEURIST_BASE_URL);

if (! @$method  ||  ! in_array($method, $legalMethods)) {
	print "{\"error\":\"unknown method\"}";
	return;
}

define('USING-XSS', 1);

require_once("$method.php");

ob_end_flush();

?>
