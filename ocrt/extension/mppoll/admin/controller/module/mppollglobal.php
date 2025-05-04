<?php

namespace Opencart\Admin\Controller\Extension\Mppoll\Module;

class MppollGlobal extends \Opencart\System\Engine\Controller {

	const DEFAULT_STORE = 0;
	const USECOLOR = 0;
	const COOKIES_TIME_SEC = 60;
	const COOKIES_TIME_MIN = 60;
	const COOKIES_TIME_HOUR = 24;
	const COOKIES_TIME_DAY = 7;
	const COOKIES_TIME_SEP = ':';
	const USESUCCESSMSG = 0;
	const DISPAYRESULT = 2;

	private array $CHART = [
		'ADMIN' => [
			'W' => 770,
			'H' => 200
		],
		'CATALOG' => [
			'W' => 770,
			'H' => 200
		]
	];


	public function install(): void {

		// Do module install tasks

		// module files into permission access/modify
		$this->load->model('user/user_group');

		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/mppoll/mppoll');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/mppoll/mppoll');

		$this->load->model('setting/event');
		$code = 'mppoll';

		$trigger = 'admin/view/common/column_left/before';
		$action = 'extension/mppoll/mppoll|column_left';
		if (VERSION > '4.0.1.1') {
			$action = str_replace('|', '.', $action);
		}

		if (VERSION > '4.0.0.0') {
			$this->model_setting_event->addEvent(['code' => $code, 'trigger' => $trigger, 'action' => $action, 'description' => '', 'status' => 1, 'sort_order' => 1]);
		} else {
			$this->model_setting_event->addEvent($code, $trigger, $action);
		}


		$trigger = 'catalog/view/common/footer/after';
		$action = 'extension/mppoll/mppoll|footer';
		if (VERSION > '4.0.1.1') {
			$action = str_replace('|', '.', $action);
		}

		if (VERSION > '4.0.0.0') {
			$this->model_setting_event->addEvent(['code' => $code, 'trigger' => $trigger, 'action' => $action, 'description' => '', 'status' => 1, 'sort_order' => 1]);
		} else {
			$this->model_setting_event->addEvent($code, $trigger, $action);
		}

