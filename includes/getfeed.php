<?php
    /*ini_set('display_errors', 1);
    error_reporting(E_ALL | E_WARN);*/
    session_start();
    if($_POST['csrfKey'] != $_SESSION['csrfToken']) {
        echo 0;
    } else{
        $outcome = getfeed($_POST['feeduri'], $_POST['wrspath']);    
        if ($outcome) echo 1;
        else echo 0;
    }

    function getfeed($feeduri, $path) {
        $success = false;
        if (!class_exists('SimpleXmlElement')) {
            return false;
        }
        $rssfile = $path . "tmp/wrs_feed.xml";
        if (file_exists($rssfile)) {
            chmod($rssfile, 0777);
            unlink($rssfile);
        }   
        /*Transfer RSS XML*/
        $useragent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1'; 
        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_USERAGENT, $useragent);  
        curl_setopt($ch, CURLOPT_URL, $feeduri); 
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
            curl_close($ch); 
            return false;
        }

        curl_close($ch); 
        if ($xmldoc = new SimpleXmlElement($data, LIBXML_NOCDATA)) {
            $rsshandle = fopen($rssfile, 'w');
            if (fwrite($rsshandle, $data)) {
                $success = true;    
            }
            fclose($rsshandle);
            chmod($rssfile, 0777);
        }    
        return $success;
    }    
?>
