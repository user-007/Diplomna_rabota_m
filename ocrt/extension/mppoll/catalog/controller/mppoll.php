<?php

namespace Opencart\Catalog\Controller\Extension\Mppoll;

class Mppoll extends \Opencart\System\Engine\Controller {

	public function footer(string &$route, array &$data, string &$output): void {

		if (!$this->config->get('module_mppollglobal_status')) {
			return;
		}

		$this->load->language('extension/mppoll/mppoll_menu');

		$find = '<li><a href="' . $data['special'] . '">' . $data['text_special'] . '</a></li>';

		$replace = '<li><a href="' . $this->url->link('extension/mppoll/mppoll', '', true) . '">' . $this->language->get('text_poll') . '</a></li>';

		$mppollglobal_display_result = $this->config->get('module_mppollglobal_display_result');
		$display_result = false;

		if ($mppollglobal_display_result == 2 || $mppollglobal_display_result == null) {
			$display_result = true;
		}
		if ($mppollglobal_display_result == 1) {
			// display results to login customer only
			if ($this->customer->isLogged()) {
				$display_result = true;
			}
		}
		if ($mppollglobal_display_result == 0) {
			$display_result = false;
		}

		if ($display_result) {
			$output = str_replace($find, $replace, $output);
		}

	}
	public function index(): void {

		if ($this->config->get('module_mppollglobal_status')) {
			$this->document->addScript(HTTP_SERVER . 'extension/mppoll/catalog/view/javascript/mppoll.js');
		}



		$this->load->language('extension/mppoll/mppoll');

		$this->load->model('extension/mppoll/mppoll');

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		];

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

		if (!$this->config->get('module_mppollglobal_status')) {
			$data['display_result'] = false;
		}

		if ($data['display_result']) {

		if (isset($this->request->get['mppoll_id'])) {
			$mppoll_ids = [(int)$this->request->get['mppoll_id']];
			$data['mppoll_id'] = (int)$this->request->get['mppoll_id'];
		} else {
			$mppoll_ids = [];
			$data['mppoll_id'] = 0;
		}


		$mppolls_infos = $this->model_extension_mppoll_mppoll->getMppolls(['mppoll_ids' => $mppoll_ids]);

		if (isset($mppolls_infos[0]['mppoll_id'])) {
			$href = $this->url->link('extension/mppoll/mppoll', 'mppoll_id=' . $mppolls_infos[0]['mppoll_id'],true);
		} else {
			$href = $this->url->link('extension/mppoll/mppoll', '',true);
		}
			
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $href,
		];

		$this->document->setTitle($this->language->get('heading_title'));

		$data['continue'] = $this->url->link('common/home');

		$data['mppolls'] = [];

