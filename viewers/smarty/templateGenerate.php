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
 * templateGenerate.php
 *
 * Generates a Smarty template on the basis of structure of record
 * type that are included in result of given query
 *
 * besides template text it returns the list of variables (array indexes) that are utilized
 * in this tempalte
 *
 * the result is JSON array
 *
 * @copyright (C) 2005-2010 University of Sydney Digital Innovation Unit.
 * @link: http://HeuristScholar.org
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @author Artem Osmakov
 * @version 2011.0819
 * @package Heurist academic knowledge management system
 * @todo
 **/



define('SEARCH_VERSION', 1);

require_once(dirname(__FILE__).'/../../common/config/initialise.php');
require_once(dirname(__FILE__).'/../../common/php/dbMySqlWrappers.php');
require_once(dirname(__FILE__).'/../../search/getSearchResults.php');
require_once(dirname(__FILE__).'/../../common/php/getRecordInfoLibrary.php');
require_once('libs.inc.php');

	mysql_connection_overwrite(DATABASE);
	//was mysql_connection_select(DATABASE);

	//load definitions (USE CACHE)
	$rtStructs = getAllRectypeStructures(true);
	$dtStructs = getAllDetailTypeStructures(true);
	$dtTerms = getTerms(true);
	$recursion_depth = 0;
	$first_record = null;  //to obtain names of record header fields

	$resVars = array();
	$resVarsByType = array();

	$mode = @$_REQUEST['mode'];
	if($mode=='varsonly'){

		$rectypeID = @$_REQUEST['rty_id'];


		$res0 = getRecordHeaderSectionForSmarty(null, 'r', 0); //from

		$resVars = array_merge($resVars, $res0['vars']);

		/*
		array_push($resVarsByType, array("id"=>"0",
											"name"=>"common",
											"vars"=>$res0['vars'],
											"text"=>$res0['text'],
											"tree"=>$res0['tree'] ));
											*/

		$res = getRecordTypeSectionForSmarty($rectypeID, 'r', 0);

		//text for rectypes fill be inserted manually:  $resText  = $resText.$res['text'];
		$resVars = array_merge($resVars, $res['vars']); //whole list of variables without grouping by record type

		$res['tree']['r'] = array_merge($res0['tree']['r'], $res['tree']['r']);

		array_push($resVarsByType, array("id"=>$rectypeID,
										"name"=>$rtStructs['names'][$rectypeID],
										"vars"=>array_merge($res0['vars'], $res['vars']), //header+details
										"detailtypes"=>$res['detailtypes'],
										"tree"=>$res['tree']));

		$qresult = loadSearch($_REQUEST);

		header("Content-type: text/javascript");
		echo json_format( array("vars"=>$resVarsByType,
								"vars2"=>$resVars,
								"records"=>$qresult["records"]), true);


	}else{

			$qresult = loadSearch($_REQUEST); //from search/getSearchResults.php - loads array of records based on GET request

			if(!array_key_exists('records',$qresult)){
				echo "Empty query. Cannot generate template";
				exit();
			}

			//convert to array that will assigned to smarty variable
			$records =  $qresult["records"];

			//it is required to obtain name of header (common) fields only
			$first_record = $records[0];

            $res = getRecordHeaderSectionForSmarty($first_record, 'r', 0); //from

			/*	$resText = '{* generated by h3 *}\n<style type="text/css">\n{literal}\n   .tlbl{min-width:150px;text-align:right;display:inline-block;}\n{/literal}\n</style>\n\n{foreach $results as $r}\n';*/

			// Instructions at top of generated template and start of main records loop
            /* old version
            $resText = _maketext(array(
                '{*',
                'Enter html (to write web pages) or other text format',
                'Use {foreach ...} loops for repeated records/values',
                'Use {$r.fldname} to indicate field values to be inserted',
                'Use tree on right to insert loops, fields & functions',
                'Braces and * enclose comments which are ignored',
                '<!-- --> is html comment which is ignored in html output',
                '---------------------------------------------------------------- *}',
                '',
                '{foreach $results as $r} {* Records loop, do not remove *}',
                '',
                '{if ($r.recOrder==0)}',
                '',
                '{elseif ($r.recOrder==count($results)-1)}',
                '',
                '{else}',
                '',
                '{/if}',
                '',
                '   <b>id=</b> <!-- use html tags as you wish -->',
                '',
                '   <b>id=</b> <!-- use html tags as you wish -->',
                '',
                '   <!-- Output some common fields -->'));

            //creates
            $resText  = $resText.$res['text'];
            */
            $resText = _maketext(array(
            '{* ',
            'Enter html for web pages or other text format.',
            'Use tree on right to insert fields.',
            'Use <!-- --> for html comments',
            '*}',
            '<h2>Title for report</h2>',
            '<hr>',
            '{foreach $results as $r} {* Records loop, do not remove *}',
            '',
            '{$r.recID} {$r.recTitle} {$r.recURL} {* add further sub-loops and fields here *}',
            '',
            '<hr>',
            '',
            '{/foreach} {* end records loop, do not remove *}',
            '',
            '<h2>End of report</h2> {* Text here appears at end of report *}'));

			$recTypes = array();

			$resVars = array_merge($resVars, $res['vars']); //whole list of variables without grouping by record type
			array_push($resVarsByType, array("id"=>"0",
											"name"=>"common",
											"vars"=>$res['vars'],
											"text"=>$res['text'],
											"tree"=>$res['tree'] ));

			//find all record type in result set
			foreach ($records as $rec){

				//generate entry for each record type only once
				$rectypeID = $rec['rec_RecTypeID'];
				$key = array_search($rectypeID, $recTypes);
				if( !(is_numeric($key) && $key>=0) )
				{
					$res = getRecordTypeSectionForSmarty($rectypeID, 'r', 0);

					//text for rectypes fill be inserted manually:  $resText  = $resText.$res['text'];
					$resVars = array_merge($resVars, $res['vars']);

					array_push($resVarsByType, array("id"=>$rectypeID,
													"name"=>$rtStructs['names'][$rectypeID],
													"vars"=>$res['vars'],
													"detailtypes"=>$res['detailtypes'],
													"text"=>$res['text'],
													"tree"=>$res['tree'] ));

					//$recTypes = array_merge($recTypes, array("".$recTypes=>"ABS"));
					array_push($recTypes, $rectypeID);
				}
			}

			$resVars = array_unique($resVars);

			// Instructions at end of main records loop
            /* old version
            $resText = $resText._maketext(array(
                '',
                '   {* add further sub-loops and fields here *}',
                '',
                '   <hr> <!-- add line between each record -->',
                '',
                '{/foreach} {* end records loop, do not remove *}'));
            */
			header("Content-type: text/javascript");
			echo json_format( array("text"=>$resText,
									"vars"=>$resVarsByType,
									"vars2"=>$resVars), true);
	}

	//$tpl_vars = $smarty->get_template_vars();
	//var_dump($tpl_vars);


	//DEBUG stuff
	//@todo - return the list of record types - to obtain the applicable templates
	//echo "query result = ".print_r($qresult,true)."\n";
	//header("Content-type: text/javascript");

	//header('Content-type: text/html; charset=utf-8');
	//echo json_format( $qresult, true);
	//echo "<br/>***<br/>";

	//echo json_format( $results, true);
	//END DEBUG stuff

