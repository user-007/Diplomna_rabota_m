<?php

namespace Opencart\Admin\Controller\Extension\Mppoll;

class Mppoll extends \Opencart\System\Engine\Controller {
	private array $error = [];


	public function column_left(string &$route, array &$data, string &$code): void {
		// Mppoll
		$mppoll = [];

		$this->load->language('extension/mppoll/column_left');

		$installed = [];
		$this->load->model('setting/extension');
		$results = $this->model_setting_extension->getExtensionsByType('module');

		foreach ($results as $result) {
			$installed[] = $result['extension'] . '.' . $result['code'];
		}

		if (!in_array('mppoll.mppollglobal', $installed)) {
			return;
		}

		if ($this->user->hasPermission('access', 'extension/mppoll/module/mppollglobal')) {
			$mppoll[] = [
				'name'	   => $this->language->get('text_mppollglobal'),
				'href'     => $this->url->link('extension/mppoll/module/mppollglobal', 'user_token=' . $this->session->data['user_token'], true),
				'children' => []
			];
		}

		if ($this->user->hasPermission('access', 'extension/mppoll/mppoll')) {
			$mppoll[] = [
				'name'	   => $this->language->get('text_mppoll_mpoll'),
				'href'     => $this->url->link('extension/mppoll/mppoll', 'user_token=' . $this->session->data['user_token'], true),
				'children' => []
			];
		}

		if (in_array('mppoll.mppoll', $installed) && $this->user->hasPermission('access', 'extension/mppoll/module/mppoll')) {
			$mppoll[] = [
				'name'	   => $this->language->get('text_mppoll'),
				'href'     => $this->url->link('extension/mppoll/module/mppoll', 'user_token=' . $this->session->data['user_token'], true),
				'children' => []
			];
		}

		if ($mppoll) {
			foreach ($data['menus'] as $key => $value) {
				if ($value['id'] == 'menu-extension') {
					$data['menus'][$key]['children'][] = [
						'name'	   => $this->language->get('text_module_mppoll'),
						'href'     => '',
						'children' => $mppoll
					];
					break;
				}
			}

		}
	}

	public function checkGlobalSettings(): void {
		$check = $this->config->get('module_mppollglobal_store');
		if (!isset($check) || is_null($check)) {
			$this->response->redirect($this->url->link('extension/mppoll/module/mppollglobal', 'user_token=' . $this->session->data['user_token'], true));
		}
	}

	public function index(): void {
		$this->checkGlobalSettings();
		$this->load->language('extension/mppoll/mppoll');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/mppoll/mppoll');

		$this->getList();
	}

