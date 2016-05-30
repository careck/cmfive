<?php echo Html::box("/timelog/edit?class={$class}&id={$id}" . (!empty($redirect) ? "&redirect=$redirect" : ''), "Add new timelog", true); ?>
<h4 style="display: inline; padding: 0px 5px;" class="right">
	<?php echo $w->Task->getFormatPeriod($total); ?>
</h4>

<?php if (!empty($timelogs)) : ?>
	<table class='small-12'>
		<thead><tr><th width="10%">Name</th><th width="15%">From</th><th width="15%">To</th><th width="40%">Description</th><th width="20%">Actions</th></tr></thead>
		<tbody>
			<?php foreach($timelogs as $timelog) : ?>
				<tr class='timelog' data-id="<?php echo $timelog->id; ?>" >
					<td><?php echo $timelog->getFullName(); ?></td>
					<td><?php echo formatDate($timelog->dt_start, "d-m-Y H:i:s"); ?></td>
					<td><?php echo formatDate($timelog->dt_end, "d-m-Y H:i:s"); ?></td>
					<td><pre class="break-pre" style="font-family: sans-serif;"><?php echo $timelog->getComment()->comment; ?></pre></td>
					<td>
						<?php echo Html::box('/timelog/edit/' . $timelog->id . (!empty($redirect) ? "?redirect=$redirect" : ''), 'Edit', true); ?>
						<?php echo Html::b('/timelog/delete/' . $timelog->id . (!empty($redirect) ? "?redirect=$redirect" : ''), 'Delete', 'Are you sure you want to delete this timelog?'); ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php endif;
