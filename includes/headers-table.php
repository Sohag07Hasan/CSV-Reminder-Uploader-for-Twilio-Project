
<?php
	$selects = $this->get_select();
?>

<table>
	<?php
		foreach($headers as $header) :
		?>
			
			<tr>
				<td> <?php echo $header; ?> <td>
				<td><select name="<?php echo $header; ?>"><?php echo $selects; ?></select></td>
			</tr>
			
		<?php	
		endforeach;
	?>
</table>
