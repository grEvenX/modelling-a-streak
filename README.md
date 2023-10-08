# About this repository

This repository was created to provide a bootstrap in PHP/Laravel
as a starting point to solve the challenge described in my blog post 
[Modelling a Streak feature](https://bootstrapped.tech/modelling-a-streak-feature).


## Default configuration
You can copy the provided `.env.example` to `.env` to use default configuration.

The default configuration is configured to use SQLite as storage, but feel free to change it to support PostgreSQL 15.x if you want to get fancy with things like
recursive queries and meet the requirement to support PostgreSQL 15.x.
In that case you will need to update the `phpunit.xml` and might also need to change the test setup. 

## Where to start and Testing the feature
This repository contains a feature test for the HIT Streaks in `tests/Feature/HitStreakTest.php`.
This is a good starting point to start developing and to see if you are meeting the requirements.  
All the tests in the feature test are tested through use cases in `app/UseCase`,
but all the "read-side" use cases requires that the "write-side" `UpdateHitsForWeek` to be implemented first,
so implementing this use-case and how the data is stored might be a good first step.

The test configuration in `phpunit.xml` overrides has some `.env` variables,
the important ones being that it configures the tests to use a sqlite in-memory database to make it
easy and quick to run the tests fasts.

## Learning Laravel

If you came here and haven't used Laravel before, you can learn more about it in the
[documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, 
making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application 
from scratch.

## License

This code is licensed under the [MIT license](https://opensource.org/licenses/MIT).
