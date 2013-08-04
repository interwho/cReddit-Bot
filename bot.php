<?php

require_once "creddit.php";
require_once "reddit.php";

set_time_limit(0);

define('REDDIT_USERNAME', 'user');
define('REDDIT_PASSWORD', 'pass');

$creddit = new Creddit();
$reddit = new Reddit();

$reddit->login(REDDIT_USERNAME, REDDIT_PASSWORD);

$loans_posts = $reddit->get_new_posts('citricsquid');

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
[{$user_info['created_human']} - total karma: {$user_info['total_karma_human']}](/meta_)\n\n
---------------------------------------\n\n
[report link](http://www.reddit.com/message/compose?to=%2Fr%2FLoans&subject=cRedditBot%20Link%20Reported%20-%20redd.it/$post_id) or [send feedback](http://www.reddit.com/message/compose?to=interwhos&subject=cRedditBot%20Feedback!)\n\n
---------------------------------------\n\n
[Want to start lending? Read this first!](http://www.reddit.com/r/Loans/comments/19y46n/meta_everything_i_can_think_of_to_give_a_first/)\n\n
---------------------------------------\n\n
[Hi! I'm the cRedditBot stats robot. Click here for more information about me.](http://www.reddit.com/r/Loans/comments/1j54kp/meta_credditbot_information/)\n\n
---------------------------------------
MESSAGE;
    
    $reddit->post_comment($post_id, $message);
    
    sleep(600);
}
?>