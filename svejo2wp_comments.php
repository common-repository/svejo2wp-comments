<?php
/*
Plugin Name: Svejo2WP comments
Plugin URI: http://yurukov.net/blog/2010/02/21/svejo2wp-komentari-veche-raboti-i-ima-admin-pa/
Description: Load comments from Svejo into a Wordpress blog
Version: 1.4.1
Author: Boyan Yurukov
Author URI: http://yurukov.net/blog

*/

$svejo2wp_db_version = "1.4";

$svejo2wp_settings_default = array(
	'svejo2wp_exclude_username'	=> ''
);
$svejo2wp_template_default = "<i><a target='_blank' href='%svejo_comment_url%'>Svejo</a>:</i> %comment_content%";

add_option('svejo2wp_settings',$svejo2wp_settings_default,'','yes');
add_option('svejo2wp_template',$svejo2wp_template_default,'','yes');

$svejo2wp_settings = get_option('svejo2wp_settings');
$svejo2wp_template = get_option('svejo2wp_template');
$svejo2wp_flash = "";

function svejo2wp_is_authorized() {
	global $user_level;
	if (function_exists("current_user_can")) {
		return current_user_can('activate_plugins');
	} else {
		return $user_level > 5;
	}
}

function svejo2wp_is_hash_valid($form_hash) {
	$saved_hash = svejo2wp_retrieve_hash();
	return $form_hash === $saved_hash;
}

function svejo2wp_generate_hash() {
	return md5(uniqid(rand(), TRUE));
}

function svejo2wp_store_hash($generated_hash) {
	return update_option('svejo2wp_token',$generated_hash);
}

function svejo2wp_retrieve_hash() {
	return get_option('svejo2wp_token');
}

function svejo2wp_add_options_page() {
	if (function_exists('add_options_page')) {
		add_options_page('Svejo2WP', 'Svejo2WP Comments', 8, basename(__FILE__), 'svejo2wp_options_subpanel');
	}
}

function svejo2wp_flash_clear() {
	global $svejo2wp_flash;
	$svejo2wp_flash="";
}

function svejo2wp_flash_add($message,$error=null) {
	global $svejo2wp_flash;
	$svejo2wp_flash .= ($svejo2wp_flash!=''?"<br/>":"");
	$svejo2wp_flash .= ($error?"<span style='color:red'>":"").$message.($error?"</span>":"");
}

