<?php 
class Store extends MY_Controller {

    function __construct() {
        parent::__construct();
    }

    public function index() {
        Modules::run('secure_tings/is_logged_in');
        $data['module'] = "store";
        $data['view_file'] = "list_store_view";
        $data['section'] = "Configuration";
        $data['subtitle'] = "List Store";
        $data['page_title'] = "Store";
        $data['user_object'] = $this->get_user_object();
        $data['main_title'] = $this->get_title();
        $this->load->library('make_bread');
        $this->make_bread->add('Configurations', '', 0);
        $this->make_bread->add('List Stores', '', 0);
        $data['breadcrumb'] = $this->make_bread->output();
        //
        echo Modules::run('template/'.$this->redirect($this->session->userdata['logged_in']['user_group']), $data);
    }


    public function create() {
        Modules::run('secure_tings/is_logged_in');

        $update_id = $this->uri->segment(3);

        if (!isset($update_id)) {
            $update_id = $this->input->post('update_id');
        }

        if (is_numeric($update_id)) {
            $data = $this->get_data_from_db($update_id);
            $data['update_id'] = $update_id;
        } else {
            $data = $this->get_data_from_post();
        }

        $data['module'] = "store";
        $data['view_file'] = "create_store_form";
        $data['section'] = "Configuration";
        $data['subtitle'] = "Add Store";
        $data['page_title'] = "Store";
        $data['user_object'] = $this->get_user_object();
        $data['main_title'] = $this->get_title();
        $data['level'] = $this->session->userdata['logged_in']['user_level'];
        $this->load->model('stock/mdl_stock');
        $user_id = $this->session->userdata['logged_in']['user_id'];
        if ($data['level'] == 1) {
            /*
                  user_level = national
                  retrieve all regions
                  */
            $data['locations'] = $this->mdl_stock->get_region_base();
        }
        elseif($data['level'] == 2) {
            /*
                  user_level = regional
                  retrieve all counties
                  */
            $data['locations'] = $this->mdl_stock->get_county_base($user_id);
        }
        elseif($data['level'] == 3) {
            /*
                  user_level = county
                  retrieve all subcounties
                  */
            $data['locations'] = $this->mdl_stock->get_subcounty_base($user_id);
        }

        $this->load->library('make_bread');
        $this->make_bread->add('Configurations', '', 0);
        $this->make_bread->add('List Stores', 'store/', 0);
        $this->make_bread->add('Add Store', '', 0);
        $data['breadcrumb'] = $this->make_bread->output();
        echo Modules::run('template/'.$this->redirect($this->session->userdata['logged_in']['user_group']), $data);

    }


    function submit() {

        $this->load->library('form_validation');
        $this->form_validation->set_rules('store_location', 'Store Location', 'trim|required');
        $this->form_validation->set_rules('officer', 'Store Officer', 'trim|required');
        $this->form_validation->set_rules('officer_phone', 'Mobile Phone', 'trim|required');
        $this->form_validation->set_rules('officer_email', 'Email Address', 'trim|required');

        $this->form_validation->set_error_delimiters('<p class="red_text semi-bold">'.'*', '</p>');
        $update_id = $this->input->post('update_id', TRUE);
        if ($this->form_validation->run() == FALSE) {
            $this->create();

        } else {
            $data = $this->get_data_from_post();

            if (is_numeric($update_id)) {
                $this->_update($update_id, $data);
                $this->session->set_flashdata('msg', '<div id="alert-message" class="alert alert-success text-center">Store details updated successfully!</div>');

            } else {
                $this->_insert($data);
                $this->session->set_flashdata('msg', '<div id="alert-message" class="alert alert-success text-center">New Store added successfully!</div>');
            }

            redirect('store');
        }
    }

    function get_data_from_post() {
        $info['user_object'] = $this->get_user_object();
        $station_id = $info['user_object']['user_statiton'];
        $data['store_location'] = $this->input->post('store_location', TRUE);
        $data['officer']=$this->input->post('officer', TRUE);
        $data['officer_phone']=$this->input->post('officer_phone', TRUE);
        $data['officer_email']=$this->input->post('officer_email', TRUE);
        $data['level'] = $this->session->userdata['logged_in']['user_level'];
        $data['station'] = $station_id;
        return $data;
    }

    function get_data_from_db($update_id) {
        $query = $this->get_store($update_id);
        foreach($query->result() as $row) {
            $data['store_location'] = $row->location;
            $data['officer'] = $row->officer;
            $data['officer_phone'] = $row->officer_phone;
            $data['officer_email'] = $row->officer_email;
        }
        return $data;
    }

