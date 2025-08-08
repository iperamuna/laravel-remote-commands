# Laravel Remote Commands

[![Latest Version on Packagist](https://img.shields.io/packagist/v/iperamuna/laravel-remote-commands.svg?style=flat-square)](https://packagist.org/packages/iperamuna/laravel-auto-translations)
[![Total Downloads](https://img.shields.io/packagist/dt/iperamuna/laravel-remote-commands.svg?style=flat-square)](https://packagist.org/packages/iperamuna/laravel-auto-translations)
[![License](https://img.shields.io/github/license/iperamuna/laravel-remote-commands?style=flat-square)](LICENSE)

✨ A lightweight Laravel package that enables executing commands on remote servers over SSH using phpseclib. Designed for Laravel developers who need a simple and secure way to run remote commands — no file transfers, just command execution.

---

## 📦 Installation

```bash
composer require iperamuna/laravel-remote-commands
```

## 📚 Configuration

Publish the config:

```bash
php artisan vendor:publish --tag=remote-commands-config
```


## 🛠️ Config

```bash
return [
    'servers' => [
        'server_name' => [
            'host' => '',
            'port' => 22,
            'username' => '',
            'auth_type' => 'password',//publickey, password
            'password' => '',
            'key' => '' // publicKey Path
        ],
    ],
];
```

## 🌐 Usage

```bash
use LaravelRemoteCommands\Facades\RemoteCommand;

RemoteCommand::into('server_name')->run([
        'pwd',
        'ls -l',
        'pwd',
    ], function ($line) use ($logFile) {
        $this->info($line);
    });
```

---

## 📜 License

This package is open-source software licensed under the [MIT license](LICENSE).

---

## 🙋‍♀️ Contributing

Pull requests are welcome! For major changes, please open an issue first to discuss what you would like to change.

---

## 📣 Credits

Made with ❤️ by [Indunil Peramuna](https://github.com/iperamuna)  
Built for Laravel developers who want to run commands on remote servers.
