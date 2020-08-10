<?php
/**
 * @package    PB Klaro! Consent Manager
 *
 * @author     Sebastian Brümmer <sebastian@produktivbuero.de>
 * @copyright  Copyright (C) 2020 *produktivbüro . All rights reserved
 * @license    GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Plugin\CMSPlugin;

class plgSystemPbKlaro extends CMSPlugin
{
  /**
   * SETTINGS
   */
  const KLARO = '0.4.24'; // https://github.com/kiprotect/klaro/releases
  
  /**
   * Application object
   *
   * @var    CMSApplication
   * @since  1.0
   */
  protected $app;

  /**
   * Load the language file on instantiation
   *
   * @var    boolean
   * @since  3.1
   */
  protected $autoloadLanguage = true;

  /**
   * This function is called on initialization.
   *
   * @return  void.
   *
   * @since   1.0
   */

  public function __construct(&$subject, $config = array())
  {

    parent::__construct($subject, $config);

    $this->settings = array();

    // plugin parameters
    $params = new JRegistry($config['params']);

    $this->settings['privacyPolicy'] = $params->get('privacyPolicy'); /* itemID */
    $this->settings['default'] = (int) $params->get('default', 0);
    $this->settings['mustConsent'] = (int) $params->get('mustConsent', 0);
    $this->settings['acceptAll'] = (int) $params->get('acceptAll', 0);
    $this->settings['hideDeclineAll'] = (int) $params->get('hideDeclineAll', 0);
    $this->settings['hideLearnMore'] = (int) $params->get('hideLearnMore', 0);

    $this->settings['styles']['backgroundcolor'] = $params->get('backgroundcolor');
    $this->settings['styles']['linkcolor'] = $params->get('linkcolor');
    $this->settings['styles']['acceptcolor'] = $params->get('acceptcolor');
    $this->settings['styles']['savecolor'] = $params->get('savecolor');
    $this->settings['styles']['declinecolor'] = $params->get('declinecolor');
    $this->settings['styles']['togglecolor'] = $params->get('togglecolor');
    $this->settings['styles']['css'] = $params->get('css');

    $this->settings['elementID'] = (string) $params->get('elementID', 'klaro');
    $this->settings['storageMethod'] = (string) $params->get('storageMethod', 'cookie');
    $this->settings['cookieName'] = (string) $params->get('cookieName', 'klaro');
    $this->settings['cookieExpiresAfterDays'] = (int) $params->get('cookieExpiresAfterDays', 120);
    $this->settings['cookieDomain'] = (string) $params->get('cookieDomain', '');

    $this->settings['klaro-apps'] = $params->get('klaro-apps'); /* cookie apps */
  }

  /**
   * This event is triggered before the framework creates the head section of the Document.
   *
   * @return  void.
   *
   * @since   1.0
   */
  public function onBeforeCompileHead()
  {
    /* fast fail */
    if ( $this->app->getName() != 'site' ) {
      return true;
    }

    $doc = JFactory::getDocument();
    $lang = JFactory::getLanguage();
    $langtag = substr( $lang->getTag() ,0 ,2 );


    /* PREPARE APPS */
    if ( empty($this->settings['klaro-apps']) ) {
      $doc->addScriptDeclaration( 'var klaro = klaro || { show: function(){ return; } }' ); /* add dummy to avoid errors for manager onclick-event */
      return true;
    }
    
    $apps = [];
    $purposes = []; /* translated cookie purposes */
    $descriptions = []; /* translated apps descriptions */

    foreach ($this->settings['klaro-apps'] as $key => $app) {
      if ( $app->enabled == '0' ) continue;

      $apps[] = array(
                  'name' => $app->name,
                  'title' => $app->title,
                  'purposes' => $app->purposes,
                  'required' => $app->required == '1' ? true : false,
                  'optOut' => $app->optOut == '1' ? true : false,
                  'onlyOnce' => $app->onlyOnce == '1' ? true : false
                );
      
      foreach ($app->purposes as $purpose) {
        $purposes[$purpose] = JText::_( 'PLG_SYSTEM_PBKLARO_PURPOSE_' . strtoupper($purpose) );
      }

      if ( !empty($app->description) ) {
        $descriptions[$name] = array( 'description' => $app->description );
      }

      // add inline app script
      if ( !empty($app->inline) ) {
        $inline = preg_replace('/<script[^>]*>([^<]+)<\/script>.*/is', '<script type="text/plain" data-type="application/javascript" data-name="' . $name . '">$1</script>', $app->inline);
        
        if ( $inline != $app->inline ) $doc->addCustomTag( $inline ); // add code if there have been replacements
      }

      // add external app ressource
      if ( !empty($app->external) ) {
        $external = preg_replace('/<([\w]+).*src="([^"]+)"[^>]*>(<\/[\w]+>)*/im', '<$1 type="text/plain" data-src="$2" data-name="' . $name . '">$3', $app->external);

        if ( $external != $app->external ) $doc->addCustomTag( $external ); // add app if there have been repacements
      }
    }

    $apps = json_encode($apps);
    unset($this->settings['klaro-apps']); /* do not use in window.klaroConfig */


    /* CUSTOM STYLES */
    $style = '';

    if ( !empty($this->settings['styles']['backgroundcolor']) ) $style .= '.klaro .cookie-modal .cm-modal, .klaro .cookie-notice {background-color:' . $this->settings['styles']['backgroundcolor'] . ' !important;}';
    if ( !empty($this->settings['styles']['linkcolor']) ) $style .= '.klaro .cookie-modal a, .klaro .cookie-notice a {color:' . $this->settings['styles']['linkcolor'] . ' !important;}';
    if ( !empty($this->settings['styles']['acceptcolor']) ) $style .= '.klaro .cookie-modal .cm-btn.cm-btn-success, .klaro .cookie-notice .cm-btn.cm-btn-success {background-color:' . $this->settings['styles']['acceptcolor'] . ' !important;}';
    if ( !empty($this->settings['styles']['savecolor']) ) $style .= '.klaro .cookie-modal .cm-btn.cm-btn-info, .klaro .cookie-notice .cm-btn.cm-btn-info {background-color:' . $this->settings['styles']['savecolor'] . ' !important;}';
    if ( !empty($this->settings['styles']['declinecolor']) ) $style .= '.klaro .cookie-modal .cm-btn.cm-btn-right, .klaro .cookie-notice .cm-btn.cm-btn-right {background-color:' . $this->settings['styles']['declinecolor'] . ' !important;}';
    if ( !empty($this->settings['styles']['togglecolor']) ) $style .= '.klaro .cookie-modal .cm-app-input:checked + .cm-app-label .slider, .klaro .cookie-notice .cm-app-input:checked + .cm-app-label .slider, .klaro .cookie-modal .cm-app-input.required:checked + .cm-app-label .slider, .klaro .cookie-notice .cm-app-input.required:checked + .cm-app-label .slider {background-color:' . $this->settings['styles']['togglecolor'] . ' !important;}';
    if ( !empty($this->settings['styles']['css']) ) $style .= $this->settings['styles']['css'];

    $doc->addStyleDeclaration( $style );
    unset($this->settings['styles']); /* do not use in window.klaroConfig */


    /* PREPARE TRANSLATIONS */
    $translations = $this->getTranslations( $langtag, $purposes, $descriptions );
    $translations = json_encode($translations);


    /* PREPARE CONFIG */
    $this->settings['lang'] = $langtag;

    // localized privacy policy link
    $this->settings['privacyPolicy'] = JRoute::_("index.php?Itemid={$this->settings['privacyPolicy']}");
    if ( JLanguageMultilang::isEnabled() ) {
      $associations = MenusHelper::getAssociations($this->settings['privacyPolicy']);
      if ( array_key_exists($langtag, $associations) ) $this->settings['privacyPolicy'] = JRoute::_("index.php?Itemid={$associations[$langtag]}");
    }

    // remove empty config values
    foreach ( $this->settings as $key => $value ) {
      if ( empty($value) ) unset( $this->settings[$key] );
    }


    /* CONFIG */
    $script = 'window.klaroConfig =  {';
    foreach ( $this->settings as $key => $value ) {
      switch ( true ) {
        case $value === 1:
          $script .= $key .' : true, ';
          break;
        case $value === 2:
          $script .= $key .' : false, ';
          break;
        case is_string($value):
          $script .= $key .' : "' . $value . '", ';
          break;
        default:
          $script .= $key .' : ' . $value . ', ';
          break;
      }
    }
    
    $script .= 'apps : ' . $apps . ',';

    $script .= 'translations : ' . $translations . ',';

    $script .= '};';

    $doc->addScriptDeclaration( $script );

    /* SCRIPT */
    $doc->addScript( JURI::base(true).'/media/plg_system_pbklaro/js/klaro.js', array('version' => self::KLARO), array('defer' => true) );
  }

  /**
   * This is the first stage in preparing content for output and is the most common point for content orientated plugins to do their work.
   *
   * @param   string   $context  The context of the content being passed to the plugin.
   * @param   object   &$row     The article object.  Note $article->text is also available
   * @param   mixed    &$params  The article params
   * @param   integer  $page     The 'page' number
   *
   * @return  void.
   *
   * @since   1.0
   */
  public function onContentPrepare($context, &$row, &$params, $page = 0)
  {
    /* fast fail */
    if ( $this->app->getName() != 'site' || strstr($row->text, '{plg_system_pbklaro_manager}') === false ) {
      return true;
    }

    /* load language from the backend */
    $lang = JFactory::getLanguage();
    $lang->load('plg_'.$this->_type.'_'.$this->_name, JPATH_ADMINISTRATOR);

    $insert = '<a href="#" onclick="klaro.show(); return false;">'.JText::_('PLG_SYSTEM_PBKLARO_SHOW_MANAGER_LINK').'</a>';
    $regex = '/{plg_system_pbklaro_manager}/im';
    
    $row->text = preg_replace($regex, $insert, $row->text);
  }

  /**
   * This function builds the translation array
   *
   * @param   string   $langtag       The language shorttag
   * @param   array    $purposes      Array of purpose translations
   * @param   array    $descriptions  Array of app descriptions
   *
   * @return  array
   *
   * @since   1.0
   */

  private function getTranslations( $langtag = 'en', $purposes = [], $descriptions = [] )
  {
    $return = array(
      $langtag => array(
        'purposes' => $purposes,
        'consentModal' => array (
          'title' => JText::_('PLG_SYSTEM_PBKLARO_TRANSLATION_MODAL_TITLE'),
          'description' => JText::_('PLG_SYSTEM_PBKLARO_TRANSLATION_MODAL_DESCRIPTION'),
          'privacyPolicy' => array (
                               'name' => JText::_('PLG_SYSTEM_PBKLARO_TRANSLATION_MODAL_PRIVACYPOLICY_NAME'),
                               'text' => JText::_('PLG_SYSTEM_PBKLARO_TRANSLATION_MODAL_PRIVACYPOLICY_TEXT')
                             )
        ),
        'consentNotice' => array (
          'changeDescription' => JText::_('PLG_SYSTEM_PBKLARO_TRANSLATION_NOTICE_CHANGEDESCRIPTION'),
          'description' => JText::_('PLG_SYSTEM_PBKLARO_TRANSLATION_NOTICE_DESCRIPTION'),
          'extraHTML' => JText::_('PLG_SYSTEM_PBKLARO_TRANSLATION_NOTICE_EXTRAHTML'),
          'learnMore' => JText::_('PLG_SYSTEM_PBKLARO_TRANSLATION_NOTICE_LEARNMORE'),
          'privacyPolicy' => array ( 'name' => JText::_('PLG_SYSTEM_PBKLARO_TRANSLATION_NOTICE_PRIVACYPOLICY_NAME') ),
          'imprint' => array (  'name' => JText::_('PLG_SYSTEM_PBKLARO_TRANSLATION_NOICE_IMPRINT_NAME') ),
        ),
        'ok' => JText::_('PLG_SYSTEM_PBKLARO_TRANSLATION_OK'),
        'save' => JText::_('PLG_SYSTEM_PBKLARO_TRANSLATION_SAVE'),
        'decline' => JText::_('PLG_SYSTEM_PBKLARO_TRANSLATION_DECLINE'),
        'close' => JText::_('PLG_SYSTEM_PBKLARO_TRANSLATION_CLOSE'),
        'acceptAll' => JText::_('PLG_SYSTEM_PBKLARO_TRANSLATION_ACCEPTALL'),
        'acceptSelected' => JText::_('PLG_SYSTEM_PBKLARO_TRANSLATION_ACCEPTSELECTED'),
        'app' => array (
          'disableAll' => array (
                            'title' => JText::_('PLG_SYSTEM_PBKLARO_TRANSLATION_APP_DISABLEALL_TITLE'),
                            'description' => JText::_('PLG_SYSTEM_PBKLARO_TRANSLATION_APP_DISABLEALL_DESCRIPTION')
                          ),
          'optOut' => array (
                        'title' => JText::_('PLG_SYSTEM_PBKLARO_TRANSLATION_APP_OPTOUT_TITLE'),
                        'description' => JText::_('PLG_SYSTEM_PBKLARO_TRANSLATION_APP_OPTOUT_DESCRIPTION')
                      ),
          'required' => array (
                          'title' => JText::_('PLG_SYSTEM_PBKLARO_TRANSLATION_APP_REQUIRED_TITLE'),
                          'description' => JText::_('PLG_SYSTEM_PBKLARO_TRANSLATION_APP_REQUIRED_DESCRIPTION')
                        ),
          'purposes' => JText::_('PLG_SYSTEM_PBKLARO_TRANSLATION_APP_PURPOSES'),
          'purpose' => JText::_('PLG_SYSTEM_PBKLARO_TRANSLATION_APP_PURPOSE')
        ),
        'poweredBy' => JText::_('PLG_SYSTEM_PBKLARO_TRANSLATION_POWEREDBY')
      )
    );

    $return[$langtag] = array_merge($return[$langtag], $descriptions);

    return $return;
  }
}
