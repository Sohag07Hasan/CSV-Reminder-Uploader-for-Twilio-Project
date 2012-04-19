<div>
	<img src="http://kindly-remind.com/instructions.jpg" alt='instruction' /> <br/>
	<a href="http://kindly-remind.com/example.csv"> Example </a>
</div>

<form action="" class="form-table" method="post" enctype="multipart/form-data">
	<input type="hidden" name="csv-location" value="<?php echo $_POST['csv-location'];?>" />
	<input type="hidden" name="step-three" value="Y">
	
	<h4>When do you wish to send the reminders?</h4>
	<input type="radio" checked name="reminder_time" value="24" /> 24 Hours Advance
	<br/>
	<input type="radio" name="reminder_time" value="48" /> 48 Hours Advance
	<br/>
	<input type="radio" name="reminder_time" value="72" /> 72 Hours Advance
	<br/>

	<h4>How do you wish to notify? </h4>
	<input type="checkbox" checked name="remnder-type" value="sms"> Text (SMS) <br/>
	<input type="checkbox" checked name="remnder-type" value="voice"> Voice Message <br/>
	<input type="checkbox" checked name="remnder-type" value="email"> Email <br/>
	<br/>
	
	<input type="submit" value="Continue" class="button-primary" />	
</form>
