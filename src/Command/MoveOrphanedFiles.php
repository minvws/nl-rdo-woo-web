<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\FileStorage\Checker\FileStorageChecker;
use App\Domain\FileStorage\OrphanedFileMover;
use App\Service\Utils\Utils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * @codeCoverageIgnore This contains only basic input/output logic, the implementation within the domain is covered.
 */
class MoveOrphanedFiles extends Command
{
    public function __construct(
        private readonly FileStorageChecker $checker,
        private readonly OrphanedFileMover $orphanedFileMover,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('woopie:move-orphaned-files')
            ->setDescription('Moves orphaned files into a separate ("trash") bucket')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Searching for orphaned files...');
        $checkResult = $this->checker->check();
        $orphanedPaths = $checkResult->orphanedPaths;
        if ($orphanedPaths->totalCount === 0) {
            $output->writeln('No orphaned files found, aborting execution');

            return Command::SUCCESS;
        }

        $output->writeln(sprintf(
            '%s orphaned files found, total size %s',
            $orphanedPaths->totalCount,
            Utils::size($orphanedPaths->totalSize)
        ));

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $question = new Question('Please enter the name of the target bucket (ensure this exists first!): ');

        /** @var string $bucketName */
        $bucketName = $helper->ask($input, $output, $question);
        $bucketName = trim($bucketName);

        $question = new ChoiceQuestion(
            sprintf(
                'Please confirm: move %s orphaned files to bucket %s? (enter \'y\' to continue, \'n\' to abort) ',
                $orphanedPaths->totalCount,
                $bucketName,
            ),
            ['yes' => 'continue', 'no' => 'abort execution'],
            false,
        );
        if ($helper->ask($input, $output, $question) === 'no') {
            $output->writeln('No confirmation, aborting execution');

            return Command::SUCCESS;
        }

        $progressBar = new ProgressBar($output, $orphanedPaths->totalCount);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:16s%/%estimated:-16s%');
        $progressBar->start();

        $this->orphanedFileMover->move(
            orphanedPaths: $orphanedPaths,
            targetBucket: $bucketName,
            progressTicker: fn () => $progressBar->advance(),
        );
        $progressBar->finish();

        $output->writeln("\n");
        $output->writeln('Done, moved orphaned files into bucket ' . $bucketName);

        return Command::SUCCESS;
    }
}
