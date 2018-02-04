<?php

  class WebMailer extends PHPMailer {

  	public function __construct()
  	{
  	  $MTA = "SMTP";

  	  if (getenv("APPLICATION_SUB_ENVIRONMENT") == "racr") {
  	  	$MTA = "Mail";
  	  }

  	  if ($MTA == "SMTP") {
  	    $this->isSMTP(); // Set mailer to use SMTP
  	    $this->Host = \SiteConfig::getInstance()->get ( 'email_server_host' ); // Specify main SMTP server host
  	    $this->Port = \SiteConfig::getInstance()->get ( 'email_server_port' ); // Specify main SMTP server port
  	  }

  	  if ($MTA == "Mail") {
  	    $this->isMail(); // Set mailer to use SMTP
  	  }

  	  $this->setFrom(\SiteConfig::getInstance()->get ('email_from_address'), \SiteConfig::getInstance()->get ('email_from_name'));
  	  $this->CharSet = 'UTF-8';

  	  // Override messages with current language.
  	  $this->language = array(
  	  		'authenticate' => _('SMTP Error: Could not authenticate.'),
  	  		'connect_host' => _('SMTP Error: Could not connect to SMTP host.'),
  	  		'data_not_accepted' => _('SMTP Error: data not accepted.'),
  	  		'empty_message' => _('Message body empty'),
  	  		'encoding' => _('Unknown encoding: '),
  	  		'execute' => _('Could not execute: '),
  	  		'file_access' => _('Could not access file: '),
  	  		'file_open' => _('File Error: Could not open file: '),
  	  		'from_failed' => _('The following From address failed: '),
  	  		'instantiate' => _('Could not instantiate mail function.'),
  	  		'invalid_address' => _('Invalid address'),
  	  		'mailer_not_supported' => _('Mailer is not supported.'),
  	  		'provide_address' => _('You must provide at least one recipient email address.'),
  	  		'recipients_failed' => _('SMTP Error: The following recipients failed: '),
  	  		'signing' => _('Signing Error: '),
  	  		'smtp_connect_failed' => _('SMTP connect() failed.'),
  	  		'smtp_error' => _('SMTP server error: '),
  	  		'variable_set' => _('Cannot set or reset variable: ')
  	  );
  	}

  	private function strip_html($message)
  	{
  		$pattern = array();
  		$replace = array();

  		$pattern[] = "/<li>(.*)<\/li>/i";
  		$replace[] = " * $1\n";
  		$pattern[] = "/<p>/i";
  		$replace[] = "\n \n";
  		$pattern[] = "/<br\/>/i";
  		$replace[] = "\n";
  		$pattern[] = "/<br>/i";
  		$replace[] = "\n";

  		$result = preg_replace($pattern, $replace, $message);

  		$result = strip_tags($result);

  		$pattern = array();
  		$replace = array();

  		$pattern[] = "/\n\n/i";
  		$replace[] = "\n";
  		$pattern[] = "/  /";
  		$replace[] = " ";

  		$result = preg_replace($pattern, $replace, $result);

  		$result = wordwrap($result, 75, "\n");

  		return $result;
  	}

  	public function ConstructBrandedMessage($message, $text_message = null)
  	{
  		if ($text_message == null) {
  		  $text_message = 	$this->strip_html($message);
  		}

  		$message_vars = array (
  		  "{subject}"        => $this->Subject,
	  	  "{html_message}"   => $message,
  		  "{text_message}"   => $text_message,
  		  "{baseurl}"        => \SiteConfig::getInstance()->get ( 'full_url' ),
  		  "{servicecontact}" => \SiteConfig::getInstance()->get ( 'email_service_address' ),
        "{year}" => date("Y")
  		);

  		$plain_text = \Libs\Locale::processFile("mail_message_template_text", $message_vars );
  		$html_text = \Libs\Locale::processFile("mail_message_template_html", $message_vars );

        // Don't enable this again... Adds strange '!\n' to message
  		// $html_text = preg_replace('!\s+!', ' ', $html_text);
  		// $html_text = preg_replace('!> <!', '><', $html_text);

  		$html_text = str_replace("  ", " ", $html_text);
  		$html_text = str_replace("> <", "><", $html_text);

  		$this->addEmbeddedImage(\SiteConfig::getInstance()->get ( 'email_service_logo' ), 'logo');
  		$this->addEmbeddedImage(\SiteConfig::getInstance()->get ( 'email_message_top' ), 'top');
  		$this->addEmbeddedImage(\SiteConfig::getInstance()->get ( 'email_message_bottom' ), 'bottom');
      $this->addEmbeddedImage(\SiteConfig::getInstance()->get ( 'email_message_spacer' ), 'spacer');
      
  		$this->Body = $html_text;
  		$this->AltBody = $plain_text;
  	}

  	public function SendToAdmin($more_emails = null)
  	{
  	  foreach(\SiteConfig::getInstance()->get('admin_list') as $email)
  		$this->addAddress ( $email );

  	  if ($more_emails) {
  	    foreach($more_emails as $email)
  	      $this->addAddress ( $email );
  	  }

  	  return $this->send ();
  	}
  }

  class ThrottleWebMailer extends WebMailer
  {
  	public function send() {

  		$filename = "/tmp/throttle-mail-message-" . md5($this->Subject);

  		if (file_exists($filename)) {
  	      $filemtime = filemtime ($filename);
  		  if (time() - $filemtime < \SiteConfig::getInstance()->get ( 'repeat-error-message' ) ) {
  			return false;
  		  }
  		}

  		touch($filename);

  		return parent::send();
  	}
  }
