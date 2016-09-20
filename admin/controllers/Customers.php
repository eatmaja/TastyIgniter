<?php if (!defined('BASEPATH')) exit('No direct access allowed');

class Customers extends Admin_Controller
{
	public $filter = array(
		'filter_search' => '',
		'filter_date'   => '',
		'filter_status' => '',
	);

	public $default_sort = array('date_added', 'DESC');

	public $sort = array('first_name', 'last_name', 'email', 'date_added', 'customer_id');

	public function __construct() {
		parent::__construct(); //  calls the constructor

		$this->user->restrict('Admin.Customers');

		$this->load->model('Customers_model');

		$this->lang->load('customers');
	}

	public function index() {
		if ($this->input->post('delete') AND $this->_deleteCustomer() === TRUE) {
			$this->redirect();
		}

		$this->template->setTitle($this->lang->line('text_title'));
		$this->template->setHeading($this->lang->line('text_heading'));
		$this->template->setButton($this->lang->line('button_new'), array('class' => 'btn btn-primary', 'href' => page_url() . '/edit'));
		$this->template->setButton($this->lang->line('button_delete'), array('class' => 'btn btn-danger', 'onclick' => 'confirmDelete();'));;
		$this->template->setButton($this->lang->line('button_icon_filter'), array('class' => 'btn btn-default btn-filter pull-right', 'data-toggle' => 'button'));

		$data = $this->getList();

		$this->template->render('customers', $data);
	}

	public function edit() {
		$customer_info = $this->Customers_model->getCustomer((int)$this->input->get('id'));

		if ($this->input->post() AND $customer_id = $this->_saveCustomer($customer_info['email'])) {
			$this->redirect($customer_id);
		}

		$title = (isset($customer_info['first_name']) AND isset($customer_info['last_name'])) ? $customer_info['first_name'] . ' ' . $customer_info['last_name'] : $this->lang->line('text_new');
		$this->template->setTitle(sprintf($this->lang->line('text_edit_heading'), $title));
		$this->template->setHeading(sprintf($this->lang->line('text_edit_heading'), $title));
		$this->template->setButton($this->lang->line('button_save'), array('class' => 'btn btn-primary', 'onclick' => '$(\'#edit-form\').submit();'));
		$this->template->setButton($this->lang->line('button_save_close'), array('class' => 'btn btn-default', 'onclick' => 'saveClose();'));
		$this->template->setButton($this->lang->line('button_icon_back'), array('class' => 'btn btn-default', 'href' => site_url('customers')));

		$data = $this->getForm($customer_info);

		$this->template->render('customers_edit', $data);
	}

	public function login() {
		$customer_info = $this->Customers_model->getCustomer((int)$this->input->get('id'));

		if (!$this->user->canAccessCustomerAccount()) {
			$this->alert->set('warning', $this->lang->line('alert_login_restricted'));
		} else if ($customer_info) {
			$customer_id = $customer_info['customer_id'];

			$this->load->library('customer');
			$this->load->library('cart');

			$this->customer->logout();
			$this->cart->destroy();

			if ($this->customer->login($customer_info['email'], '', TRUE)) {
				log_activity($customer_id, 'logged in', 'customers', get_activity_message('activity_master_logged_in',
					array('{staff}', '{staff_link}', '{customer}', '{customer_link}'),
					array($this->user->getStaffName(), admin_url('staffs/edit?id=' . $this->user->getId()), $this->customer->getName(), $this->pageUrl($this->edit_url, array('id' => $customer_id)))
				));

				$this->redirect(root_url('account/account'));
			}
		}

		$this->redirect();
	}

