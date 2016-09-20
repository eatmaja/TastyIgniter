<?php if (!defined('BASEPATH')) exit('No direct access allowed');

class Menu_options extends Admin_Controller
{

	public $filter = array(
		'filter_search'       => '',
		'filter_display_type' => '',
	);

	public $default_sort = array('option_id', 'DESC');

	public $sort = array('option_name', 'priority', 'display_type', 'option_id');

	public function __construct() {
		parent::__construct(); //  calls the constructor

		$this->user->restrict('Admin.MenuOptions');

		$this->load->model('Menu_options_model'); // load the menus model

		$this->load->library('currency'); // load the currency library

		$this->lang->load('menu_options');
	}

	public function index() {
		if ($this->input->post('delete') AND $this->_deleteMenuOption() === TRUE) {
			$this->redirect();
		}

		$this->template->setTitle($this->lang->line('text_title'));
		$this->template->setHeading($this->lang->line('text_heading'));

		$this->template->setButton($this->lang->line('button_new'), array('class' => 'btn btn-primary', 'href' => page_url() . '/edit'));
		$this->template->setButton($this->lang->line('button_delete'), array('class' => 'btn btn-danger', 'onclick' => 'confirmDelete();'));;
		$this->template->setButton($this->lang->line('button_icon_filter'), array('class' => 'btn btn-default btn-filter pull-right', 'data-toggle' => 'button'));

		$data = $this->getList();

		$this->template->render('menu_options', $data);
	}

	public function edit() {
		if ($this->input->post() AND $option_id = $this->_saveOption()) {
			$this->redirect($option_id);
		}

		$option_info = $this->Menu_options_model->getOption((int)$this->input->get('id'));

		$title = (isset($option_info['option_name'])) ? $option_info['option_name'] : $this->lang->line('text_new');
		$this->template->setTitle(sprintf($this->lang->line('text_edit_heading'), $title));
		$this->template->setHeading(sprintf($this->lang->line('text_edit_heading'), $title));

		$this->template->setButton($this->lang->line('button_save'), array('class' => 'btn btn-primary', 'onclick' => '$(\'#edit-form\').submit();'));
		$this->template->setButton($this->lang->line('button_save_close'), array('class' => 'btn btn-default', 'onclick' => 'saveClose();'));
		$this->template->setButton($this->lang->line('button_icon_back'), array('class' => 'btn btn-default', 'href' => site_url('menu_options')));

		$this->assets->setScriptTag(assets_url('js/jquery-sortable.js'), 'jquery-sortable-js');

		$data = $this->getForm($option_info);

		$this->template->render('menu_options_edit', $data);
	}

	public function autocomplete() {
		$json = array();

		if ($this->input->get('term')) {
			$results = $this->Menu_options_model->getAutoComplete(array('option_name' => $this->input->get('term')));
			if ($results) {
				foreach ($results as $result) {
					$json['results'][] = array(
						'id'            => $result['option_id'],
						'text'          => utf8_encode($result['option_name']),
						'display_type'  => utf8_encode($result['display_type']),
						'priority'      => $result['priority'],
						'option_values' => $result['option_values'],
					);
				}
			} else {
				$json['results'] = array('id' => '0', 'text' => $this->lang->line('text_no_match'));
			}
		}

		$this->output->set_output(json_encode($json));
	}

	public function getList() {
		$data = array_merge($this->getFilter(), $this->getSort());

		$data['menu_options'] = array();
		$results = $this->Menu_options_model->paginate($this->getFilter());
		foreach ($results->list as $result) {
			$data['menu_options'][] = array_merge($result, array(
				'display_type' => ucwords($result['display_type']),
				'edit'         => $this->pageUrl($this->edit_url, array('id' => $result['option_id'])),
			));
		}

		$data['pagination'] = $results->pagination;

		return $data;
	}

	public function getForm($option_info = array()) {
		$data = $option_info;

		$option_id = 0;
		$data['_action'] = $this->pageUrl($this->create_url);
		if (!empty($option_info['option_id'])) {
			$option_id = $option_info['option_id'];
			$data['_action'] = $this->pageUrl($this->edit_url, array('id' => $option_id));
		}

		$data['option_id'] = $option_info['option_id'];
		$data['option_name'] = $option_info['option_name'];
		$data['display_type'] = $option_info['display_type'];
		$data['priority'] = $option_info['priority'];

		if ($this->input->post('option_values')) {
			$data['values'] = $this->input->post('option_values');
		} else {
			$data['values'] = $this->Menu_options_model->getOptionValues($option_id);
		}

		return $data;
	}

	protected function _saveOption() {
		if ($this->validateForm() === TRUE) {
			$save_type = (!is_numeric($this->input->get('id'))) ? $this->lang->line('text_added') : $this->lang->line('text_updated');
			if ($option_id = $this->Menu_options_model->saveOption($this->input->get('id'), $this->input->post())) {
				$this->alert->set('success', sprintf($this->lang->line('alert_success'), 'Menu option ' . $save_type));
			} else {
				$this->alert->set('warning', sprintf($this->lang->line('alert_error_nothing'), $save_type));
			}

			return $option_id;
		}
	}

	protected function _deleteMenuOption() {
		if ($this->input->post('delete')) {
			$deleted_rows = $this->Menu_options_model->deleteOption($this->input->post('delete'));
			if ($deleted_rows > 0) {
				$prefix = ($deleted_rows > 1) ? '[' . $deleted_rows . '] Menu options' : 'Menu option';
				$this->alert->set('success', sprintf($this->lang->line('alert_success'), $prefix . ' ' . $this->lang->line('text_deleted')));
			} else {
				$this->alert->set('warning', sprintf($this->lang->line('alert_error_nothing'), $this->lang->line('text_deleted')));
			}

			return TRUE;
		}
	}

	protected function validateForm() {
		$rules[] = array('option_name', 'lang:label_option_name', 'xss_clean|trim|required|min_length[2]|max_length[32]');
		$rules[] = array('display_type', 'lang:label_display_type', 'xss_clean|trim|required|alpha');
		$rules[] = array('priority', 'lang:label_priority', 'xss_clean|trim|required|integer');

		if ($this->input->post('option_values')) {
			foreach ($this->input->post('option_values') as $key => $value) {
				$rules[] = array('option_values[' . $key . '][value]', 'lang:label_option_value', 'xss_clean|trim|required|min_length[2]|max_length[128]');
				$rules[] = array('option_values[' . $key . '][price]', 'lang:label_option_price', 'xss_clean|trim|required|numeric');
			}
		}

		return $this->Menu_options_model->set_rules($rules)->validate();
	}
}

/* End of file Menu_options.php */
/* Location: ./admin/controllers/Menu_options.php */