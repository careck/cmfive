<?php echo Html::box("/channels-processor/edit", "Add Processor", true); ?>

<?php if (!empty($processors)) : ?>

	<table class="tablesorter">
		<thead>
			<tr><th>Name</th><th>Processor Class</th><th>Processor Module</th><th>Attached To</th><th>Actions</th></tr>
		</thead>
		<tbody>
			<?php foreach($processors as $p) : ?>
				<?php $channel = $p->getChannel(); ?>
				<tr>
					<td><?php echo $p->name; ?></td>
					<td><?php echo $p->class; ?></td>
					<td><?php echo $p->module; ?></td>
					<td><?php echo !empty($channel->name) ? $channel->name : ""; ?></td>
					<td>
						<?php echo Html::box("/channels-processor/edit/{$p->id}", "Edit"); ?>
						<?php echo Html::box("/channels-processor/editsettings/{$p->id}", "Edit Settings"); ?>
					</td>
				</tr>	
			<?php endforeach; ?>
		</tbody>
	</table>

<?php endif; ?>
