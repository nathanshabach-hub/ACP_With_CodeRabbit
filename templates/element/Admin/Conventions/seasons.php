<div class="admin_loader" id="loaderID"><?php echo $this->Html->image('loader_large_blue.gif');?></div>
<?php $isSquadAdmin = (strtolower((string)$this->request->getSession()->read('admin_username')) === 'squad'); ?>
<?php if (!$convseasons->isEmpty()) { ?> 
    <div class="panel-body">
        <style>
            .season-cards-wrap { margin-top: 12px; }
            .cs-card { background: #fff; border: 1px solid #e3e6ec; border-radius: 8px; margin-bottom: 18px; overflow: hidden; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03); }
            .cs-card-head { display: flex; align-items: center; gap: 10px; padding: 14px 18px; background: linear-gradient(180deg, #fbfcff 0%, #f6f9ff 100%); border-bottom: 1px solid #e5ebf5; }
            .cs-year { font-size: 36px; font-weight: 700; line-height: 1; color: #243458; }
            .cs-id { font-size: 12px; color: #7a869c; margin-top: 4px; }
            .cs-spacer { flex: 1; }
            .cs-badge { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; border-radius: 999px; padding: 5px 12px; border: 1px solid transparent; }
            .cs-badge i { font-size: 11px; }
            .b-open { color: #2f7d46; background: #e9f7ee; border-color: #d0ecda; }
            .b-locked { color: #a53a3a; background: #ffe9ed; border-color: #f7ced6; }
            .b-released { color: #2f7d46; background: #e9f7ee; border-color: #d0ecda; }
            .b-hidden { color: #a53a3a; background: #ffe9ed; border-color: #f7ced6; }
            .cs-meta { display: grid; grid-template-columns: repeat(6, minmax(140px, 1fr)); border-bottom: 1px solid #edf1f7; }
            .cs-meta .m { padding: 12px 18px; border-right: 1px solid #edf1f7; }
            .cs-meta .m:last-child { border-right: 0; }
            .cs-meta .lbl { display: block; font-size: 11px; text-transform: uppercase; letter-spacing: 0.4px; color: #8a94a6; margin-bottom: 3px; font-weight: 600; }
            .cs-meta .val { display: block; font-size: 25px; font-weight: 600; color: #2a3b5e; }
            .cs-meta .fee { color: #2a7f44; }
            .cs-actions { padding: 10px 14px 12px 14px; }
            .cs-actions .grp { display: flex; align-items: flex-start; gap: 10px; padding: 8px 4px; border-top: 1px dashed #e8edf5; flex-wrap: wrap; }
            .cs-actions .grp:first-child { border-top: 0; }
            .grp-label { width: 86px; min-width: 86px; color: #6f7d93; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; line-height: 30px; }
            .btn-act { display: inline-flex; align-items: center; gap: 6px; border-radius: 6px; padding: 6px 12px; font-size: 12px; font-weight: 600; border: 1px solid transparent; text-decoration: none !important; margin-right: 6px; margin-bottom: 6px; }
            .btn-act i { font-size: 11px; }
            .a-info { color: #2f4f9e; background: #e9efff; border-color: #d8e2ff; }
            .a-primary { color: #5e3ca2; background: #eee9ff; border-color: #ddd2ff; }
            .a-warn { color: #8b6612; background: #fff5d9; border-color: #f3e6b5; }
            .a-danger { color: #a53a3a; background: #ffe9ed; border-color: #f7ced6; }
            .grp-controls { flex: 1; min-width: 0; }
            .grp-controls .delete-float { float: right; margin-left: 8px; }

            @media (max-width: 1280px) {
                .cs-meta { grid-template-columns: repeat(3, minmax(140px, 1fr)); }
            }
            @media (max-width: 767px) {
                .cs-card-head { flex-wrap: wrap; }
                .cs-year { font-size: 30px; }
                .cs-meta { grid-template-columns: repeat(2, minmax(120px, 1fr)); }
                .cs-meta .m { padding: 10px 12px; }
                .cs-actions .grp { gap: 8px; }
                .grp-label { width: 100%; min-width: 100%; line-height: 1.2; }
                .grp-controls .delete-float { float: none; }
            }
        </style>
        <div class="ersu_message"> <?php echo $this->Flash->render() ?></div>
        <?php echo $this->Form->create(null, ['id' => 'actionFrom', 'method' => 'post']); ?>
        <section id="no-more-tables" class="lstng-section">
            <div class="topn">
                <div class="topn_left">Seasons List</div>
                <div class="topn_right ajshort" id="pagingLinks" align="right">
                    <?php 
                        echo $this->Paginator->counter(['model' => 'Conventionseasons', 'format' => '{{page}} of {{pages}} &nbsp;']);
                        echo $this->Paginator->prev('« Prev');
                        echo $this->Paginator->numbers();
                        echo $this->Paginator->next('Next »');
                        
                    ?>
                </div>
            </div>   

            <div class="season-cards-wrap">
                <?php foreach ($convseasons as $datarecord) { ?>
                    <?php
                    $submissionsFieldName = isset($submissionsLockField) && $submissionsLockField ? $submissionsLockField : 'submissions_open';
                    $submissionsRaw = $datarecord->get($submissionsFieldName);
                    if ($submissionsRaw === null && isset($datarecord->submissions_open)) {
                        $submissionsRaw = $datarecord->submissions_open;
                    }
                    if ($submissionsRaw === null && isset($datarecord->submission_open)) {
                        $submissionsRaw = $datarecord->submission_open;
                    }
                    if (isset($submissionsLockOverrides) && isset($submissionsLockOverrides[$datarecord->slug])) {
                        $submissionsRaw = $submissionsLockOverrides[$datarecord->slug];
                    }
                    if (is_string($submissionsRaw) && strlen($submissionsRaw) === 1) {
                        $submissionsOrd = ord($submissionsRaw);
                        if ($submissionsOrd === 0 || $submissionsOrd === 1) {
                            $submissionsRaw = $submissionsOrd;
                        }
                    }
                    $submissionsOpen = ((int)$submissionsRaw === 1);
                    $resultsReleased = ((int)$datarecord->results_release !== 0);
                    ?>
                    <div class="cs-card">
                        <div class="cs-card-head">
                            <div>
                                <div class="cs-year"><?php echo h($datarecord->season_year); ?></div>
                                <div class="cs-id">DB ID #<?php echo h($datarecord->id); ?></div>
                            </div>
                            <div class="cs-spacer"></div>
                            <?php if (!$isSquadAdmin) { ?>
                                <?php if ($submissionsOpen) { ?>
                                    <span class="cs-badge b-open"><i class="fa fa-unlock"></i> Submissions Open</span>
                                <?php } else { ?>
                                    <span class="cs-badge b-locked"><i class="fa fa-lock"></i> Submissions Locked</span>
                                <?php } ?>
                                <?php if ($resultsReleased) { ?>
                                    <span class="cs-badge b-released"><i class="fa fa-eye"></i> Results Released</span>
                                <?php } else { ?>
                                    <span class="cs-badge b-hidden"><i class="fa fa-eye-slash"></i> Results Hidden</span>
                                <?php } ?>
                            <?php } ?>
                        </div>

                        <div class="cs-meta">
                            <div class="m">
                                <span class="lbl">Reg. Start</span>
                                <span class="val"><?php echo h((($__ts = strtotime((string)$datarecord->registration_start_date)) !== false && $__ts > 0 ? date('M d, Y', $__ts) : 'N/A')); ?></span>
                            </div>
                            <div class="m">
                                <span class="lbl">Reg. End</span>
                                <span class="val"><?php echo h((($__ts = strtotime((string)$datarecord->registration_end_date)) !== false && $__ts > 0 ? date('M d, Y', $__ts) : 'N/A')); ?></span>
                            </div>
                            <div class="m">
                                <span class="lbl">Student (<?php echo h(CURR); ?>)</span>
                                <span class="val fee"><?php echo number_format($datarecord->student_registration_fees, 2); ?></span>
                            </div>
                            <div class="m">
                                <span class="lbl">Non-competitor</span>
                                <span class="val fee"><?php echo number_format($datarecord->non_competitor_registration_fees, 2); ?></span>
                            </div>
                            <div class="m">
                                <span class="lbl">Non-affiliate</span>
                                <span class="val fee"><?php echo number_format($datarecord->non_affiliate_registration_fees, 2); ?></span>
                            </div>
                            <div class="m">
                                <span class="lbl">Supervisor</span>
                                <span class="val fee"><?php echo number_format($datarecord->supervisor_registration_fees, 2); ?></span>
                            </div>
                        </div>

                        <div class="cs-actions">
                            <div class="grp">
                                <span class="grp-label">Setup</span>
                                <div class="grp-controls">
                                    <?php if (!$isSquadAdmin) { echo $this->Html->link('<i class="fa fa-pencil"></i> Prices', ['controller' => 'conventions', 'action' => 'changeprices', $datarecord->slug, $slug], ['escape' => false, 'class' => 'btn-act a-info', 'title' => 'Change Prices']); } ?>
                                    <?php echo $this->Html->link('<i class="fa fa-puzzle-piece"></i> Events', ['controller' => 'conventions', 'action' => 'events', $datarecord->slug, $slug], ['escape' => false, 'class' => 'btn-act a-info', 'title' => 'Manage Events']); ?>
                                    <?php if (!$isSquadAdmin) { echo $this->Html->link('<i class="fa fa-registered"></i> Room Events', ['controller' => 'conventions', 'action' => 'roomevents', $datarecord->slug], ['escape' => false, 'class' => 'btn-act a-primary', 'title' => 'Room Events']); } ?>
                                    <?php if (!$isSquadAdmin) { echo $this->Html->link('<i class="fa fa-clock-o"></i> Scheduling', ['controller' => 'schedulings', 'action' => 'precheck', $datarecord->slug], ['escape' => false, 'class' => 'btn-act a-primary', 'title' => 'Scheduling Pre-check']); } ?>
                                </div>
                            </div>

                            <div class="grp">
                                <span class="grp-label">Results</span>
                                <div class="grp-controls">
                                    <?php if (!$isSquadAdmin) { echo $this->Html->link('<i class="fa fa-circle"></i> Division Pts', ['controller' => 'results', 'action' => 'points', $datarecord->slug, $slug], ['escape' => false, 'class' => 'btn-act a-warn', 'title' => 'Division Points']); } ?>
                                    <?php if (!$isSquadAdmin) { echo $this->Html->link('<i class="fa fa-bullseye"></i> Overall Pts', ['controller' => 'results', 'action' => 'overallpoints', $datarecord->slug, $slug], ['escape' => false, 'class' => 'btn-act a-warn', 'title' => 'Overall Points']); } ?>
                                    <?php if (!$isSquadAdmin) { echo $this->Html->link('<i class="fa fa-trophy"></i> Division Winners', ['controller' => 'results', 'action' => 'divisionwinners', $datarecord->slug, $slug], ['escape' => false, 'class' => 'btn-act a-warn', 'title' => 'Division Winners']); } ?>
                                    <?php if (!$isSquadAdmin) { echo $this->Html->link('<i class="fa fa-mortar-board"></i> Results List', ['controller' => 'results', 'action' => 'overallpositions', $datarecord->slug, $slug], ['escape' => false, 'class' => 'btn-act a-info', 'title' => 'Results List']); } ?>
                                    <?php if (!$isSquadAdmin) { echo $this->Html->link('<i class="fa fa-star"></i> Scripture Award', ['controller' => 'conventions', 'action' => 'scriptureawardslist', $datarecord->slug, $slug], ['escape' => false, 'class' => 'btn-act a-warn', 'title' => 'Scripture Award']); } ?>
                                    <?php echo $this->Html->link('<i class="fa fa-book"></i> Scripture Reading List', ['controller' => 'conventions', 'action' => 'scripturereadinglist', $datarecord->slug, $slug], ['escape' => false, 'class' => 'btn-act a-info', 'title' => 'Scripture Reading List']); ?>
                                    <?php if (!$isSquadAdmin) { echo $this->Html->link('<i class="fa fa-heart"></i> Heart Events', ['controller' => 'heartevents', 'action' => 'listheartevents', $datarecord->slug, $slug], ['escape' => false, 'class' => 'btn-act a-primary', 'title' => 'Events of the Heart Students']); } ?>
                                </div>
                            </div>

                            <div class="grp">
                                <span class="grp-label">Operations</span>
                                <div class="grp-controls">
                                    <?php if (!$isSquadAdmin) { echo $this->Html->link('<i class="fa fa-user-secret"></i> Judges', ['controller' => 'conventions', 'action' => 'judges', $datarecord->slug, $slug], ['escape' => false, 'class' => 'btn-act a-info', 'title' => 'Judges List']); } ?>
                                    <?php if (!$isSquadAdmin) { echo $this->Html->link('<i class="fa fa-gavel"></i> Broken Records', ['controller' => 'conventions', 'action' => 'brokenrecordcertificate', $datarecord->slug, $slug], ['escape' => false, 'class' => 'btn-act a-primary', 'title' => 'Broken Record Certificate']); } ?>
                                    <?php if (!$isSquadAdmin) { echo $this->Html->link('<i class="fa fa-certificate"></i> Certificates', ['controller' => 'conventions', 'action' => 'certificates', $datarecord->slug, $slug], ['escape' => false, 'class' => 'btn-act a-primary', 'title' => 'Certificates']); } ?>
                                    <?php if (!$isSquadAdmin && (int)$datarecord->results_release === 0) { echo $this->Html->link('<i class="fa fa-eye"></i> Results Released', ['controller' => 'conventions', 'action' => 'seasonresultrelease', $datarecord->slug, $slug], ['escape' => false, 'class' => 'btn-act a-info', 'title' => 'Release Result', 'confirm' => 'Are you sure you want to release results ?']); } ?>
                                    <?php if (!$isSquadAdmin && (int)$datarecord->results_release !== 0) { echo $this->Html->link('<i class="fa fa-eye-slash"></i> Hide Results', ['controller' => 'conventions', 'action' => 'seasonresultreleasestop', $datarecord->slug, $slug], ['escape' => false, 'class' => 'btn-act a-warn', 'title' => 'Stop Release Result', 'confirm' => 'Are you sure you want to stop release results ?']); } ?>
                                    <?php if (!$isSquadAdmin) { echo $this->Html->link('<i class="fa fa-lock"></i> '.($submissionsOpen ? 'Lock Submissions' : 'Unlock Submissions'), ['controller' => 'conventions', 'action' => 'togglesubmissions', $datarecord->slug, $slug], ['escape' => false, 'class' => 'btn-act a-warn', 'title' => 'Lock / Unlock Submissions']); } ?>
                                    <?php if (!$isSquadAdmin) { echo $this->Html->link('<i class="fa fa-trash-o"></i> Delete', ['controller' => 'conventions', 'action' => 'deleteconventionsseason', $datarecord->slug, $slug], ['escape' => false, 'class' => 'btn-act a-danger delete-float action-list delete-list', 'title' => 'Delete Season', 'confirm' => 'Are you sure you want to Delete ?']); } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </section>

        <?php echo $this->Form->end(); ?>
    
    </div>
<?php } else { ?>
    <div id="listingJS" style="display: none;" class="alert alert-success alert-block fade in"></div>
    <div class="admin_no_record">Sorry, no record found.</div>
<?php }
?>