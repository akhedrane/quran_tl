<?php
include_once 'auth.inc';

mysql_connect($mysql_host, $mysql_user, $mysql_passwd);
mysql_select_db($mysql_db);

$limit = 10;
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

if (count($words) > 1) {
    if (count($words) == 2){
       $q = "select second_word, matches from cache_l2 where word='" .
          $words[0] . "' and second_word like '" . $words[1] . "%'";
       $res = mysql_query($q) or
           die("could not query: " . mysql_error());
       if (mysql_num_rows($res)==0){
           print json_encode(array());
           return;
       }

       $agg_arr = array();
       while ($row = mysql_fetch_assoc($res)){
           $second_word = $row['second_word'];
           $match = split(',', $row['matches']);
           $agg_arr[$second_word] = $match;
       }
       if (count($agg_arr) != 1){
          $iter = 0;
          $inserts = 0;
          $arr = array();
          while ($inserts < $limit){
             foreach ($agg_arr as $word => $matches){
                if (isset($matches[$iter])){
                   $arr[] = $matches[$iter];
                   $inserts++;
                }
             }
             $iter++;
          }
          $matches = $arr;
       }
       else {
          $keys = array_keys($agg_arr);
          $words[1] = $keys[0];
          $words[] = '';
       }
    }
    if (count($words) == 3){
       $q = "select third_word, matches from cache_l3 where word='" .
          $words[0] . "' and second_word='" . $words[1] . "' and " .
          "third_word like '" . $words[2] . "%'";
       $res = mysql_query($q) or
           die("could not query: " . mysql_error());
       if (mysql_num_rows($res)==0){
           print json_encode(array());
           return;
       }

       $agg_arr = array();
       while ($row = mysql_fetch_assoc($res)){
           $third_word = $row['third_word'];
           $match = split(',', $row['matches']);
           $agg_arr[$third_word] = $match;
       }
       
       $iter = 0;
       $inserts = 0;
       $arr = array();
       while ($inserts < $limit){
          foreach ($agg_arr as $word => $matches){
             if (isset($matches[$iter])){
                $arr[] = $matches[$iter];
                $inserts++;
             }
          }
          $iter++;
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
    if (count($arr)==$limit) break;
}

$res = array('results' => $arr);
print json_encode($res);