		/*
		 Table structure for table `oc_mppoll`
		*/
		$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."mppoll` (
		  `mppoll_id` int(11) NOT NULL AUTO_INCREMENT,
		  `status` int(1) NOT NULL,
		  `active` int(1) NOT NULL,
		  `maxanswers` int(11) NOT NULL,
		  `chartcolors` longtext COLLATE utf8_bin NOT NULL,
		  `successmsg` varchar(250) COLLATE utf8_bin NOT NULL,
		  `useglobalcolor` varchar(1) COLLATE utf8_bin NOT NULL,
		  `allowcoloroverride` varchar(1) COLLATE utf8_bin NOT NULL,
		  `useglobalsuccessmsg` varchar(1) COLLATE utf8_bin NOT NULL,
		  `allowsuccessmsgoverride` varchar(1) COLLATE utf8_bin NOT NULL,
		  `sort_order` int(11) NOT NULL,
		  `date_added` datetime NOT NULL,
		  `date_modified` datetime NOT NULL,
		  PRIMARY KEY (`mppoll_id`)
		) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;");
		/*
		 Table structure for table `oc_mppoll_description`
		*/
		$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."mppoll_description` (
		  `mppoll_id` int(11) NOT NULL,
		  `language_id` int(11) NOT NULL,
		  `question` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '',
		  `answers` mediumtext COLLATE utf8_bin NOT NULL,
		  PRIMARY KEY (`mppoll_id`,`language_id`),
		  KEY `question` (`question`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;");

		/*
		 Table structure for table `oc_mppoll_hasvotes`
		*/
		$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."mppoll_hasvotes` (
		  `mppoll_reaction_id` int(11) NOT NULL AUTO_INCREMENT,
		  `mppoll_id` int(11) NOT NULL,
		  `answer` int(11) NOT NULL,
		  `status` int(11) NOT NULL,
		  `ip` varchar(30) COLLATE utf8_bin NOT NULL,
		  `date_added` datetime NOT NULL,
		  `date_modified` datetime NOT NULL,
		  PRIMARY KEY (`mppoll_reaction_id`)
		) ENGINE=MyISAM AUTO_INCREMENT=23 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;");

		/*
		 Table structure for table `oc_mppoll_to_store`
		*/
		$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."mppoll_to_store` (
		  `mppoll_id` int(11) NOT NULL,
		  `store_id` int(11) NOT NULL,
		  PRIMARY KEY (`mppoll_id`,`store_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;");

	}
	public function uninstall(): void {
		// Do module un-install tasks

		// remove module events
		$this->load->model('setting/event');
		$code = 'mppoll';

		$this->model_setting_event->deleteEventByCode($code);
	}

	public function save(): void {
		$this->load->language('extension/mppoll/module/mppollglobal');
		$this->load->model('setting/setting');
		$this->load->model('extension/mppoll/mppoll');

		if (isset($this->request->get['store_id'])) {
			$store_id = (int)$this->request->get['store_id'];
		} else {
			$store_id = self :: DEFAULT_STORE;
		}

		$json = [];

		$error = $this->validate();

		if ($error) {
			$json['error'] = $error;
		}

		if (!$json) {

			$this->model_setting_setting->editSetting('module_mppollglobal', $this->request->post, $store_id);
			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function index(): void {

		$this->load->language('extension/mppoll/module/mppollglobal');
		$this->load->model('setting/setting');
		$this->load->model('extension/mppoll/mppoll');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->document->addStyle(HTTP_CATALOG . 'extension/mppoll/admin/view/javascript/mppoll/colorpicker/css/colorpicker.css');
		$this->document->addScript(HTTP_CATALOG . 'extension/mppoll/admin/view/javascript/mppoll/colorpicker/js/colorpicker.js');

		if (isset($this->request->get['store_id'])) {
			$store_id = (int)$this->request->get['store_id'];
		} else {
			$store_id = self :: DEFAULT_STORE;
		}

		$mppollsettings = $this->model_setting_setting->getSetting('module_mppollglobal', $store_id);

		// inform user to save once with default settings. if they don't want to modified.
		$data['info'] = '';
		if (!isset($mppollsettings['module_mppollglobal_store']) || is_null($mppollsettings['module_mppollglobal_store'])) {
			$data['info'] = $this->language->get('text_saveonce');
		}
		
		$data['help_cookie'] = str_replace(
			':',
			self :: COOKIES_TIME_SEP,
			str_replace(
				'SEC:MIN:HOUR:DAY',
				self :: COOKIES_TIME_SEC . self :: COOKIES_TIME_SEP .self :: COOKIES_TIME_MIN . self :: COOKIES_TIME_SEP .self :: COOKIES_TIME_HOUR . self :: COOKIES_TIME_SEP .self :: COOKIES_TIME_DAY,
				$this->language->get('help_cookie')
			)
		);

		$data['help_successmsg'] = htmlentities(sprintf( $this->language->get('help_successmsg'), $this->language->get('const_successmsg')));

		$data['mppoll'] = $this->url->link('extension/mppoll/mppoll', 'user_token=' . $this->session->data['user_token'], true);


		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/mppoll/module/mppollglobal', 'user_token=' . $this->session->data['user_token'], true)
		];

		if ($store_id) {
			$data['save'] = $this->url->link('extension/mppoll/module/mppollglobal|save', 'user_token=' . $this->session->data['user_token'] . 'store_id=' . $store_id, true);
		} else {
			$data['save'] = $this->url->link('extension/mppoll/module/mppollglobal|save', 'user_token=' . $this->session->data['user_token'], true);
		}

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], true);

		$this->load->model('setting/store');

		$data['stores'] = $this->model_setting_store->getStores();

		$data['user_token'] = $this->session->data['user_token'];

		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

		if (isset($this->request->post['module_mppollglobal_status'])) {
			$data['module_mppollglobal_status'] = $this->request->post['module_mppollglobal_status'];
		} elseif (isset($mppollsettings['module_mppollglobal_status'])) {
			$data['module_mppollglobal_status'] = $mppollsettings['module_mppollglobal_status'];
		} else {
			$data['module_mppollglobal_status'] = 0;
		}

		if (isset($this->request->post['module_mppollglobal_store'])) {
			$data['module_mppollglobal_store'] = $this->request->post['module_mppollglobal_store'];
		} elseif (isset($mppollsettings['module_mppollglobal_store']) && !empty($mppollsettings['module_mppollglobal_store'])) {
			$data['module_mppollglobal_store'] = $mppollsettings['module_mppollglobal_store'];
		} else {
			$data['module_mppollglobal_store'] = self :: DEFAULT_STORE;
		}

		if (isset($this->request->post['module_mppollglobal_usecolor'])) {
			$data['module_mppollglobal_usecolor'] = $this->request->post['module_mppollglobal_usecolor'];
		} elseif (isset($mppollsettings['module_mppollglobal_usecolor']) && !empty($mppollsettings['module_mppollglobal_usecolor'])) {
			$data['module_mppollglobal_usecolor'] = $mppollsettings['module_mppollglobal_usecolor'];
		} else {
			$data['module_mppollglobal_usecolor'] = self :: USECOLOR;
		}

		if (isset($this->request->post['module_mppollglobal_cookie'])) {
			$data['module_mppollglobal_cookie'] = $this->request->post['module_mppollglobal_cookie'];
		} elseif (isset($mppollsettings['module_mppollglobal_cookie']) && !empty($mppollsettings['module_mppollglobal_cookie'])) {
			$data['module_mppollglobal_cookie'] = $mppollsettings['module_mppollglobal_cookie'];
		} else {
			$data['module_mppollglobal_cookie'] = self :: COOKIES_TIME_SEC . self :: COOKIES_TIME_SEP .self :: COOKIES_TIME_MIN . self :: COOKIES_TIME_SEP .self :: COOKIES_TIME_HOUR . self :: COOKIES_TIME_SEP .self :: COOKIES_TIME_DAY;
		}


		if (isset($this->request->post['module_mppollglobal_chartadmin_w'])) {
			$data['module_mppollglobal_chartadmin_w'] = $this->request->post['module_mppollglobal_chartadmin_w'];
		} elseif (isset($mppollsettings['module_mppollglobal_chartadmin_w']) && !empty($mppollsettings['module_mppollglobal_chartadmin_w'])) {
			$data['module_mppollglobal_chartadmin_w'] = $mppollsettings['module_mppollglobal_chartadmin_w'];
		} else {
			$data['module_mppollglobal_chartadmin_w'] = $this->CHART['ADMIN']['W'];
		}
		if (isset($this->request->post['module_mppollglobal_chartadmin_h'])) {
			$data['module_mppollglobal_chartadmin_h'] = $this->request->post['module_mppollglobal_chartadmin_h'];
		} elseif (isset($mppollsettings['module_mppollglobal_chartadmin_h']) && !empty($mppollsettings['module_mppollglobal_chartadmin_h'])) {
			$data['module_mppollglobal_chartadmin_h'] = $mppollsettings['module_mppollglobal_chartadmin_h'];
		} else {
			$data['module_mppollglobal_chartadmin_h'] = $this->CHART['ADMIN']['H'];
		}

		if (isset($this->request->post['module_mppollglobal_chartcatalog_w'])) {
			$data['module_mppollglobal_chartcatalog_w'] = $this->request->post['module_mppollglobal_chartcatalog_w'];
		} elseif (isset($mppollsettings['module_mppollglobal_chartcatalog_w']) && !empty($mppollsettings['module_mppollglobal_chartcatalog_w'])) {
			$data['module_mppollglobal_chartcatalog_w'] = $mppollsettings['module_mppollglobal_chartcatalog_w'];
		} else {
			$data['module_mppollglobal_chartcatalog_w'] = $this->CHART['CATALOG']['W'];
		}
		
		if (isset($this->request->post['module_mppollglobal_chartcatalog_h'])) {
			$data['module_mppollglobal_chartcatalog_h'] = $this->request->post['module_mppollglobal_chartcatalog_h'];
		} elseif (isset($mppollsettings['module_mppollglobal_chartcatalog_h']) && !empty($mppollsettings['module_mppollglobal_chartcatalog_h'])) {
			$data['module_mppollglobal_chartcatalog_h'] = $mppollsettings['module_mppollglobal_chartcatalog_h'];
		} else {
			$data['module_mppollglobal_chartcatalog_h'] = $this->CHART['CATALOG']['H'];
		}

		if (isset($this->request->post['module_mppollglobal_errornoneselectmsg'])) {
			$data['module_mppollglobal_errornoneselectmsg'] = $this->request->post['module_mppollglobal_errornoneselectmsg'];
		} elseif (isset($mppollsettings['module_mppollglobal_errornoneselectmsg']) && !empty($mppollsettings['module_mppollglobal_errornoneselectmsg'])) {
			$data['module_mppollglobal_errornoneselectmsg'] = $mppollsettings['module_mppollglobal_errornoneselectmsg'];
		} else {
			$const_errornoneselectmsg = [];
			foreach ($data['languages'] as $language) {
				$const_errornoneselectmsg[$language['language_id']] = $this->language->get('const_errornoneselectmsg');;
			}

			$data['module_mppollglobal_errornoneselectmsg'] = $const_errornoneselectmsg;
		}

		if (isset($this->request->post['module_mppollglobal_notauthorizedmsg'])) {
			$data['module_mppollglobal_notauthorizedmsg'] = $this->request->post['module_mppollglobal_notauthorizedmsg'];
		} elseif (isset($mppollsettings['module_mppollglobal_notauthorizedmsg']) && !empty($mppollsettings['module_mppollglobal_notauthorizedmsg'])) {
			$data['module_mppollglobal_notauthorizedmsg'] = $mppollsettings['module_mppollglobal_notauthorizedmsg'];
		} else {
			$const_notauthorizedmsg = [];
			foreach ($data['languages'] as $language) {
				$const_notauthorizedmsg[$language['language_id']] = $this->language->get('const_notauthorizedmsg');;
			}

			$data['module_mppollglobal_notauthorizedmsg'] = $const_notauthorizedmsg;
		}

		if (isset($this->request->post['module_mppollglobal_successmsg'])) {
			$data['module_mppollglobal_successmsg'] = $this->request->post['module_mppollglobal_successmsg'];
		} elseif (isset($mppollsettings['module_mppollglobal_successmsg']) && !empty($mppollsettings['module_mppollglobal_successmsg'])) {
			$data['module_mppollglobal_successmsg'] = $mppollsettings['module_mppollglobal_successmsg'];
		} else {
			$const_successmsg = [];
			foreach ($data['languages'] as $language) {
				$const_successmsg[$language['language_id']] = $this->language->get('const_successmsg');;
			}

			$data['module_mppollglobal_successmsg'] = $const_successmsg;
		}

		if (isset($this->request->post['module_mppollglobal_usesuccessmsg'])) {
			$data['module_mppollglobal_usesuccessmsg'] = $this->request->post['module_mppollglobal_usesuccessmsg'];
		} elseif (isset($mppollsettings['module_mppollglobal_usesuccessmsg']) && !empty($mppollsettings['module_mppollglobal_usesuccessmsg'])) {
			$data['module_mppollglobal_usesuccessmsg'] = $mppollsettings['module_mppollglobal_usesuccessmsg'];
		} else {
			$data['module_mppollglobal_usesuccessmsg'] = self :: USESUCCESSMSG;
		}

		if (isset($this->request->post['module_mppollglobal_chartcolors'])) {
			$mppollglobal_chartcolors = $this->request->post['module_mppollglobal_chartcolors'];
		} elseif (isset($mppollsettings['module_mppollglobal_chartcolors']) && !empty($mppollsettings['module_mppollglobal_chartcolors'])) {
			$mppollglobal_chartcolors = $mppollsettings['module_mppollglobal_chartcolors'];
		} else {
			$mppollglobal_chartcolors = [];
		}

		if (isset($this->request->post['module_mppollglobal_display_result'])) {
			$data['module_mppollglobal_display_result'] = $this->request->post['module_mppollglobal_display_result'];
		} elseif (isset($mppollsettings['module_mppollglobal_display_result'])) {
			$data['module_mppollglobal_display_result'] = $mppollsettings['module_mppollglobal_display_result'];
		} else {
			$data['module_mppollglobal_display_result'] = self :: DISPAYRESULT;
		}

		$data['module_mppollglobal_chartcolors'] = [];
		foreach ($mppollglobal_chartcolors as $mppollglobal_chartcolor) {
			$data['module_mppollglobal_chartcolors'][] = $mppollglobal_chartcolor;
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/mppoll/module/mppollglobal', $data));
	}

	protected function validate(): array {

		$error = array();

		if (!$this->user->hasPermission('modify', 'extension/mppoll/module/mppollglobal')) {
			$error['warning'] = $this->language->get('error_permission');
		}

		// validate success message
		foreach ($this->request->post['module_mppollglobal_successmsg'] as $language_id => $value) {
			if (($this->model_extension_mppoll_mppoll->strlen($value) < 3) || ($this->model_extension_mppoll_mppoll->strlen($value) > 255)) {
				$error['successmsg_' . $language_id] = $this->language->get('error_successmsg');
			}
		}

		// validate error none select message
		foreach ($this->request->post['module_mppollglobal_errornoneselectmsg'] as $language_id => $value) {
			if (($this->model_extension_mppoll_mppoll->strlen($value) < 3) || ($this->model_extension_mppoll_mppoll->strlen($value) > 255)) {
				$error['errornoneselectmsg_' . $language_id] = $this->language->get('error_errornoneselectmsg');
			}
		}

		// validate not authorized message
		foreach ($this->request->post['module_mppollglobal_notauthorizedmsg'] as $language_id => $value) {
			if (($this->model_extension_mppoll_mppoll->strlen($value) < 3) || ($this->model_extension_mppoll_mppoll->strlen($value) > 255)) {
				$error['notauthorizedmsg_' . $language_id] = $this->language->get('error_notauthorizedmsg');
			}
		}

		if (!isset($this->request->post['module_mppollglobal_display_result'])) {
			$error['display_result'] = $this->language->get('error_display_result');
		}
		
		if (empty($this->request->post['module_mppollglobal_chartadmin_h'])) {
			$error['chartadmin_h'] = $this->language->get('error_chartadmin_h');
		}
		
		if (empty($this->request->post['module_mppollglobal_chartadmin_w'])) {
			$error['chartadmin_w'] = $this->language->get('error_chartadmin_w');
		}

		if (empty($this->request->post['module_mppollglobal_chartcatalog_h'])) {
			$error['chartcatalog_h'] = $this->language->get('error_chartcatalog_h');
		}
		
		if (empty($this->request->post['module_mppollglobal_chartcatalog_w'])) {
			$error['chartcatalog_w'] = $this->language->get('error_chartcatalog_w');
		}

		// validate cookies time. if empty or any invalid value found, raise an error
		if (empty($this->request->post['module_mppollglobal_cookie']) ) {
			$error['cookie'] = str_replace(':',self :: COOKIES_TIME_SEP, str_replace('SEC:MIN:HOUR:DAY', self :: COOKIES_TIME_SEC . self :: COOKIES_TIME_SEP .self :: COOKIES_TIME_MIN . self :: COOKIES_TIME_SEP .self :: COOKIES_TIME_HOUR . self :: COOKIES_TIME_SEP .self :: COOKIES_TIME_DAY, $this->language->get('error_cookie')) );
		}
		// break string using seperator and check valid time or not.
		

		// validate over ride color yes (1) or no(0). Any value apart from it, raise an error
		if (!isset($this->request->post['module_mppollglobal_usecolor'])) {
			$error['usecolor'] = $this->language->get('error_usecolor');
		} elseif (isset($this->request->post['module_mppollglobal_usecolor']) && $this->request->post['module_mppollglobal_usecolor'] != 1 && $this->request->post['module_mppollglobal_usecolor'] != 0) {
			$error['usecolor'] = $this->language->get('error_usecolor');
		}

		if ($error && !isset($error['warning'])) {
			$error['warning'] = $this->language->get('error_warning');
		}

		return $error;
	}
}