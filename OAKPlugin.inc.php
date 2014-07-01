<?php

/**
 * @file plugins/generic/oak/OAKPlugin.inc.php
 *
 * Copyright (c) 2013 Simon Fraser University Library
 * Copyright (c) 2000-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OAK
 * @ingroup plugins_generic_oak
 *
 * @brief OAK plugin class
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class OAKPlugin extends GenericPlugin {

	/**
	 * URL for oak server
	 * @var string
	 */
	var $_host = 'https://www.openaccesskey.com/publisherservice/oakpublisherservice.asmx?WSDL';

	/**
	 * Publisher account for oak server
	 * @var string
	 */
	var $_publisherId;

	/**
	 * Password for oak server
	 * @var string
	 */
	var $_password;

	/**
	 * Secure ID for user on oak server
	 * @var string
	 */
	var $_sid;

	/**
	 * OAK ID for primary author
	 * @var string
	 */
	var $_oakId;

	/**
	 * Set the OAK ID for the primary author
	 * @param $oakId string
	 */
	function setOakId($oakId) {
		$this->_oakId = $oakId;
	}

	/**
	 * Get the OAK ID for the primary author
	 * @return $oakId string
	 */
	function getOakId() {
		return $this->_oakId;
	}

	/**
	 * Get the host
	 * @return string
	 */
	function getHost() {
		return $this->_host;
	}

	/**
	 * Set the publisherId
	 * @param $publisherId string
	 */
	function setPublisherId($publisherId) {
		$this->_publisherId = $publisherId;
	}

	/**
	 * Get the publisherId
	 * @return string
	 */
	function getPublisherId() {
		return $this->_publisherId;
	}

	/**
	 * Set the password
	 * @param $password string
	 */
	function setPassword($password) {
		$this->_password = $password;
	}

	/**
	 * Get the password
	 * @return string
	 */
	function getPassword() {
		return $this->_password;
	}

	/**
	 * Set the SID
	 * @param $sid string
	 */
	function setSid($sid) {
		$this->_sid = $sid;
	}

	/**
	 * Get the SID
	 * @return string
	 */
	function getSid() {
		return $this->_sid;
	}

	/**
	 * Set the journalId
	 * @param $journalId string
	 */
	function setJournalId($journalId) {
		$this->_journalId = $journalId;
	}

	/**
	 * Get the journalId
	 * @return string
	 */
	function getJournalId() {
		return $this->_journalId;
	}


	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		$this->addLocaleData();

		$journal =& Request::getJournal();

		// Set access values
		if ($journal) {
			$this->setPublisherId($this->getSetting($journal->getId(), 'publisherId'));
			$this->setPassword($this->getSetting($journal->getId(), 'password'));

			// Check if we're submitting the paper to OAK, otherwise just register the callback
			$submissionId = Request::getUserVar('submissionId');
			if(isset($submissionId)) {
				$price = Request::getUserVar('price');
				$authorOAKId = Request::getUserVar('authorOAKId');
				$currencyCode = Request::getUserVar('currencyCode');
				$discount = Request::getUserVar('discount');

				$this->submitArticle($submissionId, $price, $authorOAKId, $currencyCode, $discount);
			}
		}

		if ($success && $this->getEnabled()) {
			HookRegistry::register('TemplateManager::include', array($this, 'insertOak'));
		}
		return $success;
	}

	/**
	 * Return the display name for this plugin.
	 * @return string
	 */
	function getDisplayName() {
		return __('plugins.generic.oak.displayName');
	}

	/**
	 * Return the description for this plugin.
	 * @return string
	 */
	function getDescription() {
		return __('plugins.generic.oak.description');
	}

	/**
	 * Display verbs for the management interface.
	 */
	function getManagementVerbs() {
		$verbs = array();
		if ($this->getEnabled()) {
			$verbs[] = array('settings', __('plugins.generic.oak.manageSettings'));
		}
		return parent::getManagementVerbs($verbs);
	}

	/**
	 * Callback to insert the OAK interface into the editing page
	 * @param string $hookName
	 * @param array $params
	 * @return boolean
	 */
	function insertOak($hookName, $params) {
		if ($this->getEnabled()) {
			$smarty =& $params[0];
			$template =& $params[1]['smarty_include_tpl_file'];

			if ($template == 'sectionEditor/submission/proofread.tpl') {
				$submission =& $smarty->get_template_vars('submission'); /* @var $submission Article */
				$smarty->assign_by_ref('submission', $submission);

				// Get the primary author
				$authors =& $submission->getAuthors();
				foreach ($authors as $author) {
					if ($author->getPrimaryContact()) {
						$primaryAuthor =& $author;
					}
				}

				if ($primaryAuthor) {
					// Look up the author in OAK if possible
					$authorExists = $this->_lookupAuthor($primaryAuthor->getEmail());

					// If the author is not found create an author
					if(!$authorExists) {
						$this->_addAuthor($primaryAuthor);
						$this->_lookupAuthor($primaryAuthor->getEmail());
					}

					// Make sure the submission is published, or else we can't submit to OAK
					$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
					$publishedArticle =& $publishedArticleDao->getPublishedArticleByArticleId($submission->getId());
					$smarty->assign_by_ref('publishedArticle', $publishedArticle);

					$smarty->assign('oakId', $this->getOakId());
					$smarty->assign('submissionId', $submission->getId());
					$smarty->assign('submittedToOak', $submission->getData('submittedToOak'));
					$smarty->assign('authorEmail', $primaryAuthor->getEmail());
					$smarty->assign('authorFullName', $primaryAuthor->getFullName());
					$smarty->assign('pluginUrl', $this->smartyPluginUrl(array('path' => 'submitArticle'), $smarty));

					echo $smarty->fetch($this->getTemplatePath() . '/templates/oak.tpl');
				}
			}
			return false;
		}
	}


	/**
	 * Execute a management verb on this plugin
	 * @param $verb string
	 * @param $args array
	 * @param $message string Result status message
	 * @param $messageParams array Parameters for the message key
	 * @return boolean
	 */
	function manage($verb, $args, &$message, &$messageParams) {

		if (!parent::manage($verb, $args, $message, $messageParams)) return false;

		switch ($verb) {
			case 'settings':
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));
				$journal =& Request::getJournal();

				$this->import('OAKSettingsForm');
				$form = new OAKSettingsForm($this, $journal->getId());
				if (Request::getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						Request::redirect(null, 'manager', 'plugin');
						return false;
					} else {
						$this->setBreadCrumbs(true);
						$form->display();
					}
				} else {
					$this->setBreadCrumbs(true);
					$form->initData();
					$form->display();
				}
				return true;
			default:
				// Unknown management verb
				assert(false);
			return false;
		}
	}

	/**
	 * Submit an article to OAK.
	 * @param int $submissionId
	 * @param String $price
	 * @param int $authorOAKId
	 * @param String $currencyCode
	 * @param String $discount
	 */
	function submitArticle($submissionId, $price, $authorOAKId, $currencyCode = 'USD', $discount = "0") {
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$article =& $publishedArticleDao->getPublishedArticleByArticleId($submissionId);
		$isPublished = false;
		if ($article) {
			// Get the issue
			$issueDao =& DAORegistry::getDAO('IssueDAO');
			$issue =& $issueDao->getIssueById($article->getIssueId(), $article->getJournalId(), true);
			$isPublished = true;
		} else { // Article is not published yet.
			$articleDao =& DAORegistry::getDAO('ArticleDAO');
			$article =& $articleDao->getArticle($submissionId);
		}

		$articleLink = Request::url($article->getJournalId(), 'article', 'view', $submissionId);

		// Set the journal ID for use below
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$journal = $journalDao->getJournal($article->getJournalId());
		$journalName = $journal->getLocalizedTitle();
		$this->setJournalId($this->_getJournalId($journalName));

		$params = array(
			'SubmitArticleExtended2' => array(
				'DOI'                  => $article->getPubId('doi'),
				'PublisherId'          => $this->getPublisherId(),
				'PublisherReferenceNo' => $article->getId(),
				'Password'             => $this->getPassword(),
				'ArticleTitle'         => $article->getLocalizedTitle(),
				'JournalId'            => $this->getJournalId(),
				'JournalVolume'        => isset($issue) ? $issue->getVolume() : 'na',
				'JournalIssue'         => isset($issue) ? $issue->getNumber() : 'na',
				'CorrespondingAuthor'  => $authorOAKId,
				'Currency'             => $currencyCode,
				'Price'                => $price,
				'DiscountPercentage'   => $discount,
				'Publishingdate'       => $article->getDatePublished(),
				'PermanentLink'        => $articleLink,
				'Abstract'             => $article->getLocalizedAbstract(),
				'OverrideVAT'          => 0,
				'KeyWords'             => ''
			)
		);

		$response = $this->_makeRequest('SubmitArticleExtended2', $params);

		$sReturnValue = $response->SubmitArticleResult;

		if ($sReturnValue == 'OK') {
			$articleDao =& DAORegistry::getDAO('ArticleDAO');
			$articleDao->updateSetting($article->getId(), 'submittedToOak', Core::getCurrentDate(), 'date', false);
		} else {
			error_log('OAK error: '.$sReturnValue);
		}

		Request::redirect(null, 'editor', 'submissionEditing', $submissionId);
	}

	/**
	 * Set the page's breadcrumbs, given the plugin's tree of items
	 * to append.
	 * @param $subclass boolean
	 */
	function setBreadcrumbs($isSubclass = false) {
		$templateMgr =& TemplateManager::getManager();
		$pageCrumbs = array(
			array(
				Request::url(null, 'user'),
				'navigation.user'
			),
			array(
				Request::url(null, 'manager'),
				'user.role.manager'
			)
		);
		if ($isSubclass) $pageCrumbs[] = array(
			Request::url(null, 'manager', 'plugins'),
			'manager.plugins'
		);

		$templateMgr->assign('pageHierarchy', $pageCrumbs);
	}

	/**
	 * Search for an author by email.
	 * @param String $email
	 * @return true if found
	 */
	function _lookupAuthor($email) {
		$params = array(
			'FindAuthor' => array(
				'PublisherId' => $this->getPublisherId(),
				'Password' => $this->getPassword(),
				'SearchTerm' => $email
			)
		);

		$response = $this->_makeRequest('FindAuthor', $params);

		$oakId = null;
		$findAuthorResult = explode(chr(10), $response->FindAuthorResult);
		foreach($findAuthorResult AS $sAuthor) {
			if($sAuthor != "") {
				$sAuthor_arr = explode(";", $sAuthor);
				$nvcAuthors[] = array($sAuthor_arr[1], $sAuthor_arr[0]);
				$oakId = $sAuthor_arr[0];
			}
		}

		if(!$oakId) {
			return false;
		} else {
			$this->setOakId($oakId);
			return true;
		}
	}

	/**
	 * Create a new author
	 * @param String $author.
	 * @return int OAK ID
	 */
	function _addAuthor($author) {
		$params = array(
			'CreateAuthor' => array(
				'PublisherId' => $this->getPublisherId(),
				'Password' => $this->getPassword(),
				'FirstName' => $author->getFirstName(),
				'LastName' => $author->getLastName(),
				'Email' => $author->getEmail(),
				'University' => $author->getLocalizedAffiliation()
			)
		);
		$response = $this->_makeRequest('CreateAuthor', $params);

		$sReturnValue = $response->CreateAuthorResult;

		$oakId = null;
		if(substr($sReturnValue, 0, 3) == "OK|") {
			$oakId = str_replace("OK|", "", $sReturnValue);
	}

		if(!$oakId) {
			return false;
		} else {
			$this->setOakId($oakId);
			return true;
		}
	}

	/**
	 * Create a new journal
	 * @param $journalName string Full name of journal to match against OAK journal list
	 * @return int journal ID
	 */
	function _getJournalId($journalName) {
		$params = array(
			'GetJournals' => array(
				'PublisherId' => $this->getPublisherId(),
				'Password' => $this->getPassword()
			)
		);
		$response = $this->_makeRequest('GetJournals', $params);

		$sReturnValue = $response->GetJournalsResult;

		$journalId = null;
		foreach(explode(chr(10), $sReturnValue) AS $sJournal) {
			if($sJournal != "") {
				$sJournal = trim($sJournal);
				$sJournal_arr = explode(";", $sJournal);
				if($sJournal_arr[1] == $journalName) $journalId = $sJournal_arr[0];
			}
		}

		return $journalId;
	}

	/**
	 * Make a SOAP request using the SoapClient API.
	 * @param PKPRequest $request
	 * @param array $params
	 * @return mixed
	 */
	function _makeRequest($request, $params) {
		$options = array(
			'soap_version' => SOAP_1_2,
			'cache_wsdl'   => WSDL_CACHE_NONE,
			'exceptions'   => true,
			'trace'        => true
		);
		$client = new SoapClient($this->getHost(), $options);

		return $client->__soapCall($request, $params);
	}
}

?>
