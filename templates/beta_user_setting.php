<?php
script('ecloud-accounts', 'script');
?>

<div id="email-main">
	<div id="email-content">
		<div class="section">
			<?php if ($groupExists) { ?>
				<?php if ($isBeta) { ?>
					<h2><?php p($l->t('You are part of the beta users.')); ?></h2>
					<p class="settings-hint">
						
						<?php $aliasstring = $l->t('Note : As the features are not released yet, you may encounter some bugs. Please report them in {linkopen}GitLab ↗{linkclose} if they have not already been filed. You can also provide some feedback using the form further down.');
						$aliasstring = str_replace('{linkopen}', '<a target="_blank" rel="noreferrer noopener" href="https://gitlab.e.foundation/e/backlog/-/issues">', $aliasstring);
						$aliasstring = str_replace('{linkclose}', '</a>', $aliasstring);
						echo html_entity_decode($aliasstring);
						?>

					</p>
					<p class="settings-hint"><?php p($l->t('Want to take a break from novelties? Just click on the button below. You can become a beta user again anytime!')) ?></p>
				<?php } else { ?>
					<h2><?php p($l->t('Do you want to become the beta user?')); ?></h2>
					<p class="settings-hint"><?php p($l->t('You want to experiment new features ahead of the others and provide feedback on them before and if they\'re released? This section is made for you!')) ?></p>
					<p class="settings-hint"><?php p($l->t('To get a preview showing of our new features you need to become part of our beta users. To do so, simply click on the button below. You can opt out of beta features anytime.')) ?></p>
				<?php } ?>
				<div id="groups" class="aliases-info">
					<form id="isbForm">
						<?php if (!$isBeta) { ?>
							<div>
								<input id="agree" type="checkbox" class="checkbox">
								<label for="agree"><?php p($l->t('I agree with terms & conditions.')) ?></label>
							</div>
						<?php } ?>
						<input name="beta" id="beta" type="hidden" value="<?= ($isBeta) ? 'deregister' : 'register' ?>">
						<input type="submit" value="<?= ($isBeta) ? p($l->t('Opt out of beta features')) : p($l->t('Become beta user')) ?>" />
					</form>
				</div>

			<?php } else { ?>
				<h2><?php p($l->t('Beta Program is closed for now.')); ?></h2>
			<?php } ?>
		</div>
	</div>
</div>