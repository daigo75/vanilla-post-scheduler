<?php if (!defined('APPLICATION')) exit();


?>
<div class="PostSchedulerPlugin">
	<div class="Header">
		<h1><?php echo T($this->Data['Title']); ?></h1>
	</div>
	<div class="Content">
		<div class="Info">
		<?php
			echo T($this->Data['PluginDescription']);
			echo Wrap(T('This Plugin does not require configuration. To use its features, ' .
									'simply open a new Discussion, or edit an existing one. The interface ' .
									'will display additional controls that will allow you ' .
									'to schedule when the discussion will become visible on the forum.'),
								'p',
								array('class' => 'Info'));
		?>
		</div>
		<div class="Info">
			<?php
				echo Wrap(T('Notes'), 'h2');
			?>
			<ul>
				<li>
					<?php
						echo T('The schedule date/time you will see on a Discussion is always ' .
									 'in <strong>your time zone</strong>. That is, if you schedule ' .
									 'a Discussion for 12 noon and you are in GMT Time Zone, you will see a message ' .
									 'stating that the post will appear at <strong>12 noon</strong>.<br />' .
									 'If an Administrator from Kyoto (GMT+9) would look at the same post, he would ' .
									 'see a message saying that the post will appear at <strong>9 PM</strong>.'
									 );
					?>
				</li>
				<li>
					<?php
						echo T('You can change the schedule of a Discussion as many times as you like, ' .
									 'even after is was already displayed. Keep in mind, though, that, if you ' .
									 'reschedule a visible discussion to appear at a later date, it will disappear ' .
									 'from the Discussions list, but it could still be accessed by typing its URL.<br />' .
									 'To avoid confusion, it is recommended to avoid rescheduling a discussion that ' .
									 'is already visible.');
					?>
				</li>
				<li>
					<?php
						echo T('You cannot schedule a Discussion that already received replies. ' .
									 'This is to avoid confusion for the Users, who would see it disappear.');
					?>
				</li>
				<li>
					<?php
						echo T('You can always see all your own discussions, even when they are scheduled ' .
									 'to appear at a later date. You can easily identify them by their different ' .
									 'appearance.');
					?>
				</li>
				<li>
					<?php
						echo T('Administrators and Users with proper permissions can always see ' .
									 '<strong>all</strong> discussions, at any time.');
					?>
				</li>
			</ul>
		</div>
		<div class="Overrides">
			<?php
				$Classes = array('ActivityModel');
				$AFCPlugin = Gdn::PluginManager()->GetPluginInstance('AeliaFoundationClasses');
				echo $AFCPlugin->GetOverridesStatusView($this, $Classes);
			?>
		</div>
	</div>
</div>
