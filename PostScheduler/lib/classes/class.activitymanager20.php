<?php if (!defined('APPLICATION')) exit();

/**
{licence}
*/

class ActivityManager20 extends ActivityManager {
	/* @var int Indicates that a Notification has been sent. Constant SENT_YES has
	 * a value of 2 because that's the value used by Vanilla 2.1 to indicate that
	 * a notification was sent. Vanilla 2.0 doesn't anything analogous, therefore
	 * we can use the same value existing in 2.1 to keep the mechanism consistent.
	 */
	const SENT_YES = 2;
	// @var int Indicates that a Notification has not yet been sent.
	const SENT_NO = 0;

	// @var array Holds a list of Discussions, using their Route as a key. It's
	// used to schedule Activity Notifications, which only contain the Discussion
	// Route. Since the same Discussion can generate multiple Activity
	// Notifications, this variable will allow not to query the database every
	// time.
	private $_DiscussionsByRoute = array();


	private function GetDiscussionByRoute($Route) {
		if(empty($Route)) {
			return null;
		}

		// Check if we have the discussion already stored, to avoid fetching it again
		$Discussion = GetValue($Route, $this->_DiscussionsByRoute);
		if(!empty($Discussion)) {
			return $Discussion;
		}

		// Check if the Route is related to a Discussion. If not, we don't need to
		// do anything.
		$RegExMatches = array();
		if(preg_match('/^\/discussion\/([0-9]+?)\//i', $Route, $RegExMatches) != 1) {
			return null;
		}

		// Return the Discussion ID, returned by capturing it in a RegEx group
		$DiscussionID = GetValue(1, $RegExMatches, -1);

		// Without a Discussion Id, there's no point in proceeding
		if($DiscussionID <= 0) {
			return null;
		}

		// Retrieve the Discussion details and store them before returning them
		$DiscussionModel = new DiscussionModel();
		$this->_DiscussionsByRoute[$Route] = $DiscussionModel->GetID($DiscussionID);

		return $this->_DiscussionsByRoute[$Route];
	}

	public function ActivityModel_BeforeActivityInsert_Handler($Sender) {
		// We need the Route to find out if an Activity is linked to a Discussion.
		// This is because Activity entries have no link to other entities, and the
		// Route is the only one we can use
		$Route = GetValue('Route', $Sender->EventArguments['Fields']);

		$Discussion = $this->GetDiscussionByRoute($Route);

		// No need to do anything if the Discussion is not existing
		if(empty($Discussion)) {
			return;
		}

		// Add the DiscussionID to the Activity, as it will be used to reschedule
		// the Notification whenever the Discussion will be updated
		$Sender->EventArguments['Fields']['DiscussionID'] = $Discussion->DiscussionID;

		/* NotificationSent allows to identify Activities that have already been sent
		 * to Users. If a Discussion is scheduled in the future, then the notification
		 * won't be sent straight away, therefore Sent field wil be set to zero. If,
		 * instead, a Discussion is NOT scheduled, or scheduled in the past (which
		 * doesn't make much sense, but it can happen), the related Activity will
		 * be sent immediately. The Sent flag is, therefore, set to 1.
		 */
		$NotificationSent =
			$Discussion->Scheduled == PostSchedulerPlugin::SCHEDULED_YES &&
			$Discussion->ScheduleTime > gmdate('Y-m-d H:i:s') ? self::SENT_NO : self::SENT_YES;
		$Sender->EventArguments['Fields']['NotificationSent'] = $NotificationSent;
	}

	/**
	 * Alters the SQL of a ActivityModel to hide the Activities that are
	 * scheduled to be sent at a later time.
	 *
	 * @see ActivityManager::ActivityModel_AfterActivityQuery_Handler()
	 */
	public function ActivityModel_AfterActivityQuery_Handler($Sender) {
		$Now = gmdate('Y-m-d H:i:s');
		$Sender->SQL
			->LeftJoin('Discussion d', 'd.DiscussionID = a.DiscussionID')
			->BeginWhereGroup()
			->Where('d.Scheduled', null)
			->OrWhere('d.Scheduled', 0)
			->OrWhere('d.ScheduleTime <=', $Now)
			->EndWhereGroup();
	}

	public function SendScheduledNotifications(ActivityModel $ActivityModel) {
		// Retrieve all the Notifications scheduled, due to be sent and not yet sent
		// The filters on Schedule flag and Time are added by
		// PostSchedulerPlugin::ActivityModel_AfterActivityQuery_Handler() method.
		$ActivityNotificationsToSend = $ActivityModel->GetWhere('a.NotificationSent', 0)->Result();

		foreach($ActivityNotificationsToSend as $Activity) {
			// Queue each Activity Notification for sending and update the
			// NotificationSent flag to indicate that the entry has been processed
			$ActivityModel->QueueNotification($Activity->ActivityID);
			$ActivityModel->Update(array('NotificationSent' => self::SENT_YES),
														 array('ActivityID' => $Activity->ActivityID));
		}
		// Send all queued Notifications
		$ActivityModel->SendNotificationQueue();
	}
}