exit();

/**
* generates various line separator
*
* @param mixed $lines
*/
function _maketext($lines, $ind=0){

	$sep1 = str_repeat('   ', $ind);
	$sep2 = '\n';

	$res = '';

	foreach ($lines as $line ){
		$res = $res.$sep1.$line.$sep2;
	}

	return $res;
}

function _maketext_div($lines, $ind){

	if($ind>0){
	 	$sep1 = '<div style="padding-left: '.(10*$ind).'px;">';
	}else{
		$sep1 = '<div>';
	}

	$sep2 = '</div>';
	//$sep1 = '';
	//$sep2 = '\n';

	$res = '';

	foreach ($lines as $line ){

		$pos1 = strpos($line,"{foreach");
		$pos2 = strpos($line,"{/foreach");
		if((is_numeric($pos1) && $pos1==0)||(is_numeric($pos2) && $pos2==0))
		{
			$line = "<div style='background-color:#ffcccc;'>".$line."</div>";
		}

		$res = $res.$sep1.$line.$sep2;
	}

	return $res;
}

//
// convert record or detail name string to PHP applicable variable name (index in smarty variable)
// for field(detail) type it will  in low case
//
function getVariableNameForSmarty($name, $is_fieldtype = true){

	global $mode;
//$dtname = strtolower(str_replace(' ','_',strtolower($dtNames[$dtKey]));
//'/[^(\x20-\x7F)\x0A]*/'     "/^[a-z0-9]+$/"
	if($mode=='varsonly'){
		return $name; //without changes
	}else{
		if($is_fieldtype){
			$name = strtolower($name);
		}

		$goodname = preg_replace('~[^a-z0-9]+~i','_', $name);
		$goodname = str_replace('__','_',$goodname);

		return $goodname;
	}
}

