<?php
// long_light

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
$container_width = 640;
$container_height = 1024;

//
// The apikey may be used to effect management of bad clients.
// Obtain your own from https://service.mpsvr.com/ rather than
// risk the one below getting revoked
$dflt_apikey = "e3c12c03cf320b243977d6ac389805de";

//
// Frames and elements. Element[0] usually 2 as there is both
// a title, and a mouseover text. Element[1] refers to frame 01
// of the webcomic: how many text elements are within? Three
// speech bubbles = 3. Assumption of upto 8 frames, so frame_array
// must be 9 elements long: pad with zeros
$frame_array = array(2, 2, 3, 1, 2, 1, 3, 0, 0);

//
// UTF-8 bc unicode and reasons; head and lack of style amirite
header("Content-Type: text/html; charset=utf-8");
include_once('../head_and_style.html');

//
// This is the website itself communicating with the backend.
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

//
// We need to know the uploader's SID is in order to construct the 
// permalink. We also need to know the record's CRID for the same
// reason.
function handlePOSTResponse($rv, &$crid, &$uploadersid) {
  if ($rv === '') return;
  if (preg_match('|.*/([^/]*)$|', $rv, $matches) === 1) {
    if ($crid === '') {
      $crid = $matches[1];
    }
    preg_match('|.*/xlates/\d+,([^/]*)/.*$|', $rv, $matches);
    if ($uploadersid === '') {
      $uploadersid = $matches[1];
    }
  }
  return $rv;
}

/////////////
/////////////

//
// main body of code

/////////////
/////////////

//
// Discover our visitor's AL for later use
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

//
// Also discover if visitor has a permalink
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
// investigate if user is trying to upload for a permalink
// u_ as in user
//-- Doc: handle POSTS
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

if (array_key_exists('ignore_blanks', $_POST)) $opt_ignore_blanks = $_POST['ignore_blanks'];
if (array_key_exists('u_username', $_POST)) $u_username = $_POST['u_username'];
if (array_key_exists('u_password', $_POST)) $u_password = $_POST['u_password'];
if (array_key_exists('u_languagecode', $_POST)) $u_languagecode = urlencode($_POST['u_languagecode']);

if ($frame_array[0] > 0 && array_key_exists('u_fr0001', $_POST)) $u_fr0001 = $_POST['u_fr0001'];
if ($frame_array[0] > 0 && array_key_exists('u_fr0002', $_POST)) $u_fr0002 = $_POST['u_fr0002'];

if ($frame_array[1] > 0 && array_key_exists('u_fr0101', $_POST)) $u_fr0101 = $_POST['u_fr0101'];
if ($frame_array[1] > 1 && array_key_exists('u_fr0102', $_POST)) $u_fr0102 = $_POST['u_fr0102'];
if ($frame_array[1] > 2 && array_key_exists('u_fr0103', $_POST)) $u_fr0103 = $_POST['u_fr0103'];
if ($frame_array[1] > 3 && array_key_exists('u_fr0104', $_POST)) $u_fr0104 = $_POST['u_fr0104'];
if ($frame_array[1] > 4 && array_key_exists('u_fr0105', $_POST)) $u_fr0105 = $_POST['u_fr0105'];
if ($frame_array[1] > 5 && array_key_exists('u_fr0106', $_POST)) $u_fr0106 = $_POST['u_fr0106'];
if ($frame_array[1] > 6 && array_key_exists('u_fr0107', $_POST)) $u_fr0107 = $_POST['u_fr0107'];
if ($frame_array[1] > 7 && array_key_exists('u_fr0108', $_POST)) $u_fr0108 = $_POST['u_fr0108'];

if ($frame_array[2] > 0 && array_key_exists('u_fr0201', $_POST)) $u_fr0201 = $_POST['u_fr0201'];
if ($frame_array[2] > 1 && array_key_exists('u_fr0202', $_POST)) $u_fr0202 = $_POST['u_fr0202'];
if ($frame_array[2] > 2 && array_key_exists('u_fr0203', $_POST)) $u_fr0203 = $_POST['u_fr0203'];
if ($frame_array[2] > 3 && array_key_exists('u_fr0204', $_POST)) $u_fr0204 = $_POST['u_fr0204'];

