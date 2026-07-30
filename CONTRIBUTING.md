# Contributing to Codebase LLM Assistant

Thank you for your interest in contributing to Codebase LLM Assistant! We welcome contributions from developers of all skill levels.

## How to Contribute

### 1. Reporting Bugs
- Search existing issues to ensure the bug hasn't already been reported.
- Open a new issue with a clear title, description, and steps to reproduce.

### 2. Feature Requests
- Open an issue describing the feature you'd like to see, why it would be useful, and how it might work.

### 3. Submitting Pull Requests
1. Fork the repository.
2. Create a new topic branch (`git checkout -b feature/my-new-feature`).
3. Write clean, readable code with tests where applicable.
4. Ensure tests and static checks pass:
   ```bash
   docker compose exec app php artisan test
   pnpm run typecheck
   pnpm run build
   ```
5. Commit your changes with clear, descriptive commit messages.
6. Push to your branch and open a Pull Request.

## Code Style & Standards

- **Backend (PHP/Laravel)**: Follow PSR-12 coding standards. Run `./vendor/bin/pint` to auto-format.
- **Frontend (Vue 3/TypeScript)**: Use PrimeVue components, Vue 3 Composition API with `<script setup lang="ts">`. Run `pnpm lint:fix`.

## License

By contributing, you agree that your contributions will be licensed under the MIT License.
