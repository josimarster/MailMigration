<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);

$cp_src = "{mail.servidororigem.com:143/novalidate-cert}";
$cp_dst = "{mail.servidordestino.com}";
$account = "suaconta@seudominio.com";
$password = "Senha";

$src_mbox = imap_open($cp_src, $account, $password);
$dest_mbox = imap_open($cp_dst, $account, $password);

$list = imap_list ( $src_mbox , $cp_src , "*" );

pre("Migration account $account from $cp_src to $cp_dst");

foreach($list as $mbox){
	pre("WORKING IN ".$mbox);
	$mbox = imap_utf7_encode($mbox);
	$cbox = str_replace($cp_src, $cp_dst, $mbox);
	imap_createmailbox($dest_mbox, $cbox );
	$current_box = imap_open($cbox, $account, $password);
	$current_src = imap_open($mbox, $account, $password);
	
	$status = imap_status($current_src, $mbox, SA_ALL);
	$msgs = imap_sort($current_src, SORTDATE, 1, SE_UID);
	
	foreach ($msgs as $msguid) {
		$i = imap_msgno($current_src, $msguid);		
		$headers = imap_headerinfo($current_src, $i);
		
		if(!is_object($headers))
			continue;
				
		$Unseen = (trim($headers->Unseen) != "");
		$Flagged = (trim($headers->Flagged) != "");
		$Answered = (trim($headers->Answered) != "");
		$Deleted = (trim($headers->Deleted) != "");
		$Draft = (trim($headers->Draft) != "");
	
		$MailDate = $headers->MailDate;
	
		$options = "";
		if( !$Unseen ){
			$options .='\\Seen ';
		}
		if( $Flagged ){
			$options .='\\Flagged ';
		}
		if( $Answered ){
			$options .='\\Answered ';
		}
		if( $Deleted ){
			$options .='\\Deleted ';
		}
		if( $Draft ){
			$options .='\\Draft ';
		}
	
		$check = imap_check($current_src);
		pre( "Msg Count before append: ". $check->Nmsgs);
	
		$contents = imap_fetchheader($current_src, $i) . "\r\n" . imap_body($current_src, $i, FT_PEEK);
		$result = imap_append($current_box, $cbox, $contents, $options, date('d-M-Y H:i:s O', $headers->udate));
	
		if( $result ){
			imap_delete($current_src, $i);
			imap_expunge($current_src);
		}
		var_dump($result);
	
		$check = imap_check($current_src);
		pre("Msg Count after append: ". $check->Nmsgs);
	
	}
	
}



function pre($str, $bol=false){
	echo '<pre>';
	print_r($str);
	echo '</pre>';
	if($bol)
		exit;
		return;
}

?>