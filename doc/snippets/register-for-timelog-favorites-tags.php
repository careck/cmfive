// Register for timelog
$w->Timelog->registerTrackingObject($wiki);
		
// list tags		
<?php echo $w->partial('listTags',['object' => $wiki], 'tag'); ?>
// show the favorite button
<?php echo $w->Favorite->getFavoriteButton($wiki);?>
                
