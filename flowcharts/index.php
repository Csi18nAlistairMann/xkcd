<?php

header("Content-Type: text/html; charset=utf-8");

function post_a_new($un, $pw, $apikey, $newmark, $lang, $vis, $trans, $opt_ignore_blanks) {
  if ($opt_ignore_blanks === 'ignore' && $trans == '') {
    return '';
  }
  $errno = $errstr = '';
  $fp = fsockopen("tls://rest.mpsvr.com", 443, $errno, $errstr, 10);
  if (!$fp) {
    $error = true;
    //    echo "$errstr ($errno)<br />\n";
  } else {
    $error = false;
    $x = json_encode(array('csi18n_xlate_resource' => array('newmark' => $newmark,
							    'language' => $lang,
							    'visibility' => $vis,
							    'translation' => $trans)));
    $x .= "\r\n\r\n";

    $out = "POST /newmarks/me/" . $newmark . " HTTP/1.1\r\n";
    $out .= "Host: rest.mpsvr.com\r\n";
    $out .= "X-APIKey: $apikey\r\n";
    $out .= "Authorization: Basic " . base64_encode("$un:$pw") . "\r\n";
    $out .= "Content-Type: application/json;v=1.0\r\n";
    $out .= "Content-Length: " . mb_strlen($x) . "\r\n";
    $out .= "Connection: close\r\n\r\n";

    $out .= $x;
    fwrite($fp, $out);
    $received = '';
    while (!feof($fp)) {
      $received .= fgets($fp, 128);
    }
    fclose($fp);

    list($headers, $body) = explode("\r\n\r\n", $received, 2);
    $rv = substr($headers, 9, 3);
    $headers_arr = explode("\r\n", $headers);
    switch($rv){
      //    case (404) : echo "404 - No resource found at that URI"; break;
      //    case (409) : echo "409 - Conflict. Update identical to current"; break;
    case (201) : //201 created ok
    case (301) : //301 moved 
      break;
    default: 
      echo substr($headers_arr[0], 9); break;
    }

  }
  if ($error === true) {
    echo "This went wrong<br>";
  }

  $loc = '';
  foreach($headers_arr as $header) {
    if (mb_substr($header, 0, 10) === 'Location: ') {
      $loc = mb_substr($header, 10);
    }
  }
  //var_dump($headers_arr);
  return $loc;
}

$al = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
$al_arr = explode(',', $al);
$al_pref_arr = array();
$maxq = 0;
foreach($al_arr as $l) {
  //  echo "new lang seq: $l<br>";
  $l_arr = explode(';', $l);
  //  $lang = $l_arr[0]; //array_shift($l_arr);
  $lang = array_shift($l_arr);
  //  echo "Lang: $lang (" . print_r($l_arr, true) . ")<br>";
  $q = 1;
  foreach($l_arr as $param) {
    //    echo " new param seq: $param<br>";
    $param_arr = explode('=', $param);
    if (sizeof($param_arr) === 2) {
      if ($param_arr[0] === 'q') {
	$q = $param_arr[1];
      }
    }
  }
  $q = (int) ($q * 1000);
  if ($q > $maxq) {
    $maxq = $q;
  }
  $al_pref_arr[$q][] = $lang;
}
$probable_lang = $al_pref_arr[$maxq][0]; //$first_lang;
$probable_lang = htmlentities($probable_lang);
//echo ">>" . print_r($al_pref_arr, true) . "<<";
//echo ">>$probable_lang<<<br>";

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

// 
// investigate if user has uploaded new material
// u_ as in user
$u_username = $u_password = $u_languagecode = null;
$u_fr0001 = 
  $u_fr0101 = $u_fr0102 = $u_fr0103 = $u_fr0104 =
  $u_fr0105 = $u_fr0106 = $u_fr0107 = $u_fr0108 =
  $u_fr0109 = $u_fr0110 = $u_fr0111 = $u_fr0112 =
  $u_fr0301 = $u_fr0114 = $u_fr0115 = $u_fr0116 =
  $u_fr0117 = $u_fr0118 = $u_fr0119 = $u_fr0120 =
  $u_fr0121 = $u_fr0122 = $u_fr0123 = $u_fr0124 =
  $u_fr0125 = $u_fr0126 = $u_fr0127 = $u_fr0128 =
  $u_fr0129 = $u_fr0130 = $u_fr0131 = $u_fr0132 =
  $u_fr0133 = $u_fr0134 = $u_fr0135 = $u_fr0136 =
  $u_fr0137 = $u_fr0138 =
  $u_fr0139 = $u_fr0140 = $u_fr0141 = $u_fr0142 =
  $u_fr0143 = $u_fr0144 = $u_fr0145 = $u_fr0146 =
  $opt_ignore_blanks = null;
if (array_key_exists('ignore_blanks', $_POST))
  $opt_ignore_blanks = $_POST['ignore_blanks'];
if (array_key_exists('u_username', $_POST))
  $u_username = $_POST['u_username'];
if (array_key_exists('u_password', $_POST))
  $u_password = $_POST['u_password'];
if (array_key_exists('u_languagecode', $_POST))
  $u_languagecode = $_POST['u_languagecode'];
if (array_key_exists('u_fr0001', $_POST))
  $u_fr0001 = $_POST['u_fr0001'];

if (array_key_exists('u_fr0101', $_POST))
  $u_fr0101 = $_POST['u_fr0101'];
if (array_key_exists('u_fr0102', $_POST))
  $u_fr0102 = $_POST['u_fr0102'];
if (array_key_exists('u_fr0103', $_POST))
  $u_fr0103 = $_POST['u_fr0103'];
if (array_key_exists('u_fr0104', $_POST))
  $u_fr0104 = $_POST['u_fr0104'];

if (array_key_exists('u_fr0105', $_POST))
  $u_fr0105 = $_POST['u_fr0105'];
if (array_key_exists('u_fr0106', $_POST))
  $u_fr0106 = $_POST['u_fr0106'];
if (array_key_exists('u_fr0107', $_POST))
  $u_fr0107 = $_POST['u_fr0107'];
