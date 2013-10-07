<?php 
extract($_REQUEST);
if(isset($_REQUEST['email']) && $_REQUEST['email']!=''){
	$from = $_REQUEST['email'];
	$to = "";//your email id
	$subject = "Contact Inquiry details";
	
	$email_message = "Contact Form Details\n";
	$email_message.= "----------------------\n";
	$email_message.= "Name: $name\n";
	$email_message.= "Email: $email\n";
	$email_message.= "url: $url\n";
	$email_message.= "Message: $message\n";
	$header = "From: ".$email;
	mail($to,$subject,$email_message,$header);
	echo "true";
}
else{
	echo "false";
}

?>