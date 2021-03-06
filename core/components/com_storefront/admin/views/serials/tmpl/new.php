<?php
/**
 * @package    hubzero-cms
 * @copyright  Copyright 2005-2019 HUBzero Foundation, LLC.
 * @license    http://opensource.org/licenses/MIT MIT
 */

// No direct access
defined('_HZEXEC_') or die();

$tmpl = Request::getCmd('tmpl', '');

$text = 'Add serial number';

if ($tmpl != 'component')
{
	Toolbar::title(Lang::txt('COM_STOREFRONT').': ' . $text, 'storefront');
	if ($canDo->get('core.edit'))
	{
		Toolbar::save();
	}
	Toolbar::cancel();
}

Html::behavior('framework');
?>
<script type="text/javascript">
function submitbutton(pressbutton)
{
	var form = document.adminForm;

	if (pressbutton == 'cancel') {
		submitform(pressbutton);
		return;
	}

	// form field validation
	if (form.serials.value == '') {
		alert('<?php echo Lang::txt('COM_STOREFRONT_ERROR_MISSING_INFORMATION'); ?>');
	} else {
		submitform(pressbutton);
		window.top.setTimeout("window.parent.location='index.php?option=<?php echo $this->option; ?>&controller=<?php echo $this->controller; ?>&sId=<?php echo $this->sId; ?>'", 700);
	}
}

jQuery(document).ready(function($){
	$(window).on('keypress', function(){
		if (window.event.keyCode == 13) {
			submitbutton('addserials');
		}
	})
});
</script>
<?php if ($this->getError()) { ?>
	<p class="error"><?php echo implode('<br />', $this->getError()); ?></p>
<?php } ?>
<form action="<?php echo Route::url('index.php?option=' . $this->option); ?>" method="post" name="adminForm" id="component-form">
<?php if ($tmpl == 'component') { ?>
	<fieldset>
		<div class="configuration" >
			<div class="fltrt configuration-options">
				<button type="button" onclick="submitbutton('addserials');"><?php echo Lang::txt('Save');?></button>
				<button type="button" onclick="window.parent.$.fancybox.close();"><?php echo Lang::txt('Cancel');?></button>
			</div>
			<?php echo Lang::txt('Add new serial numbers') ?>
		</div>
	</fieldset>
<?php } ?>
	<div class="col span12">
		<fieldset class="adminform">
			<input type="hidden" name="sId" value="<?php echo $this->sId; ?>" />
			<input type="hidden" name="option" value="<?php echo $this->option; ?>" />
			<input type="hidden" name="controller" value="<?php echo $this->controller; ?>">
			<input type="hidden" name="no_html" value="<?php echo ($tmpl == 'component') ? '1' : '0'; ?>">
			<input type="hidden" name="task" value="addusers" />

			<table class="admintable">
				<tbody>
					<tr>
						<td class="key"><label for="field-serials"><?php echo Lang::txt('Serial numbers (comma-separated)'); ?>:</label></td>
						<td><input type="text" name="serials" class="input-users" id="field-serials" value="" size="50" /></td>
					</tr>
				</tbody>
			</table>
		</fieldset>
	</div>

	<?php echo Html::input('token'); ?>
</form>
