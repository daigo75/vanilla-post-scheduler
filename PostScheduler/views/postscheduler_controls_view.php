<?php if (!defined('APPLICATION')) exit();
/**
{licence}
*/
?>
<li class="PostScheduler">
	<?php
	echo $this->Form->CheckBox('Scheduled',
														 T('Publish at'),
														 array('value' => 1));
	echo $this->Form->TextBox('ScheduleTime');
	?>
</li>
