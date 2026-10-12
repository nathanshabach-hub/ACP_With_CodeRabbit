<div class="content-wrapper admin-dashboard">
    <section class="content-header">
        <ol class="breadcrumb" style="position: static; float: none; display: flex; align-items: center; gap: 8px; margin: 0 0 10px; font-size: 14px;">
            <li><?php echo $this->Html->link('<i class="fa fa-dashboard"></i> Home', ['action' => 'dashboard'], ['escape' => false]); ?></li>
            <li class="active">Stage Program Automation</li>
        </ol>
    </section>

    <section class="content">
        <div class="box box-info" style="margin-bottom: 0;">
            <div class="box-header with-border">
                <h3 class="box-title">Stage Program Manager</h3>
            </div>
            <div class="box-body" style="padding: 0;">
                <iframe
                    src="<?php echo h($spaUrl); ?>"
                    title="Stage Program Automation"
                    allow="clipboard-read; clipboard-write"
                    style="width: 100%; height: calc(100vh - 260px); border: 0; display: block;"
                ></iframe>
            </div>
        </div>
    </section>
</div>
