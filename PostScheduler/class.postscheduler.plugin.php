<?php if (!defined('APPLICATION')) exit();
/**
{licence}
*/

// Define the plugin:
$PluginInfo['PostScheduler'] = array(
	'Description' => 'Allows to schedule a Discussion to become visible at from a specific date and time.',
	'Version' => '13.01.17',
	'RequiredApplications' => array('Vanilla' => '2.0.10'),
	'RequiredTheme' => FALSE,
	'RequiredPlugins' => FALSE,
	'HasLocale' => FALSE,
	'SettingsUrl' => '/plugin/postscheduler',
	'SettingsPermission' => 'Garden.AdminUser.Only',
	'Author' => 'Diego Zanella',
	'AuthorEmail' => 'diego@pathtoenlightenment.net',
	'AuthorUrl' => 'http://dev.pathtoenlightenment.net',
	'RegisterPermissions' => array('Plugins.PostScheduler.Manage',
																 'Plugins.PostScheduler.ViewAllScheduled'
																 ),
);

// Load validation functions
require(PATH_PLUGINS . '/PostScheduler/lib/postscheduler.validation.php');

class PostSchedulerPlugin extends Gdn_Plugin {
	// Values for the Schedule field
	const SCHEDULED_YES = 1;
	const SCHEDULED_NO = 0;

	/**
	 * Plugin constructor
	 */
	public function __construct() {
		// dummy
	}

	/**
	 * Base_Render_Before Event Hook
	 *
	 * @param Controller Sender Sending controller instance.
	 */
	public function Base_Render_Before($Sender) {
		$Sender->AddCssFile($this->GetResource('design/css/postscheduler.css', FALSE, FALSE));
		$Sender->AddJsFile($this->GetResource('js/postscheduler.js', FALSE, FALSE));
	}

	/**
	 * Create a method called "PostScheduler" on the PluginController
	 *
	 * @param Controller Sender Sending controller instance.
	 */
	public function PluginController_PostScheduler_Create($Sender) {
		/*
		 * If you build your views properly, this will be used as the <title> for your page, and for the header
		 * in the dashboard. Something like this works well: <h1><?php echo T($this->Data['Title']); ?></h1>
		 */
		$Sender->Title('Post Scheduler Plugin');
		$Sender->AddSideMenu('plugin/postscheduler');

		$Sender->Form = new Gdn_Form();

		$this->Dispatch($Sender, $Sender->RequestArgs);
	}

	public function Controller_Index($Sender) {
		// Prevent non-admins from accessing this page
		$Sender->Permission('Vanilla.Settings.Manage');
		$Sender->SetData('PluginDescription',$this->GetPluginKey('Description'));

		//$Validation = new Gdn_Validation();
		//$ConfigurationModel = new Gdn_ConfigurationModel($Validation);
		//$ConfigurationModel->SetField(array(
		//));

		// Set the model on the form.
		//$Sender->Form->SetModel($ConfigurationModel);

		//// If seeing the form for the first time...
		//if ($Sender->Form->AuthenticatedPostBack() === FALSE) {
		//	// Apply the config settings to the form.
		//	//$Sender->Form->SetData($ConfigurationModel->Data);
		//} else {
		//	$ConfigurationModel->Validation->ApplyRule('Plugin.PostScheduler.TrimSize', 'Integer');
		//
		//	$Saved = $Sender->Form->Save();
		//	if ($Saved) {
		//		$Sender->StatusMessage = T("Your changes have been saved.");
		//	}
		//}

		// GetView() looks for files inside plugins/PluginFolderName/views/ and returns their full path. Useful!
		$Sender->Render($this->GetView('postscheduler_generalsettings_view.php'));
	}

	/**
	 * Add a link to the dashboard menu
	 *
	 * By grabbing a reference to the current SideMenu object we gain access to its methods, allowing us
	 * to add a menu link to the newly created /plugin/PostScheduler method.
	 *
	 * @param Controller Sender Sending controller instance.
	 */
	public function Base_GetAppSettingsMenuItems_Handler($Sender) {
		$Menu = $Sender->EventArguments['SideMenu'];
		$Menu->AddLink('Add-ons', T('Post Scheduler'), 'plugin/postscheduler', 'Garden.AdminUser.Only');
	}

	/**
	 * Add Controller to display Scheduled Discussions.
	 *
	 * @param Controller Sender Sending controller instance.
	 */
	public function DiscussionsController_Scheduled_Create($Sender) {
		// Replace standard View with the "Scheduled Discussions" view
		$this->ShowScheduledDiscussions($Sender, GetValue(0, $Args, 'p1'));
	}

	/**
	 * Adds a "Scheduled" link to tabs in Index page.
	 *
	 * @param Controller Sender Sending controller instance.
	 */
	public function Base_AfterDiscussionTabs_Handler($Sender) {
		$CssClass = $Sender->RequestMethod == 'scheduled' ? 'Active' : '';
		echo Wrap(Anchor(T('Mine (Scheduled)'),
										 '/discussions/scheduled',
										 'ScheduledDiscussions TabLink'),
							'li',
							array('class' => $CssClass)
						 );
	}

