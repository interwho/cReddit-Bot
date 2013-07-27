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

//Date Converter
function time_ago($date,$granularity=2) {
    $difference = time() - $date;
    $periods = array(
        'year' => 31536000,
        'month' => 2628000,
        'week' => 604800,
        'day' => 86400,
        'hour' => 3600,
        'minute' => 60,
        'second' => 1);

    foreach ($periods as $key => $value) {
        if ($difference >= $value) {
            $time = floor($difference/$value);
            $difference %= $value;
            $retval .= ($retval ? ' ' : '').$time.' ';
            $retval .= (($time > 1) ? $key.'s' : $key);
            $granularity--;
        }
        if ($granularity == '0') { break; }
    }
    if(strlen($retval) == 0) {
    	$retval = 'an instant';
    }
    return 'joined: '.$retval.' ago';
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
	$acctage = time_ago($acctage);
	//Search Username And Get Variables
	//BADKARMA CHECK
	$response = curlGet("http://www.reddit.com/r/badkarma/search.xml?q=title%3A$username&restrict_sr=on&sort=relevance&t=all");
	$response = strtoupper($response);
	$bad = substr_count($response, '<TITLE>[BAD]');
	if($bad > 0) {
		$bad = "\n[WARNING: THIS USER MAY HAVE BEEN REPORTED IN R/BADKARMA!](/unpaid_) ([link](http://www.reddit.com/r/badkarma/search?q=title%3A$username&restrict_sr=on&sort=relevance&t=all))\n\n";
	} else {
		$bad = "";
	}
	//Main Stat Checks
	$response = curlGet("http://www.reddit.com/r/Loans/search.xml?syntax=cloudsearch&q=author%3A%27$username%27&restrict_sr=on&sort=new");
	$response = strtoupper($response);
	$req = substr_count($response, '<TITLE>[REQ]');
	$req = $req + substr_count($response, '<TITLE>[REQUEST]');
	$granted = substr_count($response, '<TITLE>[PAID]');
	$gpaid = substr_count($response, '<TITLE>[PAID]');
	$granted = $granted + substr_count($response, '<TITLE>[UNPAID]');
	$gunpaid = substr_count($response, '<TITLE>[UNPAID]');
	//Search For Paid & Unpaid Loans
	$response = strtoupper(curlGet("http://www.reddit.com/r/Loans/search.xml?q=title%3A$username+%5BPAID%5D&restrict_sr=on&sort=new"));
	$paid = substr_count($response, '<TITLE>[PAID]');
	$response = strtoupper(curlGet("http://www.reddit.com/r/Loans/search.xml?q=title%3A$username+%5BUNPAID%5D&restrict_sr=on&sort=new"));
	$unpaid = substr_count($response, '<TITLE>[UNPAID]');
	//Add The Comment
	$urltopost = "https://ssl.reddit.com/api/login/cRedditBot";
	$datatopost = array (
		"user" => "cRedditBot",
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
	$idl = $id;
	$id = 't3_'.$id;
	//Temp - This breaks things with the new separated stats though...
	//$paid = $paid - $gpaid;
	//$unpaid = $unpaid - $gunpaid;
	//End temp
	$message = "Stats for **[$username](http://www.reddit.com/r/Loans/search?q=author%3A%27$username%27&restrict_sr=on)** on r/Loans\n\n
---------------------------------------\n\n
**Requester Stats**\n\n
* [$req Loan(s) Requested](/req_) ([view all posts in r/loans by this user](http://www.reddit.com/r/Loans/search?syntax=cloudsearch&q=author%3A%27$username%27&restrict_sr=on&sort=new))
* [$paid Loan(s) Paid Back By This Redditor](/paid_) ([view](http://www.reddit.com/r/Loans/search?q=title%3A$username+%5BPAID%5D&restrict_sr=on&sort=new))
* [$unpaid Loan(s) NOT Paid Back By This Redditor](/unpaid_) ([view](http://www.reddit.com/r/Loans/search?q=title%3A$username+%5BUNPAID%5D&restrict_sr=on&sort=new))\n\n
**Lender Stats**\n\n
* [$granted Loan(s) Granted To Others](/offer_)
* [$gpaid Loan(s) Paid Back To This Redditor](/paid_)
* [$gunpaid Loan(s) NOT Paid Back To This Redditor](/unpaid_)\n\n$bad
---------------------------------------\n\n
[$acctage - total karma: $karma](/meta_)\n\n
---------------------------------------\n\n
[report link](http://www.reddit.com/message/compose?to=%2Fr%2FLoans&subject=cRedditBot%20Link%20Reported%20-%20".urlencode('http://redd.it/'.$idl).") or [send feedback](http://www.reddit.com/message/compose?to=interwhos&subject=cRedditBot%20Feedback!)\n\n
---------------------------------------\n\n
[Want to start lending? Read this first!](http://www.reddit.com/r/Loans/comments/19y46n/meta_everything_i_can_think_of_to_give_a_first/)\n\n
---------------------------------------\n\n
[Hi! I'm the cRedditBot stats robot. I'm here to help protect the lenders that make this place great.](/meta_)\n\n
---------------------------------------";
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
$loansbase = preg_replace('#<TITLE>Loans(.*?)</image>#', '//', $loansbase);
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