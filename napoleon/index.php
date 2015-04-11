<?php

//
// The newmark identifies 'that which is to be translated', and is
// formed on these pages using "xkcd" - "title" - "fr" + frame number + 
// balloon number. Those first two elements are defined here.
$nroot = 'xkcd-napoleon-';

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
// sticks at 1024 pixels
$container_width = 392;
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
// speech balloons = 3. Assumption of upto 8 frames, so frame_array
// must be 9 elements long: pad with zeros
$frame_array = array(2, 2, 3, 3, 2, 0, 0, 0, 0);

include_once('../common_php_v1a.php');
?>