function svejo2wp_options_subpanel() {
	global $svejo2wp_flash, $svejo2wp_settings,$svejo2wp_template,$svejo2wp_settings_default,$svejo2wp_template_default, $_POST, $wp_rewrite;
	if (svejo2wp_is_authorized()) {
		if(isset($_POST['svejo2wp_updated']) || isset($_POST['restore_to_defaults'])) {
			if(svejo2wp_is_hash_valid($_POST['token'])) {
				svejo2wp_flash_clear();
				if(isset($_POST['svejo2wp_updated'])) {

					if (isset($_POST['svejo2wp_exclude_username']) && 
				 	    $_POST['svejo2wp_exclude_username']!=$svejo2wp_settings['svejo2wp_exclude_username']) { 
						$svejo2wp_settings['svejo2wp_exclude_username'] = $_POST['svejo2wp_exclude_username'];
						update_option('svejo2wp_settings',$svejo2wp_settings);
						svejo2wp_flash_add(__("Excluded usernames has been saved.",'svejo2wp_comments'));
					} 
					if (isset($_POST['svejo2wp_template'])) { 
						$svejo2wp_template_temp = $_POST['svejo2wp_template'];
						$svejo2wp_template_temp = str_replace("\'","'",$svejo2wp_template_temp);
						$svejo2wp_template_temp = str_replace('\"','"',$svejo2wp_template_temp);
						if ($svejo2wp_template_temp!=$svejo2wp_template) {
							$svejo2wp_flash .= ($svejo2wp_flash!=''?"<br/>":"");
							if ($_POST['svejo2wp_template']!="" && strpos($_POST['svejo2wp_template'],"%comment_content%")!==false) {
								$svejo2wp_template = $svejo2wp_template_temp;
								update_option('svejo2wp_template',$svejo2wp_template);
								svejo2wp_flash_add(__("Comment template has been saved.",'svejo2wp_comments'));
							} else
								svejo2wp_flash_add(__("Comment template should contain",'svejo2wp_comments'). " %comment_content%","error");
						}
					}
				} else
				if(isset($_POST['restore_to_defaults'])) {
					update_option('svejo2wp_settings',$svejo2wp_settings_default);
					update_option('svejo2wp_template',$svejo2wp_template_default);
					$svejo2wp_settings=$svejo2wp_settings_default;
					$svejo2wp_template=$svejo2wp_template_default;
					svejo2wp_flash_add(__('Settings restored to defaults.','svejo2wp_comments'));
				} 
			} else 
				svejo2wp_flash_add(__('Security hash missing.','svejo2wp_comments'),'error');
		}
	} else
		svejo2wp_flash_add(__("You don't have enough access rights.",'svejo2wp_comments'),'error');
	
	if ($svejo2wp_flash != '') echo '<div id="message" class="updated fade"><p>' . $svejo2wp_flash . '</p></div>';
	
	if (svejo2wp_is_authorized()) {
		$temp_hash = svejo2wp_generate_hash();
		svejo2wp_store_hash($temp_hash);
		echo '<div class="wrap">
		<h2>'.__("Svejo2WP Settings",'svejo2wp_comments').'</h2>
		<img src="'. WP_PLUGIN_URL.'/'.basename(dirname(__FILE__)).'/img/svejo_wp_logo.png" style="margin:0 20px 20px 20px; float:right;border:1px solid gray;"/>
		<p>'.__("This plugin makes it possible to load comments on the blog post from",'svejo2wp_comments').' <a href="http://svejo.net/" target="_blank">Svejo.net</a>.</p>
		<p>'.__("Created by",'svejo2wp_comments').' <b><a href="http://yurukov.net/blog">'.__("Boyan Yurukov",'svejo2wp_comments').'</a></b>. '.__("Visit my",'svejo2wp_comments').' <a href="http://yurukov.net/blog">'.__("blog",'svejo2wp_comments').'</a> '.__("for more updates and news",'svejo2wp_comments').'.</p>
		<form action="" method="post">
		<input type="hidden" name="redirect" value="true" />
		<input type="hidden" name="token" value="' . svejo2wp_retrieve_hash() . '" />
		<input type="hidden" name="svejo2wp_updated" value="true" />
		<table class="form-table">
		<tr valign="top">
		<th scope="row">'.__("Exclude Svejo usernames",'svejo2wp_comments').'</th>
		<td>
		<p><input type="text" name="svejo2wp_exclude_username" value="' . htmlentities($svejo2wp_settings['svejo2wp_exclude_username'],ENT_NOQUOTES,"UTF-8") . '" size="50" /></p>
		<p><i>'.__("Comma separated. Comments by these authors will not be added. When an author is added, his/her old comments will be kept. When an author is removed, all his/hers old comments will eventually be imported from Svejo.",'svejo2wp_comments').'</i></p>
		</td>
		</tr>
		<tr valign="top">
		<th scope="row">'.__("Comment template",'svejo2wp_comments').'</th>
		<td>
		<p>'.__("You can use the following tags",'svejo2wp_comments').':</p>
		<p><i>%svejo_comment_url% - '.__("address of the comment page",'svejo2wp_comments').'<br/> 
		%svejo_story_url% - '.__("address of the voting page",'svejo2wp_comments').'<br/> 
		%svejo_author_url% - '.__("address of the author's page",'svejo2wp_comments').'<br/> 
		%comment_date% - '.__("comment date",'svejo2wp_comments').'<br/> 
		%comment_author% - '.__("the name of the author",'svejo2wp_comments').'<br/> 
		%comment_content% - '.__("the text of the comment (required)",'svejo2wp_comments').'</i><p>
		<p><textarea cols="50" rows="3" name="svejo2wp_template">'.htmlentities($svejo2wp_template,ENT_NOQUOTES,"UTF-8").'</textarea></p>
		</td>
		</tr>
		</table>
		<p class="submit"><input class="button-primary" type="submit" value="'.__("Save",'svejo2wp_comments').'" /></p></form>
		<p>'.__("Restore settings to default.",'svejo2wp_comments').' <i>'.__("This will undo all your changes!",'svejo2wp_comments').'</i></p>
		<form action="" method="post">
		<input type="hidden" name="redirect" value="true" />
		<input type="hidden" name="token" value="' . svejo2wp_retrieve_hash() . '" />
		<input type="hidden" name="restore_to_defaults" value="true"/>
		<p class="submit"><input class="button-primary" type="submit" value="'.__("Restore to defaults",'svejo2wp_comments').'" /></p></form>
		</div>';
	} else {
		echo '<div class="wrap"><p>'.__('Sorry, you are not allowed to access this page.','svejo2wp_comments').'</p></div>';
	}

}


