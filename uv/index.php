<?php
// uv

//
// Who has final moderation control over the content at whichever
// page/ site/ application/ webservice this is? That would normally
// be an account created by the developer. That account has a 
// SubscriberID (SID) associated with it, and that SID needs to
// known to the software here. 
$thisPageBelongsToSID = 92;

//
// The image lives in a div whose dimensions are below. Knowing
// the dimensions ahead of time is a cheap and tacky way of ensuring
// that the bottom edge of an element of an as yet unknown height
// can nevertheless be kept a certain distance from the top. For
// now the width is the width of the webcomic img, and the height
// sticks at 1 Kilopixels (Kibipixels?)
$container_width = 740;
$container_height = 1024;

//
// The apikey may be used to effect management of bad clients.
// Obtain your own from https://service.mpsvr.com/ rather than
// risk the one below getting revoked
$dflt_apikey = "e3c12c03cf320b243977d6ac389805de";

header("Content-Type: text/html; charset=utf-8");

function post_a_new($un, $pw, $apikey, $newmark, $lang, $vis, $trans, $opt_ignore_blanks, $thisPageBelongsToSID) {
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

    $out = "POST /newmarks/$thisPageBelongsToSID/$newmark HTTP/1.1\r\n";
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
    case (401) : //401 Unauthorised
      return 401;
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
$hal_lang = $al;
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
$u_fr0001 = $u_fr0002 = 
  $u_fr0101 = $u_fr0102 = $u_fr0103 = $u_fr0104 =
  $u_fr0105 = $u_fr0106 = $u_fr0107 = $u_fr0108 =
  $u_fr0201 = $u_fr0202 = $u_fr0203 = $u_fr0204 =
  $u_fr0301 = $u_fr0302 = $u_fr0303 = $u_fr0304 =
  $u_fr0401 = $u_fr0402 = $u_fr0403 = $u_fr0404 =
  $u_fr0501 = $u_fr0502 = $u_fr0503 = $u_fr0504 =
  $u_fr0601 = $u_fr0602 = $u_fr0603 = $u_fr0604 =
  $u_fr0701 = $u_fr0702 = $u_fr0703 = $u_fr0704 =
  $u_fr0801 = $u_fr0802 = $u_fr0803 = $u_fr0804 =
  $opt_ignore_blanks = null;
if (array_key_exists('ignore_blanks', $_POST))
  $opt_ignore_blanks = $_POST['ignore_blanks'];
if (array_key_exists('u_username', $_POST))
  $u_username = $_POST['u_username'];
if (array_key_exists('u_password', $_POST))
  $u_password = $_POST['u_password'];
if (array_key_exists('u_languagecode', $_POST))
  $u_languagecode = urlencode($_POST['u_languagecode']);
if (array_key_exists('u_fr0001', $_POST))
  $u_fr0001 = $_POST['u_fr0001'];
if (array_key_exists('u_fr0002', $_POST))
  $u_fr0002 = $_POST['u_fr0002'];

if (array_key_exists('u_fr0101', $_POST))
  $u_fr0101 = $_POST['u_fr0101'];
if (array_key_exists('u_fr0102', $_POST))
  $u_fr0102 = $_POST['u_fr0102'];
if (array_key_exists('u_fr0103', $_POST))
  $u_fr0103 = $_POST['u_fr0103'];
//if (array_key_exists('u_fr0104', $_POST))
//  $u_fr0104 = $_POST['u_fr0104'];

/*
if (array_key_exists('u_fr0201', $_POST))
  $u_fr0201 = $_POST['u_fr0201'];
if (array_key_exists('u_fr0202', $_POST))
  $u_fr0202 = $_POST['u_fr0202'];
if (array_key_exists('u_fr0203', $_POST))
  $u_fr0203 = $_POST['u_fr0203'];
if (array_key_exists('u_fr0204', $_POST))
  $u_fr0204 = $_POST['u_fr0204'];
*/

if (array_key_exists('u_fr0301', $_POST))
  $u_fr0301 = $_POST['u_fr0301'];
if (array_key_exists('u_fr0302', $_POST))
  $u_fr0302 = $_POST['u_fr0302'];
if (array_key_exists('u_fr0303', $_POST))
  $u_fr0303 = $_POST['u_fr0303'];
//if (array_key_exists('u_fr0304', $_POST))
//  $u_fr0304 = $_POST['u_fr0304'];

if (array_key_exists('u_fr0401', $_POST))
  $u_fr0401 = $_POST['u_fr0401'];
if (array_key_exists('u_fr0402', $_POST))
  $u_fr0402 = $_POST['u_fr0402'];
//if (array_key_exists('u_fr0403', $_POST))
//  $u_fr0403 = $_POST['u_fr0403'];
//if (array_key_exists('u_fr0404', $_POST))
//  $u_fr0404 = $_POST['u_fr0404'];

if (array_key_exists('u_fr0501', $_POST))
  $u_fr0501 = $_POST['u_fr0501'];
if (array_key_exists('u_fr0502', $_POST))
  $u_fr0502 = $_POST['u_fr0502'];
//if (array_key_exists('u_fr0503', $_POST))
//  $u_fr0503 = $_POST['u_fr0503'];
//if (array_key_exists('u_fr0504', $_POST))
//  $u_fr0504 = $_POST['u_fr0504'];

if (array_key_exists('u_fr0601', $_POST))
  $u_fr0601 = $_POST['u_fr0601'];
if (array_key_exists('u_fr0602', $_POST))
  $u_fr0602 = $_POST['u_fr0602'];
//if (array_key_exists('u_fr0603', $_POST))
//  $u_fr0603 = $_POST['u_fr0603'];
//if (array_key_exists('u_fr0604', $_POST))
//  $u_fr0604 = $_POST['u_fr0604'];

/*
if (array_key_exists('u_fr0701', $_POST))
  $u_fr0701 = $_POST['u_fr0701'];
if (array_key_exists('u_fr0702', $_POST))
  $u_fr0702 = $_POST['u_fr0702'];
if (array_key_exists('u_fr0703', $_POST))
  $u_fr0703 = $_POST['u_fr0703'];
if (array_key_exists('u_fr0704', $_POST))
  $u_fr0704 = $_POST['u_fr0704'];
*/

if (array_key_exists('u_fr0801', $_POST))
  $u_fr0801 = $_POST['u_fr0801'];
if (array_key_exists('u_fr0802', $_POST))
  $u_fr0802 = $_POST['u_fr0802'];
//if (array_key_exists('u_fr0803', $_POST))
//  $u_fr0803 = $_POST['u_fr0803'];
//if (array_key_exists('u_fr0804', $_POST))
//  $u_fr0804 = $_POST['u_fr0804'];


if ($u_username !== null && $u_password !== null && $u_languagecode !== null &&
    $u_fr0001 !== null && 
    $u_fr0101 !== null && $u_fr0102 !== null && $u_fr0103 !== null &&
    $u_fr0301 !== null && $u_fr0302 !== null && $u_fr0303 !== null && 
    $u_fr0401 !== null && $u_fr0402 !== null && 
    $u_fr0501 !== null && $u_fr0502 !== null && 
    $u_fr0601 !== null && $u_fr0602 !== null && 
    $u_fr0801 !== null && $u_fr0802 !== null){
    
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
  
  $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-uv-fr0001', $u_languagecode, 'anonymous', $u_fr0001, $opt_ignore_blanks, $thisPageBelongsToSID);
  if ($rv === 401) {
    echo "<font color=red>The username and/or password is wrong.</font> Please go back and check them!<br><br>\n";
  } else {
    if ($rv !== '') {
      preg_match('|.*/xlates/\d+,([^/]*)/.*$|', $rv, $matches);
      $uploadersid = $matches[1];
    }
    if ($rv !== '') {
      preg_match('|.*/([^/]*)$|', $rv, $matches);
      $crid_fr0001 = $matches[1];
    }
    if (0) {
      $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-uv-fr0002', $u_languagecode, 'anonymous', $u_fr0002, $opt_ignore_blanks, $thisPageBelongsToSID);
      if ($rv !== '') {
	preg_match('|.*/([^/]*)$|', $rv, $matches);
	$crid_fr0002 = $matches[1];
      }
    }

    $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-uv-fr0101', $u_languagecode, 'anonymous', $u_fr0101, $opt_ignore_blanks, $thisPageBelongsToSID);
    if ($rv !== '') {
      preg_match('|.*/([^/]*)$|', $rv, $matches);
      $crid_fr0101 = $matches[1];
    }
    $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-uv-fr0102', $u_languagecode, 'anonymous', $u_fr0102, $opt_ignore_blanks, $thisPageBelongsToSID);
    if ($rv !== '') {
      preg_match('|.*/([^/]*)$|', $rv, $matches);
      $crid_fr0102 = $matches[1];
    }
    $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-uv-fr0103', $u_languagecode, 'anonymous', $u_fr0103, $opt_ignore_blanks, $thisPageBelongsToSID);
    if ($rv !== '') {
      preg_match('|.*/([^/]*)$|', $rv, $matches);
      $crid_fr0103 = $matches[1];
    }

    $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-uv-fr0301', $u_languagecode, 'anonymous', $u_fr0301, $opt_ignore_blanks, $thisPageBelongsToSID);
    if ($rv !== '') {
      preg_match('|.*/([^/]*)$|', $rv, $matches);
      $crid_fr0301 = $matches[1];
    }
    $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-uv-fr0302', $u_languagecode, 'anonymous', $u_fr0302, $opt_ignore_blanks, $thisPageBelongsToSID);
    if ($rv !== '') {
      preg_match('|.*/([^/]*)$|', $rv, $matches);
      $crid_fr0302 = $matches[1];
    }
    $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-uv-fr0303', $u_languagecode, 'anonymous', $u_fr0303, $opt_ignore_blanks, $thisPageBelongsToSID);
    if ($rv !== '') {
      preg_match('|.*/([^/]*)$|', $rv, $matches);
      $crid_fr0303 = $matches[1];
    }

    $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-uv-fr0401', $u_languagecode, 'anonymous', $u_fr0401, $opt_ignore_blanks, $thisPageBelongsToSID);
    if ($rv !== '') {
      preg_match('|.*/([^/]*)$|', $rv, $matches);
      $crid_fr0401 = $matches[1];
    }
    $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-uv-fr0402', $u_languagecode, 'anonymous', $u_fr0402, $opt_ignore_blanks, $thisPageBelongsToSID);
    if ($rv !== '') {
      preg_match('|.*/([^/]*)$|', $rv, $matches);
      $crid_fr0402 = $matches[1];
    }

    $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-uv-fr0501', $u_languagecode, 'anonymous', $u_fr0501, $opt_ignore_blanks, $thisPageBelongsToSID);
    if ($rv !== '') {
      preg_match('|.*/([^/]*)$|', $rv, $matches);
      $crid_fr0501 = $matches[1];
    }
    $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-uv-fr0502', $u_languagecode, 'anonymous', $u_fr0502, $opt_ignore_blanks, $thisPageBelongsToSID);
    if ($rv !== '') {
      preg_match('|.*/([^/]*)$|', $rv, $matches);
      $crid_fr0502 = $matches[1];
    }

    $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-uv-fr0601', $u_languagecode, 'anonymous', $u_fr0601, $opt_ignore_blanks, $thisPageBelongsToSID);
    if ($rv !== '') {
      preg_match('|.*/([^/]*)$|', $rv, $matches);
      $crid_fr0601 = $matches[1];
    }
    $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-uv-fr0602', $u_languagecode, 'anonymous', $u_fr0602, $opt_ignore_blanks, $thisPageBelongsToSID);
    if ($rv !== '') {
      preg_match('|.*/([^/]*)$|', $rv, $matches);
      $crid_fr0602 = $matches[1];
    }

    $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-uv-fr0801', $u_languagecode, 'anonymous', $u_fr0801, $opt_ignore_blanks, $thisPageBelongsToSID);
    if ($rv !== '') {
      preg_match('|.*/([^/]*)$|', $rv, $matches);
      $crid_fr0801 = $matches[1];
    }
    $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-uv-fr0802', $u_languagecode, 'anonymous', $u_fr0802, $opt_ignore_blanks, $thisPageBelongsToSID);
    if ($rv !== '') {
      preg_match('|.*/([^/]*)$|', $rv, $matches);
      $crid_fr0802 = $matches[1];
    }

    $link = $_SERVER['SCRIPT_URI'] . "?q=$uploadersid,$u_languagecode,$crid_fr0001,$crid_fr0101,$crid_fr0102,$crid_fr0103,$crid_fr0301,$crid_fr0302,$crid_fr0303,$crid_fr0401,$crid_fr0402,$crid_fr0501,$crid_fr0502,$crid_fr0601,$crid_fr0602,$crid_fr0801,$crid_fr0802";

    echo "Your changes are at this link, and you can forward it to others!<br>";
    echo "<a href='$link' target=_blank>$link</a><br>";
    echo "<br>";
  }
} else {
  $u_username = $u_password = $u_languagecode = null;
  $u_fr0001 = $u_fr0002 = 
    $u_fr0101 = $u_fr0102 = $u_fr0103 = $u_fr0104 =
    $u_fr0105 = $u_fr0106 = $u_fr0107 = $u_fr0108 =
    $u_fr0201 = $u_fr0202 = $u_fr0203 = $u_fr0204 =
    $u_fr0301 = $u_fr0302 = $u_fr0303 = $u_fr0304 =
    $u_fr0401 = $u_fr0402 = $u_fr0403 = $u_fr0404 =
    $u_fr0501 = $u_fr0502 = $u_fr0503 = $u_fr0504 =
    $u_fr0601 = $u_fr0602 = $u_fr0603 = $u_fr0604 =
    $u_fr0701 = $u_fr0702 = $u_fr0703 = $u_fr0704 =
    $u_fr0801 = $u_fr0802 = $u_fr0803 = $u_fr0804 =
    null;
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
$g_newmarkfr0001 = $g_newmarkfr0002 = 
  $g_newmarkfr0101 = $g_newmarkfr0102 = $g_newmarkfr0103 = $g_newmarkfr0104 = 
  $g_newmarkfr0105 = $g_newmarkfr0106 = $g_newmarkfr0107 = $g_newmarkfr0108 = 
  $g_newmarkfr0201 = $g_newmarkfr0202 = $g_newmarkfr0203 = $g_newmarkfr0204 = 
  $g_newmarkfr0301 = $g_newmarkfr0302 = $g_newmarkfr0303 = $g_newmarkfr0304 = 
  $g_newmarkfr0401 = $g_newmarkfr0402 = $g_newmarkfr0403 = $g_newmarkfr0404 = 
  $g_newmarkfr0501 = $g_newmarkfr0502 = $g_newmarkfr0503 = $g_newmarkfr0504 = 
  $g_newmarkfr0601 = $g_newmarkfr0602 = $g_newmarkfr0603 = $g_newmarkfr0604 = 
  $g_newmarkfr0701 = $g_newmarkfr0702 = $g_newmarkfr0703 = $g_newmarkfr0704 = 
  $g_newmarkfr0801 = $g_newmarkfr0802 = $g_newmarkfr0803 = $g_newmarkfr0804 = 
  null;

if ($guest_params !== '') {
  $guest_params_arr = explode(',', $guest_params);
  if (sizeof($guest_params_arr) === 17) {
    $g_uploadersid = $guest_params_arr[0];
    $g_lang = $guest_params_arr[1];
    $g_newmarkfr0001 = $guest_params_arr[2];
    $g_newmarkfr0101 = $guest_params_arr[3];
    $g_newmarkfr0102 = $guest_params_arr[4];
    $g_newmarkfr0103 = $guest_params_arr[5];
    $g_newmarkfr0301 = $guest_params_arr[6];
    $g_newmarkfr0302 = $guest_params_arr[7];
    $g_newmarkfr0303 = $guest_params_arr[8];
    $g_newmarkfr0401 = $guest_params_arr[9];
    $g_newmarkfr0402 = $guest_params_arr[10];
    $g_newmarkfr0501 = $guest_params_arr[11];
    $g_newmarkfr0502 = $guest_params_arr[12];
    $g_newmarkfr0601 = $guest_params_arr[13];
    $g_newmarkfr0602 = $guest_params_arr[14];
    $g_newmarkfr0801 = $guest_params_arr[15];
    $g_newmarkfr0802 = $guest_params_arr[16];
  }
}

if ($g_uploadersid !== null && $g_lang !== null &&
    $g_newmarkfr0001 !== null && 
    $g_newmarkfr0101 !== null && $g_newmarkfr0102 !== null && $g_newmarkfr0103 !== null &&
    $g_newmarkfr0301 !== null && $g_newmarkfr0302 !== null && $g_newmarkfr0303 !== null && 
    $g_newmarkfr0401 !== null && $g_newmarkfr0402 !== null && 
    $g_newmarkfr0501 !== null && $g_newmarkfr0502 !== null && 
    $g_newmarkfr0601 !== null && $g_newmarkfr0602 !== null && 
    $g_newmarkfr0801 !== null && $g_newmarkfr0802 !== null){
  //  echo ">-$g_uploadersid-$g_lang-$g_newmarkfr0001-$g_newmarkfr0101-$g_newmarkfr0102<";
} else {
  $g_uploadersid = $g_lang = null;
  $g_newmarkfr0001 = $g_newmarkfr0002 = 
    $g_newmarkfr0101 = $g_newmarkfr0102 = $g_newmarkfr0103 = $g_newmarkfr0104 = 
    $g_newmarkfr0105 = $g_newmarkfr0106 = $g_newmarkfr0107 = $g_newmarkfr0108 = 
    $g_newmarkfr0201 = $g_newmarkfr0202 = $g_newmarkfr0203 = $g_newmarkfr0204 = 
    $g_newmarkfr0301 = $g_newmarkfr0302 = $g_newmarkfr0303 = $g_newmarkfr0304 = 
    $g_newmarkfr0401 = $g_newmarkfr0402 = $g_newmarkfr0403 = $g_newmarkfr0404 = 
    $g_newmarkfr0501 = $g_newmarkfr0502 = $g_newmarkfr0503 = $g_newmarkfr0504 = 
    $g_newmarkfr0601 = $g_newmarkfr0602 = $g_newmarkfr0603 = $g_newmarkfr0604 = 
    $g_newmarkfr0701 = $g_newmarkfr0702 = $g_newmarkfr0703 = $g_newmarkfr0704 = 
    $g_newmarkfr0801 = $g_newmarkfr0802 = $g_newmarkfr0803 = $g_newmarkfr0804 = 
    null;
}

if ($g_uploadersid === null) 
  $g_uploadersid = 'null';
if ($g_lang === null) 
  $g_lang = 'null';
if ($g_newmarkfr0001 === null) 
  $g_newmarkfr0001 = 'null';
if ($g_newmarkfr0002 === null) 
  $g_newmarkfr0002 = 'null';

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

if ($g_newmarkfr0201 === null) 
  $g_newmarkfr0201 = 'null';
if ($g_newmarkfr0202 === null) 
  $g_newmarkfr0202 = 'null';
if ($g_newmarkfr0203 === null) 
  $g_newmarkfr0203 = 'null';
if ($g_newmarkfr0204 === null) 
  $g_newmarkfr0204 = 'null';

if ($g_newmarkfr0301 === null) 
  $g_newmarkfr0301 = 'null';
if ($g_newmarkfr0302 === null) 
  $g_newmarkfr0302 = 'null';
if ($g_newmarkfr0303 === null) 
  $g_newmarkfr0303 = 'null';
if ($g_newmarkfr0304 === null) 
  $g_newmarkfr0304 = 'null';

if ($g_newmarkfr0401 === null) 
  $g_newmarkfr0401 = 'null';
if ($g_newmarkfr0402 === null) 
  $g_newmarkfr0402 = 'null';
if ($g_newmarkfr0403 === null) 
  $g_newmarkfr0403 = 'null';
if ($g_newmarkfr0404 === null) 
  $g_newmarkfr0404 = 'null';

if ($g_newmarkfr0501 === null) 
  $g_newmarkfr0501 = 'null';
if ($g_newmarkfr0502 === null) 
  $g_newmarkfr0502 = 'null';
if ($g_newmarkfr0503 === null) 
  $g_newmarkfr0503 = 'null';
if ($g_newmarkfr0504 === null) 
  $g_newmarkfr0504 = 'null';

if ($g_newmarkfr0601 === null) 
  $g_newmarkfr0601 = 'null';
if ($g_newmarkfr0602 === null) 
  $g_newmarkfr0602 = 'null';
if ($g_newmarkfr0603 === null) 
  $g_newmarkfr0603 = 'null';
if ($g_newmarkfr0604 === null) 
  $g_newmarkfr0604 = 'null';

if ($g_newmarkfr0701 === null) 
  $g_newmarkfr0701 = 'null';
if ($g_newmarkfr0702 === null) 
  $g_newmarkfr0702 = 'null';
if ($g_newmarkfr0703 === null) 
  $g_newmarkfr0703 = 'null';
if ($g_newmarkfr0704 === null) 
  $g_newmarkfr0704 = 'null';

if ($g_newmarkfr0801 === null) 
  $g_newmarkfr0801 = 'null';
if ($g_newmarkfr0802 === null) 
  $g_newmarkfr0802 = 'null';
if ($g_newmarkfr0803 === null) 
  $g_newmarkfr0803 = 'null';
if ($g_newmarkfr0804 === null) 
  $g_newmarkfr0804 = 'null';

include_once('../head_and_style.html');

echo "<script type='text/javascript'>\n".
"var global_g_visitsid = '$thisPageBelongsToSID';\n" .
"var global_g_uploadersid = '$g_uploadersid';\n".
"var global_g_lang = '$g_lang';\n".
"var global_container_width = '$container_width';\n".
"var global_container_height = '$container_height';\n".
"var global_frame_array = '';\n" .

"var global_g_newmarkfr0001 = '$g_newmarkfr0001';\n".
"var global_g_newmarkfr0002 = '$g_newmarkfr0002';\n".

"var global_g_newmarkfr0101 = '$g_newmarkfr0101';\n".
"var global_g_newmarkfr0102 = '$g_newmarkfr0102';\n".
"var global_g_newmarkfr0103 = '$g_newmarkfr0103';\n".
"var global_g_newmarkfr0104 = '$g_newmarkfr0104';\n".
"var global_g_newmarkfr0105 = '$g_newmarkfr0105';\n".
"var global_g_newmarkfr0106 = '$g_newmarkfr0106';\n".
"var global_g_newmarkfr0107 = '$g_newmarkfr0107';\n".
"var global_g_newmarkfr0108 = '$g_newmarkfr0108';\n".

"var global_g_newmarkfr0109 = '';\n".
"var global_g_newmarkfr0110 = '';\n".
"var global_g_newmarkfr0111 = '';\n".
"var global_g_newmarkfr0112 = '';\n".
"var global_g_newmarkfr0113 = '';\n".
"var global_g_newmarkfr0114 = '';\n".
"var global_g_newmarkfr0115 = '';\n".
"var global_g_newmarkfr0116 = '';\n".

"var global_g_newmarkfr0201 = '$g_newmarkfr0201';\n".
"var global_g_newmarkfr0202 = '$g_newmarkfr0202';\n".
"var global_g_newmarkfr0203 = '$g_newmarkfr0203';\n".
"var global_g_newmarkfr0204 = '$g_newmarkfr0204';\n".

"var global_g_newmarkfr0301 = '$g_newmarkfr0301';\n".
"var global_g_newmarkfr0302 = '$g_newmarkfr0302';\n".
"var global_g_newmarkfr0303 = '$g_newmarkfr0303';\n".
"var global_g_newmarkfr0304 = '$g_newmarkfr0304';\n".

"var global_g_newmarkfr0401 = '$g_newmarkfr0401';\n".
"var global_g_newmarkfr0402 = '$g_newmarkfr0402';\n".
"var global_g_newmarkfr0403 = '$g_newmarkfr0403';\n".
"var global_g_newmarkfr0404 = '$g_newmarkfr0404';\n".

"var global_g_newmarkfr0501 = '$g_newmarkfr0501';\n".
"var global_g_newmarkfr0502 = '$g_newmarkfr0502';\n".
"var global_g_newmarkfr0503 = '$g_newmarkfr0503';\n".
"var global_g_newmarkfr0504 = '$g_newmarkfr0504';\n".

"var global_g_newmarkfr0601 = '$g_newmarkfr0601';\n".
"var global_g_newmarkfr0602 = '$g_newmarkfr0602';\n".
"var global_g_newmarkfr0603 = '$g_newmarkfr0603';\n".
"var global_g_newmarkfr0604 = '$g_newmarkfr0604';\n".

"var global_g_newmarkfr0701 = '$g_newmarkfr0701';\n".
"var global_g_newmarkfr0702 = '$g_newmarkfr0702';\n".
"var global_g_newmarkfr0703 = '$g_newmarkfr0703';\n".
"var global_g_newmarkfr0704 = '$g_newmarkfr0704';\n".

"var global_g_newmarkfr0801 = '$g_newmarkfr0801';\n".
"var global_g_newmarkfr0802 = '$g_newmarkfr0802';\n".
"var global_g_newmarkfr0803 = '$g_newmarkfr0803';\n".
"var global_g_newmarkfr0804 = '$g_newmarkfr0804';\n".
"</script>\n";

include_once('../common_javascript_v2.html');
include_once('index.html');
?>
