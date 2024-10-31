<?php
/**
 * User: Apexol
 * Date: 21-May-16
 * Time: 11:17 AM
 */
class UrlShortnerDb
{
    function __construct(){

    }

    function _get_table_name(){
        global $wpdb;
        $table_name = $wpdb->prefix . "shortslugs";
        return $table_name;
    }
    function install(){
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS ". $this->_get_table_name() ."(
              id mediumint(9) NOT NULL AUTO_INCREMENT,
              slug text NOT NULL,
              post_id mediumint(9) NOT NULL,
              UNIQUE KEY id (id)
            ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta($sql);
    }

    function save_slug($slug, $post_id){
        //check if this slug exist?
        $permalink = $this->get_post_link($slug);
        if($permalink == null){
            global $wpdb;
            $data = array(
                "slug" => $slug,
                "post_id" => $post_id
            );
            $wpdb->insert($this->_get_table_name(), $data);
            return true;
        }
        return false;
    }

    function get_post_link($slug){
        global $wpdb;
        $sql = "SELECT post_id FROM " . $this->_get_table_name() . " WHERE slug=%s";

        $post_id = $wpdb->get_var($wpdb->prepare($sql, $slug));
        if(empty($post_id))
            return null;

        return get_permalink($post_id);
    }

    function get_short_slug_by_id($post_id){
        global $wpdb;
        $sql = "SELECT slug FROM " . $this->_get_table_name() . " WHERE post_id=%d";

        $slug = $wpdb->get_var($wpdb->prepare($sql, $post_id));
        if(empty($slug))
            return null;

        return $slug;
    }
}