function svejo2wp_loadscript() {
	global $post;
	if (!is_single() || !isset($post))
		return;

	$svejo2wp_plugin_folder = WP_PLUGIN_URL.'/'.basename(dirname(__FILE__)).'/';

	echo "
<script>
<!--// 
  svejo2wp_loaderpath = '$svejo2wp_plugin_folder';
  svejo2wp_postid = '".$post->ID."';
//-->
</script>
<script src=\"".$svejo2wp_plugin_folder."svejo2wp_loader.js\" type=\"text/javascript\"></script>
	";

}


function svejo2wp_install() {
   global $wpdb;
   global $svejo2wp_db_version;

   $table_name = $wpdb->prefix . "svejo_comments";
   if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
      
      $sql = "CREATE TABLE IF NOT EXISTS " . $table_name . " (
		`comment_ID` bigint(20) NOT NULL,
		`svejo_id` bigint(20) NOT NULL,
		`svejo_author_url` varchar(200) NOT NULL,
		`svejo_avatar` varchar(200) NOT NULL,
		PRIMARY KEY  (`svejo_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      dbDelta($sql);
 
      add_option("svejo2wp_db_version", $svejo2wp_db_version);
   }
}

function svejo2wp_avatar($comment, $size, $default) {
   	global $wpdb;
	if (!isset($comment) || !$comment || !isset($comment->comment_agent))
		return "";
	if ($comment->comment_type=='' && $comment->comment_agent=='Svejo') {
		$table_name = $wpdb->prefix . "svejo_comments";
		$yuri_query="select svejo_author_url,svejo_avatar from $table_name where comment_ID=".$comment->comment_ID." limit 1";
		$yuri_query_res = mysql_query($yuri_query);
		if ($yuri_query_res && $row = mysql_fetch_assoc($yuri_query_res)) {
			echo "<img alt='' src='".$row['svejo_avatar']."' class='avatar avatar-$size' width='$size' />";
			return;
		}
	}
	get_avatar( $comment, $size, $default ); 
}

function svejo2wp_loadmo() {
	$currentLocale = get_locale();
	if(!empty($currentLocale)) 
		$currentLocale="bg_BG";
	$moFile = dirname(__FILE__) . "/lang/svejo2wp_" . $currentLocale . ".mo";
	if(@file_exists($moFile) && is_readable($moFile))
		load_textdomain('svejo2wp_comments', $moFile);
}	


svejo2wp_loadmo();
register_activation_hook(__FILE__,'svejo2wp_install');
add_action('admin_menu', 'svejo2wp_add_options_page');
add_action('wp_footer', 'svejo2wp_loadscript');


?>