    public function stores() {

        $this->load->model('mdl_store');
        $option = 1;
        $list = $this->get_stores($option);
        $data = array();
        $no = $_POST['start'];
        foreach($list as $store) {
            $no++;
            $row = array();
            $row[] = $store->location;
            $row[] = $store->officer;
            $row[] = $store->officer_phone;

            //add html for action

            $row[] = '  <a class="btn btn-sm btn-info" href="store/list_fridge/'.$store->id.'" title="View"><i class="fa fa-eye"></i> View</a>
                              <a class="btn btn-sm btn-primary" href="store/create/'.$store->id.'" title="Edit"><i class="glyphicon glyphicon-pencil"></i> Edit</a>
                              <a class="btn btn-sm btn-danger"  href="store/delete/'.$store->id.'" title="Delete"><i class="glyphicon glyphicon-trash"></i> Delete</a>
                              <a class="btn btn-sm btn-info"  href="store/list_fridge/'.$store->id.'" title="Add"><i class="glyphicon glyphicon-plus"></i> Fridge</a>';

            $data[] = $row;
        }

        $output = array("draw" => $_POST['draw'], "recordsTotal" => $this->count_all(), "recordsFiltered" => $this->count_filtered($option), "data" => $data, );

        echo json_encode($output);
    }

    public function other_stores() {

        $this->load->model('mdl_store');
        $option = 2;
        $list = $this->get_stores($option);
        $data = array();
        $no = $_POST['start'];
        foreach($list as $store) {
            $no++;
            $row = array();
            $row[] = $store->location;
            $row[] = $store->officer;
            $row[] = $store->officer_phone;

            //add html for action

            $row[] = '  <a class="btn btn-sm btn-info" href="store/list_fridge/'.$store->id.'" title="View"><i class="fa fa-eye"></i> View</a>
                              <a class="btn btn-sm btn-primary" href="store/create/'.$store->id.'" title="Edit"><i class="glyphicon glyphicon-pencil"></i> Edit</a>
                              <a class="btn btn-sm btn-danger"  href="store/delete/'.$store->id.'" title="Delete"><i class="glyphicon glyphicon-trash"></i> Delete</a>
                              <a class="btn btn-sm btn-info"  href="store/list_fridge/'.$store->id.'" title="Add"><i class="glyphicon glyphicon-plus"></i> Fridge</a>';

            $data[] = $row;
        }

        $output = array("draw" => $_POST['draw'], "recordsTotal" => $this->count_all(), "recordsFiltered" => $this->count_filtered($option), "data" => $data, );

        echo json_encode($output);
    }


    public function list_fridge() {
        $update_id = $this->uri->segment(3);
        $data['id'] = $update_id;
        $data['fridge_model'] = $this->get_fridge_model();
        $data['module'] = "store";
        $data['view_file'] = "list_refrigerator_view";
        $data['section'] = "Configuration";
        $data['subtitle'] = "Refrigerator";
        $data['page_title'] = "Refrigerator";
        $data['user_object'] = $this->get_user_object();
        $data['main_title'] = $this->get_title();
        $data['level'] = $this->session->userdata['logged_in']['user_level'];
        $data['station'] = $this->session->userdata['logged_in']['user_level'];
        $this->load->library('make_bread');
        $this->make_bread->add('Configurations', '', 0);
        $this->make_bread->add('List Stores', 'store/', 0);
        $this->make_bread->add('List Refrigerators', '', 0);
        $data['breadcrumb'] = $this->make_bread->output();
        echo Modules::run('template/'.$this->redirect($this->session->userdata['logged_in']['user_group']), $data);

    }


    function get_fridges_by_id() {
        $store_id = $this->uri->segment(3);
        $this->load->model('mdl_store');
        $data['user_object'] = $this->get_user_object();
        //        $station_name = $data['user_object']['user_statiton'];
        $list = $this->mdl_store->get_fridges_by_id($store_id);
        $data = array();
        $no = $_POST['start'];
        foreach($list as $fridge) {
            $no++;
            $row = array();

            $row[] = $fridge->Model;
            $row[] = $fridge->Manufacturer;
            $row[] = $fridge->temperature_monitor_no;
            $row[] = $fridge->main_power_source;
            $row[] = $fridge->age;
            $row[] = $fridge->refrigerator_status;
            //add html for action

            $row[] = '  <a class="btn btn-sm btn-primary" title="Edit" onclick="edit_fridge('."'".$fridge->id."'".')"><i class="glyphicon glyphicon-pencil"></i> Edit</a>
                          <a class="btn btn-sm btn-danger"  title="Delete" onclick="delete_fridge('."'".$fridge->id."'".')"><i class="glyphicon glyphicon-trash"></i> Delete</a>';


            $data[] = $row;
        }

        $output = array("draw" => $_POST['draw'], "recordsTotal" => $this->count_fridges($store_id), "recordsFiltered" => $this->count_fridges_filtered($store_id), "data" => $data, );

        echo json_encode($output);
    }