	/**
	 * Alters the SQL of a DiscussionModel to only show the Discussions scheduled
	 * by current User and still not displayed.
	 *
	 * @param Controller Sender Sending controller instance.
	 */
	private function ShowUserScheduledDiscussions($Sender) {
		$Now = date('Y-m-d H:i:s');
		$Sender->SQL
			->BeginWhereGroup()
			->Where('InsertUserID', Gdn::Session()->UserID)
			->Where('Scheduled', self::SCHEDULED_YES)
			->Where('ScheduleTime >', $Now)
			->EndWhereGroup();
	}

	/**
	 * Alters the SQL of a DiscussionModel to hide the Discussions that are
	 * scheduled by other Users, and not yet due to be displayed.
	 *
	 * @param Controller Sender Sending controller instance.
	 */
	private function FilterScheduledDiscussions($Sender) {
		$Now = date('Y-m-d H:i:s');
		$Sender->SQL
			->BeginWhereGroup()
			->Where('InsertUserID', Gdn::Session()->UserID)
			->OrWhere('Scheduled', null)
			->OrWhere('ScheduleTime <=', $Now)
			->EndWhereGroup();
	}

	/**
	 * Handler of Event Base::DiscussionMeta.
	 * Displays schedule information for scheduled discussions.
	 *
	 * @param Controller Sender Sending controller instance.
	 */
	public function Base_DiscussionMeta_Handler($Sender) {
		$Discussion = &$Sender->EventArguments['Discussion'];

		$Now = date('Y-m-d H:i:s');
		if($Discussion->Scheduled == self::SCHEDULED_YES &&
			 $Discussion->ScheduleTime > $Now) {
			echo Wrap(sprintf(T('Discussion will be displayed on %s.'),
												Gdn_Format::Date($Discussion->ScheduleTime, T('Date.DefaultDateTimeFormat'))),
								'div',
								array('class' => 'PostInfo ScheduleTime'));

			//var_dump($Discussion->ScheduleTime);
		}
	}

	/**
	 * Handler of Event DiscussionModel::BeforeGet.
	 * Alter SQL of Discussions Model to only retrieve Scheduled discussions.
	 *
	 * @param Controller Sender Sending controller instance.
	 */
	public function DiscussionModel_BeforeGet_Handler($Sender, $Args) {
		// If controller is not Discussions, no action should be taken
		if(Gdn::Controller()->ClassName != 'DiscussionsController') {
			return;
		}

		// Handle requests to displaying the list of User's Scheduled Discussions
		if(Gdn::Controller()->RequestMethod == 'scheduled') {
			$this->ShowUserScheduledDiscussions($Sender);
			return;
		}

		// If User is authorised to see all the Discussions (scheduled or not), just
		// return, no filtering needed
		if(Gdn::Session()->CheckPermission('Plugins.PostScheduler.ViewAllScheduled') ||
			 Gdn::Session()->User->Admin){
			return;
		}
		// Filter normal Discussions list by hiding the scheduled Discussions
		$this->FilterScheduledDiscussions($Sender);
	}

	/**
	 * Handler of Event PostController::DiscussionFormOptions.
	 * Adds to the Discussion Add/Edit page the HTML controls required to schedule
	 * a discussion.
	 *
 	 * @param Controller Sender Sending controller instance.
 	 */
  public function PostController_DiscussionFormOptions_Handler($Sender) {
		$Sender->EventArguments['Options'] .= $this->GetSchedulerControls($Sender);
  }

	/**
	 * Adds validation rules for the scheduling of Discussions.
	 *
	 * @param Gdn_Validation Validation The Validation object associated with a
	 * DiscussionModel instance.
	 */
	private function SetDiscussionValidation(Gdn_Validation $Validation) {
		$Validation->AddRule('CheckNoReplies', 'function:CheckNoReplies');
		$Validation->AddRule('ValidateScheduleTime', 'function:ValidateScheduleTime');

		$Validation->ApplyRule('ScheduleTime', 'CheckNoReplies', T('Discussion cannot be scheduled because it already received replies.'));
		$Validation->ApplyRule('ScheduleTime', 'ValidateScheduleTime', T('Schedule Time is not a valid date/time.'));
	}

	/**
	 * Handler of Event DiscussionModel::BeforeSaveDiscussion.
	 * It adds validation related to Post scheduling.
	 *
 	 * @param Controller Sender Sending controller instance.
 	 */
	public function DiscussionModel_BeforeSaveDiscussion_Handler($Sender) {
		$this->SetDiscussionValidation($Sender->Validation);
		//var_dump($FormPostValues);die();
	}

	public function DiscussionsController_BeforeDiscussionName_Handler($Sender) {
		$Discussion = $Sender->EventArguments['Discussion'];

		if($Discussion->Scheduled == self::SCHEDULED_YES) {
			$Sender->EventArguments['CssClass'] .= ' Scheduled';
		}

		$Now = date('Y-m-d H:i:s');
		if($Discussion->ScheduleTime > $Now) {
			$Sender->EventArguments['CssClass'] .= ' NotVisible';
		}
	}

