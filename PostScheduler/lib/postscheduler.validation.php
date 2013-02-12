<?php if (!defined('APPLICATION')) exit();
/**
{licence}
*/

/**
 * Validation functions for Post Scheduler plugin.
 *
 */
if(!function_exists('UserAuthorisedToSchedulePost')){
	/**
	 * Checks if current User is authorised to schedule a Discussion.
	 *
	 * @param int Value The value of the "Schedule" field.
	 * @param object Field The field which is being validated.
	 * @param array FormPostValues An array containing all the values posted via
	 * the form.
	 * @return bool False if User is trying to schedule a discussion and he is
	 * not authorised to do so. True in any other case.
	 */
	function UserAuthorisedToSchedulePost($Value, $Field, $FormPostedValues){
		return ($FormPostedValues['Scheduled'] != PostSchedulerPlugin::SCHEDULED_YES ||
						Gdn::Session()->CheckPermission('Plugins.PostScheduler.ScheduleDiscussions'));
	}
}

if(!function_exists('CheckNoReplies')){
	/**
	 * Checks if a Discussion that should be scheduled has any replies. If it does,
	 * it cannot be scheduled.
	 *
	 * @param int Value The value of the "ScheduleTime" field.
	 * @param object Field The field which is being validated.
	 * @param array FormPostValues An array containing all the values posted via
	 * the form.
	 * @return bool False if current User is trying to schedule a Discussion which
	 * already received replies. True in any other case.
	 */
	function CheckNoReplies($Value, $Field, $FormPostedValues){
		if($FormPostedValues['Scheduled'] != PostSchedulerPlugin::SCHEDULED_YES ||
			// New discussions are always allowed to be scheduled.
			 empty($FormPostedValues['DiscussionID'])
			 ) {
			return true;
		}

		$DiscussionModel = new DiscussionModel();
		$Discussion = $DiscussionModel->GetID($FormPostedValues['DiscussionID']);

		// An existing discussion can be scheduled only if it doesn't have any reply
		return empty($Discussion->LastCommentID);
	}
}

if(!function_exists('ValidateScheduleTime')){
	/**
	 * Checks that a Schedule Time has been passed if "Schedule" flag is set.
	 *
	 * @param int Value The value of the "ScheduleTime" field.
	 * @param object Field The field which is being validated.
	 * @param array FormPostValues An array containing all the values posted via
	 * the form.
	 * @return bool False if current User is trying to schedule a Discussion and
	 * the Schedule Date/Time is not valid. False in any other case.
	 */
	function ValidateScheduleTime($Value, $Field, $FormPostedValues){
		return
			($FormPostedValues['Scheduled'] != PostSchedulerPlugin::SCHEDULED_YES) ||
			(!empty($Value) && ValidateDate($Value));
	}
}
