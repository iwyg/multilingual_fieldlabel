<?php
/* vim: et:ts=4:sw=4:sts=4 */

/**
 * @package multilingual_fieldlabel
 * @author thomas appel <mail@thomas-appel.com>

 * Displays <a href="http://opensource.org/licenses/gpl-3.0.html">GNU Public License</a>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */
require_once EXTENSIONS . '/multilingual_fieldlabel/lib/class.mllabel.php';
require_once EXTENSIONS . '/firebug_profiler/lib/FirePHPCore/fb.php';

class extension_multilingual_fieldlabel extends Extension
{

	/**
	 * __construct
	 *
	 * @access public
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
		$this->_syslang = Symphony::Configuration()->get('lang', 'symphony');
	}

	/**
	 * install
	 *
	 * @access public
	 * @return void
	 */
	public function install()
	{
		$addlangs = MlLabel::getAdditionalLanguages();
		$avLangs = array_keys(Lang::getAvailableLanguages());
		MlLabel::writeConf($addlangs);
		if (!empty($addlangs)) {
			MlLabel::alterFieldsTable($addlangs, 'add');
		}
		return true;
	}


	/**
	 * uninstall
	 *
	 * @access public
	 * @return void
	 */
	public function uninstall()
	{
		$addlangs = MlLabel::getAdditionalLanguages();
		if (!empty($addlangs)) {
			return MlLabel::alterFieldsTable($addlangs, 'drop');
		}
		return true;
	}

    /**
     * getSubscribedDelegates
     *
     * @access public
     * @return void
     */
    public function getSubscribedDelegates()
    {
        return array(
            array(
                'page' => '/blueprints/sections/',
                'delegate' => 'FieldPostCreate',
                'callback' => 'populateMlLabels'
            ),
            array(
                'page' => '/blueprints/sections/',
                'delegate' => 'FieldPostEdit',
                'callback' => 'populateMlLabels'
            ),
            array(
                'page' => '/backend/',
                'delegate' => 'AdminPagePreGenerate',
                'callback' => '__testAddSysLang'
			),
            array(
                'page' => '/blueprints/sections/',
                'delegate' => 'AddSectionElements',
                'callback' => '__appendLabels'
			)
        );
    }
	public function __appendLabels($context)
	{
		MlLabel::prepareSettingsContents($context);
		Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/multilingual_fieldlabel/assets/mllabel.tabs.css', 'screen', 111, false);
		Administration::instance()->Page->addScriptToHead(URL . '/extensions/multilingual_fieldlabel/assets/mllabel.tabs.js', 112, false);
		Administration::instance()->Page->addScriptToHead(URL . '/extensions/multilingual_fieldlabel/assets/mllabel.settings.js', 113, false);
	}
	/**
	 * @see lib/MlLabel#postPopulateFields()
	 */
	public function populateMlLabels(&$context)
	{
		return MlLabel::postPopulateFields($context['field']);
	}

	/**
	 * __testAddSysLang
	 *
	 * @access public
	 * @return void
	 */
	public function __testAddSysLang($context)
	{
        $callback = Symphony::Engine()->getPageCallback();
		/*
		if ($callback['driver'] == 'blueprintssections' && (!empty($callback['context']) && ($callback['context'][0] == 'edit' || $callback['context'][0] == 'new'))) {
			prepare section:
		}
		*/

		if ($callback['driver'] == 'publish' && $callback['context']['page'] != 'index') {
			if (MlLabel::preparePublishContents($callback, $context)) {
				// append publish script.
				Administration::instance()->Page->addScriptToHead(URL . '/extensions/multilingual_fieldlabel/assets/mllabel.publish.js', 111, false);
			}
		} else if ($callback['driver'] == 'systemextensions' || $context['driver'] == 'systempreferences') {
			// if page is systempreferences or extensions, test for system language
			// or additional language changes.
			MlLabel::updateFieldsTable();
		}
	}
}
