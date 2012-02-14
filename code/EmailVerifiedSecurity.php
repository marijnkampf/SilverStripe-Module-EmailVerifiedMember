<?php

/**
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class EmailVerifiedSecurity extends Extension {
    
    public static $allowed_actions = array('emailsent','verifyemail','verifyEmailSent','validate');
    
    /**
     * Show the "password sent" page, after a user has requested
     * to reset their password.
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
    	$tmpPage->Title = _t('Security.LOSTPASSWORDHEADER');
    	$tmpPage->URLSegment = 'Security';
    	$tmpPage->ID = -1; // Set the page ID to -1 so we dont get the top level pages as its children
    	$controller = new Page_Controller($tmpPage);
    	$controller->init();

    	$email = Convert::raw2xml($request->param('ID') . '.' . $request->getExtension());
		
    	$customisedController = $controller->customise(array(
            'Title' => sprintf(_t('EmailVerifiedMember.EMAILSENTHEADER', "Verify Email link sent to '%s'"), $email),
            'Content' =>
                "<p>" .
		sprintf(_t('EmailVerifiedMember.EMAILSENTTEXT', "Thank you! A verify email link has been sent to  '%s', provided an account exists for this email address."), $email) .
		"</p>",
            'Email' => $email
	));
	return $customisedController->renderWith(array('Security_emailsent', 'Security', $this->owner->stat('template_main'), 'ContentController'));
    }
    
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
	$tmpPage->Title = _t('EmailVerifiedMember.VERIFYEMAIL', 'Verify your email');
	$tmpPage->URLSegment = 'Security';
	$tmpPage->ID = -1; // Set the page ID to -1 so we dont get the top level pages as its children
	$controller = new Page_Controller($tmpPage);
	$controller->init();

	$customisedController = $controller->customise(array(
            'Content' =>
                '<p>' . _t('EmailVerifiedMember.VERIFYBEFORELOGON','You need to verify the link in the email we sent you before you can log on.') . '</p>' .
		'<p>' . _t('EmailVerifiedMember.USEFORMBELOW','Use the form below if you would like us to resend the link.') . '</p>',
            'Form' => $this->owner->VerifyEmailForm(),
	));
        
        //Controller::$currentController = $controller;
	return $customisedController->renderWith(array('Security_verifyemail', 'Security', $this->owner->stat('template_main'), 'ContentController'));
    }
    
    /**
     * Factory method for the lost verify email form
     *
     * @return Form Returns the lost verify email form
     */
    public function VerifyEmailForm() {
        return new EmailVerifiedMemberLoginForm(
            $this->owner,
            'verifyEmailSent',
            new FieldSet(
                new EmailField('Email', _t('Member.EMAIL', 'Email'))
            ),
            new FieldSet(
                new FormAction(
                    'verifyEmailSent',
                    _t('EmailVerifiedMember.BUTTONSEND', 'Send me the verify email link again')
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
            EmailVerifiedMember::sendemail($member);
            Director::redirect('Security/emailsent/' . urlencode($data['Email']));
	} elseif($data['Email']) {
            // Avoid information disclosure by displaying the same status,
            // regardless wether the email address actually exists
            Director::redirect('Security/emailsent/' . urlencode($data['Email']));
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
            Director::redirect('Security/verifyemail/');
	}
    }
    
    /**
     * Validate the link clicked in email
     *
     * @param SS_HTTPRequest $request The SS_HTTPRequest for this action.
     * @return string Returns the "validated" page as HTML code.
     */
    public function validate($request) {
        
        $tmpPage = new Page();
	$tmpPage->Title = _t('EmailVerifiedMember.VERIFYEMAILHEADER', 'Verification link');
	$tmpPage->URLSegment = 'Security';
	$tmpPage->ID = -1; // Set the page ID to -1 so we dont get the top level pages as its children
	$controller = new Page_Controller($tmpPage);
	$controller->init();
        
        if($request && $member = DataObject::get_one('Member', "\"Email\" = '".Convert::raw2sql($request->param('ID'))."'")){
            if ($member->VerificationString == Convert::raw2sql($request->param('OtherID'))){
                $member->Verified = true;
                $member->write();

                $customisedController = $controller->customise(array(
                    'Title' => _t('EmailVerifiedMember.ACCOUNTVERIFIEDTITLE', "Member account verified"),
                    'Content' =>
                        "<p>" .
                        sprintf(_t('EmailVerifiedMember.ACCOUNTVERIFIED', "Thank you %s! Your account has been verified, you can now login to the website."), $member->Name) .
                        "</p>"
                ));
                return $customisedController->renderWith(array('Security_validationsuccess', 'Security', $this->owner->stat('template_main'), 'ContentController'));
            }
        }
        
        // Verification failed
        $customisedController = $controller->customise(array(
            'Title' => _t('EmailVerifiedMember.ACCOUNTVERIFIEDFAILTITLE', "Member email address verification failed"),
            'Content' =>
                "<p>" .
                sprintf(_t('EmailVerifiedMember.ACCOUNTVERIFIEDFAIL', "Member email address verification failed, either unknown email address or invalid verification string. Please ensure you copy and pasted the entire link."), $member->Name) .
                "</p>"
        ));
        return $customisedController->renderWith(array('Security_validationfail', 'Security', $this->owner->stat('template_main'), 'ContentController'));
    }
}