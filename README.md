# GitHub AI Reviewer

> AI-assisted GitHub repository health analyzer built with Laravel.

[![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/)
[![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Status](https://img.shields.io/badge/status-in%20development-yellow)](#project-status)

## Overview

GitHub AI Reviewer is a web application that analyzes public GitHub
repositories and produces an explainable repository health report.

The application combines deterministic, rule-based checks with
AI-assisted review. Objective repository characteristics are evaluated
by the application's analysis engine, while AI is used primarily to
explain findings and provide actionable recommendations.

The project is currently under active development.

## Planned MVP Features

-   Analyze public GitHub repositories from a repository URL
-   Retrieve repository metadata through the GitHub REST API
-   Analyze documentation quality
-   Detect testing-related indicators
-   Evaluate project structure
-   Perform basic repository security hygiene checks
-   Evaluate Git practices
-   Perform basic code-quality analysis
-   Generate explainable category scores
-   Generate an overall repository health score
-   Provide AI-assisted findings and recommendations
-   Store analysis history
-   Display findings by severity and source

## Repository Health Categories

The MVP evaluates six categories:

  Category              Weight
  ------------------- --------
  Documentation            20%
  Testing                  20%
  Security                 20%
  Project Structure        15%
  Code Quality             15%
  Git Practices            10%

The scoring model is intended as an explainable repository-health
heuristic, not an absolute measurement of developer ability or software
quality.

## Tech Stack

  Component                Technology
  ------------------------ -----------------------------------------
  Backend                  Laravel 13
  Language                 PHP 8.3+
  Frontend                 Blade + Tailwind CSS
  Development Database     SQLite
  Repository Integration   GitHub REST API
  AI Integration           API-based LLM with provider abstraction
  Version Control          Git + GitHub

SQLite is used for local development. The production database and AI
provider will be selected later based on deployment requirements.

## High-Level Architecture

``` text
User
  |
  v
Laravel Application
  |
  +---- GitHub REST API
  |
  +---- Repository Analysis Engine
  |       |
  |       +---- Documentation Analyzer
  |       +---- Testing Analyzer
  |       +---- Security Analyzer
  |       +---- Project Structure Analyzer
  |       +---- Code Quality Analyzer
  |       +---- Git Practices Analyzer
  |
  +---- Scoring Engine
  |
  +---- AI Review Service
  |
  +---- Database
  |
  v
Repository Health Report
```

The MVP is designed as a modular Laravel monolith. More complex
infrastructure such as Redis, queues, dedicated workers, or separate
analysis services will only be introduced when justified by application
requirements.

## Documentation

Detailed engineering documentation is available in [`docs/`](docs/):

-   [Problem Definition](docs/01-PROBLEM.md)
-   [Research & Feasibility](docs/02-RESEARCH.md)
-   [Product Requirements Document](docs/03-PRD.md)
-   [System Architecture](docs/04-ARCHITECTURE.md)
-   [Database Design](docs/05-DATABASE.md)
-   [Scoring Specification](docs/06-SCORING.md)
-   [Development Roadmap](docs/07-ROADMAP.md)

## Project Status

  Phase                                  Status
  -------------------------------------- -------------
  Planning & Engineering Documentation   Completed
  Project Foundation                     Completed
  Repository Input & URL Validation      Completed
  GitHub Integration                     Planned
  Repository Analysis Engine             Planned
  Scoring Engine                         Planned
  AI Review                              Planned
  Report UI                              Planned
  Testing & Hardening                    Planned
  Public Deployment                      Planned

## MVP Scope

Version 1 focuses on analyzing **public GitHub repositories**.

The following features are intentionally outside the initial MVP:

-   Private repository analysis
-   GitHub OAuth
-   Pull Request review
-   Automatic code modification
-   Automatic Pull Request creation
-   Team collaboration
-   Organization-wide monitoring
-   IDE extensions

These capabilities may be considered in later versions.

## Security and AI Disclaimer

GitHub AI Reviewer is intended to provide repository-health guidance.

Security-related findings may indicate potential issues but **must not
be treated as a professional security audit or proof of a
vulnerability**.

AI-generated observations are probabilistic and will be distinguished
from deterministic findings wherever possible.

Repository content is treated as untrusted input and should never be
interpreted as instructions by the AI analysis pipeline.

## Local Development

The project is currently under development. Complete installation
instructions will be added as the application foundation stabilizes.

Current requirements include:

-   PHP 8.3+
-   Composer
-   Node.js
-   npm
-   SQLite

For the current Laravel development server:

``` bash
composer install
npm install
php artisan serve
```

Environment configuration and complete setup instructions will be
documented before the first public release.

## Roadmap

Development is organized into the following stages:

1.  Project foundation
2.  Repository input and URL validation
3.  GitHub REST API integration
4.  Deterministic repository analysis
5.  Explainable scoring engine
6.  Analysis persistence
7.  AI-assisted review
8.  Repository report interface
9.  Testing and security hardening
10. Portfolio preparation
11. Public deployment

See the full [Development Roadmap](docs/07-ROADMAP.md) for details.

## License

A project license will be selected before the first public release.
