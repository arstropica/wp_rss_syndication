<?php
    function wrs_client_list_shortcode($atts) {
        global $wp_query, $post;
        $wrs_domain = get_option('wrshost_domain', false);
        $wrs_feed_path = get_option('wrsfeedpath', 'feed?orderby=date&order=desc');
        $wrs_cat_filter = get_option('wrscat_filter', false);    
        $wrs_tags_filter = get_option('wrstags_filter', false);    
        $wrs_featured_guid = get_option('wrs_featured_guid', '');    
        if (!class_exists('SimpleXmlElement')) {
            return "<p>SimpleXML not installed.</p>";
        }
        if (is_feed()) {
            return "<p>RSS Posts List</p>";
        }
        $output = "<p>No Data!</p>";
        extract(shortcode_atts(array(
        'show' => 'title',
        'listid' => 'wrs_list',
        'limit' => 4,
        'link_target' => '_blank'
        ), $atts));

        $tmpdir = WRS_PLUGIN_PATH . "tmp";
        $category_arry = explode(',' , strtolower($wrs_cat_filter));
        $tag_arry = explode(',' , strtolower($wrs_tags_filter));
        $terms_arry = array_merge($category_arry, $tag_arry);
        $terms = array_unique($terms_arry);
        $rssfile = $tmpdir . "/wrs_feed.xml";
        if (file_exists($rssfile)) {
            chmod($rssfile, 0777);
            if (strtotime("-24 hours") >= filemtime($rssfile)) {
                unlink($rssfile);
                if (false === $wrs_domain) {
                    return "<p>Remote WordPress Blog Domain not selected.</p>";
                }
                $run_curl = true;
            } else {
                $run_curl = false;
            }
        } else {
            if (false === $wrs_domain) {
                return "<p>Remote WordPress Blog Domain not selected.</p>";
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
                $output = "<ul id=\"$listid\">\n";
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
                    switch ($show) {
                        case "full" :
                            $content = '<a target="' . $link_target . '" href="'.$url.'">'. $title . "</a><br />" . "<span style='text-transform: uppercase; font-size: 11px;'>" . date_i18n("D, F j, Y", strtotime($date)) . "</span><br /><span style='font-size: 12px'>" . $excerpt . "</span>";
                            break;
                        case "title" :
                            $content = '<a target="' . $link_target . '" href="'.$url.'">'. $title . "</a>";
                            break;
                        case "featured" :
                            if ($url == $wrs_featured_guid) {
                                $content = '<a target="' . $link_target . '" href="'.$url.'">'. $title . "</a><br />" . "<span style='text-transform: uppercase; font-size: 11px;'>" . date_i18n("D, F j, Y", strtotime($date)) . "</span><br /><span style='font-size: 12px'>" . $excerpt . "</span>";
                            } else {$content = "";}
                            break;
                        default :
                            $content = '<a target="' . $link_target . '" href="'.$url.'">'. $title . "</a>";
                    }
                    $counter ++;
                    if (!empty($content)) $output .= '<li>' . $content . '</li>' . "\n";
                    if ($counter >= $limit) break;
                }
                $output .="</ul>\n";        
            }
        } catch (Exception $e) {
            $output = '<p>Error: '.  $e->getMessage(). "</p>\n";
        }
        return $output;
    }
    add_shortcode("display_wrs_list","wrs_client_list_shortcode");
?>
