<?php if (!defined('APPLICATION')) exit();

/**
{licence}
*/

abstract class ActivityManager {
	private $Log;

	public function __construct() {
		$this->Log = LoggerPlugin::GetLogger();
	}

	public function ActivityModel_BeforeActivityInsert_Handler($Sender) {
		// Intentionally left empty. This method is a placeholder, but, depending on
		// the version of Vanilla, it doesn't necessarily have to perform any
		// operation.
	}

	public function ActivityModel_BeforeProcessingActivityNotifications_Handler($Sender) {
		// Intentionally left empty. This method is a placeholder, but, depending on
		// the version of Vanilla, it doesn't necessarily have to perform any
		// operation.
	}

	public function ActivityModel_BeforeSave_Handler($Sender) {
		// Intentionally left empty. This method is a placeholder, but, depending on
		// the version of Vanilla, it doesn't necessarily have to perform any
		// operation.
	}

	/**
	 * Alters the SQL of a ActivityModel to hide the Activities that are
	 * scheduled to be sent at a later time. This will prevent them from being
	 * sent immediately when a Discussion is created.
	 *
	 * @param Controller Sender Sending controller instance.
	 */
	public function ActivityModel_AfterActivityQuery_Handler($Sender) {
		throw new Exception('Not implemented.');
	}

	public function SendScheduledNotifications(ActivityModel $ActivityModel) {
		throw new Exception('Not implemented.');
	}
}
