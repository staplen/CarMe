<?php

$user_info = array();
if (fAuthorization::checkAuthLevel('user')) {
	$user_info['isAuthenticated'] = true;
	$user_info['accounts'] = fSession::get('user_accounts');
}
echo fJSON::encode($user_info);

?>