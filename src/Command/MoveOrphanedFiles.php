<?php

declare(strict_types=1);

namespace Shared\Command;

use Shared\Domain\FileStorage\Checker\FileStorageChecker;
use Shared\Domain\FileStorage\OrphanedFileMover;
use Shared\Service\Utils\Utils;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Webmozart\Assert\Assert;

use function sprintf;
use function trim;

#[AsCommand(name: self::COMMAND_NAME, description: 'Moves orphaned files into a separate ("trash") bucket')]
class MoveOrphanedFiles extends Command
{
    public const string COMMAND_NAME = 'woopie:move-orphaned-files';

    public function __construct(
        private readonly FileStorageChecker $checker,
        private readonly OrphanedFileMover $orphanedFileMover,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Searching for orphaned files...');
        $checkResult = $this->checker->check();
        $orphanedPaths = $checkResult->orphanedPaths;
        if ($orphanedPaths->totalCount === 0) {
            $output->writeln('No orphaned files found, aborting execution');

            return self::SUCCESS;
        }

        $output->writeln(sprintf(
            '%s orphaned files found, total size %s',
            $orphanedPaths->totalCount,
            Utils::size($orphanedPaths->totalSize)
        ));

        $helper = $this->getHelper('question');
        Assert::isInstanceOf($helper, QuestionHelper::class);

        $question = new Question('Please enter the name of the target bucket (ensure this exists first!): ');

        $bucketName = $helper->ask($input, $output, $question);
        Assert::string($bucketName);
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

            return self::SUCCESS;
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

        return self::SUCCESS;
    }
}
