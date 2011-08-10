<?php
/**
 * Implements a verification email on user registration
 * @module verifyemailrole
 */
class VerifyEmailRole extends DataObjectDecorator {
  function extraStatics() {
		return array(
			"db" => array(
				"Verified" => "Boolean",
				"VerificationString" => "Varchar(32)",
				"VerificationEmailSent" => "Boolean",
			),
			"defaults" => array(
				"Verified" => false,
			)
		);
	}

  /**
   * Modify the field set to be displayed in the CMS detail pop-up
   */
  function updateCMSFields(FieldSet $currentFields) {
    $currentFields->insertAfter(new CheckboxField('Verified', 'Email Verified'), "Email");
  }

	/**
	 * Additional columns in Member Table displayed in the CMS so that you can easily see whether members email address has been verified etc.
	 */
	function IsVerified() {
		return ($this->owner->Verified)?'Yes':'No';
	}

	function MemberDateJoined() {
		return $this->owner->dbObject('Created')->Nice();
	}

	function MemberDateAgoJoined() {
		return $this->owner->dbObject('Created')->Ago();
	}

	function updateSummaryFields(Fieldset &$fields) {
		$fields['IsVerified'] = 'EmailIsVerified';
		$fields['MemberDateJoined'] = 'DateMemberJoined';
		$fields['MemberDateAgoJoined'] = 'HowLongAgoMemberJoined';
	}

	/**
	 * Check if the user has verified their email address.
	 *
	 * @param  ValidationResult $result
	 * @return ValidationResult
	 */
	function canLogIn(&$result) {
		if (!$this->owner->Verified) {
			$result->error('<h2>' . _t ('VerifyEmailRole.ERRORNOTEMAILVERIFIED', 'Please verify your email address before login.') . '</h2>' .
				'<a href="' . Director::absoluteBaseURL() . VerifyEmail_Controller::$ModuleURLSegment . '/verifyemail">' . _t ('VerifyEmailRole.CLICKHERE', 'Click here') . '</a> ' .
				_t ('VerifyEmailRole.ERRORSENTEMAILAGAIN', 'if you would like us to sent the verification email again.'),'bad'
			);
		}
		return $result;
	}


	/**
	 * Set VerificationString if not set
	 * If not verified log out user and display message.
	 */
	function onBeforeWrite() {
		if (!$this->owner->VerificationString) {
			$this->owner->VerificationString = MD5(rand());
		}
		if (!$this->owner->Verified) {
			if ((!$this->owner->VerificationEmailSent)) {
				$this->owner->sendemail($this->owner, false);
			}
			if (Member::currentUserID() && ($this->owner->Email == Member::currentUser()->Email)) {
				parent::onBeforeWrite();

				Security::logout(false);

				if (Director::redirected_to() == null) {
					$messageSet = array(
						'default' => _t('VerifyEmailRole.EMAILVERIFY','Please verify your email address by clicking on the link in the email before logging in.'),
					);
				}
				Session::set("Security.Message.type", 'bad');
				Security::permissionFailure($this->owner, $messageSet);
			} else return;
		}

		parent::onBeforeWrite();
	}

	/**
	 * Factory method for the verify email form
	 *
	 * @return Form Returns the verify email form
	 */
	public function VerifyEmailForm() {
		return new VerifyEmailForm(
			$this,
			'VerifyEmailForm',
			new FieldSet(
				new EmailField('Email', _t('Member.EMAIL', 'Email'))
			),
			new FieldSet(
				new FormAction(
					'verifyEmail',
					_t('VerifyEmailRole.BUTTONSEND', 'Send me the verify email link')
				)
			),
			false
		);
	}

