<?php echo Html::box("/timelog/edit?class={$class}&id={$id}" . (!empty($redirect) ? "&redirect=$redirect" : ''), __("Add new timelog"), true); ?>
<h4 style="display: inline; padding: 0px 5px;" class="right">
	<?php echo $w->Task->getFormatPeriod($total); ?>
</h4>

<?php if (!empty($timelogs)) : ?>
	<table class='small-12'>
		<thead><tr><th width="10%"><?php _e('Name'); ?></th><th width="15%"><?php _e('From'); ?></th><th width="15%"><?php _e('To'); ?></th><th width="40%"><?php _e('Description'); ?></th><th width="20%"><?php _e('Actions'); ?></th></tr></thead>
		<tbody>
			<?php foreach($timelogs as $timelog) : ?>
				<tr class='timelog' data-id="<?php echo $timelog->id; ?>" >
					<td><?php echo $timelog->getFullName(); ?></td>
					<td><?php echo formatDate($timelog->dt_start, "d-m-Y H:i:s"); ?></td>
					<td><?php echo formatDate($timelog->dt_end, "d-m-Y H:i:s"); ?></td>
					<td><?php echo $timelog->getComment()->comment; ?></td>
					<td>
						<?php echo Html::box('/timelog/edit/' . $timelog->id . (!empty($redirect) ? "?redirect=$redirect" : ''), __('Edit'), true); ?>
						<?php echo Html::b('/timelog/delete/' . $timelog->id . (!empty($redirect) ? "?redirect=$redirect" : ''), __('Delete'), __('Are you sure you want to delete this timelog?')); ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php endif;
