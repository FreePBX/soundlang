
<div class="modal fade" id="langmodal">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
				<h4 class="modal-title"><?php echo _("Formats Installed")?></h4>
			</div>
			<div class="modal-body">
				<table id="soundlang-packages-lang-list" class="table table-striped"
					data-toggle="table"
					data-pagination="false"
					data-show-columns="false"
					data-show-toggle="false"
					data-search="false"
					data-cookie="true"
					data-cookie-id-table="soundlangscookie-lang"
					data-url="ajax.php?module=soundlang&amp;command=packagesLang&amp;lang=">
					<thead>
						<tr>
							<th data-sortable="true" data-field="module"><?php echo _("Module")?></th>
							<th data-sortable="true" data-field="format"><?php echo _("Format")?></th>
							<th data-sortable="true" data-field="version"><?php echo _("Available")?></th>
							<th data-sortable="true" data-field="installed"><?php echo _("Installed")?></th>
						</tr>
					</thead>
				</table>
			</div>
	 	</div>
  	</div>
</div>

