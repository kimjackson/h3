<?php

    /* getListOfdatabases.php - returns list of databases on the current server, with links
    * Ian Johnson 10/8/11
    * @copyright (C) 2005-2010 University of Sydney Digital Innovation Unit.
    * @link: http://HeuristScholar.org
    * @license http://www.gnu.org/licenses/gpl-3.0.txt
    * @package Heurist academic knowledge management system
    * @todo
    * 
    -->*/       

    require_once(dirname(__FILE__).'/../../common/connect/applyCredentials.php');
    require_once(dirname(__FILE__).'/../../common/php/dbMySqlWrappers.php');

    // Deals with all the database connections stuff

    mysql_connection_db_select(DATABASE);
    if(mysql_error()) {
        die("Could not get database structure from given database source.");
    }

    print "<html><head></head>";
    print '<body class="popup" width="400" height="500" style="font-size: 14px;overflow:auto;">';

    print "<h3>Heurist databases on this server</h3>";
    print "Click on the database name to open in new window<br><br>";

    $query = "show databases";
    $res = mysql_query($query);

    while ($row = mysql_fetch_array($res)) { 
        $test=strpos($row[0],$dbPrefix);
        if (is_numeric($test) && ($test==0) ) {
            $name = substr($row[0],strlen($dbPrefix));  // delete the prefix
            print("<a href=".HEURIST_BASE_URL."?db=$name target=_blank>$name</a><br>");
        }
    }


?>