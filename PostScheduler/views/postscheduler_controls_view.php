<?php if (!defined('APPLICATION')) exit();
/**
{licence}
*/
?>
<li class="PostScheduler">
	<?php
	$LastCommentID = $this->Form->GetValue('LastCommentID');
	if(!empty($LastCommentID)) {
		echo Wrap(T('Discussion cannot be scheduled because it already received replies.'),
							'span',
							array('class' => 'ErrorMessage',));
	}
	else{
		echo $this->Form->CheckBox('Scheduled',
															 T('Publish at'),
															 array('value' => 1));
		echo $this->Form->TextBox('ScheduleTime');
	}
	?>
</li>
