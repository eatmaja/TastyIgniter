<?php
/**
 * TastyIgniter
 *
 * An open source online ordering, reservation and management system for restaurants.
 *
 * @package   TastyIgniter
 * @author    SamPoyigi
 * @copyright (c) 2013 - 2016. TastyIgniter
 * @link      http://tastyigniter.com
 * @license   http://opensource.org/licenses/GPL-3.0 The GNU GENERAL PUBLIC LICENSE
 * @since     File available since Release 1.0
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * TastyIgniter Form Validation Class
 *
 * @category       Libraries
 * @package        TastyIgniter\Libraries\TI_Form_validation.php
 * @link           http://docs.tastyigniter.com
 */
class TI_Form_validation extends CI_Form_validation
{
	public $CI;
	protected $_old_field_data = [];
	protected $_old_error_array = [];
	protected $_old_error_messages = [];
	protected $old_error_string = '';

	public function set_rules($field, $label = '', $rules = [], $errors = [])
	{
		if (is_array($field)) {
			foreach ($field as $rule) {
				if (!isset($rule['field'])) {
					if (isset($rule[0]))
						$temp_rule['field'] = $rule[0];
					if (isset($rule[1]))
						$temp_rule['label'] = $rule[1];
					if (isset($rule[2]))
						$temp_rule['rules'] = $rule[2];

					$rule = $temp_rule;
				}

				$_field[] = $rule;
			}

			$field = !empty($_field) ? $_field : $field;
		}

		return parent::set_rules($field, $label, $rules, $errors);
	}

	/**
	 * Is Unique
	 *
	 * Check if the input value doesn't already exist
	 * in the specified database field.
	 *
	 * @param    string $str
	 * @param    string $field
	 *
	 * @return    bool
	 */
	public function is_unique($str, $field)
	{
		sscanf($field, '%[^.].%[^.]', $table, $field);

		return isset($this->CI->db)
			? ($this->CI->db->limit(1)->get_where($table, [$field => $str])->num_rows() === 0)
			: FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * Valid Time
	 *
	 * @access  public
	 *
	 * @param   string
	 *
	 * @return  bool
	 */
	public function valid_time($str)
	{
		return (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $str) AND !preg_match('/^(1[012]|[1-9]):[0-5][0-9](\s)?(?i)(am|pm)$/', $str)) ? FALSE : TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Valid Date
	 *
	 * @access  public
	 *
	 * @param   string
	 *
	 * @return  bool
	 */
	public function valid_date($str)
	{
		if ($str != '0000-00-00' AND $str != '00-00-0000') {
			return (!preg_match('/^(0[1-9]|[1-2][0-9]|3[0-1])-(0[1-9]|1[0-2])-[0-9]{4}$/', $str) AND !preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/', $str)) ? FALSE : TRUE;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Valid Date
	 *
	 * @access  public
	 *
	 * @param        $str
	 * @param string $post_item
	 *
	 * @return bool
	 */
	public function get_lat_lng($str, $post_item = 'address')
	{
		if (!empty($str) AND $post_data = $this->CI->input->post($post_item)) {
			if (is_array($post_data) AND !empty($post_data['address_1'])) {
				$this->CI->load->library('country');
				$post_data['country'] = $this->CI->country->getCountryNameById($post_data['country']);
				unset($post_data['location_lat'], $post_data['location_lng']);

				$this->CI->load->library('location_geocode');
				$position = $this->CI->location_geocode->geocodePosition(implode(", ", $post_data));

				if (!empty($position->status) AND $position->status == 'OK') {
					$_POST[$post_item]['location_lat'] = $position->lat;
					$_POST[$post_item]['location_lng'] = $position->lng;

					return TRUE;
				}
			} else {
				return FALSE;
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * XSS Clean
	 *
	 * @param    string
	 *
	 * @return    string
	 */
	public function xss_clean($str)
	{
		return $this->CI->security->xss_clean($str);
	}

	// --------------------------------------------------------------------

	/**
	 * Translate a field name
	 *
	 * @param    string    the field name
	 *
	 * @return    string
	 */
	protected function _translate_fieldname($fieldname)
	{
		// Do we need to translate the field name?
		// We look for the prefix lang: to determine this
		if (sscanf($fieldname, 'lang:%s', $line) === 1) {
			// Were we able to translate the field name?  If not we use $line
			if (FALSE === ($fieldname = $this->CI->lang->line('form_validation_' . $line, FALSE))
				// added DEPRECATED support for non-prefixed keys to be used within TI
				&& FALSE === ($fieldname = $this->CI->lang->line($line, FALSE))
			) {
				return $line;
			}
		}

		return $fieldname;
	}

	// --------------------------------------------------------------------

	public function error($field, $prefix = '', $suffix = '')
	{
		if (!empty($this->_old_field_data[$field]['error'])) {
			if ($prefix === '') {
				$prefix = $this->_error_prefix;
			}

			if ($suffix === '') {
				$suffix = $this->_error_suffix;
			}

			return $prefix . $this->_old_field_data[$field]['error'] . $suffix;
		}

		return parent::error($field, $prefix, $suffix);
	}

	// --------------------------------------------------------------------

	/**
	 * Reset validation vars
	 *
	 * Prevents subsequent validation routines from being affected by the
	 * results of any previous validation routine due to the CI singleton.
	 *
	 * @return    CI_Form_validation
	 */
	public function reset_validation()
	{
		$this->_old_field_data = $this->_field_data;
		$this->_old_error_array = $this->_error_array;
		$this->_old_error_messages = $this->_error_messages;
		$this->old_error_string = $this->error_string;

		$this->_field_data = [];
		$this->_config_rules = [];
		$this->_error_array = [];
		$this->_error_messages = [];
		$this->error_string = '';

		return $this;
	}

}

// END Form_validation Class

/* End of file Form_validation.php */
/* Location: ./system/tastyigniter/libraries/Form_validation.php */