$tree = null;
$vars = null;
$arr_text = null;
$parentName = null;

//internal function
function __addvar($key)
{
			global $tree, $vars, $arr_text, $parentName;

			$name_wo_parent = 'rec'.$key;	   //without parent
			$name = $parentName.'.'.$name_wo_parent;

			$tree[$parentName] = array_merge($tree[$parentName], array($name_wo_parent=>$name));
			$vars = array_merge($vars, array($name=>$key));
			array_push($arr_text, '{$'.$name.'}');

//error_log(">>>".$key);// print_r(
}

//
// Creates default header details for any record (name, modified, URL
// @todo - labels or headers
//
function getRecordHeaderSectionForSmarty($rec, $_parentName, $ind, $addrelationfield=false){

	global $tree, $vars, $arr_text, $parentName;

	$parentName = $_parentName;
	$tree = array($parentName=>array());
	$vars = array();
	$arr_text = array();


	if(!$rec){ //for varsonly mode

		$key="ID";
		$name_wo_parent = 'rec'.$key;
		$name = $parentName.'.'.$name_wo_parent;
		$tree[$parentName] = array_merge($tree[$parentName], array($name_wo_parent=>$name));
		$vars = array_merge($vars, array($name=>$key));

		if($parentName!='r'){
			$key="RecTitle";
			$name_wo_parent = 'rec'.$key;
			$name = $parentName.'.'.$name_wo_parent;
			$tree[$parentName] = array_merge($tree[$parentName], array($name_wo_parent=>$name));
			$vars = array_merge($vars, array($name=>$key));
		}else{
			/*
			$key="Title";
			$name_wo_parent = 'rec'.$key;
			$name = $parentName.'.'.$name_wo_parent;
			$tree[$parentName] = array_merge($tree[$parentName], array($name_wo_parent=>$name));
			$vars = array_merge($vars, array($name=>$key));
			*/
		}

		$key="Modified";
		$name_wo_parent = 'rec'.$key;
		$name = $parentName.'.'.$name_wo_parent;
		$tree[$parentName] = array_merge($tree[$parentName], array($name_wo_parent=>$name));
		$vars = array_merge($vars, array($name=>$key));

		if($addrelationfield){
				$key="RelationType";
				$name_wo_parent = 'rec'.$key;
				$name = $parentName.'.'.$name_wo_parent;
				$tree[$parentName] = array_merge($tree[$parentName], array($name_wo_parent=>$name));
				$vars = array_merge($vars, array($name=>$key));
		}

      	return array("text"=>"", "vars"=>$vars, "tree"=>$tree);
	}
	else
	{

		$ind++;

		//loop for all record properties
		foreach ($rec as $key => $value){
		    $pos = strpos($key,"rec_");
			if(is_numeric($pos) && $pos==0){

				if($key=="RecTypeID"){
					$key = "TypeName";
				}else{
					$key = substr($key, 4); //label
				}

				if($key=="ID" || $key=="Title" || $key=="TypeName" || $key=="URL" || $key=="Modified")
				{
					__addvar($key);
				}
			}
		} // for

		//add WOOT field
		__addvar("WootText");

		//add specific Relationship fields
		if($addrelationfield){
			__addvar("RelationType");
			//__addvar("RelationInterpretation");
			__addvar("RelationNotes");
			__addvar("RelationStartDate");
			__addvar("RelationEndDate");
			//__addvar("RelationType");
		}

		$text = _maketext($arr_text, $ind);

      	return array("text"=>$text, "vars"=>$vars, "tree"=>$tree);
	}
}

