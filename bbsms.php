<?php

require_once 'bbsms.civix.php';
// used in hook_civicrm_navigationMenu
// use CRM_Bbsms_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function bbsms_civicrm_config(&$config)
{
    _bbsms_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function bbsms_civicrm_xmlMenu(&$files)
{
    _bbsms_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function bbsms_civicrm_install()
{
    $groupID = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', 'sms_provider_name', 'id', 'name');
    $params =
        array('option_group_id' => $groupID,
            'label' => 'bbSMS',
            'value' => 'info.kabbalah.bbSMS',
            'name' => 'bbSMS',
            'is_default' => 1,
            'is_active' => 1,
            'version' => 3,);
    require_once 'api/api.php';
    civicrm_api('option_value', 'create', $params);
    return __bbsms_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function bbsms_civicrm_postInstall()
{
    _bbsms_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function bbsms_civicrm_uninstall()
{
    $optionID = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue','bbSMS','id','name');
    if ($optionID)
        CRM_Core_BAO_OptionValue::del($optionID);
    $filter    =  array('name'  => 'info.kabbalah.bbSMS');
    $Providers =  CRM_SMS_BAO_Provider::getProviders(False, $filter, False);
    if ($Providers){
        foreach($Providers as $key => $value){
            CRM_SMS_BAO_Provider::del($value['id']);
        }
    }
    return _bbsms_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function bbsms_civicrm_enable()
{
    $optionID = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue','bbSMS' ,'id','name');
    if ($optionID)
        CRM_Core_BAO_OptionValue::setIsActive($optionID, TRUE);
    $filter    =  array('name' => 'info.kabbalah.bbSMS');
    $Providers =  CRM_SMS_BAO_Provider::getProviders(False, $filter, False);
    if ($Providers){
        foreach($Providers as $key => $value){
            CRM_SMS_BAO_Provider::setIsActive($value['id'], TRUE);
        }
    }
    return _bbsms_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function bbsms_civicrm_disable()
{
    $optionID = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue','bbSMS','id','name');
    if ($optionID)
        CRM_Core_BAO_OptionValue::setIsActive($optionID, FALSE);
    $filter    =  array('name' =>  'info.kabbalah.bbSMS');
    $Providers =  CRM_SMS_BAO_Provider::getProviders(False, $filter, False);
    if ($Providers){
        foreach($Providers as $key => $value){
            CRM_SMS_BAO_Provider::setIsActive($value['id'], FALSE);
        }
    }
    return _bbsms_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function bbsms_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL)
{
    return _bbsms_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function bbsms_civicrm_managed(&$entities)
{
    _bbsms_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function bbsms_civicrm_caseTypes(&$caseTypes)
{
    _bbsms_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function bbsms_civicrm_angularModules(&$angularModules)
{
    _bbsms_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function bbsms_civicrm_alterSettingsFolders(&$metaDataFolders = NULL)
{
    _bbsms_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_entityTypes
 */
function bbsms_civicrm_entityTypes(&$entityTypes)
{
    _bbsms_civix_civicrm_entityTypes($entityTypes);
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *
 * function bbsms_civicrm_preProcess($formName, &$form) {
 *
 * } // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 *
 * function bbsms_civicrm_navigationMenu(&$menu) {
 * _bbsms_civix_insert_navigation_menu($menu, 'Mailings', array(
 * 'label' => E::ts('New subliminal message'),
 * 'name' => 'mailing_subliminal_message',
 * 'url' => 'civicrm/mailing/subliminal',
 * 'permission' => 'access CiviMail',
 * 'operator' => 'OR',
 * 'separator' => 0,
 * ));
 * _bbsms_civix_navigationMenu($menu);
 * } // */
