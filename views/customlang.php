<?php
if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'showcustomlang'){
	$heading = '<h1>' . _("Edit Custom Language") . '</h1>';
	$formaction = 'config.php?display=soundlang&action=showcustomlang&language=' . $customlang['id'];
}else{
	$heading = '<h1>' . _("Add Custom Language") . '</h1>';
	$formaction = 'config.php?display=soundlang&action=customlangs';

}

echo $heading;
?>
<div class="container-fluid">
	<div class="row">
		<div class="col-sm-9">
			<div class="fpbx-container">
				<?php if(!empty($message)){ ?>
					<div class="alert alert-<?php echo $message['type']?>"><?php echo $message['message']?></div>
				<?php } ?>
				<div class="display no-border">
					<form class="fpbx-submit" autocomplete="off" name="editM" id="editM" action="<?php echo $formaction ?>" method="post" <?php if(!empty($customlang['id'])) {?>data-fpbx-delete="config.php?display=soundlang&amp;action=delcustomlang&amp;customlang=<?php echo $customlang['id']?>"<?php } ?> onsubmit="return true;">
						<input type="hidden" name="save" value="customlang">
						<input type="hidden" name="customlang" value="<?php echo !empty($customlang['id']) ? $customlang['id'] : ''; ?>">
						<!--LANGUAGE-->
						<div class="element-container">
							<div class="row">
								<div class="col-md-12">
									<div class="row">
										<div class="form-group">
											<div class="col-md-3">
												<label class="control-label" for="language"><?php echo _("Language Code")?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="language"></i>
											</div>
											<div class="col-md-9">
												<input type="text" class="form-control" id="language" name="language" value="<?php echo !empty($customlang['language']) ? $customlang['language'] : ''; ?>">
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-12">
									<span id="language-help" class="help-block fpbx-help-block"><?php echo _("Language Code (e.g. 'en').")?></span>
								</div>
							</div>
						</div>
						<!--END LANGUAGE-->
						<!--DESCRIPTION-->
						<div class="element-container">
							<div class="row">
								<div class="col-md-12">
									<div class="row">
										<div class="form-group">
											<div class="col-md-3">
												<label class="control-label" for="description"><?php echo _("Description")?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="description"></i>
											</div>
											<div class="col-md-9">
												<input type="text" class="form-control" id="description" name="description" value="<?php echo !empty($customlang['description']) ? $customlang['description'] : ''; ?>">
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-12">
									<span id="description-help" class="help-block fpbx-help-block"><?php echo _("A brief description for this Custom Language.")?></span>
								</div>
							</div>
						</div>
						<!--END DESCRIPTION-->
					</form>
				</div>
			</div>
		</div>
		<div class="col-sm-3 hidden-xs bootnav">
			<?php echo load_view(dirname(__FILE__).'/rnav.php',array()); ?>
		</div>
	</div>
</div>