if (array_key_exists('u_fr0108', $_POST))
  $u_fr0108 = $_POST['u_fr0108'];

if (array_key_exists('u_fr0109', $_POST))
  $u_fr0109 = $_POST['u_fr0109'];
if (array_key_exists('u_fr0110', $_POST))
  $u_fr0110 = $_POST['u_fr0110'];
if (array_key_exists('u_fr0111', $_POST))
  $u_fr0111 = $_POST['u_fr0111'];
if (array_key_exists('u_fr0112', $_POST))
  $u_fr0112 = $_POST['u_fr0112'];

if (array_key_exists('u_fr0113', $_POST))
  $u_fr0113 = $_POST['u_fr0113'];
if (array_key_exists('u_fr0114', $_POST))
  $u_fr0114 = $_POST['u_fr0114'];
if (array_key_exists('u_fr0115', $_POST))
  $u_fr0115 = $_POST['u_fr0115'];
if (array_key_exists('u_fr0116', $_POST))
  $u_fr0116 = $_POST['u_fr0116'];

if (array_key_exists('u_fr0117', $_POST))
  $u_fr0117 = $_POST['u_fr0117'];
if (array_key_exists('u_fr0118', $_POST))
  $u_fr0118 = $_POST['u_fr0118'];
if (array_key_exists('u_fr0119', $_POST))
  $u_fr0119 = $_POST['u_fr0119'];
if (array_key_exists('u_fr0120', $_POST))
  $u_fr0120 = $_POST['u_fr0120'];

if (array_key_exists('u_fr0121', $_POST))
  $u_fr0121 = $_POST['u_fr0121'];
if (array_key_exists('u_fr0122', $_POST))
  $u_fr0122 = $_POST['u_fr0122'];
if (array_key_exists('u_fr0123', $_POST))
  $u_fr0123 = $_POST['u_fr0123'];
if (array_key_exists('u_fr0124', $_POST))
  $u_fr0124 = $_POST['u_fr0124'];

if (array_key_exists('u_fr0125', $_POST))
  $u_fr0125 = $_POST['u_fr0125'];
if (array_key_exists('u_fr0126', $_POST))
  $u_fr0126 = $_POST['u_fr0126'];
if (array_key_exists('u_fr0127', $_POST))
  $u_fr0127 = $_POST['u_fr0127'];
if (array_key_exists('u_fr0128', $_POST))
  $u_fr0128 = $_POST['u_fr0128'];

if (array_key_exists('u_fr0129', $_POST))
  $u_fr0129 = $_POST['u_fr0129'];
if (array_key_exists('u_fr0130', $_POST))
  $u_fr0130 = $_POST['u_fr0130'];
if (array_key_exists('u_fr0131', $_POST))
  $u_fr0131 = $_POST['u_fr0131'];
if (array_key_exists('u_fr0132', $_POST))
  $u_fr0132 = $_POST['u_fr0132'];

if (array_key_exists('u_fr0133', $_POST))
  $u_fr0133 = $_POST['u_fr0133'];
if (array_key_exists('u_fr0134', $_POST))
  $u_fr0134 = $_POST['u_fr0134'];
if (array_key_exists('u_fr0135', $_POST))
  $u_fr0135 = $_POST['u_fr0135'];
if (array_key_exists('u_fr0136', $_POST))
  $u_fr0136 = $_POST['u_fr0136'];

if (array_key_exists('u_fr0137', $_POST))
  $u_fr0137 = $_POST['u_fr0137'];
if (array_key_exists('u_fr0138', $_POST))
  $u_fr0138 = $_POST['u_fr0138'];

if (array_key_exists('u_fr0139', $_POST))
  $u_fr0139 = $_POST['u_fr0139'];
if (array_key_exists('u_fr0140', $_POST))
  $u_fr0140 = $_POST['u_fr0140'];
if (array_key_exists('u_fr0141', $_POST))
  $u_fr0141 = $_POST['u_fr0141'];
if (array_key_exists('u_fr0142', $_POST))
  $u_fr0142 = $_POST['u_fr0142'];

if (array_key_exists('u_fr0143', $_POST))
  $u_fr0143 = $_POST['u_fr0143'];
if (array_key_exists('u_fr0144', $_POST))
  $u_fr0144 = $_POST['u_fr0144'];
if (array_key_exists('u_fr0145', $_POST))
  $u_fr0145 = $_POST['u_fr0145'];
if (array_key_exists('u_fr0146', $_POST))
  $u_fr0146 = $_POST['u_fr0146'];

