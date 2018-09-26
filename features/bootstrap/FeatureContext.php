<?php

declare(strict_types=1);

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use PHPUnit\Framework\Assert;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context
{
    /** @var Process */
    protected $process;

    protected $phpBin;

    protected $phar = 'build/oct.phar';

    public function __construct()
    {
    }

    /**
     * Prepares test folders in the temporary directory.
     *
     * @BeforeScenario
     */
    public function prepareTest(): void
    {
        $phpFinder = new PhpExecutableFinder();
        $this->phpBin = $phpFinder->find();
        $this->process = new Process(null);
        $this->process->setTimeout(20);
    }

    /**
     * @When I run the command :arg1
     *
     * @param mixed $command
     */
    public function iRunTheCommand($command): void
    {
        $this->process->setWorkingDirectory(__DIR__.'/../../');
        $this->process->setCommandLine(
            sprintf(
                '%s %s %s',
                $this->phpBin,
                $this->phar,
                $command
            )
        );
        $this->process->run();
    }

    /**
     * @Then the command output should contain:
     *
     * @param PyStringNode $text PyString text instance
     */
    public function theCommandOutputShouldBe(PyStringNode $text): void
    {
        Assert::assertContains($text->getRaw(), $this->getOutput());
    }

    private function getOutput()
    {
        $output = $this->process->getErrorOutput().$this->process->getOutput();
        // Normalize the line endings in the output
        if ("\n" !== PHP_EOL) {
            $output = str_replace(PHP_EOL, "\n", $output);
        }

        return trim(preg_replace('/ +$/m', '', $output));
    }

    private function getExitCode()
    {
        return $this->process->getExitCode();
    }
}
