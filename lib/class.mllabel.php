<?php
/*
vim: et:ts=4:sw=4:sts=4
*/

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
     * Write the extensions configuration array.
     *
     * @param mixed $locales can take an array of locale code strings like en,de,es
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
     * Reads the extends configuration array and returns an array of available
     * additional languages.
     *
     * @static
     * @access public
     * @return {Array} an Array of language strings of additional installed and
     * previously stored in the extensions configuration.
     * languages
     */
    public static function getConfig()
    {
        $lstring = Symphony::Configuration()->get('additional_lang', 'multilingual_fieldlabel');
        return  strlen($lstring) > 0 ? explode(',', $lstring) : array();
    }

    /**
     * Utilizes `createQueryString` to create the database query required
     * for altering symphonys' field table. Augments `tbl_fields` with `handle-<langcode>` rows.
     *
     * @see multilingual_fieldlabel.lib.MlLabel#createQueryString
     * @param {Array}  $locales  an array of language code strings
     * @param {String} $type     accepts `add` or `drop` weather you will add
     * or drop rows for the given laguage codes.
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
     * Create the database query string for altering Symphonys' `tbl_fields`
     * field.
     *
     * @param {Array}  $locales  an array of language code strings
     * @param {String} $type     accepts `add` or `drop` weather you will add
     * or drop rows for the given laguage codes.
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
     * Returns language codes of all installed system languages except for the
     * system defualt language.
     *
     * @static
     * @access public
     * @return {Array} Array of languagecode strings
     */
    public static function getAdditionalLanguages()
    {
        $langs = array_keys(Lang::getAvailableLanguages());
        $sys_lang = Symphony::Configuration()->get('lang', 'symphony');
        return array_values(array_diff($langs, array($sys_lang)));
    }

    /**
     * check if exception configuration and/or Symphonys' `tbl_fields` table
     * requires to be updated.
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
     * Returns an escaped JSON string containing all data required for populating dynamically added fieldlabels.
     * Used on Section creation or edit without error.
     *
     * @param {int} $section_id the section id
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

    /**
     * getFieldSchemaFromErrors
     * @see multilingual_fieldlabel.lib.MlLabel#getFieldSchema
     *
     * Does the same as `getFieldSchema` but is utilized if an error occurs while
     * saving an section. (this way newly added labels won't get lost while not
     * saved to the database).
     *
     * @param Array $postFields
     * @static
     * @access public
     * @return void
     */
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
     * Gets the current authors language and appends data required for
     * dynamilcally altering the displayed field lables on a publish page.
     *
     * @param {Array} $callback publish page callback array
     * @param mixed $context see delegates <AdminPagePreGenerate>
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
     * Appends data required for dynamilcally adding the displayed language tabs.
     *
     * @param mixed $context see delegates <AdminPagePreGenerate>
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