if ($frame_array[3] > 0 && array_key_exists('u_fr0301', $_POST)) $u_fr0301 = $_POST['u_fr0301'];
if ($frame_array[3] > 1 && array_key_exists('u_fr0302', $_POST)) $u_fr0302 = $_POST['u_fr0302'];
if ($frame_array[3] > 2 && array_key_exists('u_fr0303', $_POST)) $u_fr0303 = $_POST['u_fr0303'];
if ($frame_array[3] > 3 && array_key_exists('u_fr0304', $_POST)) $u_fr0304 = $_POST['u_fr0304'];

if ($frame_array[4] > 0 && array_key_exists('u_fr0401', $_POST)) $u_fr0401 = $_POST['u_fr0401'];
if ($frame_array[4] > 1 && array_key_exists('u_fr0402', $_POST)) $u_fr0402 = $_POST['u_fr0402'];
if ($frame_array[4] > 2 && array_key_exists('u_fr0403', $_POST)) $u_fr0403 = $_POST['u_fr0403'];
if ($frame_array[4] > 3 && array_key_exists('u_fr0404', $_POST)) $u_fr0404 = $_POST['u_fr0404'];

if ($frame_array[5] > 0 && array_key_exists('u_fr0501', $_POST)) $u_fr0501 = $_POST['u_fr0501'];
if ($frame_array[5] > 1 && array_key_exists('u_fr0502', $_POST)) $u_fr0502 = $_POST['u_fr0502'];
if ($frame_array[5] > 2 && array_key_exists('u_fr0503', $_POST)) $u_fr0503 = $_POST['u_fr0503'];
if ($frame_array[5] > 3 && array_key_exists('u_fr0504', $_POST)) $u_fr0504 = $_POST['u_fr0504'];

if ($frame_array[6] > 0 && array_key_exists('u_fr0601', $_POST)) $u_fr0601 = $_POST['u_fr0601'];
if ($frame_array[6] > 1 && array_key_exists('u_fr0602', $_POST)) $u_fr0602 = $_POST['u_fr0602'];
if ($frame_array[6] > 2 && array_key_exists('u_fr0603', $_POST)) $u_fr0603 = $_POST['u_fr0603'];
if ($frame_array[6] > 3 && array_key_exists('u_fr0604', $_POST)) $u_fr0604 = $_POST['u_fr0604'];

if ($frame_array[7] > 0 && array_key_exists('u_fr0701', $_POST)) $u_fr0701 = $_POST['u_fr0701'];
if ($frame_array[7] > 1 && array_key_exists('u_fr0702', $_POST)) $u_fr0702 = $_POST['u_fr0702'];
if ($frame_array[7] > 2 && array_key_exists('u_fr0703', $_POST)) $u_fr0703 = $_POST['u_fr0703'];
if ($frame_array[7] > 3 && array_key_exists('u_fr0704', $_POST)) $u_fr0704 = $_POST['u_fr0704'];

if ($frame_array[8] > 0 && array_key_exists('u_fr0801', $_POST)) $u_fr0801 = $_POST['u_fr0801'];
if ($frame_array[8] > 1 && array_key_exists('u_fr0802', $_POST)) $u_fr0802 = $_POST['u_fr0802'];
if ($frame_array[8] > 2 && array_key_exists('u_fr0803', $_POST)) $u_fr0803 = $_POST['u_fr0803'];
if ($frame_array[8] > 3 && array_key_exists('u_fr0804', $_POST)) $u_fr0804 = $_POST['u_fr0804'];

