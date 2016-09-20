<?php if (!defined('BASEPATH')) exit('No direct access allowed');

class Languages extends Admin_Controller
{

	public $filter = array(
		'filter_search' => '',
		'filter_status' => '',
	);

	public $default_sort = array('language_id', 'DESC');

	public $sort = array('name', 'code');

	public function __construct() {
		parent::__construct();

		$this->user->restrict('Site.Languages');

		$this->load->model('Languages_model');
		$this->load->model('Image_tool_model');

		$this->load->helper('language');

		$this->lang->load('languages');
	}

	public function index() {
		if ($this->input->post('delete') AND $this->_deleteLanguage() === TRUE) {
			$this->redirect();
		}

		$this->template->setTitle($this->lang->line('text_title'));
		$this->template->setHeading($this->lang->line('text_heading'));
		$this->template->setButton($this->lang->line('button_new'), array('class' => 'btn btn-primary', 'href' => page_url() . '/edit'));
		$this->template->setButton($this->lang->line('button_delete'), array('class' => 'btn btn-danger', 'onclick' => 'confirmDelete();'));;
		$this->template->setButton($this->lang->line('button_icon_filter'), array('class' => 'btn btn-default btn-filter pull-right', 'data-toggle' => 'button'));

		$data = $this->getList();

		$this->template->render('languages', $data);
	}

	public function edit() {
		if ($this->input->post() AND $language_id = $this->_saveLanguage()) {
			$this->redirect($language_id);
		}

		$language_info = $this->Languages_model->getLanguage((int)$this->input->get('id'));

		$title = (isset($language_info['name'])) ? $language_info['name'] : $this->lang->line('text_new');
		$this->template->setTitle(sprintf($this->lang->line('text_edit_heading'), $title));
		$this->template->setHeading(sprintf($this->lang->line('text_edit_heading'), $title));

		$this->template->setButton($this->lang->line('button_save'), array('class' => 'btn btn-primary', 'onclick' => '$(\'#edit-form\').submit();'));
		$this->template->setButton($this->lang->line('button_save_close'), array('class' => 'btn btn-default', 'onclick' => 'saveClose();'));
		$this->template->setButton($this->lang->line('button_icon_back'), array('class' => 'btn btn-default', 'href' => site_url('languages')));

		$data = $this->getForm($language_info);

		$this->template->render('languages_edit', $data);
	}

	public function getList() {
		$data = array_merge($this->getFilter(), $this->getSort());

		$data['language_id'] = $this->config->item('language_id');

		$data['languages'] = array();
		$results = $this->Languages_model->paginate($this->getFilter());
		foreach ($results->list as $result) {
			$data['languages'][] = array_merge($result, array(
				'image' => (!empty($result['image'])) ? $this->Image_tool_model->resize($result['image']) : $this->Image_tool_model->resize('data/flags/no_flag.png'),
				'edit'  => $this->pageUrl($this->edit_url, array('id' => $result['language_id'])),
			));
		}

		$data['pagination'] = $results->pagination;

		return $data;
	}

	public function getForm($language_info = array()) {
		$data = $language_info;

		$language_id = 0;
		$data['_action'] = $this->pageUrl($this->create_url);
		if (!empty($language_info['language_id'])) {
			$language_id = $language_info['language_id'];
			$data['_action'] = $this->pageUrl($this->edit_url, array('id' => $language_info['language_id']));
		}

		if ($language_id === '11') {
			$this->alert->set('info', $this->lang->line('alert_caution_edit'));
		}

		$data['language_id'] = $language_info['language_id'];
		$data['name'] = $language_info['name'];
		$data['code'] = $language_info['code'];
		$data['idiom'] = $language_info['idiom'];
		$data['can_delete'] = $language_info['can_delete'];
		$data['status'] = $language_info['status'];
		$data['no_photo'] = $this->Image_tool_model->resize('data/flags/no_flag.png');

		$data['image'] = array();
		if ($this->input->post('image')) {
			$data['image']['path'] = $this->Image_tool_model->resize($this->input->post('image'));
			$data['image']['name'] = basename($this->input->post('image'));
			$data['image']['input'] = $this->input->post('image');
		} else if (!empty($language_info['image'])) {
			$data['image']['path'] = $this->Image_tool_model->resize($language_info['image']);
			$data['image']['name'] = basename($language_info['image']);
			$data['image']['input'] = $language_info['image'];
		} else {
			$data['image']['path'] = $this->Image_tool_model->resize('data/flags/no_flag.png');
			$data['image']['name'] = 'no_flag.png';
			$data['image']['input'] = 'data/flags/no_flag.png';
		}

		$data['close_edit_link'] = $this->pageUrl($this->edit_url, array('id' => $language_info['language_id']));
		$data['lang_file'] = $this->input->get('file');
		$data['lang_location'] = $this->input->get('location');

		$data['lang_files'] = array();
		$data['lang_file_values'] = array();
		if (!empty($language_info['idiom']) AND $lang_files = list_lang_files($language_info['idiom'])) {
			foreach ($lang_files as $location => $files) {
				if (!empty($files)) foreach ($files as $file) {
					$data['lang_files'][$location][] = array(
						'name' => $file,
						'edit' => $this->pageUrl($this->create_url . '?id=' . $language_id . '&location=' . $location . '&file=' . $file),
					);
				}
			}

			if (!empty($data['lang_file'])) {
				if ($lang_file_values = load_lang_file($this->input->get('file'), $language_info['idiom'], $this->input->get('location'))) {
					foreach ($lang_file_values as $key => $value) {
						$data['lang_file_values'][$key] = $value;
					}
				}
			}
		}

		$data['languages'] = $this->Languages_model->isEnabled()->dropdown('idiom', 'name');

		return $data;
	}

