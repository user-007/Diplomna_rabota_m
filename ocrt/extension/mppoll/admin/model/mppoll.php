<?php

namespace Opencart\Admin\Model\Extension\Mppoll;

class Mppoll extends \Opencart\System\Engine\Model {
	public function addMppoll($data): int {
		
		$this->db->query("INSERT INTO " . DB_PREFIX . "mppoll SET status = '" . (int)$data['status'] . "', sort_order = '" . (int)$data['sort_order'] . "', maxanswers=8, active = '1', date_added = now(), useglobalcolor = '". (int)$data['useglobalcolor'] ."', allowcoloroverride = '". (int)$data['allowcoloroverride'] ."', useglobalsuccessmsg = '". (int)$data['useglobalsuccessmsg'] ."', allowsuccessmsgoverride = '". (int)$data['allowsuccessmsgoverride'] ."', successmsg = '". $this->db->escape(json_encode( $data['successmsg']) ) ."'");
		
		$mppoll_id = $this->db->getLastId();

		foreach ($data['mppoll_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "mppoll_description SET mppoll_id = '" . (int)$mppoll_id . "', language_id = '" . (int)$language_id . "', `question` = '" . $this->db->escape($value['question']) . "', `answers` = '" . $this->db->escape(json_encode($value['answer'])) . "'");
		}

		if (!empty($data['chartcolors'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "mppoll SET  `chartcolors` = '" . $this->db->escape(json_encode($data['chartcolors'])) . "' WHERE mppoll_id='". $mppoll_id ."'");
		}

		if (isset($data['mppoll_store'])) {
			foreach ($data['mppoll_store'] as $store_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "mppoll_to_store SET mppoll_id = '" . (int)$mppoll_id . "', store_id = '" . (int)$store_id . "'");
			}
		}

		return $mppoll_id;
	}

	public function editMppoll($mppoll_id, $data): void {

		$this->db->query("UPDATE " . DB_PREFIX . "mppoll SET status = '" . (int)$data['status'] . "', active = '1', sort_order = '" . (int)$data['sort_order'] . "', maxanswers=8, date_modified = now(), useglobalcolor = '". (int)$data['useglobalcolor'] ."', allowcoloroverride = '". (int)$data['allowcoloroverride'] ."', useglobalsuccessmsg = '". (int)$data['useglobalsuccessmsg'] ."', allowsuccessmsgoverride = '". (int)$data['allowsuccessmsgoverride'] ."', successmsg = '". $this->db->escape(json_encode( $data['successmsg']) ) ."' WHERE mppoll_id = '" . (int)$mppoll_id . "'");
		
		if (!empty($data['chartcolors'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "mppoll SET  `chartcolors` = '" . $this->db->escape(json_encode($data['chartcolors'])) . "' WHERE mppoll_id='". $mppoll_id ."'");
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "mppoll_description WHERE mppoll_id = '" . (int)$mppoll_id . "'");
		
		foreach ($data['mppoll_description'] as $language_id => $value) {

			$this->db->query("INSERT INTO " . DB_PREFIX . "mppoll_description SET mppoll_id = '" . (int)$mppoll_id . "', language_id = '" . (int)$language_id . "', `question` = '" . $this->db->escape($value['question']) . "', `answers` = '" . $this->db->escape(json_encode($value['answer'])) . "'");
		}


		$this->db->query("DELETE FROM " . DB_PREFIX . "mppoll_to_store WHERE mppoll_id = '" . (int)$mppoll_id . "'");
		if (isset($data['mppoll_store'])) {
			foreach ($data['mppoll_store'] as $store_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "mppoll_to_store SET mppoll_id = '" . (int)$mppoll_id . "', store_id = '" . (int)$store_id . "'");
			}
		}

	}

	public function deleteMppoll($mppoll_id): void {

		$this->db->query("DELETE FROM " . DB_PREFIX . "mppoll WHERE mppoll_id = '" . (int)$mppoll_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "mppoll_description WHERE mppoll_id = '" . (int)$mppoll_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "mppoll_hasvotes WHERE mppoll_id = '" . (int)$mppoll_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "mppoll_to_store WHERE mppoll_id = '" . (int)$mppoll_id . "'");
		
	}	

	public function getMppoll($mppoll_id): array {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "mppoll p LEFT JOIN " . DB_PREFIX . "mppoll_description pd ON (p.mppoll_id = pd.mppoll_id) LEFT JOIN " . DB_PREFIX . "mppoll_to_store p2s ON (p.mppoll_id = p2s.mppoll_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND p.mppoll_id = '" . (int)$mppoll_id . "'");

		if ($query->row) {
			$query->row['successmsg'] =  json_decode($query->row['successmsg'] ,1);
		}
		
		return $query->row;
	}

