<?php

namespace Opencart\Catalog\Controller\Extension\Mppoll\Module;

class Mppoll extends \Opencart\System\Engine\Controller {
	public function index(array $setting): string {

		if (!$this->config->get('module_mppollglobal_status')) {
			return '';
		}

		static $module = 0;

		$this->document->addStyle(HTTP_SERVER . 'extension/mppoll/catalog/view/stylesheet/mppoll.css');
		$this->document->addScript(HTTP_SERVER . 'extension/mppoll/catalog/view/javascript/mppoll.js');

		$this->load->language('extension/mppoll/module/mppoll');

		$maxanswers = 8;

		$mppollglobal_display_result = $this->config->get('module_mppollglobal_display_result');
		$data['display_result'] = false;
		if ($mppollglobal_display_result == 2 || $mppollglobal_display_result == null) {
			$data['display_result'] = true;
		}
		if ($mppollglobal_display_result == 1) {
		// display results to login customer only
		if ($this->customer->isLogged()) {
		$data['display_result'] = true;	
		}
		}
		if ($mppollglobal_display_result == 0) {
		$data['display_result'] = false;	
		}


		$data['heading_title'] = $this->language->get('heading_title');
		if (!empty($setting['module_description'][ $this->config->get('config_language_id') ]['title'] )) {
			$data['heading_title'] = $setting['module_description'][ $this->config->get('config_language_id') ]['title'];
		}

		// get no answer select message from setting
		$global_errornoneselectmsg = $this->config->get('module_mppollglobal_errornoneselectmsg');

		if (isset($global_errornoneselectmsg[ $this->config->get('config_language_id') ]) && !empty($global_errornoneselectmsg[ $this->config->get('config_language_id') ])) {
			$data['const_errornoneselectmsg'] = $global_errornoneselectmsg[ $this->config->get('config_language_id') ];
		}

		$this->load->model('extension/mppoll/mppoll');

		$data['mppolls'] = [];

		if (!empty($setting['mppoll'])) {
			
		foreach ($setting['mppoll'] as $mppoll_id) {
		$mppoll_info = $this->model_extension_mppoll_mppoll->getMppoll($mppoll_id);

		if ($mppoll_info) {

		//check poll status. if poll is disabled. Then take no more votes on particular poll. and display results.
		$disabled = false;
		if ($mppoll_info['status'] == 0) {
		$disabled = true;
		}

		// if poll already answered by user, then take no more votes from particular user.
		// User detection based on cookies.
		// user can re vote after 7 days.
		$answered = false;

		if (isset($this->request->cookie['mppoll_'.$mppoll_info['mppoll_id'].'_answered'])) {
			if ($this->request->cookie['mppoll_'.$mppoll_info['mppoll_id'].'_answered'] == $mppoll_info['mppoll_id']) {
				$answered = true;
			}
		}

		/**
		Get max answers from database
		So if max answers values changes occured in future. Previous votes stay un-effected
		*/

		$hasvotes = $this->model_extension_mppoll_mppoll->getMppollHasVotes($mppoll_info['mppoll_id']);

		$total_votes = $this->model_extension_mppoll_mppoll->getTotalVotes($mppoll_info['mppoll_id']);
		$myhasvotes = false;
		$percent = [];
		$totals  = [];

		for ($i = 1; $i <= $mppoll_info['maxanswers']; $i++) {
			$percent[$i] = 0;
			$totals[$i] = 0;
		}

		// print_r($mppoll_info);
		// print_r($totals);
		// print_r($hasvotes);

		$vote_max_answer = count(array_filter(json_decode($mppoll_info['answers'], 1)));


		if ($hasvotes) {
		$myhasvotes = true;
		foreach ($hasvotes as $hasvote) {
			if ($hasvote['answer'] > $vote_max_answer) {
				$total_votes--;
				continue;
			}
			$totals[$hasvote['answer']]++;
		}

		for ($i = 1; $i <= $mppoll_info['maxanswers']; $i++) {
			$percent[$i] = round(100 * ($totals[$i]/$total_votes));
		}
		}


		$data['mppolls'][] = [
			'mppoll_id'  => $mppoll_info['mppoll_id'],
			'question'        => $mppoll_info['question'],
			'maxanswers'        => $mppoll_info['maxanswers'],
			'hasvotes'        => $myhasvotes,
			'answered'        => $answered,
			'disabled'        => $disabled,
			'percent'        => $percent,
			'total_votes'        => $total_votes,
			'answers' => array_filter(json_decode($mppoll_info['answers'], true)),

		];
		}
		}
		}

		if ($data['mppolls']) {
			$data['module'] = $module++;
			return $this->load->view('extension/mppoll/module/mppoll', $data);
		}

		return '';
	}