	public function autocomplete() {
		$json = array();

		if ($this->input->get('term') OR $this->input->get('customer_id')) {
			$filter['customer_name'] = $this->input->get('term');
			$filter['customer_id'] = $this->input->get('customer_id');

			$results = $this->Customers_model->getAutoComplete($filter);

			if ($results) {
				foreach ($results as $result) {
					$json['results'][] = array(
						'id'   => $result['customer_id'],
						'text' => utf8_encode($result['first_name'] . ' ' . $result['last_name']),
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

		$data['country_id'] = $this->config->item('country_id');
		$data['access_customer_account'] = $this->user->canAccessCustomerAccount();

		$data['customers'] = array();
		$results = $this->Customers_model->paginate($this->getFilter());
		foreach ($results->list as $result) {
			$data['customers'][] = array_merge($result, array(
				'date_added' => day_elapsed($result['date_added']),
				'login'      => $this->pageUrl($this->index_url . '/login?id=' . $result['customer_id']),
				'edit'       => $this->pageUrl($this->edit_url, array('id' => $result['customer_id'])),
			));
		}

		$data['pagination'] = $results->pagination;

		$customer_dates = $this->Customers_model->getCustomerDates();
		foreach ($customer_dates as $customer_date) {
			$month_year = $customer_date['year'] . '-' . $customer_date['month'];
			$data['customer_dates'][$month_year] = mdate('%F %Y', strtotime($customer_date['date_added']));
		}

		return $data;
	}

	public function getForm($customer_info = array()) {
		$data = $customer_info;

		$data['_action'] = $this->pageUrl($this->create_url);
		if (!empty($customer_info['customer_id'])) {
			$data['_action'] = $this->pageUrl($this->edit_url, array('id' => $customer_info['customer_id']));
		}

		$data['first_name'] = $customer_info['first_name'];
		$data['last_name'] = $customer_info['last_name'];
		$data['email'] = $customer_info['email'];
		$data['telephone'] = $customer_info['telephone'];
		$data['security_question'] = $customer_info['security_question_id'];
		$data['security_answer'] = $customer_info['security_answer'];
		$data['newsletter'] = $customer_info['newsletter'];
		$data['customer_group_id'] = (!empty($customer_info['customer_group_id'])) ? $customer_info['customer_group_id'] : $this->config->item('customer_group_id');
		$data['status'] = $customer_info['status'];
		$data['country_id'] = $this->config->item('country_id');

		$this->load->model('Addresses_model');
		if ($this->input->post('address')) {
			$data['addresses'] = $this->input->post('address');
		} else {
			$data['addresses'] = $this->Addresses_model->getAddresses($customer_info['customer_id']);
		}

		$this->load->model('Security_questions_model');
		$data['questions'] = $this->Security_questions_model->dropdown('text');

		$this->load->model('Customer_groups_model');
		$data['customer_groups'] = $this->Customer_groups_model->dropdown('group_name');

		$this->load->model('Countries_model');
		$data['countries'] = $this->Countries_model->isEnabled()->dropdown('country_name');

		return $data;
	}

	protected function _saveCustomer($customer_email) {
		if ($this->validateForm($customer_email) === TRUE) {
			$save_type = (!is_numeric($this->input->get('id'))) ? $this->lang->line('text_added') : $this->lang->line('text_updated');
			if ($customer_id = $this->Customers_model->saveCustomer($this->input->get('id'), $this->input->post())) {
				$customer_name = $this->input->post('first_name') . ' ' . $this->input->post('last_name');

				log_activity($this->user->getStaffId(), $save_type, 'customers', get_activity_message('activity_custom',
					array('{staff}', '{action}', '{context}', '{link}', '{item}'),
					array($this->user->getStaffName(), $save_type, 'customer', $this->pageUrl($this->edit_url, array('id' => $customer_id)), $customer_name)
				));

				$this->alert->set('success', sprintf($this->lang->line('alert_success'), 'Customer ' . $save_type));
			} else {
				$this->alert->set('warning', sprintf($this->lang->line('alert_error_nothing'), $save_type));
			}

			return $customer_id;
		}
	}

	protected function validateForm($customer_email = FALSE) {
		$rules[] = array('first_name', 'lang:label_first_name', 'xss_clean|trim|required|min_length[2]|max_length[32]');
		$rules[] = array('last_name', 'lang:label_last_name', 'xss_clean|trim|required|min_length[2]|max_length[32]');

		if ($customer_email !== $this->input->post('email')) {
			$rules[] = array('email', 'lang:label_email', 'xss_clean|trim|required|valid_email|max_length[96]|is_unique[customers.email]');
		}

		if ($this->input->post('password')) {
			$rules[] = array('password', 'lang:label_password', 'xss_clean|trim|required|min_length[6]|max_length[40]|matches[confirm_password]');
			$rules[] = array('confirm_password', 'lang:label_confirm_password', 'xss_clean|trim|required');
		}

		$rules[] = array('telephone', 'lang:label_telephone', 'xss_clean|trim|required|integer');
		$rules[] = array('security_question_id', 'lang:label_security_question', 'xss_clean|trim|required|integer');
		$rules[] = array('security_answer', 'lang:label_security_answer', 'xss_clean|trim|required|min_length[2]');
		$rules[] = array('newsletter', 'lang:label_newsletter', 'xss_clean|trim|required|integer');
		$rules[] = array('customer_group_id', 'lang:label_customer_group', 'xss_clean|trim|required|integer');
		$rules[] = array('status', 'lang:label_status', 'xss_clean|trim|required|integer');

		if ($this->input->post('address')) {
			foreach ($this->input->post('address') as $key => $value) {
				$rules[] = array('address[' . $key . '][address_1]', '[' . $key . '] lang:label_address_1', 'xss_clean|trim|required|min_length[3]|max_length[128]');
				$rules[] = array('address[' . $key . '][city]', '[' . $key . '] lang:label_city', 'xss_clean|trim|required|min_length[2]|max_length[128]');
				$rules[] = array('address[' . $key . '][state]', '[' . $key . '] lang:label_state', 'xss_clean|trim|max_length[128]');
				$rules[] = array('address[' . $key . '][postcode]', '[' . $key . '] lang:label_postcode', 'xss_clean|trim|min_length[2]|max_length[10]');
				$rules[] = array('address[' . $key . '][country_id]', '[' . $key . '] lang:label_country', 'xss_clean|trim|required|integer');
			}
		}

		return $this->Customers_model->set_rules($rules)->validate();
	}

	protected function _deleteCustomer() {
		if ($this->input->post('delete')) {
			$deleted_rows = $this->Customers_model->deleteCustomer($this->input->post('delete'));
			if ($deleted_rows > 0) {
				$prefix = ($deleted_rows > 1) ? '[' . $deleted_rows . '] Customers' : 'Customer';
				$this->alert->set('success', sprintf($this->lang->line('alert_success'), $prefix . ' ' . $this->lang->line('text_deleted')));
			} else {
				$this->alert->set('warning', sprintf($this->lang->line('alert_error_nothing'), $this->lang->line('text_deleted')));
			}

			return TRUE;
		}
	}
}

/* End of file Customers.php */
/* Location: ./admin/controllers/Customers.php */