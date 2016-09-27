<!--License Modal-->
<div class="modal fade" id="licensemodal">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
			</div>
			<div class="modal-body">
				<input type="hidden" id="langid" value=""/>
				<div class="element-container">
					<div class="row">
						<div class="col-md-12">
							<div class="row">
								<div class="form-group">
									<div class="col-md-12">
										<span id="licenselink" name="licenselink"></span>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="element-container">
					<div class="row">
						<div class="col-md-12">
							<div class="row">
								<div class="form-group">
									<div class="col-md-12">
										<span id="licensetext" name="licensetext"></span>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		<div class="modal-footer">
			<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _("Close")?></button>
			<button type="button" class="btn btn-primary" id="licensesub"><?php echo _("Accept License")?></button>
		</div>
	 </div>
  </div>
</div>

<!--End License Modal-->
