<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Ofi_m extends MY_Model
{
    
    private $_table_ofi_results = 'OFI_RESULTS';
    
    /**
     * attempted simplification
     * adds or updates ofi, depending on whether ofi_idx exists (handled in stored proc)
     * @param $ofi
     * @return mixed
     */
    public function add_or_update_ofi($ofi) {
        
        // remove ofi_idx field as it is an incremental key
        
        log_message('debug', '****************************************************************** in ofi_m/add_or_update_ofi');
        
        foreach ($ofi as $key => $value) {
            
            //$value = str_replace("'", "", $value);
            //$ofi[$key] = $value;
            log_message('debug', print_r('key is: ' . $key . '. value is: ' . $value, true));
        }
        
        $ofi = $this->set_OFI_result_string($ofi);
        
        //temp for Frankie issues
        
        foreach ($ofi as $key => $value) {
            
            $value = str_replace("'", "", $value);
            $ofi[$key] = $value;
            log_message('debug', print_r('key is: ' . $key . '. value is: ' . $value, true));
        }
        
        //changing keys of $ofi array all to lowercase
        $ofi = array_change_key_case($ofi, CASE_LOWER);
        
        foreach ($ofi as $key => $value) {
            log_message('debug', print_r('key is: ' . $key . '. value is: ' . $value, true));
        }
        
        //mod.  adding parameter for my_notes and potential seller at the end
		// all these values are needed and some of them are loaded from that function that 
		// returns an array from local storage that is the peoples names and such
		// others are probably loaded from session data such as the current user
		// and some of these fields are probably redundant so just hard coded
		// don think we are suing hot_prospect so probably just hard codd to
		// it  should run OK on windows.  Does it not???? okay and tell me functionality of log_messgase function is it saving data or just printing log files 
		//I think it is writing to file in n Application/log files direcotry okay so leeme insert dynamic parameter that comming from window then i will show response are you ready ?yes shall i disconnect teamviwer ? Why not keep connection? 
        
        $sql = 'exec wl_write_ofi_results3 ' 
        		. $this->db->escape($ofi['office_id']) 
        		. ', ' . $this->db->escape($ofi['id'])
        		. ', ' . $this->db->escape($ofi['user_id']) 
        		. ', ' . $this->db->escape($ofi['ofi_date']) 
        		. ', ' . (isset($ofi["ofi_idx"]) ? $this->db->escape($ofi["ofi_idx"]) : '0') 
        		. ', ' . (isset($ofi["name"]) ? $this->db->escape($ofi["name"]) : '\'\'') 
        		. ', ' . (isset($ofi['phone']) ? $this->db->escape($ofi['phone']) : '\'\'') 
        		. ', ' . (isset($ofi['notes']) ? $this->db->escape($ofi['notes']) : '\'\'') 
        		. ', ' . (isset($ofi['interested']) ? $this->db->escape($ofi['interested']) : '0') 
        		. ', ' . (isset($ofi['investor']) ? $this->db->escape($ofi['investor']) : '0') 
        		. ', ' . (isset($ofi['hot_prospect']) ? $this->db->escape($ofi['hot_prospect']) : '0') 
        		. ', ' . (isset($ofi['price']) ? $this->db->escape($ofi['price']) : '\'\'') 
        		. ', ' . (isset($ofi['email']) ? $this->db->escape($ofi['email']) : '\'\'') 
        		. ', ' . (isset($ofi['address']) ? $this->db->escape($ofi['address']) : '\'\'') 
        		. ', ' . (isset($ofi['surname']) ? $this->db->escape($ofi['surname']) : '\'\'') 
        		. ', ' . (isset($ofi['wants_sect32']) ? $this->db->escape($ofi['wants_sect32']) : 0) 
        		. ', ' . (isset($ofi['result']) ? $this->db->escape($ofi['result']) : '\'\'') . ', ' 
        		. (isset($ofi['my_notes']) ? $this->db->escape($ofi['my_notes']) : '\'\'') 
        		. ', ' . (isset($ofi['potential_seller']) ? $this->db->escape($ofi['potential_seller']) : 0)
        		. ', ' . (isset($ofi['activiy_type']) ? $this->db->escape($ofi['activiy_type']) : $this->db->escape('Inspected') );
        
        log_message('debug', print_r('sql is : ' . $sql, true));
        
        if ($messageFromStoredProc = $this->db->query($sql)->result()) {
            
            $messageVarDumped = $this->varDumpToString($messageFromStoredProc);
            
            $attendeeFromProc = $messageFromStoredProc[0];
            
            $attendeeFromProcAsArray = (array)$attendeeFromProc;
            
            $ofi['ofi_idx'] = $attendeeFromProcAsArray['OFI_IDX'];
            return $ofi;
        } else {
            return false;
        }
    }
    
    /**
     * Get message and subject by giving ofi_idx
     * 
     * @param sting $OfiIdx
     * @param string $medium
     * @param string $sender
     * @return mixed
     */
    public function get_buyer_message_and_subject($OfiIdx,$medium='email',$sender='ACTIVITY') {
    	$sql = 'exec wl_get_buyer_message_and_subject ' 
    			. $this->db->escape($OfiIdx) . ', ' 
    			. $this->db->escape($medium);//. ',' 
    			//. $this->db->escape($sender);
    	$result = $this->db->query($sql)->result();
    	return $result;
    }
    
    /**
     * Retrieve Frequent Texts
     * @param unknown $office_id
     * @param unknown $user_id
     * @return mixed|boolean
     */
    public function get_text($office_id, $user_id){
    	$sql = "select FREQUENT_TEXT FROM USERS WHERE  USER_OFFICE_ID = ".$this->db->escape($office_id)." AND  USER_ID = ".$this->db->escape($user_id);
    
    	if($row = $this->db->query($sql)->row_array()){
    		return json_decode($row['FREQUENT_TEXT']);
    	}else{
    		return false;
    	}
    }
    
    /**
     * version called from api method that uses ls dump
     * adds or updates ofi, depending on whether ofi_idx exists (handled in stored proc)
     * @param $ofi
     * @return mixed
     */
    public function add_or_update_attendee_ls_dump($attendee) {
        
        // remove ofi_idx field as it is an incremental key
        
        log_message('debug', '****************************************************************** in ofi_m/add_or_update_attendee_ls_dump');
        
        //mod. result sent back should be a true if the attendee was succesfully added or updated.
        //using this variable to pass back
        //initially set to false
        $attendeeSuccesfullyAddedOrUpdated = false;
        
        $attendeeVarDump = VarDumpToString($attendee);
        log_message('debug', print_r(' attendeeVarDump is : ' . $attendeeVarDump, true));
        
        $attendeeAsArray = explode($attendee, ',');
        
        $attendeeAsArrayVarDump = VarDumpToString($attendeeAsArray);
        log_message('debug', print_r(' attendeeAsArrayVarDump is : ' . $attendeeAsArrayVarDump, true));
        
        $attendeeJsonDecoded = json_decode($attendee, true);
        
        $attendeeJsonDecodedVarDump = VarDumpToString($attendeeJsonDecoded);
        log_message('debug', print_r(' attendeeJsonDecodedVarDump is : ' . $attendeeJsonDecodedVarDump, true));
        
        $attendeeJsonDecoded = $this->set_OFI_result_string($attendeeJsonDecoded);
        
        //temp for Frankie issues
        
        foreach ($attendeeJsonDecoded as $key => $value) {
            
            $value = str_replace("'", "", $value);
            $attendeeJsonDecoded[$key] = $value;
            log_message('debug', print_r('key is: ' . $key . '. value is: ' . $value, true));
        }
        
        //changing keys of $ofi array all to lowercase
        $attendeeJsonDecoded = array_change_key_case($attendeeJsonDecoded, CASE_LOWER);
        
        foreach ($attendeeJsonDecoded as $key => $value) {
            log_message('debug', print_r('key is: ' . $key . '. value is: ' . $value, true));
        }
        
        //mod.  adding parameter for my_notes and potential seller at the end
        
        
        //investor field: 0 - inspection and 1: Introduced
        $sql = 'exec wl_write_ofi_results3 ' . $this->db->escape($attendeeJsonDecoded['office_id']) . ', ' . $this->db->escape($attendeeJsonDecoded['id']) . ', ' . $this->db->escape($attendeeJsonDecoded['user_id']) . ', ' . $this->db->escape($attendeeJsonDecoded['ofi_date']) . ', ' . (isset($attendeeJsonDecoded["ofi_idx"]) ? $this->db->escape($attendeeJsonDecoded["ofi_idx"]) : '0') . ', ' . (isset($attendeeJsonDecoded["name"]) ? $this->db->escape($attendeeJsonDecoded["name"]) : '\'\'') . ', ' . (isset($attendeeJsonDecoded['phone']) ? $this->db->escape($attendeeJsonDecoded['phone']) : '\'\'') . ', ' . (isset($attendeeJsonDecoded['notes']) ? $this->db->escape($attendeeJsonDecoded['notes']) : '\'\'') . ', ' . (isset($attendeeJsonDecoded['interested']) ? $this->db->escape($attendeeJsonDecoded['interested']) : '0') . ', ' . (isset($attendeeJsonDecoded['investor']) ? $this->db->escape($attendeeJsonDecoded['investor']) : '0') . ', ' . (isset($attendeeJsonDecoded['hot_prospect']) ? $this->db->escape($attendeeJsonDecoded['hot_prospect']) : '0') . ', ' . (isset($attendeeJsonDecoded['price']) ? $this->db->escape($attendeeJsonDecoded['price']) : '\'\'') . ', ' . (isset($attendeeJsonDecoded['email']) ? $this->db->escape($attendeeJsonDecoded['email']) : '\'\'') . ', ' . (isset($attendeeJsonDecoded['address']) ? $this->db->escape($attendeeJsonDecoded['address']) : '\'\'') . ', ' . (isset($attendeeJsonDecoded['surname']) ? $this->db->escape($attendeeJsonDecoded['surname']) : '\'\'') . ', ' . (isset($attendeeJsonDecoded['wants_sect32']) ? $this->db->escape($attendeeJsonDecoded['wants_sect32']) : 0) . ', ' . (isset($attendeeJsonDecoded['result']) ? $this->db->escape($attendeeJsonDecoded['result']) : '\'\'') . ', ' . (isset($attendeeJsonDecoded['my_notes']) ? $this->db->escape($attendeeJsonDecoded['my_notes']) : '\'\'') . ', ' 
        		. (isset($attendeeJsonDecoded['potential_seller']) ? $this->db->escape($attendeeJsonDecoded['potential_seller']) : 0) . ','
        		. (isset($attendeeJsonDecoded['activiy_type']) ? $this->db->escape($attendeeJsonDecoded['activiy_type']) : 'Inspected');
        
        log_message('debug', print_r('sql is : ' . $sql, true));
        
        if ($messageFromStoredProc = $this->db->query($sql)->result()) {
            
            $messageVarDumped = $this->varDumpToString($messageFromStoredProc);
            
            log_message('debug', print_r('message returned by wl_write_ofi_results3 is: ' . $messageVarDumped, true));
            
            $attendeeFromProc = $messageFromStoredProc[0];
            
            $attendeeFromProcAsArray = (array)$attendeeFromProc;
            
            //mod. result sent back should be a true if the attendee was succesfully added or updated.
            //checking this by checking if the array version of the object returned by the stored proc has an ofi_idx key
            
            if (array_key_exists('OFI_IDX', $attendeeFromProcAsArray)) {
                $attendeeSuccesfullyAddedOrUpdated = true;
            }
            
            //mod.  no longer doing the following key statement
            //$attendeeJsonDecoded['ofi_idx'] = $attendeeFromProcAsArray['OFI_IDX'];
            //return $attendeeSuccesfullyAddedOrUpdated;
            
        }
        
        //returning this variable, true if succesful, false if not
        return $attendeeSuccesfullyAddedOrUpdated;
    }
    
    /**
     * mod.  for streamlined
     * gets attendees for a given ofi (for a specific property on a specific date)
     *
     */
    public function get_list_of_people_who_attended_ofi($office_id, $user_id, $property_id, $ofi_date) {
        
        // remove ofi_idx field as it is an incremental key
        
        log_message('debug', '****************************************************************** in ofi_m/get_list_of_people_who_attended_ofi');
        
        //@OFFICE_ID int, @USER_ID int, @ID int, @OFI_DATE varchar(255)
        
        //$this->load->helper('date');
        
        //if (empty($property_id) or empty($ofi_date)) {
        //    return false;
        // }
        
        //** need to validate if the user can update the contact
        
        $sql = 'exec wl_get_localstorage ' . $this->db->escape($office_id) . ', ' . 
        $this->db->escape($user_id) . ', ' . $this->db->escape($property_id) . ', ' . $this->db->escape($ofi_date);

        if ($messageFromStoredProc = $this->db->query($sql)->result()) {
        	$messageVarDumped = varDumpToString($messageFromStoredProc);
            log_message('debug', print_r('messageVarDumped is : ' . $messageVarDumped, true));
            
            // $attendeeFromProc = $messageFromStoredProc[0];
            
            //$attendeeFromProcAsArray = (array)$attendeeFromProc;
            
            // $ofi['ofi_idx'] = $attendeeFromProcAsArray['OFI_IDX'];
            
            $list_of_people_who_attended_ofi = array();
            
            foreach ($messageFromStoredProc as $key => $value) {
                
                //               log_message('debug', print_r('key is : ' . $key, true));
                // log_message('debug', print_r('value is : ' . $value, true));
                $list_of_people_who_attended_ofi[$key] = (array)$value;
                
                //                         log_message('debug', print_r('buyersAsArray as array is is : ' . $buyersAsArray[$key] , true));
                
                //mod.  temp do the formatting here to avoid the string versions 0 being treated as a positive etc
                //here could try to make string '1' and '0' into 1 and 0
                foreach ($list_of_people_who_attended_ofi[$key] as $arrayKey => $arrayValue) {
                    if ($arrayValue == '0') {
                        $arrayValue = 0;
                        $list_of_people_who_attended_ofi[$key][$arrayKey] = 0;
                    }
                    // code...
                    
                }
            }
            
            //ment need to re
            
            $list_of_people_who_attended_ofiVarDumped = varDumpToString($list_of_people_who_attended_ofi);
            
            log_message('debug', print_r('list_of_people_who_attended_ofiVarDumped is : ' . $list_of_people_who_attended_ofiVarDumped, true));
            return $list_of_people_who_attended_ofi;
        } else {
            return false;
        }
    }
    
    /**
     * new one to get the activity for a given property
     * gets attendees for a given ofi (for a specific property )
     *
     */
    public function get_list_of_people_who_attended_any_ofis_for_a_given_property($office_id, $user_id, $property_id,$ofi_idx=0) {
        
        // remove ofi_idx field as it is an incremental key
        
        log_message('debug', '****************************************************************** in ofi_m/get_list_of_people_who_attended_any_ofis_for_a_given_property');
        
        //@OFFICE_ID int, @USER_ID int, @ID int, @OFI_DATE varchar(255)
        
        //$this->load->helper('date');
        
        if (empty($property_id) or empty($office_id) or empty($user_id)) {
            return false;
        }
        
        //** need to validate if the user can update the contact
        
        $sql = 'exec wl_get_property_activity2 ' . $this->db->escape($office_id) . ', ' . 
        $this->db->escape($user_id) . ', ' . $this->db->escape($property_id).','. $this->db->escape($ofi_idx);
        
        //echo('sql is : ' . $sql);
        
        log_message('debug', print_r('sql is : ' . $sql, true));
        
        if ($messageFromStoredProc = $this->db->query($sql)->result()) {
            
            //echo('messageFromStoredProc is : ' . $messageFromStoredProc);
//             dump_exit($messageFromStoredProc);
            
            $messageVarDumped = $this->varDumpToString($messageFromStoredProc);
            
            log_message('debug', print_r('messageVarDumped is : ' . $messageVarDumped, true));
            
            // $attendeeFromProc = $messageFromStoredProc[0];
            
            //$attendeeFromProcAsArray = (array)$attendeeFromProc;
            
            // $ofi['ofi_idx'] = $attendeeFromProcAsArray['OFI_IDX'];
            
            $list_of_people_who_attended_any_ofis_for_this_property = array();
            
            foreach ($messageFromStoredProc as $key => $value) {
                
                //               log_message('debug', print_r('key is : ' . $key, true));
                // log_message('debug', print_r('value is : ' . $value, true));
                $list_of_people_who_attended_any_ofis_for_this_property[$key] = (array)$value;
                
                //                         log_message('debug', print_r('buyersAsArray as array is is : ' . $buyersAsArray[$key] , true));
                
                //mod.  temp do the formatting here to avoid the string versions 0 being treated as a positive etc
                //here could try to make string '1' and '0' into 1 and 0
                //foreach ($list_of_people_who_attended_any_ofis_for_this_property[$key] as $arrayKey => $arrayValue) {
                //    if ($arrayValue == '0'){
                //        $arrayValue = 0;
                //        $list_of_people_who_attended_ofi[$key][$arrayKey] = 0;
                //    }
                
                //}
                
                //trying to make them lowercase
                $list_of_people_who_attended_any_ofis_for_this_property[$key] = array_change_key_case($list_of_people_who_attended_any_ofis_for_this_property[$key], CASE_LOWER);
            }
            
            //var_dump($list_of_people_who_attended_any_ofis_for_this_property);
            
            //ment need to re
            
            $list_of_people_who_attended_ofiVarDumped = $this->varDumpToString($list_of_people_who_attended_any_ofis_for_this_property);
            
            log_message('debug', print_r('list_of_people_who_attended_ofiVarDumped is : ' . $list_of_people_who_attended_ofiVarDumped, true));
            
            return $list_of_people_who_attended_any_ofis_for_this_property;
        } else {
            return false;
        }
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
    
    /**
     *  check if ofi of a property has been sent sms and emails.
     *
     *  @param $office_id string
     *  @param $stock_id string
     *  @param $user_id string
     *  @param $ofi_date string date value in format 'dmY'
     *
     *  @return notes value if there is any, otherwise return false.
     */
    public function is_ofis_sent($office_id, $stock_id, $user_id, $ofi_date) {
//         $sql = "
//         SELECT * 
//         FROM OFI_RESULTS_SENT 
//         WHERE 
//         OFFICE_ID = " . $this->db->escape($office_id) . " AND " . "ID = " . $this->db->escape($stock_id) . " AND " . "USER_ID = " . $this->db->escape($user_id) . " AND " . "OFI_DATE = " . $this->db->escape($ofi_date);
        
        $sql = 'exec wl_get_ofi_results_sent2 '
        		 . $this->db->escape($office_id) . "," 
        		 . $this->db->escape($stock_id) . "," 
        		 . $this->db->escape($user_id) . "," 
        		 . $this->db->escape($ofi_date);
        $query = $this->db->query($sql);
        
        $response = $query->row_array();
//         dump($response);
        $result = count($response)>0 ? TRUE : FALSE;
//         $query->free_result();
//         dump_exit($response);
        
        return $result;
    }
    
    /**
     * mod. NEW version which calls the stored proc
     * log sent ofi in DB.
     *
     * @param $office_id
     * @param $stock_id
     * @param $user_id
     * @param $ofi_date
     * @param $sent_date
     * @param $notes
     */
    public function ofi_send_write_result_using_SP($office_id, $stock_id, $user_id, $ofi_date, $sent_date, $notes = "",$ofi_idx='') {
        
        log_message('debug', '****************************************************************** in ofi_m/ofi_send_write_result_using_SP');
        
        $query = 'exec wl_write_ofi_results_sent ' . $this->db->escape($office_id) . ', ' . $this->db->escape($stock_id) . ', ' . $this->db->escape($user_id) . ', ' 
        			. $this->db->escape($ofi_date) . ', ' . $this->db->escape($sent_date) . ', ' . $this->db->escape($notes);
        
//         $query = 'exec wl_write_ofi_results_sent2 ' 
//         		. $this->db->escape($office_id) 
//         		. ', ' . $this->db->escape($stock_id) 
//         		. ', ' . $this->db->escape($user_id) 
//         		. ', ' . $this->db->escape($ofi_date) 
//         		. ', ' . $this->db->escape($ofi_idx)
// 		        . ', ' . $this->db->escape($sent_date) 
// 		        . ', ' . $this->db->escape($notes);
        
        log_message('debug', print_r('query is: ' . $query, true));
        
        $this->db->query($query);
    }
    
    /**
     * log sent ofi in DB.
     * DEPRECATED FUNCTION.  NOW USING STORED PROCEDURE VERSION.  TODO: DELETE ONCE TRANSITION TO STORED PROCEDURE COMPLETED.
     * @param $office_id
     * @param $stock_id
     * @param $user_id
     * @param $ofi_date
     * @param $sent_date
     * @param $notes
     */
    public function ofi_send_write_result($office_id, $stock_id, $user_id, $ofi_date, $sent_date, $notes) {
        
        log_message('debug', '****************************************************************** in ofi_m/ofi_send_write_result');
        
        $query = "
        INSERT INTO OFI_RESULTS_SENT (
            OFFICE_ID,ID,USER_ID,OFI_DATE, SENT_DATE,NOTES)
VALUES (" . $this->db->escape($office_id) . "," . $this->db->escape($stock_id) . "," . $this->db->escape($user_id) . "," . $this->db->escape($ofi_date) . "," . $this->db->escape($sent_date) . "," . $this->db->escape($notes) . ")";

$this->db->query($query);
}

private function set_OFI_result_string($attendee) {
    
    log_message('debug', '****************************************************************** in ofi_m/set_OFI_result_string');
    
        //if interested 1, put "interested" in result field
    if (isset($attendee['interested']) AND $attendee['interested']) {
        $attendee['result'] = "Interested";
    }
    
        //if not_interested 1, put "not_interested" in result field
    if (isset($attendee['not_interested']) AND $attendee['not_interested']) {
        $attendee['result'] = "Not Interested";
    }
    
        //added for maybe interested.
    if (isset($attendee['maybe_interested']) AND $attendee['maybe_interested']) {
        $attendee['result'] = "Maybe";
    }
    
        //added  maybe interested.
        // if both interested and not_interested are set to 0
        // then set the result field as null.
    if (isset($attendee['interested']) AND isset($attendee['not_interested']) AND isset($attendee['maybe_interested']) AND !$attendee['interested'] AND !$attendee['maybe_interested'] AND !$attendee['not_interested']) {
        $attendee['result'] = '';
    }
    
    return $attendee;
}

    /**
     * for getting customised messaging template
     *mod now will try to just pass the ofi_idx
     * @param $passedOfiIdx
     * @param $medium is either sms or email
     */
    public function get_message_template($passedOfiIdx, $medium) {
        
        log_message('debug', '****************************************************************** in ofi_m/get_message_template');
        
        //mod.   pass the ofi_idx
        //mod. second parameter is now sms or email
        $sql = 'exec wl_get_buyer_message ' . $passedOfiIdx . ', ' . $medium;
        
        log_message('debug', print_r('sql is : ' . $sql, true));
        
        //the below will need to be adjusted, wasnt'for this stored proc
        
        if ($messageFromStoredProc = $this->db->query($sql)->result()) {
            
            $messageVarDumped = $this->varDumpToString($messageFromStoredProc);
            
            log_message('debug', print_r('messageVarDumped is : ' . $messageVarDumped, true));
            
            $firstFromProc = $messageFromStoredProc[0];
            
            $firstFromProcAsArray = (array)$firstFromProc;
            
            $buyerMessage = $firstFromProcAsArray['BUYER_MESSAGE'];
            
            log_message('debug', print_r('buyerMessage is : ' . $buyerMessage, true));
            
            // $ofi['ofi_idx'] = $attendeeFromProcAsArray['OFI_IDX'];
            return $buyerMessage;
        } else {
            return false;
        }
    }
    
    /**
     * for getting customised messaging template
     *mod now will try to just pass the ofi_idx
     * @param $passedOfiIdx
     * @param $medium is either sms or email
     */
    public function get_message_template_and_subject($passedOfiIdx, $medium, $sender='OFI') {
        
        log_message('debug', '****************************************************************** in ofi_m/get_message_template_and_subject');
        
        //mod.   pass the ofi_idx
        //mod. second parameter is now sms or email
        //$sql = 'exec wl_get_buyer_message_and_subject ' . $passedOfiIdx . ', ' . $medium;
        
        $sql = 'exec wl_get_buyer_message_and_subject2 ' 
        		. $this->db->escape($passedOfiIdx) . ', ' 
        		. $this->db->escape($medium). ',' 
        		. $this->db->escape($sender);
        
        log_message('debug', print_r('sql is : ' . $sql, true));
        
        //the below will need to be adjusted, wasnt'for this stored proc
        $resource = $this->db->query($sql);
        
        /**
         * The success result should be
         * array (size=1)
			  0 => 
			    object(stdClass)[29]
			      public 'BUYER_MESSAGE' => string '<!DOCTYPE html> <html> <body> Hi justin0311,<br><br>Thank you for your interest in our property at <a href="http://www.multilink.com.au/PropertyDetails/MultilinkPropDetails.php?id=112683&office_id=1">5/37 Margaret St, South Yarra</a> .<br><br>As requested, please click the following link to view the documents for this property   <a href="http://www.multilink.com.au/webmultilink/index.php/stock/public/stock/display_doc/1/112683/dcd/18773">Documentation for 5/37 Margaret St</a>  and feel free to contact me on'... (length=877)
			      public 'SUBJECT' => string 'Thanks for your interest' (length=24)
         */
        // New way to handle this
        $the_result = $resource->result();
        
        if (count($the_result)>0) {
        	$object = $the_result[0];
        	return (array)$object;
        }else{
        	return array(
        			'BUYER_MESSAGE' => '',
        			'SUBJECT' =>'' 
        	);
        }
        
        return $resource->result();
        
//         if ($messageFromStoredProc = $resource->result()) {
//         	$resource->free_result();
//             $messageVarDumped = $this->varDumpToString($messageFromStoredProc);
            
//             log_message('debug', print_r('messageVarDumped is : ' . $messageVarDumped, true));
            
//             $firstFromProc = $messageFromStoredProc[0];
            
//             $firstFromProcAsArray = (array)$firstFromProc;
            
//             //in this version passing whole array
//             $buyerMessageAndSubjectArray = $firstFromProcAsArray;
            
//             $buyerMessageAndSubjectArrayVarDumped = $this->varDumpToString($buyerMessageAndSubjectArray);
            
//             log_message('debug', print_r('buyerMessageAndSubjectArrayVarDumped is : ' . $buyerMessageAndSubjectArrayVarDumped, true));
            
//             // $ofi['ofi_idx'] = $attendeeFromProcAsArray['OFI_IDX'];
//             //odbc_free_result($resource);
            
//             return $buyerMessageAndSubjectArray;
//         } else {
//             return false;
//         }
    }
}
