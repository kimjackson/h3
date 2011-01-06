<?php

define('SAVE_URI', 'disabled');

require_once(dirname(__FILE__).'/../../common/connect/cred.php');

header('Content-type: text/javascript');

session_start();

// note $collection is a reference - SW also we suppress warnings to let the system create the key
$collection = &$_SESSION[@HEURIST_INSTANCE_PREFIX.'heurist'][@'record-collection'];

function digits ($s) {
	return preg_match('/^\d+$/', $s);
}

if (array_key_exists('add', $_REQUEST)) {
	$ids = array_filter(explode(',', $_REQUEST['add']), 'digits');
	foreach ($ids as $id) {
		$collection[$id] = true;
	}
}

if (array_key_exists('remove', $_REQUEST)) {
	$ids = array_filter(explode(',', $_REQUEST['remove']), 'digits');
	foreach ($ids as $id) {
		unset($collection[$id]);
	}
}

if (array_key_exists('clear', $_REQUEST)) {
	$collection = array();
}

$rv = array(
	'count' => count($collection)
);

if (array_key_exists('fetch', $_REQUEST)) {
	$rv['ids'] = array_keys(@$collection);
}

print json_format($rv);

?>