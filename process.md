Updating a copy of xkcd for which others can offer up translations
Alistair Mann

At mo, code assumes no more than 8 frames, no more than 48 text elements per frame on first frame, 4 on remainder. Process takes about 20 assuming the simplest kind, excluding mistakes, backing up, etc etc

 1. Visit [xkcd](https://xkcd.com/)
 1. Right-click image | save image as | xkcd-orig-\<title>.png | save
 1. Open GIMP
 1. File | open | xkcd-orig-\<title>.png | open
 1. file | save as | xkcd-orig-\<title>.xcf | save
 1. print the image
   1. Get xkcd# and title written down
   1. ~~title the image~~
   1. Record dimensions of image
   1. ~~Hover image on site: is there a title=”” popup? If so, note a fr0001~~ All seem to
   1. label the textual elements (“fr0104” etc)
     1. If some textual elements repeat (“Yes”, “X” etc) then label the first, second and subsequent label with a new lable AND the original. This sign that position changes but the newmark doesn't
   1. label point elements (“speech line”) leaving original lines in this time
 1. Title two tables 
   1. textual
     1. one row per textual elements
     1. cols: corner; x,y, w
   1. point elements
     1. one row per two points (“pt1, pt2”)
     1. Cols: xy->xy
 1. Co-ords:
   1. For each textual element
     1. determine where the corner should go (just tl, tr, bl, br for now. The corner should generally be the closest corner the ballon has to any line indicating the speaker. )
       1. Append a "C" for centering within autowidth div, space if not used (eg, "TLC", "BR ")
        1. Append a "T" to make a fixed width block above or below the image (eg, "TLCT")
     1. Get pixel co-ords for textual element and point elements
     1. determine max width of bubble
 1. Erase all textual and speech bubble elements
 1. File | save as | xkcd-notext-\<title>.xcf
 1. File | export … | export | export
 1. Close gimp
 1. Upload png to imgur and capture img src address
 1. At www.csi18n.com, mkdir -p ~/csi18n/xkcd/\<title> //title should use underscore not spaces
   1. cd ~/csi18n/xkcd/\<title>
   1. cp ../20141201/index.php . (or whichever is most recent version)
   1. cp ../20141201/index.html .
     1. in index.php,
       1. replace nroot with new title, vis "xkcd-\<title>-"
       1. change container_width to match image width
       1. fill in frame_array: first is 2, then number text els in each frame, 8 frames
       1. ~~edit “handle POSTS” to suit~~
       1. ~~edit “check if enough to POST” to suit~~
       1. ~~edit “perform POSTs”~~
       1. ~~Modify script_uri to suit~~
       1. ~~edit “check guest params inbound”, ensure sizeof correct!~~
       1. ~~Edit “reset all the things, if not got expected things “~~
     1. When dealing with same newmark, different location
       1. Add content to the uncircled/first label
        1. Circled labels should copy
        1. index.html/createDialog_v2 should change 10th arg so newmark matches first in line
        1. index.html comment out textareas for those elements reusing labels
        1. index.php comment out IFs for those elements reusing labels
        1. index.php comment out post_a_new for those elements reusing labels
        1. index.php LEAVE unused elements in allcrids
   1. In index.html
     1. search/replace previous title with new
     1. change \<img src=”
     1. Change attribution
     1. Change date
     1. Change “prev” link to last xkcd
     1. ~~change divs to handle particular number this day~~
     1. ~~change textareas to handle particular number this day~~
     1. ~~comment out unused createDialog_v2s in xkcdShow()~~
     1. ~~comment out unused items in xkcdShowLines()~~
     1. Data for xkcdShow() for BT, TL etc, co-ords and widths
     1. Review xkcdShow() font sizes
     1. Data for xkcdShowLines() for speech lines
     1. If see-through PNG
       1. Work up each z-index
        1. Add second area map because closer z-index of see through PNG means can't click text behind. A better solution: divide PNG into four around unused center 
     1. ~~hidden image title?~~ Have always seen a hidden image
       1. If necc, uncomment hideWhatWasImgTitle
        1. ~~If necc, hideWhatWasImgTitle, showWhatWasImgTitle correct element~~
       1. Correct image-map via [image maps](http://www.image-maps.com/)
         1. Use the first \<area … tag in the html code
    1. in ../common_javascript_v3.html (if additional elements needed)
      1. extend if $el … to suit (two loads)
      1. extend handler_fr... to suit 
      1. extend localstorage.setitem to suit
      1. extend d.innerHTML to suit
      1. extend xkcdShow to suit
 11. Tie in this page to any indexing pages:
   1. add as “next” to previous index.html
   1. add to xkcd/index.html
 11. At site: Page should now work, with 404s for text. add English text as available translation:
   1. Make sure your credentials are correct in Globe | username / password -- they default to test05
   1. For each “404”
     1. Click
     1. Offer Another
     1. Add original English
     1. Anonymous
     1. Submit
   1. Make text a bit larger/smaller to suit
 1. Adjust co-ords to suit
 1. Adjust css styles to suit (top index.html)
