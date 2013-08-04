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
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>cReddit - <?php echo isset($username) ? "{$username}'s r/loans history" : "r/loans user history"; ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="">
        <meta name="author" content="">
        <style type="text/css">
            body{font-family: Arial, Helvetica, sans-serif; margin: 0px; padding: 0px;}
            .container{margin: auto; width: 600px;}
            #header{background-color: #1E90FF; color: #FFF; padding: 10px 0;}
            #header h1{font-size: 22px; font-weight: normal; margin: 10px 0px;}
            #intro p{line-height: 20px;}
            .separate-me{border-top: 2px solid #EEE; margin: 20px auto;}
            /* via http://www.fiveforblogger.com/2012/02/5-styles-fluid-search-bar.html */
            #search-box{position:relative;width:100%;margin:0}
            #search-text{font-size:14px;color:#ddd;border-width:0;background:transparent}
            #search-box input[type=text]{width:90%;padding:11px 0 12px 1em;color:#333;outline:0}
            #search-form{height:40px;border:1px solid #eee;-webkit-border-radius:5px;-moz-border-radius:5px;border-radius:5px;background-color:#fff;-webkit-box-shadow:inset 2px 2px 4px #ccc;-moz-box-shadow:inset 2px 2px 4px #ccc;box-shadow:inset 2px 2px 4px #ccc;overflow:hidden}
            #search-button{position:absolute;top:5px;right:4px;height:32px;width:80px;font-size:14px;color:#fff;line-height:32px;text-align:center;text-shadow:1px 1px 0 #888;border-width:0;-webkit-border-radius:3px;-moz-border-radius:3px;border-radius:3px;background-color:#bbb;background:-moz-linear-gradient(top,#aaa 0,#b9b9b9);background:-webkit-gradient(linear,left top,left bottom,from(#aaa),to(#b9b9b9));-webkit-box-shadow:inset 0 -1px 1px #888,0 2px 2px #ccc;-moz-box-shadow:inset 0 -1px 1px #888,0 2px 2px #ccc;box-shadow:inset 0 -1px 1px #888,0 2px 2px #ccc;cursor:pointer}
            #results a{color: #1E90FF; text-decoration: none;}
            #results li{margin-bottom: 4px;}
            .gold{color:#9a7d2e; font-weight:bold;}
            .error{color:#f56991; font-weight: bold;}
        </style>
    </head>
    <body>
        <div id="header">
            <div class="container">
                <h1>reddit/<strong>loans</strong> user stats by interwhos</h1>
            </div>
        </div>
        <div id="intro" class="container">
            <p>
                Search for a reddit.com user's history on r/loans.
            </p>
        </div>
        <div id="search" class="container">
            <div id="search-box">
                <form id="search-form" method="GET">
                    <input id="search-text" name="username" placeholder="reddit.com username" type="text"/>
                    <button id="search-button" type="submit">
                        <span>Search</span>
                    </button>
                </form>
            </div>
            <?php if(isset($error)) { ?>
                <p><span class="error">Error:</span> <?php echo $error; ?></p>
            <?php } ?>
        </div>
        <?php if(isset($user_info)) { ?>
        <div id="results" class="container separate-me">
            <h2><a href="http://reddit.com/user/<?php echo $user_info['name']; ?>"><?php echo $user_info['name']; ?></a></h2>
            <p>
                Reddit user for <?php echo time_ago($user_info['created']); ?> with 
                <?php echo number_format($user_info['link_karma'] + $user_info['comment_karma']); ?> 
                total karma and <?php if($user_info['is_gold']) { ?>
                <span class="gold">has reddit gold</span><?php } else { ?>
                does not have reddit gold<?php } ?>.
            </p>
            <h3>Loans Requested</h3>
            <?php if($loan_info['requested'] > 0) { ?>
            <ul>
                <li>
                    <a href="http://www.reddit.com/r/Loans/search?q=author:<?php echo $user_info['name']; ?>+title:req+OR+title:request&restrict_sr=on&sort=new&t=all">
                        <?php echo $loan_info['requested']; ?>
                    </a>
                    loans requested
                </li>
                <li>
                    <a href="http://www.reddit.com/r/Loans/search?q=title:<?php echo $user_info['name']; ?>+PAID&restrict_sr=on&sort=new&t=all">
                        <?php echo $loan_info['requested_paid']; ?>
                    </a>
                    loans paid back</li>
                <li>
                    <a href="http://www.reddit.com/r/Loans/search?q=title:<?php echo $user_info['name']; ?>+UNPAID&restrict_sr=on&sort=new&t=all">
                        <?php echo $loan_info['requested_unpaid']; ?>
                    </a>
                    loans <strong>not</strong> paid back :(</li>
                </li>
            </ul>
            <?php } else { ?>
            <p>No loans have been requested by <?php echo $user_info['name']; ?>.</p>
            <?php } ?>
            <h3>Loans Granted</h3>
            <?php if($loan_info['granted'] > 0) { ?>
            <ul>
                <li>
                    <a href="http://www.reddit.com/r/Loans/search?q=author:<?php echo $user_info['name']; ?>+title:PAID+OR+UNPAID&restrict_sr=on&sort=new&t=all">
                        <?php echo $loan_info['granted']; ?>
                    </a>
                    loans granted to others</li>
                </li>
                <li>
                    <a href="http://www.reddit.com/r/Loans/search?q=author:<?php echo $user_info['name']; ?>+title:PAID&restrict_sr=on&sort=new&t=all">
                        <?php echo $loan_info['granted_paid']; ?>
                    </a>
                    loans repaid by others</li>
                </li>
                <li>
                    <a href="http://www.reddit.com/r/Loans/search?q=author:<?php echo $user_info['name']; ?>+title:UNPAID&restrict_sr=on&sort=new&t=all">
                        <?php echo $loan_info['granted_unpaid']; ?>
                    </a>
                    loans <strong>not</strong> repaid by others :(</li>
                </li>
            </ul>
            <?php } else { ?>
            <p>No loans granted to others</p>
            <?php } ?>
        </div>
        <?php } ?>
    </body>
</html>