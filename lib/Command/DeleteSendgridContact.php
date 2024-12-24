<?php

declare(strict_types=1);

namespace OCA\EcloudAccounts\Command;

use Exception;
use OCA\EcloudAccounts\Service\SendGridService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteSendGridContactsCommand extends Command {
	private SendGridService $sendGridService;

	public function __construct(SendGridService $sendGridService) {
		$this->sendGridService = $sendGridService;
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('ecloud-accounts:delete-sendgrid-contacts')
			->setDescription('Delete SendGrid contacts within a specified date range.')
			->addOption(
				'start-date',
				null,
				InputOption::VALUE_REQUIRED,
				'Start date in format Y-m-d H:i:s',
				''
			)
			->addOption(
				'end-date',
				null,
				InputOption::VALUE_REQUIRED,
				'End date in format Y-m-d H:i:s',
				''
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$startDate = $input->getOption('start-date');
		$endDate = $input->getOption('end-date');

		if (empty($startDate) || empty($endDate)) {
			$output->writeln('Both start-date and end-date options are required.');
			return Command::FAILURE;
		}

		try {
			$contacts = $this->sendGridService->fetchContactsByDateRange($startDate, $endDate);
			$contactIds = array_column($contacts, 'id');

			if (empty($contactIds)) {
				$output->writeln('No contacts found within the specified date range.');
				return Command::SUCCESS;
			}

			$this->sendGridService->deleteContacts($contactIds);
			$output->writeln('Successfully deleted ' . count($contactIds) . ' contacts.');
			return Command::SUCCESS;
		} catch (Exception $e) {
			$output->writeln('Error: ' . $e->getMessage());
			return Command::FAILURE;
		}
	}
}
