<?php
/*
Plugin Name: CSV Reminder Importer
Description: Import data as posts from a CSV file. 
Version: 1.1.1
Author: Mahibul Hasan Sohag
Author Uri: http://sohag.me
Plugins Uri: http://kindly-remind.com
*/


class CSVImporterPlugin {
    var $defaults = array(
        'Reminder'      => null,
        'csv_post_post'       => null,
        'csv_post_type'       => null,
        'csv_post_excerpt'    => null,
        'csv_post_date'       => null,
        'csv_post_tags'       => null,
        'csv_post_categories' => null,
        'csv_post_author'     => null,
        'csv_post_slug'       => null,
        'csv_post_parent'     => 0,
    );
    
   var $new_post = array(
            'first_name'   => 'Customer First Name (Example: John)',
            'last_name'   => 'Customer Last Name (Example: Doe)',            
            'full_name'   => 'Customer First Name + Last Name (Example: John Doe)',            
            'phone_number'    => 'Customer Phone Number (Example: 5555555555)',
            'mobile_number'    => 'Customer Mobil Number (Example: 5555555555)',
            'appointment_date'    => 'Appointment Date ',
            'appointment_time'	   => 'Appointment Time',            
            'email_address'		   => 'Email address to send email'            
          );

    var $log = array();
    
    
    /*
     * creates select for the form
     * */
     function get_select(){
		 $options = '<option value="">Select</option>';
		foreach($this->new_post as $key=>$value){
			$options .= "<option value='$key'>$value</option>";			
		}
		
		return $options;
	 }
      
   
    
    /*
     * This will create table to assing the column name against each csv column name
     * */
    function  csv_key_assign($file){
		if(!$file) return false;
		
		require_once dirname(__FILE__) . '/File_CSV_DataSource/DataSource.php';
				
		
		$csv = new File_CSV_DataSource();

        if (!$csv->load($file)) {
            $this->log['error'][] = 'Failed to load file, aborting.';
            $this->print_messages();
            return false;
        }
        
        return $csv->getHeaders();
                
	}
         
    

    function print_messages() {
        if (!empty($this->log)) {

        // messages HTML {{{
?>


    <?php if (!empty($this->log['error'])): ?>
    <?php array_unique($this->log['error']); ?>

    <div class="error">

        <?php foreach ($this->log['error'] as $error): ?>
            <p><?php echo $error; ?></p>
        <?php endforeach; ?>

    </div>

    <?php endif; ?>

    <?php if (!empty($this->log['notice'])): ?>

    <div class="updated fade">

        <?php foreach ($this->log['notice'] as $notice): ?>
            <p><?php echo $notice; ?></p>
        <?php endforeach; ?>

    </div>

    <?php endif; ?>

<?php
        // end messages HTML }}}

            $this->log = array();
        }
    }
    
    /*
     * Uploads csv to server and waits for user verification clicks
     * */
     function upload_csv(){		 
		
		 
		if (empty($_FILES['csv_import']['tmp_name'])) {
            $this->log['error'][] = 'No CSV is selected, aborting....';
            $this->print_messages();
            return;
        }
        
        if(preg_match('#\.csv#', $_FILES["csv_import"]["name"])) :
         //if($_FILES['csv_import']['type'] == "text/csv") :		  
		 
			 //sanitizing the csv files
			 $this->stripBOM($_FILES['csv_import']['tmp_name']);
			 
			//upload the csv file to server
					
			$dirs = wp_upload_dir();
			$basedir = $dirs['basedir'];
			$baseurl = $dirs['baseurl'];
			$csv_dir = $basedir . '/reminder-csv';    
			$csv_file = $csv_dir . '/' . preg_replace('/[ ]/', '',$_FILES['csv_import']['name']);
			   
			if(is_dir($csv_dir)){
				if(move_uploaded_file($_FILES['csv_import']['tmp_name'], $csv_file)) return $csv_file;
				
			}
			else{
				@mkdir($csv_dir);
				if(move_uploaded_file($_FILES['csv_import']['tmp_name'], $csv_file)) return $csv_file;
			}
			
			$this->log['error'][] = 'Upload directoy is write protected. Please make the upload direcoty under wp-content writable and try again, aborting....';
			$this->print_messages();
			return false; 
        
        else :
			$this->log['error'][] = 'File must be a csv file, aborting....';
            $this->print_messages();
            return;
        endif;     
	 }
	 
	 

