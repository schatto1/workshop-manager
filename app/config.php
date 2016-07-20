<?php

use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\IO\NullIO;
use Composer\Json\JsonFile;
use Composer\Util\RemoteFilesystem;
use Github\Client;
use Humbug\SelfUpdate\Updater as PharUpdater;
use Interop\Container\ContainerInterface;
use PhpSchool\WorkshopManager\Application;
use PhpSchool\WorkshopManager\Command\InstallWorkshop;
use PhpSchool\WorkshopManager\Command\ListWorkshops;
use PhpSchool\WorkshopManager\Command\SearchWorkshops;
use PhpSchool\WorkshopManager\Command\SelfRollback;
use PhpSchool\WorkshopManager\Command\SelfUpdate;
use PhpSchool\WorkshopManager\Command\UninstallWorkshop;
use PhpSchool\WorkshopManager\Command\UpdateWorkshop;
use PhpSchool\WorkshopManager\Command\VerifyInstall;
use PhpSchool\WorkshopManager\ComposerInstallerFactory;
use PhpSchool\WorkshopManager\Downloader;
use PhpSchool\WorkshopManager\Filesystem;
use PhpSchool\WorkshopManager\IOFactory;
use PhpSchool\WorkshopManager\Linker;
use PhpSchool\WorkshopManager\ManagerState;
use PhpSchool\WorkshopManager\Repository\InstalledWorkshopRepository;
use PhpSchool\WorkshopManager\Repository\RemoteWorkshopRepository;
use PhpSchool\WorkshopManager\Uninstaller;
use PhpSchool\WorkshopManager\Updater;
use PhpSchool\WorkshopManager\VersionChecker;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