	public function add(): void {

		$this->checkGlobalSettings();

		$this->load->language('extension/mppoll/mppoll');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/mppoll/mppoll');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_extension_mppoll_mppoll->addMppoll($this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('extension/mppoll/mppoll', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	public function edit(): void {
		$this->checkGlobalSettings();
		$this->load->language('extension/mppoll/mppoll');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/mppoll/mppoll');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_extension_mppoll_mppoll->editMppoll($this->request->get['mppoll_id'], $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('extension/mppoll/mppoll', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	public function delete(): void {
		$this->checkGlobalSettings();
		$this->load->language('extension/mppoll/mppoll');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/mppoll/mppoll');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $mppoll_id) {
				$this->model_extension_mppoll_mppoll->deleteMppoll($mppoll_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('extension/mppoll/mppoll', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getList();
	}

	protected function getList(): void {
		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'pd.question';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'ASC';
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/mppoll/mppoll', 'user_token=' . $this->session->data['user_token'] . $url, true)
		];

		$data['add'] = $this->url->link('extension/mppoll/mppoll|add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['delete'] = $this->url->link('extension/mppoll/mppoll|delete', 'user_token=' . $this->session->data['user_token'] . $url, true);

		$data['mppolls'] = [];

		$filter_data = [
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('config_pagination_admin'),
			'limit' => $this->config->get('config_pagination_admin')
		];

		$mppoll_total = $this->model_extension_mppoll_mppoll->getTotalMppolls();

		$results = $this->model_extension_mppoll_mppoll->getMppolls($filter_data);

		foreach ($results as $result) {
			$data['mppolls'][] = [
				'mppoll_id' => $result['mppoll_id'],
				'question'          => $result['question'],
				'status'          => ($result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled')),
				'date_added'          => date($this->language->get('date_format_short'), strtotime($result['date_added'] )),
				'sort_order'     => $result['sort_order'],
				'edit'           => $this->url->link('extension/mppoll/mppoll|edit', 'user_token=' . $this->session->data['user_token'] . '&mppoll_id=' . $result['mppoll_id'] . $url, true)
			];
		}

		$data['mppollglobal'] = $this->url->link('extension/mppoll/module/mppollglobal', 'user_token=' . $this->session->data['user_token'], true);

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->request->post['selected'])) {
			$data['selected'] = $this->request->post['selected'];
		} else {
			$data['selected'] = [];
		}

		$url = '';

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['sort_question'] = $this->url->link('extension/mppoll/mppoll', 'user_token=' . $this->session->data['user_token'] . '&sort=pd.question' . $url, true);
		$data['sort_status'] = $this->url->link('extension/mppoll/mppoll', 'user_token=' . $this->session->data['user_token'] . '&sort=p.status' . $url, true);
		$data['sort_date_added'] = $this->url->link('extension/mppoll/mppoll', 'user_token=' . $this->session->data['user_token'] . '&sort=p.date_added' . $url, true);
		$data['sort_sort_order'] = $this->url->link('extension/mppoll/mppoll', 'user_token=' . $this->session->data['user_token'] . '&sort=p.sort_order' . $url, true);

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $mppoll_total,
			'page'  => $page,
			'limit' => $this->config->get('config_pagination_admin'),
			'url'   => $this->url->link('extension/mppoll/mppoll', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}')
		]);

