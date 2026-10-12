<?php echo $this->Html->script('ajax-pagging.js'); ?>
<style>
@media print {
	@page {
		margin: 12mm;
		size: A4 landscape;
	}

	body {
		background: #fff !important;
	}

	.cr-packreg-print {
		box-sizing: border-box;
		margin: 0 auto;
		max-width: 100%;
		overflow: hidden;
		width: 100%;
	}

	.cr-packreg-print h4 {
		font-size: 18px !important;
		margin: 0 0 8px !important;
	}

	.cr-packreg-print .cr-pr-card-header {
		padding: 8px 10px;
	}

	.cr-packreg-print .cr-pr-student-name {
		font-size: 15px !important;
	}

	.cr-packreg-print .cr-pr-student-details {
		font-size: 10px;
		gap: 8px;
	}

	.cr-packreg-print .cr-pr-count {
		font-size: 10px;
		padding: 5px 8px;
	}

	.cr-packreg-print .cr-pr-events {
		gap: 4px;
		padding: 6px 8px 8px;
	}

	.cr-packreg-print .cr-pr-event-row {
		gap: 6px;
		grid-template-columns: 56px minmax(0, 1fr) auto;
		padding: 5px 8px;
	}

	.cr-packreg-print .cr-pr-event-id,
	.cr-packreg-print .cr-pr-event-name {
		font-size: 12px;
		line-height: 1.2;
	}

	.cr-packreg-print .cr-pr-status {
		font-size: 10px;
		padding: 4px 8px;
	}

	.cr-packreg-print .cr-pr-pill {
		font-size: 9px;
		padding: 3px 6px;
	}

	.cr-packreg-print .cr-pr-list .cr-pr-card {
		break-inside: avoid;
		page-break-inside: avoid;
		break-after: page;
		page-break-after: always;
		margin-bottom: 0 !important;
	}

	.cr-packreg-print .cr-pr-list .cr-pr-card:last-child {
		break-after: auto;
		page-break-after: auto;
	}

	.cr-packreg-print .cr-pr-list {
		display: block !important;
	}

	.cr-packreg-print .cr-pr-card {
		background: #fff !important;
		border: 1px solid #b9c5d1 !important;
		border-radius: 4px !important;
		box-shadow: none !important;
		cursor: default !important;
		height: auto !important;
		margin: 0 0 12mm !important;
		overflow: visible !important;
		transform: none !important;
	}

	.cr-packreg-print .cr-pr-card-header {
		background: #eef3f7 !important;
		border-bottom: 2px solid #6b7f91 !important;
		box-sizing: border-box;
		min-width: 0;
		padding: 9px 11px !important;
	}

	.cr-packreg-print .cr-pr-card-meta,
	.cr-packreg-print .cr-pr-card-actions {
		min-width: 0;
	}

	.cr-packreg-print .cr-pr-events {
		background: #fff !important;
		display: flex !important;
		gap: 3px !important;
		padding: 7px !important;
	}

	.cr-packreg-print .cr-pr-event-row {
		background: #fff !important;
		border: 1px solid #d5dce3 !important;
		border-radius: 2px !important;
		display: grid !important;
		gap: 6px !important;
		grid-template-columns: 48px minmax(0, 1fr) 112px !important;
		min-width: 0;
		padding: 4px 6px !important;
	}

	.cr-packreg-print .cr-pr-event-id {
		color: #1f4f7d !important;
		font-size: 11px !important;
		font-weight: 700 !important;
	}

	.cr-packreg-print .cr-pr-event-name {
		color: #223f5d !important;
		font-size: 11px !important;
		font-weight: 600 !important;
		min-width: 0;
		overflow-wrap: anywhere;
	}

	.cr-packreg-print .cr-pr-event-action {
		font-size: 10px !important;
		min-width: 0;
		width: 100%;
		text-align: right;
	}

	.cr-packreg-print .cr-pr-status {
		background: transparent !important;
		border: 0 !important;
		color: #273746 !important;
		font-size: 10px !important;
		font-weight: 600 !important;
		padding: 0 !important;
		white-space: normal !important;
		word-break: normal;
	}

	.cr-packreg-print .cr-pr-pill,
	.cr-packreg-print .cr-pr-upload-btn,
	.cr-packreg-print .cr-pr-card-toggle,
	.cr-packreg-print .cr-pr-detail-modal {
		display: none !important;
	}

	.cr-packreg-print .cr-pr-event-row.is-complete {
		background: #f5fbf6 !important;
		border-color: #b8d7bf !important;
	}

	.cr-packreg-print .cr-pr-event-row.is-missing,
	.cr-packreg-print .cr-pr-event-row.is-warning {
		background: #fffaf4 !important;
	}

	.cr-packreg-print .cr-pr-event-row {
		break-inside: avoid;
		page-break-inside: avoid;
	}

	.cr-packreg-print .cr-pr-events {
		display: grid !important;
	}

	.cr-packreg-print .cr-pr-card {
		border: 1px solid #cfd6df;
		border-radius: 4px;
		padding-bottom: 4px;
	}
}
</style>
<div class="container-fluid p-0">
	<div class="row">
		
		<main class="cr-packreg-print">
			
			<h4 class="mt-4">Registration Check List</h4>
			

			<div class="m_content" id="listID">
				<?php echo $this->element("Conventionregistrations/packageregistration", ['enableAccordion' => false]); ?>
			</div>


			<!-- dashboard-section-2 end-->

		</main>
	</div>
</div>

<script type="text/javascript">
<!--
window.print();
//-->
</script>