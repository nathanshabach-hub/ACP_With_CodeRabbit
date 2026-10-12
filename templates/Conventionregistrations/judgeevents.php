<?php echo $this->Html->script('ajax-pagging.js'); ?>
<div class="container-fluid p-0">
	<div class="row">
		<?php echo $this->element('user_left_menu'); ?>
		<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">

			<div class="ersu_message">
				<?php echo $this->Flash->render() ?>
			</div>
			<div class="teachers-top-heading">
				<span>Judge Events</span>
				<?php
				$notificationPending = !empty($judgeEventNotification) && (int)($judgeEventNotification['is_pending'] ?? 0) === 1;
				$notificationMessage = !empty($judgeEventNotification['message']) ? (string)$judgeEventNotification['message'] : 'No new notifications right now.';
				?>
				<button type="button" id="judgeNotificationBtn" class="btn <?php echo $notificationPending ? 'btn-danger' : 'btn-primary'; ?>" style="margin-left:12px;">
					<i class="fa fa-bell"></i> Notification
				</button>
				<?php //echo $this->Html->link('Add Events Heart', ['controller' => 'heartevents', 'action' => 'addnew'], ['escape' => false, 'class' => 'btn btn-primary']); ?>
			</div>
			<!-- dashboard-section-2 start-->

			<div class="m_content" id="listID">
				<?php echo $this->element("Conventionregistrations/judgeevents"); ?>
			</div>


			<!-- dashboard-section-2 end-->

		</main>
	</div>
</div>

<div id="judgeNotificationModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);z-index:10000;align-items:center;justify-content:center;">
	<div style="background:#fff;border-radius:12px;width:92%;max-width:820px;max-height:86vh;box-shadow:0 8px 32px rgba(0,0,0,0.35);display:flex;flex-direction:column;overflow:hidden;">
		<div style="display:flex;justify-content:space-between;align-items:center;padding:12px 14px;border-bottom:1px solid #e3e6ea;">
			<strong style="font-size:14px;line-height:1.2;color:#1c2b3a;">Judge Notification</strong>
			<button type="button" id="judgeNotificationCloseBtn" style="border:none;background:#fff;color:#333;font-size:20px;line-height:1;cursor:pointer;">&times;</button>
		</div>
		<div style="padding:16px;overflow:auto;background:#f7f9fc;">
			<div id="judgeNotificationMessageBox" style="background:#fff;border:1px solid #d9e1eb;border-radius:10px;padding:14px;font-size:14px;line-height:1.5;color:#1f2937;">
				<?php echo nl2br(h($notificationMessage)); ?>
			</div>
			<?php if (!empty($judgeEventNotification)) { ?>
				<?php echo $this->Form->create(null, ['url' => ['controller' => 'conventionregistrations', 'action' => 'judgeevents', $conventionRegD->slug], 'id' => 'judgeNotificationReadForm', 'style' => 'margin-top:12px;']); ?>
					<?php echo $this->Form->hidden('mark_judge_notification_read', ['value' => 1]); ?>
					<label style="display:flex;align-items:center;gap:8px;font-size:14px;color:#243447;margin:0 0 12px 0;">
						<input type="checkbox" id="judge_notification_read" name="judge_notification_read" value="1" <?php echo $notificationPending ? '' : 'checked="checked"'; ?>>
						<span>Read</span>
					</label>
					<button type="submit" class="btn btn-success">Save</button>
				<?php echo $this->Form->end(); ?>
			<?php } ?>
		</div>
	</div>
</div>

<script>
(function () {
	var btn = document.getElementById('judgeNotificationBtn');
	var modal = document.getElementById('judgeNotificationModal');
	var closeBtn = document.getElementById('judgeNotificationCloseBtn');
	if (!btn || !modal || !closeBtn) {
		return;
	}
	btn.addEventListener('click', function () {
		modal.style.display = 'flex';
	});
	closeBtn.addEventListener('click', function () {
		modal.style.display = 'none';
	});
	modal.addEventListener('click', function (event) {
		if (event.target === modal) {
			modal.style.display = 'none';
		}
	});
})();
</script>