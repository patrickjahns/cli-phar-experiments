<?php

declare(strict_types=1);

/**
 * @author Patrick Jahns <github@patrickjahns.de>
 * @copyright Copyright (c) 2018, Patrick Jahns.
 * @license GPL-2.0
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

namespace OC\Tekton\Console\Command;

use OC\Tekton\Console\Configuration\TektonConfiguration;
use phpseclib\Crypt\RSA;
use phpseclib\File\X509;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

class BuildCommand extends Command
{
    /** @var Filesystem */
    private $fileSystem;

    public function __construct(string $name = null)
    {
        parent::__construct($name);
        $this->fileSystem = new Filesystem();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $appPath = $input->getArgument('path');
        $privateKeyPath = $input->getArgument('privateKey');
        $keyBundlePath = $input->getArgument('certificate');
        if (!$this->fileSystem->exists([$appPath, $privateKeyPath, $keyBundlePath])) {
            $io->error('not accessible');

            return 1;
        }
        $configurationFile = $appPath.'/tekton.yml';
        if (!$this->fileSystem->exists($configurationFile)) {
            $io->error('tekton configuration not found');

            return 1;
        }

        $configuration = $this->processConfiguration($configurationFile);
        $io->section('built tasks');
        $this->executePreCommands($io, $appPath, $configuration['build_tasks']);

        $tmpPath = '/tmp/'.basename($appPath);
        $io->section('prepare packaging');
        $this->mirror($appPath, $tmpPath, $configuration['package']);

        $io->section('creating app signature');
        $this->generateSignature($tmpPath, $privateKeyPath, $keyBundlePath);

        $io->section('creating archive');
        $this->generateArchive($tmpPath, $appPath);

        $io->section('cleanup');
        $this->fileSystem->remove($tmpPath);
    }

    protected function configure(): void
    {
        $this->setName('build')
            ->setDescription('build an owncloud app')
            ->addArgument('path', InputArgument::REQUIRED, 'path to app being built')
            ->addArgument('privateKey', InputArgument::REQUIRED, 'Path to private key to use for signing.')
            ->addArgument('certificate', InputArgument::REQUIRED, 'Path to certificate to use for signing.');
    }

    private function mirror($appPath, $tmpPath, $configuration): void
    {
        $finder = new Finder();
        $finder->in($appPath)->ignoreVCS(true);
        foreach ($configuration['exclude'] as $exclude) {
            $finder->exclude($exclude);
        }
        $options = [];
        if (true === $configuration['flatten']) {
            $options['copy_on_windows'] = true;
        }
        $this->fileSystem->remove($tmpPath);
        $this->fileSystem->mirror($appPath, $tmpPath, $finder, $options);
    }

    private function generateArchive($tmpPath, $appPath): void
    {
        $finder = new Finder();
        $finder->in($tmpPath);
        $targetFile = $appPath.'/'.basename($appPath).'.tar.gz';
        $archive = new \Archive_Tar($targetFile, 'gz');
        $archive->createModify([$tmpPath], '', dirname($tmpPath));
    }

    private function generateSignature($tmpPath, $privateKeyPath, $keyBundlePath): void
    {
        $rsa = new RSA();
        $rsa->loadKey(file_get_contents($privateKeyPath));
        $x509 = new X509();
        $x509->loadX509(file_get_contents($keyBundlePath));
        $x509->setPrivateKey($rsa);

        $hashes = $this->createHashes($tmpPath);
        $signature = $this->createSignatureData($hashes, $x509, $rsa);
        $this->fileSystem->dumpFile($tmpPath.'/appinfo/signature.json', json_encode($signature, JSON_PRETTY_PRINT));
    }

    private function createHashes($appPath): array
    {
        $finder = new Finder();
        $finder->in($appPath)->files();
        $hashes = [];
        foreach ($finder as $file) {
            if ('appinfo/signature.json' === $file->getRelativePathname()) {
                continue;
            }
            $hashes[$file->getRelativePathname()] = hash_file('sha512', $file->getPathname());
        }

        return $hashes;
    }

    /**
     * Creates the signature data.
     *
     * @param array $hashes
     * @param X509  $certificate
     * @param RSA   $privateKey
     *
     * @return array
     */
    private function createSignatureData(
        array $hashes,
                                         X509 $certificate,
                                         RSA $privateKey
    ) {
        ksort($hashes);

        $privateKey->setSignatureMode(RSA::SIGNATURE_PSS);
        $privateKey->setMGFHash('sha512');
        $privateKey->setSaltLength(0);
        $signature = $privateKey->sign(json_encode($hashes));

        return [
            'hashes' => $hashes,
            'signature' => base64_encode($signature),
            'certificate' => $certificate->saveX509($certificate->currentCert),
        ];
    }

    private function processConfiguration($configurationFile)
    {
        $yamlContent = Yaml::parse(file_get_contents($configurationFile));
        $configurationProcessor = new Processor();
        $tektonConfiguration = new TektonConfiguration();

        return $configurationProcessor->processConfiguration($tektonConfiguration, [$yamlContent]);
    }

    private function executePreCommands(SymfonyStyle $io, $appPath, $commands): void
    {
        foreach ($commands as $command) {
            $io->writeln('Executing "'.$command.'"');
            $io->writeln('----------------------------------');
            $cmd = new Process($command);
            $cmd->setWorkingDirectory($appPath);
            $cmd->setTimeout(600);
            $cmd->run(function ($type, $buffer) use ($io): void {
                if (Process::ERR === $type) {
                    $io->warning('ERR > '.$buffer);
                } else {
                    $io->writeln('OUT > '.$buffer);
                }
            });
            if (!$cmd->isSuccessful()) {
                throw new ProcessFailedException($cmd);
            }
            $io->writeln('----------------------------------');
        }
    }
}