if ($u_username !== null && $u_password !== null && $u_languagecode !== null &&
    $u_fr0001 !== null && 
    $u_fr0101 !== null && $u_fr0102 !== null && $u_fr0103 !== null &&
    $u_fr0104 !== null && $u_fr0105 !== null && $u_fr0106 !== null &&
    $u_fr0107 !== null && $u_fr0108 !== null && $u_fr0109 !== null &&

    $u_fr0110 !== null && $u_fr0111 !== null && $u_fr0112 !== null && $u_fr0113 !== null &&
    $u_fr0114 !== null && $u_fr0115 !== null && $u_fr0116 !== null &&
    $u_fr0117 !== null && $u_fr0118 !== null && $u_fr0119 !== null &&

    $u_fr0120 !== null && $u_fr0121 !== null && $u_fr0122 !== null && $u_fr0123 !== null &&
    $u_fr0124 !== null && $u_fr0125 !== null && $u_fr0126 !== null &&
    $u_fr0127 !== null && $u_fr0128 !== null && $u_fr0129 !== null &&
    /*
      label reuse
    $u_fr0130 !== null && $u_fr0131 !== null && $u_fr0132 !== null && $u_fr0133 !== null &&
    $u_fr0134 !== null && 
    */
    $u_fr0135 !== null && 
    /*
      label reuse
    $u_fr0136 !== null && $u_fr0137 !== null && $u_fr0138 !== null && $u_fr0139 !== null &&
    $u_fr0140 !== null && 
    */
    $u_fr0141 !== null && // label reuse $u_fr0142 !== null && 
    $u_fr0143 !== null && // label reuse $u_fr0144 !== null && 
    $u_fr0145 !== null // label reuse && $u_fr0146 !== null
){
    
  // Assuming all well, the service will upload this new creation, 
  // create a link to it
  // and display to the user. He can then forward link to others, who'll
  //  then see what he uploaded.
  
  /*
    the link and display should already be doable then, as we have 'de'
    uploaded.
    we have the visitsid and the lang. The visitsid says who, the 
    lang says which. This precludes multiple suggestions. This can be
    settled by changing lang to a personal code which changes each time.
    But then this removes our ability to extract useful language data
    for others
    1. language code to take from browser?
    2. personal code

how would you assert that THESE xlates should be used and not THOSE?
/xlates/UPLOADERSID/newmarks/LANG/CRID
 so: reload indicating uploadersid*1; lang*1; and newmark1crid=CRID&newmark2crid=CRID etc
  */

  //  echo ">-$u_username-$u_password-$u_languagecode-$u_fr0001-$u_fr0101-$u_fr0102<";

  $apikey = '798e31c43d6b9f03aa504a6f88cb4550';
  
  $crid_fr0001 = 
    $crid_fr0101 = $crid_fr0102 = $crid_fr0103 = $crid_fr0104 = 
    $crid_fr0105 = $crid_fr0106 = $crid_fr0107 = $crid_fr0108 = 
    $crid_fr0109 = $crid_fr0110 = $crid_fr0111 = $crid_fr0112 = 
    $crid_fr0113 = $crid_fr0114 = $crid_fr0115 = $crid_fr0116 = 
    $crid_fr0117 = $crid_fr0118 = $crid_fr0119 = $crid_fr0120 = 
    $crid_fr0121 = $crid_fr0122 = $crid_fr0123 = $crid_fr0124 = 
    $crid_fr0125 = $crid_fr0126 = $crid_fr0127 = $crid_fr0128 = 
    $crid_fr0129 = $crid_fr0130 = $crid_fr0131 = $crid_fr0132 = 
    $crid_fr0133 = $crid_fr0134 = $crid_fr0135 = $crid_fr0136 = 
    $crid_fr0137 = $crid_fr0138 = 
    $crid_fr0139 = $crid_fr0140 = $crid_fr0141 = $crid_fr0142 = 
    $crid_fr0143 = $crid_fr0144 = $crid_fr0145 = $crid_fr0146 = '';

  $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0001', $u_languagecode, 'anonymous', $u_fr0001, $opt_ignore_blanks);
  if ($rv !== '') {
    preg_match('|.*/xlates/([^/]*)/.*$|', $rv, $matches);
    $uploadersid = $matches[1];
  }
  if ($rv !== '') {
    preg_match('|.*/([^/]*)$|', $rv, $matches);
    $crid_fr0001 = $matches[1];
  }
  
  $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0101', $u_languagecode, 'anonymous', $u_fr0101, $opt_ignore_blanks);
  if ($rv !== '') {
    preg_match('|.*/([^/]*)$|', $rv, $matches);
    $crid_fr0101 = $matches[1];
  }
  $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0102', $u_languagecode, 'anonymous', $u_fr0102, $opt_ignore_blanks);
  if ($rv !== '') {
    preg_match('|.*/([^/]*)$|', $rv, $matches);
    $crid_fr0102 = $matches[1];
  }
  $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0103', $u_languagecode, 'anonymous', $u_fr0103, $opt_ignore_blanks);
  if ($rv !== '') {
    preg_match('|.*/([^/]*)$|', $rv, $matches);
    $crid_fr0103 = $matches[1];
  }
  $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0104', $u_languagecode, 'anonymous', $u_fr0104, $opt_ignore_blanks);
  if ($rv !== '') {
    preg_match('|.*/([^/]*)$|', $rv, $matches);
    $crid_fr0104 = $matches[1];
  }
  $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0105', $u_languagecode, 'anonymous', $u_fr0105, $opt_ignore_blanks);
  if ($rv !== '') {
    preg_match('|.*/([^/]*)$|', $rv, $matches);
    $crid_fr0105 = $matches[1];
  }
  $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0106', $u_languagecode, 'anonymous', $u_fr0106, $opt_ignore_blanks);
  if ($rv !== '') {
    preg_match('|.*/([^/]*)$|', $rv, $matches);
    $crid_fr0106 = $matches[1];
  }
  $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0107', $u_languagecode, 'anonymous', $u_fr0107, $opt_ignore_blanks);
  if ($rv !== '') {
    preg_match('|.*/([^/]*)$|', $rv, $matches);
    $crid_fr0107 = $matches[1];
  }
  $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0108', $u_languagecode, 'anonymous', $u_fr0108, $opt_ignore_blanks);
  if ($rv !== '') {
    preg_match('|.*/([^/]*)$|', $rv, $matches);
    $crid_fr0108 = $matches[1];
  }


  $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0109', $u_languagecode, 'anonymous', $u_fr0109, $opt_ignore_blanks);
  if ($rv !== '') {
    preg_match('|.*/([^/]*)$|', $rv, $matches);
    $crid_fr0109 = $matches[1];
  }
  $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0110', $u_languagecode, 'anonymous', $u_fr0110, $opt_ignore_blanks);
  if ($rv !== '') {
    preg_match('|.*/([^/]*)$|', $rv, $matches);
    $crid_fr0110 = $matches[1];
  }
  $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0111', $u_languagecode, 'anonymous', $u_fr0111, $opt_ignore_blanks);
  if ($rv !== '') {
    preg_match('|.*/([^/]*)$|', $rv, $matches);
    $crid_fr0111 = $matches[1];
  }
  $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0112', $u_languagecode, 'anonymous', $u_fr0112, $opt_ignore_blanks);
  if ($rv !== '') {
    preg_match('|.*/([^/]*)$|', $rv, $matches);
    $crid_fr0112 = $matches[1];
  }

  $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0113', $u_languagecode, 'anonymous', $u_fr0113, $opt_ignore_blanks);
  if ($rv !== '') {
    preg_match('|.*/([^/]*)$|', $rv, $matches);
    $crid_fr0113 = $matches[1];
  }
  $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0114', $u_languagecode, 'anonymous', $u_fr0114, $opt_ignore_blanks);
  if ($rv !== '') {
    preg_match('|.*/([^/]*)$|', $rv, $matches);
    $crid_fr0114 = $matches[1];
  }
  $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0115', $u_languagecode, 'anonymous', $u_fr0115, $opt_ignore_blanks);
  if ($rv !== '') {
    preg_match('|.*/([^/]*)$|', $rv, $matches);
    $crid_fr0115 = $matches[1];
  }
  $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0116', $u_languagecode, 'anonymous', $u_fr0116, $opt_ignore_blanks);
  if ($rv !== '') {
    preg_match('|.*/([^/]*)$|', $rv, $matches);
    $crid_fr0116 = $matches[1];
  }

  $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0117', $u_languagecode, 'anonymous', $u_fr0117, $opt_ignore_blanks);
  if ($rv !== '') {
    preg_match('|.*/([^/]*)$|', $rv, $matches);
    $crid_fr0117 = $matches[1];
  }
  $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0118', $u_languagecode, 'anonymous', $u_fr0118, $opt_ignore_blanks);
  if ($rv !== '') {
    preg_match('|.*/([^/]*)$|', $rv, $matches);
    $crid_fr0118 = $matches[1];
  }
  $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0119', $u_languagecode, 'anonymous', $u_fr0119, $opt_ignore_blanks);
  if ($rv !== '') {
    preg_match('|.*/([^/]*)$|', $rv, $matches);
    $crid_fr0119 = $matches[1];
  }
  $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0120', $u_languagecode, 'anonymous', $u_fr0120, $opt_ignore_blanks);
  if ($rv !== '') {
    preg_match('|.*/([^/]*)$|', $rv, $matches);
    $crid_fr0120 = $matches[1];
  }

  $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0121', $u_languagecode, 'anonymous', $u_fr0121, $opt_ignore_blanks);
  if ($rv !== '') {
    preg_match('|.*/([^/]*)$|', $rv, $matches);
    $crid_fr0121 = $matches[1];
  }
  $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0122', $u_languagecode, 'anonymous', $u_fr0122, $opt_ignore_blanks);
  if ($rv !== '') {
    preg_match('|.*/([^/]*)$|', $rv, $matches);
    $crid_fr0122 = $matches[1];
  }
  $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0123', $u_languagecode, 'anonymous', $u_fr0123, $opt_ignore_blanks);
  if ($rv !== '') {
    preg_match('|.*/([^/]*)$|', $rv, $matches);
    $crid_fr0123 = $matches[1];
  }
  $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0124', $u_languagecode, 'anonymous', $u_fr0124, $opt_ignore_blanks);
  if ($rv !== '') {
    preg_match('|.*/([^/]*)$|', $rv, $matches);
    $crid_fr0124 = $matches[1];
  }

  $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0125', $u_languagecode, 'anonymous', $u_fr0125, $opt_ignore_blanks);
  if ($rv !== '') {
    preg_match('|.*/([^/]*)$|', $rv, $matches);
    $crid_fr0125 = $matches[1];
  }
  $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0126', $u_languagecode, 'anonymous', $u_fr0126, $opt_ignore_blanks);
  if ($rv !== '') {
    preg_match('|.*/([^/]*)$|', $rv, $matches);
    $crid_fr0126 = $matches[1];
  }

  $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0127', $u_languagecode, 'anonymous', $u_fr0127, $opt_ignore_blanks);
  if ($rv !== '') {
    preg_match('|.*/([^/]*)$|', $rv, $matches);
    $crid_fr0127 = $matches[1];
  }
  $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0128', $u_languagecode, 'anonymous', $u_fr0128, $opt_ignore_blanks);
  if ($rv !== '') {
    preg_match('|.*/([^/]*)$|', $rv, $matches);
    $crid_fr0128 = $matches[1];
  }
  $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0129', $u_languagecode, 'anonymous', $u_fr0129, $opt_ignore_blanks);
  if ($rv !== '') {
    preg_match('|.*/([^/]*)$|', $rv, $matches);
    $crid_fr0129 = $matches[1];
  }

  if (0) {
    //label reuse. Multiline comments fubared in emacs by regex so if (0) instead
    $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0130', $u_languagecode, 'anonymous', $u_fr0130, $opt_ignore_blanks);
    if ($rv !== '') {
      preg_match('|.*/([^/]*)$|', $rv, $matches);
      $crid_fr0130 = $matches[1];
    }
    $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0131', $u_languagecode, 'anonymous', $u_fr0131, $opt_ignore_blanks);
    if ($rv !== '') {
      preg_match('|.*/([^/]*)$|', $rv, $matches);
      $crid_fr0131 = $matches[1];
    }
    $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0132', $u_languagecode, 'anonymous', $u_fr0132, $opt_ignore_blanks);
    if ($rv !== '') {
      preg_match('|.*/([^/]*)$|', $rv, $matches);
      $crid_fr0132 = $matches[1];
    }

    $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0133', $u_languagecode, 'anonymous', $u_fr0133, $opt_ignore_blanks);
    if ($rv !== '') {
      preg_match('|.*/([^/]*)$|', $rv, $matches);
      $crid_fr0133 = $matches[1];
    }
    $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0134', $u_languagecode, 'anonymous', $u_fr0134, $opt_ignore_blanks);
    if ($rv !== '') {
      preg_match('|.*/([^/]*)$|', $rv, $matches);
      $crid_fr0134 = $matches[1];
    }
  }

  $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0135', $u_languagecode, 'anonymous', $u_fr0135, $opt_ignore_blanks);
  if ($rv !== '') {
    preg_match('|.*/([^/]*)$|', $rv, $matches);
    $crid_fr0135 = $matches[1];
  }
  if (0) {
    //label reuse. Multiline comments fubared in emacs by regex so if (0) instead
    $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0136', $u_languagecode, 'anonymous', $u_fr0136, $opt_ignore_blanks);
    if ($rv !== '') {
      preg_match('|.*/([^/]*)$|', $rv, $matches);
      $crid_fr0136 = $matches[1];
    }

    $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0137', $u_languagecode, 'anonymous', $u_fr0137, $opt_ignore_blanks);
    if ($rv !== '') {
      preg_match('|.*/([^/]*)$|', $rv, $matches);
      $crid_fr0137 = $matches[1];
    }
    $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0138', $u_languagecode, 'anonymous', $u_fr0138, $opt_ignore_blanks);
    if ($rv !== '') {
      preg_match('|.*/([^/]*)$|', $rv, $matches);
      $crid_fr0138 = $matches[1];
    }
    $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0139', $u_languagecode, 'anonymous', $u_fr0139, $opt_ignore_blanks);
    if ($rv !== '') {
      preg_match('|.*/([^/]*)$|', $rv, $matches);
      $crid_fr0139 = $matches[1];
    }
    $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0140', $u_languagecode, 'anonymous', $u_fr0140, $opt_ignore_blanks);
    if ($rv !== '') {
      preg_match('|.*/([^/]*)$|', $rv, $matches);
      $crid_fr0140 = $matches[1];
    }
  }
  $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0141', $u_languagecode, 'anonymous', $u_fr0141, $opt_ignore_blanks);
  if ($rv !== '') {
    preg_match('|.*/([^/]*)$|', $rv, $matches);
    $crid_fr0141 = $matches[1];
  }
  if (0) {
    //label reuse. Multiline comments fubared in emacs by regex so if (0) instead
    $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0142', $u_languagecode, 'anonymous', $u_fr0142, $opt_ignore_blanks);
    if ($rv !== '') {
      preg_match('|.*/([^/]*)$|', $rv, $matches);
      $crid_fr0142 = $matches[1];
    }
  }

  $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0143', $u_languagecode, 'anonymous', $u_fr0143, $opt_ignore_blanks);
  if ($rv !== '') {
    preg_match('|.*/([^/]*)$|', $rv, $matches);
    $crid_fr0143 = $matches[1];
  }
  if (0) {
    //label reuse. Multiline comments fubared in emacs by regex so if (0) instead
    $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0144', $u_languagecode, 'anonymous', $u_fr0144, $opt_ignore_blanks);
    if ($rv !== '') {
      preg_match('|.*/([^/]*)$|', $rv, $matches);
      $crid_fr0144 = $matches[1];
    }
  }

  $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0145', $u_languagecode, 'anonymous', $u_fr0145, $opt_ignore_blanks);
  if ($rv !== '') {
    preg_match('|.*/([^/]*)$|', $rv, $matches);
    $crid_fr0145 = $matches[1];
  }
  if (0) {
    //label reuse. Multiline comments fubared in emacs by regex so if (0) instead
    $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-flowcharts-fr0146', $u_languagecode, 'anonymous', $u_fr0146, $opt_ignore_blanks);
    if ($rv !== '') {
      preg_match('|.*/([^/]*)$|', $rv, $matches);
      $crid_fr0146 = $matches[1];
    }
  }

  $allcrids = "$crid_fr0101,$crid_fr0102,$crid_fr0103,$crid_fr0104,$crid_fr0105,$crid_fr0106,$crid_fr0107,$crid_fr0108,$crid_fr0109,";
  $allcrids .= "$crid_fr0110,$crid_fr0111,$crid_fr0112,$crid_fr0113,$crid_fr0114,$crid_fr0115,$crid_fr0116,$crid_fr0117,$crid_fr0118,$crid_fr0119,";
  $allcrids .= "$crid_fr0120,$crid_fr0121,$crid_fr0122,$crid_fr0123,$crid_fr0124,$crid_fr0125,$crid_fr0126,$crid_fr0127,$crid_fr0128,$crid_fr0129,";
  $allcrids .= "$crid_fr0130,$crid_fr0131,$crid_fr0132,$crid_fr0133,$crid_fr0134,$crid_fr0135,$crid_fr0136,$crid_fr0137,$crid_fr0138,$crid_fr0139,";
  $allcrids .= "$crid_fr0140,$crid_fr0141,$crid_fr0142,$crid_fr0143,$crid_fr0144,$crid_fr0145,$crid_fr0146";

  $u_languagecode = urlencode($u_languagecode);
  $link = $_SERVER['SCRIPT_URI'] . "?q=$uploadersid,$u_languagecode,$crid_fr0001," . $allcrids;

  echo "Your changes are at this link, and you can forward it to others!<br>";
  echo "<a href='$link' target=_blank>$link</a><br>";
  echo "<br>";

} else {
  $u_username = $u_password = $u_languagecode = $opt_ignore_blanks = null;
  $u_fr0001 = 
    $u_fr0101 = $u_fr0102 = $u_fr0103 = $u_fr0104 =
    $u_fr0105 = $u_fr0106 = $u_fr0107 = $u_fr0108 =
    $u_fr0109 = $u_fr0110 = $u_fr0111 = $u_fr0112 =
    $u_fr0113 = $u_fr0114 = $u_fr0115 = $u_fr0116 =
    $u_fr0117 = $u_fr0118 = $u_fr0119 = $u_fr0120 =
    $u_fr0121 = $u_fr0122 = $u_fr0123 = $u_fr0124 =
    $u_fr0125 = $u_fr0126 = $u_fr0127 = $u_fr0128 =
    $u_fr0129 = $u_fr0130 = $u_fr0131 = $u_fr0132 =
    $u_fr0133 = $u_fr0134 = $u_fr0135 = $u_fr0136 =
    $u_fr0137 = $u_fr0138 =
    $u_fr0139 = $u_fr0140 = $u_fr0141 = $u_fr0142 =
    $u_fr0143 = $u_fr0144 = $u_fr0145 = $u_fr0146 = null;
}

