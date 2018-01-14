<div align="" class="span12">
    <div class="alert alert-warning" role="alert">
	<?php echo "Dear " . $USER->firstname . ", " . get_string("paymentrequired", "enrol_hubtel") ?>
	<span style="float:right">
	    <b><?php echo "Pay" . ": {$instance->currency} {$localisedcost}"; ?></b> 
	</span>
    </div>
    <a href="<?php echo "$CFG->wwwroot/enrol/hubtel/pay.php?id=" . $course->id ?>">
	<button class="btn btn-warning" style="float:right">
	    <?php print_string("sendpaymentbutton", "enrol_hubtel") ?>
	</button>
	<center>
	    <img src="<?php echo $CFG->wwwroot . '/enrol/hubtel/pix/hubt.png' ?>" style="width:250px">
	</center>
    </a>
</div>