	public function addPollHasVote(): void {

		if ($this->config->get('module_mppollglobal_status')) {

			$json = [];

			$this->load->language('extension/mppoll/mppoll');
			$this->load->language('extension/mppoll/module/mppoll');

			if (!isset($this->request->post['answer_id']) || !isset($this->request->post['id'])) {
				$json['error'] = $this->language->get('error_invalid_url');
			}

			if (count($json) == 0) {

				$this->load->model('extension/mppoll/mppoll');
				$this->model_extension_mppoll_mppoll->addHasVote($this->request->post);

				// default is 7 days
				$cookietime = 60 * 60 * 24 * 7;
				$mppollglobal_cookie = $this->config->get('module_mppollglobal_cookie');
				if ($mppollglobal_cookie != null && !empty($mppollglobal_cookie)) {
					$cookies = explode(':', $mppollglobal_cookie);
					$newcookie = 1;
					// cookie time values
					foreach ($cookies as $cook) {
						$newcookie = $newcookie * (int)$cook;
					}

					// if new cookie time is greater than 1, than apply new cookie time.
					if ($newcookie > 1) {
						$cookietime = $newcookie;
					}
				}

				// Set a cookie:
				setcookie('mppoll_'.$this->request->post['id'] .'_answered', $this->request->post['id'], time() + $cookietime); // Can only vote once a week

				// success msg variable
				$successmsg = '';
				// get mppoll info using id.
				$mppoll_info = $this->model_extension_mppoll_mppoll->getMppoll($this->request->post['id']);

				if (!empty($mppoll_info)) {
					// check if particular poll success message empty or not.

					if (!empty($mppoll_info['successmsg']) && isset($mppoll_info['successmsg'][ (int)$this->config->get('config_language_id') ] ) && !empty($mppoll_info['successmsg'][ (int)$this->config->get('config_language_id') ])) {
						$successmsg = $mppoll_info['successmsg'][ (int)$this->config->get('config_language_id') ];
					}

					// check if user want to use global success msg ?
					if ($mppoll_info['useglobalsuccessmsg'] == 1) {
						$global_successmsg = $this->config->get('module_mppollglobal_successmsg');

						if ($global_successmsg != null && !empty($global_successmsg)) {

							// now check global msg available in current language?
							if (isset($global_successmsg[ $this->config->get('config_language_id') ]) && !empty($global_successmsg[ $this->config->get('config_language_id') ])) {
									$successmsg = $global_successmsg[ $this->config->get('config_language_id') ];
							}

						}
					}

					// now check if override for success msg is yes in global settings or not.
					if ($this->config->get('module_mppollglobal_usesuccessmsg') == 1) {

						// check if user want to allow success msg to be override ?
						if ($mppoll_info['allowsuccessmsgoverride'] == 1) {

							$global_successmsg = $this->config->get('module_mppollglobal_successmsg');
							if ($global_successmsg != null && !empty($global_successmsg)) {

								// now check global msg available in current language?
								if (isset($global_successmsg[ $this->config->get('config_language_id') ]) && !empty($global_successmsg[ $this->config->get('config_language_id') ])) {
										$successmsg = $global_successmsg[ $this->config->get('config_language_id') ];
								}

							}

						}
					}

				}
				// if success msg is still empty. Use language file translation.
				if (empty($successmsg) || is_null($successmsg)) {
					$successmsg = $this->language->get('const_successmsg');
				}

				$json['success'] = $successmsg;

			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}