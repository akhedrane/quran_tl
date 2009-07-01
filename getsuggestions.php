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

$processing_done = false;
if (count($words)==1){
   $res = get_matches($words[0]);
   if (count($res)==1){
      $keys = array_keys($res);
      $words[0] = $keys[0];
      $words[1] = "";
   }
   else {
      $matches = choose_results($res, $limit);
      $processing_done = true;
   }
}

if (count($words) == 2){
   $res = get_matches($words[0], $words[1]);
   if (count($res) > 1){
      $matches = choose_results($res, $limit);
      $processing_done = true;
   }

   else {
      $keys = array_keys($res);
      $words[1] = $keys[0];
      $words[2] = '';
   }
}

# todo - change this to >= 3, make else if an if.
# if count($res) > 1 - return choose_results();
# else if count($matches) < 5, fall through and sql.
# else $res2 = get_matches($words[1], $words[2], $words[3] || "")
#      join res and res2 together using intersections
#      if count($res) > 1 - return choose_results();
#      else if count($matches < 5), fall through and sql.
#      else if already looked at all words and blank, return choose_results.
#      else loop.

if ((!$processing_done) && (count($words) == 3)){
   $res = get_matches($words[0], $words[1], $words[2]);
   $matches = choose_results($res, $limit);
   $processing_done = true;
}
else if (!$processing_done) {
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
   if (count($arr)==$limit) break;
}

$res = array('results' => $arr);
print json_encode($res);

function choose_results($res, $limit){
   $iter = 0;
   $inserts = 0;
   $arr = array();
   $round = 0;
   while ($inserts < $limit){
      $round = 0;
      foreach ($res as $word => $matches){
         if (isset($matches[$iter])){
            $arr[] = $matches[$iter];
            $inserts++;
            $round++;
         }
      }
      $iter++;
      if ($round == 0) break;
   }
   return $arr;
}

function get_matches($word, $word2 = null, $word3 = null){
   $column = (is_null($word3)? 
      (is_null($word2)? "word" : "second_word") : "third_word");
   $columns = "$column, matches";
   $tbl = "cache" . (is_null($word2)? "" : (is_null($word3)? "_l2" : "_l3"));
   $cond = "word " . (is_null($word2)? "like '$word%'" : " = '$word'");
   if (!is_null($word2))
      $cond .= " and second_word " . (is_null($word3)? "like '$word2%'" : 
      " = '$word2'");
   if (!is_null($word3))
      $cond .= " and third_word like '$word3%'";
   $q = "select $columns from $tbl where $cond";
   $res = mysql_query($q) or die("[$q] could not query: " . mysql_error());

   $ret = array();
   while ($row = mysql_fetch_assoc($res)){
      $match = split(',', $row['matches']);
      $ret[$row[$column]] = $match;
   }

   if (count($ret)==0){
      print json_encode(array());
      return;
   }

   return $ret;
}