//
// look to see if this is a guest asking for an existing translation
$qs = $_SERVER['QUERY_STRING'];
$qs_arr = explode('=', $qs);
$guest_params = '';
if (sizeof($qs_arr) === 2 && $qs_arr[0] === 'q') {
  $guest_params = $qs_arr[1];
}

$g_uploadersid = $g_lang = null;
$g_newmarkfr0001 = 
  $g_newmarkfr0101 = $g_newmarkfr0102 = $g_newmarkfr0103 = $g_newmarkfr0104 = 
  $g_newmarkfr0105 = $g_newmarkfr0106 = $g_newmarkfr0107 = $g_newmarkfr0108 = 
  $g_newmarkfr0109 = $g_newmarkfr0110 = $g_newmarkfr0111 = $g_newmarkfr0112 = 
  $g_newmarkfr0113 = $g_newmarkfr0114 = $g_newmarkfr0115 = $g_newmarkfr0116 = 
  $g_newmarkfr0117 = $g_newmarkfr0118 = $g_newmarkfr0119 = $g_newmarkfr0120 = 
  $g_newmarkfr0121 = $g_newmarkfr0122 = $g_newmarkfr0123 = $g_newmarkfr0124 = 
  $g_newmarkfr0125 = $g_newmarkfr0126 = $g_newmarkfr0127 = $g_newmarkfr0128 = 
  $g_newmarkfr0129 = $g_newmarkfr0130 = $g_newmarkfr0131 = $g_newmarkfr0132 = 
  $g_newmarkfr0133 = $g_newmarkfr0134 = $g_newmarkfr0135 = $g_newmarkfr0136 = 
  $g_newmarkfr0137 = $g_newmarkfr0138 = 
  $g_newmarkfr0139 = $g_newmarkfr0140 = $g_newmarkfr0141 = $g_newmarkfr0142 = 
  $g_newmarkfr0143 = $g_newmarkfr0144 = $g_newmarkfr0145 = $g_newmarkfr0146 = null;

