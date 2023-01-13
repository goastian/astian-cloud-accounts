<?php
style('ecloud-accounts', 'style');
?>

<div id="email-main">
	<div id="email-content">
		<div class="section">
			<h2><?php p($l->t('You are part of the beta users.')); ?></h2>
			<p class="settings-hint">
				<?php p($l->t('Note : as the features are not released yet, you may encounter some bugs. Please report them or give your feedback using the form below.')); ?>
			</p>
			<div>
				<p><?php p($l->t('Here is the list of currently available beta features:')) ?></p>
				<ul class="beta-apps">
					<?php foreach ($betaApps as $apps) { ?>
						<li><?= $apps ?></li>
					<?php } ?>
				</ul>
			</div>
			<form id="issue-submit-form" class="mt-20">
				<p>
					<label for="title" id="title_label"><b><?php p($l->t('Title')); ?> <sup class="color-red">*</sup></b></label>
				</p>
				<p>
					<input type="text" id="title" name="title" placeholder="<?php p($l->t('Summary of your feedback')); ?>">
				</p>
				<p class="mt-20">
					<label for="description" id="description_label"><b><?php p($l->t('Description')); ?> <sup class="color-red">*</sup></b></label>
				</p>
				<p>
					<textarea id="description" name="description" placeholder="<?php p($l->t('Please give us as many details as possible')); ?>"></textarea>
				</p>
				<p class="mt-20">
					<input type="submit" value="<?php p($l->t('Submit')) ?>" class="width300" />
				</p>
				<div id="issue_message" class="alert"></div>
			</form>
			<p class="settings-hint mt-20"><?php p($l->t('Want to take a break from beta features? Just click on the button below. You can become a beta user again anytime!')) ?></p>

			<div id="groups" class="aliases-info">
				<form id="beta-form">
					<input name="beta" id="beta" type="hidden" value="deregister">
					<input type="submit" class="width300 btn-optout" value="<?= p($l->t('Opt out of beta features')) ?>" />
				</form>
			</div>
			<div id="message" class="alert"></div>
		</div>
	</div>
</div>
