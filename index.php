<?php

require_once "creddit.php";

if(isset($_GET['username'])) {
    $creddit = new Creddit();
    list($loan_info, $user_info) = $creddit->statistics($_GET['username']);
}

require_once "rating.phtml";