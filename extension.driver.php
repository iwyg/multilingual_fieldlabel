<?php
/*
vim: et:ts=4:sw=4:sts=4
*/

/**
 * @package multilingual_fieldlabel
 * @author thomas appel <mail@thomas-appel.com>

 * Displays <a href="http://opensource.org/licenses/gpl-3.0.html">GNU Public License</a>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */
require_once EXTENSIONS . '/multilingual_fieldlabel/lib/class.mllabel.php';

class extension_multilingual_fieldlabel extends Extension
{

    /**
     * @see toolkit.Extension#__construct
     */
    public function __construct()
    {
        parent::__construct();
        $this->_syslang = Symphony::Configuration()->get('lang', 'symphony');
    }

    /**
     * @see toolkit.Extension#install
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
     * @see toolkit.Extension#uninstall
     */
    public function uninstall()
    {
        Symphony::Configuration()->remove('multilingual_fieldlabel');

        $addlangs = MlLabel::getAdditionalLanguages();
        if (!empty($addlangs)) {
            return MlLabel::alterFieldsTable($addlangs, 'drop');
        }
        return true;
    }

    /**
     * @see toolkit.Extension#getSubscribedDelegates
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
    /**
     * __appendLabels
     *
     * adds nessesary assetss for a sctions settings page.
     *
     * @param mixed $context see delegates <AddSectionElements>
     * @access public
     * @return void
     */
    public function __appendLabels($context)
    {
        $engine = Symphony::Engine();
        MlLabel::prepareSettingsContents($context);
        $engine->Page->addStylesheetToHead(URL . '/extensions/multilingual_fieldlabel/assets/mllabel.tabs.css', 'screen', 111, false);
        $engine->Page->addScriptToHead(URL . '/extensions/multilingual_fieldlabel/assets/mllabel.tabs.js', 112, false);
        $engine->Page->addScriptToHead(URL . '/extensions/multilingual_fieldlabel/assets/mllabel.settings.js', 113, false);
    }
    /**
     * @see lib/MlLabel#postPopulateFields()
     */
    public function populateMlLabels(&$context)
    {
        return MlLabel::postPopulateFields($context['field']);
    }

    public function __updateFieldsTable(&$context, $driver = null)
    {
       $driver = is_null($driver) ? 'systempreferences' : $driver;
       MlLabel::updateFieldsTable($driver);
    }


    /**
     * Dump name I know.
     * Test weather page is a publish view or a sections settings view.
     * Appends assets to that pages.
     *
     * @param mixed $context see delegates <AdminPagePreGenerate>
     * @access public
     * @return void
     */
    public function __testAddSysLang($context)
    {
        $engine = Symphony::Engine();

        if (!$engine->Author) {
            return false;
        }

        $callback = $engine->getPageCallback();
        $driver = $callback['driver'];
        switch ($driver) {
            case 'publish':
                if (MlLabel::preparePublishContents($callback, $context)) {
                    // append publish script.
                    $engine->Page->addScriptToHead(URL . '/extensions/multilingual_fieldlabel/assets/mllabel.publish.js', 999, false);
                }
                break;
            case 'systempreferences':
                if (is_array($callback['context']) && in_array('success', $callback['context'])) {
                   MlLabel::updateFieldsTable($driver);
                }
                break;
            case 'systemextensions':
                   MlLabel::updateFieldsTable($driver);
                break;

            default:
                break;
        }
    }
}
