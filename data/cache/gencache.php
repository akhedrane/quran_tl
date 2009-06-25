<?php
include_once '../../auth.inc';
mysql_connect($mysql_host, $mysql_user, $mysql_passwd);
mysql_select_db($mysql_db);

$seen = 0;
$cache = array();         
for ($sura=1; $sura<115; $sura++){
    print "processing sura $sura\n";
    $q = "select ayahnum, ayahtext from transliteration where suranum=$sura";

    $res = mysql_query($q) or die("could not query: " . mysql_error());
    while ($row = mysql_fetch_assoc($res)){
        $text = $row['ayahtext'];
        $ayah = $row['ayahnum'];
        $text = trim($text);

        $words = split(' ', $text);
        foreach ($words as $word){
            $seen += count($words);
            $w = $word;
            $w = strtolower($w);
            if (isset($cache[$w])){
                $key = "$sura:$ayah";
                if (!in_array($key, $cache[$w]))
                    $cache[$w][] = $key;
            }
            else $cache[$w] = array("$sura:$ayah");
        }
    }
}

foreach ($cache as $word => $verses){
    $v = mysql_real_escape_string(join(',', $verses));
    $word = mysql_real_escape_string($word);
    $q = "insert into cache(word, matches) values('$word', '$v')";
    mysql_query($q) or die("could not query: " . mysql_error());
}

print "processed $seen words, a total of " . count($cache) . " were unique.\n";
