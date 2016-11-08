/**
     * Ofi manager is designed to check if there is failed ofis in local storage,
     *
*/
angular.module('webmultilink').factory('ofi_daemon', function(checkLocalStorage, $rootScope, $log, $location,$window){
        // maximum amount of attendees of an inpection.
        var attendee_max = 99;
        var ofi_api = siteUrl + "/stock/api/";

        // key prefix prepended to each attendee's key in local storage.
        var key_pre = "ofi-";

        // ofi listing path
        var ofi_listing_path = "stock/ofi/listing/";

        // base key of attendee record. The format is
        // office_id-stock_id-ofi_date-
        // 1-1002562-09102013-
        function get_key_base (office_id, stock_id, ofi_date){
            return  key_pre + office_id + "-" +  stock_id + "-" +  ofi_date + "-";
        }


        function attendee_exists(key){
            if(angular.isDefined(key)){
                return true
            }else{
                $log.error("Could not find attendee with key "+ key + " in local storage.");
                return false;
            }
        }

        /**
         * get an attendee from local storage in plain object based on its key.
         *
         * @param key
         * @returns {*}
         */
        function attendee_get(key){
            if(attendee_exists(key)){
                return angular.fromJson(localStorage[key]);
            }
            return null;
        }

        function attendee_update(attendee){
            if(attendee_exists(attendee.key)){
                localStorage[attendee.key] = angular.toJson(attendee)
            }
            return false;
        }

        function is_attendee_key(key){
            if(key.indexOf(key_pre)>-1){
                return true;
            }
            return false;
        }

        function get_new_attendees(){
            var new_attendees = {};

            for(var key in localStorage){
                if(is_attendee_key(key)){
                    var attendee = attendee_get(key);

                    // if an attendee object does not have ofi_idx field,
                    // then it is treated as an new attendee.
                    if(!angular.isDefined(attendee['ofi_idx'])){
                        new_attendees[key] = attendee;
                    }
                }
            }

            return new_attendees;
        }
        /**
         * makes a long array of all local storage contents
         * version that worked comprehensively, as used for login dump
         */
        function get_local_storage_as_array() {

            //mod. no longer deleting this.  created issues with cats
           //localStorage.removeItem('all_categories');
		   // 			
		   var   $testConent =	"{\"office_id\":\"1\",\"id\":\"279759\",\"user_id\":\"86\",\"ofi_date\":\"04112016\",\"ofi_idx\":\"15\",\"phone\":\"\",\"name\":\"test\",\"surname\":\"test\",\"price\":\"\",\"notes\":\"\",\"result\":\"\",\"email\":\"\",\"my_notes\":\"\",\"potential_seller\":0,\"wants_sect32\":0,\"interested\":0,\"maybe_interested\":0,\"not_interested\":0,\"activity_type\":\"Inspected\",\"key\":\"ofi-1-279759-04112016-0\"}";
            var localStContents = [];
			//localStContents.push(testConent);
			//return localStContents;

            for(var key in localStorage){
            //alert(key);
            // alert(localStorage[key]);
                   //localStContents.push(key);
			
            //now again not including this entry, instead of deleting it, to prevent issue with cats
              if (key != 'all_categories' && key.indexOf('avatar_')== -1){
                  localStContents.push(localStorage[key]);
              }
            }
            //alert(localStContents);
			var userAgent = $window.navigator.userAgent;
			console.log(JSON.stringify(userAgent+'Line: 102 '+JSON.stringify(localStorage)));
			//alert(userAgent+' : '+JSON.stringify('Line: 102 '+JSON.stringify(localStorage)));
			//alert(JSON.stringify('Line: 102 '+key));
			// jim 
            return localStContents;
        }
        /**
         * for consistent for se
         */
        function get_all_local_storage_objects(){
            var ls_objects = {};
            for(var key in localStorage){
                        ls_objects[key] = localStorage[key];
                        $log.log('the key:');
                        $log.log(key);
                        $log.log('the object data is :');
                        $log.log(localStorage[key]);
            }
            return ls_objects;
        }
        /**
         * get all attendees that has not been downloaded
         * from local storage in plain object format
         * @returns {{}}
         */
        function get_all_local_available_attendees(){
            var attendees = {};
            for(var key in localStorage){
                if(is_attendee_key(key)){
                    var attendee = attendee_get(key);
                    var download_status = parseInt(attendee.download_status);
                    if(_.isNumber(download_status) && download_status != 99){
                        attendees[key] = attendee;
                        $log.log('the key:');
                        $log.log(key);
                        $log.log('the attendee:');
                        $log.log(attendee);

                    }
                }
            }
            return attendees;
        }

        /**
         * save an attendee
         */
        function save(attendee, success, error, complete){
            // the ofi daemon program don't apply to ofi listing page.
            var abs_url = $location.absUrl();

            if(abs_url.indexOf(ofi_listing_path) > -1){
                $log.log('In ofi listing page');
                return;
            }

            $.ajax({
                async: true,
                timeout: config.timeout * 1000,
                url: siteUrl + '/stock/api/ofi_save/',
                type: 'POST',
                data: {'ofi' : angular.copy(attendee)},
                dataType: 'json',
                success: function(response){
                    // if it is saved successfully, then
                    // update the finalised state.
                    if(response.result === true){
                        if(attendee_exists(attendee.key)){
                            var _attendee = attendee_get(attendee.key);
                            _attendee['finalised'] = 1;
                            attendee_update(_attendee);
                        }
                    }
                    success && success();
                },
                error:function(jqXHR, textStatus, errorThrown ){
                    $log.error("Failed to save attendee");
                    $log.error(attendee);
                    error && error();
                },
                'complete' : function(){
                    complete && complete();
                }
            })
        }

        return {
            /**
             * bulk save attendees in an inspection
             *
             * @param stock_id
             * @param ofi_date_
             * @param ofis
             * @param success
             * @param error
             * @param complete
             */
            'bulk_save_attendees' : function(stock_id, ofi_date_, ofis, success, error, complete){
                $.ajax({
                    url: siteUrl + "/stock/api/bulk_save/" + stock_id + "/" + ofi_date_,
                    timeout: config.timeout * 10000,
                    type: 'POST',
                    data: {'ofis': ofis},
                    dataType:'json',
                    success: function(response){
                        // update attendees in local storage
                        if(response.result === true){
                            angular.forEach(ofis, function(attendee, key){
                                if(angular.isDefined(localStorage[key])){
                                    var local_attendee = angular.fromJson(localStorage[key]);
                                    local_attendee['finalised'] = 1;
                                    localStorage[key] = angular.toJson(local_attendee);
                                }
                            })
                        }

                        success(response);
                    },
                    error: function(){
                        angular.forEach(ofis, function(attendee, key){
                            if(angular.isDefined(localStorage[key])){
                                var local_attendee = angular.fromJson(localStorage[key]);
                                local_attendee['finalised'] = 0;
                                localStorage[key] = angular.toJson(local_attendee);
                            }

                        })
                        error();
                    },
                    'complete':function(){
                        complete();
                    }
                })
            },

            'clean_old_ofi': function(){
                return;
            },

            /**
             * construct the key for a new attendee in an inspection.
             *
             * @param office_id
             * @param stock_id
             * @param ofi_date
             * @returns {*}
             */
            'get_next_key' : function(office_id, stock_id, ofi_date){
                for(var i = 0; i<  attendee_max; i++){
                    var nextKey = get_key_base(office_id, stock_id, ofi_date) + i;
                    if(angular.isUndefined(localStorage[nextKey])){
                        return nextKey;
                    }else{
                        continue;
                    }
                }
                return false;
            },

            /**
             * get all attendess of a specific date, stock and office.
             *
             * @param office_id
             * @param stock_id
             * @param ofi_date
             * @returns {{}}
             */
            'get_local_attendees' : function(office_id, stock_id, ofi_date){
                var attendees = {};

                for(var i=0 ; i < attendee_max; i++){
                    var attendee_key = get_key_base(office_id, stock_id, ofi_date) + i;
                    if(angular.isDefined(localStorage[attendee_key])){
                        attendees[attendee_key] = angular.fromJson(localStorage[attendee_key]);

                        // convert price_to to int
                        var price_to = parseInt(attendees[attendee_key]['price']);
                        if(price_to){
                            attendees[attendee_key]['price'] = price_to;
                        }else{
                            attendees[attendee_key]['price'] = null;
                        }

                        // use the text of result field to determine the
                        // if the interested and not_interested fields
                        var result = attendees[attendee_key]['result'];
                        if(!result){
                            delete attendees[attendee_key]['interested'];
                            delete attendees[attendee_key]['not_interested'];
                            delete attendees[attendee_key]['maybe_interested'];


                        }else{
                            result = result.toLowerCase();
                            if(result == "interested"){
                                attendees[attendee_key]['interested'] = 1;
                                attendees[attendee_key]['not_interested'] = 0;
                                attendees[attendee_key]['maybe_interested'] = 0;
                               
                            }
                            if(result == "not interested"){
                                attendees[attendee_key]['not_interested'] = 1;
                                attendees[attendee_key]['interested'] = 0;
                                attendees[attendee_key]['maybe_interested'] = 0;
                            
                            }

                            if(result == "Maybe"){
                                attendees[attendee_key]['maybe_interested'] = 1;
                                attendees[attendee_key]['interested'] = 0;
                                attendees[attendee_key]['not_interested'] = 0;
                            
                            }



                        }
                    }
                }

                return attendees;
            },

            /**
             * It is a basic 2 ways sync as on the server side,
             * only ofi_idx and download_status can update local storage.
             * and the rest of fields of local storage update the counterpart in the server side.
             *
             */

'sync' : function(beforeSend_func, success, error, complete ){
                (function($){
                    var attendees = get_all_local_available_attendees();

                    if(!$.isEmptyObject(attendees)){
                        $.ajax({
                            async: false,
                            beforeSend : function(){
                                $log.log("====== Sync Starts ======");
                                beforeSend_func && beforeSend_func();
                            },
                            timeout : config.timeout * 1000,
                            url : ofi_api + "sync",
                            type: "POST",
                            dataType: "json",
                            data : {
                                'attendees' : attendees
                            },
                            success : function(response){
                                if(response.result === true){
                                    // update attendee in local storage.
                                    angular.forEach(response.data, function(rtn_attendee, key){

                                        // retrieve the corresponding attendee in plain object from local storage.
                                        // and update fields from remote attendee, but it will not kill the
                                        // fields that the remote attendee doesn't have.
                                        if(angular.isDefined(localStorage[key])){
                                            var local_attendee = attendee_get(key);
                                            angular.forEach(rtn_attendee, function(value,key){
                                                    local_attendee[key] = value;
                                            })
                                            localStorage[key] = angular.toJson(local_attendee);
                                        }
                                    })
                                }
                                success && success();
                                $log.log(response);
                            },
                            error: function(){
                                $rootScope.error = "Network not available. Please return to this screen and resend later.";
                                error && error();
                            },
                            complete: function(){
                                complete && complete();
                                $log.log("====== Sync end ======");
                            }
                        })
                    }

                })(window.jQuery);
            },





            /**
             * mod. this one gets a different dump of the local storage in an array ( as in the login )
             * then calls simplified_sync_through_web_service in the api php
             */

'simplified_sync_through_web_service' : function(beforeSend_func, success, error, complete ){
      //alert('there');


                $log.log("====== ofi_daemon.simplified_sync_through_web_service called ======");

                (function($){
                    //diff array version of local storage, as in login
                    var attendees = get_local_storage_as_array();

                    //attendees.removeItem('all_categories');

                    var numberOfAttendees = '' + localStorage.length;

                    //attendees = localStorage.length + '' + attendees;


                    if(!$.isEmptyObject(attendees)){
                        $.ajax({
                            async: false,
                            beforeSend : function(){
                                $log.log("====== Sync Starts ======");
                                beforeSend_func && beforeSend_func();
                            },
                            timeout : config.timeout * 1000,
                            url : ofi_api + "simplified_sync_through_web_service",
                            type: "POST",
                            dataType: "json",
                            data : {
                                'attendees' : attendees, 
                                'numberOfAttendees' : numberOfAttendees

                            },
                            success : function(response){
                                if(response.result === true){
                                    // update attendee in local storage.



                                    console.log("====== about to clear local storage ======");

                                    //alert('all good, need to uncomment clearing of local storage');
                                    //localStorage.clear();


                                    
                                }
                                success && success();
                                $log.log(response);
                            },
                            error: function(){
                                //alert('there were problems');
                                    console.log("====== in error function ======");

                                $rootScope.error = "Network not available. Please return to this screen and resend later.";
                                error && error();
                            },
                            complete: function(){
                                complete && complete();
                                $log.log("====== Sync end ======");
                            }
                        })

                    //commenting the not calling for empty cases
                    }

                })(window.jQuery);
            },





            /**
             * mod. trying a simplified sync which puts more of the control on SP
             * see sync function above
             */

'simplified_sync' : function(beforeSend_func, success, error, complete ){

                $log.log("====== ofi_daemon.simplified_sync called ======");

                (function($){
                    var attendees = get_all_local_available_attendees();

                    if(!$.isEmptyObject(attendees)){
                        $.ajax({
                            async: false,
                            beforeSend : function(){
                                $log.log("====== Sync Starts ======");
                                beforeSend_func && beforeSend_func();
                            },
                            timeout : config.timeout * 1000,
                            url : ofi_api + "simplified_sync",
                            type: "POST",
                            dataType: "json",
                            data : {
                                'attendees' : attendees
                            },
                            success : function(response){
                                if(response.result === true){
                                    // update attendee in local storage.

                                    //alert(response.data);
                                    $log.log("====== debugging response.data ======");

                                    console.debug(response.data);
                                    //trying to put it in a rootscope varible
                                    $rootScope.responseDataSynced = response.data;



                                    angular.forEach(response.data, function(rtn_attendee, key){

                                        //alert(rtn_attendee);

                                        // retrieve the corresponding attendee in plain object from local storage.
                                        // and update fields from remote attendee, but it will not kill the
                                        // fields that the remote attendee doesn't have.
                                        if(angular.isDefined(localStorage[key])){
                                            var local_attendee = attendee_get(key);
                                            angular.forEach(rtn_attendee, function(value,key){
                                                    local_attendee[key] = value;
                                                    //alert(key);
                                                    //alert(value);


                                            })
                                            localStorage[key] = angular.toJson(local_attendee);
                                        }
                                    })
                                }
                                
                                //temp clearing storage here
                                //alert('no longer clearing');
                                //localStorage.clear();



                                success && success();
                                $log.log(response);
                            },
                            error: function(){
                                $rootScope.error = "Network not available. Please return to this screen and resend later.";
                                error && error();
                            },
                            complete: function(){
                                complete && complete();
                                $log.log("====== Sync end ======");


                            }
                        })
                    }

                })(window.jQuery);
            },


          /**
             * mod. consistent with ls dump, for se
             */

'simplified_sync_consistent_for_se' : function(beforeSend_func, success, error, complete ){

                $log.log("====== ofi_daemon.simplified_sync_consistent_for_se called ======");

                (function($){
                    var attendees = get_all_local_storage_objects();

                    if(!$.isEmptyObject(attendees)){
                        $.ajax({
                            async: false,
                            beforeSend : function(){
                                $log.log("====== Sync Starts ======");
                                beforeSend_func && beforeSend_func();
                            },
                            timeout : config.timeout * 1000,
                            url : ofi_api + "simplified_sync_consistent_for_se",
                            type: "POST",
                            dataType: "json",
                            data : {
                                'attendees' : attendees
                            },
                            success : function(response){
                                if(response.result === true){
                                    // update attendee in local storage.

                                    //alert(response.data);
                                    $log.log("====== debugging response.data ======");

                                    console.debug(response.data);
                                    //trying to put it in a rootscope varible
                                    $rootScope.responseDataSynced = response.data;



                                    angular.forEach(response.data, function(rtn_attendee, key){

                                        //alert(rtn_attendee);

                                        // retrieve the corresponding attendee in plain object from local storage.
                                        // and update fields from remote attendee, but it will not kill the
                                        // fields that the remote attendee doesn't have.
                                        if(angular.isDefined(localStorage[key])){
                                            var local_attendee = attendee_get(key);
                                            angular.forEach(rtn_attendee, function(value,key){
                                                    local_attendee[key] = value;
                                                    //alert(key);
                                                    //alert(value);


                                            })
                                            localStorage[key] = angular.toJson(local_attendee);
                                        }
                                    })
                                }
                                
                                //temp clearing storage here
                                //alert('no longer clearing');
                                //localStorage.clear();



                                success && success();
                                $log.log(response);
                            },
                            error: function(){
                                $rootScope.error = "Network not available. Please return to this screen and resend later.";
                                error && error();
                            },
                            complete: function(){
                                complete && complete();
                                $log.log("====== Sync end ======");
                            }
                        })
                    }

                })(window.jQuery);
            },









          /**
             * mod. same version using dump, but doesn't clear storage
             * actually calls a diff api method which should send response data with data etc
             */

'simplified_sync_for_send_using_ls_dump' : function(beforeSend_func, success, error, complete ){

                $log.log("====== ofi_daemon.simplified_sync_for_send_using_ls_dump called ======");

                (function($){
                    var attendees = get_local_storage_as_array();
                    var numberOfAttendees = '' + localStorage.length;

                    if(!$.isEmptyObject(attendees)){
                        $.ajax({
                            async: false,
                            beforeSend : function(){
                                $log.log("====== Sync Starts ======");
                                beforeSend_func && beforeSend_func();
                            },
                            timeout : config.timeout * 1000,
                            url : ofi_api + "simplified_sync_using_ls_dump_for_sending",
                            type: "POST",
                            dataType: "json",
                            data : {
                                'attendees' : attendees, 
                                'numberOfAttendees' : numberOfAttendees
                            },
                            success : function(response){

                                    $log.log("success apparently");
                                    $log.log("====== response.result was: ======");
                                    $log.log(response.result);

                                    $log.log("======  response.data was: ======");
                                    $log.log(response.data);




                                    if(response.result === true){
                                        //this means that all the attendees from LS sent to server were saved or updated, so can clear LS
                                        //alert(response.data);
                                        $log.log("====== debugging response.data ======");

                                        console.debug(response.data);
                                    
                                        //this version doesn't clear local storage
                                        //localStorage.clear();
                                        success && success();
                                        //$log.log(response);


                                    }
                       
                                //success && success();
                                //$log.log(response);
                       
                            },
                            error: function(){

                                    $log.log("error apparently");
                                    $log.log("====== response.result was: ======");
                                    $log.log(response.result);

                                    $log.log("======  response.data was: ======");
                                    $log.log(response.data);

  


                                $rootScope.error = "Network not available. Please return to this screen and resend later.";
                                error && error();
                            },
                            complete: function(){

                                  $log.log("complete apparently");
                                    $log.log("====== response.result was: ======");
                                    //$log.log(response.result);

                                    $log.log("======  response.data was: ======");
                                    //$log.log(response.data);




                                complete && complete();
                                $log.log("====== Sync end ======");


                            }
                        })
                    }

                })(window.jQuery);
            },









			/**
             * mod. trying a simplified sync which puts more of the control on SP
             * see sync function above
             */

'simplified_sync_using_ls_dump' : function(beforeSend_func, success, error, complete ) {
                console.log("====== ofi_daemon.simplified_sync_using_ls_dump called Justinxxxx here======");
				console.log("====== The problem is in here Seemasit !!! ======");
                (function($){
                    var attendees = get_local_storage_as_array();
                    
                    //mod. now changing the way the numberOfAttendees is worked out too.  no longer length of entire localStorage as no longer deleting all_categories from local storage et    
                    var numberOfAttendees = '' + attendees.length;
                    if(!$.isEmptyObject(attendees)) {
						if (window.localStorage) {
							var key = localStorage.key(2);
							alert( "username = " + localStorage.getItem(key));
							attendees=localStorage.getItem(key);
							
						} else {
							alert('Else part');
						}
						console.log("attendees: "+attendees);
						//var attend=attendees.toString().split(',');
						//console.log(attend+"URL: "+ofi_api+"simplified_sync_using_ls_dump");
						console.log("URL: "+ofi_api+"simplified_sync_using_ls_dump");
						//alert("URL: "+ofi_api+"simplified_sync_using_ls_dump "+attend[0]);
						console.log("attendees: "+attendees);
						console.log("numberOfAttendees: "+numberOfAttendees);
						
                        $.ajax({
                            async: false,
                            beforeSend : function(){
                                $log.log("====== Sync Starts ======");
                                beforeSend_func && beforeSend_func();
                            },
                            timeout : config.timeout * 1000,
                            url : ofi_api + "simplified_sync_using_ls_dump",
							cache: true,
                            type: "POST",
                            dataType: "json",
                            data : {"attendees":attendees,"numberOfAttendees":numberOfAttendees},
                            success : function(response){
								alert("Success: "+JSON.stringify(response));
                                    $log.log("success apparently");
                                    $log.log("====== response.result was: ======");
                                    $log.log(response.result);

                                    $log.log("======  response.data was: ======");
                                    $log.log(response.data);

                                    if(response.result === true){
                                        //this means that all the attendees from LS sent to server were saved or updated, so can clear LS
                                        $log.log("====== debugging response.data ======");

                                        console.debug(response.data);
                                        localStorage.clear();
                                        success && success();
                                    }
                            },
                            error: function(jqXHR, textStatus, errorThrown){
								alert("Line 805: "+textStatus+" thrown:"+errorThrown);
                                $log.log("error apparently");
                                error && error();
								
                            },
                            complete: function(){
								alert("Line 819: complete ");
								
                                  $log.log("complete apparently");
                                    $log.log("====== response.result was: ======");
                                    $log.log("======  response.data was: ======");
                                complete && complete();
                                $log.log("====== Sync end ======");
                            }
                        }); 
                    }else{
                    	console.log('flag: '+$('#btn-new-ofi').attr('target-clicked-flag'));
                    	console.log('flag: '+ ($('#btn-new-ofi').attr('target-clicked-flag') == undefined) );
                    	if($('#btn-new-ofi').attr('target-clicked-flag') != undefined){
                    		window.close();
                    	}
                    }

                })(window.jQuery);
            },

/**
             * commenting out the below temporarily for debugging the failure to send email error
             *
            'sync' : function(beforeSend_func, success, error, complete ){
                (function($){
                    //just testing to see if logging could be done within an angular/testing 
                        $.ajax({
                            async: false,
                            beforeSend : function(){
                                $log.log("====== for testing services, testing the logging here temporarily  ======");
                                beforeSend_func && beforeSend_func();
                            },
                            timeout : config.timeout * 1000,


                            url : siteUrl + "/logging/testLog",
                            

                            type: "POST",
                            dataType: "json",
                            data : {
                                'attendees' : attendees,
                                'urlBeingVisited' : $location.absUrl()
                            },
                            success : function(response){
                                if(response.result === true){
                                    // update attendee in local storage.
                                    angular.forEach(response.data, function(rtn_attendee, key){

                                        // retrieve the corresponding attendee in plain object from local storage.
                                        // and update fields from remote attendee, but it will not kill the
                                        // fields that the remote attendee doesn't have.
                                        if(angular.isDefined(localStorage[key])){
                                            var local_attendee = attendee_get(key);
                                            angular.forEach(rtn_attendee, function(value,key){
                                                    local_attendee[key] = value;
                                            })
                                            localStorage[key] = angular.toJson(local_attendee);
                                        }
                                    })
                                }
                                success && success();
                                $log.log(response);
                            },
                            error: function(){
                                $rootScope.error = "Network not available. Please return to this screen and resend later.";
                                error && error();
                            },
                            complete: function(){
                                complete && complete();
                                $log.log("====== log testing completed ======");
                            }
                        })

                    var attendees = get_all_local_available_attendees();
                    //alert ("asdfasdfasdfas");

                    if(!$.isEmptyObject(attendees)){
                        $.ajax({
                            async: false,
                            beforeSend : function(){
                                $log.log("====== Sync Starts ======");
                                beforeSend_func && beforeSend_func();
                            },
                            timeout : config.timeout * 1000,
                            url : ofi_api + "sync",
                            type: "POST",
                            dataType: "json",
                            data : {
                                'attendees' : attendees
                            },
                            success : function(response){
                                if(response.result === true){
                                    // update attendee in local storage.
                                    angular.forEach(response.data, function(rtn_attendee, key){

                                        // retrieve the corresponding attendee in plain object from local storage.
                                        // and update fields from remote attendee, but it will not kill the
                                        // fields that the remote attendee doesn't have.
                                        if(angular.isDefined(localStorage[key])){
                                            var local_attendee = attendee_get(key);
                                            angular.forEach(rtn_attendee, function(value,key){
                                                    local_attendee[key] = value;
                                            })
                                            localStorage[key] = angular.toJson(local_attendee);
                                        }
                                    })
                                }
                                success && success();
                                $log.log(response);
                            },
                            error: function(){
                                $rootScope.error = "Network not available. Please return to this screen and resend later.";
                                error && error();
                            },
                            complete: function(){
                                complete && complete();
                                $log.log("====== Sync end ======");
                            }
                        })
                    }

                })(window.jQuery);
            },
*/
            'get_all_local_storage_objects' : get_all_local_storage_objects,
            'get_all_local_available_attendees' : get_all_local_available_attendees
        }
    })


