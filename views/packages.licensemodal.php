<!--License Modal-->
<div class="modal fade" id="licensemodal">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title mr-auto"><?php echo _("License Agreement")?></h4>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<input type="hidden" id="langid" value=""/>
				<div class="element-container">
					<div class="row">
						<div class="col-md-12">
							<div class="row">
								<div class="form-group">
									<div class="col-md-12">
										<pre id="licensetext" name="licensetext"><?php echo _("Loading...")?></pre>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		<div class="modal-footer">
			<button type="button" class="btn btn-default" data-bs-dismiss="modal"><?php echo _("Close")?></button>
			<button type="button" class="btn btn-primary" id="licensesub"><?php echo _("Accept License")?></button>
		</div>
	 </div>
  </div>
</div>
<!--End License Modal-->
