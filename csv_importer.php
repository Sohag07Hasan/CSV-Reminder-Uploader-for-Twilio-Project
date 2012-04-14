<?php
/*
Plugin Name: CSV Reminder Importer
Description: Import data as posts from a CSV file. <em>You can reach the author at <a href="mailto:d.v.kobozev@gmail.com">d.v.kobozev@gmail.com</a></em>.
Version: 0.3.7
Author: Denis Kobozev
*/

/**
 * LICENSE: The MIT License {{{
 *
 * Copyright (c) <2009> <Denis Kobozev>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @author    Denis Kobozev <d.v.kobozev@gmail.com>
 * @copyright 2009 Denis Kobozev
 * @license   The MIT License
 * }}}
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
            'post_title'   => 'Reminder Title',
            'post_content' => 'Description for Reminder',
            'post_date'    => 'Reminder Date',
            'post_time'    => 'Reminder Time',
            'mobile'	   => 'Mobile Number for SMS',
            'phone'		   => 'Phone Number for Voice',
            'email'		   => 'Email address to send email'            
          );

    var $log = array();
    
    
    /*
     * creates select for the form
     * */
     function get_select(){
		 $options = '';
		foreach($this->new_post as $key=>$value){
			$options .= "<option value='$key'>$value</option>";			
		}
		
		return $options;
	 }
     
    
    /**
     * Plugin's interface
     *
     * @return void
     */
    function form() {     
       
        
        $csv_link = plugins_url('/',__FILE__) . 'example.csv';

				// form HTML {{{
		?>

		<div class="wrap">
			<?php screen_icon('tools'); ?>
			<h2>Import Reminders as CSV</h2>
			<br/>
			
			<?php
				 if ($_POST['reminder-import-csv'] == 'Y') {
					if($_POST['uploaded_file_yes'] == 'Y') {
						$this->post();
					 }
					 else{
						$headers = $this->csv_key_assign();
						$file_location = $this->upload_csv();
						
					 }
					
				 }
			?>	
			
			
			<div>
				<img src="http://kindly-remind.com/instructions.jpg" alt='instruction' /> <br/>
				<a href="http://kindly-remind.com/example.csv"> Example </a>
			</div>
			
			
			<form action="" class="form-table" method="post" enctype="multipart/form-data">
				<input type="hidden" name="reminder-import-csv" value="Y" />
				
				<?php if($file_location) :	?>
					<input type="hidden" name="uploaded_file" value="<?php echo $file_location; ?>" />
					<input type="hidden" name="uploaded_file_yes" value="Y" />
					
					<?php
						if(is_array($headers)) :
							include dirname(__FILE__) . '/includes/headers-table.php';
						endif;
					?>
					
					<input type="submit" value="import" class="button-primary" />
				<?php else : ?>
					  
				<!-- File input -->
				<p>
					<label for="csv_import">Upload file:</label>
						<input name="csv_import" id="csv_import" type="file" value="" aria-required="true" />
				   <input type="submit" class="button" name="submit" value="Import" />
			   </p>
			   <?php endif; ?>
			   
			</form>
		</div><!-- end wrap -->

		<?php
				// end form HTML }}}

    }  
    
    
    /*
     * This will create table to assing the column name against each csv column name
     * */
    function  csv_key_assign(){
		require_once dirname(__FILE__) . '/File_CSV_DataSource/DataSource.php';
		$file = $_FILES['csv_import']['tmp_name'];
		
		$this->stripBOM($file);
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
         if($_FILES['csv_import']['type'] != 'text/csv') {
			$this->log['error'][] = 'File must be a csv file, aborting....';
            $this->print_messages();
            return;
		 }
		 
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
        $file = $_POST['uploaded_file'];
       // $this->stripBOM($file);

		$csv = new File_CSV_DataSource();

        if (!$csv->load($file)) {
            $this->log['error'][] = 'Failed to load file, aborting.';
            $this->print_messages();
            return;
        }		
		
		$headers = $csv->getHeaders();
		
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
			$post_id = $this->create_post($data, $headers);
			
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
            $this->log['notice'][] = "<b>Skipped {$skipped} posts (most likely due to empty title, body and excerpt).</b>";
        }
        $this->log['notice'][] = sprintf("<b>Imported {$imported} posts in %.2f seconds.</b>", $exec_time);
        $this->print_messages();
    }

    function create_post($data, $headers) {	
		                 
        $new_post = array(
            'post_title'   => convert_chars($data['reminder_title']),
            'post_content' => wpautop(convert_chars($data['reminder_title'])),
            'post_status'  => 'publish',
            'post_type'    => 'reminderagent',
            'post_date'    => $this->parse_date($data['reminder_date'], $data['reminder_time']),
            
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
		$userdata = get_userdata($user_ID);		
		
				
        update_post_meta($post_id, "_reminderagent_content", $data["reminder_info"]);
        update_post_meta($post_id, "_sent", '');
		update_post_meta($post_id, "_reminderagent_sms_phone", $data["sms_mobile"]);
		update_post_meta($post_id, "_reminderagent_sms_message", get_user_meta($userdata->ID, 'reminderagent_sms', true));
		update_post_meta($post_id, "_reminderagent_voice_phone", $data["voice_phone"]);
		update_post_meta($post_id, "_reminderagent_voice_message", get_user_meta($userdata->ID, 'reminderagent_tts', true));
		//update_post_meta($post_id, "_reminderagent_audio_source", $_POST["reminderagent_audio_source"]);
		update_post_meta($post_id, "_reminderagent_email_address", $data["email_address"]);
		update_post_meta($post_id, "_reminderagent_email_message", get_user_meta($userdata->ID, 'reminderagent_email', true));        
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

