<?php if (!defined('APPLICATION')) exit();
/**
{licence}
*/

require('plugin.schema.php');

/**
 * Handle schema changes.
 */
class PostSchedulerSchema extends PluginSchema {
	/**
	 * Adds the columns that will indicate if a Discussion is scheduled.
	 */
	private function AddScheduledDiscussionColumns() {
		Gdn::Structure()->Table('Discussion');

		Gdn::Structure()
			->Column('Scheduled', 'smallint', 0)
			->Column('ScheduleTime', 'datetime', true)
			->Set();

		$this->CreateIndex('Discussion',
											 'IX_DiscussionSchedule',
											 array('ScheduleTime'),
											 '');
	}

	/**
	 * Drops the columns that will indicate if a Discussion is scheduled.
	 */
	private function DropScheduledDiscussionColumns() {
		$this->DropIndex('Discussion', 'IX_DiscussionSchedule');

		Gdn::Structure()->Table('Discussion');

		// Drop "Scheduled" column, if it exists
		if(Gdn::Structure()->ColumnExists('Scheduled')) {
			Gdn::Structure()->DropColumn('Scheduled');
		}
		// Drop "ScheduleTime" column, if it exists
		if(Gdn::Structure()->ColumnExists('ScheduleTime')) {
			Gdn::Structure()->DropColumn('ScheduleTime');
		}
	}

	/**
	 * Adds the columns that will indicate if an Activity Notification is scheduled.
	 */
	private function AddScheduledActivityNotificationColumns() {
		Gdn::Structure()->Table('Activity');

		Gdn::Structure()
			->Column('Scheduled', 'smallint', 0)
			->Column('ScheduleTime', 'datetime', true)
			->Column('NotificationSent', 'smallint', 1)
			->Column('DiscussionID', 'smallint', true)
			->Set();

		$this->CreateIndex('Activity',
											 'IX_ActivitySchedule',
											 array('ScheduleTime, Scheduled, NotificationSent'),
											 '');
		$this->CreateIndex('Activity',
											 'IX_ActivityDiscussionID',
											 array('DiscussionID'),
											 '');
	}

	/**
	 * Drops the columns that will indicate if a Discussion is scheduled.
	 */
	private function DropScheduledActivityNotificationColumns() {
		$this->DropIndex('Discussion', 'IX_ActivitySchedule');
		$this->DropIndex('Discussion', 'IX_ActivityDiscussionID');

		Gdn::Structure()->Table('Activity');

		// Drop "Scheduled" column, if it exists
		if(Gdn::Structure()->ColumnExists('Scheduled')) {
			Gdn::Structure()->DropColumn('Scheduled');
		}
		// Drop "ScheduleTime" column, if it exists
		if(Gdn::Structure()->ColumnExists('ScheduleTime')) {
			Gdn::Structure()->DropColumn('ScheduleTime');
		}
		// Drop "ScheduleTime" column, if it exists
		if(Gdn::Structure()->ColumnExists('NotificationSent')) {
			Gdn::Structure()->DropColumn('NotificationSent');
		}
	}

	/**
	 * Create all the Database Objects in the appropriate order.
	 */
	protected function CreateObjects() {
		$this->AddScheduledDiscussionColumns();
		$this->AddScheduledActivityNotificationColumns();
	}

	/**
	 * Delete the Database Objects.
	 */
	protected function DropObjects() {
		$this->DropScheduledDiscussionColumns();
		$this->DropScheduledActivityNotificationColumns();
	}
}