    function edit_fridge($id) {
        $this->load->model('mdl_store');
        $query = $this->mdl_store->get_fridges($id);
        echo json_encode($query);
    }

    public function add_fridge() {

        $id = $this->uri->segment(3);
        $data2['user_object2'] = $this->get_user_object();
        $data3['user_object3'] = $this->get_user_object();
        $user_id = $this->session->userdata['logged_in']['user_id'];
        $user_level = $data2['user_object2']['user_level'];
        $station_name = $data3['user_object3']['user_statiton'];
        $data = array('user_id' => $user_id, 'station' => $station_name, 'station' => $user_level, 'date_added' => date('Y-m-d', strtotime(date('Y-m-d'))), 'fridge_id' => $this->input->post('model'), 'temperature_monitor_no' => $this->input->post('temperature_monitor_no'), 'main_power_source' => $this->input->post('main_power_source'), 'age' => $this->input->post('refrigerator_age'), 'store_id' => $id, 'refrigerator_status' => $this->input->post('refrigerator_status'),

        );
        $this->_insert_fridge($data);
        echo json_encode(array("status" => TRUE));
    }

    public function update_fridge($id) {
        //        $id = $this->uri->segment(3);
        $data = array('temperature_monitor_no' => $this->input->post('temperature_monitor_no'), 'main_power_source' => $this->input->post('main_power_source'), 'refrigerator_status' => $this->input->post('refrigerator_status'), );
        $this->_update_fridge($id, $data);
        echo json_encode(array("status" => TRUE));
    }

    function get_stores($option) {
        $info['user_object'] = $this->get_user_object();
        $station_id = $info['user_object']['user_statiton'];
        $this->load->model('mdl_store');
        $query = $this->mdl_store->get_stores($station_id,$option);
        return $query;
    }

    function get_fridge_model() {
        $this->load->model('mdl_store');
        $query = $this->mdl_store->get_fridge_model();
        return $query;
    }

    function dump() {
        $this->load->model('mdl_store');
        $query = $this->mdl_store->get_stores();
        //return $query;
        var_dump($query);
    }

    function count_all() {
        $info['user_object'] = $this->get_user_object();
        $station_id = $info['user_object']['user_statiton'];
        $this->load->model('mdl_store');
        $query = $this->mdl_store->count_all($station_id);
        return $query;
    }

    function count_filtered($option) {
        $info['user_object'] = $this->get_user_object();
        $station_id = $info['user_object']['user_statiton'];
        $this->load->model('mdl_store');
        $query = $this->mdl_store->count_filtered($station_id,$option);
        return $query;
    }

    function delete($id) {
        $this->_delete($id);
        $this->session->set_flashdata('msg', '<div id="alert-message" class="alert alert-success text-center">Depot details deleted successfully!</div>');
        redirect('store');
    }

    function delete_fridge() {
        $id = $this->uri->segment(3);
        $this->_delete_fridge($id);
        echo json_encode(array("status" => TRUE));
        //        $this->session->set_flashdata('msg', '<div id="alert-message" class="alert alert-success text-center">Depot details deleted successfully!</div>');
        //        redirect('store/list_fridge');
    }

    function get($order_by) {
        $this->load->model('mdl_store');
        $query = $this->mdl_store->get($order_by);
        return $query;
    }

    function count_fridges($store_id) {

        $this->load->model('mdl_store');
        $query = $this->mdl_store->count_fridges($store_id);
        return $query;
    }


    function count_fridges_filtered($store_id) {
        $this->load->model('mdl_store');
        $query = $this->mdl_store->count_fridges_filtered($store_id);
        return $query;
    }

    function get_where($id) {
        $this->load->model('mdl_store');
        $query = $this->mdl_store->get_where($id);
        return $query;
    }

    function get_store($id) {
        $this->load->model('mdl_store');
        $query = $this->mdl_store->get_store($id);
        return $query;
    }

    function _insert($data) {
        $this->load->model('mdl_store');
        $this->mdl_store->_insert($data);
    }

    function _update($id, $data) {
        $this->load->model('mdl_store');
        $this->mdl_store->_update($id, $data);
    }

    function _delete($id) {
        $this->load->model('mdl_store');
        $this->mdl_store->_delete($id);
    }

    function _insert_fridge($data) {
        $this->load->model('mdl_store');
        $this->mdl_store->_insert_fridge($data);
    }

    function _update_fridge($id, $data) {
        $this->load->model('mdl_store');
        $this->mdl_store->_update_fridge($id, $data);
    }

    function _delete_fridge($id) {
        $this->load->model('mdl_store');
        $this->mdl_store->_delete_fridge($id);
    }

}