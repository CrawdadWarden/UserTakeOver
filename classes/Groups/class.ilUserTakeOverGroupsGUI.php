<?php

require_once __DIR__ . "/../../vendor/autoload.php";

use srag\CustomInputGUIs\UserTakeOver\MultiSelectSearchNewInputGUI\UsersAjaxAutoCompleteCtrl;
use srag\DIC\UserTakeOver\DICTrait;

/**
 * Class ilUserTakeOverGroupsGUI
 *
 * @author Benjamin Seglias <bs@studer-raimann.ch>
 * @author Thibeau Fuhrer <thf@studer-raimann.ch>
 *
 * (I have no idea, but somehow without this it won't work...)
 * @ilCtrl_isCalledBy srag\CustomInputGUIs\UserTakeOver\MultiSelectSearchNewInputGUI\UsersAjaxAutoCompleteCtrl: ilUserTakeOverGroupsGUI
 */
class ilUserTakeOverGroupsGUI
{
    use DICTrait;

    const PLUGIN_CLASS_NAME = ilUserTakeOverPlugin::class;

    const CMD_STANDARD = 'content';
    const CMD_ADD = 'add';
    const CMD_SAVE = 'save';
    const CMD_CREATE = 'create';
    const CMD_EDIT = 'edit';
    const CMD_UPDATE = 'update';
    const CMD_CONFIRM = 'confirmDelete';
    const CMD_DELETE = 'delete';
    const CMD_CANCEL = 'cancel';
    const CMD_APPLY_FILTER = 'applyFilter';
    const CMD_RESET_FILTER = 'resetFilter';
    const IDENTIFIER = 'usrtoGrp';

    /**
     * dispatches the current command from ilCtrl.
     */
    public function executeCommand()
    {
        if (!ilObjUserTakeOverAccess::hasWriteAccess()) {
            ilUtil::sendFailure(self::plugin()->translate('permission_denied'), true);
            self::dic()->ctrl()->redirectByClass(ilObjComponentSettingsGUI::class, 'listPlugins');
        }

        $cmd = self::dic()->ctrl()->getCmd(self::CMD_STANDARD);
        switch ($cmd) {
            case self::CMD_STANDARD:
            case self::CMD_ADD:
            case self::CMD_SAVE:
            case self::CMD_CREATE:
            case self::CMD_EDIT:
            case self::CMD_UPDATE:
            case self::CMD_CONFIRM:
            case self::CMD_CANCEL:
            case self::CMD_DELETE:
            case self::CMD_APPLY_FILTER:
            case self::CMD_RESET_FILTER:
                $this->{$cmd}();
                break;
            default:
                throw new ilException("command not defined.");
                break;
        }
    }

    protected function content()
    {
        $f = self::dic()->ui()->factory();
        self::dic()->toolbar()->addComponent($f->button()->standard(self::plugin()->translate("add_grp"), self::dic()->ctrl()
                                                                                                              ->getLinkTargetByClass(ilUserTakeOverGroupsGUI::class, ilUserTakeOverGroupsGUI::CMD_ADD)));

        $ilUserTakeOverGroupsTableGUI = new ilUserTakeOverGroupsTableGUI($this, self::CMD_STANDARD);
        self::output()->output($ilUserTakeOverGroupsTableGUI->getHTML());
    }

    protected function add()
    {
        $usrtoGroupFormGUI = new usrtoGroupFormGUI($this, new usrtoGroup());
        self::output()->output($usrtoGroupFormGUI);
    }

    protected function create()
    {
        $usrtoGroupFormGUI = new usrtoGroupFormGUI($this, new usrtoGroup());
        $usrtoGroupFormGUI->setValuesByPost();
        if ($usrtoGroupFormGUI->saveObject()) {
            ilUtil::sendSuccess(self::plugin()->translate('create_grp_msg_success'), true);
            self::dic()->ctrl()->redirectByClass([ilUserTakeOverMainGUI::class, self::class]);
        }
        self::output()->output($usrtoGroupFormGUI);
    }

    protected function edit()
    {
        $usrtoGroupFormGUI = new usrtoGroupFormGUI($this, usrtoGroup::find(filter_input(INPUT_GET, self::IDENTIFIER)));
        $usrtoGroupFormGUI->fillForm();
        self::output()->output($usrtoGroupFormGUI);
    }

    protected function update()
    {
        $usrtoGroupFormGUI = new usrtoGroupFormGUI($this, usrtoGroup::find(filter_input(INPUT_GET, self::IDENTIFIER)));
        $usrtoGroupFormGUI->setValuesByPost();
        if ($usrtoGroupFormGUI->saveObject()) {
            ilUtil::sendSuccess(self::plugin()->translate('update_grp_msg_success'), true);
            self::dic()->ctrl()->redirectByClass([ilUserTakeOverMainGUI::class, self::class]);
        }
        self::output()->output($usrtoGroupFormGUI);
    }

    protected function confirmDelete()
    {
        /**
         * @var usrToGroup $usrToGroup
         */
        $usrToGroup = usrtoGroup::find(filter_input(INPUT_GET, self::IDENTIFIER));

        ilUtil::sendQuestion(self::plugin()->translate('confirm_delete_grp'), true);
        $confirm = new ilConfirmationGUI();
        $confirm->addItem(self::IDENTIFIER, $usrToGroup->getId(), $usrToGroup->getTitle());
        $confirm->setFormAction(self::dic()->ctrl()->getFormActionByClass([ilUserTakeOverMainGUI::class, self::class]));
        $confirm->setCancel(self::plugin()->translate('cancel'), self::CMD_CANCEL);
        $confirm->setConfirm(self::plugin()->translate('delete'), self::CMD_DELETE);

        self::output()->output($confirm);
    }

    protected function delete()
    {
        /**
         * @var usrtoGroup $usrtoGroup
         */
        $usrtoGroup = usrtoGroup::find(filter_input(INPUT_POST, self::IDENTIFIER));
        $members    = usrtoMember::where(["group_id" => $usrtoGroup->getId()])->get();
        /**
         * @var usrtoMember $member
         */
        foreach ($members as $member) {
            $usrtoMember = usrtoMember::find($member->getId());
            $usrtoMember->delete();
        }
        $usrtoGroup->delete();
        $this->cancel();
    }

    protected function cancel()
    {
        self::dic()->ctrl()->redirectByClass([ilUserTakeOverMainGUI::class, self::class], self::CMD_STANDARD);
    }

    protected function applyFilter()
    {
        $ilUserTakeOverGroupsTableGUI = new ilUserTakeOverGroupsTableGUI($this, self::CMD_STANDARD);
        $ilUserTakeOverGroupsTableGUI->writeFilterToSession();
        self::dic()->ctrl()->redirectByClass([ilUserTakeOverMainGUI::class, self::class], self::CMD_STANDARD);
    }

    protected function resetFilter()
    {
        $ilUserTakeOverGroupsTableGUI = new ilUserTakeOverGroupsTableGUI($this, self::CMD_STANDARD);
        $ilUserTakeOverGroupsTableGUI->resetFilter();
        $ilUserTakeOverGroupsTableGUI->resetOffset();
        self::dic()->ctrl()->redirectByClass([ilUserTakeOverMainGUI::class, self::class], self::CMD_STANDARD);
    }
}
