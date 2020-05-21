<?php

//error_reporting(0);

require_once "simple_html_dom.php";
$buvosSzam = 39.886;
$ev = "20k";

try {
    $pdo = new PDO('mysql:host=localhost;dbname=felvi;charset=utf8', "root", "");
    $pdo->exec("set names utf8");
} catch (PDOException $e) {
    print "Error!: " . $e->getMessage() . "<br/>";
    die();
}


function BreakCSS($css)
{

    $results = array();

    preg_match_all('/(.+?)\s?\{\s?(.+?)\s?\}/', $css, $matches);
    foreach($matches[0] AS $i=>$original)
        foreach(explode(';', $matches[2][$i]) AS $attr)
            if (strlen(trim($attr)) > 0) 
            {
                list($name, $value) = explode(':', $attr);
                $results[$matches[1][$i]][trim($name)] = trim($value);
            }
    return $results;
}

$a = 0;


			//Gyorsítás érdekébe előtte lefuttatandó az arrayFinder.php
$oldalak = array(5,28,31,34,49,51,56,63,69,74,80,81,82,83,91,93,95,97,102,170,171,174,175,176,178,181,182,185,186,187,188,189,190,193,195,198,202,215,216,217,222,223,226,229,231,232,233,239,240,242,249,250,252,253,256,257,259,261,262,263,264,266,267,270,273,274,275,276,277,278,283,284,285,294,296,297,298,300,301,305,306,307,1375,1377,1378,1379,1380,1381,1382,1383,1453,1454,1457,1564,2212,2213,2214,2215,2216,2218,2220,2221,2222,2322,2323,2848,2851,2852,2911,2914,2915,2916,2917,2918,2923,3490,3959,4162,4675,4676);

foreach($oldalak as $a){
    $dom = file_get_html('http://www.felvi.hu/bin/content/vonal'. $ev .'/html/meg/meg_'.$a.'.html');
    if(!empty($dom)) {
        //echo $a . "<br>";
        $base =  $dom->find("script", 4)->innertext;
        $base = str_replace("welding('", "", $base);
        $base = str_replace("');", "", $base);
        $html = base64_decode($base);

        $final = str_get_html($html);
        


        foreach ($final->find("tr") as $tr) {
            $i = 1;
            $kepzesiszint = "";
            $munkarend = "";
            $finforma = "";
            $szak = "";
            $ponthatar = "";
            $szakid = $a;
            $intezmeny = "";

            foreach ($tr->find("td[class*='t-mezo']") as $td) {
                if($i == 6){
                    if(strlen($td->plaintext) > 0 && $td->plaintext != "&nbsp;"){
                        $ponthatar = $td->plaintext;
                        echo $td->plaintext;
                    }else{
                        $temp = str_replace("<div style=\"", "#div{", $td->innertext);
                        $temp = str_replace("\">&nbsp;</div>", "}", $temp);
                        $css = BreakCSS($temp);
                        $backgroundpos = str_replace("px","",$css["#div"]["background-position"]);
                        $toInt = intval($backgroundpos);
                        $eredmeny = abs(round($toInt / $buvosSzam));
                        if($eredmeny == 482){
                            echo "n. i.";
                            $ponthatar = "n. i.";
                        }
                        elseif($eredmeny < 200){
                            echo $eredmeny + 1;
                            $ponthatar = strval($eredmeny + 1);
                        }
                        else{
                            echo $eredmeny;
                            $ponthatar = strval($eredmeny);
                        }
                    }
                }
                elseif($i == 1){
                    $kar = str_replace("&nbsp;", "", $td->plaintext);
                    $kar = explode('-', $kar);
                    
                    $user = $pdo->prepare("SELECT id FROM egyetem WHERE rovid_nev='". $kar[0] ."' LIMIT 1");
                    $user->execute();
                    if($user->rowCount() == 1){
                        $suli = str_replace("&nbsp;", "", $td->plaintext);
                        $regi = 0;
                        $egyId = $user->fetchColumn();
                        $intezmeny = $suli;
                        $sql =  "INSERT INTO karok (karnev, egyetemid, regi) "
                                . "SELECT '$suli', $egyId, $regi "
                                . "WHERE NOT EXISTS "
                                . "(SELECT id FROM karok WHERE karnev = '".$suli."');";
                        $insert = $pdo->prepare($sql);
                        $insert->execute();
                    }
                    else{
                        $user = $pdo->prepare("SELECT id FROM regi_egy_nevek WHERE regi_kod='". $kar[0] ."' LIMIT 1");
                        $user->execute();
                        if($user->rowCount() == 1){
                            $suli = str_replace("&nbsp;", "", $td->plaintext);
                            $regi = 1;
                            $intezmeny = $suli;
                            $egyId = $user->fetchColumn();
                            $sql =  "INSERT INTO karok (karnev, egyetemid, regi) "
                            . "SELECT '$suli', $egyId, $regi "
                            . "WHERE NOT EXISTS "
                            . "(SELECT id FROM karok WHERE karnev = '".$suli."');";
                            $insert = $pdo->prepare($sql);
                            $insert->execute();
                        }
                    }

                    echo $td->plaintext . " ";
                }
                elseif($i == 2){
                    $kepzesiszint = str_replace("&nbsp;", "", $td->plaintext);
                }
                elseif($i == 3){
                    $munkarend = str_replace("&nbsp;", "", $td->plaintext);
                }
                elseif($i == 4){
                    $finforma = str_replace("&nbsp;", "", $td->plaintext);
                }
                elseif($i == 5){
                    $szak = str_replace("&nbsp;", "", $td->plaintext);
                }
                else{
                    echo trim($td->plaintext) . " ";
                }
                
                $i++;
            }

            $user = $pdo->prepare("SELECT id FROM karok WHERE karnev='". $intezmeny ."' LIMIT 1");
            $user->execute();
            if($user->rowCount() == 1){
                $karId = $user->fetchColumn();
                $sth = $pdo->prepare("INSERT INTO ponthatarok (karid, szaknev, kepzesi_szint, munkarend, finanszirozas, ponthatar, szakid, ev) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $sth->execute(array($karId, $szak, $kepzesiszint, $munkarend, $finforma, $ponthatar, $szakid, $ev));
            }
            echo "<br>";
        }
    }
    $a++;
}

?>