//-- Doc: check if enough to POST
//
// Only accept for upload if all fields are presented. 
if ($u_username !== null && $u_password !== null && $u_languagecode !== null &&
    $u_fr0001 !== null && $u_fr0002 !== null && 
    $u_fr0101 !== null && $u_fr0102 !== null && 
    $u_fr0201 !== null && $u_fr0202 !== null && $u_fr0203 !== null && 
    $u_fr0301 !== null &&
    $u_fr0401 !== null && $u_fr0402 !== null && 
    $u_fr0501 !== null &&
    $u_fr0601 !== null && $u_fr0602 !== null && $u_fr0603 !== null
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
  
  $crid_fr0001 = $crid_fr0002 = 
    $crid_fr0101 = $crid_fr0102 = $crid_fr0103 = $crid_fr0104 = 
    $crid_fr0105 = $crid_fr0106 = $crid_fr0107 = $crid_fr0108 = 
    $crid_fr0201 = $crid_fr0202 = $crid_fr0203 = $crid_fr0204 = 
    $crid_fr0301 = $crid_fr0302 = $crid_fr0303 = $crid_fr0304 = 
    $crid_fr0401 = $crid_fr0402 = $crid_fr0403 = $crid_fr0404 = 
    $crid_fr0501 = $crid_fr0502 = $crid_fr0503 = $crid_fr0504 = 
    $crid_fr0601 = $crid_fr0602 = $crid_fr0603 = $crid_fr0604 = 
    $crid_fr0701 = $crid_fr0702 = $crid_fr0703 = $crid_fr0704 = 
    $crid_fr0801 = $crid_fr0802 = $crid_fr0803 = $crid_fr0804 = '';

  //-- Doc: perform POSTs
    
  $rv = 0;
  $uploadersid = '';
  if ($rv !== 401 && $frame_array[0] > 0) $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-long_light-fr0001', $u_languagecode, 'anonymous', $u_fr0001, $opt_ignore_blanks, $thisPageBelongsToSID);
  handlePOSTResponse($rv, $crid_fr0001, $uploadersid);
  if ($rv !== 401 && $frame_array[0] > 1) $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-long_light-fr0002', $u_languagecode, 'anonymous', $u_fr0001, $opt_ignore_blanks, $thisPageBelongsToSID);
  handlePOSTResponse($rv, $crid_fr0002, $uploadersid);

  if ($rv !== 401 && $frame_array[1] > 0) $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-long_light-fr0101', $u_languagecode, 'anonymous', $u_fr0101, $opt_ignore_blanks, $thisPageBelongsToSID);
  handlePOSTResponse($rv, $crid_fr0101, $uploadersid);
  if ($rv !== 401 && $frame_array[1] > 1) $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-long_light-fr0102', $u_languagecode, 'anonymous', $u_fr0102, $opt_ignore_blanks, $thisPageBelongsToSID);
  handlePOSTResponse($rv, $crid_fr0102, $uploadersid);
  if ($rv !== 401 && $frame_array[1] > 2) $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-long_light-fr0103', $u_languagecode, 'anonymous', $u_fr0103, $opt_ignore_blanks, $thisPageBelongsToSID);
  handlePOSTResponse($rv, $crid_fr0103, $uploadersid);
  if ($rv !== 401 && $frame_array[1] > 3) $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-long_light-fr0104', $u_languagecode, 'anonymous', $u_fr0104, $opt_ignore_blanks, $thisPageBelongsToSID);
  handlePOSTResponse($rv, $crid_fr0104, $uploadersid);
  if ($rv !== 401 && $frame_array[1] > 4) $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-long_light-fr0105', $u_languagecode, 'anonymous', $u_fr0105, $opt_ignore_blanks, $thisPageBelongsToSID);
  handlePOSTResponse($rv, $crid_fr0105, $uploadersid);
  if ($rv !== 401 && $frame_array[1] > 5) $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-long_light-fr0106', $u_languagecode, 'anonymous', $u_fr0106, $opt_ignore_blanks, $thisPageBelongsToSID);
  handlePOSTResponse($rv, $crid_fr0106, $uploadersid);
  if ($rv !== 401 && $frame_array[1] > 6) $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-long_light-fr0107', $u_languagecode, 'anonymous', $u_fr0107, $opt_ignore_blanks, $thisPageBelongsToSID);
  handlePOSTResponse($rv, $crid_fr0107, $uploadersid);
  if ($rv !== 401 && $frame_array[1] > 7) $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-long_light-fr0108', $u_languagecode, 'anonymous', $u_fr0108, $opt_ignore_blanks, $thisPageBelongsToSID);
  handlePOSTResponse($rv, $crid_fr0108, $uploadersid);

  if ($rv !== 401 && $frame_array[2] > 0) $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-long_light-fr0201', $u_languagecode, 'anonymous', $u_fr0201, $opt_ignore_blanks, $thisPageBelongsToSID);
  handlePOSTResponse($rv, $crid_fr0201, $uploadersid);
  if ($rv !== 401 && $frame_array[2] > 1) $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-long_light-fr0202', $u_languagecode, 'anonymous', $u_fr0202, $opt_ignore_blanks, $thisPageBelongsToSID);
  handlePOSTResponse($rv, $crid_fr0202, $uploadersid);
  if ($rv !== 401 && $frame_array[2] > 2) $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-long_light-fr0203', $u_languagecode, 'anonymous', $u_fr0203, $opt_ignore_blanks, $thisPageBelongsToSID);
  handlePOSTResponse($rv, $crid_fr0203, $uploadersid);
  if ($rv !== 401 && $frame_array[2] > 3) $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-long_light-fr0204', $u_languagecode, 'anonymous', $u_fr0204, $opt_ignore_blanks, $thisPageBelongsToSID);
  handlePOSTResponse($rv, $crid_fr0204, $uploadersid);

  if ($rv !== 401 && $frame_array[3] > 0) $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-long_light-fr0301', $u_languagecode, 'anonymous', $u_fr0301, $opt_ignore_blanks, $thisPageBelongsToSID);
  handlePOSTResponse($rv, $crid_fr0301, $uploadersid);
  if ($rv !== 401 && $frame_array[3] > 1) $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-long_light-fr0302', $u_languagecode, 'anonymous', $u_fr0302, $opt_ignore_blanks, $thisPageBelongsToSID);
  handlePOSTResponse($rv, $crid_fr0302, $uploadersid);
  if ($rv !== 401 && $frame_array[3] > 2) $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-long_light-fr0303', $u_languagecode, 'anonymous', $u_fr0303, $opt_ignore_blanks, $thisPageBelongsToSID);
  handlePOSTResponse($rv, $crid_fr0303, $uploadersid);
  if ($rv !== 401 && $frame_array[3] > 3) $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-long_light-fr0304', $u_languagecode, 'anonymous', $u_fr0304, $opt_ignore_blanks, $thisPageBelongsToSID);
  handlePOSTResponse($rv, $crid_fr0304, $uploadersid);

  if ($rv !== 401 && $frame_array[4] > 0) $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-long_light-fr0401', $u_languagecode, 'anonymous', $u_fr0401, $opt_ignore_blanks, $thisPageBelongsToSID);
  handlePOSTResponse($rv, $crid_fr0401, $uploadersid);
  if ($rv !== 401 && $frame_array[4] > 1) $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-long_light-fr0402', $u_languagecode, 'anonymous', $u_fr0402, $opt_ignore_blanks, $thisPageBelongsToSID);
  handlePOSTResponse($rv, $crid_fr0402, $uploadersid);
  if ($rv !== 401 && $frame_array[4] > 2) $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-long_light-fr0403', $u_languagecode, 'anonymous', $u_fr0403, $opt_ignore_blanks, $thisPageBelongsToSID);
  handlePOSTResponse($rv, $crid_fr0403, $uploadersid);
  if ($rv !== 401 && $frame_array[4] > 3) $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-long_light-fr0404', $u_languagecode, 'anonymous', $u_fr0404, $opt_ignore_blanks, $thisPageBelongsToSID);
  handlePOSTResponse($rv, $crid_fr0404, $uploadersid);

  if ($rv !== 401 && $frame_array[5] > 0) $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-long_light-fr0501', $u_languagecode, 'anonymous', $u_fr0501, $opt_ignore_blanks, $thisPageBelongsToSID);
  handlePOSTResponse($rv, $crid_fr0501, $uploadersid);
  if ($rv !== 401 && $frame_array[5] > 1) $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-long_light-fr0502', $u_languagecode, 'anonymous', $u_fr0502, $opt_ignore_blanks, $thisPageBelongsToSID);
  handlePOSTResponse($rv, $crid_fr0502, $uploadersid);
  if ($rv !== 401 && $frame_array[5] > 2) $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-long_light-fr0503', $u_languagecode, 'anonymous', $u_fr0503, $opt_ignore_blanks, $thisPageBelongsToSID);
  handlePOSTResponse($rv, $crid_fr0503, $uploadersid);
  if ($rv !== 401 && $frame_array[5] > 3) $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-long_light-fr0504', $u_languagecode, 'anonymous', $u_fr0504, $opt_ignore_blanks, $thisPageBelongsToSID);
  handlePOSTResponse($rv, $crid_fr0504, $uploadersid);

  if ($rv !== 401 && $frame_array[6] > 0) $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-long_light-fr0601', $u_languagecode, 'anonymous', $u_fr0601, $opt_ignore_blanks, $thisPageBelongsToSID);
  handlePOSTResponse($rv, $crid_fr0601, $uploadersid);
  if ($rv !== 401 && $frame_array[6] > 1) $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-long_light-fr0602', $u_languagecode, 'anonymous', $u_fr0602, $opt_ignore_blanks, $thisPageBelongsToSID);
  handlePOSTResponse($rv, $crid_fr0602, $uploadersid);
  if ($rv !== 401 && $frame_array[6] > 2) $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-long_light-fr0603', $u_languagecode, 'anonymous', $u_fr0603, $opt_ignore_blanks, $thisPageBelongsToSID);
  handlePOSTResponse($rv, $crid_fr0603, $uploadersid);
  if ($rv !== 401 && $frame_array[6] > 3) $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-long_light-fr0604', $u_languagecode, 'anonymous', $u_fr0604, $opt_ignore_blanks, $thisPageBelongsToSID);
  handlePOSTResponse($rv, $crid_fr0604, $uploadersid);

  if ($rv !== 401 && $frame_array[7] > 0) $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-long_light-fr0701', $u_languagecode, 'anonymous', $u_fr0701, $opt_ignore_blanks, $thisPageBelongsToSID);
  handlePOSTResponse($rv, $crid_fr0701, $uploadersid);
  if ($rv !== 401 && $frame_array[7] > 1) $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-long_light-fr0702', $u_languagecode, 'anonymous', $u_fr0702, $opt_ignore_blanks, $thisPageBelongsToSID);
  handlePOSTResponse($rv, $crid_fr0702, $uploadersid);
  if ($rv !== 401 && $frame_array[7] > 2) $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-long_light-fr0703', $u_languagecode, 'anonymous', $u_fr0703, $opt_ignore_blanks, $thisPageBelongsToSID);
  handlePOSTResponse($rv, $crid_fr0703, $uploadersid);
  if ($rv !== 401 && $frame_array[7] > 3) $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-long_light-fr0704', $u_languagecode, 'anonymous', $u_fr0704, $opt_ignore_blanks, $thisPageBelongsToSID);
  handlePOSTResponse($rv, $crid_fr0704, $uploadersid);

  if ($rv !== 401 && $frame_array[8] > 0) $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-long_light-fr0801', $u_languagecode, 'anonymous', $u_fr0801, $opt_ignore_blanks, $thisPageBelongsToSID);
  handlePOSTResponse($rv, $crid_fr0801, $uploadersid);
  if ($rv !== 401 && $frame_array[8] > 1) $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-long_light-fr0802', $u_languagecode, 'anonymous', $u_fr0802, $opt_ignore_blanks, $thisPageBelongsToSID);
  handlePOSTResponse($rv, $crid_fr0802, $uploadersid);
  if ($rv !== 401 && $frame_array[8] > 2) $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-long_light-fr0803', $u_languagecode, 'anonymous', $u_fr0803, $opt_ignore_blanks, $thisPageBelongsToSID);
  handlePOSTResponse($rv, $crid_fr0803, $uploadersid);
  if ($rv !== 401 && $frame_array[8] > 3) $rv = post_a_new($u_username, $u_password, $apikey, 'xkcd-long_light-fr0804', $u_languagecode, 'anonymous', $u_fr0804, $opt_ignore_blanks, $thisPageBelongsToSID);
  handlePOSTResponse($rv, $crid_fr0804, $uploadersid);

  if ($rv === 401) {
    //
    // if any of the uploads 401, the following uploads should be skipped ifo this message
    echo "<font color=red>The username and/or password is wrong.</font> Please go back and check them!<br><br>\n";
  } else {
    //-- Doc: script uri
    //
    // construct the permalink
    $link = $_SERVER['SCRIPT_URI'] . "?q=$uploadersid,$u_languagecode,$crid_fr0001,$crid_fr0002,$crid_fr0101,$crid_fr0102,$crid_fr0201,$crid_fr0202,$crid_fr0203,$crid_fr0301,$crid_fr0401,$crid_fr0402,$crid_fr0501,$crid_fr0601,$crid_fr0602,$crid_fr0603";
    
    echo "Your changes are at this link, and you can forward it to others!<br>";
    echo "<a href='$link' target=_blank>$link</a><br>";
    echo "<br>";
  }
} else {
  // 
  // no POSTed material from user
  $u_username = $u_password = $u_languagecode = $opt_ignore_blanks = null;
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

//-- Doc: check guest params inbound
//
// If user has a permalink, assign CRIDs to appropriate vars
if ($guest_params !== '') {
  $guest_params_arr = explode(',', $guest_params);
  // DONT FORGET TO CHANEGE THE SIZEOF!!
  if (sizeof($guest_params_arr) === 17) {
    $g_uploadersid = $guest_params_arr[0];
    $g_lang = $guest_params_arr[1];
    $g_newmarkfr0001 = $guest_params_arr[2];
    $g_newmarkfr0002 = $guest_params_arr[3];
    
    $g_newmarkfr0101 = $guest_params_arr[4];
    $g_newmarkfr0102 = $guest_params_arr[5];
    /*
      $g_newmarkfr0103 = $guest_params_arr[6];
      $g_newmarkfr0104 = $guest_params_arr[7];
      $g_newmarkfr0105 = $guest_params_arr[8];
      $g_newmarkfr0106 = $guest_params_arr[8];
      $g_newmarkfr0107 = $guest_params_arr[9];
      $g_newmarkfr0108 = $guest_params_arr[10];
    */
    
    $g_newmarkfr0201 = $guest_params_arr[6];
    $g_newmarkfr0202 = $guest_params_arr[7];
    $g_newmarkfr0203 = $guest_params_arr[8];
   
    $g_newmarkfr0301 = $guest_params_arr[9];
    /*
      $g_newmarkfr0302 = $guest_params_arr[7];
      $g_newmarkfr0303 = $guest_params_arr[8];
    */
    $g_newmarkfr0401 = $guest_params_arr[10];
    $g_newmarkfr0402 = $guest_params_arr[11];
      
    $g_newmarkfr0501 = $guest_params_arr[12];
    /*
      $g_newmarkfr0502 = $guest_params_arr[8];
      $g_newmarkfr0503 = $g_newmarkfr0502;
    */
    $g_newmarkfr0601 = $guest_params_arr[13];
    $g_newmarkfr0602 = $guest_params_arr[14];
    $g_newmarkfr0603 = $guest_params_arr[15];
    $g_newmarkfr0802 = $guest_params_arr[16];
    
  }
}

//-- Doc: reset all the things, if not got expected things
//
// Make sure all relevant CRID assigns took place. If not, reset them all
if (!($g_uploadersid !== null && $g_lang !== null &&
      $g_newmarkfr0001 !== null && $g_newmarkfr0002 !== null &&
      $g_newmarkfr0101 !== null && $g_newmarkfr0102 !== null &&
      $g_newmarkfr0201 !== null && $g_newmarkfr0202 !== null && $g_newmarkfr0203 !== null && 
      $g_newmarkfr0301 !== null &&
      $g_newmarkfr0401 !== null && $g_newmarkfr0402 !== null &&
      $g_newmarkfr0501 !== null && 
      $g_newmarkfr0601 !== null && $g_newmarkfr0602 !== null && $g_newmarkfr0603 !== null
      )){
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

//
// and null out non-relevent CRIDs
if ($g_uploadersid === null) $g_uploadersid = 'null';
if ($g_lang === null) $g_lang = 'null';

if ($g_newmarkfr0001 === null) $g_newmarkfr0001 = 'null';
if ($g_newmarkfr0002 === null) $g_newmarkfr0002 = 'null';

if ($g_newmarkfr0101 === null) $g_newmarkfr0101 = 'null';
if ($g_newmarkfr0102 === null) $g_newmarkfr0102 = 'null';
if ($g_newmarkfr0103 === null) $g_newmarkfr0103 = 'null';
if ($g_newmarkfr0104 === null) $g_newmarkfr0104 = 'null';
if ($g_newmarkfr0105 === null) $g_newmarkfr0105 = 'null';
if ($g_newmarkfr0106 === null) $g_newmarkfr0106 = 'null';
if ($g_newmarkfr0107 === null) $g_newmarkfr0107 = 'null';
if ($g_newmarkfr0108 === null) $g_newmarkfr0108 = 'null';

if ($g_newmarkfr0201 === null) $g_newmarkfr0201 = 'null';
if ($g_newmarkfr0202 === null) $g_newmarkfr0202 = 'null';
if ($g_newmarkfr0203 === null) $g_newmarkfr0203 = 'null';
if ($g_newmarkfr0204 === null) $g_newmarkfr0204 = 'null';

if ($g_newmarkfr0301 === null) $g_newmarkfr0301 = 'null';
if ($g_newmarkfr0302 === null) $g_newmarkfr0302 = 'null';
if ($g_newmarkfr0303 === null) $g_newmarkfr0303 = 'null';
if ($g_newmarkfr0304 === null) $g_newmarkfr0304 = 'null';

if ($g_newmarkfr0401 === null) $g_newmarkfr0401 = 'null';
if ($g_newmarkfr0402 === null) $g_newmarkfr0402 = 'null';
if ($g_newmarkfr0403 === null) $g_newmarkfr0403 = 'null';
if ($g_newmarkfr0404 === null) $g_newmarkfr0404 = 'null';

if ($g_newmarkfr0501 === null) $g_newmarkfr0501 = 'null';
if ($g_newmarkfr0502 === null) $g_newmarkfr0502 = 'null';
if ($g_newmarkfr0503 === null) $g_newmarkfr0503 = 'null';
if ($g_newmarkfr0504 === null) $g_newmarkfr0504 = 'null';

if ($g_newmarkfr0601 === null) $g_newmarkfr0601 = 'null';
if ($g_newmarkfr0602 === null) $g_newmarkfr0602 = 'null';
if ($g_newmarkfr0603 === null) $g_newmarkfr0603 = 'null';
if ($g_newmarkfr0604 === null) $g_newmarkfr0604 = 'null';

if ($g_newmarkfr0701 === null) $g_newmarkfr0701 = 'null';
if ($g_newmarkfr0702 === null) $g_newmarkfr0702 = 'null';
if ($g_newmarkfr0703 === null) $g_newmarkfr0703 = 'null';
if ($g_newmarkfr0704 === null) $g_newmarkfr0704 = 'null';

if ($g_newmarkfr0801 === null) $g_newmarkfr0801 = 'null';
if ($g_newmarkfr0802 === null) $g_newmarkfr0802 = 'null';
if ($g_newmarkfr0803 === null) $g_newmarkfr0803 = 'null';
if ($g_newmarkfr0804 === null) $g_newmarkfr0804 = 'null';

//
// Get relevant data into javascript as globals
$js_frame_array = '';
reset($frame_array);
foreach($frame_array as $element) {
  $js_frame_array .= ', ' . $element;
}
$js_frame_array = substr($js_frame_array, 2);

echo "<script type='text/javascript'>\n".
"var global_g_visitsid = '$thisPageBelongsToSID';  // SID of user who created the page\n" .
"var global_g_uploadersid = '$g_uploadersid';\n".
"var global_g_lang = '$g_lang';\n".
"var global_container_width = '$container_width';  // width of xkcd image\n".
"var global_container_height = '$container_height';// height of grey box\n".

"var global_frame_array = [$js_frame_array]; // el[0] is always 2, el[1-8] how many translations in each frame\n" .

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

//
// include javascript common to all pages (implementing service
// and translation menus, for example)
include_once('../common_javascript_v2.html');

//
// Include per-xkcd styles, javascript and body
include_once('index.html');
?>
