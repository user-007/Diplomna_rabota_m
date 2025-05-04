<?php

namespace Opencart\Catalog\Model\Extension\Mppoll;

class Mppoll extends \Opencart\System\Engine\Model {
	public function getActiveMppoll(): array {
		$query = $this->db->query(" SELECT DISTINCT * FROM " . DB_PREFIX . "mppoll p LEFT JOIN " . DB_PREFIX . "mppoll_to_store p2s ON (p.mppoll_id = p2s.mppoll_id) WHERE active = '1' AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");

		if ($query->row) {
			$query->row['successmsg'] = json_decode($query->row['successmsg'], 1);
		}

		return $query->row;
	}

	public function getMppoll($mppoll_id): array {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "mppoll p LEFT JOIN " . DB_PREFIX . "mppoll_description pd ON (p.mppoll_id = pd.mppoll_id) LEFT JOIN " . DB_PREFIX . "mppoll_to_store p2s ON (p.mppoll_id = p2s.mppoll_id) WHERE p.status='1' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND p.mppoll_id = '" . (int)$mppoll_id . "'");

		if ($query->row) {
			$query->row['successmsg'] = json_decode($query->row['successmsg'], 1);
		}

		return $query->row;
	}

	public function getMppollHasVotes($mppoll_id): array {
		$query = $this->db->query("
			SELECT * 
			FROM " . DB_PREFIX . "mppoll_hasvotes pr
			LEFT JOIN " . DB_PREFIX . "mppoll_to_store p2s ON (pr.mppoll_id = p2s.mppoll_id)
			WHERE pr.mppoll_id = '" . (int)$mppoll_id . "' AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'
		");

		return $query->rows;
	}

	public function getTotalVotes($mppoll_id): int {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "mppoll_hasvotes pr LEFT JOIN " . DB_PREFIX . "mppoll_to_store p2s ON (pr.mppoll_id = p2s.mppoll_id) WHERE pr.mppoll_id = '" . (int)$mppoll_id . "' AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");

		return $query->row['total'];
	}

	public function addHasVote($data): int {
		$this->db->query("INSERT INTO " . DB_PREFIX . "mppoll_hasvotes SET mppoll_id = '" . (int)$data['id'] . "', answer = '" . (int)$data['answer_id'] . "', status='1', `ip` = '" . $this->db->escape($this->request->server['REMOTE_ADDR']) . "', date_added=now()");

		return $this->db->getLastId();
	}

	public function getMppolls($data = []): array {
		$sql = "SELECT * FROM " . DB_PREFIX . "mppoll p LEFT JOIN " . DB_PREFIX . "mppoll_description pd ON (p.mppoll_id = pd.mppoll_id) LEFT JOIN " . DB_PREFIX . "mppoll_to_store p2s ON (p.mppoll_id = p2s.mppoll_id) WHERE p.status='1' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND p.status = '1'";

		if (!empty($data['mppoll_ids'])) {
			$sql .= " AND p.mppoll_id IN ('". implode("', '", $data['mppoll_ids']) ."')";
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getMppollLayoutId($mppoll_id): int {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "mppoll_to_layout WHERE mppoll_id = '" . (int)$mppoll_id . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");

		if ($query->num_rows) {
			return $query->row['layout_id'];
		} else {
			return 0;
		}
	}
}
