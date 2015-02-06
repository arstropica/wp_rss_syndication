<?php
 /*WordPress RSS Syndication Loop */
?>
<?php
    class wrs_client_loop{
        function wrs_client_loop() {
            global $post, $wp_query;
            $wrs_integration = get_option('wrs_integration', '');
            $wrs_front_page = get_option('wrs_front_page', '');
            $wrs_posts_page = get_option('wrs_posts_page', '');
            $is_posts_page = $wp_query->is_posts_page; 
            $wrs_position = get_option('wrs_position');
            $priority = stristr($wrs_position, 'before') ? 11 : 0;
            if (1 == $wrs_integration) {
                if (($is_posts_page) && (1 == $wrs_posts_page)) {
                    add_action($wrs_position, array(&$this, 'wrs_client_do_loop'), $priority);
                } elseif ((is_front_page()) && (1 == $wrs_front_page)) {
                    add_action($wrs_position, array(&$this, 'wrs_client_do_loop'), $priority);
                }
            }   
        }
        
        function wrs_client_do_loop() {
            global $wp_query, $post;
            $wrs_domain = get_option('wrshost_domain', false);
            $wrs_feed_path = get_option('wrsfeedpath', 'feed?orderby=date&order=desc');
            $wrs_cat_filter = get_option('wrscat_filter', false);    
            $wrs_tags_filter = get_option('wrstags_filter', false);    
            $wrs_numposts = get_option('wrs_numposts', '3'); 
            $wrs_numposts = empty($wrs_numposts) ? 3 : $wrs_numposts; 
            $wrs_blog_subtitle = get_option('wrs_blog_subtitle', false);    
            if (!class_exists('SimpleXmlElement')) {
                return;
            }
            if (is_feed()) {
                return;
            }
            $output = "<p>No Posts Found!</p>";

            $tmpdir = WRS_PLUGIN_PATH . "tmp";
            $rssfile = $tmpdir . "/wrs_feed.xml";
            if (file_exists($rssfile)) {
                chmod($rssfile, 0777);
                if (strtotime("-24 hours") >= filemtime($rssfile)) {
                    unlink($rssfile);
                    if (false === $wrs_domain) {
                        return;
                    }
                    $run_curl = true;
                } else {
                    $run_curl = false;
                }
            } else {
                if (false === $wrs_domain) {
                    return;
                }
                $run_curl = true;
            }   
            if ($run_curl) {
                /*Transfer RSS XML*/
                $feedURL = 'http://' . $wrs_domain . ($wrs_cat_filter ? "/category/" . $wrs_cat_filter : "") . "/" . $wrs_feed_path . ($wrs_tags_filter ? "&tag=" . $wrs_tags_filter : "");
                $useragent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1'; 


                $ch = curl_init(); 
                curl_setopt($ch, CURLOPT_USERAGENT, $useragent);  
                curl_setopt($ch, CURLOPT_URL, $feedURL); 
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
                curl_setopt($ch, CURLOPT_VERBOSE, 1); 
                if ($curlerror = tmpfile()) {
                    curl_setopt($ch, CURLOPT_STDERR, $curlerror); 
                }
                if( ! $data = curl_exec($ch)) { 
                    trigger_error(curl_error($ch)); 
                } 

                if (curl_errno($ch)) {
                    return $curlerror;
                }

                curl_close($ch); 
                $rsshandle = fopen($rssfile, 'w');
                fwrite($rsshandle, $data);
                fclose($rsshandle);  
                chmod($rssfile, 0777);
            } else {
                $data = file_get_contents($rssfile);
            }
            try {
                $xmldoc = new SimpleXmlElement($data, LIBXML_NOCDATA);
                if(isset($xmldoc->channel)) {
                    $output = "<style type='text/css'>\n";
                    $output .= "#wp_rss_syndication_posts P.postmetadata\n";
                    $output .= "{\n";
                    $output .= "display: table;\n";
                    $output .= "margin: 10px 0 25px;\n";
                    $output .= "white-space: nowrap;\n";
                    $output .= "}\n";
                    $output .= "#wp_rss_syndication_posts P.postmetadata SPAN.categories\n";
                    $output .= "{\n";
                    $output .= "float: none !important;\n";
                    $output .= "display: table-cell;\n";
                    $output .= "clear: none !important;\n";
                    $output .= "margin-left: 10px !important;\n";
                    $output .= "white-space: normal;\n";
                    $output .= "}\n";
                    $output .= "</style>\n";
                    $output .= "<div id=\"wp_rss_syndication_posts\"><hr />\n";
                    $output .= $wrs_blog_subtitle ? "<h3 class=\"wrs_subtitle\">" . $wrs_blog_subtitle . "</h3>\n" : "";
                    $output .= "<div class=\"post wp_rss_syndication_post\" id=\"rss_post-$counter\">\n";
                    $cnt = count($xmldoc->channel->item);
                    $counter = 0;
                    for($i=0; $i<$cnt; $i++) {
                        $url        = $xmldoc->channel->item[$i]->link;
                        $title      = $xmldoc->channel->item[$i]->title;
                        $date       = $xmldoc->channel->item[$i]->pubDate;
                        $excerpt    = $xmldoc->channel->item[$i]->description;
                        $countcat   = count($xmldoc->channel->item[$i]->category);
                        $cat = array();
                        if ($countcat > 0) {
                            for ($j=0; $j < $countcat; $j++) {
                                $cat[] = strtolower($xmldoc->channel->item[$i]->category[$j]);
                            } 
                        } 
                        $rawdate = strtotime($date);
                        
                        $content = "<h2 class=\"entry-title\"><a href=\"" . $url . "\" target=\"_blank\" title=\"Permanent link to " . $title . "\">" . $title . "</a></h2>\n";
                        $content .= "<small class=\"date ie6fix\">" . date_i18n("D, F j, Y", strtotime($date)) . "</small>\n";
                        $content .= "<div class=\"entry\">" . $excerpt . "</div>\n";
                        if ($countcat > 0) {
                            $content .= "<p class=\"postmetadata\">Posted in : <span class=\"categories\">" . implode(", ", $cat) . "</span>\n";                 
                        }
                        $counter ++;
                        $output .= $content . "\n";
                        if ($counter >= $wrs_numposts) break;
                    }
                    $output .="</div>\n";        
                    $output .="</div>\n";        
                }
            } catch (Exception $e) {
                $output = '<p>Error: '.  $e->getMessage(). "</p>\n";
            }
            echo $output;
        }
    }
    
    function wrs_client_loop_init(){
        global $wrs_client_loop;
        $wrs_client_loop = new wrs_client_loop();
    }
    add_action('wp_head', 'wrs_client_loop_init');
?>
