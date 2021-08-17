<?php
class ModelExtensionSticker extends Model {
	public function getSticker($product_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "sticker s LEFT JOIN " . DB_PREFIX . "product_to_store pts ON ( s.product_id = pts.product_id AND s.store_id = pts.store_id ) WHERE s.product_id = '" . (int)$product_id . "' AND s.store_id = '" . (int)$this->config->get('config_store_id') . "'");

		if ($query->num_rows) {
			return array(
				'topleft_png'       => $query->row['topleft_png'],
				'topright_png'      => $query->row['topright_png'],
				'bottomleft_png'    => $query->row['bottomleft_png'],
				'bottomright_png'   => $query->row['bottomright_png'],
			);
		} else {
			return false;
		}
	}


}
