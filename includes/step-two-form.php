<div>
	<img src="http://kindly-remind.com/instructions.jpg" alt='instruction' /> <br/>
	<a href="http://kindly-remind.com/example.csv"> Example </a>
</div>

<form action="" class="form-table" method="post" enctype="multipart/form-data">
	<input type="hidden" name="step-two" value="Y" />
	<input type="hidden" name="csv-location" value="<?php echo $file_location; ?>" />
	<?php
		$selects = $this->get_select();
	?>

	<table>
		
		<?php
			foreach($headers as $header) :
			?>
				
				<tr>
					<td> <?php echo $header; ?> </td>
					<td> = </td>
					<td><select name="<?php echo preg_replace('#[ ]#', '', $header); ?>"><?php echo $selects; ?></select></td>
				</tr>
				
			<?php	
			endforeach;
		?>
	</table>
	
	<input type="submit" value="Continue" class="button-primary" />
</form>
