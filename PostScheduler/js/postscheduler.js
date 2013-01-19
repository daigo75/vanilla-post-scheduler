/**
Copyright (c) 2013 Diego Zanella (http://dev.pathtoenlightenment.net)

@package PostScheduler for Vanilla Forums 2.0
@author Diego Zanella <diego@pathtoenlightenment.net>
@copyright Copyright (c) 2013 Diego Zanella (http://dev.pathtoenlightenment.net)
@license http://dev.pathtoenlightenment.net/noncommercial-licence/ Noncommercial Licence

Any usage in websites generating revenue, from any source, is prohibited.
*/

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
		timeFormat: 'hh:mm',
		timeText: PSchedulerTimeText,
		hourText: PSchedulerHourText,
		minuteText: PSchedulerMinuteText,
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
