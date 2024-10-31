<?php
/*
Plugin Name: Roasted Url Shortener
Plugin URI: http://www.roastedbytes.com
Description: Simple URL shortener plugin for your wordpress site
Version: 1.0
Author: Roasted Bytes Team
Author URI: http://wittylog.com
License: GPLv2 or later
*/
require_once("urlshortnerdb.php");
class RoastedUrlShortner
{
    const SLUG_LENGTH = 5;
    private $_db;
    function __construct(){
        $this->_db = new UrlShortnerDb();

        add_action("wp_loaded", array($this, "redirect_if_found"));
        add_action( 'save_post', array($this, "save_slug") );
        add_action( 'edit_form_before_permalink', array($this, "show_shorturl") );

    }

    function redirect_if_found(){
        $url = wp_parse_url($_SERVER['REQUEST_URI']);
        $path = $url['path'];
        if($path == "/"){
            //do nothing
           return;
        }
        $path = substr($path, 1);
        $path_parts =  explode("/", $path);

        if(count($path_parts) == 0 || empty($path_parts[0])){
                return;
        }

        //check if the slug is in db
        $slug = $path_parts[0];

        $permalink = $this->_db->get_post_link($slug);
        if(!empty($permalink)){
            //we'll do a permanent redirection. Good for SEO
            $query = $url['query'];

            if(!empty($query)){
                $queryArr = array();
                parse_str($query, $queryArr);
                $permalink = add_query_arg($queryArr, $permalink);
            }
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: $permalink");
            exit;
        }
    }

    function install(){
        $this->_db->install();
    }

    function save_slug($post_id){
        // verify if this is an auto save routine.
        // If it is our form has not been submitted, so we dont want to do anything
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
            return null;

        //is it already saved?
        $slug = $this->_db->get_short_slug_by_id($post_id);
        if($slug != null)
            return $slug; //do nothing, we already have saved it's permalink

        //create a new slug
        $slug = $this->generate_random_slug();

        //does this slug already exist? chances are rare but better safe than sorry
        while(!$this->_db->save_slug($slug, $post_id)){
            $slug = $this->generate_random_slug();//another slug
        }

        return $slug;
    }

    function show_shorturl($post, $force_generate = true){
        //not for pages
        if($post->post_type === "page" || $post->ID == 0 || $post->post_status=="auto-draft"){
            return;
        }
        //first check if the $post is saved or not
        //is it already saved?
        $slug = $this->_db->get_short_slug_by_id($post->ID);
        if($slug == null){
            if(!$force_generate)
                return;
            //save a slug
            $slug = $this->save_slug($post->ID);
        }
        $url = get_bloginfo("url") . "/" . $slug;
        ?>
        <div class="inside" style="padding: 0 10px;margin: 15px 0">
            <strong>Short Url: </strong>
            <a href="<?php echo $url; ?>" target="_blank"><?php echo $url; ?></a>
            <button type="button" onclick="prompt('Short Url for your post', '<?php echo $url; ?>')" class="button button-small">Copy</button>
        </div>
        <?php
    }
    function generate_random_slug(){
        $characters = '23456789abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ-_';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < self::SLUG_LENGTH; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}

$urlShortner = new RoastedUrlShortner();
//register activation hook
register_activation_hook(__FILE__, array($urlShortner, "install"));