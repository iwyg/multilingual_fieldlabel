<?php
/**
 * MlLabel
 *
 * @package Lib
 * @release 1
 * @copyright 1997-2005 The PHP Group
 * @author Tobias Schlitt <toby@php.net>
 * @license PHP Version 3.0 {@link http://www.php.net/license/3_0.txt}
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

}
