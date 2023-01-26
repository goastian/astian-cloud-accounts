<?php
style('ecloud-accounts', 'style');
?>

<div id="email-main">
	<div id="email-content">
		<div class="section">
			<h2><?php p($l->t('Do you want to become a beta user?')); ?></h2>
			<p class="settings-hint"><?php p($l->t('You want to experiment new features ahead of the others and provide feedback on them before and if they\'re released? This section is made for you!')) ?></p>
			<p class="settings-hint"><?php p($l->t('To get a preview of our new features you need to become part of our beta users. To do so, simply click on the button below. You can opt out of beta features at anytime.')) ?></p>
			<div id="groups" class="aliases-info">
				<form id="beta-form">
					<input name="beta" id="beta" type="hidden" value="register">
					<input type="submit" class="width300" value="<?= p($l->t('Become a beta user')) ?>" />
				</form>
			</div>
			<div id="message" class="alert"></div>
			<div>
				<p class="settings-hint"><?php p($l->t('Here is the list of currently available beta features:')) ?></p>
				<ul class="beta-apps settings-hint">
					<?php foreach ($betaApps as $app) { ?>
					<li><?= $app ?></li>
					<?php } ?>
				</ul>
			</div>
		</div>
	</div>
</div>