	/**
	 * Helper function to send email to member
	 *
	 * @param Member $member
	 * @param Boolean $write Save to database
	 */
	public function sendemail($member, $write = true) {
		$config = SiteConfig::current_site_config();

		$email = new Email();
		$email->setTemplate('VerificationEmail');
		$email->setTo($member->Email);
		$email->setSubject(sprintf(_t('VerifyEmailRole.CONFIRMEMAILSUBJECT', 'Please confirm your email address with %s'), $config->Title));
		$email->populateTemplate(array(
			'ValdiationLink' => Director::absoluteBaseURL() . VerifyEmail_Controller::$ModuleURLSegment . '/validate/' . urlencode($member->Email) . '/' . $member->VerificationString,
			'Member' => $member,
		));
		$member->VerificationEmailSent = $email->send();
		if ($write) $member->write();
	}
}

class VerifyEmail_Controller extends Page_Controller {
	/**
	 * ModuleURLSegment used for the module defaults to 'verification'
	 *
	 * @var string
	 */
	public static $ModuleURLSegment = "verification";

	/**
	 * Show the "verify email" page
	 *
	 * @return string Returns the "verify email" page as HTML code.
	 */
	public function verifyemail() {
		Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/prototype/prototype.js');
		Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/behaviour/behaviour.js');
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/loader.js');
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/prototype_improvements.js');
		Requirements::javascript(THIRDPARTY_DIR . '/scriptaculous/effects.js');

		$tmpPage = new Page();
		$tmpPage->Title = _t('VerifyEmailRole.VERIFYEMAIL', 'Verify your email');
		$tmpPage->ModuleURLSegment = $this->ModuleURLSegment;
		$tmpPage->ID = -1; // Set the page ID to -1 so we dont get the top level pages as its children
		$controller = new Page_Controller($tmpPage);
		$controller->init();

		$customisedController = $controller->customise(array(
			'Content' =>
				'<p>' . _t('VerifyEmailRole.VERIFYBEFORELOGON','You need to verify the link in the email we sent you before you can log on.') . '</p>' .
				'<p>' . _t('VerifyEmailRole.USEFORMBELOW','Use the form below if you would like us to resend the link.') . '</p>',
			'Form' => $this->VerifyEmailForm(),
		));

		//Controller::$currentController = $controller;
		return $customisedController->renderWith(array('VerifyEmail_verifyemail', 'VerifyEmail', 'Page', 'ContentController'));
	}

	/**
	 * Factory method for the lost verify email form
	 *
	 * @return Form Returns the lost verify email form
	 */
	public function VerifyEmailForm() {
		return new MemberLoginForm(
			$this->owner,
			'verifyEmailSent',
			new FieldSet(
				new EmailField('Email', _t('Member.EMAIL', 'Email'))
			),
			new FieldSet(
				new FormAction(
					'verifyEmailSent',
					_t('VerifyEmailRole.BUTTONSEND', 'Send me the verify email link again')
				)
			),
			false
		);
	}

	/**
	 * Sent verification email form handler method
	 *
	 * This method is called when the user clicks on "Resent verification email"
	 *
	 * @param array $data Submitted data
	 */
	function verifyEmailSent($data = null) {
		$member = null;
		if ($data) {
			$SQL_email = Convert::raw2sql($data->postVar("Email"));
			$member = DataObject::get_one('Member', "\"Email\" = '{$SQL_email}'");
		}
		if($member) {
			$member->generateAutologinHash();
			VerifyEmailRole::sendemail($member);
			Director::redirect(VerifyEmail_Controller::$ModuleURLSegment . '/emailsent/' . urlencode($data['Email']));
		} elseif($data['Email']) {
			// Avoid information disclosure by displaying the same status,
			// regardless wether the email address actually exists
			Director::redirect(VerifyEmail_Controller::$ModuleURLSegment . '/emailsent/' . urlencode($data['Email']));
		} else {
				// Adds error message if nothing is entered into Email field.
			 $FormInfo = array(
					"MemberLoginForm_verifyEmailSent" => array(
						 "formError" => array(
								"message" => "Please enter an email address to have the email verification link resent.", "type" => "bad"
						 )
					)
			 );
			 Session::set("FormInfo", array_merge(Session::get("FormInfo"), $FormInfo ));
			 Director::redirect(Director::absoluteBaseURL() . VerifyEmail_Controller::$ModuleURLSegment . '/verifyemail/');
		}
	}