	/**
	 * Render the controls used to schedule a Post.
	 *
 	 * @param Controller Sender Sending controller instance.
	 */
	protected function GetSchedulerControls($Sender, $Wrap = FALSE) {
		$View = $Sender->FetchView('postscheduler_controls_view', '', 'plugins/PostScheduler');
		if($Wrap) {
			return Wrap($View, 'div', array('class' => 'P'));
		}
		else {
			return $View;
		}
	}

	/**
	 * Plugin setup
	 *
	 * This method is fired only once, immediately after the plugin has been enabled in the /plugins/ screen,
	 * and is a great place to perform one-time setup tasks, such as database structure changes,
	 * addition/modification ofconfig file settings, filesystem changes, etc.
	 */
	public function Setup() {
		// Set up the plugin's default values

		// Create Database Objects needed by the Plugin
		require('install/postscheduler.schema.php');
		PostSchedulerSchema::Install();
	}

	/**
	 * Cleanup operations to be performend when the Plugin is disabled, but not
	 * permanently removed.
	 */
	public function OnDisable() {
	}

	/**
	* Plugin cleanup
	*
	* This method is fired only once, when the plugin is removed, and is a great place to
	* perform cleanup tasks such as deletion of unsued files and folders.
	*/
	public function CleanUp() {
		// Drop Database Objects created by the Plugin
		require('install/postscheduler.schema.php');
		PostSchedulerSchema::Uninstall();
	}

	/**
	 * Renders the Scheduled Discussions page.
	 * This method is an almost exact copy of DiscussionController::Index(), with
	 * the exception that it doesn't load Announcements separately. Announcements
	 * are retrieved as any other Discussion created by the User.
	 *
 	 * @param Controller Sender Sending controller instance.
	 * @param int Page The page to display (used by Pager).
	 */
	private function ShowScheduledDiscussions($Sender, $Page = '0') {
		// Determine offset from $Page
		list($Page, $Limit) = OffsetLimit($Page, C('Vanilla.Discussions.PerPage', 30));
		$Sender->CanonicalUrl(Url(ConcatSep('/', 'discussions', PageNumber($Page, $Limit, TRUE, FALSE)), TRUE));

		// Validate $Page
		if (!is_numeric($Page) || $Page < 0) {
			$Page = 0;
		}

		// Setup head.
		if (!$Sender->Data('Title')) {
			$Sender->Title(T('Scheduled Posts'));
		}

		// Add modules
		$Sender->AddModule('NewDiscussionModule');
		$Sender->AddModule('CategoriesModule');
		$Sender->AddModule('BookmarkedModule');

		// Set criteria & get discussions data
		$Sender->SetData('Category', FALSE, TRUE);
		$DiscussionModel = new DiscussionModel();
		$DiscussionModel->Watching = TRUE;

		// Get Discussion Count
		$CountDiscussions = $DiscussionModel->GetCount();
		$Sender->SetData('CountDiscussions', $CountDiscussions);

		// Get Discussions
		// To get only Scheduled Posts, additional WHERE clauses are required. These
		// are added via an Event Handler, as DiscussionModel doesn't provide a way
		// to do it.
		$Sender->DiscussionData = $DiscussionModel->Get($Page, $Limit, array('Announce' => 'all'));

//		var_dump($Sender->DiscussionData);
//		die();

		$Sender->SetData('Discussions', $Sender->DiscussionData, TRUE);
		$Sender->SetJson('Loading', $Page . ' to ' . $Limit);

		// Build a pager
		$PagerFactory = new Gdn_PagerFactory();
		$Sender->EventArguments['PagerType'] = 'Pager';
		$Sender->FireEvent('BeforeBuildPager');
		$Sender->Pager = $PagerFactory->GetPager($Sender->EventArguments['PagerType'], $this);
		$Sender->Pager->ClientID = 'Pager';
		$Sender->Pager->Configure(
			$Page,
			$Limit,
			$CountDiscussions,
			'discussions/%1$s'
		);
		$Sender->SetData('_PagerUrl', 'discussions/scheduled/{Page}');
		$Sender->SetData('_Page', $Page);
		$Sender->SetData('_Limit', $Limit);
		$Sender->FireEvent('AfterBuildPager');

		// Deliver JSON data if necessary
		if($Sender->DeliveryType() != DELIVERY_TYPE_ALL) {
			$Sender->SetJson('LessRow', $Sender->Pager->ToString('less'));
			$Sender->SetJson('MoreRow', $Sender->Pager->ToString('more'));
			$Sender->View = 'discussions';
		}

		// Set a definition of the user's current timezone from the db. jQuery
		// will pick this up, compare to the browser, and update the user's
		// timezone if necessary.
		$CurrentUser = Gdn::Session()->User;
		if (is_object($CurrentUser)) {
			$ClientHour = $CurrentUser->HourOffset + date('G', time());
			$Sender->AddDefinition('SetClientHour', $ClientHour);
		}

		// Render default view (discussions/index.php)
		$Sender->View = 'index';
		$Sender->Render();
	}
}
