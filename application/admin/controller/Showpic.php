<?php
$url = $_GET["url"];
//$url = str_replace("http:/","http://",$url);
$dir = pathinfo($url);
$host = $dir['dirname'];
$refer = $host.'/';

$ch = curl_init($url);
curl_setopt ($ch, CURLOPT_REFERER, $refer);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//激活可修改页面,Activation can modify the page
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
$data = curl_exec($ch);
curl_close($ch);

$ext = strtolower(substr(strrchr($img,'.'),1,10));
$types = array(
    'gif'=>'image/gif',
    'jpeg'=>'image/jpeg',
    'jpg'=>'image/jpeg',
    'jpe'=>'image/jpeg',
    'png'=>'image/png',
);
$type = $types[$ext] ? $types[$ext] : 'image/jpeg';
header("Content-type: ".$type);
echo $data;
