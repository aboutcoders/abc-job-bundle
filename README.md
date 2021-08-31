AbcJobBundle
============

A symfony bundle to process jobs asynchronously by simply annotating a method and registering the class within the service container.

Build Status: [![Build Status](https://travis-ci.org/aboutcoders/job-bundle.svg?branch=master)](https://travis-ci.org/aboutcoders/job-bundle)

## Features

This bundle provides the following features:

- Asynchronous execution of jobs
- Status information about jobs
- Functionality to cancel, update, restart a job
- Repeated execution of jobs with schedules (cron based expressions)
- JSON REST-Api
- Support for multiple message queue systems:
  - Doctrine DBAL
  - PhpAmqp / RabbitMQ
  - InMemory
  - Predis / PhpRedis
  - Amazon SQS
  - Iron MQ
  - Pheanstalk

## Documentation

- [Installation](./Resources/docs/installation.md)
- [Configuration](./Resources/docs/configuration.md)
- [Basic Usage](./Resources/docs/basic-usage.md)
- [Message Consuming](./Resources/docs/message-consuming.md)
- [Job Management](./Resources/docs/job-management.md)
- [Scheduled Jobs](./Resources/docs/scheduled-jobs.md)
- [Cancel Jobs](./Resources/docs/cancel-jobs.md)
- [Runtime Parameters](./Resources/docs/runtime-parameters.md)
- [Serialization](./Resources/docs/serialization.md)
- [Validation](./Resources/docs/validation.md)
- [Logging](./Resources/docs/logging.md)
- [Lifecycle Events](./Resources/docs/lifecycle-events.md)
- [Unit Testing](./Resources/docs/unit-testing.md)
- [REST-API](./Resources/docs/rest-api.md)
- [Process Control](./Resources/docs/process-control.md)
- [Clustered Environment](./Resources/docs/clustered-environment.md)
- [Configuration Reference](./Resources/docs/configuration-reference.md)

## Demo Project

Please take a look at [aboutcoders/job-bundle-skeleton-app](https://github.com/aboutcoders/job-bundle-skeleton-app) to see how the AbcJobBundle can be used within Symfony project.

## License

The MIT License (MIT). Please see [License File](./LICENSE) for more information.