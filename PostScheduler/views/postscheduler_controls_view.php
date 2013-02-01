<?php if (!defined('APPLICATION')) exit();
/**
Copyright (c) 2013 Diego Zanella (http://dev.pathtoenlightenment.net)

@package PostScheduler for Vanilla Forums 2.0
@author Diego Zanella <diego@pathtoenlightenment.net>
@copyright Copyright (c) 2013 Diego Zanella (http://dev.pathtoenlightenment.net)
@license http://dev.pathtoenlightenment.net/noncommercial-licence/ Noncommercial Licence

Any usage in websites generating revenue, from any source, is prohibited.
*/
?>

<?php
/**
 * JS initialisation
 * The short script below initialises some parameters that will be
 * used during the document.ready() phase by jQuery. It cannot be moved to a
 * separate JS file because such files are not parsed by PHP and, therefore,
 * it would not be possible to retrieve translations and configuration values.
 */

/**
 * Formats the raw ScheduleTime read from the database for use in the User
 * Interface.
 *
 * @param string ScheduleTime A date/time in MySQL Format (YYYY-MM-DD HH:MM:SS).
 * @return string|null The Schedule Time without the seconds, or null if the time
 * is not valid.
 */
function FormatScheduleTime($ScheduleTime) {
	// Replace any invalid date/time with null. This will take care of any "dirty"
	// data fetched from the database
	if(strtotime($ScheduleTime) === false) {
		return null;
	}

	/* Strip the seconds from the ScheduleTime. It's an information that we don't
	 * need and, if present, it doesn't allow the jQuery DateTimePicker to work
	 * correctly.
	 */
	return Gdn_Format::Date($ScheduleTime, '%Y-%m-%d %H:%M');
}
?>
<script type="text/javascript">
	var PSchedulerTimeText = '<?php echo T('Time'); ?>';
	var PSchedulerHourText = '<?php echo T('Hour'); ?>';
	var PSchedulerMinuteText = '<?php echo T('Minute'); ?>';
</script>
<?php // End of JS initialisation ?>

<li class="PostScheduler Controls">
	<?php
	// Schedule Time is stored as UTC and must be converted to Server's Local Time
	// Zone before formatting
	$this->Form->SetValue('ScheduleTime', FormatScheduleTime(
		PostSchedulerPlugin::UTCDateTimeToLocalDateTime($this->Form->GetValue('ScheduleTime')))
	);

	$LastCommentID = $this->Form->GetValue('LastCommentID');
	if(!empty($LastCommentID)) {
		echo Wrap(T('Discussion cannot be scheduled because it already received replies.'),
							'span',
							array('class' => 'ErrorMessage',));
	}
	else {
		echo $this->Form->CheckBox('Scheduled',
															 T('Publish on'),
															 array('value' => 1));

		echo $this->Form->TextBox('ScheduleTime');
		// The Controls below are hidden at the startup and will be revealed by
		// JavaScript. This way, if JavaScript is disabled, they will stay hidden
		// and not confuse the User.
		echo Wrap(T('Not scheduled'),
							'span',
							array('id' => 'VisibleScheduleTime',
										'class' => 'Hidden'));
		echo $this->Form->Button('ChangeSchedule',
															array('type' => 'button',
																		'id' => 'ChangeSchedule',
																		'class' => 'Button Hidden',
																		'value' => T('Change Schedule')));
	}
	?>
	<noscript>
		<?php
			echo Wrap(T('<strong>Post Scheduler plugin</strong> uses JavaScript, but it is disabled. ' .
									'Please enable it to display the User Interface to schedule a post. ' .
									'If you wish to keep it disabled, you will have to enter the schedule ' .
									'date and time in the format <strong>YYYY-MM-DD HH:SS</strong>.'),
								'div',
								array('class' => 'ErrorMessage'));
		?>
	</noscript>
</li>
