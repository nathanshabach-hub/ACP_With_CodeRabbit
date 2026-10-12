<script type="text/javascript">
    $(document).ready(function() {
        $("#adminForm").validate();
    });
</script>

<div class="content-wrapper admin-form-page admin-editroomevents-page">
    <section class="content-header">
      <h1>
        Edit Room Events :: Convention - <?php echo $conventionSD->Conventions['name']; ?>
      </h1>
      <ol class="breadcrumb">
          <li><?php echo $this->Html->link('<i class="fa fa-dashboard"></i> <span>Dashboard</span> ', ['controller'=>'admins', 'action'=>'dashboard'], ['escape'=>false]);?></li>
          <li><?php echo $this->Html->link('<i class="fa fa-bars"></i> Conventions ', ['controller'=>'conventions', 'action'=>'index'], ['escape'=>false]);?></li>
		  <li><?php echo $this->Html->link('<i class="fa fa-bullhorn"></i> Seasons ', ['controller'=>'conventions', 'action'=>'seasons',$convSeasonEventD->Conventions['slug']], ['escape'=>false]);?></li>
          <li class="active">Edit Room Events :: Convention - <?php echo $conventionSRoomD->Conventionrooms['room_name']; ?></li>
      </ol>
    </section>

    <section class="content">
     <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">Edit Room Event Setup</h3>
            </div>
            <div class="ersu_message"> <?php echo $this->Flash->render() ?> </div>
            <?php echo $this->Form->create($conventionseasonroomevents, ['id'=>'adminForm', 'type' => 'file']); ?>
                <div class="form-horizontal">
                    <div class="box-body">

					<div class="admin-form-note">Review current events in this room, remove any you no longer need, then assign new ones.</div>

					<div class="room-summary-panel">
						<div class="summary-item">
							<span class="summary-label">Room</span>
							<span class="summary-value"><?php echo $conventionSRoomD->Conventionrooms['room_name']; ?></span>
						</div>
						<div class="summary-item">
							<span class="summary-label">Convention</span>
							<span class="summary-value"><?php echo $conventionSD->Conventions['name']; ?></span>
						</div>
					</div>
					
          <div class="form-group compact-field">
                      <label class="col-sm-2 control-label">Choose New Event(s) <span class="require">*</span></label>
                      <div class="col-sm-10 room-selector-wrap">
						  <div class="field-hint">Select one or more events for this room.</div>
						  <?php echo $this->Form->select('Conventionseasonroomevents.event_ids', $convSeasEventDD, ['id' => 'event_ids', 'multiple' =>'multiple', 'label' => false, 'div' => false, 'class' => 'form-control js-example-basic-multiple required', 'autocomplete' => 'off', 'value' =>$convRoomIDS]); ?>
							<script>
							$(document).ready(function() {
								$('#event_ids').select2({
									placeholder: 'Select events',
									allowClear: true
								});
							});
						</script>
                      </div>
                    </div>
					
					<div class="current-events-header-wrapper">Current Events</div>
					
					<div class="form-group compact-field">
                      <div class="col-sm-12">
                        <div class="room-current-events">
                          <div class="current-events-grid">
                            <?php
                            if (!empty($roomEventsL)) {
                                foreach($roomEventsL as $datarecevent)
                                {
                                ?>
                                <div class="current-event-card">
                                    <div class="current-event-label"><?php echo $datarecevent->event_name; ?></div>
                                    <div class="event-code"><?php echo $datarecevent->event_id_number; ?></div>
                                    <?php
                                    echo $this->Html->link('<i class="fa fa-trash-o"></i> Remove', ['controller' => 'conventions', 'action' => 'deleteeventfromroom',$slug,$slug_convention_season,$datarecevent->id], [ 'escape' => false, 'title' => 'Delete', 'class'=>'btn btn-danger btn-xs action-list delete-list']);
                                    ?>
                                </div>
                                <?php
                                }
                            } else {
                                ?><div class="current-event-empty">No events currently assigned to this room.</div><?php
                            }
                            ?>
                          </div>
                        </div>
                    </div>
					
					
                    <div class="box-footer">
                        <label class="col-sm-2 control-label" for="inputPassword3">&nbsp;</label>
                        <?php echo $this->Form->button('Save', ['type'=>'submit', 'class' => 'btn btn-info', 'div'=>false]); ?>
						<?php echo $this->Html->link('Cancel', ['controller'=>'conventions', 'action' => 'roomevents',$conventionSD->slug], ['class'=>'btn btn-default canlcel_le']); ?>
                        <?php //echo $this->Form->button('Reset', ['type'=>'reset', 'class' => 'btn btn-default canlcel_le', 'div'=>false]); ?>
                    </div>
                  </div>
                </div>
            <?php echo $this->Form->end(); ?>
          </div>
    </section>
  </div>