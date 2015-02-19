<?php

header("Content-Type: text/html; charset=utf-8");

$qs = $_SERVER['QUERY_STRING'];
$in_arr = explode('&', $qs);
$visitsid = '';
$lang = '';
foreach($in_arr as $el) {
  $el_arr = explode('=', $el, 2);
  if (sizeof($el_arr) === 2) {
    $key = $el_arr[0];
    $val = $el_arr[1];
    if ($key === 'visitsid') {
      $visitsid = $val;
    }
    if ($key === 'lang') {
      $lang = $val;
    }
  }
}

include_once('../head_and_style.html');
include_once('../common_javascript.html');
include_once('index.html');
?>
