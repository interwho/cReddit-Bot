<?php

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