	public function getMppollDescriptions($mppoll_id): array {
		$mppoll_description_data = [];
		
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "mppoll_description WHERE mppoll_id = '" . (int)$mppoll_id . "'");
		
		foreach ($query->rows as $result) {
			$mppoll_description_data[$result['language_id']] = [
				'question' => $result['question'],
				'answer' => json_decode($result['answers'],true),
				
			];
		}
		return $mppoll_description_data;
	}

	public function getMppolls($data = []): array {

		$sql = "SELECT * FROM " . DB_PREFIX . "mppoll p LEFT JOIN " . DB_PREFIX . "mppoll_description pd ON (p.mppoll_id = pd.mppoll_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' ";

		if (!empty($data['filter_question'])) {
			$sql .= " AND pd.question LIKE '" . $this->db->escape($data['filter_question']) . "%'";
		}

		if (isset($data['filter_status']) && !is_null($data['filter_status'])) {
			$sql .= " AND p.status = '" . (int)$data['filter_status'] . "'";
		}

		$sort_data = [
			'pd.question',
			'p.status',
			'p.date_added',
		];

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY pd.question";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}


		$query = $this->db->query($sql);
		return $query->rows;
	}

	public function getTotalMppolls($data = []): int {

		$sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "mppoll p LEFT JOIN " . DB_PREFIX . "mppoll_description pd ON (p.mppoll_id = pd.mppoll_id)";

		$sql .= " WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

		if (!empty($data['filter_question'])) {
			$sql .= " AND pd.question LIKE '" . $this->db->escape($data['filter_question']) . "%'";
		}

		if (isset($data['filter_status']) && !is_null($data['filter_status'])) {
			$sql .= " AND p.status = '" . (int)$data['filter_status'] . "'";
		}

     	$query = $this->db->query($sql);

		return $query->row['total'];
	}

	public function checkActiveMppoll(): int {
     	$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "mppoll WHERE active = '1'");
		return $query->row['total'];
	}

	public function getActiveMppoll(): array {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "mppoll WHERE active = '1'");
		return $query->row;
	}

	public function getMppollData($mppoll_id): array {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "mppoll p LEFT JOIN " . DB_PREFIX . "mppoll_description pd ON (p.mppoll_id = pd.mppoll_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.mppoll_id = '" . (int)$mppoll_id . "'");
		return $query->row;
	}

	public function getMppollResults($mppoll_id): array {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "mppoll_hasvotes WHERE mppoll_id = '" . (int)$mppoll_id . "'");
		return $query->rows;
	}

	public function getTotalVotes($mppoll_id): int {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "mppoll_hasvotes WHERE mppoll_id = '" . (int)$mppoll_id . "'");
		return $query->row['total'];
	}

	public function getMppollStores($mppoll_id): array {
		$mppoll_store_data = [];
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "mppoll_to_store WHERE mppoll_id = '" . (int)$mppoll_id . "'");
		foreach ($query->rows as $result) {
			$mppoll_store_data[] = $result['store_id'];
		}
		return $mppoll_store_data;
	}

	public function strlen(string $string) {
		if (VERSION < '4.0.2.0') {
			return \Opencart\System\Helper\Utf8\strlen($string);
		} else {
			return oc_strlen($string);
		}
	}
}