if ($guest_params !== '') {
  $guest_params_arr = explode(',', $guest_params);
  //  echo ">-" . print_r($guest_params_arr) . "-<" ;
  if (sizeof($guest_params_arr) === 49 ) {
    $g_uploadersid = $guest_params_arr[0];
    $g_lang = urldecode($guest_params_arr[1]);
    $g_newmarkfr0001 = $guest_params_arr[2];
    $g_newmarkfr0101 = $guest_params_arr[3];
    $g_newmarkfr0102 = $guest_params_arr[4];
    $g_newmarkfr0103 = $guest_params_arr[5];
    $g_newmarkfr0104 = $guest_params_arr[6];
    $g_newmarkfr0105 = $guest_params_arr[7];
    $g_newmarkfr0106 = $guest_params_arr[8];
    $g_newmarkfr0107 = $guest_params_arr[9];
    $g_newmarkfr0108 = $guest_params_arr[10];
    $g_newmarkfr0109 = $guest_params_arr[11];

    $g_newmarkfr0110 = $guest_params_arr[12];
    $g_newmarkfr0111 = $guest_params_arr[13];
    $g_newmarkfr0112 = $guest_params_arr[14];
    $g_newmarkfr0113 = $guest_params_arr[15];
    $g_newmarkfr0114 = $guest_params_arr[16];
    $g_newmarkfr0115 = $guest_params_arr[17];
    $g_newmarkfr0116 = $guest_params_arr[18];
    $g_newmarkfr0117 = $guest_params_arr[19];
    $g_newmarkfr0118 = $guest_params_arr[20];
    $g_newmarkfr0119 = $guest_params_arr[21];

    $g_newmarkfr0120 = $guest_params_arr[22];
    $g_newmarkfr0121 = $guest_params_arr[23];
    $g_newmarkfr0122 = $guest_params_arr[24];
    $g_newmarkfr0123 = $guest_params_arr[25];
    $g_newmarkfr0124 = $guest_params_arr[26];
    $g_newmarkfr0125 = $guest_params_arr[27];
    $g_newmarkfr0126 = $guest_params_arr[28];
    $g_newmarkfr0127 = $guest_params_arr[29];
    $g_newmarkfr0128 = $guest_params_arr[30];
    $g_newmarkfr0129 = $guest_params_arr[31];

    $g_newmarkfr0130 = $guest_params_arr[32];
    $g_newmarkfr0131 = $guest_params_arr[33];
    $g_newmarkfr0132 = $guest_params_arr[34];
    $g_newmarkfr0133 = $guest_params_arr[35];
    $g_newmarkfr0134 = $guest_params_arr[36];
    $g_newmarkfr0135 = $guest_params_arr[37];
    $g_newmarkfr0136 = $guest_params_arr[38];
    $g_newmarkfr0137 = $guest_params_arr[39];
    $g_newmarkfr0138 = $guest_params_arr[40];
    $g_newmarkfr0139 = $guest_params_arr[41];

    $g_newmarkfr0140 = $guest_params_arr[42];
    $g_newmarkfr0141 = $guest_params_arr[43];
    $g_newmarkfr0142 = $guest_params_arr[44];
    $g_newmarkfr0143 = $guest_params_arr[45];
    $g_newmarkfr0144 = $guest_params_arr[46];
    $g_newmarkfr0145 = $guest_params_arr[47];
    $g_newmarkfr0146 = $guest_params_arr[48];
  }
}

