

/**
 * Event handler. Invoked every time a User selects a date in the DateTimePicker.
 */
function DisplayScheduleTime(DateText, Inst) {
	var ScheduleTime = $('#Form_ScheduleTime').datetimepicker('getDate');
	if(ScheduleTime == null) {
		return;
	}
	var TimeObj = {
		hour: ScheduleTime.getHours(),
		minute: ScheduleTime.getMinutes()
	}

	$('#VisibleScheduleTime').text($.datepicker.formatDate('dd-MM-yy', ScheduleTime) +
																 ' ' +
																 $.datepicker.formatTime('HH:mm', TimeObj)
																 );
}

$(document).ready(function(){
	var ScheduleTimePicker = $('#Form_ScheduleTime').datetimepicker({
		dateFormat: 'yy-mm-dd',
		timeFormat: 'HH:mm',
		timeText: PostSchedulerTimeText,
		hourText: PostSchedulerHourText,
		minuteText: PostSchedulerMinuteText,
		onSelect: DisplayScheduleTime
	});

	DisplayScheduleTime();
	$('#ChangeSchedule').click(function(){
		ScheduleTimePicker.datepicker("show");
	});

	$('#Form_ScheduleTime').toggleClass('Hidden');
	$('#ChangeSchedule').toggleClass('Hidden');
	$('#VisibleScheduleTime').toggleClass('Hidden');
});
