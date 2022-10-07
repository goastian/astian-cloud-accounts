<?php
script('beta-user', 'script');
?>

<div id="email-main">
	
	<div id="email-content">
        <div class="section">
			<?php if($isBeta){ ?>
            	<h3><strong><?php p($l->t('You are part of the beta users.'));?></strong></h3>
				<p><?php p($l->t('Do you want to opt out from the Beta testing program.')) ?></p>
			<?php }else{ ?>
				<h3><strong><?php p($l->t('Do you want to become the beta user?'));?></strong></h3>
				<p><?php p($l->t('Want to take a break from novelties? Just click on the button below. You can become a beta user again anytime!')) ?></p>
			<?php } ?>
            <div id="groups" class="aliases-info">
				<form id="isbForm">
					<div>
						<span><input type="checkbox" id="agree" /> <label for="agree">Please agree with terms & Conditions to become beta user?</label></span>
					</div>
					<input name="beta" id="beta" type="hidden" value="<?= ($isBeta) ? 'deregister':'register' ?>" >
					<input type="submit" value="<?= ($isBeta) ? 'Deregister':'Become beta user' ?>"/>
				</form>
            </div>
        </div>
    </div>
</div>
