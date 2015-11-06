<?php

$url = 'http://localhost/ApiProject/web/app_dev.php/leboncoin/get/ads/offers/?token=TmpjeU5qazJPRE15Ok1UZ3pOVE14TXpFNU1RPT0=';

echo $url;
echo '<br><br>';

$counter = 0;
$counter_total = 20;
$counter_error = 0;

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_POSTFIELDS, "");
curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

for ($i = 0; $i < $counter_total; $i++) {
    $result = curl_exec($ch);
    if ($result == false) {
        echo '<strong>'.($i+1).'</strong><span style="color: red"> - ERROR</span><br>';
        $counter_error++;
    }
    else echo '<strong>'.($i+1).'</strong><span style="color: green"> - SUCCESS</span><br>';
}

echo '<h2>'.($counter_total-$counter_error).'/'.$counter_total;
