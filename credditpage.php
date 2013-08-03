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
function rateUser($username) {
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
		$bad = "<br><a href='http://www.reddit.com/r/badkarma/search?q=title%3A$username&restrict_sr=on&sort=relevance&t=all'>WARNING: THIS USER MAY HAVE BEEN REPORTED IN R/BADKARMA!</a><br>";
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
	$idl = $id;
	//Temp - This breaks things with the new separated stats though...
	//$paid = $paid - $gpaid;
	//$unpaid = $unpaid - $gunpaid;
	//End temp
	$message = "Stats for <a href='http://www.reddit.com/r/Loans/search?q=author%3A%27$username%27&restrict_sr=on'>$username</a> on r/Loans<br>
---------------------------------------<br>
**Requester Stats**<br>
$req Loan(s) Requested (<a href='http://www.reddit.com/r/Loans/search?syntax=cloudsearch&q=author%3A%27$username%27&restrict_sr=on&sort=new'>view all posts in r/loans by this user</a>)
<br>$paid Loan(s) Paid Back By This Redditor (<a href='http://www.reddit.com/r/Loans/search?q=title%3A$username+%5BPAID%5D&restrict_sr=on&sort=new'>view</a>)
<br>$unpaid Loan(s) NOT Paid Back By This Redditor (<a href='http://www.reddit.com/r/Loans/search?q=title%3A$username+%5BUNPAID%5D&restrict_sr=on&sort=new'>view</a>)<br>
**Lender Stats**<br>
$granted Loan(s) Granted To Others
<br>$gpaid Loan(s) Paid Back To This Redditor
<br>$gunpaid Loan(s) NOT Paid Back To This Redditor<br>$bad
---------------------------------------<br>
<a href='http://reddit.com/u/$username'>$acctage - total karma: $karma</a><br>
---------------------------------------<br>
<a href='http://www.reddit.com/r/Loans/comments/19y46n/meta_everything_i_can_think_of_to_give_a_first/'>Want to start lending? Read this first!</a><br>
---------------------------------------<br>
<a href='http://www.reddit.com/r/Loans/comments/1j54kp/meta_credditbot_information'>Hi! I'm the cRedditBot stats robot. Click here for more information about me.</a><br>
---------------------------------------";
	return $message;
}

#########################################################################################################

if(isset($_GET['username'])) { echo rateUser($_GET['username']); echo '<br><hr><br>'; }
?>
Usage: Rates A User On Reddit's r/Loans<br>
<form method="get">Enter The Username You Would Like Rated: <input type="text" name="username"><br><button type="submit">Get Rating</button></form>