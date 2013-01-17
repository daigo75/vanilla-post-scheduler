<?php if (!defined('APPLICATION')) exit();
/**
{licence}
*/

/**
 * Validation functions for Post Scheduler plugin.
 *
 */
if(!function_exists('CheckNoReplies')){
	/**
	 * Checks if a Discussion that should be scheduled has any replies. If it does,
	 * it cannot be scheduled.
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
	 */
	function ValidateScheduleTime($Value, $Field, $FormPostedValues){
		return
			($FormPostedValues['Scheduled'] != PostSchedulerPlugin::SCHEDULED_YES) ||
			(!empty($Value) && ValidateDate($Value));
	}
}
