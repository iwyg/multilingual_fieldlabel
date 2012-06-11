<?php
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
        );
    }

	/**
	 * populateMlLabels
	 *
	 * @param mixed $context
	 * @access public
	 * @return void
	 */
	public function populateMlLabels(&$context)
	{
		$fields = array();
		$field_id = $context['field']->get('id');

		foreach (MlLabel::getAdditionalLanguages() as $locale) {
			$fields['label-' . $locale] = General::sanitize($context['field']->get('label-' . $locale));
		}
		FieldManager::edit($field_id, $fields);
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
		if ($callback['driver'] == 'blueprintssections' && (!empty($callback['context']) && ($callback['context'][0] == 'edit' || $callback['context'][0] == 'new'))) {
			// prepare section:
			$flabels = MlLabel::getFieldSchema($callback, $context);
			$settings = Symphony::Configuration()->get('multilingual_fieldlabel');
			$settings['additional_lang'] = explode(',', $settings['additional_lang']);

			$values = Widget::input('mllabel-settings', null, 'hidden', array(
				'id' => 'mllabel-settings',
				'readonly' => 'readonly',
				'data-settings' => General::sanitize(json_encode($settings))
			));
			$labels = Widget::input('mllabel-labels', null, 'hidden', array(
				'id' => 'mllabel-labels',
				'readonly' => 'readonly',
				'data-values' => $flabels
				)
			);

			$context['oPage']->Form->appendChild($values);
			$context['oPage']->Form->appendChild($labels);

			Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/multilingual_fieldlabel/assets/mllabel.tabs.css', 'screen', 111, false);
			Administration::instance()->Page->addScriptToHead(URL . '/extensions/multilingual_fieldlabel/assets/mllabel.tabs.js', 112, false);
			Administration::instance()->Page->addScriptToHead(URL . '/extensions/multilingual_fieldlabel/assets/mllabel.settings.js', 113, false);

		} else if ($callback['driver'] == 'publish' && $callback['context']['page'] != 'index') {
			// append publish script.
			$author_lang = Symphony::Engine()->Author->get('language');
			$sys_lang = Symphony::Configuration()->get('lang', 'symphony');

			if ($author_lang != $sys_lang && !is_null($author_lang)) {

				Administration::instance()->Page->addScriptToHead(URL . '/extensions/multilingual_fieldlabel/assets/mllabel.publish.js', 111, false);

				$labels = array();
				$section_handle = $callback['context']['section_handle'];
				$section_id = SectionManager::fetchIDFromHandle($section_handle);
				$field_schema = FieldManager::fetchFieldsSchema($section_id);

				foreach ($field_schema as $f) {
					$field = FieldManager::fetch($f['id']);
					$label = $field->get('label-' . $author_lang);
					$labels['field-' . $field->get('id')] = $label;
				}

				$labelValues = Widget::input('mllabel-labels', null, 'hidden', array(
					'id' => 'mllabel-labels',
					'data-labels' => General::sanitize(json_encode($labels))
				));

				$context['oPage']->Form->appendChild($labelValues);
			}
		} else if ($callback['driver'] == 'systemextensions' || $context['driver'] == 'systempreferences') {
			// if page is systempreferences or extensions, test for system language
			// or additional language changes.
			MlLabel::updateFieldsTable();
		}
	}
}
