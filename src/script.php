<?php
/**
 * @package    PB Klaro! Consent Manager
 *
 * @author     Sebastian Brümmer <sebastian@produktivbuero.de>
 * @copyright  Copyright (C) 2020 *produktivbüro . All rights reserved
 * @license    GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

class plgSystemPbKlaroInstallerScript
{
  /**
   * Constructor
   *
   * @param   JAdapterInstance  $adapter  The object responsible for running this script
   */
  public function __construct(JAdapterInstance $adapter)
  {
    // helper
    JLoader::register('PlgContentPBContactMapHelper', __DIR__ . '/helper.php');
  }


  /**
   * Called after any type of action
   *
   * @param   string  $route  Which action is happening (install|uninstall|discover_install|update)
   * @param   JAdapterInstance  $adapter  The object responsible for running this script
   *
   * @return  boolean  True on success
   */
  public function postflight($route, JAdapterInstance $adapter)
  {
    if ($route == 'install')
    {

      echo '<div class="alert alert-info">';
      echo '<strong>' . JText::_('PLG_SYSTEM_PBKLARO') . '</strong> - ' . JText::_('PLG_SYSTEM_PBKLARO_XML_INSTALL_MESSAGE');
      echo '</div>';
    }

    return true;
  }
}
