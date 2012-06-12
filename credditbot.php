<?php
//cReddit Rating Bot - by interwhos
error_reporting(0);
set_time_limit(0);

#########################################################################################################

//Set Initial Variables
$granted = 0;
$paid = 0;
$unpaid = 0;
$req = 0;

#########################################################################################################

//Curl Grabber For Search
function curlGet($url)	{
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_USERAGENT, "r/Loans cRedditBot Bot by u/interwhos");
	curl_setopt($curl, CURLOPT_TIMEOUT, 30);
	$return = curl_exec($curl);
	curl_close($curl);
	return $return;
}

//User Rater
function rateUser($post,$id) {
	//Get Username By Parsing Post Description
	preg_match('#http://www.reddit.com/user/(.*?)">#', $post->description, $username);
	$username = $username[0];
	$username = preg_replace('#http://www.reddit.com/user/#', '', $username);
	$username = preg_replace('#">#', '', $username);	
	//Get Username Info
	$response = curlGet("http://www.reddit.com/user/$username/about.json");
	$response = json_decode($response);
	$response = $response->{'data'};
	$acctage = $response->{'created'};
	$karma = $response->{'link_karma'};
	$karma = $karma + $response->{'comment_karma'};
	$acctage = strftime("%B %d %Y %r",$acctage);
	//Search Username And Get Variables
	$response = curlGet("http://www.reddit.com/r/Loans/search.xml?syntax=cloudsearch&q=author%3A%27$username%27&restrict_sr=on&sort=new");
	$req = substr_count($response, '<title>[REQ]');	
	$granted = substr_count($response, '<title>[PAID]');
	$granted = $granted + substr_count($response, '<title>[UNPAID]');
	//Search For Paid & Unpaid Loans
	$response = curlGet("http://www.reddit.com/r/Loans/search.xml?syntax=cloudsearch&q=$username+%5BPAID%5D&restrict_sr=on&sort=new");
	$paid = substr_count($response, '<title>[PAID]');
	$response = curlGet("http://www.reddit.com/r/Loans/search.xml?syntax=cloudsearch&q=$username+%5BUNPAID%5D&restrict_sr=on&sort=new");
	$unpaid = substr_count($response, '<title>[UNPAID]');
	//Add The Comment
	$urltopost = "https://ssl.reddit.com/api/login/cRedditBot";
	$datatopost = array (
		"user" => "USERNAME",
		"passwd" => "PASSWORD",
		"api_type" => "json",
	);
	$ch = curl_init ($urltopost);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $datatopost);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, "r/Loans cRedditBot Bot by u/interwhos");
	$loginvars = curl_exec($ch);
	$loginvars = json_decode($loginvars);
	$loginvars = $loginvars->{'json'};
	$loginvars = $loginvars->{'data'};
	$hash = $loginvars->{'modhash'};
	$cookie = $loginvars->{'cookie'};
	$cookie = urlencode($cookie);
	$id = 't3_'.$id;
	$message = "**The following is your cReddit rating:**\n\n
[Account Created: **$acctage**](/meta_)\n\n
[Total Karma: **$karma**](/meta_)\n\n
[Total # of Loans Requested: **$req**](/req_)\n\n
[Total # of Loans Granted To Other Redditors by This Redditor: **$granted**](/offer_)\n\n
[Total # of Loans Paid Back by/to This Redditor: **$paid**](/paid_)\n\n
[Total # of Loans NOT Paid Back by/to This Redditor: **$unpaid**](/unpaid_)\n\n
\n\n**[I am an automated bot. If you have any questions or feedback, please contact my owner, interwhos.](http://www.reddit.com/u/interwhos)**";
	$urltopost = "http://www.reddit.com/api/comment";
	$datatopost = array(
		"thing_id" => $id,
		"text" => $message,
		"uh" => $hash
	);
	$cookie = "reddit_session=".$cookie;
	$ch = curl_init($urltopost);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $datatopost);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, "r/Loans cRedditBot Bot by u/interwhos");
	curl_setopt($ch, CURLOPT_COOKIE, $cookie);
	$returndata = curl_exec($ch);
	//10 Minute Timeout (So We Don't Get A Captcha)
	sleep(600);
}

#########################################################################################################

//Start Script Logic
$loansbase = curlGet("http://www.reddit.com/r/Loans/new.xml?sort=new");
$loansbase = preg_replace('#<title>Loans(.*?)</image>#', '//', $loansbase);
$loansbase = simplexml_load_string($loansbase);
$loansbase = $loansbase->channel;

foreach ($loansbase->item as $post) {
	$url = $post->guid;
	preg_match('#/r/Loans/comments/(.*?)/#', $url, $id);
	$id = $id[0];
	$id = preg_replace('#/r/Loans/comments/#', '', $id);
	$id = preg_replace('#/#', '', $id);
	$ratingcheck = curlGet($url);
	if(preg_match("/cRedditBot/i", $ratingcheck)) {
		exit();
	} else {
		rateUser($post,$id);
	}
}
?>