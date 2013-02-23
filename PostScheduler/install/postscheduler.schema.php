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
											 array('Scheduled', 'ScheduleTime'),
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
			->Set();

		$this->CreateIndex('Activity',
											 'IX_ActivitySchedule',
											 array('Scheduled', 'ScheduleTime'),
											 '');
	}

	/**
	 * Drops the columns that will indicate if a Discussion is scheduled.
	 */
	private function DropScheduledActivityNotificationColumns() {
		$this->DropIndex('Discussion', 'IX_ActivitySchedule');

		Gdn::Structure()->Table('Activity');

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
