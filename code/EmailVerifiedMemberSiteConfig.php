<?php

class EmailVerifiedMemberSiteConfig extends DataExtension
{
    public static $db = array(
        "Moderate" => "Boolean"
    );

    public static $defaults = array(
        "Moderate" => "true"
    );


    public static $has_many = array(
        "Moderators" => 'Member'
    );

    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab('Root.MemberModerators', new CheckboxField("Moderate", _t('EmailVerifiedMember.REQUIREMODERATION', "Require moderation.")));
        $config = GridFieldConfig_RelationEditor::create();
        // Set the names and data for our gridfield columns
        $config->getComponentByType('GridFieldDataColumns')->setDisplayFields(array(
                'FirstName'=> 'First name',
                'Surname' => 'Surname',
                'Email' => 'Email'
        ));
        $config->removeComponentsByType('GridFieldAddNewButton');
        $config->removeComponentsByType('GridFieldEditButton');
        $config->getComponentByType('GridFieldAddExistingAutocompleter')->setSearchFields(array('FirstName', 'Surname', 'Email'))->setResultsFormat('$FirstName $Surname $Email');
        // Create a gridfield to hold the student relationship   
        $headerMenuField = new GridField(
            'Moderators', // Field name
            'Members', // Field title
            $this->owner->Moderators(),
            $config
        );
        
//		$fields->addFieldToTab('Root.Main', new EmailField("AdminEmail", "Admin email address (from address used for forms etc.)")); 


        $fields->addFieldToTab('Root.MemberModerators', $headerMenuField);
    }
}
