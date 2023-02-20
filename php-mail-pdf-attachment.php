<?php
/**
 * Send an email using default mail() function, and attach a pdf
 * Adjust accordingly to remove the die() statements, those are used mainly as placeholders for better code ;)
 * Run this from a controller taking in $_POST data
 */

function sendEmailFromPostData($postData)
{
    // ===========================================================================
    // NOTE: CHANGE THESE VALUES
    $from_email = "YOUREMAILHERE";
    
    //pdf inclusion
    $file_name = '/path/to/pdf/here/filename.pdf';//make sure this matches

    $email_from_name = "YOUR NAME HERE";

    // END CHANGE
    // ===========================================================================

    //get filename from path
    $name = basename($file_name);

    // Sanitize input data
    $email = filter_var($postData['email'], FILTER_SANITIZE_EMAIL);
    $first_name = ucwords(filter_var($postData['first_name'], FILTER_SANITIZE_STRING));

    //this should be empty in form -- it is a hidden field that only a bot would see and fill
    $x = filter_var($postData['x'], FILTER_SANITIZE_STRING);

    //check that require info exists, die if not
    if (empty($postData['email'])||empty($postData['first_name'])) {
        die("Missing required information, cannot continue");
    }

    // check that x is indeed empty -- the only reason it would be filled is by a bot filling out the form
    if (!empty($postData['x'])) {
        die('success.');//false positive - makes bot think it worked so they don't keep retrying
    }

    // Compose email message
    $subject = "[ENCLOSED] Here's what you requested from {$email_from_name}";
    
    $messageContent = "<p>Hey there {$first_name}, here is the info you requested (please see attached)</p>";
    $messageContent .= "<p>Thanks for your interest!</p>";
    $messageContent .= "<p>Best,<br />{$email_from_name}</p>";

    //nohtml
    $contentTextOnly = "Hey there {$first_name}, here is the info you requested (please see attached)\r\n\r\n";
    $contentTextOnly .= "Thanks for your interest!\r\n\r\n";
    $contentTextOnly .= "Best,\r\n{$email_from_name}";

    // Set email headers

    $uid = md5(uniqid(time()));//unique each attempt
    $boundary = "xx{$uid}xx";

    //start headers
    $headers = "MIME-Version: 1.0\r\n";//start here
    
    $headers .= "From: {$email_from_name} <{$from_email}>\r\n"; // Sender Email
    $headers .= "Reply-To: ".$from_email."\r\n"; // Email address to reach back
    $headers .= "X-Mailer: PHP/" . phpversion()."\r\n";
    $headers .= "Content-Type: multipart/mixed;";
    $headers .= "boundary = {$boundary}\r\n";
    
    //message body, text only
    $body = "--{$boundary}\r\n";
    $body .= "Content-Type: text/plain; charset=ISO-8859-1\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $body .= chunk_split(base64_encode($contentTextOnly));

    //message body, html
    $body = "--{$boundary}\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $body .= chunk_split(base64_encode($messageContent));
    
    if (file_exists($file_name)) {
        $fp =    @fopen($file_name, "rb");
        $data =  @fread($fp, filesize($file_name));
        @fclose($fp);
        $encoded_content = chunk_split(base64_encode($data));

        //attachment
        $body .= "--$boundary\r\n";
        $body .="Content-Type: application/pdf; name={$name}\r\n";
        $body .="Content-Disposition: attachment; filename=\"{$name}\"\r\n";
        $body .="Content-Transfer-Encoding: base64\r\n";
        $body .="X-Attachment-Id: ".rand(1000, 99999)."\r\n\r\n";
        $body .= $encoded_content; // Attaching the encoded file with email
    }

    // Send email
    try {
        mail($email, $subject, $body, $headers);
        return true;
    } catch (Exception $e) {
        echo $e->getMessage();
        return false;
    }
}
// end functions

// ===========================================================================
// THIS IS WHERE PROCESSING HAPPENS ~>
//

// process it here
if (empty($_POST)) {
    die('Error, missing information');
}

// run it since we have post data
$sent = sendEmailFromPostData($_POST);

if (true == $sent) :
    // this is a basic page/message for user, it would be better to put this into a template instead of inline html
    ?>
    <html><body><h2>Your request was received. Please check your email</h2></body></html><?php
else :
    die('Error');
endif;

/* end of file */