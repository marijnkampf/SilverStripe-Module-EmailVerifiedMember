<?php
/**
 * Implements a verification email on user registration
 * @module EmailVerifiedMember
 */
class EmailVerifiedMember extends DataExtension
{
    public static $hasOnBeforeWrite = false;

    public static $db = array(
        "Verified" => "Boolean",
        "VerificationString" => "Varchar(32)",
        "VerificationEmailSent" => "Boolean",
        "Moderated" => "Boolean",
        "ModerationEmailSent" => "Boolean",
    );

    public static $has_one = array(
        "Moderator" => "SiteConfig"
    );

    public static $defaults = array(
        "Verified" => false,
        "Moderated" => false,
    );

  /**
   * Modify the field set to be displayed in the CMS detail pop-up
   */
    public function updateCMSFields(FieldList $fields)
    {
        $fields->removeByName("ModerationEmailSent");
        $fields->insertAfter(new CheckboxField('Verified', 'Email Verified'), _t('EmailVerifiedMember.EMAIL', 'Email'));
    }

    /**
     * Additional columns in Member Table displayed in the CMS so that you can easily see whether members email address has been verified etc.
     */
    public function IsVerified()
    {
        return ($this->owner->Verified)?_t('EmailVerifiedMember.YES', 'Yes'):_t('EmailVerifiedMember.NO', 'No');
    }

    public function IsModerated()
    {
        return ($this->owner->Moderated)?_t('EmailVerifiedMember.YES', 'Yes'):_t('EmailVerifiedMember.NO', 'No');
    }

    public function MemberDateJoined()
    {
        return $this->owner->dbObject('Created')->Nice();
    }

    public function MemberDateAgoJoined()
    {
        return $this->owner->dbObject('Created')->Ago();
    }

    public function updateSummaryFields(&$fields)
    {
        $fields['IsVerified'] = _t('EmailVerifiedMember.EMAILISVERIFIED', 'Email is Verified');
        $fields['IsModerated'] = _t('EmailVerifiedMember.MEMBERHASBEENMODERATED', 'Member has been moderated');
        $fields['MemberDateJoined'] = _t('EmailVerifiedMember.DATEMEMBERJOINED', 'Date member joined');
        $fields['MemberDateAgoJoined'] = _t('EmailVerifiedMember.HOWLONGAGOMEMBERJOINED', 'How long ago member joined');
    }

    /**
     * Check if the user has verified their email address.
     *
     * @param  ValidationResult $result
     * @return ValidationResult
     */
    public function canLogIn(&$result)
    {
        if (!Permission::check('ADMIN')) {
            if (!$this->owner->Verified) {
                $result->error(_t('EmailVerifiedMember.ERRORNOTEMAILVERIFIED', 'Please verify your email address before login.'));
            }
            if ((!$this->owner->Moderated) && ($this->owner->requiresModeration())) {
                $result->error(_t('EmailVerifiedMember.ERRORNOTMODERATED', 'A moderator needs to approve your account before you can log in.'));
            }
        }
        return $result;
    }

    public function requiresModeration()
    {
        $config = SiteConfig::current_site_config();
        return ($config->Moderate);
    }


    /**
     * Set VerificationString if not set
     * If not verified log out user and display message.
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if (!$this->owner->VerificationString) {
            $this->owner->VerificationString = MD5(rand());
        }
        if (!$this->owner->Verified) {
            if ((!$this->owner->VerificationEmailSent)) {
                if (!self::$hasOnBeforeWrite) {
                    self::$hasOnBeforeWrite = true;
                    $this->owner->sendemail($this->owner, false);
                }
            }
            if (Member::currentUserID() && ($this->owner->Email == Member::currentUser()->Email)) {
                Security::logout(false);

                if (!is_null(Controller::redirectedTo())) {
                    $messageSet = array(
                        'default' => _t('EmailVerifiedMember.EMAILVERIFY', 'Please verify your email address by clicking on the link in the email before logging in.'),
                    );
                }
                Session::set("Security.Message.type", 'bad');
                Security::permissionFailure(Controller::curr(), $messageSet);
            } else {
                return;
            }
        } else {
            return;
        }
    }

    /**
     * Factory method for the verify email form
     *
     * @return Form Returns the verify email form
     */
    public function VerifyEmailForm()
    {
        return new VerifyEmailForm(
            $this,
            'VerifyEmailForm',
            new FieldList(
                new EmailField('Email', _t('Member.EMAIL', 'Email'))
            ),
            new FieldList(
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
    public function sendemail($member, $write = true)
    {
        $config = SiteConfig::current_site_config();

        $email = new Email();
        $email->setTemplate('VerificationEmail');
        $email->setTo($member->Email);
        $email->setSubject(sprintf(_t('EmailVerifiedMember.CONFIRMEMAILSUBJECT', 'Please confirm your email address with %s'), $config->Title));
        $email->populateTemplate(array(
            'ValdiationLink' => Director::absoluteBaseURL() . 'Security/validate/' . urlencode($member->Email) . '/' . $member->VerificationString,
            'Member' => $member,
            'ModerationRequired' => $config->Moderate
        ));

        $member->VerificationEmailSent = $email->send();
        if ($write) {
            $member->write();
        }
    }

    /**
     * Helper function to send email to member
     *
     * @param Member $member
     * @param Boolean $write Save to database
     */
    public function sendmoderatoremail()
    {
        $config = SiteConfig::current_site_config();

        foreach ($config->Moderators() as $moderator) {
            try {
                $email = new Email();
                $email->setTemplate('ModerationEmail');
                $email->setTo($moderator->Email);
                $email->replyTo($this->owner->Email);
                $email->setSubject(sprintf(_t('EmailVerifiedMember.NEWMEMBEREMAILSUBJECT', 'New member waiting for moderation at %s'), $config->Title));
                $email->populateTemplate(array(
                    'ModerationLink' => Director::absoluteBaseURL() . 'admin/security/EditForm/field/Members/item/' . urlencode($this->owner->ID) . '/edit',
                    'Moderator' => $moderator,
                    'Member' => $this->owner,
                    'SiteTitle' => $config->Title,
                ));
                $this->owner->ModerationEmailSent = $email->send();
//				Debug::Show($email);
            } catch (Exception $e) {
                Debug::Show($e);
            }
        }
        //$this->owner->ModerationEmailSent = $email->send();
    }
}
