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

// cache_l1 and cache_l2 queries
while (true){
   if (count($words) > 2) break;
   $res = get_matches($words[0], isset($words[1])? $words[1] : null);
   if (count($res) > 1){
      $matches = choose_results($res, $limit);
      $processing_done = true;
      break;
   }
   else {
      $keys = array_keys($res);
      $idx = count($words)-1;
      $words[$idx] = $keys[0];
      $words[$idx+1] = '';
      if ($idx > 0) break;
   }
}

// cache_l3 queries
if ((!$processing_done) && (count($words) >= 3)){
   $ctr = 0;
   $cur_res = array();
   while (true){
      $third_word = (isset($words[$ctr+2])? $words[$ctr+2] : "");
      $res = get_matches($words[$ctr], $words[$ctr+1], $third_word);
      if (!empty($cur_res))
         $res = result_merge($cur_res, $res);
      if ((empty($third_word)) || 
          ((count($words) <= ($ctr+3)) && (count($res)>1))){
         $matches = choose_results($res, $limit);
         $processing_done = true;
         break;
      }
      $num_matches = 0;
      foreach ($res as $word => $matches)
         $num_matches += count($matches);
      if ($num_matches < $limit){
         $matches = choose_results($res, $limit);
         $processing_done = true;
         break;
      }
      $cur_res = $res;
      $ctr++;
   }
}

$arr = array();
foreach ($matches as $key => $val){
   $verse_num = $val;

   $q = "select suranum, ayahnum, ayahtext from transliteration " .
      "where versenum=$verse_num";
   $res = mysql_query($q) or die("could not query: " . mysql_error());
   $row = mysql_fetch_assoc($res);
   $fulltext = $row['ayahtext'];

   $sura = $row['suranum'];
   $ayah = $row['ayahnum'];
   $text = $fulltext;

   $match = array('sura' => $sura, 'ayah' => $ayah, 'match' => $text); 
   $arr[] = $match;
   if (count($arr)==$limit) break;
}

$res = array('results' => $arr);
print json_encode($res);

function result_merge($oldarr, $newarr){
   $res = array();
   $oldarr_m = array_values($oldarr);

   foreach ($newarr as $key => $val){
      $m = array_intersect($val, $oldarr_m[0]);
      if (count($m) > 0)
         $res[$key] = $m;
   }

   return $res;
}

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
   $res = mysql_query($q) or die("could not query: " . mysql_error());

   $ret = array();
   while ($row = mysql_fetch_assoc($res)){
      $match = split(',', $row['matches']);
      $ret[$row[$column]] = $match;
   }

   if (count($ret)==0){
      print json_encode(array());
      exit;
   }

   return $ret;
}