//    echo ">-$g_uploadersid-$g_lang-$g_newmarkfr0001-$g_newmarkfr0101-$g_newmarkfr0102<";
if ($g_uploadersid !== null && $g_lang !== null &&
    $g_newmarkfr0001 !== null && 
    $g_newmarkfr0101 !== null && $g_newmarkfr0102 !== null && $g_newmarkfr0103 !== null &&
    $g_newmarkfr0104 !== null && $g_newmarkfr0105 !== null && $g_newmarkfr0106 !== null &&
    $g_newmarkfr0107 !== null && $g_newmarkfr0108 !== null && $g_newmarkfr0109 !== null &&

    $g_newmarkfr0110 !== null && $g_newmarkfr0111 !== null && $g_newmarkfr0112 !== null && $g_newmarkfr0113 !== null &&
    $g_newmarkfr0114 !== null && $g_newmarkfr0115 !== null && $g_newmarkfr0116 !== null &&
    $g_newmarkfr0117 !== null && $g_newmarkfr0118 !== null && $g_newmarkfr0119 !== null &&

    $g_newmarkfr0120 !== null && $g_newmarkfr0121 !== null && $g_newmarkfr0122 !== null && $g_newmarkfr0123 !== null &&
    $g_newmarkfr0124 !== null && $g_newmarkfr0125 !== null && $g_newmarkfr0126 !== null &&
    $g_newmarkfr0127 !== null && $g_newmarkfr0128 !== null && $g_newmarkfr0129 !== null &&

    /*
      label reuse
    $g_newmarkfr0130 !== null && $g_newmarkfr0131 !== null && $g_newmarkfr0132 !== null && $g_newmarkfr0133 !== null &&
    $g_newmarkfr0134 !== null && 
    */
    $g_newmarkfr0135 !== null && 
    /*
      label reuse
      $g_newmarkfr0136 !== null &&
    $g_newmarkfr0137 !== null && $g_newmarkfr0138 !== null && $g_newmarkfr0139 !== null &&

    $g_newmarkfr0140 !== null && 
    */
    $g_newmarkfr0141 !== null && // label reuse $g_newmarkfr0142 !== null && 
    $g_newmarkfr0143 !== null &&  // label reuse   $g_newmarkfr0144 !== null && 
    $g_newmarkfr0145 !== null  // label reuse&& $g_newmarkfr0146 !== null
    ){
  //    echo ">-$g_uploadersid-$g_lang-$g_newmarkfr0001-$g_newmarkfr0101-$g_newmarkfr0102<";
} else {
  $g_uploadersid = $g_lang = null;
  $g_newmarkfr0001 = 
    $g_newmarkfr0101 = $g_newmarkfr0102 = $g_newmarkfr0103 = $g_newmarkfr0104 = 
    $g_newmarkfr0105 = $g_newmarkfr0106 = $g_newmarkfr0107 = $g_newmarkfr0108 = 
    $g_newmarkfr0109 = $g_newmarkfr0110 = $g_newmarkfr0111 = $g_newmarkfr0112 = 
    $g_newmarkfr0113 = $g_newmarkfr0114 = $g_newmarkfr0115 = $g_newmarkfr0116 = 
    $g_newmarkfr0117 = $g_newmarkfr0118 = $g_newmarkfr0119 = $g_newmarkfr0120 = 
    $g_newmarkfr0121 = $g_newmarkfr0122 = $g_newmarkfr0123 = $g_newmarkfr0124 = 
    $g_newmarkfr0125 = $g_newmarkfr0126 = $g_newmarkfr0127 = $g_newmarkfr0128 = 
    $g_newmarkfr0129 = $g_newmarkfr0130 = $g_newmarkfr0131 = $g_newmarkfr0132 = 
    $g_newmarkfr0133 = $g_newmarkfr0134 = $g_newmarkfr0135 = $g_newmarkfr0136 = 
    $g_newmarkfr0137 = $g_newmarkfr0138 = 
    $g_newmarkfr0139 = $g_newmarkfr0140 = $g_newmarkfr0141 = $g_newmarkfr0142 = 
    $g_newmarkfr0143 = $g_newmarkfr0144 = $g_newmarkfr0145 = $g_newmarkfr0146 = null;
}

