<?php
error_reporting(1);
include "../../../wp-blog-header.php";

if (!(isset($_GET["post_id"]) && is_numeric($_GET["post_id"])))
	exit;

$svejo2wp_settings = get_option('svejo2wp_settings');
$svejo2wp_template = get_option('svejo2wp_template');
if ($svejo2wp_settings!=null && is_array($svejo2wp_settings)) {
	$excluded_names=explode(',',$svejo2wp_settings['svejo2wp_exclude_username']);
	for ($i=0;$i<count($excluded_names);$i++)
		$excluded_names[$i]=trim($excluded_names[$i]);
} else
	$excluded_names=array();

$table_name = $wpdb->prefix . "svejo_comments";
$post_id=intval($_GET["post_id"]);

$urls=array();
$urls[]=get_permalink($post_id);

$url_temp=get_post_meta($post_id, 'svejo_link', true);
if ($url_temp!='')
	$urls[]=$url_temp;

$url_temp=get_post_meta($post_id, 'svejo_alt_link', false);
if (is_array($url_temp))
	foreach ($url_temp as $oneurl)
		$urls[]=$oneurl;
else if ($url_temp!='')
	$urls[]=$url_temp;

if (!$urls || $urls=="" || count($urls)==0)
	exit;

$count=0;
foreach ($urls as $url) {


$content=file_get_contents("http://svejo.net/public_api/comments/show/xml?url=".$url);
if ($content===false)
	continue;
$xml = new SimpleXMLElement($content);
$records = $xml->xpath('/records/record');
if (count($records)==0)
	continue;

$comment_ids=array();
foreach ($records as $onerecord) 
	$comment_ids[]=$onerecord->id;

$yuri_query="select svejo_id from $table_name where svejo_id in ('".implode("','",$comment_ids)."')";
$yuri_query_res = mysql_query($yuri_query);
if (!$yuri_query_res) 
	continue;

$comment_inserted_ids=array();
while ($row = mysql_fetch_assoc($yuri_query_res))
	$comment_inserted_ids[]=$row['svejo_id'];
$comment_new_id=array_diff($comment_ids,$comment_inserted_ids);

foreach ($comment_new_id as $svejo_id) {
	$yuri_query="select svejo_id from $table_name where svejo_id=".$svejo_id;
	$yuri_query_res = mysql_query($yuri_query);
	if (!$yuri_query_res || mysql_num_rows($yuri_query_res)>1)
		continue; 
	
	$data = $xml->xpath('/records/record[id='.$svejo_id.']');
	$data = $data[0];
	
	$b='0';
	$a='url-for-avatar';	$svejo_avatar=$data->$a."";
	$a='username'; 		$comment_author=$data->$a."";
	$a='url'; 			$svejo_comment_url=$data->$a."";
	$a='url-for-story'; 		$svejo_story_url=$data->$a."";
	$a='url-for-user'; 		$svejo_author_url=$data->$a."";
	$a='created-at'; 		$comment_date=$data->$a."";
	$a='content'; 			$comment_content=$data->$a."";

	if (in_array($comment_author,$excluded_names))
		continue;

	if (strpos($svejo_avatar,'http://')===false)
		$svejo_avatar='http://svejo.net'.$svejo_avatar;
	else
	if (strpos($svejo_avatar,'http://www.gravatar.com')!==false)
		$svejo_avatar=str_ireplace("s=45&d=identicon","s=80",$svejo_avatar);
	if (strpos($svejo_avatar,'http://svejo.net')!==false)
		$svejo_avatar=str_ireplace("-thumbnail.jpg","-profile.jpg",$svejo_avatar);


	$yuri_query =	"INSERT INTO $table_name (comment_ID,svejo_id,svejo_author_url,svejo_avatar)
			 	VALUES ('0','$svejo_id','$svejo_author_url','$svejo_avatar')";
	mysql_query($yuri_query);

	$commentdata=array(
		"comment_post_ID"=> (int) $post_id,
		"comment_author" => $comment_author,
		"comment_author_email" => get_option('admin_email','admin@yurukov.net'),
		"comment_author_url" => $svejo_author_url,
		"comment_approved" => 1,
		"user_id" => 0,
		"comment_subscribe" => "N",
		"comment_content" => $comment_content
	);

	if ($svejo2wp_template!="") {
		$comment_template=$svejo2wp_template;
		$comment_template = str_replace("%svejo_comment_url%",$svejo_comment_url, $comment_template);
		$comment_template = str_replace("%svejo_story_url%",$svejo_story_url, $comment_template);
		$comment_template = str_replace("%svejo_author_url%",$svejo_author_url, $comment_template);
		$comment_template = str_replace("%comment_date%",$comment_date, $comment_template);
		$comment_template = str_replace("%comment_author%",$comment_author, $comment_template);
		$comment_template = str_replace("%comment_content%",$comment_content, $comment_template);
	} else 
		$comment_template = "<i><a target='_blank' rel='nofollow' href='$svejo_comment_url'>Svejo</a>:</i> ".$commentdata['comment_content'];

	$commentdata['comment_author_IP'] = preg_replace( '/[^0-9., ]/', '',$_SERVER['REMOTE_ADDR'] );
	$commentdata['comment_agent']     = "Svejo";
	$commentdata['comment_date']     = date("Y-m-d H:i:s",strtotime($comment_date)+2*60*60);
	$commentdata['comment_date_gmt'] = date("Y-m-d H:i:s",strtotime($comment_date)-0*60*60);
	$commentdata['comment_content']      = apply_filters('pre_comment_content', $commentdata['comment_content']);
	$commentdata['comment_author_url']   = apply_filters('pre_comment_author_url', $commentdata['comment_author_url']);
	$commentdata['filtered'] = true;
	$commentdata['comment_approved'] = 1;
	$commentdata['comment_content'] = $comment_template;

	$comment_id = wp_insert_comment($commentdata);
	do_action('comment_post', $comment_id, $commentdata['comment_approved']);


	$yuri_query =	"UPDATE $table_name SET comment_ID='$comment_id' WHERE svejo_id = '$svejo_id' limit 1";
	mysql_query($yuri_query);

	$count++;
} //one URL comments


} // all urls

echo $count;

?>
