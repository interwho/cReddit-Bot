<?php

function curl_fetch($url) {
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_USERAGENT, "r/Loans cRedditBot Bot by u/interwhos");
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    $return = curl_exec($curl);
    curl_close($curl);
    return $return;
}

function time_ago($datetime, $granularity = 2) {
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

if(isset($_GET['username'])) {
    $username = $_GET['username'];
    $user_info = json_decode(curl_fetch("http://www.reddit.com/user/$username/about.json"), true);

    if(isset($user_info['error'])) {
        $error = $username . ' does not exist on reddit (' . $user_info['error'] . ')';
        unset($user_info);
    } else {
        $user_info = $user_info['data'];
        $bad_karma = json_decode(curl_fetch("http://www.reddit.com/r/badkarma/search.json?q=title%3A$username&restrict_sr=on&sort=relevance&t=all&limit=500"), true);
        if(count($bad_karma['data']['children']) > 0)
            $user_info['bad_karma'] = true;

        $loans_search = json_decode(curl_fetch("http://www.reddit.com/r/Loans/search.json?syntax=cloudsearch&q=author%3A%27$username%27&restrict_sr=on&sort=relevance&t=all&limit=500"), true);
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

        $report_search = json_decode(curl_fetch("http://www.reddit.com/r/Loans/search.json?q=title%3A$username+%5BPAID%5D+OR+%5BUNPAID%5D&restrict_sr=on&sort=relevance&t=all&limit=500"), true);
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
    }
}

include("user_rating.phtml");