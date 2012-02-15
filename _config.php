<?php

/**
 * Default EmailVerifiedMember extends Member class, alter line below if you would only like to extend particular sub-classes.
 */
DataObject::add_extension('Member', 'EmailVerifiedMember');

/**
 * Alter the Silverstripe Login Forms
 */
Authenticator::register('EmailVerifiedMemberAuthenticator');
Authenticator::unregister('MemberAuthenticator');

/**
 * Alter the Security Controller 
 */
Object::add_extension('Security', 'EmailVerifiedSecurity');