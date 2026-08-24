<?php

namespace Zeiterfassung\Command;

use Zeiterfassung\Repository\ScannerLogEntryRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:delete-old-logs',
    description: 'Deletes logs that are atleast '.ScannerLogEntryRepository::DAYS_UNTIL_REMOVAL.' days old',
)]
class DeleteOldLogsCommand extends Command
{
    public function __construct(
        private ScannerLogEntryRepository $scannerLogRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Dry run; Run without executing deletion');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('dry-run')) {
            $io->note('Dry mode enabled');

            $count = $this->scannerLogRepository->countOld();
        } else {
            $count = $this->scannerLogRepository->deleteOld();
        }

        $io->success($count ? sprintf('Deleted %d old Log entries.', $count) : 'No Logs to delete');

        return Command::SUCCESS;
    }
}
