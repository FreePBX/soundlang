<div class="container-fluid">
	<div class="row">
		<div class="col-sm-9">
			<div class="fpbx-container">
				<div class="display full-border">
					<form method="POST" class="fpbx-submit">
						<input type="hidden" name="action" value="save">
						<div class="element-container">
							<div class="row">
								<div class="col-md-12">
									<div class="row">
										<div class="form-group">
											<div class="col-md-3">
												<label class="control-label" for="language"><?php echo _("Language") ?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="language"></i>
											</div>
											<div class="col-md-9">
												<select name="language" id="language" class="form-control">
												<?php foreach ($languages as $key => $val) { ?>
													<option value="<?php echo $key?>" <?php echo ($language == $key ? 'selected' : '')?>><?php echo $val?></option>
												<?php } ?>
												</select>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-12">
								<span id="language-help" class="help-block fpbx-help-block"><?php echo _("Language to be used for sound prompts throughout the system")?></span>
							</div>
						</div>
					</div>
					</form>
				</div>
			</div>
		</div>
		<div class="col-sm-3 hidden-xs bootnav">
			<?php echo load_view(dirname(__FILE__).'/rnav.php',array()); ?>
		</div>
	</div>
</div>
