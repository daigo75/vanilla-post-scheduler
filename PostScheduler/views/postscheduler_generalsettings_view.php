<?php if (!defined('APPLICATION')) exit();
/**
{licence}
*/

// TODO Polish settings page. Since plugin requires none, just write some instructions.
?>
<h1><?php echo T($this->Data['Title']); ?></h1>
<div class="Info">
   <?php echo T($this->Data['PluginDescription']); ?>
</div>
<h3><?php echo T('Settings'); ?></h3>
<?php
		echo Wrap(T('This Plugin does not require configuration.'));
?>
