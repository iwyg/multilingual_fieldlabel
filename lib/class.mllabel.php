<?php
/* vim: et:ts=4:sw=4:sts=4 */

/**
 * @package Lib
 * @author thomas appel <mail@thomas-appel.com>

 * Displays <a href="http://opensource.org/licenses/gpl-3.0.html">GNU Public License</a>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */
class MlLabel
{

	/**
	 * writeConf
	 *
	 * @param mixed $locales
	 * @static
	 * @access public
	 * @return void
	 */
	public static function writeConf($locales = null)
	{
		$locales = is_array($locales) ? $locales : self::getAdditionalLanguages();
		Symphony::Configuration()->set('sys_lang', Symphony::Configuration()->get('lang', 'symphony'), 'multilingual_fieldlabel');
		Symphony::Configuration()->set('additional_lang', implode(',', $locales), 'multilingual_fieldlabel');
		Symphony::Configuration()->write();
	}

	/**
	 * getConfig
	 *
	 * @static
	 * @access public
	 * @return void
	 */
	public static function getConfig()
	{
		$lstring = Symphony::Configuration()->get('additional_lang', 'multilingual_fieldlabel');
		return  strlen($lstring) > 0 ? explode(',', $lstring) : array();
	}

	/**
	 * alterFieldsTable
	 *
	 * @param Array $locales
	 * @param mixed $type
	 * @static
	 * @access public
	 * @return void
	 */
	public static function alterFieldsTable(Array $locales, $type)
	{
		$alter = self::createQueryString($locales, $type);
		return Symphony::Database()->query("ALTER TABLE `tbl_fields` " . $alter);
	}

	/**
	 * createQueryString
	 *
	 * @param Array $locales
	 * @param mixed $type
	 * @static
	 * @access public
	 * @return void
	 */
	public static function createQueryString(Array $locales, $type)
	{
		$columns = '';
		switch ($type) {
		case 'add':
			foreach ($locales as $locale) {
				$columns .= "ADD COLUMN `label-" . $locale . "` varchar(255) NOT NULL,";
			}
			break;
		case 'drop':
			foreach ($locales as $locale) {
				$columns .= "DROP COLUMN `label-" . $locale . "`,";
			}
			break;
		}
		return substr($columns,0, -1);
	}


	/**
	 * getAdditionalLanguages
	 *
	 * @static
	 * @access public
	 * @return void
	 */
	public static function getAdditionalLanguages()
	{
		$langs = array_keys(Lang::getAvailableLanguages());
		$sys_lang = Symphony::Configuration()->get('lang', 'symphony');
		return array_values(array_diff($langs, array($sys_lang)));
	}

	/**
	 * updateFieldsTable
	 *
	 * @static
	 * @access public
	 * @return void
	 */
	public static function updateFieldsTable()
	{
		$sys_lang = Symphony::Configuration()->get('lang', 'symphony');
		$old_sys_lang = Symphony::Configuration()->get('sys_lang', 'multilingual_fieldlabel');
		$sys_lang_changed = $sys_lang != $old_sys_lang;
		$alter_config = false;

		if ($sys_lang_changed) {
			try {
				self::alterFieldsTable(array($sys_lang), 'drop');
			} catch(Exception $e) {}
			self::alterFieldsTable(array($old_sys_lang), 'add');
			$alter_config = true;
		}

		$langs = MlLabel::getConfig();
		$nlangs = self::getAdditionalLanguages();
		$adds = array_diff($nlangs, $langs);
		$drops = array_diff($langs, $nlangs);

		if (!empty($adds)) {
			$add = MlLabel::alterFieldsTable($adds, 'add');
			$alter_config = true;
		}

		if (!empty($drops)) {
			$add = MlLabel::alterFieldsTable($drops, 'drop');
			$alter_config = true;
		}

		if ($alter_config) {
			self::writeConf($nlangs);
		}
	}

	/**
	 * getFieldSchema
	 *
	 * @param mixed $callback
	 * @param mixed $context
	 * @static
	 * @access public
	 * @return void
	 */
	public static function getFieldSchema($section_id)
	{

		if (!$section_id) {
			return false;
		}

		$langs = MlLabel::getAdditionalLanguages();
		$schema_json = array();
		$schema = FieldManager::fetchFieldsSchema($section_id);

		foreach ($schema as $fieldArray) {
			$lang_labels = array();
			$field = FieldManager::fetch($fieldArray['id']);
			foreach ($langs as $locale) {
				$lang_labels['label-' . $locale] = $field->get('label-' . $locale);
			}

			$schema_json[$field->get('label')] = array(
				'element_name' => $field->get('element_name'),
				'id' => $field->get('id'),
				'labels' => $lang_labels
			);
		}
		return General::sanitize(json_encode($schema_json));
	}

	public static function getFieldSchemaFromErrors(Array $postFields)
	{
		$langs = MlLabel::getAdditionalLanguages();
		$schema_json = array();

		foreach ($postFields as $field) {
			$lang_labels = array();

			foreach ($langs as $locale) {
				$lang_labels['label-' . $locale] = $field['label-' . $locale];
			}

			$schema_json[$field['label']] = array(
				'element_name' => $field['element_name'],
				'id' => $field['id'],
				'labels' => $lang_labels
			);
		}
		return General::sanitize(json_encode($schema_json));
	}

	public static function postPopulateFields(Field $field)
	{
		$fields = array();
		$field_id = $field->get('id');

		foreach (self::getAdditionalLanguages() as $locale) {
			$fields['label-' . $locale] = General::sanitize($field->get('label-' . $locale));
		}
		FieldManager::edit($field_id, $fields);
	}


	/**
	 * preparePublishContents
	 *
	 * @param mixed $callback
	 * @param mixed $context
	 * @static
	 * @access public
	 * @return {Boolean}
	 */
	public static function preparePublishContents(&$callback, &$context)
	{
		$author_lang = Symphony::Engine()->Author->get('language');
		$sys_lang = Symphony::Configuration()->get('lang', 'symphony');

		if ($author_lang != $sys_lang && !is_null($author_lang)) {

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
			return true;
		}
		return false;
	}

	/**
	 * prepareSettingsContents
	 *
	 * @param mixed $callback
	 * @param mixed $context
	 * @static
	 * @access public
	 * @return void
	 */
	public static function prepareSettingsContents(&$context)
	{
		if (is_array($context['errors']) && !empty($context['errors'])) {
			$flabels = self::getFieldSchemaFromErrors($_POST['fields']);
		} else {
			$section_id = SectionManager::fetchIDFromHandle($context['meta']['name']);
			$flabels = self::getFieldSchema($section_id);
		}
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

		$context['form']->appendChild($values);
		$context['form']->appendChild($labels);
	}

}
