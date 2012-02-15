<?php
/**
 * Implements a verification email on user registration
 * @module EmailVerifiedMember
 */
class EmailVerifiedMember extends DataObjectDecorator {
    
  static $Default_From_Address = null;
  
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
			$result->error(_t('EmailVerifiedMember.ERRORNOTEMAILVERIFIED', 'Please verify your email address before login.'));
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
						'default' => _t('EmailVerifiedMember.EMAILVERIFY','Please verify your email address by clicking on the link in the email before logging in.'),
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
					_t('EmailVerifiedMember.BUTTONSEND', 'Send me the verify email link')
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
		$email->setSubject(sprintf(_t('EmailVerifiedMember.CONFIRMEMAILSUBJECT', 'Please confirm your email address with %s'), $config->Title));
		$email->populateTemplate(array(
			'ValdiationLink' => Director::absoluteBaseURL() . 'Security/validate/' . urlencode($member->Email) . '/' . $member->VerificationString,
			'Member' => $member,
		));
                
                if(EmailVerifiedMember::$Default_From_Address) $email->setFrom(EmailVerifiedMember::$Default_From_Address);
                
		$member->VerificationEmailSent = $email->send();
		if ($write) $member->write();
	}
}