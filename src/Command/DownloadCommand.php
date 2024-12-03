<?php

declare(strict_types=1);

namespace Php\Pie\Command;

use Php\Pie\ComposerIntegration\ComposerIntegrationHandler;
use Php\Pie\ComposerIntegration\ComposerRunFailed;
use Php\Pie\ComposerIntegration\PieComposerFactory;
use Php\Pie\ComposerIntegration\PieComposerRequest;
use Php\Pie\ComposerIntegration\PieOperation;
use Php\Pie\DependencyResolver\DependencyResolver;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function sprintf;

#[AsCommand(
    name: 'download',
    description: 'Same behaviour as build, but puts the files in a local directory for manual building and installation.',
)]
final class DownloadCommand extends Command
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly DependencyResolver $dependencyResolver,
        private readonly ComposerIntegrationHandler $composerIntegrationHandler,
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        parent::configure();

        CommandHelper::configureDownloadBuildInstallOptions($this);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        CommandHelper::validateInput($input, $this);

        $targetPlatform = CommandHelper::determineTargetPlatformFromInputs($input, $output);

        $requestedNameAndVersion = CommandHelper::requestedNameAndVersionPair($input);

        $composer = PieComposerFactory::createPieComposer(
            $this->container,
            new PieComposerRequest(
                $output,
                $targetPlatform,
                $requestedNameAndVersion,
                PieOperation::Download,
                [], // Configure options are not needed for download only
                null,
                CommandHelper::determineDryRunFromInputs($input),
            ),
        );

        $package = ($this->dependencyResolver)($composer, $targetPlatform, $requestedNameAndVersion);
        $output->writeln(sprintf('<info>Found package:</info> %s which provides <info>%s</info>', $package->prettyNameAndVersion(), $package->extensionName->nameWithExtPrefix()));

        try {
            ($this->composerIntegrationHandler)($package, $composer, $targetPlatform, $requestedNameAndVersion);
        } catch (ComposerRunFailed $composerRunFailed) {
            $output->writeln('<error>' . $composerRunFailed->getMessage() . '</error>');

            return $composerRunFailed->getCode();
        }

        return Command::SUCCESS;
    }
}
