Updating a copy of xkcd for which others can offer up translations
Alistair Mann

At mo, code assumes no more than 8 frames, no more than 8 text elements per frame on first frame, 4 on remainder

 1. Visit [xkcd](https://xkcd.com/)
 2. Right-click image | save image as | xkcd-orig-<title>.png | save
 3. Open GIMP
 4. File | open | xkcd-orig-<title>.png | open
 5. file | save as | xkcd-orig-<title>.xcf | save
 6. print the image
   1. Get xkcd# written down
   1. title the image
   2. Record dimensions of image
   3. Hover image on site: is there a title=”” popup? If so, note a fr0001 
   4. label the textual elements (“fr0104” etc)
     1. If some textual elements repeat (“Yes”, “X” etc) then label the first, second and subsequent label with a new lable AND the original. This sign that position changes but the newmark doesn't
   5. ~~label point elements (“speech line”) leaving original lines in this time~~
 1. Title two tables 
   1. textual
     1. one row per textual elements
     2. cols: corner; x,y, w
   2. ~~point elements~~
     1. ~~one row per two points (“pt1, pt2”)~~
     2. ~~Cols: xy->xy~~
 8. Co-ords:
   1. For each textual element
     1. determine where the corner should go (just tl, tr, bl, br for now)
     2. Get pixel co-ords for textual element and point elements
     3. determine max width of bubble
 9. Erase all textual and speech bubble elements
 10. File | save as | xkcd-notext-<title>.xcf
 11. File | export … | export | export
 12. Close gimp
 13. Upload png to imgur and capture img src address
 14. At www.csi18n.com, mkdir -p ~/csi18n/xkcd/<title> //title should use underscore not spaces
   1. cd ~/csi18n/xkcd/<title>
   2. cp ../20141201/index.php . (or whichever is most recent version)
   3. cp ../20141201/index.html .
   4. Edit index.html
     1. search/replace previous title with new
     2. change <img src=”
     3. Change attribution
     4. Change date
     5. Change “prev” link to last xkcd
     6. ~~change divs to handle particular number this day~~
     7. ~~change textareas to handle particular number this day~~
     8. ~~comment out unused createDialog_v2s in xkcdShow()~~
     9. ~~comment out unused items in xkcdShowLines()~~
     10. Data for xkcdShow() for BT, TL etc, co-ords and widths
     11. Review xkcdShow() font sizes
     12. ~~Data for xkcdShowLines() for speech lines~~
     13. If see-through PNG
       1. Work up each z-index
       2. Add second area map because closer z-index of see through PNG means can't click text behind. A better solution: divide PNG into four around unused center 
     14. hidden image title?
       1. If necc, uncomment hideWhatWasImgTitle
       2. If necc, hideWhatWasImgTitle, showWhatWasImgTitle correct element
       3. Correct image-map via [image maps](http://www.image-maps.com/)
         1. Use the first <area … tag in the html code
     15. in index.php,
       1. search replace old title with new
       2. change container_width to match image width
       1. fill in frame_array: first is 2, then number text els in each frame, 8 frames
       3. ~~edit “handle POSTS” to suit~~
       4. edit “check if enough to POST” to suit
       5. ~~edit “perform POSTs”~~
       6. Modify script_uri to suit
       7. edit “check guest params inbound”, ensure sizeof correct!
       8. Edit “reset all the things, if not got expected things “
     16. in ../common_javascript_v2.html (if additional elements needed)
       1. extend if $el … to suit (two loads)
       2. extend handler_fr... to suit 
       3. extend localstorage.setitem to suit
       4. extend d.innerHTML to suit
       5. extend xkcdShow to suit
     17. When dealing with same newmark, different location
       1. Add content to the uncircled/first label
       2. Circled labels should copy
       3. index.html/createDialog_v2 should change 10th arg so newmark matches first in line
       4. index.html comment out textareas for those elements reusing labels
       5. index.php comment out IFs for those elements reusing labels
       6. index.php comment out post_a_new for those elements reusing labels
       7. index.php LEAVE unused elements in allcrids
 15. Tie in this page to any indexing pages:
   1. add as “next” to previous index.html
   2. add to xkcd/index.html
 16. At site: Page should now work, with 404s for text. add English text as available translation:
   1. Make sure your credentials are correct in Globe | username / password -- they default to test05
   1. For each “404”
     1. Click
     2. Offer Another
     3. Add original English
     4. Anonymous
     5. Submit
   2. Make text a bit larger/smaller to suit
 17. Adjust co-ords to suit
