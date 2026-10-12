<script type="text/javascript">
    $(document).ready(function() {
        $("#adminForm").validate();
    });
</script>
<div class="content-wrapper admin-list-page admin-transaction-details-page">
    <section class="content-header">
      <h1>
         Transaction Details
      </h1>
      <ol class="breadcrumb">
          <li><?php echo $this->Html->link('<i class="fa fa-dashboard"></i> <span>Dashboard</span> ', ['controller'=>'admins', 'action'=>'dashboard'], ['escape'=>false]);?></li>
          <li><?php echo $this->Html->link('<i class="fa fa-dollar"></i> Transactions ', ['controller'=>'transactions', 'action'=>'index'], ['escape'=>false]);?></li>
          <li class="active">Transaction Details </li>
      </ol>
    </section>
	
	<?php
	if($transactionD->status == 2 || $transactionD->status == 3)
	{
	?>
	<section class="content">
     <div class="box box-info">
            <div class="admin-transaction-accordion">
                <details class="admin-accordion-item" open>
                    <summary>Payment Review</summary>
                    <div class="admin-accordion-body">
                        <div class="ersu_message"> <?php echo $this->Flash->render() ?> </div>
                        <?php echo $this->Form->create($transactions, ['id'=>'adminForm', 'type' => 'file']); ?>
                        <div class="form-horizontal">
                            <div class="box-body">
					
					<div class="form-group">
                      <label class="col-sm-2 control-label">Confirm this payment <span class="require">*</span></label>
                      <div class="col-sm-10">
                          <?php echo $this->Form->select('Transactions.status', $changePaymentS, ['id' => 'status', 'label' => false, 'div' => false, 'class' => 'form-control required', 'autocomplete' => 'off', 'empty' => 'Choose']); ?>
						  <em>Note: If you receive payment via online or invoice, then only you can update payment status as confirmed.</em>
							 
                      </div>
                    </div>
					
					<div class="form-group">
                      <label class="col-sm-2 control-label">Transaction ID <span class="require"></span></label>
                      <div class="col-sm-10">
                          <?php echo $this->Form->input('Transactions.transaction_id_received', ['label'=>false, 'type'=>'text',  'div'=>false, 'class'=>'form-control', 'placeholder'=>'Transaction ID', 'autocomplete'=>'off']); ?>
						  <em>Note: Please enter transaction ID from your bank statements on behalf of the payment received.</em>
                      </div>
                    </div>
					
					<div class="form-group">
                      <label class="col-sm-2 control-label">Notes <span class="require"></span></label>
                      <div class="col-sm-10">
                          <?php echo $this->Form->input('Transactions.transaction_data', ['label'=>false, 'type'=>'text',  'div'=>false, 'class'=>'form-control', 'placeholder'=>'Notes', 'autocomplete'=>'off']); ?>
						  <em>Note: Please enter any other information regarding this invoice or online payment.</em>
                      </div>
                    </div>
                    
                    <div class="box-footer">
                        <label class="col-sm-2 control-label" for="inputPassword3">&nbsp;</label>
                        <?php echo $this->Form->input('Transactions.id', ['label'=>false, 'type'=>'hidden']); ?>
                        <?php echo $this->Form->button('Save', ['type'=>'submit', 'class' => 'btn btn-info', 'div'=>false]); ?>
                        <?php echo $this->Html->link('Cancel', ['controller'=>'transactions', 'action' => 'index'], ['class'=>'btn btn-default canlcel_le']); ?>
                    </div>
					
					<div class="box-footer">
                        <label class="col-sm-2 control-label" for="inputPassword3">&nbsp;</label>
                        <em>Note: Once you update payment status, it cannot be change.</em>
                    </div>
					
					
                          </div>
                        </div>
                        <?php echo $this->Form->end(); ?>
                    </div>
                </details>
            </div>
          </div>
    </section>
	<?php
	}
	?>

    <section class="content">
     <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">Transaction Details</h3>
            </div>
            <div class="ersu_message"> <?php echo $this->Flash->render() ?> </div>
			
			 
			<div class="tbl-resp-listing">
                <table class="table table-bordered table-striped table-condensed cf">
                    <thead class="cf ajshort">
                        <tr>
                            <th class="sorting_paging">Convention</th>
                            <th class="sorting_paging">School</th>
                            <th class="sorting_paging">Season Year</th>
                            <th class="sorting_paging">Price Structure</th>
                            <th class="sorting_paging">Discount</th>
                            <th class="sorting_paging">Amount</th>
                            <th class="sorting_paging">Status</th>
                            <th class="sorting_paging">Transaction Date</th>
                            <th class="sorting_paging">Transaction ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $grossAmount = (float)($transactionD->total_amount ?? 0);
                            $discountAmount = (float)($transactionD->total_discount_applied ?? 0);
                            $finalFromHeader = isset($transactionD->final_amount_paid) && $transactionD->final_amount_paid !== ''
                                ? (float)$transactionD->final_amount_paid
                                : max(0, $grossAmount - $discountAmount);

                            $studentNetAmount = 0.0;
                            if (!empty($transactionStudents)) {
                                foreach ($transactionStudents as $studentRow) {
                                    $studentNetAmount += (float)($studentRow->final_paid_amount ?? 0);
                                }
                            }

                            $teacherNetAmount = 0.0;
                            if (!empty($transactionTeachers)) {
                                foreach ($transactionTeachers as $teacherRow) {
                                    $teacherNetAmount += (float)($teacherRow->paid_amount ?? 0);
                                }
                            }

                            $lineItemTotal = $studentNetAmount + $teacherNetAmount;
                            $finalAmount = $lineItemTotal > 0 ? $lineItemTotal : $finalFromHeader;
                        ?>
                            <?php //pr($datarecord); exit;?> 
                            <tr>
                                <td data-title="Convention"><?php echo $transactionD->Conventions['name'];?></td>
                                <td data-title="School"><?php echo $transactionD->Users['first_name'];?></td>
								<td data-title="Season Year"><?php echo $transactionD->season_year; ?></td>
								<td data-title="Price Structure"><?php echo $priceStructureCR[$transactionD->price_structure]; ?></td>
								<td data-title="Discount"><?php echo CURR.' '.number_format($transactionD->total_discount_applied,2); ?></td>
							<td data-title="Amount"><?php echo CURR.' '.number_format($finalAmount,2); ?></td>
								<td data-title="Status"><?php echo $paymentStatus[$transactionD->status]; ?></td>
                                    <td data-title="Transaction Date"><?php $createdTs = !empty($transactionD->created) ? strtotime((string)$transactionD->created) : false; echo ($createdTs && $createdTs > 0) ? date('M d, Y H:i A', $createdTs) : 'N/A'; ?></td>
								<td data-title="Transaction ID"><?php echo $transactionD->transaction_id_received ? $transactionD->transaction_id_received : 'N/A'; ?></td>
                            </tr>
                    </tbody>
                </table>
            </div>

            <?php
            $studentGenderBreakdown = isset($studentGenderBreakdown) ? $studentGenderBreakdown : ['male' => 0, 'female' => 0, 'other' => 0, 'total' => 0];
            $teacherGenderBreakdown = isset($teacherGenderBreakdown) ? $teacherGenderBreakdown : ['male' => 0, 'female' => 0, 'other' => 0, 'total' => 0];
            $overallGenderBreakdown = isset($overallGenderBreakdown) ? $overallGenderBreakdown : ['male' => 0, 'female' => 0, 'other' => 0, 'total' => 0];
            ?>
            <div class="admin-transaction-accordion">
                <details class="admin-accordion-item" open>
                    <summary>Gender Breakdown (Room Planning)</summary>
                    <div class="admin-accordion-body">
                            <div class="tbl-resp-listing">
                                <table class="table table-bordered table-striped table-condensed cf">
                                    <thead class="cf ajshort">
                                        <tr>
                                            <th class="sorting_paging">Category</th>
                                            <th class="sorting_paging">Male</th>
                                            <th class="sorting_paging">Female</th>
                                            <th class="sorting_paging">Other/Not Set</th>
                                            <th class="sorting_paging">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td data-title="Category">Students</td>
                                            <td data-title="Male"><?php echo (int)$studentGenderBreakdown['male']; ?></td>
                                            <td data-title="Female"><?php echo (int)$studentGenderBreakdown['female']; ?></td>
                                            <td data-title="Other/Not Set"><?php echo (int)$studentGenderBreakdown['other']; ?></td>
                                            <td data-title="Total"><?php echo (int)$studentGenderBreakdown['total']; ?></td>
                                        </tr>
                                        <tr>
                                            <td data-title="Category">Adults (Teachers/Supervisors)</td>
                                            <td data-title="Male"><?php echo (int)$teacherGenderBreakdown['male']; ?></td>
                                            <td data-title="Female"><?php echo (int)$teacherGenderBreakdown['female']; ?></td>
                                            <td data-title="Other/Not Set"><?php echo (int)$teacherGenderBreakdown['other']; ?></td>
                                            <td data-title="Total"><?php echo (int)$teacherGenderBreakdown['total']; ?></td>
                                        </tr>
                                        <tr>
                                            <td data-title="Category"><strong>Overall</strong></td>
                                            <td data-title="Male"><strong><?php echo (int)$overallGenderBreakdown['male']; ?></strong></td>
                                            <td data-title="Female"><strong><?php echo (int)$overallGenderBreakdown['female']; ?></strong></td>
                                            <td data-title="Other/Not Set"><strong><?php echo (int)$overallGenderBreakdown['other']; ?></strong></td>
                                            <td data-title="Total"><strong><?php echo (int)$overallGenderBreakdown['total']; ?></strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                    </div>
                </details>

                <?php if($transactionStudents) { ?>
                <details class="admin-accordion-item">
                    <summary>Transaction Students</summary>
                    <div class="admin-accordion-body">
                            <div class="tbl-resp-listing">
                                <table class="table table-bordered table-striped table-condensed cf">
                                    <thead class="cf ajshort">
                                        <tr>
                                            <th class="sorting_paging">#</th>
                                            <th class="sorting_paging">First Name</th>
                                            <th class="sorting_paging">Middle Name</th>
                                            <th class="sorting_paging">Last Name</th>
                                            <th class="sorting_paging">Birth Year</th>
                                            <th class="sorting_paging">Gender</th>
                                            <th class="sorting_paging">Discount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $cntrS = 0;
                                        foreach ($transactionStudents as $datarecord)
                                        {
                                            $cntrS++;
                                        ?>
                                            <tr>
                                                <td data-title="#"><?php echo $cntrS;?></td>
                                                <td data-title="First Name"><?php echo $datarecord->Users['first_name'];?></td>
                                                <td data-title="Middle Name"><?php echo $datarecord->Users['middle_name'];?></td>
                                                <td data-title="Last Name"><?php echo $datarecord->Users['last_name'];?></td>
                                                <td data-title="Birth Year"><?php echo $datarecord->Users['birth_year'];?></td>
                                                <td data-title="Gender"><?php echo $datarecord->Users['gender'];?></td>
                                                <td data-title="Discount"><?php if($datarecord->applicable_for_discount == 1) echo 'Yes'; else echo 'No';?></td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                    </div>
                </details>
                <?php } ?>

                <?php if($transactionTeachers) { ?>
                <details class="admin-accordion-item">
                    <summary>Transaction Teachers</summary>
                    <div class="admin-accordion-body">
                            <div class="tbl-resp-listing">
                                <table class="table table-bordered table-striped table-condensed cf">
                                    <thead class="cf ajshort">
                                        <tr>
                                            <th class="sorting_paging">#</th>
                                            <th class="sorting_paging">Title</th>
                                            <th class="sorting_paging">First Name</th>
                                            <th class="sorting_paging">Last Name</th>
                                            <th class="sorting_paging">Gender</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $cntrS = 0;
                                        foreach ($transactionTeachers as $datarecord)
                                        {
                                            $cntrS++;
                                        ?>
                                            <tr>
                                                <td data-title="#"><?php echo $cntrS;?></td>
                                                <td data-title="Title"><?php echo $datarecord->Users['title'];?></td>
                                                <td data-title="First Name"><?php echo $datarecord->Users['first_name'];?></td>
                                                <td data-title="Last Name"><?php echo $datarecord->Users['last_name'];?></td>
                                                <td data-title="Gender"><?php echo $datarecord->Users['gender'];?></td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                    </div>
                </details>
                <?php } ?>
            </div>

            <style>
                .admin-transaction-details-page .box.box-info {
                    border: 1px solid #dbe5f0;
                    border-top: 1px solid #dbe5f0;
                    border-radius: 14px;
                    box-shadow: 0 10px 26px rgba(20, 48, 79, 0.10);
                    overflow: hidden;
                }

                .admin-transaction-details-page .box-header {
                    background: linear-gradient(180deg, #eef4fb 0%, #f6f9fd 100%);
                    border-bottom: 1px solid #dbe5f0;
                    padding: 12px 16px;
                }

                .admin-transaction-details-page .box-title {
                    color: #153a63;
                    font-size: 19px;
                    font-weight: 700;
                }

                .admin-transaction-accordion {
                    margin: 10px 14px 14px;
                }

                @media (max-width: 767px) {
                    .admin-transaction-accordion {
                        margin: 8px 8px 10px;
                    }
                }

                .admin-accordion-item {
                    border: 1px solid #dbe5f0;
                    border-radius: 12px;
                    margin-bottom: 12px;
                    overflow: hidden;
                    background: #fff;
                    box-shadow: 0 3px 10px rgba(26, 52, 84, 0.06);
                }

                .admin-accordion-item > summary {
                    background: linear-gradient(180deg, #f7faff 0%, #f1f6fc 100%);
                    color: #1e3556;
                    cursor: pointer;
                    font-size: 15px;
                    font-weight: 600;
                    list-style: none;
                    padding: 12px 16px;
                    border-bottom: 1px solid #dbe5f0;
                }

                .admin-accordion-item > summary::-webkit-details-marker {
                    display: none;
                }

                .admin-accordion-item > summary::after {
                    content: '+';
                    float: right;
                    font-weight: 700;
                    color: #2a4f79;
                }

                .admin-accordion-item[open] > summary::after {
                    content: '-';
                }

                .admin-accordion-body {
                    padding: 12px 14px;
                    background: #fff;
                }

                .admin-accordion-item:not([open]) .admin-accordion-body {
                    display: none;
                }

                .admin-accordion-body .tbl-resp-listing {
                    margin-bottom: 0;
                }

                .admin-accordion-body .table {
                    margin-bottom: 0;
                }

                .admin-transaction-details-page .tbl-resp-listing table thead th {
                    background: #214771;
                    color: #fff;
                    border-color: #2a527f;
                    font-weight: 700;
                }

                .admin-transaction-details-page section.content {
                    margin-bottom: 12px;
                }

                .admin-transaction-details-page section.content + section.content {
                    margin-top: 0;
                }
            </style>
			
			
			<div class="form-horizontal" style="padding-bottom:20px;">
                    <?php echo $this->Html->link('Back to transactions', ['controller'=>'transactions', 'action' => 'index'], ['class'=>'btn btn-default canlcel_le']); ?>
            </div>
			
			
			
			
          </div>
    </section>
  </div>