<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class API extends MY_Controller
{

    public function __construct() {
        parent::__construct();
        $this->load->model('stock_m');
        $this->load->model('ofi_m');
        $this->load->library('response');
        $this->load->helper('common_helper');
    }
    







//this will update vendor details 
    public function upload_docs(){

        log_message('debug', print_r('upload docs called', true));


        

        //$config['upload_path'] =  base_url('uploads');
        $config['upload_path'] = 'C:/wamp/www/websites/webmultilinkCopy/uploads';
        $config['allowed_types'] = 'gif|jpg|png|pdf';  
        $config['max_size'] = '2000';
        $config['max_width']  = '0';
        $config['max_height']  = '0';

        $this->load->library('upload', $config);

        var_dump(is_dir('C:/wamp/www/websites/webmultilinkCopy/uploads')); 
        var_dump($_SERVER['SCRIPT_FILENAME']); 


        //mod. adding this as suggested
        //$this->upload->initialize($config); 

        if ( ! $this->upload->do_upload('user_file'))
        {

            echo $config['upload_path'];
            $error = array('error' => $this->upload->display_errors());

            $this->load->view('upload_form', $error);
        }
        else
        {
            $data = array('upload_data' => $this->upload->data());

            $this->load->view('upload_success', $data);
        }



    }










//this will update vendor details 
    public function update_vendor_details(){

        log_message('debug', print_r('api version called. stock/api/update_vendor_details', true));


        



        //var_dump($this->input);


        
//echo '<h3>the post</h3>';

//var_dump($_POST);

        $postVarDumped = $this->varDumpToString($_POST);
        
        log_message('debug', print_r(' postVarDumped is : ' . $postVarDumped, true));




        $vendor_details_arr['office_id'] = $this->session->userdata('user_office_id');

        $vendor_details_arr['contact_id_from_url'] = $this->input->post('contact_id_from_url');


        $vendor_details_arr['firstname'] = $this->input->post('name');
        $vendor_details_arr['surname'] = $this->input->post('surname');

        $vendor_details_arr['email'] = $this->input->post('email');
        $vendor_details_arr['mobile'] = $this->input->post('phone');




        if ($update_result = $this->stock_m->update_vendor_details($vendor_details_arr)) {

            log_message('debug', print_r(' just updated ', true));

        }




//echo 'now load other';






    }










    public function ofi($stock_id, $ofi_date = null) {

        $stock_id = intval($stock_id);
        
        if (empty($ofi_date)) {
            $ofi_date = $current_date = date('dmY', time());
        }
        
        // verify if the stock exist or not
        if ($stock = $this->stock_m->get($this->session->userdata('user_office_id'), $stock_id)) {

            $ofis = $this->ofi_m->get_all_ofi($this->session->userdata('user_office_id'), $this->session->userdata('user_id'), $stock_id, $ofi_date);
            
            echo json_encode($ofis);
        } else {
            $result['result'] = false;
            $result['message'] = 'Stock #' . $stock_id . ' is an invalid stock id.';
            echo json_encode($result);
        }
    }
    
    /**
     *
     * mod. important for streamline.  combines get all ofi (for response and formating with other ofi controller function for data access)
     */
    public function get_people_who_attended_ofi($stock_id, $ofi_date = null) {
        $stock_id = intval($stock_id);
        
        if (empty($ofi_date)) {
            $ofi_date = $current_date = date('dmY', time());
        }
        
        // verify if the stock exist or not
        if ($stock = $this->stock_m->get($this->session->userdata('user_office_id'), $stock_id)) {

            //mod.  in this version calling newer method,
            //$ofis = $this->ofi_m->get_all_ofi($this->session->userdata('user_office_id'), $this->session->userdata('user_id'), $stock_id, $ofi_date);
            $ofis = $this->ofi_m->get_list_of_people_who_attended_ofi($this->session->userdata('user_office_id'), $this->session->userdata('user_id'), $stock_id, $ofi_date);
            
            $ofisVarDumpToString = VarDumpToString($ofis);
            log_message('debug', print_r(' ofisVarDumpToString is : ' . $ofisVarDumpToString, true));
            
            // make each attendee has ofi_idx as a key and detail as an array.
            $new_ofis = array();
            
            //Make sure $ofis is an array and iteratable
            if (is_array($ofis) && count($ofis)>0) {
            	foreach ($ofis as $ofi) {
            	
            		//try to change key to lowercase
            		$ofi = array_change_key_case($ofi, CASE_LOWER);
            	
            		if (isset($ofi['ofi_idx']) AND !empty($ofi['ofi_idx'])) {
            			$new_ofis[$ofi['ofi_idx']] = $ofi;
            		}
            	}
            }
            
            $new_ofisVarDumpToString = VarDumpToString($new_ofis);
            log_message('debug', print_r(' new_ofisVarDumpToString is : ' . $new_ofisVarDumpToString, true));
            
            $this->response->set_result(true);
            $this->response->set_data($new_ofis);
        } else {
            $this->response->set_result(true);
            $this->response->set_message('Stock #' . $stock_id . ' is an invalid stock id.');
        }
        
        echo $this->response->get_response_json();
    }
    
    /**
     *
     * @param $stock_id
     * @param null $ofi_date
     */
    public function get_all_ofi($stock_id, $ofi_date = null) {
        $stock_id = intval($stock_id);
        
        if (empty($ofi_date)) {
            $ofi_date = $current_date = date('dmY', time());
        }
        
        // verify if the stock exist or not
        if ($stock = $this->stock_m->get($this->session->userdata('user_office_id'), $stock_id)) {

            $ofis = $this->ofi_m->get_all_ofi($this->session->userdata('user_office_id'), $this->session->userdata('user_id'), $stock_id, $ofi_date);
            
            // make each attendee has ofi_idx as a key and detail as an array.
            $new_ofis = array();
            foreach ($ofis as $ofi) {
                if (isset($ofi['ofi_idx']) AND !empty($ofi['ofi_idx'])) {
                    $new_ofis[$ofi['ofi_idx']] = $ofi;
                }
            }
            
            $this->response->set_result(true);
            $this->response->set_data($new_ofis);
        } else {
            $this->response->set_result(true);
            $this->response->set_message('Stock #' . $stock_id . ' is an invalid stock id.');
        }
        
        echo $this->response->get_response_json();
    }
    
    /**
     * moved to client api as that makes sense wrt search model etc
     * this version called from the populate method in the js for populating formm from mobile
     * @param $mobile_number
     */
    public function get_attendee_data_from_mobile() {

        //$stock_id = intval($stock_id);
        log_message('debug', '****************************************************************** in stock/controllers/api/get_attendee_data_from_mobile');
        
        $result = array();
        $result['error'] = array();
        $result['success'] = array();
        
        if ($attendee_mobile = $this->input->post('attendee_mobile')) {

            log_message('debug', print_r(' mobile passed was  : ' . $attendee_mobile, true));
        }
        
        /**
         $is_ofis_sent = $this->ofi_m->is_ofis_sent($this->session->userdata('user_office_id'), $ofi['stock_id'], $this->session->userdata('user_id'), $ofi['ofi_date']);
         // if ofi has already  been sent, then just return it with error.
         if ($is_ofis_sent !== false) {
         $result['error'][] = array("SMS & Emails have been sent to this property");
         echo json_encode($result);
         return;
         }
         if (empty($ofi_date)) {
         $ofi_date = $current_date = date('dmY', time());
         }
         // verify if the stock exist or not
         if ($stock = $this->stock_m->get($this->session->userdata('user_office_id'), $stock_id)) {
         $ofis = $this->ofi_m->get_all_ofi($this->session->userdata('user_office_id'), $this->session->userdata('user_id'), $stock_id, $ofi_date);
         // make each attendee has ofi_idx as a key and detail as an array.
         $new_ofis = array();
         foreach ($ofis as $ofi) {
         if (isset($ofi['ofi_idx']) AND !empty($ofi['ofi_idx'])) {
         $new_ofis[$ofi['ofi_idx']] = $ofi;
         }
         }
         $this->response->set_result(true);
         $this->response->set_data($new_ofis);
         } else {
         $this->response->set_result(true);
         $this->response->set_message('Stock #' . $stock_id . ' is an invalid stock id.');
         }
         echo $this->response->get_response_json();
         }
         *
         */
         
        //this is the same call as made within the client controller api in the search method which calls the search model, which now uses the stored proc.
        // in the clien search method call, the string is cleaned beforehand, but in this case, already a mobile with right format??? so temp like ths
         $clients = $this->client_m->search($this->session->userdata('user_office_id'), $this->session->userdata('user_id'), $attendee_mobile);
         
         $clientsVarDumped = $this->varDumpToString($clients);
         
         log_message('debug', print_r(' the clienets obtained using that mobile number , var dumed  is : ' . $clientsVarDumped, true));
         
         $response = array('asdfasdf' => null, 'message' => $attendee_mobile);
         
         echo json_encode($response);
     }
     
     private function ofi_sms_vendor($stock_id, $ofis) {
        $result['error'] = array();
        $result['success'] = array();
        
        // get all information of the stock.
        $stock = $this->stock_m->get($this->session->userdata('user_office_id'), $stock_id);
        if (!$stock) {
            return "Error: No property found when sending sms to vendor, id " . $stock_id;
        }
        
        // settup email content and email subject
        $prospect_count = 0;
        $document_count = 0;
        
        // work out how many inpectors and how many contract requests.
        foreach ($ofis as $key => $item) {
            $prospect_count++;
            $document_count = (isset($item['wants_sect32']) AND $item['wants_sect32']) ? ++$document_count : $document_count;
        }
        
        $document_count_text = '';
        if ($document_count) {
            $document_count_text = " with " . $document_count . " contract(s) requested";
        }
        
        $username = $this->config->item('sms_username');
        $password = $this->config->item('sms_password');
        $destination = $this->_mobile_formatter($stock['vendor_mobile']);
        
        //mod. for if no agent mobile available
        //$source    = $this->_mobile_formatter($this->session->userdata('user_mobile'));
        //temp for testing the no agent mobile available
        $tempMobile = 'No reply available';
        
        if ($this->session->userdata('user_mobile') != '') {

            $source = $this->_mobile_formatter($this->session->userdata('user_mobile'));
            
            // source number as salsman number
            
            
        } else {

            $source = $this->_mobile_formatter($tempMobile);
        }
        
        // If vendor's phone number is invlaid, just return error.
        if ($destination === false) {
            $result['error'][] = $this->_get_current_time() . " FAILED> sms to vendor, " . $stock['vendor_firstname'] . " " . $stock['vendor_surname'] . ", " . $destination;
        }
        
        $prospect_count = 0;
        $document_count = 0;
        
        foreach ($ofis as $key => $item) {
            $prospect_count++;
            $document_count = (isset($item['wants_sect32']) AND $item['wants_sect32']) ? ++$document_count : $document_count;
        }
        
        // get template data ready for sms vendor
        $tmp_data['vendor_name'] = $stock['vendor_firstname'] . " " . $stock['vendor_surname'];
        $tmp_data['prospect_count'] = $prospect_count;
        $tmp_data['document_count'] = $document_count_text;
        $tmp_data['agent_name'] = $this->session->userdata('full_name');
        $text = $this->load->view('ofi_tpl/sms_vendor', $tmp_data, true);
        
        $content = 'action=sendsms' . '&user=' . rawurlencode($username) . '&password=' . rawurlencode($password) . '&to=' . rawurlencode($destination) . '&from=' . rawurlencode($source) . '&text=' . rawurlencode($text);
        
        $output = $this->_sendSMS($content);
        
        if (stripos($output, 'OK: 0;') !== false) {
            $result['success'][] = $this->_get_current_time() . " OK> sms to vendor, " . $stock['vendor_firstname'] . " " . $stock['vendor_surname'] . ", " . $destination;
        } else {
            $result['error'][] = $this->_get_current_time() . " FAILED> sms to vendor, " . $stock['vendor_firstname'] . " " . $stock['vendor_surname'] . ", " . $destination;
        }
        
        return $result;
    }
    
    /**
     * 
     * @param unknown $stock_id
     * @param unknown $ofis
     * @param unknown $filteredAttendeesToBeSent
     * @return string|Ambigous <multitype:multitype: , string>
     */
    private function ofi_sms_inspec($stock_id, $ofis, $filteredAttendeesToBeSent) {
        $result['error'] = array();
        $result['success'] = array();
        
        $stock = $this->stock_m->get($this->session->userdata('user_office_id'), $stock_id);
        if (!$stock) {
            return "Error: No property found when sending sms to buyers, id " . $stock_id;
        }
        $stock_type = strtolower(trim($stock['sales_method']));
        
        // detect if there is any document for the stock
        $ofi_doc = array();
        if ($doc_link = Modules::run('stock/has_doc', $this->session->userdata('user_office_id'), $stock_id)) {
            $ofi_doc['doc_link'] = $doc_link;
            $ofi_doc['doc_name'] = Modules::run('stock/get_doc_name', $this->session->userdata('user_office_id'), $stock_id);
        }
        
        //new input passed.  try looping etc
        $syncedOnesVarDumped = $this->varDumpToString($this->input->post('rds'));
        
        log_message('debug', print_r(' now in sms thing, syncedOnesVarDumped is : ' . $syncedOnesVarDumped, true));
        
        //new input passed.  try looping etc
        $ofisVarDumped = $this->varDumpToString($ofis);
        
        log_message('debug', print_r('now in sms thing, ofisVarDumped is : ' . $ofisVarDumped, true));
        
        //the filtered ones.  try looping etc
        $filteredAttendeesToBeSentVarDumped = $this->varDumpToString($filteredAttendeesToBeSent);
        
        log_message('debug', print_r('now in sms thing filteredAttendeesToBeSentVarDumped is : ' . $filteredAttendeesToBeSentVarDumped, true));
        
        // construct link and send sms.
        
        //mod now going to try looping through ffilteredAttendeesToBeSent instead of ofis
        $i = - 1;
        
        //foreach ($ofis as $item) {
        foreach ($filteredAttendeesToBeSent as $item) {

            $i++;
            
            $currentfilteredAttendeesToBeSentVarDumped = $this->varDumpToString($item);
            
            log_message('debug', print_r('the currentfilteredAttendeesToBeSentVarDumped is : ' . $currentfilteredAttendeesToBeSentVarDumped, true));
            
            // if the attendee doesn't provide phone number, it would skip it.
            if (!isset($item['phone']) or empty($item['phone'])) {
                continue;
            }
            
            // format inspector's phone number.
            $inspector_mobile = $this->_mobile_formatter($item['phone']);
            
            // if the phone number is not valid, skip them.
            if ($inspector_mobile === false) {
                continue;
            }
            
            //temp for testing the no agent mobile available
            $tempMobile = 'No reply available';
            
            if ($this->session->userdata('user_mobile') != '') {

                //mod.  stored proc sometimes returns landline number as mobile, so cant put it through mobile formatter.  just getting rid of spaces and using as source mobile

                $from_mobile = $this->session->userdata('user_mobile');
                
                // source number as salsman number
                $from_mobile = str_replace(' ', '', $from_mobile);
            } else {

                //$from_mobile = $this->_mobile_formatter($tempMobile);
                $from_mobile = $tempMobile;
            }
            
            // make sure surname is set.
            $item['surname'] = isset($item['surname']) ? $item['surname'] : '';
            
            //mod. temp patch for quotes issue, as per Frankie Patch
            $item['name'] = str_replace("'", "", $item['name']);
            $item['surname'] = str_replace("'", "", $item['surname']);
            
            //mod.  deleted query to get ofi_idx
            
            // if the doc is there and the inspector is requesting the contact,
            // use the tempalate with doc
            $text = '';
            
            // choose the template for rent
            
            $tmp_data['prospect_name'] = $item['name'];
            $tmp_data['property_name'] = $stock['address'];
            $tmp_data['agent_mobile'] = $from_mobile;
            $tmp_data['agent_name'] = $this->session->userdata('full_name');
            $tmp_data['inspector_email'] = (isset($item['email']) AND $item['email']) ? $item['email'] : 'Email Address Not Given';
            
            //$text = $this->load->view('ofi_tpl/sale_sms_buyers_doc', $tmp_data, true);
            
            log_message('debug', ' about to creat sms text, ofi_idx is ' . $item['ofi_idx']);
            

            //now trying to use same model method get_message_template_and_subject as the email template method
            //$text = $this->ofi_m->get_message_template($item['ofi_idx'], 'sms');



            $messageAndSubjectArray = $this->ofi_m->get_message_template_and_subject($item['ofi_idx'], 'sms');

            

            if (array_key_exists("BUYER_MESSAGE", $messageAndSubjectArray)) {
                $text = $messageAndSubjectArray['BUYER_MESSAGE'];
            } else {
                $text = 'Thanks for your attendance';
            }







            
            //logging email so dont have to wait for email
            log_message('debug', 'sms message follows');
            
            //log_message('debug', print_r($text[0], true));
            log_message('debug', print_r($text, true));
            
            // encode text message as url paramater
            $text = rawurlencode($text);
            
            // workout the trunk of long sms message
            $maxsplit = ceil(strlen($text) / 160);
            
            $content = 'action=sendsms' . '&user=' . rawurlencode($this->config->item('sms_username')) . '&password=' . rawurlencode($this->config->item('sms_password')) . '&to=' . rawurlencode($inspector_mobile) .
            
            //trying to stop character lengh limit
            '&from=' . rawurlencode($from_mobile) . '&text=' . $text . '&maxsplit=' . $maxsplit;
            
            log_message('debug', print_r('content of sms is ' . $content, true));
            
            // print_r($content . "\n");
            if (stripos($this->_sendSMS($content), 'OK: 0;') === false) {
                $result['error'][] = $this->_get_current_time() . " FAILED> sms to buyer, " . $item['name'] . " " . $item['surname'] . ", " . $item['phone'] . " failed.";
            } else {
                $result['success'][] = $this->_get_current_time() . " OK> sms to buyer, " . $item['name'] . " " . $item['surname'] . ", " . $item['phone'];
            }
        }
        
        return $result;
    }
    
    private function ofi_emal_vendor($stock_id, $ofis) {
        $result['error'] = array();
        $result['success'] = array();
        
        // get all information of the stock.
        $stock = $this->stock_m->get($this->session->userdata('user_office_id'), $stock_id);
        if (!$stock) {
            return "Error: No property found when sending email to vendor, id " . $stock_id;
        }
        
        // settup email content and email subject
        $prospect_count = 0;
        $document_count = 0;
        
        // work out how many inpectors and how many contract requests.
        foreach ($ofis as $key => $item) {
            $prospect_count++;
            $document_count = (isset($item['wants_sect32']) AND $item['wants_sect32']) ? ++$document_count : $document_count;
        }
        
        // settup email subject
        $subject = "Open house has now finished.  we had " . $prospect_count . " inspections";
        $document_count_text = '';
        
        if ($document_count) {
            $document_count_text = "with " . $document_count . " contract(s) requested";
            $subject.= " with " . $document_count . " contract(s) requested.";
        } else {
            $subject.= ".";
        }
        
        // contact email content
        $tmp_data['vendor_name'] = $stock['vendor_firstname'];
        $tmp_data['property_name'] = $stock['address'];
        $tmp_data['prospect_count'] = $prospect_count;
        $tmp_data['document_count'] = $document_count_text;
        $tmp_data['agent_name'] = $this->session->userdata('full_name');
        $text = $this->load->view('ofi_tpl/email_vendor', $tmp_data, true);
        
        $this->load->library('email');
        $the_email_sending_result = $this->email
        				->from($this->session->userdata('user_email'))
        				->reply_to($this->session->userdata('user_email'))
        				->to($stock['vendor_email'])
        				->subject($subject)
        				->message($text)
        				->send();
//         $this->email->to($stock['vendor_email']);
        
//         // $this->email->to('daniel@multilink.com.au');
//         $this->email->cc($this->session->userdata('user_email'));
//         $this->email->subject($subject);
//         $this->email->message($text);
        
        if ($the_email_sending_result) {
            $result['success'][] = $this->_get_current_time() . " OK> email to vendor, " . $stock['vendor_firstname'] . " " . $stock['vendor_surname'] . ", " . $stock['vendor_email'];
        } else {
            $result['error'][] = $this->_get_current_time() . " FAILED> email to vendor, " . $stock['vendor_firstname'] . " " . $stock['vendor_surname'] . ", " . $stock['vendor_email'] . " failed.\n" . $this->email->print_debugger();
        }
        
        return $result;
    }
    
    private function ofi_emails_inspectors($stock_id, $ofis, $filteredAttendeesToBeSent) {

        //$a = var_dump(get_defined_vars());

        //need to check status of idx at this point
        
        $this->load->library('email');
        // Load email server config from DB
        $email_server_config = get_email_server_config($this->stock_m);
        
        // load email library
        
        $result['success'] = array();
        $result['error'] = array();
        $stock = $this->stock_m->get($this->session->userdata('user_office_id'), $stock_id);
        if (!$stock) {
            return "Error: No property found when sending emails to buyers, id " . $stock_id;
        }
        $stock_type = strtolower(trim($stock['sales_method']));
        $from_mobile = $this->_mobile_formatter($this->session->userdata('user_mobile'));
        
        // detect if there is any document for the stock
        $ofi_doc = array();
        if ($doc_link = Modules::run('stock/has_doc', $this->session->userdata('user_office_id'), $stock_id)) {

            //testing extra url characters
            $ofi_doc['doc_link'] = $doc_link . "/dcd/";
            $ofi_doc['doc_name'] = Modules::run('stock/get_doc_name', $this->session->userdata('user_office_id'), $stock_id);
        }
        
        // email's content.
        $text = '';
        
        //mod. now trying to loop through filteredAttendeesToBeSent instead of ofis
        //foreach ($ofis as $item) {
        foreach ($filteredAttendeesToBeSent as $item) {

//             $currentfilteredAttendeesToBeSentVarDumped = $this->varDumpToString($item);
            
//             log_message('debug', print_r('in email thing, the currentfilteredAttendeesToBeSentVarDumped is : ' . $currentfilteredAttendeesToBeSentVarDumped, true));
            
            // make sure name and surname are set.
            
            $item['surname'] = isset($item['surname']) ? $item['surname'] : '';
            $item['name'] = isset($item['name']) ? $item['name'] : '';
            
            //mod. temp patch for quotes issue, as per Frankie Patch
            $item['name'] = str_replace("'", "", $item['name']);
            $item['surname'] = str_replace("'", "", $item['surname']);
            
            // if a ofi item's email address is empty, then skip it.
            if (!isset($item['email']) OR empty($item['email'])) {
                continue;
            }
            
            $WantsDocuments = isset($item['wants_sect32']) AND $item['wants_sect32'];
            
            // if the doc is there and the inspector is requesting the contact,
            // use the tempalate with doc
            $tmp_data['prospect_name'] = $item['name'];
            $tmp_data['property_name'] = $stock['address'];
            $tmp_data['agent_mobile'] = $from_mobile;
            $tmp_data['doc_link'] = isset($ofi_doc['doc_link']) ? $ofi_doc['doc_link'] : NULL;
            $tmp_data['doc_name'] = isset($ofi_doc['doc_name']) ? $ofi_doc['doc_name'] : NULL;
            $tmp_data['agent_name'] = $this->session->userdata('full_name');
            
            //mod.  now deleted query to get ofi_idx as using filteredAttendeesToBeSent instead of ofis
            
            //trying to get customised template for messages.
            
            //mod.  will now just try to pass the ofi idx
            
            log_message('debug', print_r('ofi_idx is ' . $item['ofi_idx'], true));
            
            //now getting both message and subjetc
            //$text = $this->ofi_m->get_message_template($item['ofi_idx'], 'email');
            $messageAndSubjectArray = $this->ofi_m->get_message_template_and_subject($item['ofi_idx'], 'email');
            
            //logging email so dont have to wait for email
            log_message('debug', 'email message  follows');
            
            //log_message('debug', print_r($text[0], true));
            log_message('debug', print_r($text, true));
            
            $this->email->clear();
            
            // Config email object because of the clear() above.
            if ($email_server_config) {
            	$this->email->initialize($email_server_config);;
            }
            
            $this->email->from($this->session->userdata('user_email'));
            $this->email->to($item['email']);
            
//             log_message('error','from: '.$this->session->userdata('user_email'));
//             log_message('error','to: '.$item['email']);
//             log_message('error','SUBJECT: '.$messageAndSubjectArray['SUBJECT']);
//             log_message('error','BUYER_MESSAGE: '.$messageAndSubjectArray['SUBJECT']);
            
            if (isset($messageAndSubjectArray['SUBJECT'])) {
                $this->email->subject($messageAndSubjectArray['SUBJECT']);
            } else {
                $this->email->subject('Thanks for your attendance');
            }
            
            //$this->email->subject('Thanks for your attendance');
            $this->email->message($messageAndSubjectArray['BUYER_MESSAGE']);
            
            
            
            if (!$this->email->send()) {
                $result['error'][] = $this->_get_current_time() . " FAILED> email to buyer, " . $item['name'] . " " . $item['surname'] . ", " . $item['email'] . " failed \n" . $this->email->print_debugger();
            } else {
                $result['success'][] = $this->_get_current_time() . " OK> email to buyer, " . $item['name'] . " " . $item['surname'] . ", " . $item['email'];
            }

            log_message('debug', 'after trying to send');
            
            //log_message('debug', print_r($text[0], true));
            log_message('debug', print_r($this->email->print_debugger(), true));
        }
        
        return $result;
    }
    
    //mod. important mod.  for using alt array with ofi_idxs post sync instead of ofis
    //temp here
    //used for filtering only the attendees for this stock for this date
    function filterAttendeesToBeSent($allIncluded, $stock_id, $date) {

        $arrayToBeNotified = array();
        
        $stringToUseForFiltering = '-' . $stock_id . '-' . $date . '-';
        $stringToUseForFilteringVarDumped = $this->varDumpToString($stringToUseForFiltering);
        
        log_message('debug', print_r('stringToUseForFilteringVarDumped is : ' . $stringToUseForFilteringVarDumped, true));
        
        foreach ($allIncluded as $key => $value) {

            $keyVarDumped = $this->varDumpToString($key);
            
            log_message('debug', print_r('key is : ' . $keyVarDumped, true));
            
            // if (strpos($key, $date) !== false) {
            if (strpos($key, $stringToUseForFiltering) !== false) {

                $arrayToBeNotified[$key] = $value;
            }
        }
        
        return $arrayToBeNotified;
    }
    
    /**
     * When click send docs in activity list page, call this function
     */
    public function send_emails_and_sms_from_activity_list_page() {
    	$result_array = array(
    			'email'=>array(),
    			'sms'=>array(),
    			'result'=>'success'
    	);
    	
//     	$result_array = array(
//     			'email'=>array('17325'=>FALSE,'17351'=>TRUE,'17483'=>TRUE),
//     			'sms'=>array('17325'=>FALSE,'17351'=>TRUE,'17483'=>FALSE),
//     			'result'=>'failed'
//     	);
//     	echo json_encode($result_array);
//     	die();
    	
    	if($this->input->is_ajax_request()){
    		$ofis_str = $this->input->get_post('ofis');
    		$stock_id = $this->input->get_post('data-sid');
    		$user_id = $this->input->get_post('user_id');
    		$user_office_id = $this->input->get_post('user_office_id');
    		
    		$ofis = explode(',', $ofis_str);
    		$emails = array();
    		$sms_arr = array();
    		
    		if ($ofis && count($ofis)>0) {
    			
    			foreach ($ofis as $key => $ofi_str) {
    				// The seperator is --
    				$ofi_arr = explode('--', $ofi_str);
    				$ofi_date = isset($ofi_arr[1]) ? $ofi_arr[1] : NULL;
    				$ofi_idx = isset($ofi_arr[2]) ? $ofi_arr[2] : NULL;
    				$ofi_receiver_email = isset($ofi_arr[3]) ? $ofi_arr[3] : NULL;
    				$ofi_reciever_mobile = isset($ofi_arr[4]) ? $ofi_arr[4] : NULL;
    				
    				if ( !empty( $ofi_idx ) ) {
    					$emailContent = $this->ofi_m->get_message_template_and_subject($ofi_idx,'email','ACTIVITY');
    					$smsContent = $this->ofi_m->get_message_template_and_subject($ofi_idx,'sms','ACTIVITY');
    					if (!empty($ofi_receiver_email) && isset($emailContent['SUBJECT']) && strlen($emailContent['SUBJECT']) && isset($emailContent['BUYER_MESSAGE']) && strlen($emailContent['BUYER_MESSAGE'])>0) {
    						$email_send_result = $result_array['email'][$ofi_idx] = $this->send_email(
    								$emailContent['SUBJECT'], 
    								$ofi_receiver_email,
    								$emailContent['BUYER_MESSAGE']
    						);
    						if(!$email_send_result){
    							$result_array['result'] = 'failed';
    						}
    					}
    					
    					if (!empty($ofi_reciever_mobile) && isset($smsContent['SUBJECT']) && strlen($smsContent['SUBJECT']) && isset($smsContent['BUYER_MESSAGE']) && strlen($smsContent['BUYER_MESSAGE'])>0) {
							$sms_send_result = $result_array['sms'][$ofi_idx] = $this->send_sms(
									$ofi_reciever_mobile, 
									$this->session->userdata('user_mobile'), 
									$smsContent['BUYER_MESSAGE']
							);
							if(!$sms_send_result){
								$result_array['result'] = 'failed';
							}
    					}
    				}
    			};
    		}else{
    			$result['msg'] = 'No activity index';
    		}
    		
//     		if (count($emails) > 0 && count($sms_arr) > 0) {
//     			//emails and sms are ready to go
//     		}else{
//     			$result['msg'] = 'Can not get the subject and template.';
//     		}
    		
    		echo json_encode($result_array);
    	}
    }
    
    // send sms & emails to vendor and inspectors
    public function notify_vendor_inspectors() {
        log_message('debug', '****************************************************************** in stock/controllers/api/notify_vendor_inspectors');
        
        $result = array();
        $result['error'] = array();
        $result['success'] = array();
        
        if ($ofi = $this->input->post('ofi') AND $ofis = $this->input->post('ofis') AND $conf = $this->input->post('conf')) {

            $is_ofis_sent = $this->ofi_m->is_ofis_sent(
            		$this->session->userdata('user_office_id'), 
            		$ofi['stock_id'], 
            		$this->session->userdata('user_id'), 
            		$ofi['ofi_date']
            );
            
            // if ofi has already  been sent, then just return it with error.
            if ($is_ofis_sent !== false) {
                $result['error'][] = array("SMS & Emails have been sent to this property");
                
                echo json_encode($result);
                return;
            }
            
            // sms to vendor
            //          if( $conf["sms_vendor"] AND
            //                ($tmp_result = $this->ofi_sms_vendor($ofi['stock_id'], $ofis))!== true){
            //              $result['error'][] = $tmp_result['error'];
            //              $result['success'][] = $tmp_result['success'];
            //          }
            
            //new input passed.  try looping etc
            $ofisRightNowVarDumped = $this->varDumpToString($ofis);
            
            //log_message('debug', print_r('In notify.., ofis is : ' . $ofisRightNowVarDumped, true));
            
            //new input passed.  try looping etc
            $syncedOnesVarDumped = $this->varDumpToString($this->input->post('rds'));
            
            //log_message('debug', print_r('In notify.., syncedOnesVarDumped rds is : ' . $syncedOnesVarDumped, true));
            
            $filteredAttendeesToBeSent = $this->filterAttendeesToBeSent($this->input->post('rds'), $ofi['stock_id'], $ofi['ofi_date']);
            
            $filteredAttendeesToBeSentVarDumped = $this->varDumpToString($filteredAttendeesToBeSent);
            
            //log_message('debug', print_r('filteredAttendeesToBeSentVarDumped is : ' . $filteredAttendeesToBeSentVarDumped, true));
            
            //need to filter the rds attendees to the ofis
            
            // $filtered = array_filter($arr, 'is_two');
            
            // sms to inspectors
            //mod. now passing also the filteredAttendeesToBeSentVarDumped
            if ($conf["sms_attendees"] AND ($tmp_result = $this->ofi_sms_inspec($ofi['stock_id'], $ofis, $filteredAttendeesToBeSent)) !== true) {
                $result['error'][] = $tmp_result['error'];
                $result['success'][] = $tmp_result['success'];
            }
            
            // email vendor
            //          if( $conf["email_vendor"] AND
            //                ($tmp_result = $this->ofi_emal_vendor($ofi['stock_id'], $ofis)) !== true) {
            //              $result['error'][] = $tmp_result['error'];
            //              $result['success'][] = $tmp_result['success'];
            //          }
            
            //echo $ofi['ofi_idx'];
            
            // email inspectors
            if ($conf["email_attendees"] AND ($tmp_result = $this->ofi_emails_inspectors($ofi['stock_id'], $ofis, $filteredAttendeesToBeSent)) !== true) {
            	$result['error'][] = $tmp_result['error'];
                $result['success'][] = $tmp_result['success'];
            }
            
            // construct all result notes
            $notes = '';
            
            if ($result['success']) {
                foreach ($result['success'] as $success) {
                    if ($success) {
                        foreach ($success as $item) {
                            $notes.= $item . "\n";
                        }
                    }
                }
            }
            
            if ($result['error']) {
                foreach ($result['error'] as $error) {
                    if ($error) {
                        foreach ($error as $item) {
                            $notes.= $item . "\n";
                        }
                    }
                }
            }
            
            //mod.  now using the model function that uses stored proc
            $this->ofi_m->ofi_send_write_result_using_SP(
            		$this->session->userdata('user_office_id'), 
            		$ofi['stock_id'], 
            		$this->session->userdata('user_id'), 
            		$ofi['ofi_date'], date('d/m/Y'), $notes);
        }
        echo json_encode($result);
    }
    
    /**
     * Check if ofis had been sent
     * 
     * @param string $stock_id
     * @param string $ofi_date
     * 
     * @return string result in json format
     */
    public function is_ofis_sent($stock_id, $ofi_date) {
		
        $result['result'] = $this->ofi_m->is_ofis_sent(
        		$this->session->userdata('user_office_id'), 
        		$stock_id, 
        		$this->session->userdata('user_id'), 
        		$ofi_date
        );
        echo json_encode($result);
    }
    
    /**
     * bulk save ofi collection
     */
    public function bulk_save($stock_id, $ofi_date) {
        $response = array('result' => null, 'message' => null,);
        
        if ($ofis = $this->input->post("ofis")) {

            if (($save_result = $this->ofi_m->bulk_save_ofis($this->session->userdata('user_office_id'), $this->session->userdata('user_id'), $stock_id, $ofi_date, $ofis)) === true) {
                $response['result'] = true;
                $response['message'] = "Bulk save ofis succeeded.";
            } else {
                $response['result'] = false;
                $response['message'] = $save_result;
            }
        } else {
            $response['result'] = false;
            $response['message'] = "The passed-in ofis in the server is empty";
        }
        
        echo json_encode($response);
    }
    
    /**
     * bulk save attendees.
     * The return attendee object will keep the key value of the original attendee object.
     *
     */
    public function bulk_add() {
        $response = array('result' => false, 'message' => null, 'data' => null,);
        
        if ($attendees = $this->input->post('attendees')) {
            if ($added_attendees = $this->ofi_m->bulk_add($attendees)) {
                $response['result'] = true;
                $response['data'] = $added_attendees;
                $response['message'] = "Bulk saved attendees successfully";
            } else {
                $response['result'] = false;
                $response['message'] = "Bulk saved attendees failed.";
            }
        } else {
            $response['result'] = false;
            $response['message'] = "Post data 'attendees' could not be empty.";
        }
        
        echo json_encode($response);
    }
    
    //mod.  this one is VERY IMPORTANT.  gets called from angular (?); proceeds to sync attendees
    public function sync() {

        log_message('debug', '****************************************************************** in stock/controllers/api/sync');
        
        $rtn_attendees = array();
        if ($attendees = $this->input->post("attendees")) {

            $i = 0;
            $j = 0;
            foreach ($attendees as $key => $attendee) {

                // add new ofi
                if (!isset($attendee['ofi_idx'])) {
                    $rtn_attendees[$key] = $this->ofi_m->add_ofi($attendee);
                    
                    //$ofi_idx_from_m = $rtn_attendees[$key]['ofi_idx'];
                    //log_message('debug', print_r($ofi_idx_from_m, true));
                    
                    $i++;
                } else {

                    // update ofi
                    $rtn_attendees[$key] = $this->ofi_m->update_ofi($attendee);
                    $j++;
                }
            }
            
            $this->response->set_result(true);
            $this->response->set_data($rtn_attendees);
            $this->response->set_message("Sync data success. (" . $i . ") added and (" . $j . ") updated.");
        } else {
            $this->response->set_result(false);
            $this->response->set_message("Sync data failed. data attendees is not found or empty.");
        }
        echo $this->response->get_response_json();
    }
    
    private function _sendSMS($content) {
        $ch = curl_init('http://www.smsglobal.com.au/http-api.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }
    
    private function _mobile_formatter($mobile) {

        // it can only support australia mobile
        if (empty($mobile)) {
            return false;
        }
        
        // remove all space inside a mobile number
        $mobile = str_replace(' ', '', $mobile);
        
        // check if the phone number starts from 04
        if (strlen($mobile) != 10 OR strpos($mobile, '04') !== 0) {
            return false;
        }
        
        return $mobile;
    }
    
    private function _get_current_time() {
        return date('d/m/Y H:m:s');
    }
    
    //mod.  attempt to simplify adding and updating process.  doing as much as possible within stored proc
    public function simplified_sync() {

        log_message('debug', '****************************************************************** in stock/controllers/api/simplified_sync');
        
        $rtn_attendees = array();
        if ($attendees = $this->input->post("attendees")) {

            $i = 0;
            $j = 0;
            foreach ($attendees as $key => $attendee) {
                log_message('debug', 'in original, just before sending attendee, attendee looks like: ');
                $attendeeVarDump = VarDumpToString($attendee);
                log_message('debug', print_r(' attendeeVarDump is : ' . $attendeeVarDump, true));

                // add or update ofi
                $rtn_attendees[$key] = $this->ofi_m->add_or_update_ofi($attendee);
                
                $i++;
            }
            
            $this->response->set_result(true);
            $this->response->set_data($rtn_attendees);
            $this->response->set_message("Sync data success. (" . $i . ") added or updated .");
        } else {
            $this->response->set_result(false);
            $this->response->set_message("Sync data failed. data attendees is not found or empty.");
        }
        echo $this->response->get_response_json();
    }
    
    //mod.  cons for se

    public function simplified_sync_consistent_for_se() {
        log_message('debug', '****************************************************************** in stock/controllers/api/simplified_sync_consistent_for_se');
        
        $rtn_attendees = array();
        if ($attendees = $this->input->post("attendees")) {

            $i = 0;
            $j = 0;
            foreach ($attendees as $key => $attendee) {

                log_message('debug', 'key is : ' . $key);
                log_message('debug', 'attendee is : ' . $attendee);
                
                if ((strpos($attendee, 'office_id') !== false) && (strpos($attendee, 'user_id') !== false) && (strpos($attendee, 'ofi_date') !== false)) {
                    log_message('debug', 'this really is an attendee object ');


                    log_message('debug', 'in consistent, just before sending attendee, attendee looks like: ');
                    $attendeeVarDump = VarDumpToString($attendee);
                    log_message('debug', print_r(' attendeeVarDump is : ' . $attendeeVarDump, true));

                    $attendeeJsonDecoded = json_decode($attendee, true);

                    log_message('debug', 'after transformation: ');
                    $attendeeJsonDecodedVarDump = VarDumpToString($attendeeJsonDecoded);
                    log_message('debug', print_r(' attendeeJsonDecodedVarDump is : ' . $attendeeJsonDecodedVarDump, true));
                    
                    // add or update ofi
                    $rtn_attendees[$key] = $this->ofi_m->add_or_update_ofi($attendeeJsonDecoded);
                    
                    //$ofi_idx_from_m = $rtn_attendees[$key]['ofi_idx'];
                    //log_message('debug', print_r($ofi_idx_from_m, true));
                    
                    $i++;
                } else {

                    log_message('debug', 'that is not really an attendee object ');
                }
            }

            $this->response->set_result(true);
            $this->response->set_data($rtn_attendees);
            $this->response->set_message("Sync data success. (" . $i . ") added or updated .");
        } else {
            $this->response->set_result(false);
            $this->response->set_message("Sync data failed. data attendees is not found or empty.");
        }
        echo $this->response->get_response_json();
    }
    
    //mod.  back to php way, but using the localstorage dump
    public function simplified_sync_using_ls_dump() {

        log_message('debug', '****************************************************************** in stock/controllers/api/simplified_sync_using_ls_dump');
        
        $rtn_attendees = array();
        
        log_message('debug', 'the objects passed are as follows ');
        log_message('debug', $this->input->post("attendees"));
		
        
        $numberOfAttendeesPassed = $this->input->post("numberOfAttendees");
        
        log_message('debug', 'the number of objects is allegedly as follows ');
        log_message('debug', $numberOfAttendeesPassed);
        
        $count_of_array_length = count($this->input->post("attendees"));
        log_message('debug', 'the count of the array is : ');
        log_message('debug', $count_of_array_length);
        
        $attendeesVarDump = VarDumpToString($this->input->post("attendees"));
        log_message('debug', print_r(' attendeesVarDump is : ' . $attendeesVarDump, true));
		$data['attendees']=$this->input->post("attendees");
		$data['numberOfAttendees']=$this->input->post("numberOfAttendees");
        //echo $this->input->post("attendees");
        //echo json_encode($data);
		//echo $this->input->post("attendees"); 
		
        //here need to filter those object that are REALLY attendees
        
        $number_of_real_attendees_passed = 0;
        $number_of_real_attendees_successfully_added_or_updated = 0;
        $current_real_attendee_succesfully_added_or_updated = false;
        $all_real_attendees_passed_were_succesfully_added_or_updated = false;
        
        if ($attendees = $this->input->post("attendees")) {

            foreach ($attendees as $key => $attendee) {

                log_message('debug', 'key is : ' . $key);
                log_message('debug', 'attendee is : ' . $attendee);
                
                if ((strpos($attendee, 'office_id') !== false) && (strpos($attendee, 'user_id') !== false) && (strpos($attendee, 'ofi_date') !== false)) {
                    log_message('debug', 'this really is an attendee object ');
                    $number_of_real_attendees_passed++;
                    
                    $current_real_attendee_succesfully_added_or_updated = $this->ofi_m->add_or_update_attendee_ls_dump($attendee);
                    
                    if ($current_real_attendee_succesfully_added_or_updated == true) {

                        $number_of_real_attendees_successfully_added_or_updated++;
                    }
                } else {

                    log_message('debug', 'that is not really an attendee object ');
                }
            }
            
            log_message('debug', 'the number of REAL attendees passed is allegedly as follows ');
            log_message('debug', $number_of_real_attendees_passed);
            
            log_message('debug', 'the number of REAL attendees succesfully updated or added is as follows ');
            log_message('debug', $number_of_real_attendees_successfully_added_or_updated);
            
            if ($number_of_real_attendees_passed == $number_of_real_attendees_successfully_added_or_updated) {

                $all_real_attendees_passed_were_succesfully_added_or_updated = true;
                $this->response->set_result(true);
            } else {

                $this->response->set_result(false);
            }
            
            //now using this to pass how many were
            $this->response->set_data($number_of_real_attendees_successfully_added_or_updated);
            
            /**
             **old way of doing it, if all goes well, delete
             //$numberOfAttendeesSuccesfullyAddedOrUpdated = 0;
             //$currentAttendeeSuccesfullyAddedOrUpdated = false;
             //$allAttendeesPassedWereSuccesfullyAddedOrUpdated = false;
             if ($attendees = $this->input->post("attendees")) {
             $i = 0;
             $j = 0;
             foreach ($attendees as $key => $attendee) {
             // add or update ofi
             $currentAttendeeSuccesfullyAddedOrUpdated = $this->ofi_m->add_or_update_attendee_ls_dump($attendee);
             if ($currentAttendeeSuccesfullyAddedOrUpdated == true) {
             $numberOfAttendeesSuccesfullyAddedOrUpdated++;
             }
             $i++;
             }
             log_message('debug', 'the number of attendees is allegedly as follows ');
             log_message('debug', $numberOfAttendeesPassed);
             log_message('debug', 'the number of attendees succesfully updated or added is as follows ');
             log_message('debug', $numberOfAttendeesSuccesfullyAddedOrUpdated);
             if ($numberOfAttendeesSuccesfullyAddedOrUpdated == $numberOfAttendeesPassed) {
             $allAttendeesPassedWereSuccesfullyAddedOrUpdated = true;
             $this->response->set_result(true);
             } else {
             $this->response->set_result(false);
             }
             //now using this to pass how many were
             $this->response->set_data($numberOfAttendeesSuccesfullyAddedOrUpdated);
             */
         } else {
            $this->response->set_result(false);
            $this->response->set_message("Sync data failed. data attendees is not found or empty.");
        }
        echo $this->response->get_response_json(); 
    }
    
    //mod.  back to php way, but using the localstorage dump
    public function simplified_sync_using_ls_dump_for_sending() {

        log_message('debug', '****************************************************************** in stock/controllers/api/simplified_sync_using_ls_dump');
        
        $rtn_attendees = array();
        
        log_message('debug', 'the attendees passed are as follows ');
        log_message('debug', $this->input->post("attendees"));
        
        $numberOfAttendeesPassed = $this->input->post("numberOfAttendees");
        
        log_message('debug', 'the number of attendees is allegedly as follows ');
        log_message('debug', $numberOfAttendeesPassed);
        
        $attendeesVarDump = VarDumpToString($this->input->post("attendees"));
        log_message('debug', print_r(' attendeesVarDump is : ' . $attendeesVarDump, true));
        
        $numberOfAttendeesSuccesfullyAddedOrUpdated = 0;
        $currentAttendeeSuccesfullyAddedOrUpdated = false;
        $allAttendeesPassedWereSuccesfullyAddedOrUpdated = false;
        
        if ($attendees = $this->input->post("attendees")) {

            $i = 0;
            $j = 0;
            foreach ($attendees as $key => $attendee) {

                //diff
                $rtn_attendees[$key] = $this->ofi_m->add_or_update_ofi($attendee);
                
                // add or update ofi
                $currentAttendeeSuccesfullyAddedOrUpdated = $this->ofi_m->add_or_update_attendee_ls_dump($attendee);
                
                if ($currentAttendeeSuccesfullyAddedOrUpdated == true) {

                    $numberOfAttendeesSuccesfullyAddedOrUpdated++;
                }
                
                $i++;
            }
            
            log_message('debug', 'the number of attendees is allegedly as follows ');
            log_message('debug', $numberOfAttendeesPassed);
            
            log_message('debug', 'the number of attendees succesfully updated or added is as follows ');
            log_message('debug', $numberOfAttendeesSuccesfullyAddedOrUpdated);
            
            if ($numberOfAttendeesSuccesfullyAddedOrUpdated == $numberOfAttendeesPassed) {

                $allAttendeesPassedWereSuccesfullyAddedOrUpdated = true;
                $this->response->set_result(true);
            } else {

                $this->response->set_result(false);
            }
            
            //now using this to pass how many were
            $this->response->set_data($numberOfAttendeesSuccesfullyAddedOrUpdated);
            
            //$this->response->set_result(true);
            //$this->response->set_data($rtn_attendees);
            //$this->response->set_message("Sync data success. (" . $i . ") added or updated .");
            
            
        } else {
            $this->response->set_result(false);
            $this->response->set_message("Sync data failed. data attendees is not found or empty.");
        }
        echo $this->response->get_response_json();
    }
    
    //for using the webService
    public function simplified_sync_through_web_service() {

        log_message('debug', '****************************************************************** in stock/controllers/api/simplified_sync_through_web_service');
        
        $rtn_attendees = array();
        
        //temp to test
        //the yes thing is just to avoid fa with if etc
        
        if ($attendees = $this->input->post("attendees")) {

            //   }
            //       $yes = 1;
            //     if ($yes == 1){

            log_message('debug', print_r('attendees is  : ' . $attendees, true));
            
            //building the string
            
            //testing
            $newUrlForCurl = 'http://www.multilinkdata.com.au/scripts/wl_localStorage44.dll/GetLS?ls=';
            
            $attendeesAsString = implode(',', $attendees);
            
            //prepending the length of localstorage
            if ($numberOfAttendees = $this->input->post("numberOfAttendees")) {

                $attendeesAsString = $numberOfAttendees . $attendeesAsString;
            }
            
            log_message('debug', print_r('attendeesAsString is  : ' . $attendeesAsString, true));
            
            $newParaToPass = $attendeesAsString;
            
            $newEntireStringForCurl = $newUrlForCurl . $newParaToPass;
            
            //$tempAllTogether = 'http://www.multilinkdata.com.au/scripts/wl_localStorage44.dll/GetLS?ls=1{"office_id":"1","user_id":"55","id":"112683","ofi_date":"02072014","phone":"321654987","name":"Steve","surname":"SURNAME\", JUNK","notes":"TEST, WITH \"\" JUNK IN NOTES","key":"ofi-1-112683-02072014-0","ofi_idx":"5979"}';
            $tempAllTogether = 'http://www.multilinkdata.com.au/scripts/wl_localStorage44.dll/GetLS?ls=19{"timestamp":1400838931561,"userId":"5373b9fa-e4fe-6a18-1af1-29e6b1a93bb7-92e"},[{"id":71,"text":"(A) Immediatte Sellers","suggest":"(A) Immediatte Sellers"},{"id":18,"text":"1300 Respondee","suggest":"1300 Respondee"},{"id":15,"text":"Accountants","suggest":"Accountants"},{"id":96,"text":"Appraised","suggest":"Appraised"},{"id":12,"text":"Architects","suggest":"Architects"},{"id":53,"text":"Archived","suggest":"Archived"},{"id":103,"text":"Bills_Reiv_Members_April_26","suggest":"Bills_Reiv_Members_April_26"},{"id":1000011,"text":"Builders","suggest":"Builders"},{"id":1,"text":"Buyer","suggest":"Buyer"},{"id":1000005,"text":"Buyer - Advocates","suggest":"Buyer - Advocates"},{"id":31,"text":"Buyer - Pool","suggest":"Buyer - Pool"},{"id":33,"text":"Buyer - Potential Mortgage","suggest":"Buyer - Potential Mortgage"},{"id":32,"text":"Buyer - Potential Vendor","suggest":"Buyer - Potential Vendor"},{"id":3,"text":"Buyer - Purchased Externally","suggest":"Buyer - Purchased Externally"},{"id":1000008,"text":"Buyer - Purchased through Bill","suggest":"Buyer - Purchased through Bill"},{"id":25,"text":"Buyer - Purchased through Christian","suggest":"Buyer - Purchased through Christian"},{"id":24,"text":"Buyer - Purchased through Frank","suggest":"Buyer - Purchased through Frank"},{"id":26,"text":"Buyer - Purchased through Real Estate Gallery","suggest":"Buyer - Purchased through Real Estate Gallery"},{"id":35,"text":"Buyer - Referrals","suggest":"Buyer - Referrals"},{"id":98,"text":"Commercial Agents","suggest":"Commercial Agents"},{"id":7,"text":"Corporate Relocation","suggest":"Corporate Relocation"},{"id":6,"text":"Developer","suggest":"Developer"},{"id":1000012,"text":"Display Homes","suggest":"Display Homes"},{"id":1000013,"text":"Draftsman","suggest":"Draftsman"},{"id":17,"text":"Editor","suggest":"Editor"},{"id":1000002,"text":"Estate Agents","suggest":"Estate Agents"},{"id":14,"text":"Financial Advisers","suggest":"Financial Advisers"},{"id":85,"text":"Hocking Stuart Directors","suggest":"Hocking Stuart Directors"},{"id":62,"text":"Jim Testing","suggest":"Jim Testing"},{"id":89,"text":"Jims Mass Multilink Mailout","suggest":"Jims Mass Multilink Mailout"},{"id":44,"text":"Jims Multilink Leads","suggest":"Jims Multilink Leads"},{"id":8,"text":"Landlord Current","suggest":"Landlord Current"},{"id":66,"text":"Landlord Previous","suggest":"Landlord Previous"},{"id":38,"text":"Leads (Unqualified)","suggest":"Leads (Unqualified)"},{"id":47,"text":"Lookie Loo","suggest":"Lookie Loo"},{"id":79,"text":"MAIL_LIST_BURWODD_BH_ONLY","suggest":"MAIL_LIST_BURWODD_BH_ONLY"},{"id":80,"text":"MAIL_LIST_BURWODD_OFFICES_ONLY","suggest":"MAIL_LIST_BURWODD_OFFICES_ONLY"},{"id":75,"text":"MAIL_LIST_BURWOOD","suggest":"MAIL_LIST_BURWOOD"},{"id":82,"text":"MAIL_LIST_BURWOOD_BH_ATTENDEES","suggest":"MAIL_LIST_BURWOOD_BH_ATTENDEES"},{"id":84,"text":"MAIL_LIST_BURWOOD_CONFIRM","suggest":"MAIL_LIST_BURWOOD_CONFIRM"},{"id":74,"text":"MAIL_LIST_GEELONG","suggest":"MAIL_LIST_GEELONG"},{"id":81,"text":"MAIL_LIST_GEELONG_BH_ATTENDEES","suggest":"MAIL_LIST_GEELONG_BH_ATTENDEES"},{"id":77,"text":"MAIL_LIST_GEELONG_BH_ONLY","suggest":"MAIL_LIST_GEELONG_BH_ONLY"},{"id":83,"text":"MAIL_LIST_GEELONG_CONFIRM","suggest":"MAIL_LIST_GEELONG_CONFIRM"},{"id":78,"text":"MAIL_LIST_GEELONG_OFFICES_ONLY","suggest":"MAIL_LIST_GEELONG_OFFICES_ONLY"},{"id":55,"text":"Mailing List","suggest":"Mailing List"},{"id":99,"text":"Marie Rollover Clients","suggest":"Marie Rollover Clients"},{"id":70,"text":"MarshallWhite","suggest":"MarshallWhite"},{"id":1000016,"text":"Medallion","suggest":"Medallion"},{"id":1000021,"text":"Moonah Links","suggest":"Moonah Links"},{"id":1000015,"text":"Moonah Purchaser","suggest":"Moonah Purchaser"},{"id":45,"text":"Multilink - Agents - Vic","suggest":"Multilink - Agents - Vic"},{"id":97,"text":"Multilink - Balwyn","suggest":"Multilink - Balwyn"},{"id":39,"text":"Multilink - Becs People","suggest":"Multilink - Becs People"},{"id":40,"text":"Multilink - Becs People Sent","suggest":"Multilink - Becs People Sent"},{"id":52,"text":"Multilink Critical","suggest":"Multilink Critical"},{"id":43,"text":"Multilink Glenwaverly Users","suggest":"Multilink Glenwaverly Users"},{"id":37,"text":"Multilink Lead Users","suggest":"Multilink Lead Users"},{"id":56,"text":"Multilink Local Projects","suggest":"Multilink Local Projects"},{"id":72,"text":"Multilink Potential Missed","suggest":"Multilink Potential Missed"},{"id":1000009,"text":"Multilink Potentials","suggest":"Multilink Potentials"},{"id":1000020,"text":"Multilink Trainees 4-6","suggest":"Multilink Trainees 4-6"},{"id":27,"text":"Multilink Trainees 9-11","suggest":"Multilink Trainees 9-11"},{"id":1000003,"text":"Multilink Users","suggest":"Multilink Users"},{"id":100,"text":"Multilink Users Elite Real Estate","suggest":"Multilink Users Elite Real Estate"},{"id":36,"text":"MULTILINK_SITES","suggest":"MULTILINK_SITES"},{"id":111,"text":"NJ Directors","suggest":"NJ Directors"},{"id":90,"text":"Owners Corp - Drummond 320","suggest":"Owners Corp - Drummond 320"},{"id":19,"text":"Personal","suggest":"Personal"},{"id":95,"text":"Platinum Agent","suggest":"Platinum Agent"},{"id":106,"text":"PORTPLUS IMPORT","suggest":"PORTPLUS IMPORT"},{"id":1000018,"text":"Portsea Golf Club","suggest":"Portsea Golf Club"},{"id":1000007,"text":"Potential Landlords","suggest":"Potential Landlords"},{"id":51,"text":"Potential Seller","suggest":"Potential Seller"},{"id":104,"text":"POTENTIAL SELLER - HOT","suggest":"POTENTIAL SELLER - HOT"},{"id":105,"text":"POTENTIAL SELLER - WARM","suggest":"POTENTIAL SELLER - WARM"},{"id":88,"text":"PROFESSIONAL OFFICES","suggest":"PROFESSIONAL OFFICES"},{"id":67,"text":"Purchaser Current","suggest":"Purchaser Current"},{"id":65,"text":"Purchaser Previous","suggest":"Purchaser Previous"},{"id":5,"text":"PVOOA","suggest":"PVOOA"},{"id":1000010,"text":"Real Estate Agents","suggest":"Real Estate Agents"},{"id":49,"text":"Referal from another agent","suggest":"Referal from another agent"},{"id":50,"text":"Referal sent to another agent","suggest":"Referal sent to another agent"},{"id":102,"text":"REIV EMail List","suggest":"REIV EMail List"},{"id":1000006,"text":"Rental Managers - Other Agents","suggest":"Rental Managers - Other Agents"},{"id":60,"text":"REView Agents Vic","suggest":"REView Agents Vic"},{"id":11,"text":"Sales Team","suggest":"Sales Team"},{"id":64,"text":"Seller Current","suggest":"Seller Current"},{"id":63,"text":"Seller Previous","suggest":"Seller Previous"},{"id":13,"text":"Solicitor","suggest":"Solicitor"},{"id":1000017,"text":"Sorrento Golf Club","suggest":"Sorrento Golf Club"},{"id":1000019,"text":"Sorrento Portsea Golf Club","suggest":"Sorrento Portsea Golf Club"},{"id":9,"text":"Suppliers","suggest":"Suppliers"},{"id":1000014,"text":"Surveyors","suggest":"Surveyors"},{"id":4,"text":"Tenant Current","suggest":"Tenant Current"},{"id":69,"text":"Tenant Current","suggest":"Tenant Current"},{"id":87,"text":"Tenant Current","suggest":"Tenant Current"},{"id":68,"text":"Tenant Previous","suggest":"Tenant Previous"},{"id":46,"text":"Testing Category","suggest":"Testing Category"},{"id":91,"text":"The Olives 320 Drummond","suggest":"The Olives 320 Drummond"},{"id":16,"text":"Town Planners","suggest":"Town Planners"},{"id":2,"text":"Unqualified","suggest":"Unqualified"},{"id":94,"text":"Unread","suggest":"Unread"},{"id":86,"text":"Unread","suggest":"Unread"}],{"office_id":"1","user_id":"2","id":"284716","ofi_date":"26102013","name":"Linda","phone":"0408800847","notes":"Early Stages - but seemed interested","key":"ofi-1-284716-26102013-0"},{"office_id":"1","user_id":"2","id":"284716","ofi_date":"26102013","name":"Tony","surname":"Noonan","phone":"0419875","price":850000,"email":"tonynoonam@hotmail.com","notes":"O/O","key":"ofi-1-284716-26102013-1"},{"office_id":"1","user_id":"2","id":"284716","ofi_date":"26102013","name":"Geoff","phone":"0408877455","email":"amov@melbpc.org.au","interested":1,"wants_sect32":1,"notes":"O/O, had doubts here??","price":1000000,"key":"ofi-1-284716-26102013-10"},{"office_id":"1","user_id":"2","id":"284716","ofi_date":"26102013","name":"Tony","surname":"Dickinson","phone":"0430595010","price":800000,"email":"aabdickinson@gmail.com","interested":1,"wants_sect32":1,"notes":"O/O likes it, but only at the right price. Concerned about Petrol Station at rear","key":"ofi-1-284716-26102013-11"},{"office_id":"1","user_id":"2","id":"284716","ofi_date":"26102013","name":"Joshua","phone":"0412348227","notes":"Bedrms too small","key":"ofi-1-284716-26102013-12"},{"office_id":"1","user_id":"2","id":"284716","ofi_date":"26102013","key":"ofi-1-284716-26102013-13"},{"office_id":"1","user_id":"2","id":"284716","ofi_date":"26102013","name":"Lucy","phone":"0451519728","price":820000,"email":"hobson.lucy@gmail.com","notes":"Too Small - House O/O","key":"ofi-1-284716-26102013-2"},{"office_id":"1","user_id":"2","id":"284716","ofi_date":"26102013","name":"Jess","surname":"Martin","phone":"0439918635","price":850000,"notes":"Bedrms too small, O/O","email":"jmartin@evansandpartners.com.au","key":"ofi-1-284716-26102013-3"},{"office_id":"1","user_id":"2","id":"284716","ofi_date":"26102013","name":"Mark","phone":"0434561600","interested":1,"wants_sect32":1,"email":"mark.frayman@bhpbilliton.com","notes":"Maybe a bit too small???","key":"ofi-1-284716-26102013-4"},{"office_id":"1","user_id":"2","id":"284716","ofi_date":"26102013","name":"Laila","phone":"0404287287","notes":"Nice but not for her. Br too small","key":"ofi-1-284716-26102013-5"},{"office_id":"1","user_id":"2","id":"284716","ofi_date":"26102013","name":"paul","phone":"0432446600","email":"paul.podbury@gmail.com","interested":1,"wants_sect32":1,"key":"ofi-1-284716-26102013-6"},{"office_id":"1","user_id":"2","id":"284716","ofi_date":"26102013","name":"Richard","surname":"Mills","phone":"0409772975","price":800000,"email":"ramills@chariot.net.au","interested":1,"wants_sect32":1,"notes":"Investor","key":"ofi-1-284716-26102013-7"},{"office_id":"1","user_id":"2","id":"284716","ofi_date":"26102013","name":"Adele","surname":"hanafan","notes":"Nice but not what looking for. Small Bedrms and rear fences.","key":"ofi-1-284716-26102013-8"},{"office_id":"1","user_id":"2","id":"284716","ofi_date":"26102013","name":"Andrew","phone":"0459827722","notes":"not what Im looking for - too tight","key":"ofi-1-284716-26102013-9"},{"office_id":"1","user_id":"2","id":"40000005","ofi_date":"17102013","name":"night","surname":"test","phone":"0418891890","email":"bill@realestategallery.com.au","interested":"1","hot_prospect":"1","wants_sect32":"1","notes":"loves property","key":"ofi-1-40000005-17102013-0","ofi_idx":"1497","investor":"","session_id":"","download_status":"99","price":"","address":"","insert_date_time":"2013-10-17 22:26:32.000","finalised":"0"},{"office_id":"1","user_id":"2","id":"40000005","ofi_date":"17102013","name":"No","surname":"Connection","phone":"0413512566","email":"j.kent@realestategallery.com.au","interested":"1","hot_prospect":"1","wants_sect32":"1","notes":"loves property 2","key":"ofi-1-40000005-17102013-1","ofi_idx":"1498","investor":"","session_id":"","download_status":"99","price":"","address":"","insert_date_time":"2013-10-17 22:26:32.123","finalised":"0"},{"office_id":"1","user_id":"2","id":"40000005","ofi_date":"17102013","name":"not","surname":"Warm","phone":"0418891890","email":"bill@multilink.com.au","notes":"loves property 3 notconnected","key":"ofi-1-40000005-17102013-2","ofi_idx":"1499","interested":"","investor":"","session_id":"","download_status":"99","hot_prospect":"","price":"","address":"","wants_sect32":"","insert_date_time":"2013-10-17 22:26:32.220","finalised":"0"}';
            
            //$newEntireStringForCurl = $tempAllTogether;
            
            $newEntireStringForCurl = str_replace('"', '%22', $newEntireStringForCurl);
            $newEntireStringForCurl = str_replace('{', '%7b', $newEntireStringForCurl);
            $newEntireStringForCurl = str_replace('}', '%7d', $newEntireStringForCurl);
            $newEntireStringForCurl = str_replace(' ', '%20', $newEntireStringForCurl);
            
            log_message('debug', print_r('newEntireStringForCurl is  : ' . $newEntireStringForCurl, true));
            
            $ch = curl_init($newEntireStringForCurl);
            
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $output = curl_exec($ch);
            curl_close($ch);
            
            //return $output;
            
            log_message('debug', print_r('output from web service is  : ' . $output, true));
            
            // ********************HERE NEED TO CHECK $output variable
            if (stripos($output, 'office_id') !== false) {
                $this->response->set_result(true);
                $this->response->set_message("Sync  succeeded.");
                
                //$result['success'][] = $this->_get_current_time() . " OK> sms to vendor, " . $stock['vendor_firstname'] . " " . $stock['vendor_surname'] . ", " . $destination;
                
                
            } else {

                $this->response->set_result(false);
                $this->response->set_message("Sync data failed. found data but was not able to sync it.");
            }
        } else {
            $this->response->set_result(false);
            $this->response->set_message("Sync data failed. data attendees is not found or empty.");
        }
        echo $this->response->get_response_json();
    }
    
    //mod.  alternatively could turn array to xml string, pass it to stored proc
    
    public function simplified_sync_all_ofis_together() {

        log_message('debug', '****************************************************************** in stock/controllers/api/simplified_sync_all_ofis_together');
        
        $rtn_attendees = array();
        
        $tempReturn = '';
        
        if ($attendees = $this->input->post("attendees")) {

            $attendees_as_xml = buildXMLData($attendees);
            
            $tempReturn = $this->ofi_m->add_or_update_ofi_all_together($attendees_as_xml);
            
            $this->response->set_result(true);
            $this->response->set_data($rtn_attendees);
            $this->response->set_message("Sync data success. ofis added or updated .");
        } else {
            $this->response->set_result(false);
            $this->response->set_message("Sync data failed. data attendees is not found or empty.");
        }
        echo $this->response->get_response_json();
    }
    
    /**
     * Build A XML Data Set
     *TODO:MOVE THIS TO A LIBRARY!
     * @param array $data Associative Array containing values to be parsed into an XML Data Set(s)
     * @param string $startElement Root Opening Tag, default fx_request
     * @param string $xml_version XML Version, default 1.0
     * @param string $xml_encoding XML Encoding, default UTF-8
     * @return string XML String containig values
     * @return mixed Boolean false on failure, string XML result on success
     */
    public function buildXMLData($data, $startElement = 'fx_request', $xml_version = '1.0', $xml_encoding = 'UTF-8') {
        if (!is_array($data)) {
            $err = 'Invalid variable type supplied, expected array not found on line ' . __LINE__ . " in Class: " . __CLASS__ . " Method: " . __METHOD__;
            trigger_error($err);
            if ($this->_debug) echo $err;
            return false;
            
            //return false error occurred
            
            
        }
        $xml = new XmlWriter();
        $xml->openMemory();
        $xml->startDocument($xml_version, $xml_encoding);
        $xml->startElement($startElement);
        
        /**
         * Write XML as per Associative Array
         * @param object $xml XMLWriter Object
         * @param array $data Associative Data Array
         */
        function write(XMLWriter $xml, $data) {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $xml->startElement($key);
                    write($xml, $value);
                    $xml->endElement();
                    continue;
                }
                $xml->writeElement($key, $value);
            }
        }
        write($xml, $data);
        
        $xml->endElement();
        
        //write end element
        //Return the XML results
        return $xml->outputMemory(true);
    }
    
    /**
     * TODO: MOVE TO A LIBRARY
     *tool for debugging
     *
     */
    
    function varDumpToString($var) {
        ob_start();
        var_dump($var);
        $result = ob_get_clean();
        return $result;
    }
}

