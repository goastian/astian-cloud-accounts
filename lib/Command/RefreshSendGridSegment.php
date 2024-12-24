<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Command;

use Exception;
use OCA\EcloudAccounts\Service\SendGridService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RefreshSendGridSegment extends Command {
	private SendGridService $sendGridService;
	public function __construct(SendGridService $sendGridService) {
		$this->sendGridService = $sendGridService;
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('ecloud-accounts:refresh-sendgrid-segment')
			->setDescription('Refreshes the SendGrid segment')
			->addOption(
				'segment-id',
				null,
				InputOption::VALUE_REQUIRED,
				'The ID of the SendGrid segment'
			)
			->addOption(
				'refresh',
				null,
				InputOption::VALUE_NONE,
				'Trigger the refresh of the SendGrid segment'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {

		if ($input->getOption('refresh')) {
			$segmentId = $input->getOption('segment-id');

			if (!$segmentId) {
				$output->writeln('<error>option --segment-id is required.</error>');
				return Command::FAILURE;
			}
			try {
				$success = $this->sendGridService->refreshSendGridSegment($segmentId);
				if ($success) {
					$output->writeln('<info>Segment refreshed successfully.</info>');
					return Command::SUCCESS;
				}
			} catch (Exception $e) {
				$output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
				return Command::FAILURE;
			}
			return 0;
		}

		$output->writeln('No action taken.');
		return 0;
	}

}
