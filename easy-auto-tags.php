<?php
/*
Plugin Name: Easy auto tags
Plugin URI: http://wpmart.com
Author:  Samaneh Mirrajabi
Author URI: http://pgacompany.com
Description: Generate post tags from post title and content with Google
Version: 1.2
*/

if (!function_exists('str_get_html')){
	include_once('libs/simple-html-dom.php');
}
	
/*
** Add textdomain to plugin
*/
function smj_easy_auto_tags_textdomain() {
  load_plugin_textdomain( 'easy-auto-tags', false, basename( dirname( __FILE__ ) ) . '/languages' ); 
}
add_action( 'init', 'smj_easy_auto_tags_textdomain' );
/*
** Add ajax juery and css to plugin
*/
function smj_enqueue_easy_auto_tags() {
    wp_enqueue_script( 'easy_auto_tags_script', plugins_url('assets/js/easy-auto-tags.js', __FILE__) , array ( 'jquery' ), 1.1, false);
    wp_localize_script( 'easy_auto_tags_script', 'easy_auto_tags_ajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
    wp_enqueue_style( 'easy_auto_tags_script', plugins_url('assets/css/easy-auto-tags.css', __FILE__));
}
add_action( 'admin_enqueue_scripts', 'smj_enqueue_easy_auto_tags' );
/*
** Setting auto tags plugin 
*/
add_action( 'admin_menu', 'smj_easy_auto_tags_page' );
function smj_easy_auto_tags_page() {
   add_menu_page( __('Auto Tags', 'easy-auto-tags'), __('Auto Tags', 'easy-auto-tags'), 'manage_options', 'easy_auto_tags', 'smj_easy_auto_tags_function','dashicons-tag');
}
function smj_easy_auto_tags_function() {
        if(isset($_POST['nonce_setting_auto_tags']) && wp_verify_nonce( $_POST['nonce_setting_auto_tags'], 'nonce_auto_tags' ) && check_admin_referer( 'nonce_auto_tags' , 'nonce_setting_auto_tags')){
            update_option('min_ch',sanitize_text_field($_POST['min_ch']));
            update_option('repeat_ch',sanitize_text_field($_POST['repeat_ch']));
            update_option('exclude_word',sanitize_text_field($_POST['exclude_word']));
            echo '<div class="notice notice-success is-dismissible"><p>'.__('Settings saved successfully.', 'easy-auto-tags').'</p></div>';
        }
?>
<div class="wrap">
	<h1><?php _e( 'Auto Tags', 'easy-auto-tags' ); ?></h1>
<form method="post">
	<table class="widefat importers striped">
		<tbody>
			<tr class="importer-item">
				<td class="import-system">
					<span class="importer-title"><label><?php _e('Minimum character', 'easy-auto-tags'); ?></label></span>
				</td>
				<td class="desc">
				  <input type="number" size="10" name="min_ch" value="<?php $number_ch=esc_html(get_option('min_ch'));  echo empty($number_ch)?'4':$number_ch;  ?>" />
				  <br><small><?php _e( "Note, please use words atleast 4 character to prevent generate tags from something like 'that, or, from' and ...", 'easy-auto-tags' ); ?></small>
				</td>
			</tr>
			<tr class="importer-item">
				<td class="import-system">
					<span class="importer-title"><label><?php _e('Minimum repetition', 'easy-auto-tags'); ?></label></span>
				</td>
				<td class="desc">
				  <input type="number" size="10" name="repeat_ch" value="<?php $number_re=esc_html(get_option('repeat_ch')); echo empty($number_re)?'1':$number_re;  ?>"/>
				  <br><small><?php _e( "Minimum repetition of words", 'easy-auto-tags' ); ?></small>
				</td>
			</tr>
			<tr class="importer-item">
				<td class="import-system">
					<span class="importer-title"><label><?php _e('Exclude words', 'easy-auto-tags'); ?></label></span>
				</td>
				<td class="desc">
				  <textarea name="exclude_word" cols="20" rows="8" placeholder="<?php _e( 'that, from, was and ...', 'easy-auto-tags' ); ?>"><?php $textarea=esc_textarea(get_option('exclude_word')); echo empty($textarea)?'':$textarea;  ?></textarea>
				  <br><small><?php _e( "Please use | as separator", 'easy-auto-tags' ); ?></small>
				</td>
			</tr>
		</tbody>
	</table>
    <?php wp_nonce_field( 'nonce_auto_tags' , 'nonce_setting_auto_tags' ); ?>
	<p class="submit"><input type="submit" class="button-primary" name="save" value="Save Settings" /></p>
</form>
<?php
}
/**
*  get id post and array tags so show  auto tags
**/
function smj_show_list_auto_tags($id,$list_true_tag){
    $result='';
    $term=  wp_get_post_terms($id, 'post_tag', array( 'fields' => 'names' ) ); 
    $result .= '<strong class="count_tags" >'. __('All Tags:', 'easy-auto-tags').sizeof($list_true_tag).'</strong><table class="widefat fixed list-easy-auto-tags" data-nonce="'. wp_create_nonce('smj_table_ajax_nonce').'">
                <thead>
                    <tr>
                        <th><small>'.__('Tags', 'easy-auto-tags').'</small></th>
                        <th><small>'.__('Content', 'easy-auto-tags').'</small></th>
                        <th><small>'.__('Google', 'easy-auto-tags').'</small></th>
                        <th><small>'.__('Add', 'easy-auto-tags').'</small></th>
                    </tr>
                </thead>
                <tr class="overly"><td colspan="4">'.__('Add tags successfully', 'easy-auto-tags').'</td></tr>';
                foreach($list_true_tag as $key=>$tag){
                    $result .= '<tr>
                                <td class="tag">'.esc_html($tag['name']).'</td>
                                <td>'.esc_html($tag['count_content']).'</td>
                                <td>'.esc_html($tag['count']).'</td>
                                <td class="add_tags"  id="'.esc_html($id).'" ><span data-id="'.esc_html($key).'" class="dashicons '.(in_array($tag["name"], $term) ? "dashicons-yes" : "dashicons-plus") .'"></span></td>
                    </tr>';   
                }
    $result .= '</table>';
    return $result;
}

add_action( 'wp_ajax_smj_generate_auto_tags_by_google', 'smj_generate_auto_tags_by_google' );
function smj_generate_auto_tags_by_google(){
    if (isset($_POST['_ajax_nonce']) && isset($_POST['id'])){
        if(!wp_verify_nonce( $_POST['_ajax_nonce'], 'smj_ajax_nonce' ) || !check_ajax_referer( "smj_ajax_nonce" ))
            wp_die();
                $id=sanitize_text_field($_POST['id']);
                set_time_limit(0);
                global $post,$wpdb;
                $repeat_ch=esc_html(get_option('repeat_ch'));
                $exclude_word=esc_html(get_option('exclude_word'));
                $min_ch=esc_html(get_option('min_ch'));
                $list_true_tag=array();
                $title = get_the_title($id);
                $term=  wp_get_post_terms( $id, 'post_tag', array( 'fields' => 'names' ) ); 
                $content = $wpdb->get_var(
                    $wpdb->prepare(
                        "select post_content from $wpdb->posts where ID = %d",
                        $id
                    )
                );
                $content =esc_html($content);
                $filterwords=array(".","+","-" ,"=" ,"!" ,"~" ,"#" ,"$" ,"%" ,"^" ,"&" ,"*" ,"(" ,")" ,"'\'" ,"|" ,"/" ,"}" ,"{" ,"[" ,"]" ,":" ,";" ,"?" ,">" ,"," ,"<" ,"`" ,"_" ,"@" ,"»" ,"«");  
                $name_tags=array();
                $excludewords=array();
                $exclude= explode("|",$exclude_word);
                // add exculd words to filter words
                foreach($exclude as $ex){   
                    array_push($excludewords,$ex);
                }
                // replace "+,-..." and exclude word to " " in title and content
                $title=strtolower($title);
                $title=str_replace($filterwords, "", $title);
                $title=str_replace($excludewords, " ", $title);

                $content=str_replace($filterwords, "", $content);
                $content=strtolower($content);
                //remove repeat word from title
                $title=implode(' ', array_unique(explode(' ', $title)));
                $words = preg_split('/\s+/', $title);
                // loop for read title after filter
                for ($i=0; $i<count($words); $i++){ 
                    if(function_exists('mb_strlen')){
                        // count true character perisan word  
                        $count_ch= mb_strlen( $words[$i] , 'utf-8' );
                        //count(preg_split('//u', $words[$i])) - 2;
                    }
                    if($count_ch >= $min_ch){
                        //count repeat word in content
                        $substringCount = substr_count($content, $words[$i]);
                        //print_r( "<br/>". $count_ch .'-'. $words[$i] . " - " . $substringCount);
                        
                        if($substringCount >= $repeat_ch){
                            //add true word after filter
                            array_push($name_tags,$words[$i]);
                        }
                    }
                }
                //$name_tags : array words after filter title and content post
                //Create Combined Words
                $p = array(array(array_shift($name_tags))); 
                foreach($name_tags as $word) { 
                    $a = $p; 
                    $b = $p;  
                    $s = count($p); 
                    $p = array(); 
                    for($i=0;$i<$s;$i++) { 
                        $a[$i][] = $word; 
                        $b[$i][count($b[$i])-1] .= " ".$word; 
                    }
                    $p = array_merge($a,$b); 
                }
                //convert  two dimension to one dimension
                $out = array();
                foreach($p as $arr){ 
                    $out = array_merge($out, $arr);
                }
                // read tags so search to google by fuction simple_dom_goolge
                foreach (array_unique($out) as $tag){
                    $name_tags = preg_split('/\s+/', $tag);
                    if(count($name_tags) <= 3){
                        //echo   '<br/>'.$tag.count($name_tags);  
                        $list_tags='';
                        $search_tag=' ';
                        $url= str_replace(" ", "+", $tag);
                        $urls = 'https://www.google.com/search?q='.$url.'&ie=utf-8&oe=utf-8';

						$wrgargs = array(
						    'user-agent'  =>  'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8) AppleWebKit/535.6.2 (KHTML, like Gecko) Version/5.2 Safari/535.6.2',
						); 

				        $response = wp_remote_get( $urls, $wrgargs);
                        $str = wp_remote_retrieve_body( $response );
                        
                        $html = str_get_html($str);
                        if(!empty($html) &&  count($html->find('.st')) > 0 ) {
                            foreach($html->find('.st') as  $item){
                                //get to discription google
                                $search_tag .= strtolower($item->innertext); 
                            }  
                            preg_match_all("/({$tag})/iu", $search_tag, $pat_array);
                            // search tags to content post
                            preg_match_all ("/({$tag})/iu", $content, $count);
                            $array_tags = array();
                            $array_tags['name'] =sanitize_text_field($tag);
                            $array_tags['count_content'] = sanitize_text_field(count($count['0']));
                            $array_tags['count'] = sanitize_text_field(count($pat_array['0']));
                            $array_tags['add'] = sanitize_text_field((in_array($array_tags['name'], $term) ? "dashicons-yes" : "dashicons-plus")) ;
                            if($array_tags['count'] > 3 && $array_tags['count_content'] >= $repeat_ch ){
                                $list_true_tag[]=$array_tags;  
                            }
                        }
                    }
                }
                if(!empty($list_true_tag)){
                    echo smj_show_list_auto_tags($_POST["id"],$list_true_tag);
                    //$value = sanitize_text_field( $list_true_tag );
                    update_post_meta($_POST["id"], '_results_tags', $list_true_tag);  
                }
                else {
                    _e('Sorry, we can\'t find any appropriate tags based on the post title and content.', 'easy-auto-tags'); 
                }
    }
}
/*
** Save tags that come from result ajax
*/
//add_action('save_post', 'smj_save_easy_auto_tags');
add_action( 'wp_ajax_smj_save_easy_auto_tags', 'smj_save_easy_auto_tags' );
function smj_save_easy_auto_tags(){
    if (isset($_POST['_ajax_nonce'])){
        if(!wp_verify_nonce( $_POST['_ajax_nonce'], 'smj_table_ajax_nonce' )) {
            die();
        }
            check_ajax_referer( "smj_table_ajax_nonce" );
            $tag_name=sanitize_text_field($_POST['tag_name']);
            $post_id=sanitize_text_field($_POST['post_id']);
            $array_id=sanitize_text_field($_POST['array_id']);
            wp_set_object_terms( $post_id,$tag_name, 'post_tag', true );
            $result_tags=get_post_meta($post_id, '_results_tags',true );
            $result_tags[$array_id]['add']=sanitize_text_field('dashicons-yes');
            //$value = sanitize_text_field( $result_tags );
            update_post_meta($post_id, '_results_tags',$result_tags );
            die();
    }
}
/*
** show suggested tags
*/
add_action("admin_init", "smj_admin_initat"); 
function smj_admin_initat(){  
    add_meta_box("Show_easy_auto_tags", "Auto Tags", "smj_create_easy_auto_tags", "post", "side", "low");  
}
function smj_create_easy_auto_tags(){  
    global $post;
    $post = get_post($post);
    echo '<input type="hidden" class="check_title" value="'. ($post->post_title ? 1 : 0 ) .'" >';
?>
    <div class="title_auto_tags">
        <button type="button" name="generate_tags"  class="button-primary generate_tags " data-nonce="<?php echo wp_create_nonce('smj_ajax_nonce'); ?>" data-id="<?php echo  $post->ID ?>">Generate <span class="spinner"></span></button>
        
    </div>
    <div class="results-easy-auto-tags">
        <?php 
            $list_true_tag=get_post_meta($post->ID, '_results_tags',true );
            if(isset($list_true_tag) && !empty($list_true_tag)){
                echo smj_show_list_auto_tags($post->ID,$list_true_tag);
            }
        ?>
    </div>
 
<?php 
} 
?>