// Create template on the base of record structure definition. Returns:
// 1. template text
// 2. array list of used variables

function getRecordTypeSectionForSmarty($recTypeId, $parentName, $ind){

	global $first_record, $rtStructs, $recursion_depth, $mode;

	/*$rtNames = $rtStructs['names'];
	$recordTypeName = $rtNames[$rectypeID];
	$recordTypeName = getVariableNameForSmarty($recordTypeName, false);*/

	$ind = $ind++;

	$tree = array($parentName=>array());
	$vars = array();
	$detailtypes = array();
	$arr_text = array();
	//IAN ask for comparison with rectype name
    // $text = $ind.$ind.'{if ($'.$parentName.'.recRecTypeID=='.$recTypeId.')}\n'; //table mode<tr><td colspan="13">';
	$recTypeName = $rtStructs['names'][$recTypeId];
	array_push($arr_text, '{if ($'.$parentName.'.recTypeName=="'.$recTypeName.'")}');

	//get the list of details from record structure
	$details =  $rtStructs['typedefs'][$recTypeId]['dtFields'];


	foreach ($details as $dtKey => $dtValue){
		$recursion_depth = 0;

		$dt = getDetailSectionForSmarty($parentName, $dtKey, $dtValue, $ind);
		if($dt){
			array_push($arr_text, $dt['text']);
			$vars = array_merge($vars, $dt['vars']);
			$detailtypes = array_merge($detailtypes, $dt['detailtypes']);

			$tree[$parentName] = array_merge($tree[$parentName], $dt['tree_children']);
			$tree = array_merge($tree, $dt['tree']);
		}
	}//for

	//force add Relationship "detail" : any recordtype has this section
	$res2 = getRecordHeaderSectionForSmarty($first_record, "Relationship", $ind, true);

	array_push($arr_text, $res2['text']);
	$vars = array_merge($vars, $res2['vars']);
	$tree = array_merge($tree, $res2['tree']);
	// end add Relationship


	array_push($arr_text, '{/if}');
	$text = _maketext($arr_text, $ind);

	return array("text"=>$text, "vars"=>$vars, "tree"=>$tree, "detailtypes"=>$detailtypes);
}