if ($g_uploadersid === null) 
  $g_uploadersid = 'null';
if ($g_lang === null) 
  $g_lang = 'null';
if ($g_newmarkfr0001 === null) 
  $g_newmarkfr0001 = 'null';

if ($g_newmarkfr0101 === null) 
  $g_newmarkfr0101 = 'null';
if ($g_newmarkfr0102 === null) 
  $g_newmarkfr0102 = 'null';
if ($g_newmarkfr0103 === null) 
  $g_newmarkfr0103 = 'null';
if ($g_newmarkfr0104 === null) 
  $g_newmarkfr0104 = 'null';

if ($g_newmarkfr0105 === null) 
  $g_newmarkfr0105 = 'null';
if ($g_newmarkfr0106 === null) 
  $g_newmarkfr0106 = 'null';
if ($g_newmarkfr0107 === null) 
  $g_newmarkfr0107 = 'null';
if ($g_newmarkfr0108 === null) 
  $g_newmarkfr0108 = 'null';

if ($g_newmarkfr0109 === null) 
  $g_newmarkfr0109 = 'null';
if ($g_newmarkfr0110 === null) 
  $g_newmarkfr0110 = 'null';
if ($g_newmarkfr0111 === null) 
  $g_newmarkfr0111 = 'null';
if ($g_newmarkfr0112 === null) 
  $g_newmarkfr0112 = 'null';

if ($g_newmarkfr0113 === null) 
  $g_newmarkfr0113 = 'null';
if ($g_newmarkfr0114 === null) 
  $g_newmarkfr0114 = 'null';
if ($g_newmarkfr0115 === null) 
  $g_newmarkfr0115 = 'null';
if ($g_newmarkfr0116 === null) 
  $g_newmarkfr0116 = 'null';

if ($g_newmarkfr0117 === null) 
  $g_newmarkfr0117 = 'null';
if ($g_newmarkfr0118 === null) 
  $g_newmarkfr0118 = 'null';
if ($g_newmarkfr0119 === null) 
  $g_newmarkfr0119 = 'null';
if ($g_newmarkfr0120 === null) 
  $g_newmarkfr0120 = 'null';

if ($g_newmarkfr0121 === null) 
  $g_newmarkfr0121 = 'null';
if ($g_newmarkfr0122 === null) 
  $g_newmarkfr0122 = 'null';
if ($g_newmarkfr0123 === null) 
  $g_newmarkfr0123 = 'null';
if ($g_newmarkfr0124 === null) 
  $g_newmarkfr0124 = 'null';

if ($g_newmarkfr0125 === null) 
  $g_newmarkfr0125 = 'null';
if ($g_newmarkfr0126 === null) 
  $g_newmarkfr0126 = 'null';
