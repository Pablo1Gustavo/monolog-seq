# Monolog Seq

<p align="center">
    <img
        src="https://upload.wikimedia.org/wikipedia/commons/thumb/3/31/Webysther_20160423_-_Elephpant.svg/2560px-Webysther_20160423_-_Elephpant.svg.png"
        width="200"
    />
    <span style="font-size: 80px; font-weight: bold;">+</span>
    <img
        src="https://blog.getseq.net/content/images/2018/09/Seq-Main-Light-400px.png"
        width="200"
        style="margin-left: 20px;"
    />
</p>

Integrates PHP Monolog with Seq using HTTP ingestion, enabling structured event logging to a centralized Seq server for enhanced log management.

## Installation

Install the latest version with

```bash
```

## Usage

This package **automatically sets** the [CLEF](https://clef-json.org/) request keys, including **@t**, **@m**, **@mt**, **@l**, and **@x**, ensuring seamless structured logging.

For more detailed usage instructions, refer to the [official Seq documentation](https://docs.datalust.co/docs/posting-raw-events).

### Usage (Vanilla PHP)

You can find an example in [`example.php`](example.php).

### Usage (Laravel)

Laravel allows you to configure **custom Monolog handlers**, such as this package, within its logging configuration file.

For step-by-step instructions, refer to the [Laravel Logging - Creating Monolog Handler Channels](https://laravel.com/docs/12.x/logging#creating-monolog-handler-channels) documentation.