    /**
     * Handle POST submission
     *
     * @param array $options
     * @return void
     */
    function post() {							     

			require_once dirname(__FILE__) . '/File_CSV_DataSource/DataSource.php';

			$time_start = microtime(true);        
			$file = $_POST['csv-location'];
		   
			$csv = new File_CSV_DataSource();

			if (!$csv->load($file)) {
				$this->log['error'][] = 'Failed to load file, aborting.';
				$this->print_messages();
				return;
			}			
			
			// pad shorter rows with empty values
			$csv->symmetrize();
		 

			$skipped = 0;
			$imported = 0;
			$comments = 0;
			foreach ($csv->connect() as $csv_data) {
				$data = array();
				foreach($csv_data as $k=>$v){
					$k = preg_replace('[ ]','',$k);
					$data[$k] = trim($v);
				}
				
				//creating the post
				$post_id = $this->create_post($data);			
				
				if($post_id){
					$imported++;               
					$this->create_custom_fields($post_id, $data);
				} else {
					$skipped++;
				}
			}

			if (file_exists($file)) {
				@unlink($file);
			}

			$exec_time = microtime(true) - $time_start;

			if ($skipped) {
				$this->log['notice'][] = "<b>Skipped {$skipped} Reminders (most likely due to empty title, body and excerpt).</b>";
			}
			$this->log['notice'][] = sprintf("<b>Imported {$imported} Reminders in %.2f seconds.</b>", $exec_time);
			$this->print_messages();
              
    }


	/*
	 * csv column name to sorting table column
	 *  */
	 function csv_col_vs_table_col($switch_key, $headers, $data = array()){
		foreach($_POST as $key=>$value) :
			if($switch_key == $value){
				return $data[$key];
			}
		endforeach;
	 }
	 
	 

    function create_post($data) {		
		
		$name = $data['first_name'];
		if(isset($data['last_name'])){
			$name .= ' ' . $data['last_name'];
		}
		
		$new_post = array(
			'post_title' => $name . ' ' . $data['mobile_number'],
			'post_content' => $data['appointment_date'] . ' at ' . $data['appointment_time'],
			'post_status'  => 'publish',
			'post_type'    => 'reminderagent',
			'post_date'    => $this->parse_date($data['appointment_date'], $data['appointment_time'])
			
		);
		
        // create!
        $id = wp_insert_post($new_post);
        if($id){
			global $wpdb;
			$wpdb->update($wpdb->posts, array('post_status'=>'future'), array('ID'=>$id), array('%s'), array('%d'));
		}
      
        return $id;
    }
    
    
    /*
     * Exact contents of the csv files
     * */
     function get_csv_data(){
		
	 }
    

    function create_custom_fields($post_id, $data) {
		
		
		global $user_ID;		
			
        update_post_meta($post_id, "_reminderagent_content", $data['appointment_date'] . ' at ' . $data['appointment_time']);
        update_post_meta($post_id, "_sent", '');
        
        //update the mobile number with sms if sms notification is set
        if(in_array('sms', $_POST['remnder-type'])) :
			update_post_meta($post_id, "_reminderagent_sms_phone", $data["mobile_number"]);
			update_post_meta($post_id, "_reminderagent_sms_message", get_user_meta($user_ID, 'reminderagent_sms', true));
		endif;
		
		
		//update the phone number if the voice notification is set
		if(in_array('voice', $_POST['remnder-type'])) :
			update_post_meta($post_id, "_reminderagent_voice_phone", $data["phone_number"]);
			update_post_meta($post_id, "_reminderagent_voice_message", get_user_meta($user_ID, 'reminderagent_tts', true));
		endif;
		
		//update thie email details if the email noitifcation is set
		if(in_array('email', $_POST['remnder-type'])) :
			update_post_meta($post_id, "_reminderagent_email_address", $data["email_address"]);
			update_post_meta($post_id, "_reminderagent_email_message", get_user_meta($user_ID, 'reminderagent_email', true));
		endif;        
    }
   
    /**
     * Convert date in CSV file to 1999-12-31 23:52:00 format     *
     * @param string $data
     * @return string
     */
    function parse_date($data, $time) {
		$time = $this->csvtime($time);
		
        $timestamp = strtotime($data);
		
        
        if (false === $timestamp) {
            return '';
        } else {
			$less = $_POST['reminder_time'] * 3600;
			$timestamp -= $less;
            $date = date('Y-m-d', $timestamp);           
			return $date .= ' ' . $time;
        }		
    }
    
    /*
     * convert time into wp compatible
     * */