	protected function _saveLanguage() {
		if ($this->validateForm() === TRUE) {
			$save_type = (!is_numeric($this->input->get('id'))) ? $this->lang->line('text_added') : $this->lang->line('text_updated');

			if ($language_id = $this->Languages_model->saveLanguage($this->input->get('id'), $this->input->post())) {
				$this->alert->set('success', sprintf($this->lang->line('alert_success'), 'Language ' . $save_type));

				if ($this->input->post('clone_language') === '1') {
					if (!clone_language($this->input->post('idiom'), $this->input->post('language_to_clone'))) {
						$this->alert->set('warning', sprintf($this->lang->line('alert_error_nothing'), $this->lang->line('text_cloned')));
					}
				}

				if (is_numeric($this->input->get('id')) AND $this->input->get('file')) {
					if (!save_lang_file($this->input->get('file'), $this->input->post('idiom'), $this->input->get('location'), $this->input->post('lang'))) {
						$this->alert->set('warning', sprintf($this->lang->line('alert_warning_file'), $save_type));
					}
				}
			} else {
				$this->alert->set('warning', sprintf($this->lang->line('alert_error_nothing'), $save_type));
			}

			return $language_id;
		}
	}

	protected function _deleteLanguage() {
		if ($this->input->post('delete')) {
			$deleted_rows = $this->Languages_model->deleteLanguage($this->input->post('delete'));

			if ($deleted_rows > 0) {
				$prefix = ($deleted_rows > 1) ? '[' . $deleted_rows . '] Languages' : 'Language';
				$this->alert->set('success', sprintf($this->lang->line('alert_success'), $prefix . ' ' . $this->lang->line('text_deleted')));
			} else {
				$this->alert->set('warning', sprintf($this->lang->line('alert_error_nothing'), $this->lang->line('text_deleted')));
			}

			return TRUE;
		}
	}

	protected function validateForm() {
		$rules[] = array('name', 'lang:label_name', 'xss_clean|trim|required|min_length[2]|max_length[32]');
		$rules[] = array('code', 'lang:label_code', 'xss_clean|trim|required|min_length[2]');
		$rules[] = array('image', 'lang:label_image', 'xss_clean|trim|required|min_length[2]|max_length[32]');

		if ($this->input->post('clone_language') === '1') {
			$rules[] = array('idiom', 'lang:label_idiom', 'xss_clean|trim|required|min_length[2]|max_length[32]');
			$rules[] = array('language_to_clone', 'lang:label_language', 'xss_clean|trim|required|alpha');
		} else {
			$rules[] = array('idiom', 'lang:label_idiom', 'xss_clean|trim|required|min_length[2]|max_length[32]|callback__valid_idiom');
		}

		$rules[] = array('can_delete', 'lang:label_can_delete', 'xss_clean|trim|required|integer');
		$rules[] = array('status', 'lang:label_status', 'xss_clean|trim|required|integer');

		return $this->Languages_model->set_rules($rules)->validate();
	}

	public function _valid_idiom($str) {
		$lang_files = list_lang_files($str);
		if (empty($lang_files['admin']) AND empty($lang_files['main']) AND empty($lang_files['module'])) {
			$this->form_validation->set_message('_valid_idiom', $this->lang->line('error_invalid_idiom'));

			return FALSE;
		} else {                                                                                // else validation is not successful
			return TRUE;
		}
	}
}

/* End of file Languages.php */
/* Location: ./admin/controllers/Languages.php */