/*
 $dtKey	 - detail type ID

 $dtValue - record type structure definition
*/
function getDetailSectionForSmarty($parentName, $dtKey, $dtValue, $ind){

	global $dtStructs, $rtStructs, $first_record, $recursion_depth, $mode;

	$dtNames = $dtStructs['names'];
	$rtNames = $rtStructs['names'];

	$dty_fi = $dtStructs['typedefs']['fieldNamesToIndex'];
	$rst_fi = $rtStructs['typedefs']['dtFieldNamesToIndex'];

	$ind++;

	if($dtNames[$dtKey]){

			$dtname_wo_parent = getVariableNameForSmarty($dtNames[$dtKey]);
			$dtname = $parentName.'.'.$dtname_wo_parent; //name of variable

			$dtDef = $dtStructs['typedefs'][$dtKey]['commonFields'];

	if($dtDef){

			$tree = array();
			$tree_children = array();
			$vars = array();
			$detailtypes = array();
			$arr_text = array();

			$detailType = $dtDef[$dty_fi['dty_Type']];  //HARDCODED!!!!
			$dt_label 	= $dtValue[$rst_fi['rst_DisplayName']];
			$dt_tooltip = $dtValue[$rst_fi['rst_DisplayHelpText']]; //help text

			switch ($detailType) {
			/* @TODO
			case 'file':
			break;
			case 'geo':
			break;
			case 'calculated':
			break;
			case 'fieldsetmarker':
			break;
			case 'relationtype':
			*/
			case 'separator':
					return null;
			case 'enum':	//@todo!!!!

/*
				$tree_children = array_merge($tree_children, array($dtname_wo_parent=>$dtname));
				$vars = array_merge($vars, array($dtname=>$dt_label));
				$detailtypes = array_merge($detailtypes, array($dtname=>$detailType));
				array_push($arr_text, '{$'.$dtname.'}'); //lbl="'.$dt_label.'"
*/
				//
				$enumtree = array(
					"id"=>$dtname.".id",
					"code"=>$dtname.".code",
					"label"=>$dtname.".label",
					"conceptid"=>$dtname.".conceptid");

				$tree_children = array_merge($tree_children, array($dtname=>$enumtree));


				$vars = array_merge($vars, array($dtname.".id"=>"id"));
				$vars = array_merge($vars, array($dtname.".code"=>"code"));
				$vars = array_merge($vars, array($dtname.".label"=>"label"));
				$vars = array_merge($vars, array($dtname.".conceptid"=>"conceptid"));

				array_push($arr_text, '{$'.$dtname.'.id}',  '{$'.$dtname.'.code}', '{$'.$dtname.'.label}', '{$'.$dtname.'.conceptid}');

				$detailtypes = array_merge($detailtypes, array($dtname=>$detailType));

			break;

			case 'resource': // link to another record type
			case 'relmarker':

				$pointerRecTypeId = $dtValue[$rst_fi['rst_PtrFilteredIDs']]; //@TODO!!!!! it may be comma separated string

				//load this record
				if($recursion_depth<2){ // && ($mode=='varsonly' || array_key_exists($pointerRecTypeId, $rtNames))) {

				$recursion_depth++;

				/*if($mode=='varsonly' || !array_key_exists($pointerRecTypeId, $rtNames)){
					$recordTypeName = $dt_label;
				}else{
					$recordTypeName = $rtNames[$pointerRecTypeId];
				}*/
				$recordTypeName = $dt_label;
				$recordTypeName = getVariableNameForSmarty($recordTypeName, false);

				if($pointerRecTypeId==""){ //unconstrainded
					$dt_label = $dt_label." ptr";
					$vars = array_merge($vars, array($recordTypeName=>$dt_label));
				}

				$_fe = array($dt_label,'{foreach $'.$parentName.'.'.$recordTypeName.'s as $'.$recordTypeName.'}');

				array_push($arr_text, _maketext($_fe, $ind));

				//creates headers
				$res2 = getRecordHeaderSectionForSmarty($first_record, $recordTypeName, $ind, false);  //was 15-Aug-2012 ($detailType=='relmarker')); //from

				array_push($arr_text, $res2['text']);
				$vars = array_merge($vars, $res2['vars']);
				$tree = $res2['tree'];

				if($recursion_depth>1){
					//$tree_children = array_merge($tree_children, $res2['tree']);
				}else{

				}

				//add specific for this record type details
				if(array_key_exists($pointerRecTypeId, $rtNames)){

					//$tree[$recordTypeName] = array_merge($tree[$recordTypeName], $res2['tree']);

					//get the list of details from this record
					$details =  $rtStructs['typedefs'][$pointerRecTypeId]['dtFields'];
	                // TODO: delete this? TEMP - to avoid self recursion
					// no need $text  = $text.'\n<tr><td colspan="13">';

					foreach ($details as $dtKey2 => $dtValue2){
						$dt = getDetailSectionForSmarty($recordTypeName, $dtKey2, $dtValue2, $ind);
						if($dt){
							array_push($arr_text, $dt['text']);
							$vars = array_merge($vars, $dt['vars']);
							$detailtypes = array_merge($detailtypes, $dt['detailtypes']);

							if(false && $recursion_depth>1){
								//$res2[$recordTypeName] = array_merge($res2[$recordTypeName], $dt['tree_children']);
								//$res2 = array_merge($res2, $dt['tree']);
							}else{



								$tree[$recordTypeName] = array_merge($tree[$recordTypeName], $dt['tree_children']); //detail fields

								if($recursion_depth>1){
									$tree[$recordTypeName] = array_merge($tree[$recordTypeName], $dt['tree']);
								}else{
									$tree = array_merge($tree, $dt['tree']); //assign first level records to root
								}
							}
						}
					}//for
					///no need $text = $text.'</td></tr>\n';
					/*
					if($recursion_depth>1){
/*****DEBUG****///error_log(">>>>            ");
/*****DEBUG****///error_log(">>>>            ".print_r($res2['tree'], true));
//						$tree = array_merge($tree, $res2['tree']);
//					}*/

				}//array_key_exists($pointerRecTypeId, $rtNames))

				array_push($arr_text, '{/foreach}');
				array_push($arr_text, '');

				}

/*
				//@todo - parsing will depend on depth level
				// if there are not mentions about this record type in template (based on obtained array of variables)
				// we will create href link to this record
				// otherwise - we have to obtain this record (by ID) and add subarray

				$res = array();
				$rectypeID = null;

				foreach ($dtValue as $key => $value){
/*****DEBUG****///error_log(">>>>>>>>>>".array_key_exists('id',$value));
/*					if (array_key_exists('id',$value))
					{
						//this is record ID
						$recordID = $value['id'];
						//get full record info
						$record = loadRecord($recordID); //from search/getSearchResults.php
						$res0 = getRecordForSmarty($record); //@todo - need to

						if($res0){
							array_push($res, $res0);
							if($rectypeID==null){
								$rectypeID = $res0['recRecTypeID'];
							}
						}
					}
				}//for each

				if( count($res)>0 && array_key_exists($rectypeID, $rtNames))
				{
					$recordTypeName = $rtNames[$rectypeID];
					$recordTypeName = getVariableNameForSmarty($recordTypeName, false);
/*****DEBUG****///error_log(">>>>>>>>>>".$rectypeID."=".$rtNames[$rectypeID]."=".$recordTypeName);
/*					$res = array( $recordTypeName."s" =>$res, $recordTypeName =>$res[0] );
				}else{
					$res = null;
				}
*/
			break;

			default:
//:&nbsp;
				//array_push($vars, $dtname);
				$tree_children = array_merge($tree_children, array($dtname_wo_parent=>$dtname));
				$vars = array_merge($vars, array($dtname=>$dt_label));
				$detailtypes = array_merge($detailtypes, array($dtname=>$detailType));
				array_push($arr_text, '{$'.$dtname.'}'); //lbl="'.$dt_label.'"

			}//end switch

			$text = _maketext($arr_text, $ind);

		return array("text"=>$text, "vars"=>$vars, 'detailtypes'=>$detailtypes, 'tree_children'=>$tree_children, 'tree'=>$tree);

	} else {
		return null; //detail type not found in detailTypes.typedefs
	}
	} else { //name is not defined
			return null;
	}

}


?>
