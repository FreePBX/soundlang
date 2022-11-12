<div class="container-fluid">
	<div class="row">
		<div class="col-sm-12">
			<h2><?php echo _("Language Packs")?></h2>

			<div class="fpbx-container">
				<div class="display no-border">
					<div id="toolbar-all">
						<button type="button" id="btnUpdatePackages" class="btn btn-primary">
							<i class="fa fa-cloud-download"></i>&nbsp;<?php echo _("Check Languages Online")?>
    					</button>

						<button type="button" id="refreshshowall" class="btn btn-primary">
							<i class="fa fa-globe"></i>&nbsp;<?php echo _("Show All Languages")?>
    					</button>
						<button type="button" id="refreshshowinstalled" class="btn btn-primary">
							<i class="fa fa-filter"></i>&nbsp;<?php echo _("Show Installed Languages")?>
    					</button>
					</div>
					<table id="soundlang-packages-list" class="table table-striped"
						data-type="soundlang"
						data-toolbar="#toolbar-all" 
						data-toggle="table" 
						data-pagination="true" 
						data-show-columns="true" 
						data-show-toggle="true" 
						data-show-refresh="true"
						data-search="true" 
						data-cookie="true" 
						data-cookie-id-table="soundlangscookie" 
						data-cache="false"
						data-url="ajax.php?module=soundlang&amp;command=packages&amp;tabledata=yes">
						<thead>
							<tr>
								<th data-formatter='soundLangPackagesColName' data-sortable="true" data-field="name"><?php echo _("Language")?></th>
								<th data-formatter='soundLangPackagesColAuthor' data-sortable="true" data-field="author"><?php echo _("Author")?></th>
								<th data-field="version_i" data-width="150px" data-align="center"><?php echo _("Installed Version")?></th>
								<th data-field="version_o" data-width="150px" data-align="center"><?php echo _("Online Version")?></th>
								<th data-formatter='soundLangPackagesColActions' data-width="160px" data-align="center"><?php echo _("Actions")?></th>
							</tr>
						</thead>
					</table>
					<?php echo load_view(dirname(__FILE__).'/packages.licensemodal.php')?>
					<?php echo load_view(dirname(__FILE__).'/packages.language.php')?>
				</div>
			</div>
		</div>
	</div>
</div>