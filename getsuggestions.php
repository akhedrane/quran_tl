<?php
include_once 'auth.inc';

mysql_connect($mysql_host, $mysql_user, $mysql_passwd);
mysql_select_db($mysql_db);

$term = $_GET['query'];
$query = mysql_real_escape_string($term);
if (strlen($query) == 0){
     print json_encode(array());
     return;
}
 
$matches = array();
$words = split(' ', $query);

if (count($words)==1){
    $q = "select matches from cache where word like '$query%'";
    $res = mysql_query($q) or
        die("could not query: " . mysql_error());
    if (mysql_num_rows($res)==0){
        print json_encode(array());
        return;
    }

    $arr = array();
    while ($row = mysql_fetch_assoc($res)){
        $match = split(',', $row['matches']);
        $arr = array_merge($arr, $match);
    }

    $matches = $arr;
}
else {
    $sql = "select suranum, ayahnum from transliteration where " .
           "ayahtext like '%$query%'";
    $res = mysql_query($sql) or
        die("could not query: " . mysql_error());
    $arr = array();
    while ($row = mysql_fetch_assoc($res)){
        $arr[] = $row['suranum'] . ":" . $row['ayahnum'];
    }
    $matches = $arr;
}

$arr = array();
foreach ($matches as $key => $val){
    list($sura, $ayah) = split(':', $val);

    $q = "select ayahtext from transliteration where " .
        "suranum=$sura and ayahnum=$ayah";
    $res = mysql_query($q) or die("could not query: " . mysql_error());
    $row = mysql_fetch_assoc($res);
    $fulltext = $row['ayahtext'];
    
    $val = stripos($fulltext, " $query");
    if (!$val){
        $val = stripos($fulltext, "$query");
        if (!$val) $val = 0;
    }

    $text = $fulltext;

    $match = array('sura' => $sura, 'ayah' => $ayah, 'match' => $text); 
    $arr[] = $match;
    if (count($arr)==10) break;
}

$res = array('results' => $arr);
print json_encode($res);
