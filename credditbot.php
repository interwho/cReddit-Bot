<?php

// creddit.php

class Creddit
{

    public function statistics($username)
    {
        $user_info = json_decode($this->curl("http://www.reddit.com/user/$username/about.json"), true);

        if(isset($user_info['error']))
            return array('error' => $username . ' does not exist on reddit (' . $user_info['error'] . ')');

        $user_info = $user_info['data'];
        $user_info['created_human'] = $this->time_ago($user_info['created']);
        $user_info['total_karma'] = $user_info['link_karma'] + $user_info['comment_karma'];
        $user_info['total_karma_human'] = number_format($user_info['link_karma'] + $user_info['comment_karma']);

        $bad_karma = json_decode($this->curl("http://www.reddit.com/r/badkarma/search.json?q=title%3A$username&restrict_sr=on&sort=relevance&t=all&limit=500"), true);
        if(count($bad_karma['data']['children']) > 0)
            $user_info['bad_karma'] = true;

        $loans_search = json_decode($this->curl("http://www.reddit.com/r/Loans/search.json?syntax=cloudsearch&q=author%3A%27$username%27&restrict_sr=on&sort=relevance&t=all&limit=500"), true);
        $loan_tags = array('request' => 0, 'req' => 0, 'unpaid' => 0, 'paid' => 0);

        foreach($loans_search['data']['children'] as $submission) {
            $tag = str_replace(array('[', ']'), '', explode(' ', $submission['data']['title']));
            $tag = strtolower($tag[0]);
            if(isset($loan_tags[$tag]))
                $loan_tags[$tag] += 1;
        }

        $loan_info = array(
            'granted' => $loan_tags['paid'] + $loan_tags['unpaid'],
            'granted_paid' => $loan_tags['paid'],
            'granted_unpaid' => $loan_tags['unpaid'],
            'requested' => $loan_tags['req'] + $loan_tags['request'],
        );

        $report_search = json_decode($this->curl("http://www.reddit.com/r/Loans/search.json?q=title%3A$username+%5BPAID%5D+OR+%5BUNPAID%5D&restrict_sr=on&sort=relevance&t=all&limit=500"), true);
        $report_tags = array('unpaid' => 0, 'paid' => 0);

        foreach($report_search['data']['children'] as $submission) {
            $tag = explode(' ', str_replace(array('[', ']'), '', $submission['data']['title']));
            $tag = strtolower($tag[0]);
            if(isset($report_tags[$tag]))
                $report_tags[$tag] += 1;
        }

        $loan_info = array_merge($loan_info, array(
            'requested_paid' => $report_tags['paid'],
            'requested_unpaid' => $report_tags['unpaid'],
        ));

        return array($loan_info, $user_info);
    }

    private function curl($url)
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_USERAGENT, "r/Loans cRedditBot Bot by u/interwhos");
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        $return = curl_exec($curl);
        curl_close($curl);
        return $return;
    }

    private function time_ago($datetime, $granularity = 2)
    {
        $difference = time() - $datetime;
        $periods = array(
            'year' => 31536000,
            'month' => 2628000,
            'week' => 604800,
            'day' => 86400,
            'hour' => 3600,
            'minute' => 60,
            'second' => 1
        );

        $retval = '';

        foreach ($periods as $key => $value) {
            if ($difference >= $value) {
                $time = floor($difference/$value);
                $difference %= $value;
                $retval .= ($retval ? ' ' : '').$time.' ';
                $retval .= (($time > 1) ? $key.'s' : $key);
                $granularity--;
            }

            if ($granularity == '0')
                break;
        }

        return $retval;
    }
}

// reddit.php

class Reddit
{
    function login($username, $password)
    {
	$login_url = "https://ssl.reddit.com/api/login/cRedditBot";
	$login_data = array (
            "user" => $username,
            "passwd" => $password,
            "api_type" => "json",
	);

	$ch = curl_init ($login_url);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $login_data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, "r/Loans cRedditBot Bot by u/interwhos");
	$login_response = curl_exec($ch);

        $account_data = json_decode($login_response, true);
        $account_data = $account_data['json']['data'];

        if(!$account_data)
            exit('Account login information not valid');

