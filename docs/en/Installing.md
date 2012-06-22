# Installing

EmailVerifiedMember is a class that requires users to confirm their email address before they can logon to the CMS.

## Setting up EmailVerifiedMember

 * EmailVerifiedMember folder should be in your sites root folder (folder name doesn't matter)
 * Default EmailVerifiedMember extends Member class, alter line in _config.php if you would only like to extend particular sub-classes.
 * ContentController includes method LoginForm() to make $LoginForm available anywhere in your Templates. This method needs to be overwritten in Page_Controller

public function LoginForm() {
  return EmailVerifiedMemberAuthenticator::get_login_form($this);
}

