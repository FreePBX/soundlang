<div class="container-fluid">
	<div class="row">
		<div class="col-sm-9">
			<div class="fpbx-container">
				<div class="display no-border">
					<table  data-toggle="table" data-pagination="true" data-show-columns="true" data-show-toggle="true" data-search="true" data-state-save="true" data-state-save-id-table="soundlangscookie"  class="table table-striped">
						<thead>
							<tr>
								<th data-sortable="true"><?php echo _("Module")?></th>
								<th data-sortable="true"><?php echo _("Language")?></th>
								<th data-sortable="true"><?php echo _("Format")?></th>
								<th data-sortable="true"><?php echo _("Available")?></th>
								<th data-sortable="true"><?php echo _("Installed")?></th>
								<th data-sortable="true"><?php echo _("Actions")?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($packages as $package) { ?>
							<tr>
								<td><?php echo $package['module'] ?></td>
								<td>
								<?php
									$lang = explode('_', $package['language'], 2);

									if (!empty($languagelocations[$lang[1]]) && !empty($languagenames[$lang[0]])) {
										$name = $languagenames[$lang[0]] . ' - ' . $languagelocations[$lang[1]] . ' (' . $package['language'] . ')';
									} else if (!empty($languagenames[$lang[0]])) {
										$name = $languagenames[$lang[0]] . ' (' . $package['language'] . ')';
									} else {
										$name = $package['language'];
									}

									echo $name;
								?>
								</td>
								<td><?php echo $package['format'] ?></td>
								<td><?php echo $package['version'] ?></td>
								<td><?php echo $package['installed'] ?></td>
								<td>
								<?php if (empty($package['installed']) || (!empty($package['version']) && $package['installed'] != $package['version'])) { ?>
									<a href="config.php?display=soundlang&action=install&type=<?php echo $package['type'] ?>&module=<?php echo $package['module'] ?>&language=<?php echo $package['language'] ?>&format=<?php echo $package['format'] ?>&version=<?php echo $package['version'] ?>"><i class="fa fa-download fa-fw"></i></a>
								<?php } else { ?>
									<a href="config.php?display=soundlang&action=uninstall&type=<?php echo $package['type'] ?>&module=<?php echo $package['module'] ?>&language=<?php echo $package['language'] ?>&format=<?php echo $package['format'] ?>"><i class="fa fa-ban fa-fw"></i></a>
								<?php } ?>
								</td>
							</tr>
							<?php } ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<div class="col-sm-3 hidden-xs bootnav">
			<?php echo load_view(dirname(__FILE__).'/rnav.php',array()); ?>
		</div>
	</div>
</div>
