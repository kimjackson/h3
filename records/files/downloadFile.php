<?php

/*<!--
 * downloadFile.php, returns an attached file requested using the obfuscated file identifier
 * @copyright (C) 2005-2010 University of Sydney Digital Innovation Unit.
 * @link: http://HeuristScholar.org
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @package Heurist academic knowledge management system
 * @todo
 -->*/


// NOTE: THis file updated 18/11/11 to use proper fiel paths and names for uploaded files rather than bare numbers

require_once(dirname(__FILE__)."/../../common/connect/applyCredentials.php");
require_once(dirname(__FILE__)."/../../common/php/dbMySqlWrappers.php");
require_once(dirname(__FILE__)."/../../records/files/uploadFile.php");

mysql_connection_db_select(DATABASE);


// May be best to avoid the possibility of somebody harvesting ulf_ID=1, 2, 3, ...
// so the files are indexed by the SHA-1 hash of the concatenation of the ulf_ID and a random integer.

if (! @$_REQUEST['ulf_ID']) return; // nothing returned if no ulf_ID parameter

$filedata = get_uploaded_file_info($_REQUEST['ulf_ID'], false);
if($filedata==null) return; // nothing returned if parameter does not match one and only one row

$filedata = $filedata['file'];

$type_source = $filedata['remoteSource'];
$type_media = $filedata['mediaType'];

if ($type_source==null || $type_source=='heurist' || $filedata['remoteURL']==null)
{

	// set the actual filename. Up to 18/11/11 this is jsut a bare nubmer corresponding with ulf_ID
	// from 18/11/11, it is a disambiguated concatenation of 'ulf_' plus ulf_id plus ulfFileName
	if ($filedata['fullpath']) {
		$filename = $filedata['fullpath']; // post 18/11/11 proper file path and name
	} else {
		$filename = HEURIST_UPLOAD_DIR ."/". $filedata['id']; // pre 18/11/11 - bare numbers as names, just use file ID
	}

	$filename = str_replace('/../', '/', $filename);  // not sure why this is being taken out, pre 18/11/11, unlikely to be needed any more
	$filename = str_replace('//', '/', $filename);
//DEBUG error_log("filename = ".$filename);

	if(!file_exists($filename) && $filedata['remoteURL']!=null){

		header('Location: '.$filedata['remoteURL']);

	}else if(false)
	{
	/*
		todo: waht is all this and when was it removed? Could it be useful for the furture. ? check with Artem, may be work related to kmls mid Nov 2011
		artem: THIS IS FOR showMap - to support kmz in timeline, it extracts kmz file and sends as kml to client side

		($mimeExt=="kmz"){
		$zip=zip_open($filename);
		if(!$zip) {return("Unable to proccess file '{$filename}'");}
		$e='';
	    while($zip_entry=zip_read($zip)) {
	       $zdir=dirname(zip_entry_name($zip_entry));
	       $zname=zip_entry_name($zip_entry);

	       if(!zip_entry_open($zip,$zip_entry,"r")) {$e.="Unable to proccess file '{$zname}'";continue;}
	       //if(!is_dir($zdir)) mkdirr($zdir,0777);

	       #print "{$zdir} | {$zname} \n";

	       $zip_fs=zip_entry_filesize($zip_entry);
	       if(empty($zip_fs)) continue;

	       $zz=zip_entry_read($zip_entry, $zip_fs);

	       $zname = HEURIST_UPLOAD_DIR."/".$zname;
	error_log(">>>>>>>>>>".$zname);
	       $z=fopen($zname,"w");
	       fwrite($z,$zz);
	       fclose($z);
	       zip_entry_close($zip_entry);

	    }
	    zip_close($zip);

		readfile($zname);
	    unlink($z);
	*/
	}else{

		// set the mime type, set to binary if mime type unknown
		if ($filedata['mimeType']) {
			header('Content-type: ' .$filedata['mimeType']);
		}else{
			header('Content-type: binary/download');
		}

		readfile($filename);
	}

}else if($type_media=='image'){ //Remote resources

	header('Location: '.$filedata['remoteURL']);

}else if($type_source=='youtube'){

//DEBUG	error_log(">>>>>>".linkifyYouTubeURLs($filedata['remoteURL'], ''));

	//return linkifyYouTubeURLs($filedata['remoteURL'], '');// $size
	header('Location: '.$filedata['remoteURL']);
}

// Linkify youtube URLs which are not already links.
function linkifyYouTubeURLs($text, $size) {
    $text = preg_replace('~
        # Match non-linked youtube URL in the wild. (Rev:20111012)
        https?://         # Required scheme. Either http or https.
        (?:[0-9A-Z-]+\.)? # Optional subdomain.
        (?:               # Group host alternatives.
          youtu\.be/      # Either youtu.be,
        | youtube\.com    # or youtube.com followed by
          \S*             # Allow anything up to VIDEO_ID,
          [^\w\-\s]       # but char before ID is non-ID char.
        )                 # End host alternatives.
        ([\w\-]{11})      # $1: VIDEO_ID is exactly 11 chars.
        (?=[^\w\-]|$)     # Assert next char is non-ID or EOS.
        (?!               # Assert URL is not pre-linked.
          [?=&+%\w]*      # Allow URL (query) remainder.
          (?:             # Group pre-linked alternatives.
            [\'"][^<>]*>  # Either inside a start tag,
          | </a>          # or inside <a> element text contents.
          )               # End recognized pre-linked alts.
        )                 # End negative lookahead assertion.
        [?=&+%\w]*        # Consume any URL (query) remainder.
        ~ix',
        '<iframe '.$size.' src="http://www.youtube.com/embed/$1" frameborder="0" allowfullscreen></iframe>',
        $text);

        //'<a href="http://www.youtube.com/watch?v=$1">YouTube link: $1</a>'
        //'<iframe width="420" height="345" src="http://www.youtube.com/embed/$1" frameborder="0" allowfullscreen></iframe>',
//error_log(">>>".$text."<<<<");
    return $text;
}

?>
