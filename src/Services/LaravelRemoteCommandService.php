<?php

namespace LaravelRemoteCommands\Services;

use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\PublicKeyLoader;
use Exception;

class LaravelRemoteCommandService
{
    protected SSH2 $ssh;

    protected ?string $prompt = null;

    protected string $promptRegex = '/^\[[\w.@-]+@[^\s\]]+ [^\]]+\][#$]\s?$/';

    /**
     * Connects and logs in to the specified remote server.
     *
     * @param string $server Config key of server to connect
     * @return $this
     * @throws Exception If login fails or config missing
     */
    public function into(string $server): self
    {
        $config = config("remote.servers.$server");
        if (!$config) {
            throw new Exception("No configuration found for server [$server]");
        }

        $this->ssh = new SSH2($config['host'], $config['port'] ?? 22);

        $key = $config['auth_type'] === 'password'
            ? $config['password']
            : PublicKeyLoader::load(file_get_contents($config['public_key']));

        if (!$this->ssh->login($config['username'], $key)) {
            throw new Exception("Login failed for server [$server]");
        }

        return $this;
    }

    /**
     * Runs an array of commands on the remote server, streaming output lines via callback.
     *
     * @param string[] $commands
     * @param callable|null $callback Receives each output line as string
     * @return void
     * @throws Exception On SSH read errors or prompt detection timeout
     */
    public function run(array $commands, ?callable $callback = null): void
    {
        $this->ssh->enablePTY();
        $this->ssh->setTimeout(10);

        if ($this->prompt === null) {
            $this->prompt = $this->detectPrompt();
            $this->promptRegex = $this->promptToRegex($this->prompt);
        }

        foreach ($commands as $cmd) {
            $this->ssh->write($cmd . "\n");

            $output = $this->ssh->read($this->promptRegex);
            if ($output === false) {
                throw new Exception("SSH read failed while running command: $cmd");
            }

            $lines = preg_split('/\r?\n/', trim($output));
            if (empty($lines)) {
                continue;
            }

            $lastLine = end($lines);
            if (preg_match($this->promptRegex, $lastLine)) {
                array_pop($lines);
            }

            foreach ($lines as $line) {
                $line = trim($line);
                if ($callback !== null && $line !== '') {
                    $callback($line);
                }
            }
        }

        $this->ssh->disconnect();
    }

    /**
     * Builds a regex pattern to match the dynamic shell prompt.
     *
     * @param string $promptLine
     * @return string Regex pattern with delimiters
     */
    protected function promptToRegex(string $promptLine): string
    {
        if (!preg_match('/^\[(.+?) (.+?)\]([#$])\s*$/', $promptLine, $matches)) {
            // fallback: escape entire prompt literally
            return '/' . preg_quote($promptLine, '/') . '/';
        }

        $userHost = preg_quote($matches[1], '/');
        $folder = '[^\\]]+'; // match any folder name except closing bracket
        $ending = preg_quote($matches[3], '/'); // $ or #

        return '/^\[' . $userHost . ' ' . $folder . '\]' . $ending . '\s?$/';
    }

    /**
     * Detects the shell prompt by reading until a prompt line is found.
     *
     * @return string The detected prompt line
     * @throws Exception If timeout occurs or read fails
     */
    protected function detectPrompt(): string
    {
        $this->ssh->write("\n");

        $timeout = 10; // seconds
        $start = time();

        while (true) {
            $output = $this->ssh->read();
            if ($output === false) {
                throw new Exception("SSH read failed while detecting prompt");
            }

            $lines = preg_split('/\r?\n/', trim($output));

            // Look for a line that ends with ]$ or ]#
            foreach (array_reverse($lines) as $line) {
                if (preg_match('/\][#$]\s*$/', $line)) {
                    return $line;
                }
            }

            // Fallback: return last non-empty line if no prompt detected yet
            foreach (array_reverse($lines) as $line) {
                if (trim($line) !== '') {
                    return $line;
                }
            }

            if ((time() - $start) > $timeout) {
                throw new Exception("Timeout waiting for prompt");
            }
        }
    }
}
