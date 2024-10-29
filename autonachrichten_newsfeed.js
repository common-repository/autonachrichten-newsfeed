//####################################################################################################
//###### Project   : Autonachrichten Newsfeed                                                   ######
//###### File Name : autonachrichten_newsfeed.php                                               ######
//###### Purpose   : This is the main page for this plugin.                                     ######
//###### Created   : Dez 7th 2011                                                               ######
//###### Modified  : Jan 13th 2014                                                              ######
//###### Author    : Matthias Tosch (http://www.mtosch.de)                                      ######
//###### Link      : http://www.autonachrichten.de/plugins/auto-nachrichten-fur-wordpress-blogs ######
//####################################################################################################
	

function an_scroll() {
    an_obj.scrollTop = an_obj.scrollTop + 1;
    an_scrollPos++;
    if ((an_scrollPos%an_heightOfElm) == 0) {
        an_numScrolls--;
        if (an_numScrolls == 0) {
            an_obj.scrollTop = '0';
            an_content();
        } else {
            if (an_scrollOn == 'true') {
                an_content();
            }
        }
    } else {
        setTimeout("an_scroll();", 10);
    }
}

var an_Num = 0;
/*
Creates amount to show + 1 for the scrolling ability to work
scrollTop is set to top position after each creation
Otherwise the scrolling cannot happen
*/
function an_content() {
    var tmp_vsrp = '';

    w_vsrp = an_Num - parseInt(an_numberOfElm);
    if (w_vsrp < 0) {
        w_vsrp = 0;
    } else {
        w_vsrp = w_vsrp%an_array.length;
    }
	
    // Show amount of vsrru
    var elementsTmp_vsrp = parseInt(an_numberOfElm) + 1;
    for (i_vsrp = 0; i_vsrp < elementsTmp_vsrp; i_vsrp++) {
		
        tmp_vsrp += an_array[w_vsrp%an_array.length];
        w_vsrp++;
    }

    an_obj.innerHTML 	= tmp_vsrp;
	
    an_Num 			= w_vsrp;
    an_numScrolls 	= an_array.length;
    an_obj.scrollTop 	= '0';
    // start scrolling
    setTimeout("an_scroll();", 2000);
}

