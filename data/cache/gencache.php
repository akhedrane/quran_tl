<?php
include_once '../../auth.inc';
mysql_connect($mysql_host, $mysql_user, $mysql_passwd);
mysql_select_db($mysql_db);

// if php is allowed to use a significant chunk of memory, enable this and
// the cache will be saved much faster.  otherwise, leave it as is.
$inMemory = false;

// in memory caching function - helps us ascertain that our data set is
// unique before saving into mysql (only if $inMemory).
function cacheEntry(&$c, $key, $verse){
   if ($c[$key]){
      if (!in_array($verse, $c[$key]))
         $c[$key][] = $verse;
   }
   else $c[$key] = array($verse);
}

function sqlCacheEntry($verse, $word, $second = '', $third = ''){
   $tbl = "cache" . (empty($third)? (empty($second)? "" : "_l2") : "_l3");
   $words = "word = '$word'";
   if (!empty($second)) $words .= " and second_word = '$second'";
   if (!empty($third)) $words .= " and third_word = '$third'";

   $q = "select matches from $tbl where $words";
   $res = mysql_query($q) or die("could not query: " . mysql_error());
   if (mysql_num_rows($res)==0){
      $vals = (empty($second)? "" : "'$second', ") .
              (empty($third)?  "" : "'$third', ");
      $q = "insert into $tbl values('$word', $vals '$verse')";
      mysql_query($q) or die("could not query: " . mysql_error());
   }
   else {
      $row = mysql_fetch_assoc($res);
      $mlist = $row['matches'];
      $matches = split(',', $mlist);
      if (in_array($verse, $matches)) return;
      $mlist .= ",$verse";
      $q = "update $tbl set matches = '$mlist' where $words";
      mysql_query($q) or die("could not query: " . mysql_error());
   }
}

$seen = 0;
$cache = array();
$l2cache = array();
$l3cache = array();
for ($sura=1; $sura<115; $sura++){
    print "processing sura $sura\n";
    $q = "select ayahnum, ayahtext from transliteration where suranum=$sura";

    $res = mysql_query($q) or die("could not query: " . mysql_error());
    while ($row = mysql_fetch_assoc($res)){
        $text = $row['ayahtext'];
        $ayah = $row['ayahnum'];
        $text = trim($text);

        $words = split(' ', $text);
        $cnt = count($words);
        $seen += $cnt;
        for ($i=0; $i<$cnt; $i++){
            $w = $words[$i];
            $w = strtolower($w);
            if ($inMemory)
               cacheEntry($cache, $w, "$sura:$ayah");
            else sqlCacheEntry("$sura:$ayah", $w);

            if (($i+1) < $cnt){
               $w2 = strtolower($words[$i+1]);
               $k = "$w:$w2";
               if ($inMemory)
                  cacheEntry($l2cache, $k, "$sura:$ayah");
               else sqlCacheEntry("$sura:$ayah", $w, $w2);
            }

            if (($i+2) < $cnt){
               $w2 = strtolower($words[$i+1]);
               $w3 = strtolower($words[$i+2]);
               $k = "$w:$w2:$w3";
               if ($inMemory)
                  cacheEntry($l3cache, $k, "$sura:$ayah");
               else sqlCacheEntry("$sura:$ayah", $w, $w2, $w3);
            }
        }
    }
}

if ($inMemory){
   foreach ($cache as $word => $verses){
       $v = mysql_real_escape_string(join(',', $verses));
       $word = mysql_real_escape_string($word);
       $q = "insert into cache(word, matches) values('$word', '$v')";
       mysql_query($q) or die("could not query: " . mysql_error());
   }

   foreach ($l2cache as $words => $verses){
      $v = mysql_real_escape_string(join(',', $verses));
      $warr = split(':', $words);
      $q = "insert into cache_l2(word, second_word, matches) values(" .
         "'{$warr[0]}', '{$warr[1]}', '$v')";
      mysql_query($q) or die("could not query: " . mysql_error());
   }

   foreach ($l3cache as $words => $verses){
      $v = mysql_real_escape_string(join(',', $verses));
      $warr = split(':', $words);
      $q = "insert into cache_l3(word, second_word, third_word, " .
         "matches) values('{$warr[0]}', '{$warr[1]}', '{$warr[2]}', '$v')";
      mysql_query($q) or die("could not query: " . mysql_error());
   }
}
