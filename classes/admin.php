<?php
    class wrs_client_admin {
        public $file;
        
        public function wrs_client_admin($file){
            $this->file = $file;
            $this->tmpdir();
            register_activation_hook($file, array(&$this, "activate"));
            register_deactivation_hook($file, array(&$this, "deactivate"));
            add_action('admin_menu', array(&$this, 'add_menu'), 1);
            add_action('admin_menu', array(&$this, '_setup'), 0);
        }
        function _setup(){
        }
        function activate() {
            add_option('wrshost_domain', '');
            add_option('wrsfeedpath', 'feed?orderby=date&order=desc');
            add_option('wrscat_filter', '');
            add_option('wrstags_filter', '');
            add_option('wrs_integration', '');
            add_option('wrs_front_page', '');
            add_option('wrs_posts_page', '');
	        add_option('wrs_position', '');
	        add_option('wrs_numposts', '');
	        add_option('wrs_blog_subtitle', '');
            add_option('wrs_featured_active', '');
            add_option('wrs_featured_guid', '');
        }
        function deactivate() {
            delete_option('wrshost_domain');
            delete_option('wrsfeedpath');
            delete_option('wrscat_filter');
            delete_option('wrstags_filter');
            delete_option('wrs_integration');
            delete_option('wrs_front_page');
            delete_option('wrs_posts_page');
	        delete_option('wrs_position');
	        delete_option('wrs_numposts');
	        delete_option('wrs_blog_subtitle');
            delete_option('wrs_featured_active');
            delete_option('wrs_featured_guid');
        }
        function wrs_client_admin_styles(){
            wp_enqueue_style('wrs-admin-stylesheet', WRS_PLUGIN_DIR . 'css/wrs_client_admin.css', array(), time());
        }
        function wrs_client_admin_scripts(){
            wp_enqueue_script('jquery_latest', 'http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js');
            wp_enqueue_script('jquery-simplerss-script', WRS_PLUGIN_DIR . 'js/jquery.simplerss.js', array('jquery_latest'), time());
            wp_enqueue_script('jquery-client-admin-script', WRS_PLUGIN_DIR . 'js/wrs_client.js', array('jquery_latest', 'jquery-simplerss-script'), time());
        }
        function add_menu(){
            $faqpage = add_submenu_page("wrsyndication_faq", "WP RSS Syndication Instructions", "Instructions", 7, "wrsyndication_faq", array(&$this, 'wrs_client_faq_page')); 
            add_action( "admin_print_styles-$faqpage", array(&$this, 'wrs_client_admin_styles') );
            $menupage = add_menu_page("WordPress RSS Syndication", "WP RSS Syndication", 7, "wrsyndication_faq", array(&$this, 'wrs_client_faq_page'), false, 10); 
            add_action( "admin_print_styles-$menupage", array(&$this, 'wrs_client_admin_styles') );
            $setuppage = add_submenu_page("wrsyndication_faq", "WP RSS Syndication Feed Setup", "Feed Setup", 7, "wrsyndication_setup", array(&$this, 'wrs_client_setup_page')); 
            add_action( "admin_print_scripts-$setuppage", array(&$this, 'wrs_client_admin_scripts') );
            add_action( "admin_print_styles-$setuppage", array(&$this, 'wrs_client_admin_styles') );
            $blogpage = add_submenu_page("wrsyndication_faq", "WP RSS Syndication Integration", "Blog Settings", 7, "wrsyndication_blog", array(&$this, 'wrs_client_settings_page')); 
            add_action( "admin_print_styles-$blogpage", array(&$this, 'wrs_client_admin_styles') );
        }
        public function wrs_client_faq_page(){
            ?>
            <div class="wrap">
                <div id="icon-options-general" class="icon32"></div>
                <?php echo "<h2>" . __('WP RSS Syndication Client Instructions', 'wrs_trdom') . "</h2>"; ?>
                <div id="wrs-display" class="wrsrounded">
                    <?php echo "<h2>" . __('How To Use', 'wrs_trdom') . "</h2>"; ?>
                    <div style="margin-bottom: 20px;">
                        <p>WP RSS Syndication Client plugin displays posts from remote blogs via RSS.  The WP RSS Syndication Client plugin can import posts from WordPress blogs on other domains using their default RSS feed and display basic post data in an HTML unordered list or on the blog page. </p>
                        <ul style="list-style: disc !important; padding-left: 15px;">
                            <li>Enter a domain name in the Setup form and click check to retrieve a sample of the the rss feed containing the blog's posts.</li>
                            <li>Click 'Save Setting' to save the domain name in WordPress.</li>
                            <li>To use WP RSS Syndication Client, enter the shortcode <strong>[display_wrs_list]</strong> in your post or page.</li>
                            <li>The shortcode has several customizable options: 
                                <ul style="list-style: disc !important; padding-left: 35px; margin-top: 15px;">
                                    <li><em>show = 'featured/full/<u>title</u>'</em> :  display featured post OR post title, excerpt, category and tags OR only the linked title. Default is 'title'.</li>
                                    <li><em>listid = '<u>custom</u>'</em> :  specify the id attribute of the HTML list containing the post data. Default is 'wrs_list'.</li>
                                    <li><em>limit = <u>4</u></em> :  specify the maximum number of posts to display in the list. Default is 4 posts.</li>
                                    <li><em>link_target = <u>'_blank'</u></em> :  Target window for links. Default is a new window.</li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <?php        
        }
        public function wrs_client_setup_page(){
            session_start();
            $csrfToken = md5(uniqid(mt_rand(),true)); // Token generation updated, as suggested by The Rook. Thanks!
            $_SESSION['csrfToken'] = $csrfToken;
            $wrs_feed_path = get_option('wrsfeedpath', 'feed?orderby=date&order=desc');
            if (isset($_POST['nonce_wrs_client'])) {
                check_admin_referer('wrs_client', 'nonce_wrs_client');
                $wrs_host_domain = $_POST['wrs_host_domain'];
                $wrs_cat_filter = $_POST['wrs_cat_filter'];
                $wrs_tags_filter = $_POST['wrs_tags_filter'];
                if( $isXML = $this->isRemoteXML("http://" . $wrs_host_domain . (empty($wrs_cat_filter) ? "" : "/category/" . $wrs_cat_filter) . "/" . $wrs_feed_path . (empty($wrs_tags_filter) ? "" : "&tag=" . $wrs_tags_filter))) {
                    switch($isXML) {
                        case '500' :
                            $this->wrs_host_msg('The domain or host: ' . $wrs_host_domain . ' is unreachable.');
                            break;
                            
                        case '404' :
                            $this->wrs_host_msg('A feed does not exist on domain: ' . $wrs_host_domain . '.  Is WordPress installed there?');
                            break;
                            
                        case 'invalid' :
                            $this->wrs_host_msg('The WordPress feed on ' . $wrs_host_domain . ' could not be read or may be invalid. Is WordPress installed there?');
                            break;
                        case 'valid' :
                            $this->wrs_host_msg('Setting Updated.', 'update');
                            update_option('wrshost_domain', $wrs_host_domain);
                            update_option('wrscat_filter', $wrs_cat_filter);
                            update_option('wrstags_filter', $wrs_tags_filter);
                            break;
                    }
                    
                } else {
                    $this->wrs_host_msg('The WordPress feed on ' . $wrs_host_domain . ' could not be read or may be invalid. Is WordPress installed there?');
                } 
                echo "<script>jQuery(document).ready(function(){getnewfeed();});</script>";
            } else {
                $wrs_host_domain = get_option('wrshost_domain', '');    
                $wrs_cat_filter = get_option('wrscat_filter', '');    
                $wrs_tags_filter = get_option('wrstags_filter', '');    
            }
            ?>
            <div class="wrap">
                <div id="icon-options-general" class="icon32"></div>
                <?php echo "<h2>" . __('WP RSS Syndication Feed Setup', 'wrs_trdom') . "</h2>"; ?>
                <div id="wrs-display" class="wrsrounded">
                    <?php echo "<h2>" . __('Remote WordPress Blog Settings', 'wrs_trdom') . "</h2>"; ?>
                    <form id="wrs_client_admin" name="wrs_client_admin" method="post" action="<?php echo get_bloginfo('url') . '/wp-admin/admin.php?page=wrsyndication_setup'; ?>">
                        <?php wp_nonce_field('wrs_client', 'nonce_wrs_client'); ?> 
                        <p>
                            <label for="wrs_gform_id">Domain of remote WordPress blog: </label><br />
                            <span>http:// </span>
                            <input name="wrs_host_domain" id="wrs_host_domain" value="<?php echo $wrs_host_domain; ?>" type="text" />
                            <span id="wrs_feed_cat"><?php echo (empty($wrs_cat_filter) ? "" : "/category/" . $wrs_cat_filter); ?></span>
                            <span>/feed/</span>
                            <span id="wrs_feed_tags"><?php echo (empty($wrs_tags_filter) ? "" : "?tag=" . $wrs_tags_filter); ?></span>
                            <input name="wrs_feed_path" id="wrs_feed_path" value="<?php echo $wrs_feed_path; ?>" type="hidden" /> 
                            <input name="csrfKey" id="csrfKey" value="<?php echo $csrfToken ?>" type="hidden" />
                            <input name="wrs_dir" id="wrs_dir" value="<?php echo WRS_PLUGIN_DIR; ?>" type="hidden" /> 
                            <input name="local_wrs_dir" id="local_wrs_dir" value="<?php echo str_replace(get_bloginfo('url'), "", WRS_PLUGIN_DIR); ?>" type="hidden" /> 
                            <input name="wrs_path" id="wrs_path" value="<?php echo WRS_PLUGIN_PATH; ?>" type="hidden" /> 
                            <input type="button"  name="wrs_host_domain_check" id="wrs_host_domain_check" class="button-secondary action" value="Check" />
                            <br class="clearboth" style="clear:both;" />
                        </p>
                        <p>
                            <label for="wrs_cat_filter">Filter by single Category (slug): </label><br />
                            <input name="wrs_cat_filter" id="wrs_cat_filter" value="<?php echo $wrs_cat_filter; ?>" type="text" />
                            <br class="clearboth" style="clear:both;" />
                        </p>
                        <p>
                            <label for="wrs_tags_filter">Filter by Tags (unspaced, comma separated): </label><br />
                            <input name="wrs_tags_filter" id="wrs_tags_filter" value="<?php echo $wrs_tags_filter; ?>" type="text" size="30" />
                            <br class="clearboth" style="clear:both;" />
                            <input type="submit" name="wrs_client_admin_submit" value="Save Setting" class="button-primary action" style="margin: 10px 0px; display:inline-block; clear: both; font-size: 10pt !important; padding: 5px 10px;" />
                        </p>
                    </form>
                    <div class="clearboth" style="clear:both;"></div>
                    <hr />
                    <h3>Feed Preview</h3>
                    <div id="wrs_display_feed"><p id="nofeed">No Feed Data</p></div>
                </div>
            </div>
            <?php        
        }
        function wrs_client_settings_page(){
            $wrs_host_domain = get_option('wrshost_domain', '');    
            $wrs_cat_filter = get_option('wrscat_filter', '');    
            $wrs_tags_filter = get_option('wrstags_filter', '');
            $wrs_feed_path = get_option('wrsfeedpath', 'feed?orderby=date&order=desc');
            $wrs_featured_active = get_option('wrs_featured_active', false);    
            if( ($isXML = $this->isRemoteXML("http://" . $wrs_host_domain . (empty($wrs_cat_filter) ? "" : "/category/" . $wrs_cat_filter) . "/" . $wrs_feed_path . (empty($wrs_tags_filter) ? "" : "&tag=" . $wrs_tags_filter))) && (file_exists(WRS_PLUGIN_PATH . 'tmp/wrs_feed.xml'))) {
                switch($isXML) {
                    case 'valid' :
                        $wrs_featured_active = "1";
                        break;
                    default :
                        $this->wrs_host_msg('Please set up and save a feed in the Feed Setup Page.');
                }
                
            } else {
                $this->wrs_host_msg('Please set up and save a feed in the Feed Setup Page.');
                $wrs_featured_active = "0";
            } 
            update_option('wrs_featured_active', $wrs_featured_active);
            if (isset($_POST['nonce_wrs_client'])) {
                check_admin_referer('wrs_client', 'nonce_wrs_client');
                $wrs_integration = isset($_POST['wrs_integration']) ? $_POST['wrs_integration'] : "";
                $wrs_front_page = isset($_POST['wrs_front_page']) ? $_POST['wrs_front_page'] : "";
                $wrs_posts_page = isset($_POST['wrs_posts_page']) ? $_POST['wrs_posts_page'] : "";
                $wrs_position = isset($_POST['wrs_position']) ? $_POST['wrs_position'] : "";
                $wrs_numposts = isset($_POST['wrs_numposts']) ? $_POST['wrs_numposts'] : "";
                $wrs_featured_guid = isset($_POST['wrs_featured_guid']) ? $_POST['wrs_featured_guid'] : "";
                $wrs_blog_subtitle = $_POST['wrs_blog_subtitle'];
                update_option('wrs_integration', $wrs_integration);
                update_option('wrs_front_page', $wrs_front_page);
                update_option('wrs_posts_page', $wrs_posts_page);
                update_option('wrs_position', $wrs_position);
                update_option('wrs_numposts', $wrs_numposts);
                update_option('wrs_featured_guid', $wrs_featured_guid);
                update_option('wrs_blog_subtitle', $wrs_blog_subtitle);
            } else {
                $wrs_integration = get_option('wrs_integration', '');    
                $wrs_front_page = get_option('wrs_front_page', '');    
                $wrs_posts_page = get_option('wrs_posts_page', '');    
                $wrs_position = get_option('wrs_position', 'loop_end');    
                $wrs_numposts = get_option('wrs_numposts', '3');    
                $wrs_featured_guid = get_option('wrs_featured_guid', '');    
                $wrs_blog_subtitle = get_option('wrs_blog_subtitle', '');    
            }
            $theme = get_template();
            ?>
            <div class="wrap">
                <div id="icon-options-general" class="icon32"></div>
                <?php echo "<h2>" . __('WP RSS Syndication Integration', 'wrs_trdom') . "</h2>"; ?>
                <div id="wrs-display" class="wrsrounded">
                    <?php echo "<h2>" . __('Blog Settings', 'wrs_trdom') . "</h2>"; ?>
                    <form id="wrs_blog_admin" name="wrs_blog_admin" method="post" action="<?php echo get_bloginfo('url') . '/wp-admin/admin.php?page=wrsyndication_blog'; ?>">
                        <?php wp_nonce_field('wrs_client', 'nonce_wrs_client'); ?> 
                        <p>
                            <label for="wrs_integration"><strong>Enable RSS Posts Integration? :</strong> <input name="wrs_integration" id="wrs_integration" value="1" type="checkbox" <?php if ( 1 == $wrs_integration ) echo 'checked="checked"'; ?> /></label>
                            <br class="clearboth" style="clear:both;" />
                        </p>
                        <p>
                            <label for="wrs_front_page"><strong>Display on Front Page? :</strong> <input name="wrs_front_page" id="wrs_front_page" value="1" type="checkbox" <?php if ( 1 == $wrs_front_page ) echo 'checked="checked"'; ?> /></label>
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            <label for="wrs_integration"><strong>Display on Posts Page? :</strong> <input name="wrs_posts_page" id="wrs_posts_page" value="1" type="checkbox" <?php if ( 1 == $wrs_posts_page ) echo 'checked="checked"'; ?> /></label>
                            <br class="clearboth" style="clear:both;" />
                        </p>
                        <p>
                            <label for="wrs_blog_subtitle">Sub-title above RSS Posts on Posts Page :</strong> <input name="wrs_blog_subtitle" id="wrs_blog_subtitle" value="<?php echo $wrs_blog_subtitle; ?>" type="text" /></label>
                            <br class="clearboth" style="clear:both;" />
                        </p>
                        <p>
                            <label for="wrs_position">RSS Posts Position: 
                                <select name="wrs_position" id="wrs_position"> 
                                <?php if ($theme == 'genesis') : ?>
                                    <option value="genesis_before_loop" <?php selected( $wrs_position, 'genesis_before_loop' ); ?>>Before Posts</option>
                                    <option value="genesis_after_loop" <?php selected( $wrs_position, 'genesis_after_loop' ); ?>>After Posts</option>
                                <?php else : ?>
                                    <option value="loop_start" <?php selected( $wrs_position, 'loop_start' ); ?>>Before Posts</option>
                                    <option value="loop_end" <?php selected( $wrs_position, 'loop_end' ); ?>>After Posts</option>
                                <?php endif; ?>                                
                                </select>                                     
                            </label>
                            <br class="clearboth" style="clear:both;" />
                        </p>
                        <p>
                            <label for="wrs_numposts">Maximum number of Posts to display: 
                                <input name="wrs_numposts" id="wrs_numposts" value="<?php echo $wrs_numposts; ?>" type="text" size="5" />
                            </label>
                            <br class="clearboth" style="clear:both;" />
                        </p>
                        <p>
                            <label for="wrs_featured_guid">Select Featured Post: 
                                <select name="wrs_featured_guid" id="wrs_featured_guid"> 
                                    <?php echo $this->ftEntriesOpt($wrs_featured_guid); ?>
                                </select>                                     
                            </label>
                            <br class="clearboth" style="clear:both;" />
                            <input type="submit" name="wrs_client_admin_submit" value="Save Setting" class="button-primary action" style="margin: 10px 0px; display:inline-block; clear: both; font-size: 10pt !important; padding: 5px 10px;" />
                        </p>
                    </form>
                    <div class="clearboth" style="clear:both;"></div>
                </div>
            </div>
            <?php        
            
        }
        function wrs_tmp_error(){
            $tmpdir = WRS_PLUGIN_PATH . "tmp";
        ?>    
            <div class = "wp_admin error"><p><strong>
            <?php
                _e ('WP RSS Syndication Client Temporary Directory is unwritable @ \'' . $tmpdir . '\'');
            ?></strong></p>
            </div>
        <?php    
        }
        function wrs_host_msg($msg='Something Went Wrong.', $state='error'){
        ?>    
            <div class = "wp_admin <?php echo ($state == 'error') ? "error" : "updated"; ?>"><p><strong>
            <?php
                _e ( $msg );
            ?></strong></p>
            </div>
        <?php    
        }
        function tmpdir(){
            $tmpdir = WRS_PLUGIN_PATH . "tmp";
            $old_mask = umask(0);
            if (file_exists($tmpdir)) {
                if (!is_writable($tmpdir)) {
                    if (false === chmod($tmpdir, 0775)) {
                        add_action('admin_notices', array(&$this, 'wrs_tmp_error'));
                    }
                }
            } else {
                if (!mkdir($tmpdir, 0775)) {
                    add_action('admin_notices', array(&$this, 'wrs_tmp_error'));
                }      
            }
            umask($old_mask);
        }
        function isRemoteXML($url) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            // Execute
            curl_exec($ch);
            //fill here the error/timeout checks.
            $http_code = curl_getinfo($ch,  CURLINFO_HTTP_CODE);
            if ($http_code == 0) return '500';
            if ($http_code == 404) return '404';
            curl_close($ch);
            $curl = curl_init();
            $useragent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1'; 
            curl_setopt($curl, CURLOPT_USERAGENT, $useragent);  
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true); 
            curl_setopt($curl, CURLOPT_VERBOSE, true); 
            $output = curl_exec($curl);
            if (simplexml_load_string($output)) {
                curl_close($curl);
                return 'valid';
            } else {
                curl_close($curl);
                return 'invalid';
            }
        }
        function ftEntriesOpt($selected) {
            $feed = WRS_PLUGIN_PATH . 'tmp/wrs_feed.xml';
            $output = "<option disabled = \"disabled\">Please Choose</option>\n";
            $data = file_get_contents($feed);
            try {
                $xmldoc = new SimpleXmlElement($data, LIBXML_NOCDATA);
                if(isset($xmldoc->channel)) {
                    $cnt = count($xmldoc->channel->item);
                    $counter = 0;
                    for($i=0; $i<$cnt; $i++) {
                        $url        = $xmldoc->channel->item[$i]->link;
                        $title      = $xmldoc->channel->item[$i]->title;
                        if (!empty($url) && !empty($title)) {
                            $content = "<option value=\"" . $url . "\"" . selected($selected, $url, false) . ">" . $title . "</option>\n";
                            $output .= $content . "\n";
                        }
                    }
                }
            } catch (Exception $e) {
                $output .= "<option disabled = \"disabled\">No Entries Found</option>\n";
            }
            return $output;
        }
    }
    // Initialization
    $wrsClient = new wrs_client_admin(WRS_PLUGIN_FILE); 

?>
