<?php $c = $this->model ?>

<div class="accordion accordion-blue panel-group" id="settings-FCom_Test">
	<div class="panel panel-default">
		<div class="panel-heading">
			<a href="#" class="accordion-toggle"><?php echo $this->_('Area Settings'); ?></a>
		</div>
		<div class="panel-collapse collapse" id="settings-FCom_Test-group0">
			<div class="panel-body">
				<div class="form-group">
					<label class="col-md-2 control-label"><?php echo $this->_('IP: Mode'); ?></label>
					<div class="col-md-5">
						<textarea class="form-control" name="config[mode_by_ip][FCom_Cron]" rows="5"><?php echo $c->get('mode_by_ip/FCom_Cron'); ?></textarea>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>