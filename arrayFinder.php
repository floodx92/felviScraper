<?php
$a = 1;

echo "array(";
while ($a <= 7000) {

    $handle = curl_init('http://www.felvi.hu/bin/content/vonal20k/html/meg/meg_'.$a.'.html');
    curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
    
    
    $response = curl_exec($handle);

    $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);

    if($httpCode != 404) {
         echo $a . ",";
    }
    
    curl_close($handle);

    $a++;
}
echo ");";

?>