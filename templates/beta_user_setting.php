<?php
script('ecloud-accounts', 'script');
?>

<div id="email-main">
	<div id="email-content">
		<div class="section">
			<?php if ($isBeta) { ?>
				<h3><strong><?php p($l->t('You are part of the beta users.')); ?></strong></h3>
				<p><?php p($l->t('Note: as the features are not released yet, you may encounter some bugs. Please report them in GitLab if they\'ve not already been filed. You can also provide some feedback using the form further down.')) ?></p>
			<?php } else { ?>
				<h3><strong><?php p($l->t('Do you want to become the beta user?')); ?></strong></h3>
				<p><?php p($l->t('You want to experiment new features ahead of the others and provide feedback on them before and if they\'re released? This section is made for you!')) ?></p>
				<p><?php p($l->t('To get a preview showing of our new features you need to become part of our beta users. To do so, simply click on the button below. You can opt out of beta features anytime.')) ?></p>
			<?php } ?>
			<div id="groups" class="aliases-info">
				<form id="isbForm">
					<div>
						<span><input type="checkbox" id="agree" /> <label for="agree">Please agree with terms & conditions?</label></span>
					</div>
					<input name="beta" id="beta" type="hidden" value="<?= ($isBeta) ? 'deregister' : 'register' ?>">
					<input type="submit" value="<?= ($isBeta) ? 'Opt out of beta features' : 'Become beta user' ?>" />
				</form>
			</div>
		</div>
	</div>
</div>