return [
    Application::class => \DI\factory(function (ContainerInterface $c) {
        $application = new \PhpSchool\WorkshopManager\Application('PHP School workshop manager', '1.0.0', $c);
        $application->command('install workshopName [-f|--force]', InstallWorkshop::class)
            ->setDescription('Install a PHP School workshop.');
        $application->command('uninstall workshopName [-f|--force]', UninstallWorkshop::class)
            ->setDescription('Uninstall a PHP School workshop.');
        $application->command('update workshopName [-f|--force]', UpdateWorkshop::class)
            ->setDescription('update a PHP School workshop.');
        $application->command('search workshopName', SearchWorkshops::class)
            ->setDescription('Search for a PHP School workshop.');
        $application->command('installed', ListWorkshops::class)
            ->setDescription('List installed PHP School workshops.');
        $application->command('self-update', SelfUpdate::class)
            ->setDescription('Update the workshop manager to the latest version.');
        $application->command('rollback', SelfRollback::class)
            ->setDescription('Rollback the workshop manager to the previous version.');
        $application->command('verify', VerifyInstall::class)
            ->descriptions('Verify your installation is working correctly');

        $application->setAutoExit(false);
        $application->setCatchExceptions(false);

        return $application;
    }),
    SelfUpdate::class => \DI\factory(function (ContainerInterface $c) {
        return new SelfUpdate($c->get(PharUpdater::class));
    }),
    SelfRollback::class => \DI\factory(function (ContainerInterface $c) {
        return new SelfRollback($c->get(PharUpdater::class));
    }),
    PharUpdater::class => \DI\factory(function (ContainerInterface $c) {
        $updater = new PharUpdater(null, false);
        $strategy = $updater->getStrategy();
        $strategy->setPharUrl('https://php-school.github.io/workshop-manager/workshop-manager.phar');
        $strategy->setVersionUrl('https://php-school.github.io/workshop-manager/workshop-manager.phar.version');
        return $updater;
    }),
    InstallWorkshop::class => \DI\factory(function (ContainerInterface $c) {
        return new InstallWorkshop(
            $c->get(Installer::class),
            $c->get(Linker::class),
            $c->get(InstalledWorkshopRepository::class),
            $c->get(RemoteWorkshopRepository::class)
        );
    }),
    UninstallWorkshop::class => \DI\factory(function (ContainerInterface $c) {
        return new UninstallWorkshop(
            $c->get(Uninstaller::class),
            $c->get(InstalledWorkshopRepository::class),
            $c->get(Linker::class)
        );
    }),
    UpdateWorkshop::class => \DI\factory(function (ContainerInterface $c) {
        return new UpdateWorkshop(
            $c->get(Updater::class)
        );
    }),
    SearchWorkshops::class => \DI\factory(function (ContainerInterface $c) {
        return new SearchWorkshops(
            $c->get(RemoteWorkshopRepository::class),
            $c->get(InstalledWorkshopRepository::class),
            $c->get(OutputInterface::class)
        );
    }),
    ListWorkshops::class => \DI\factory(function (ContainerInterface $c) {
        return new ListWorkshops(
            $c->get(InstalledWorkshopRepository::class),
            $c->get(VersionChecker::class)
        );
    }),
    VerifyInstall::class => \DI\factory(function (ContainerInterface $c) {
        return new VerifyInstall(
            $c->get(InputInterface::class),
            $c->get(OutputInterface::class),
            $c->get('appDir')
        );
    }),
    Linker::class => \DI\factory(function (ContainerInterface $c) {
        return new Linker(
            $c->get(Filesystem::class),
            $c->get('appDir'),
            $c->get(OutputInterface::class)
        );
    }),
    Installer::class => \DI\factory(function (ContainerInterface $c) {
        $io = $c->get(IOFactory::class)->getNullableIO($c->get(InputInterface::class), $c->get(OutputInterface::class));
        return new Installer(
            $c->get(InstalledWorkshopRepository::class),
            $c->get(RemoteWorkshopRepository::class),
            $c->get(Linker::class),
            $c->get(Filesystem::class),
            $c->get('appDir'),
            new ComposerInstallerFactory($c->get(Factory::class), $io),
            $c->get(Client::class),
            $c->get(VersionChecker::class)
        );
    }),
    Uninstaller::class => \DI\factory(function (ContainerInterface $c) {
        return new Uninstaller(
            $c->get(InstalledWorkshopRepository::class),
            $c->get(Linker::class),
            $c->get(Filesystem::class),
            $c->get('appDir')
        );
    }),
    Updater::class => \DI\factory(function (ContainerInterface $c) {
        return new Updater(
            $c->get(Installer::class),
            $c->get(Uninstaller::class),
            $c->get(InstalledWorkshopRepository::class),
            $c->get(VersionChecker::class)
        );
    }),
    VersionChecker::class => \DI\factory(function (ContainerInterface $c) {
        return new VersionChecker($c->get(Client::class));
    }),
    Client::class => \DI\factory(function (ContainerInterface $c) {
        return new Client(new \Github\HttpClient\CachedHttpClient);
    }),
    Factory::class => \DI\object(),
    IOFactory::class => \DI\object(),
    IOInterface::class => \DI\factory(function (ContainerInterface $c) {
        return $c->get(IOFactory::class)->getIO(
            $c->get(InputInterface::class),
            $c->get(OutputInterface::class)
        );
    }),
    InputInterface::class => \Di\factory(function () {
        return new \Symfony\Component\Console\Input\ArgvInput($_SERVER['argv']);
    }),
    OutputInterface::class => \DI\factory(function (ContainerInterface $c) {
        $input     = $c->get(InputInterface::class);
        $verbosity = OutputInterface::VERBOSITY_NORMAL;

        if (true === $input->hasParameterOption(array('--quiet', '-q'), true)) {
            $verbosity = OutputInterface::VERBOSITY_QUIET;
        }

        if ($input->hasParameterOption('-vvv', true)) {
            $verbosity = OutputInterface::VERBOSITY_DEBUG;
        }

        if ($input->hasParameterOption('-vv', true)) {
            $verbosity = OutputInterface::VERBOSITY_VERY_VERBOSE;
        }

        if ($input->hasParameterOption('-v', true)) {
            $verbosity = OutputInterface::VERBOSITY_VERBOSE;
        }

        $output = new \Symfony\Component\Console\Output\ConsoleOutput($verbosity);
        $output->getFormatter()->setStyle('phps', new OutputFormatterStyle('magenta'));
        return $output;
    }),
    RemoteWorkshopRepository::class => \DI\factory(function (ContainerInterface $c) {
        return new RemoteWorkshopRepository(
            new JsonFile(
                'https://raw.githubusercontent.com/php-school/workshop-manager/master/app/workshops.json',
                new RemoteFilesystem(new NullIo)
            )
        );
    }),
    InstalledWorkshopRepository::class => \DI\factory(function (ContainerInterface $c) {
        return new InstalledWorkshopRepository($c->get('stateFile'));
    }),
    'appDir' => sprintf('%s/.php-school', getenv('HOME')),
    'stateFile' => function (ContainerInterface $c) {
        $stateFile = new JsonFile(sprintf('%s/installed.json', $c->get('appDir')));

        if (!$stateFile->exists()) {
            $stateFile->write(['workshops' => []]);
        }

        return $stateFile;
    },
    ManagerState::class => \DI\factory(function (ContainerInterface $c) {
        return new ManagerState(
            $c->get(Filesystem::class),
            $c->get('stateFile')
        );
    }),
    Filesystem::class => \DI\factory(function (ContainerInterface $c) {
        return new Filesystem;
    }),
];
