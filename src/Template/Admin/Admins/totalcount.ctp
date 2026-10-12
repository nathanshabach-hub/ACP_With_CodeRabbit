<div class="content-wrapper admin-dashboard">
    <section class="content-header">
        <h1>
            Total Count
        </h1>
        <ol class="breadcrumb">
            <li><?php echo $this->Html->link('<i class="fa fa-dashboard"></i> Home', ['action' => 'dashboard'], ['escape' => false]); ?></li>
            <li class="active">Total Count</li>
        </ol>
    </section>

    <section class="content dashboard-overview">
        <div class="dashboard-hero-card">
            <div class="dashboard-hero-copy">
                <h2>Registered Adults &amp; Students</h2>
                <p>Male/Female breakdown for <?php echo h($convSD->Conventions['name'] ?? ''); ?> (<?php echo h($convSD->season_year); ?>).</p>
            </div>
            <div class="dashboard-hero-icon" aria-hidden="true">
                <i class="fa fa-users"></i>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-lg-3 col-xs-6">
                <div class="small-box bg-light-blue">
                    <div class="inner">
                        <h3><?php echo (int)$maleAdults; ?></h3>
                        <p>Male Adults</p>
                    </div>
                    <div class="icon"><i class="fa fa-male"></i></div>
                </div>
            </div>

            <div class="col-lg-3 col-xs-6">
                <div class="small-box bg-maroon">
                    <div class="inner">
                        <h3><?php echo (int)$femaleAdults; ?></h3>
                        <p>Female Adults</p>
                    </div>
                    <div class="icon"><i class="fa fa-female"></i></div>
                </div>
            </div>

            <div class="col-lg-3 col-xs-6">
                <div class="small-box bg-navy">
                    <div class="inner">
                        <h3><?php echo (int)$maleStudents; ?></h3>
                        <p>Male Students</p>
                    </div>
                    <div class="icon"><i class="fa fa-male"></i></div>
                </div>
            </div>

            <div class="col-lg-3 col-xs-6">
                <div class="small-box bg-purple">
                    <div class="inner">
                        <h3><?php echo (int)$femaleStudents; ?></h3>
                        <p>Female Students</p>
                    </div>
                    <div class="icon"><i class="fa fa-female"></i></div>
                </div>
            </div>
        </div>
    </section>
</div>
