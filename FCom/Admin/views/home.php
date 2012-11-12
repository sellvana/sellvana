<?php
if (class_exists('Highchart')) {
    $chart = new Highchart();
    $chart->title = array('text' => 'Monthly Average Temperature', 'x' => -20);
    $chart->series[] = array('name' => 'Tokyo', 'data' => array(7.0, 6.9, 9.5));
    $chart->chart->renderTo = 'chart';

}
?>

<script type="text/javascript" src="<?=BApp::baseUrl()?>FCom/Core/js/lib/jquery.min.js" ></script>
<script type="text/javascript" src="<?=BApp::baseUrl()?>FCom/Admin/js/highcharts/highcharts.js" ></script>

<div id="chart"></div>

<script type="text/javascript">
    <?= $chart->render("chart"); ?>
</script>


<?=$this->messagesHtml('admin');?>
<div class="home-icon">
	Welcome.
</div>