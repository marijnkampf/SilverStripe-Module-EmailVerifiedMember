# Installing

EmailVerifiedMember is a class that requires users to confirm their email address before they can logon to the CMS.

## Setting up EmailVerifiedMember

 * EmailVerifiedMember folder should be in your sites root folder (folder name doesn't matter)
 * Default EmailVerifiedMember extends Member class, alter line in _config.php if you would only like to extend particular sub-classes.
 * If your mail configuration doesn't set a default or valid From-Header, you can set the Default From header in your _config
 * EmailVerifiedMember::$Default_From_Address 

