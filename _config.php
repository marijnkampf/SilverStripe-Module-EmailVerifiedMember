<?php

/**
 * Default EmailVerifiedMember extends Member class, alter line below if you would only like to extend particular sub-classes.
 */
DataObject::add_extension('Member', 'VerifyEmailRole');

// Uncomment and change code below if you would like to use a different URL segment for the module
//VerifyEmail_Controller::$ModuleURLSegment = "verification";

/**
 * Settings below do not need to be changed.
 */
Director::addRules(20, array(VerifyEmail_Controller::$ModuleURLSegment . '//$Action//$Email//$VerificationString' => 'VerifyEmail_Controller'));
VerifyEmail_Controller::$allowed_actions = array('verifyemail', 'emailsent', 'VerifyEmailForm');