     function csvtime($a){
		$type = preg_replace('/[^ampAMP]/','',$a);
		$puretime = preg_replace('/[^0-9:]/','',$a);
		
		$times = explode(':', $puretime);
		
		$pm = strcasecmp($type, 'pm');
		$am = strcasecmp($type, 'am');
		
		if($pm == 0){			
			$time = (int) $times[0] + 12;
			if($times[0] == 12) $time = $times[0];
		}
		elseif($am == 0){
			$time = $times[0];
			if($times[0] == 12) $time = 0;
		}
		else{
			return '';
		}
		
		$time .= ':' . $times[1];
		
		return $time . ':00';
	  }


    /**
     * Delete BOM from UTF-8 file.
     *
     * @param string $fname
     * @return void
     */
    function stripBOM($fname) {
        $res = fopen($fname, 'rb');
        if (false !== $res) {
            $bytes = fread($res, 3);
            if ($bytes == pack('CCC', 0xef, 0xbb, 0xbf)) {
                $this->log['notice'][] = 'Getting rid of byte order mark...';
                fclose($res);

                $contents = file_get_contents($fname);
                if (false === $contents) {
                    trigger_error('Failed to get file contents.', E_USER_WARNING);
                }
                $contents = substr($contents, 3);
                $success = file_put_contents($fname, $contents);
                if (false === $success) {
                    trigger_error('Failed to put file contents.', E_USER_WARNING);
                }
            } else {
                fclose($res);
            }
        } else {
            $this->log['error'][] = 'Failed to open file, aborting.';
        }
    }
    
    
    /*
     * Latest Form
     * */
     
    function form(){
		?>
		<div class="wrap">
			<?php screen_icon('tools'); ?>
			<h2>Import Reminders as CSV</h2>
			<br/>			
			
		<?php
			//including necessary files
			if($_POST['step-one'] == 'Y'){
				
				$file_location = $this->upload_csv();
				$headers = $this->csv_key_assign($file_location);
				
				
				if($file_location){
					include dirname(__FILE__) . '/includes/step-two-form.php';
				}
				else{
					include dirname(__FILE__) . '/includes/step-one-form.php';
				}
				
			}
			elseif($_POST['step-two'] == 'Y'){
				if($_POST['first_name'] == 'first_name' && $_POST['phone_number'] == 'phone_number' && $_POST['mobile_number'] == 'mobile_number' && $_POST['appointment_date'] == 'appointment_date' && $_POST['appointment_time'] == 'appointment_time' && $_POST['email_address'] == 'email_address'){
					include dirname(__FILE__) . '/includes/step-three-form.php';
				}
				elseif($_POST['full_name'] == 'full_name' && $_POST['phone_number'] == 'phone_number' && $_POST['mobile_number'] == 'mobile_number' && $_POST['appointment_date'] == 'appointment_date' && $_POST['appointment_time'] == 'appointment_time' && $_POST['email_address'] == 'email_address'){
					include dirname(__FILE__) . '/includes/step-three-form.php';
				}
				else{
					//trigger the error message
					$this->log['error'][] = "Invalid Key assigned! Try with correct selection..";
					$this->print_messages();					
					
					$file_location = $_POST['csv-location'];
					$headers = $this->csv_key_assign($file_location);
					include dirname(__FILE__) . '/includes/step-two-form.php';
				}
			}
			elseif($_POST['step-three'] == 'Y'){
				if(empty($_POST['remnder-type'])){
					$this->log['error'][] = "Please choose at least one Notification method..";
					$this->print_messages();
					include dirname(__FILE__) . '/includes/step-three-form.php';
				}
				else{
					$this->post();
					include dirname(__FILE__) . '/includes/step-one-form.php';
				}
			}
			else{				
				include dirname(__FILE__) . '/includes/step-one-form.php';
			}	
		?>
		
		</div>
				
		<?php	
	} 
}


function csv_admin_menu() {
   // require_once ABSPATH . '/wp-admin/admin.php';
    $plugin = new CSVImporterPlugin;
    //add_management_page('edit.php', 'CSV Importer', 'manage_options', __FILE__,
    if(current_user_can('calendermenu')) :
		add_submenu_page('edit.php?post_type=reminderagent', 'Import from CSV', 'Import from CSV', 'calendermenu', 'reminder_import',array($plugin, 'form'));
	else :
		add_submenu_page('edit.php?post_type=reminderagent', 'Import from CSV', 'Import from CSV', 'manage_options', 'reminder_import',
        array($plugin, 'form'));
    endif;
}

add_action('admin_menu', 'csv_admin_menu');