if ($g_newmarkfr0127 === null) 
  $g_newmarkfr0127 = 'null';
if ($g_newmarkfr0128 === null) 
  $g_newmarkfr0128 = 'null';

if ($g_newmarkfr0129 === null) 
  $g_newmarkfr0129 = 'null';
if ($g_newmarkfr0130 === null) 
  $g_newmarkfr0130 = 'null';
if ($g_newmarkfr0131 === null) 
  $g_newmarkfr0131 = 'null';
if ($g_newmarkfr0132 === null) 
  $g_newmarkfr0132 = 'null';

if ($g_newmarkfr0133 === null) 
  $g_newmarkfr0133 = 'null';
if ($g_newmarkfr0134 === null) 
  $g_newmarkfr0134 = 'null';
if ($g_newmarkfr0135 === null) 
  $g_newmarkfr0135 = 'null';
if ($g_newmarkfr0136 === null) 
  $g_newmarkfr0136 = 'null';

if ($g_newmarkfr0137 === null) 
  $g_newmarkfr0137 = 'null';
if ($g_newmarkfr0138 === null) 
  $g_newmarkfr0138 = 'null';

if ($g_newmarkfr0139 === null) 
  $g_newmarkfr0139 = 'null';
if ($g_newmarkfr0140 === null) 
  $g_newmarkfr0140 = 'null';
if ($g_newmarkfr0141 === null) 
  $g_newmarkfr0141 = 'null';
if ($g_newmarkfr0142 === null) 
  $g_newmarkfr0142 = 'null';

if ($g_newmarkfr0143 === null) 
  $g_newmarkfr0143 = 'null';
if ($g_newmarkfr0144 === null) 
  $g_newmarkfr0144 = 'null';
if ($g_newmarkfr0145 === null) 
  $g_newmarkfr0145 = 'null';
if ($g_newmarkfr0146 === null) 
  $g_newmarkfr0146 = 'null';


include_once('../head_and_style.html');

echo "<script type='text/javascript'>\n".
"var global_g_uploadersid = '$g_uploadersid';\n".
"var global_g_lang = '$g_lang';\n".
"var global_g_newmarkfr0001 = '$g_newmarkfr0001';\n".

"var global_g_newmarkfr0101 = '$g_newmarkfr0101';\n".
"var global_g_newmarkfr0102 = '$g_newmarkfr0102';\n".
"var global_g_newmarkfr0103 = '$g_newmarkfr0103';\n".
"var global_g_newmarkfr0104 = '$g_newmarkfr0104';\n".

"var global_g_newmarkfr0105 = '$g_newmarkfr0105';\n".
"var global_g_newmarkfr0106 = '$g_newmarkfr0106';\n".
"var global_g_newmarkfr0107 = '$g_newmarkfr0107';\n".
"var global_g_newmarkfr0108 = '$g_newmarkfr0108';\n".

"var global_g_newmarkfr0109 = '$g_newmarkfr0109';\n".
"var global_g_newmarkfr0110 = '$g_newmarkfr0110';\n".
"var global_g_newmarkfr0111 = '$g_newmarkfr0111';\n".
"var global_g_newmarkfr0112 = '$g_newmarkfr0112';\n".

"var global_g_newmarkfr0113 = '$g_newmarkfr0113';\n".
"var global_g_newmarkfr0114 = '$g_newmarkfr0114';\n".
"var global_g_newmarkfr0115 = '$g_newmarkfr0115';\n".
"var global_g_newmarkfr0116 = '$g_newmarkfr0116';\n".

"var global_g_newmarkfr0117 = '$g_newmarkfr0117';\n".
"var global_g_newmarkfr0118 = '$g_newmarkfr0118';\n".
"var global_g_newmarkfr0119 = '$g_newmarkfr0119';\n".
"var global_g_newmarkfr0120 = '$g_newmarkfr0120';\n".

"var global_g_newmarkfr0121 = '$g_newmarkfr0121';\n".
"var global_g_newmarkfr0122 = '$g_newmarkfr0122';\n".
"var global_g_newmarkfr0123 = '$g_newmarkfr0123';\n".
"var global_g_newmarkfr0124 = '$g_newmarkfr0124';\n".

"var global_g_newmarkfr0125 = '$g_newmarkfr0125';\n".
"var global_g_newmarkfr0126 = '$g_newmarkfr0126';\n".
"var global_g_newmarkfr0127 = '$g_newmarkfr0127';\n".
"var global_g_newmarkfr0128 = '$g_newmarkfr0128';\n".

"var global_g_newmarkfr0129 = '$g_newmarkfr0129';\n".
"var global_g_newmarkfr0130 = '$g_newmarkfr0130';\n".
"var global_g_newmarkfr0131 = '$g_newmarkfr0131';\n".
"var global_g_newmarkfr0132 = '$g_newmarkfr0132';\n".

"var global_g_newmarkfr0133 = '$g_newmarkfr0133';\n".
"var global_g_newmarkfr0134 = '$g_newmarkfr0134';\n".
"var global_g_newmarkfr0135 = '$g_newmarkfr0135';\n".
"var global_g_newmarkfr0136 = '$g_newmarkfr0136';\n".

"var global_g_newmarkfr0137 = '$g_newmarkfr0137';\n".
"var global_g_newmarkfr0138 = '$g_newmarkfr0138';\n".

"var global_g_newmarkfr0139 = '$g_newmarkfr0139';\n".
"var global_g_newmarkfr0140 = '$g_newmarkfr0140';\n".
"var global_g_newmarkfr0141 = '$g_newmarkfr0141';\n".
"var global_g_newmarkfr0142 = '$g_newmarkfr0142';\n".

"var global_g_newmarkfr0143 = '$g_newmarkfr0143';\n".
"var global_g_newmarkfr0144 = '$g_newmarkfr0144';\n".
"var global_g_newmarkfr0145 = '$g_newmarkfr0145';\n".
"var global_g_newmarkfr0146 = '$g_newmarkfr0146';\n".

"</script>\n";

include_once('flowcharts_javascript.html');
include_once('index.html');
?>