        $this->cookie = 'reddit_session=' . urlencode($account_data['cookie']);
        $this->modhash = $account_data['modhash'];
    }

    function get_post($id)
    {
        $post_url = 'http://www.reddit.com/r/' . $this->subreddit . '/comments/' . $id . '.json';
        $post_data = $this->curl($post_url);
        return json_decode($post_data, true);
    }

    function post_comment($id, $body)
    {
	$comment_url = "https://ssl.reddit.com/api/comment";
	$comment_data = array(
            "thing_id" => 't3_' . $id,
            "text" => $body,
            "uh" => $this->modhash,
	);

	$ch = curl_init($comment_url);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $comment_data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, "r/Loans cRedditBot Bot by u/interwhos");
	curl_setopt($ch, CURLOPT_COOKIE, $this->cookie);
	$return_data = curl_exec($ch);
        return $return_data;
    }

    function get_new_posts($subreddit)
    {
        $this->subreddit = $subreddit;
        $url = "http://www.reddit.com/r/{$subreddit}/new.json?sort=new";
        $posts = $this->curl($url);
        $post_list = json_decode($posts, true);
        return $post_list['data']['children'];
    }

    private function curl($url)
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_USERAGENT, "r/Loans cRedditBot Bot by u/interwhos");
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        $return = curl_exec($curl);
        curl_close($curl);
        return $return;
    }

}

// bot.php

set_time_limit(0);

define('REDDIT_USERNAME', 'user');
define('REDDIT_PASSWORD', 'pass');

$creddit = new Creddit();
$reddit = new Reddit();

$reddit->login(REDDIT_USERNAME, REDDIT_PASSWORD);

$loans_posts = $reddit->get_new_posts('loans');

foreach($loans_posts as $post) {
    $post_id = substr($post['data']['name'], 3);
    $post_data = $reddit->get_post($post_id);

    foreach(array_slice($post, 1) as $comment)
        if($comment['author'] == REDDIT_USERNAME)
            exit('bot account posted already or post by the bot!');

    $username = $post_data[0]['data']['children'][0]['data']['author'];
    list($loan_info, $user_info) = $creddit->statistics($username);

$message = <<<MESSAGE
Stats for **[$username](http://www.reddit.com/r/Loans/search?q=author%3A%27$username%27&restrict_sr=on)** on r/Loans\n\n
---------------------------------------\n\n
**Requester Stats**\n\n
* [{$loan_info['requested']} Loan(s) Requested](/req_) ([view all posts in r/loans by this user](http://www.reddit.com/r/Loans/search?syntax=cloudsearch&q=author%3A%27$username%27&restrict_sr=on&sort=new))
* [{$loan_info['requested_paid']} Loan(s) Paid Back By This Redditor](/paid_) ([view](http://www.reddit.com/r/Loans/search?q=title%3A$username+%5BPAID%5D&restrict_sr=on&sort=new))
* [{$loan_info['requested_unpaid']} Loan(s) NOT Paid Back By This Redditor](/unpaid_) ([view](http://www.reddit.com/r/Loans/search?q=title%3A$username+%5BUNPAID%5D&restrict_sr=on&sort=new))\n\n
**Lender Stats**\n\n
* [{$loan_info['granted']} Loan(s) Granted To Others](/offer_)
* [{$loan_info['granted_paid']} Loan(s) Paid Back To This Redditor](/paid_)
* [{$loan_info['granted_unpaid']} Loan(s) NOT Paid Back To This Redditor](/unpaid_)\n\n
---------------------------------------\n\n
[member for: {$user_info['created_human']} - total karma: {$user_info['total_karma_human']}](/meta_)\n\n
---------------------------------------\n\n
[report link](http://www.reddit.com/message/compose?to=%2Fr%2FLoans&subject=cRedditBot%20Link%20Reported%20-%20redd.it/$post_id) or [send feedback](http://www.reddit.com/message/compose?to=interwhos&subject=cRedditBot%20Feedback!)\n\n
---------------------------------------\n\n
[Want to start lending? Read this first!](http://www.reddit.com/r/Loans/comments/19y46n/meta_everything_i_can_think_of_to_give_a_first/)\n\n
---------------------------------------\n\n
[Hi! I'm the cRedditBot stats robot. Click here for more information about me.](http://www.reddit.com/r/Loans/comments/1j54kp/meta_credditbot_information/)\n\n
---------------------------------------
MESSAGE;

    $reddit->post_comment($post_id, $message);

    sleep(600); // TODO: Look at the captcha problem; this COULD cause a comment overlap!
}
?>