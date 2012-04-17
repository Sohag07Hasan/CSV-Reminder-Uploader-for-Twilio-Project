
<?php
	$selects = $this->get_select();
?>

<table>
	<tr>
		<td><h4>LABEL HERE</h4></td>
		<td>&nbsp; &nbsp;</td>
						
		<td><h4>LABEL HERE</h4></td>
	</tr>
	<?php
		foreach($headers as $header) :
		?>
			
			<tr>
				<td> <?php echo $header; ?> </td>
				<td> = </td>
				<td><select name="<?php echo $header; ?>"><?php echo $selects; ?></select></td>
			</tr>
			
		<?php	
		endforeach;
	?>
</table>
