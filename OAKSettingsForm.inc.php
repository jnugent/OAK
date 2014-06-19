<?php

/**
 * @file plugins/generic/oak/OAKSettingsForm.inc.php
 *
 * Copyright (c) 2013 Simon Fraser University Library
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OAKSettingsForm
 * @ingroup plugins_generic_oak
 *
 * @brief Form for journal managers to modify OAK plugin settings
 */


import('lib.pkp.classes.form.Form');

class OAKSettingsForm extends Form {

	/** @var $journalId int */
	var $journalId;

	/** @var $plugin object */
	var $plugin;

	/**
	 * Constructor
	 * @param $plugin object
	 * @param $journalId int
	 */
	function OAKSettingsForm(&$plugin, $journalId) {
		$this->journalId = $journalId;
		$this->plugin =& $plugin;

		parent::Form($plugin->getTemplatePath() . 'templates/settingsForm.tpl');

		$this->addCheck(new FormValidator($this, 'publisherId', 'required', 'plugins.generic.oak.manager.settings.publisherIdRequired'));
		$this->addCheck(new FormValidator($this, 'password', 'required', 'plugins.generic.oak.manager.settings.passwordRequired'));
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$journalId = $this->journalId;
		$plugin =& $this->plugin;

		$this->_data = array(
			'publisherId' => $plugin->getSetting($journalId, 'publisherId'),
			'password' => $plugin->getSetting($journalId, 'password')
		);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('publisherId', 'password'));
	}

	/**
	 * Save settings.
	 */
	function execute() {
		$plugin =& $this->plugin;
		$journalId = $this->journalId;

		$plugin->updateSetting($journalId, 'publisherId', trim($this->getData('publisherId'), "\"\';"), 'string');
		$plugin->updateSetting($journalId, 'password', trim($this->getData('password'), "\"\';"), 'string');
	}
}

?>