	/**
	 * Validate the link clicked in email
	 *
	 * @param SS_HTTPRequest $request The SS_HTTPRequest for this action.
	 * @return string Returns the "validated" page as HTML code.
	 */
	public function validate($request) {
		if ($request) {
			$SQL_email = Convert::raw2sql($request->param("Email"));
			$member = DataObject::get_one('Member', "\"Email\" = '{$SQL_email}'");
		}

		$tmpPage = new Page();
		$tmpPage->Title = _t('VerifyEmailRole.VERIFYEMAILHEADER', 'Verification link');
		$tmpPage->ModuleURLSegment = $this->ModuleURLSegment;
		$tmpPage->ID = -1; // Set the page ID to -1 so we dont get the top level pages as its children
		$controller = new Page_Controller($tmpPage);
		$controller->init();

		if ($member->VerificationString == Convert::raw2sql($request->param("VerificationString"))) {
			$member->Verified = true;
			$member->write();

			$customisedController = $controller->customise(array(
				'Title' => _t('VerifyEmailRole.ACCOUNTVERIFIEDTITLE', "Member account verified"),
				'Content' =>
					"<p>" .
					sprintf(_t('VerifyEmailRole.ACCOUNTVERIFIED', "Thank you %s! Your account has been verified, you can now login to the website."), $member->Name) .
					"</p>"
			));
			return $customisedController->renderWith(array('VerifyEmail_success', 'VerifyEmail', 'Page', $this->stat('template_main'), 'ContentController'));
		} else {
			$customisedController = $controller->customise(array(
				'Title' => _t('VerifyEmailRole.ACCOUNTVERIFIEDFAILTITLE', "Member email address verification failed"),
				'Content' =>
					"<p>" .
					sprintf(_t('VerifyEmailRole.ACCOUNTVERIFIEDFAIL', "Member email address verification failed, either unknown email address or invalid verification string. Please ensure you copy and pasted the entire link."), $member->Name) .
					"</p>"
			));
			return $customisedController->renderWith(array('VerifyEmail_fail', 'VerifyEmail', 'Page', $this->stat('template_main'), 'ContentController'));
		}
	}

	/**
	 * Show the "email sent" page, after a user has requested
	 * to resent validation email
	 *
	 * @param SS_HTTPRequest $request The SS_HTTPRequest for this action.
	 * @return string Returns the "password sent" page as HTML code.
	 */
	public function emailsent($request) {
		Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/behaviour/behaviour.js');
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/loader.js');
		Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/prototype/prototype.js');
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/prototype_improvements.js');
		Requirements::javascript(THIRDPARTY_DIR . '/scriptaculous/effects.js');

		$tmpPage = new Page();
		$tmpPage->Title = _t('VerifyEmailRole.VERIFYEMAILHEADER', 'Verification link');
		$tmpPage->ModuleURLSegment = 'Security';
		$tmpPage->ID = -1; // Set the page ID to -1 so we dont get the top level pages as its children
		$controller = new Page_Controller($tmpPage);
		$controller->init();

		$email = Convert::raw2xml($request->param('ID') . '.' . $request->getExtension());

		$customisedController = $controller->customise(array(
			'Title' => sprintf(_t('VerifyEmailRole.EMAILSENTHEADER', "Verify Email link sent to '%s'"), $email),
			'Content' =>
				"<p>" .
				sprintf(_t('VerifyEmailRole.EMAILSENTTEXT', "Thank you! A verify email link has been sent to  '%s', provided an account exists for this email address."), $email) .
				"</p>",
			'Email' => $email
		));
		return $customisedController->renderWith(array('verification_emailsent', 'Page', $this->stat('template_main'), 'ContentController'));
	}
}