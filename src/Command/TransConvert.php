<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class TransConvert extends Command
{
    protected TranslatorInterface|TranslatorBagInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        parent::__construct();

        $this->translator = $translator;
    }

    protected function configure(): void
    {
        $this->setName('translation:convert')
            ->setDescription('Converts translations found in dutch to english')
            ->setHelp('Converts translations found in dutch to english')
            ->setDefinition([
            ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        unset($input);

        $finder = (new Finder())->files()->in(__DIR__ . '/../../templates')->name('*.twig');

        if (! $this->translator instanceof TranslatorBagInterface) {
            throw new \Exception('Translator must implement TranslatorBagInterface');
        }

        $cat = $this->translator->getCatalogue('nl');
        $messages = $cat->all('messages');

        foreach ($finder as $file) {
            $output->writeln('Processing file: <info>' . $file->getRealPath() . '</info>');

            $contents = file_get_contents($file->getRealPath());
            if ($contents === false) {
                $contents = '';
            }

            $contents = preg_replace_callback('/(["\'])([^"\']+)\1\s*\|\s*trans/', function ($matches) use ($messages) {
                foreach ($messages as $engKey => $nlValue) {
                    if ($nlValue == $matches[2]) {
                        return str_replace($matches[2], $engKey, $matches[0]);
                        // return $engKey;
                    }
                    if ($engKey == $matches[2]) {
                        return $matches[0];
                    }
                }

                throw new \Exception('Key not found: ' . $matches[2]);
            }, $contents);

            file_put_contents($file->getRealPath(), $contents);
        }

        return 0;
    }
}
