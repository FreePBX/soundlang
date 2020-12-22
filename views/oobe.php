<div class="container-fluid">
	<div class="row">
		<div class="col-sm-12">
			<h2><?php echo _("Please Select the default locales of the PBX"); ?></h2>
			<h3><?php echo _("Based on your locale your language and timezone have been pre-selected.")?></h3>
			<div class="fpbx-container">
				<div class="display full-border">
					<form method="POST" id="localeForm">
						<div class="element-container">
							<div class="row">
								<div class="col-md-12">
									<div class="row">
										<div class="form-group">
											<div class="col-md-3">
												<label class="control-label" for="oobeSoundLang"><?php echo _('Sound Prompts Language')?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="oobeSoundLang"></i>
											</div>
											<div class="col-md-9">
												<select class="form-control" id="oobeSoundLang" name="oobeSoundLang">
												<?php foreach($langs as $key => $lang) {?>
													<option value="<?php echo $key?>"><?php echo $lang?></option>
												<?php } ?>
												</select>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-12">
									<span id="oobeSoundLang-help" class="help-block fpbx-help-block"><?php echo _("This language will be used for the prompts when you call in to your PBX. You can change it later by navigating to the 'Sound Languages' module")?></span>
								</div>
							</div>
						</div>
						<div class="element-container">
							<div class="row">
								<div class="col-md-12">
									<div class="row">
										<div class="form-group">
											<div class="col-md-3">
												<label class="control-label" for="oobeGuiLang"><?php echo _('System Language')?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="oobeGuiLang"></i>
											</div>
											<div class="col-md-9">
												<select class="form-control" id="oobeGuiLang" name="oobeGuiLang">
													<?php foreach($langlist as $key => $value) {?>
														<option value="<?php echo $key?>"><?php echo $value?></option>
													<?php } ?>
												</select>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-12">
									<span id="oobeGuiLang-help" class="help-block fpbx-help-block"><?php echo _("This language will be used for the GUI and CLI commands for your PBX. You can change it later by navigating to 'Advanced Settings'")?></span>
								</div>
							</div>
						</div>
					</form>
				</div>
				<div class="text-right">
				    <br>
					<button id="submitOobe" class="btn btn-default"><?php echo _("Submit")?></button>
				</div>
			</div>
		</div>
	</div>
</div>
<script>

	var soundLangs = <?php echo json_encode($langs)?>;
	var oobeSoundLanguage = navigator.languages && navigator.languages[0] || navigator.language || navigator.userLanguage;
	oobeSoundLanguage = oobeSoundLanguage.replace("-","_");
	$("#oobeGuiLang").val(oobeSoundLanguage);
	// If that wasn't matched, set it to en_US
	if (!$("#oobeGuiLang").val()) {
		$("#oobeGuiLang").val("en_US");
		oobeSoundLanguage="en_US";
	}
	if(typeof soundLangs[oobeSoundLanguage] !== "undefined") {
		$("#oobeSoundLang").val(oobeSoundLanguage);
	} else {
		oobeSoundLanguage = oobeSoundLanguage.split("_")[0];
		$("#oobeSoundLang").val(oobeSoundLanguage);
	}
	$("#submitOobe").click(function(e) {
		if($("#oobeGuiLang").val() === "") {
			return warnInvalid($("#oobeGuiLang"),_("Please select a valid language"));
		}
		if($("#oobeSoundLang").val() === "") {
			return warnInvalid($("#oobeSoundLang"),_("Please select a valid language"));
		}
		Cookies.set("lang",$("#oobeGuiLang").val());
		$(this).prop("disabled",true);
		$(this).text(_("Processing. This may take some time. Please be patient"));
		e.preventDefault();
		e.stopPropagation();
		$("#localeForm").submit();
	});
</script>