		foreach ($mppolls_infos as $mppolls_info) {
			$data['mppolls'][] = [
				'question'  => $mppolls_info['question'],
				'responses' => $this->model_extension_mppoll_mppoll->getTotalVotes($mppolls_info['mppoll_id']),
				'mppoll_id'      => $mppolls_info['mppoll_id'],
			];
		}
		// end mppoll

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('extension/mppoll/mppoll', $data));

		} else {

			$data['const_notauthorizedmsg'] = $this->language->get('const_notauthorizedmsg');	

			// get not authorized message from setting
			$global_notauthorizedmsg = $this->config->get('module_mppollglobal_notauthorizedmsg');

			if (isset($global_notauthorizedmsg[ $this->config->get('config_language_id') ]) && !empty($global_notauthorizedmsg[ $this->config->get('config_language_id') ])) {
				$data['const_notauthorizedmsg'] = $global_notauthorizedmsg[ $this->config->get('config_language_id') ];
			}


			$data['mppoll_id'] = 0;

			$data['breadcrumbs'][] = [
				'text' => $data['const_notauthorizedmsg'],
				'href' => $this->url->link('extension/mppoll/mppoll', '',true)
			];

			$this->document->setTitle($data['const_notauthorizedmsg']);

			$data['heading_title'] = $data['const_notauthorizedmsg'];

			$data['button_continue'] = $this->language->get('button_continue');

			$data['continue'] = $this->url->link('common/home');

			$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');
			$this->response->setOutput($this->load->view('error/not_found', $data));
		}
		
	}

	public function viewResult(): void {
		$this->response->addHeader('Content-Type: application/json');

		$this->load->model('extension/mppoll/mppoll');
		$this->load->language('extension/mppoll/mppoll');

		$json = [];

		if (!$this->config->get('module_mppollglobal_status')) {
			$json = [];
			$this->response->setOutput(json_encode($json));
			$this->response->output();
			exit();
		}

		$mppollglobal_display_result = $this->config->get('module_mppollglobal_display_result');
		$display_result = false;

		if ($mppollglobal_display_result == 2 || $mppollglobal_display_result == null) {
			$display_result = true;
		}
		if ($mppollglobal_display_result == 1) {
			// display results to login customer only
			if ($this->customer->isLogged()) {
				$display_result = true;
			}
		}
		if ($mppollglobal_display_result == 0) {
			$display_result = false;
		}

		if ($display_result == false) {
			$json['error'] = $this->language->get('const_notauthorizedmsg');
			$this->response->setOutput(json_encode($json));
			$this->response->output();
			exit();
		}

		if (isset($this->request->post['id'])) {
			$mppoll_id = (int)$this->request->post['id'];
		} else {
			$mppoll_id = 0;
		}

		$mppoll_info = $this->model_extension_mppoll_mppoll->getMppoll($mppoll_id);

		if (empty($mppoll_info)) {
			$json['error'] = $this->language->get('error_invalid_url');
		}

		$json['mppoll_results'] = '';

		if (!empty($mppoll_info)) {

			$hasvotes = $this->model_extension_mppoll_mppoll->getMppollHasVotes($mppoll_id);

			$total_votes = $this->model_extension_mppoll_mppoll->getTotalVotes($mppoll_id);

			$vote_max_answer = count(array_filter(json_decode($mppoll_info['answers'], 1)));

			if ($hasvotes) {
				$data['hasvotes'] = true;
				$percent = [];
				$totals = [];
				for ($i = 1; $i <= $mppoll_info['maxanswers']; $i++) {
					$percent[$i] = 0;
					$totals[$i]  = 0;
				}


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

				$data['answers'] = array_filter(json_decode($mppoll_info['answers'], true));

				$data['percent'] = $percent;
				$data['total_votes'] = $total_votes;
				$data['question'] = $mppoll_info['question'];
				$data['mppoll_id'] = $mppoll_info['mppoll_id'];

				/**Chart color settings start*/
				$r_chartcolors = [];
				if (!empty($mppoll_info['chartcolors'])) {
					$r_chartcolors =  json_decode($mppoll_info['chartcolors'],1);
				}

				// check if user want to use global chart color ?
				if ($mppoll_info['useglobalcolor'] == 1) {
					$r_chartcolors = $this->config->get('module_mppollglobal_chartcolors');
				}

				// now check if override for color is yes in global settings or not. 
				if ($this->config->get('module_mppollglobal_usecolor') == 1) {

					// check if user want to allow chart colors to be override ?
					if ($mppoll_info['allowcoloroverride'] == 1) {
						$r_chartcolors = $this->config->get('module_mppollglobal_chartcolors');
					}
				}


				// count chartcolors. if less than max answers, then add upto max answers. 
				// For example chartcolors has 3 elements, then 4th element get value of 1st element, 5th will get 2nd and soo on.
				// print_r($chartcolors);
				// cut array for testing :) :D

				// $r_chartcolors = (array_slice($r_chartcolors,0,5,true)); 
				// echo "before filling \n";
				// print_r($r_chartcolors);
				
				/*fix when answer no is 0 or empty starts*/
				/* loop through and check answers_no is empty or 0;*/

				foreach ($r_chartcolors as $key => $value) {
					if (empty($value['answer_no'])) {
						unset($r_chartcolors[$key]);
					}
				}

				/*fix when answer no is 0 or empty ends*/
				// reset array keys on 07/01/2017
				$r_chartcolors = array_values($r_chartcolors);

					
				if ( count($r_chartcolors) > 0 && count($r_chartcolors) < $mppoll_info['maxanswers']) {
					// check how many elements we need.
					do {
						$total_r_chartcolor = count($r_chartcolors);
						// echo $total_chartcolor;
						$remaining = $mppoll_info['maxanswers'] - $total_r_chartcolor;
						$j = $total_r_chartcolor;
						for ($i = 1; $i <= $remaining; $i++) {
							if ($i <= ($total_r_chartcolor - 1)) {
							$j++;
							// echo "\n\n i : ".$i . "\n\n";
							// echo $chartcolors[$i]['chartcolor'];

							// can use answer_no
							$canuse = [];
							for ($ci = 1; $ci <= $mppoll_info['maxanswers']; $ci++) {
								$canuse[$ci] = $ci;
							}

							// check here if answer no is not already in use. which answer no. to be assign.	

							// loop through and check answers_no in use.
							
							foreach ($r_chartcolors as $r_chartcolor) {
								unset( $canuse[ $r_chartcolor['answer_no'] ]);
							}
							$ncanuse = array_values($canuse);

							$r_chartcolors[$j - 1] = [
								'chartcolor' => $r_chartcolors[$i]['chartcolor'],
								'answer_no' => $ncanuse[0],
							];

							}
						}	
					
					} while (count($r_chartcolors) < $mppoll_info['maxanswers']);

					
				}

				// echo "\n\n\n after filling \n";
				// print_r($r_chartcolors);
				// die();
				// set default chart colors.
				if (count($r_chartcolors) <= 0) {
					$k = 0;
					$defcolor = ['0F151A', '0091BF', '328B3C'];
					$t_defcolor= count($defcolor) -1;
					for ($i = 1; $i <= $mppoll_info['maxanswers']; $i++) {
						
						$r_chartcolors[] = ['chartcolor' => $defcolor[$k], 'answer_no' => $i + 1];
						$k++;
						if ($k == $t_defcolor) {
							$k = 0;
						}
					}
				}


				$data['r_chartcolors'] = [];
				// set answer_no as key of chart color
				foreach ($r_chartcolors as $r_chartcolor) {
					$data['r_chartcolors'][ $r_chartcolor['answer_no'] ] = $r_chartcolor['chartcolor'];
				}

				// sort($data['chartcolors'],true);
				// print_r($data['chartcolors']);
				// die();

				/**Chart color settings ends*/
				// get chart height, width of admin.
				$data['chart_w'] = $this->config->get('module_mppollglobal_chartcatalog_w');
				$data['chart_h'] = $this->config->get('module_mppollglobal_chartcatalog_h');
				// set default chart width,  height in case of empty or null.
				if ($data['chart_w'] == '' || $data['chart_w'] == null) {
					$data['chart_w'] = 770;
				}
				if ($data['chart_h'] == '' || $data['chart_h'] == null) {
					$data['chart_h'] = 200;
				}

				$json['success'] = true;

				$json['mppoll_result'] = $this->load->view('extension/mppoll/mppoll_result', $data);

			}
		}
		$this->response->setOutput(json_encode($json));
	}
}