		$data['results'] = sprintf($this->language->get('text_pagination'), ($mppoll_total) ? (($page - 1) * $this->config->get('config_pagination_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_pagination_admin')) > ($mppoll_total - $this->config->get('config_pagination_admin'))) ? $mppoll_total : ((($page - 1) * $this->config->get('config_pagination_admin')) + $this->config->get('config_pagination_admin')), $mppoll_total, ceil($mppoll_total / $this->config->get('config_pagination_admin')));

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/mppoll/mppoll_list', $data));
	}

	protected function getForm(): void {

		$this->document->addStyle(HTTP_CATALOG . 'extension/mppoll/admin/view/javascript/mppoll/colorpicker/css/colorpicker.css');
		$this->document->addScript(HTTP_CATALOG . 'extension/mppoll/admin/view/javascript/mppoll/colorpicker/js/colorpicker.js');

		$data['maxanswers'] = 8;

		$data['text_form'] = !isset($this->request->get['mppoll_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

		$data['help_successmsg'] = htmlentities(sprintf( $this->language->get('help_successmsg'), $this->language->get('const_successmsg')));		

		$data['mppollglobal'] = $this->url->link('extension/mppoll/module/mppollglobal', 'user_token=' . $this->session->data['user_token'], true);

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['title'])) {
			$data['error_title'] = $this->error['title'];
		} else {
			$data['error_title'] = [];
		}

		if (isset($this->error['question'])) {
			$data['error_question'] = $this->error['question'];
		} else {
			$data['error_question'] = [];
		}

		if (isset($this->error['answer'])) {
			$data['error_answer'] = $this->error['answer'];
		} else {
			$data['error_answer'] = [];
		}
		
		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/mppoll/mppoll', 'user_token=' . $this->session->data['user_token'] . $url, true)
		];

		if (!isset($this->request->get['mppoll_id'])) {
			$data['action'] = $this->url->link('extension/mppoll/mppoll|add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		} else {
			$data['action'] = $this->url->link('extension/mppoll/mppoll|edit', 'user_token=' . $this->session->data['user_token'] . '&mppoll_id=' . $this->request->get['mppoll_id'] . $url, true);
		}

		$data['cancel'] = $this->url->link('extension/mppoll/mppoll', 'user_token=' . $this->session->data['user_token'] . $url, true);

		if (isset($this->request->get['mppoll_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$mppoll_info = $this->model_extension_mppoll_mppoll->getMppoll($this->request->get['mppoll_id']);
		}

		$data['user_token'] = $this->session->data['user_token'];

		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

		if (isset($this->request->post['mppoll_description'])) {
			$data['mppoll_description'] = $this->request->post['mppoll_description'];
		} elseif (isset($this->request->get['mppoll_id'])) {
			$data['mppoll_description'] = $this->model_extension_mppoll_mppoll->getMppollDescriptions($this->request->get['mppoll_id']);
		} else {
			$data['mppoll_description'] = [];
		}


		// Stores
		$this->load->model('setting/store');

		$data['stores'] = [];

		$data['stores'][] = [
			'store_id' => 0,
			'name'     => $this->language->get('text_default')
		];

		$stores = $this->model_setting_store->getStores();

		foreach ($stores as $store) {
			$data['stores'][] = [
				'store_id' => $store['store_id'],
				'name'     => $store['name']
			];
		}

		if (isset($this->request->post['mppoll_store'])) {
			$data['mppoll_store'] = $this->request->post['mppoll_store'];
		} elseif (isset($this->request->get['mppoll_id'])) {
			$data['mppoll_store'] = $this->model_extension_mppoll_mppoll->getMppollStores($this->request->get['mppoll_id']);
		} else {
			$data['mppoll_store'] = [0];
		}

		
		if (isset($this->request->post['status'])) {
			$data['status'] = $this->request->post['status'];
		} elseif (isset($mppoll_info['status'])) {
			$data['status'] = $mppoll_info['status'];
		} else {
			$data['status'] = 1;
		}

		if (isset($this->request->post['useglobalcolor'])) {
			$data['useglobalcolor'] = $this->request->post['useglobalcolor'];
		} elseif (isset($mppoll_info['useglobalcolor']) && $mppoll_info['useglobalcolor'] != '') {
			$data['useglobalcolor'] = $mppoll_info['useglobalcolor'];
		} else {
			$data['useglobalcolor'] = 0;
		}

		if (isset($this->request->post['allowcoloroverride'])) {
			$data['allowcoloroverride'] = $this->request->post['allowcoloroverride'];
		} elseif (isset($mppoll_info['allowcoloroverride']) && $mppoll_info['allowcoloroverride'] != '') {
			$data['allowcoloroverride'] = $mppoll_info['allowcoloroverride'];
		} else {
			$data['allowcoloroverride'] = 1;
		}

		if (isset($this->request->post['useglobalsuccessmsg'])) {
			$data['useglobalsuccessmsg'] = $this->request->post['useglobalsuccessmsg'];
		} elseif (isset($mppoll_info['useglobalsuccessmsg']) && $mppoll_info['useglobalsuccessmsg'] != '') {
			$data['useglobalsuccessmsg'] = $mppoll_info['useglobalsuccessmsg'];
		} else {
			$data['useglobalsuccessmsg'] = 0;
		}

		if (isset($this->request->post['allowsuccessmsgoverride'])) {
			$data['allowsuccessmsgoverride'] = $this->request->post['allowsuccessmsgoverride'];
		} elseif (isset($mppoll_info['allowsuccessmsgoverride']) && $mppoll_info['allowsuccessmsgoverride'] != '') {
			$data['allowsuccessmsgoverride'] = $mppoll_info['allowsuccessmsgoverride'];
		} else {
			$data['allowsuccessmsgoverride'] = 1;
		}

		if (isset($this->request->post['successmsg'])) {
			$data['successmsg'] = $this->request->post['successmsg'];
		} elseif (isset($mppoll_info['successmsg'])) {
			$data['successmsg'] = $mppoll_info['successmsg'];
		} else {
			$const_successmsg = [];
			foreach ($data['languages'] as $language) {
				$const_successmsg['language_id'] = $this->language->get('const_successmsg');;
			}
			$data['successmsg'] = $const_successmsg;
		}


		if (isset($this->request->post['chartcolors'])) {
			$chartcolors = $this->request->post['chartcolors'];
		} elseif (isset($mppoll_info['chartcolors'])) {
			$chartcolors =  json_decode($mppoll_info['chartcolors'], 1);
		} else {
			$chartcolors = [];
		}

		$data['chartcolors'] = [];
		foreach ($chartcolors as $chartcolor) {
			$data['chartcolors'][] = $chartcolor;
		}

		if (isset($this->request->post['sort_order'])) {
			$data['sort_order'] = $this->request->post['sort_order'];
		} elseif (isset($mppoll_info['sort_order'])) {
			$data['sort_order'] = $mppoll_info['sort_order'];
		} else {
			$data['sort_order'] = '';
		}

		// Extract results
		$data['hasvotes'] = false;
		if (isset($this->request->get['mppoll_id'])) {
			$mppoll_id = $this->request->get['mppoll_id'];

			$poll_data = $this->model_extension_mppoll_mppoll->getMppoll($mppoll_id);

			$poll_data['oanswers'] = $poll_data['answers'];
			$poll_data['answers'] = array_filter(json_decode($poll_data['answers'], true));

			$hasvotes = $this->model_extension_mppoll_mppoll->getMppollResults($mppoll_id);

			$total_votes = $this->model_extension_mppoll_mppoll->getTotalVotes($mppoll_id);

			$vote_max_answer = count($poll_data['answers']);

			if ($hasvotes) {
				$data['hasvotes'] = true;
				$percent = [];
				$totals  = [];
				for ($i = 1; $i <= $data['maxanswers']; $i++) {
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

				for ($i = 1; $i <= $data['maxanswers']; $i++) {
					$percent[$i] = round(100 * ($totals[$i]/$total_votes));
				}

				/**Chart color settings start*/
				$r_chartcolors = [];
				if (!empty($poll_data['chartcolors'])) {
					$r_chartcolors =  json_decode($poll_data['chartcolors'],1);
				}

				// check if user want to use global chart color ?
				if ($poll_data['useglobalcolor'] == 1) {
					$r_chartcolors = $this->config->get('module_mppollglobal_chartcolors');
				}

				// now check if override for color is yes in global settings or not. 
				if ($this->config->get('module_mppollglobal_usecolor') == 1) {

					// check if user want to allow chart colors to be override ?
					if ($poll_data['allowcoloroverride']==1) {
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
				
				if ( count($r_chartcolors) > 0 && count($r_chartcolors) < $data['maxanswers']) {
					// check how many elements we need.
					do {
						$total_r_chartcolor = count($r_chartcolors);
						// echo $total_chartcolor;
						$remaining = $data['maxanswers'] - $total_r_chartcolor;
						$j = $total_r_chartcolor;
						for ($i = 1; $i <= $remaining; $i++) {
							if ($i <= ($total_r_chartcolor - 1)) {
							$j++;
							// echo "\n\n i : ".$i . "\n\n";
							// echo $chartcolors[$i]['chartcolor'];

							// can use answer_no
							$canuse = [];
							for ($ci = 1; $ci <= $data['maxanswers']; $ci++) {
								$canuse[$ci] = $ci;
							}
							

							// check here if answer no is not already in use. which answer no. to be assign.	

							// loop through and check answers_no in use.
							
							foreach ($r_chartcolors as $r_chartcolor) {
								unset( $canuse[ $r_chartcolor['answer_no'] ]);
							}

							$ncanuse = array_values($canuse);

							$r_chartcolors[$j-1] = [
								'chartcolor' => $r_chartcolors[$i]['chartcolor'],
								'answer_no' => $ncanuse[0],
							];
							// print_r($r_chartcolors);
							// die();
							}
						}	
					
					} while (count($r_chartcolors) < $data['maxanswers']);

					
				}
				// echo "\n\n\n after filling \n";
				// print_r($r_chartcolors);
				// die();
				// set default chart colors.
				if (count($r_chartcolors) <= 0) {
					$k = 1;
					$defcolor = ['0F151A', '0091BF', '328B3C'];
					$t_defcolor= count($defcolor) - 1;
					for ($i = 1; $i <= $data['maxanswers']; $i++) {
						
						$r_chartcolors[] = ['chartcolor' => $defcolor[$k], 'answer_no' => $i + 1];
						$k++;
						if ($k == $t_defcolor) {
							$k = 1;
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
				$data['question'] = htmlentities($poll_data['question']);
				$data['answers'] = $poll_data['answers'];
				$data['percent'] = $percent;
				$data['total_votes'] = $total_votes;
			}
		}

		// get chart height, width of admin.
		$data['chart_w'] = $this->config->get('module_mppollglobal_chartadmin_w');
		$data['chart_h'] = $this->config->get('module_mppollglobal_chartadmin_h');

		// set default chart width,  height in case of empty or null.
		if ($data['chart_w'] == '' || $data['chart_w'] == null) {
			$data['chart_w'] = 770;
		}
		if ($data['chart_h'] == '' || $data['chart_h'] == null) {
			$data['chart_h'] = 200;
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/mppoll/mppoll_form', $data));
	}

	protected function validateForm(): bool {
		if (!$this->user->hasPermission('modify', 'extension/mppoll/mppoll')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		$maxanswers = 8;
		
		foreach ($this->request->post['mppoll_description'] as $language_id => $value) {
			if (($this->model_extension_mppoll_mppoll->strlen($value['question']) < 3) || ($this->model_extension_mppoll_mppoll->strlen($value['question']) > 64)) {
				$this->error['question'][$language_id] = $this->language->get('error_question');
			}

			$filled_answers = 0;
			for ($i = 1; $i <= $maxanswers; $i++) {
				// check how many answers are filled
				if (!empty( $value['answer'][$i] ) ) {
					$filled_answers++;
				}
			}


			if ($filled_answers < 2) {
				
				for ($i = 1; $i <= 2; $i++) {
					$this->error['answer'][$language_id][$i] = sprintf($this->language->get('error_answer'), 2) ;
				}
			}
		}

		if ($this->error && !isset($this->error['warning'])) {
			$this->error['warning'] = $this->language->get('error_warning');
		}

		return !$this->error;
	}

	protected function validateDelete(): bool {
		if (!$this->user->hasPermission('modify', 'extension/mppoll/mppoll')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		return !$this->error;
	}

	public function autocomplete(): void {
		$json = [];

		if (isset($this->request->get['filter_question'])) {
			$this->load->model('extension/mppoll/mppoll');

			if (isset($this->request->get['filter_question'])) {
				$filter_question = $this->request->get['filter_question'];
			} else {
				$filter_question = '';
			}

			if (isset($this->request->get['limit'])) {
				$limit = $this->request->get['limit'];
			} else {
				$limit = 5;
			}

			$filter_data = [
				'filter_question'  => $filter_question,
				//'status'	=> 1,
				'start'        => 0,
				'limit'        => $limit
			];

			$results = $this->model_extension_mppoll_mppoll->getMppolls($filter_data);

			foreach ($results as $result) {
				$json[] = [
					'mppoll_id' => $result['mppoll_id'],
					'question'  => strip_tags(html_entity_decode($result['question'], ENT_QUOTES, 'UTF-8')),
				];
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}