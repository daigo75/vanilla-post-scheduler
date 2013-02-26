<?php if (!defined('APPLICATION')) exit();

/**
{licence}
*/

/**
 * Base class containing handles and methods to alter the standard workflow of
 * Vanilla Activity Notifications.
 */
abstract class ActivityManager {
	private $Log;

	/**
	 * Class constructor.
	 *
	 * @return ActivityManager An instance of ActivityManager.
	 */
	public function __construct() {
		$this->Log = LoggerPlugin::GetLogger();
	}

	/**
	 * Event handler. Fired before an Activity is added to Activity table.
	 *
	 * @param Controller Sender Requesting controller instance.
	 */
	public function ActivityModel_BeforeActivityInsert_Handler($Sender) {
		// Intentionally left empty. This method is a placeholder, but, depending on
		// the version of Vanilla, it doesn't necessarily have to perform any
		// operation.
	}

	/**
	 * Event handler. Fired before an Activity is processed by the ActivityModel
	 * (before any Save, Notify or other manipulation occurs).
	 * This event is fired only by the modified ActivityModel for Vanilla 2.1b1.
	 *
	 * @param Controller Sender Requesting controller instance.
	 */
	public function ActivityModel_BeforeProcessingActivityNotifications_Handler($Sender) {
		// Intentionally left empty. This method is a placeholder, but, depending on
		// the version of Vanilla, it doesn't necessarily have to perform any
		// operation.
	}

	/**
	 * Event handler. Fired before an Activity is saved to the Activity table.
	 *
	 * @param Controller Sender Requesting controller instance.
	 */
	public function ActivityModel_BeforeSave_Handler($Sender) {
		// Intentionally left empty. This method is a placeholder, but, depending on
		// the version of Vanilla, it doesn't necessarily have to perform any
		// operation.
	}

	/**
	 * Alters the SQL of a ActivityModel to hide the Activities that are
	 * scheduled to be sent at a later time. This will prevent them from being
	 * sent immediately when a Discussion is created. Must be implemented by
	 * descendant classes.
	 *
	 * @param Controller Sender Sending controller instance.
	 */
	public function ActivityModel_AfterActivityQuery_Handler($Sender) {
		throw new Exception('Not implemented.');
	}

	/**
	 * Retrieves all scheduled notifications that are due to be sent and emails
	 * them to User who requested to be notified. Must be implemented by
	 * descendant classes.
	 *
	 * @param ActivityModel ActivityModel The Activity Model to use to retrieve
	 * the pending notifications.
	 */
	public function SendScheduledNotifications(ActivityModel $ActivityModel) {
		throw new Exception('Not implemented.');
	}
}
