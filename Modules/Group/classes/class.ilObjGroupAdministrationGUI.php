<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once "./Services/Membership/classes/class.ilMembershipAdministrationGUI.php" ;

/**
 * Group Administration Settings
 *
 * @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
 * @version $Id:$
 *
 * @ilCtrl_Calls ilObjGroupAdministrationGUI: ilPermissionGUI, ilMemberExportSettingsGUI, ilUserActionadminGUI
 *
 * @ingroup ModulesGroup
 */
class ilObjGroupAdministrationGUI extends ilMembershipAdministrationGUI
{
    public function getType()
    {
        return "grps";
    }
    
    public function getParentObjType()
    {
        return "grp";
    }
    
    protected function getAdministrationFormId()
    {
        return ilAdministrationSettingsFormHandler::FORM_GROUP;
    }

    protected function addChildContentsTo($form)
    {
        return $form;
    }

    protected function saveChildSettings($form)
    {
    }

    protected function getChildSettingsInfo($a_form_id)
    {
        return null;
    }
}
