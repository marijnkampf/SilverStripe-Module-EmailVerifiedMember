<?php
/**
 * Log-in form for the "member" authentication method
 * @package EmailVerifiedMember
 */
class EmailVerifiedMemberLoginForm extends MemberLoginForm {

	protected $authenticator_class = 'EmailVerifiedMemberAuthenticator';

	/**
	 * Constructor
	 *
	 * @param Controller $controller The parent controller, necessary to
	 *                               create the appropriate form action tag.
	 * @param string $name The method on the controller that will return this
	 *                     form object.
	 * @param FieldList|FormField $fields All of the fields in the form - a
	 *                                   {@link FieldList} of {@link FormField}
	 *                                   objects.
	 * @param FieldList|FormAction $actions All of the action buttons in the
	 *                                     form - a {@link FieldList} of
	 *                                     {@link FormAction} objects
	 * @param bool $checkCurrentUser If set to TRUE, it will be checked if a
	 *                               the user is currently logged in, and if
	 *                               so, only a logout button will be rendered
	 * @param string $authenticatorClassName Name of the authenticator class that this form uses.
	 */
	function __construct($controller, $name, $fields = null, $actions = null,
											 $checkCurrentUser = true) {

		// This is now set on the class directly to make it easier to create subclasses
		// $this->authenticator_class = $authenticatorClassName;

		$customCSS = project() . '/css/member_login.css';
		if(Director::fileExists($customCSS)) {
			Requirements::css($customCSS);
		}

		if(isset($_REQUEST['BackURL'])) {
			$_REQUEST['BackURL'] = str_replace("/RegistrationForm", "", $_REQUEST['BackURL']);
			$backURL = $_REQUEST['BackURL'];
		} else {
			if (strpos(Session::get('BackURL'), "/RegistrationForm") > 0) {
				Session::set('BackURL', str_replace("/RegistrationForm", "", Session::get('BackURL')));
			}
			$backURL = str_replace("/RegistrationForm", "", Session::get('BackURL'));
		}

		if($checkCurrentUser && Member::currentUser() && Member::logged_in_session_exists()) {
			$fields = new FieldList(
				new HiddenField("AuthenticationMethod", null, $this->authenticator_class, $this)
			);
			$actions = new FieldList(
				new FormAction("logout", _t('Member.BUTTONLOGINOTHER', "Log in as someone else"))
			);
		} else {
			if(!$fields) {
				$label=singleton('Member')->fieldLabel(Member::get_unique_identifier_field());
				$fields = new FieldList(
					new HiddenField("AuthenticationMethod", null, $this->authenticator_class, $this),
					//Regardless of what the unique identifer field is (usually 'Email'), it will be held in the 'Email' value, below:
					new TextField("Email", $label, Session::get('SessionForms.MemberLoginForm.Email'), null, $this),
					new PasswordField("Password", _t('Member.PASSWORD', 'Password'))
				);
				if (Security::config()->autologin_enabled) {
					$fields->push(new CheckboxField(
						"Remember",
						_t('Member.REMEMBERME', "Remember me next time?")
					));
				}
			}
			if(!$actions) {
				$actions = new FieldList(
					new FormAction('dologin', _t('Member.BUTTONLOGIN', "Log in")),
					new LiteralField(
						'forgotPassword',
						'<p id="ForgotPassword"><a href="Security/lostpassword">' . _t('Member.BUTTONLOSTPASSWORD', "I've lost my password") . '</a></p>'
					),
					new LiteralField(
						'resendEmail',
						'<p id="ResendEmail"><a href="Security/verifyemail">' . _t('EmailVerifiedMember.BUTTONRESENDEMAIL', "I've lost my verification email") . '</a></p>'
					)
				);
			}
		}

		if(isset($backURL)) {
			$fields->push(new HiddenField('BackURL', 'BackURL', $backURL));
		}

		parent::__construct($controller, $name, $fields, $actions);

		// Focus on the email input when the page is loaded
		// Only include this if other form JS validation is enabled
/*		if($this->getValidator()->getJavascriptValidationHandler() != 'none') {
			Requirements::customScript(<<<JS
				(function() {
					var el = document.getElementById("MemberLoginForm_LoginForm_Email");
					if(el && el.focus) el.focus();
				})();
JS
			);
		}*/
	}

}