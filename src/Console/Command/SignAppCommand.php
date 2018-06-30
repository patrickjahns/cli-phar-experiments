<?php

/**
 * @author Patrick Jahns <github@patrickjahns.de>
 *
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
 *
 */

namespace Cliph\Console\Command;

use phpseclib\Crypt\RSA;
use phpseclib\File\X509;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class SignAppCommand extends Command
{


	protected function configure() {
		$this
			->setName('integrity:sign-app')
			->setDescription('Signs an app using a private key.')
			->addArgument('path', InputArgument::REQUIRED, 'Application to sign.')
			->addArgument('privateKey',InputArgument::REQUIRED, 'Path to private key to use for signing.')
			->addArgument('certificate', InputArgument::REQUIRED, 'Path to certificate to use for signing.');
	}

	public function execute(InputInterface $input, OutputInterface $output)
	{
		$io = new SymfonyStyle($input, $output);
		$appPath = $input->getArgument('path');
		$privateKeyPath = $input->getArgument('privateKey');
		$keyBundlePath = $input->getArgument('certificate');


		$fileSystem = new Filesystem();
		if (!$fileSystem->exists([$appPath, $privateKeyPath, $keyBundlePath])) {
			$io->error('not accessible');
			return 1;
		}

		$rsa = new RSA();
		$rsa->loadKey(file_get_contents($privateKeyPath));
		$x509 = new X509();
		$x509->loadX509(file_get_contents($keyBundlePath));
		$x509->setPrivateKey($rsa);

		$hashes = $this->createHashes($appPath);
		$signature = $this->createSignatureData($hashes, $x509, $rsa);
		$fileSystem->dumpFile($appPath.'/appinfo/signature.json', json_encode($signature, JSON_PRETTY_PRINT));
		return 0;
	}

	private function createHashes($appPath): array
	{
		$finder = new Finder();
		$finder->in($appPath)->files();
		$hashes = [];
		foreach ($finder as $file) {
			if ($file->getRelativePathname() === 'appinfo/signature.json') {
				continue;
			}
			$hashes[$file->getRelativePathname()] = hash_file('sha512', $file->getPathname());
		}
		return $hashes;
	}

	/**
	 * Creates the signature data
	 *
	 * @param array $hashes
	 * @param X509 $certificate
	 * @param RSA $privateKey
	 * @return array
	 */
	private function createSignatureData(array $hashes,
										 X509 $certificate,
										 RSA $privateKey) {
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
}
