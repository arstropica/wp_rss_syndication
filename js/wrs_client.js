// XML Check
var scripts = document.getElementsByTagName("script"),
scriptsrc = scripts[scripts.length-1].src;

jQuery(document).ready(function(){
    jQuery.ajaxSetup({
        timeout: 60000,
        cache: false
    });
    jQuery("#wrs_display_feed").ajaxStart(function(){
       var loading = jQuery("<div>&nbsp;</div>").attr('id', 'loading').css({ 'opacity': 0.75, 'height' : '100%', 'width': '100%', 'display': 'block', 'background-color': '#FFF', 'position' : 'absolute', 'top' : 0, 'left': 0, 'z-index': 2 });
       jQuery(this).prepend(loading);
     });
    jQuery("#wrs_display_feed").ajaxStop(function(){
       jQuery(this).ready().delay(3000).remove("#loading");
     }); 
    getfeed();
    jQuery("#wrs_host_domain_check").click(function(){
        getnewfeed();
        return false;
    });
    jQuery("INPUT#wrs_cat_filter").keyup(function () {
        var value = jQuery(this).val();
        value = jQuery.trim(value);
        if (value.length) {
            jQuery("SPAN#wpt_feed_cat").text("/category/" + value);
        } else {
            jQuery("SPAN#wpt_feed_cat").text("");
        }
    }).keyup();
    jQuery("INPUT#wrs_tags_filter").keyup(function () {
        var value = jQuery(this).val();
        value = jQuery.trim(value);
        if (value.length) {
            jQuery("SPAN#wpt_feed_tags").text("?tag=" + value);
        } else {
            jQuery("SPAN#wpt_feed_tags").text("");
        }
    }).keyup();
});
function getnewfeed(){
    var feeddomain = jQuery("#wrs_host_domain").val();
    if (feeddomain.length > 0) {
        var feedcat = jQuery("#wrs_cat_filter").val();
        var feedcat = jQuery.trim(feedcat);
        var feedtags = jQuery("#wrs_tags_filter").val();
        var feedtags = jQuery.trim(feedtags);
        var feedpath = jQuery("#wrs_feed_path").val();
        feedpath = encodeURIComponent(feedpath);
        if (feedcat.length) {
            feedpath = "category/" + feedcat + "/" + feedpath;
        }
        if (feedtags.length) {
            feedpath = feedpath + "&tag=" + feedtags;
        }
        var pluginpath = jQuery("#wrs_path").val();
        var plugindir = jQuery("#wrs_dir").val();
        var localplugindir = jQuery("#local_wrs_dir").val();
        var csrfKey = jQuery("#csrfKey").val();
        var patt = /\/$/;
        feeddomain = (patt.test(feeddomain)) ? (feeddomain.substr(0, feeddomain.length -1)) : (feeddomain);
        var ishttp = feeddomain.search(/http:\/\//i);
        if (ishttp == -1) { feeddomain = "http://" + feeddomain;}
        var feeduri = feeddomain + "/" + feedpath;
        var feedtable = jQuery('<TABLE id="rsspreview" class="widefat"><THEAD><TR><TH style="width: 150px;">Date</TH><TH style="width: 150px;">Title</TH><TH style="width: 350px;">Excerpt</TH><TH style="width: 150px;">Category</TH></TR></THEAD><TBODY></TBODY></TABLE>"').attr("id", "rsspreview");
        jQuery.ajax({
            type: "POST",
            url: plugindir + 'includes/getfeed.php',
            cache: false,
            data: 'csrfKey=' + csrfKey + '&feeduri=' + feeduri + '&wrspath=' + pluginpath,
            success: function(data) {
                if (data == '1') {
                    var ts = Math.round((new Date()).getTime() / 1000);
                    var localfeeduri = localplugindir + 'tmp/wrs_feed.xml?ver=' + ts;
                    jQuery('#wrs_display_feed').html(feedtable);
                    jQuery('#rsspreview TBODY').simplerss({
                        url: localfeeduri,
                        display: 10,
                        html: "<td><p>{pubDate}</p></td><td><p><em><a target=\"_blank\" href=\"{link}\">{title}</a></em></p></td><td><p>{text}</p></td><td><p>{category}</p></td>",
                        wrapper: 'TR'
                    }).show();
                } else {
                    jQuery('#wrs_display_feed').html("<p id='nofeed'>No Feed Discovered.</p>");
                }
            },
            error: function (xhr, status, e) {
                jQuery('#wrs_display_feed').html("<p id='nofeed'>No Feed Discovered.</p>");
            }
        });
    } else {
        jQuery('#wrs_display_feed').html("<p id='nofeed'>No Feed Discovered.</p>");
    }        
}
function getfeed(){
    var feeddomain = jQuery("#wrs_host_domain").val();
    if (feeddomain.length > 0) {
        var plugindir = jQuery("#wrs_dir").val();
        var localplugindir = jQuery("#local_wrs_dir").val();
        var ts = Math.round((new Date()).getTime() / 1000);
        var localfeeduri = localplugindir + 'tmp/wrs_feed.xml?ver=' + ts;
        var feedtable = jQuery('<TABLE id="rsspreview" class="widefat"><THEAD><TR><TH style="width: 150px;">Date</TH><TH style="width: 150px;">Title</TH><TH style="width: 350px;">Excerpt</TH><TH style="width: 150px;">Category</TH></TR></THEAD><TBODY></TBODY></TABLE>"').attr("id", "rsspreview");
        jQuery.ajax({
            url: localfeeduri,
            cache: false,
            type:'HEAD',
            error:
            function(){
                jQuery('#wrs_display_feed').html("<p id='nofeed'>No Feed Discovered.</p>");
            },
            success:
            function(){
                jQuery('#wrs_display_feed').html(feedtable);
                jQuery('#rsspreview TBODY').simplerss({
                    url: localfeeduri,
                    display: 10,
                    html: "<td><p>{pubDate}</p></td><td><p><em><a target=\"_blank\" href=\"{link}\">{title}</a></em></p></td><td><p>{text}</p></td><td><p>{category}</p></td>",
                    wrapper: 'TR'
                }).show();
            }
        });
    } else {
        jQuery('#wrs_display_feed').html("<p id='nofeed'>No Feed Discovered.</p>");
    }
}
function parseFeed(feeduri){
    jQuery.ajax({
        type: "GET",
        cache: false,
        url: feeduri,
        dataType: "xml",
        success: function(xml) {
            jQuery(xml).find('channel').each(function(){
                var doctitle = jQuery(this).find('title').text(); 
                var docdesc = jQuery(this).find('description').text(); 
                var builddate = jQuery(this).find('lastBuildDate').text(); 
                jQuery(this).find('item').each(function(){
                    var title = jQuery(this).find('title').text();
                    var link = jQuery(this).find('link').text();
                    var date = jQuery(this).find('pubDate').text();
                    var author = jQuery(this).find('dc\\:creator').text();
                    var description = jQuery(this).find('description:first').text();
                    var content = jQuery(this).find('content\\:encoded:first').text();
                    var numcomments = jQuery(this).find('slash\\:comments').text();
                    var category = jQuery(this).find('category').text();

                });
            });
        }
    });
}

function xmlItem(title, link, date, author, description, content, numcomments) {
    var output = "<div>\r\n";
    output += ""
}