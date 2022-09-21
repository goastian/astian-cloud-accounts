<?php

use OCP\Defaults;
use OCP\IL10N;

/** @var $_ array */
/** @var $l IL10N */
/** @var $theme Defaults */
?>
<?php switch ($_['status']) {
	case 'deleted': ?>
		<p class="message warning">
			<?php
			p($l->t('Your account has been marked for deletion. You can now close this window.'));
		?>
		</p>
		<?php
		break;
	case 'not-found':
		?>
		<p class="message warning">
			<?php
			p($l->t('Account not found.'));
		?>
		</p>
		<?php break;
	case 'invalid-token': ?>
		<div class="warning">
			<p class="message">
				<strong>
					<?php
					p($l->t('The token provided was not found.'));
		?>
				</strong>
			</p>
			<p class="message">
				<?php
				p($l->t('Make sure the link opened is valid or that you are logged-in with the correct user.')); ?>
			</p>

			<p><a href="<?php p($_['rootURL']) ?>"><?php p($l->t('Back to %s', [$theme->getTitle()])); ?></a>
			</p>
		</div>
		<?php
		break;
	default: ?>
		<p class="message warning">
			<?php
			p($l->t('Unknown error.'));
		?>
		</p>
		<?php
		break;
} ?>
</p>
