<div class="container-fluid">
	<div class="row">
		<div class="col-sm-9">
			<div class="fpbx-container">
				<div class="display no-border">
					<div class="container-fluid">
						<div class="table-responsive">
							<div id="toolbar-customlangs">
								<a href="config.php?display=soundlang&amp;action=addcustomlang" id="add-customlangs" class="btn btn-danger btn-add" data-type="customlangs" data-section="customlangs">
									<i class="fa fa-plus"></i> <span><?php echo _('Add')?></span>
								</a>
								<button id="remove-customlangs" class="btn btn-danger btn-remove" data-type="customlangs" disabled data-section="customlangs">
									<i class="fa fa-remove"></i> <span><?php echo _('Delete')?></span>
								</button>
							</div>
							<table data-toolbar="#toolbar-customlangs" data-toggle="table" data-pagination="true" data-show-columns="true" data-show-toggle="true" data-search="true" class="table table-striped" id="table-customlangs">
								<thead>
									<tr>
										<th data-checkbox="true"></th>
										<th data-sortable="true" data-field="id"><?php echo _("ID") ?></th>
										<th data-sortable="true"><?php echo _("Language") ?></th>
										<th data-sortable="true"><?php echo _("Description") ?></th>
										<th><?php echo _("Action") ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach($customlangs as $customlang) { ?>
										<tr id = "row<?php echo $customlang['id']?>">
											<td></td>
											<td><?php echo $customlang['id']?></td>
											<td><?php echo $customlang['language']?></td>
											<td><?php echo $customlang['description']?></td>
											<td>
												<a href="config.php?display=soundlang&amp;action=showcustomlang&amp;customlang=<?php echo $customlang['id']?>">
												<i class="fa fa-edit"></i></a>&nbsp;&nbsp;
												<a href="#" id="del<?php echo $customlang['id']?>" data-uid="<?php echo $customlang['id']?>" >
													<i class="fa fa-trash-o"></i>
												</a>
											</td>
										</tr>
									<?php } ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="col-sm-3 hidden-xs bootnav">
			<?php echo load_view(dirname(__FILE__).'/rnav.php',array()); ?>
		</div>
	</div>
</div>
