<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Command;

use Exception;
use OCA\EcloudAccounts\Service\SendGridService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteSendgridContact extends Command {
	private SendGridService $sendGridService;

	public function __construct(SendGridService $sendGridService) {
		$this->sendGridService = $sendGridService;
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('ecloud-accounts:delete-sendgrid-contact')
			->setDescription('Delete SendGrid contacts within a segment and date range')
			->addOption(
				'segment-id',
				null,
				InputOption::VALUE_REQUIRED,
				'The ID of the SendGrid segment'
			)
			->addOption(
				'start-date',
				null,
				InputOption::VALUE_REQUIRED,
				'Start date in YYYY-MM-DD format'
			)
			->addOption(
				'end-date',
				null,
				InputOption::VALUE_REQUIRED,
				'End date in YYYY-MM-DD format'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$segmentId = $input->getOption('segment-id');
		$startDate = $input->getOption('start-date');
		$endDate = $input->getOption('end-date');

		if (!$segmentId || !$startDate || !$endDate) {
			$output->writeln('<error>All options --segment-id, --start-date, and --end-date are required.</error>');
			return Command::FAILURE;
		}

		try {
			$contacts = $this->sendGridService->fetchContactsFromSegment($segmentId);
			$filteredContacts = $this->sendGridService->filterContactsByDateRange($contacts, $startDate, $endDate);
			$contactIds = array_column($filteredContacts, 'id');

			if (empty($contactIds)) {
				$output->writeln('<info>No contacts found within the specified date range.</info>');
				return Command::SUCCESS;
			}

			$this->sendGridService->deleteContacts($contactIds);
			$output->writeln('<info>Successfully deleted ' . count($contactIds) . ' contacts.</info>');
			return Command::SUCCESS;
		} catch (Exception $e) {
			$output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
			return Command::FAILURE;
		}
	}
}
