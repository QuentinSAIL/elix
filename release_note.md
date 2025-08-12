# v1.0.0 — 2025-08-12

## Changes
* chore(release): bump version to v1.0.0 (11bf1d0) – Quentin SAILLARD
* feat: update workflow trigger to release published events and add Makefile for release management (c694424) – Quentin SAILLARD
* fix: test and code quality (14b949d) – Quentin SAILLARD
* Merge pull request #26 from QuentinSAIL/code-quality (eef4f16) – Quentin SAILLARD
* fix: change parameter type of hasApiKey method (c6ece1b) – Quentin SAILLARD
* feat: Introduced Larastan for static analysis and improved code quality checks. (f28164f) – Quentin SAILLARD
* fix: lint project (a9e4ae8) – Quentin SAILLARD
* Merge pull request #25 from QuentinSAIL/test (080d241) – Quentin SAILLARD
* test: Add feature tests for Livewire components and model functionalities (59ecfa0) – Quentin SAILLARD
* refactor: improve layout and styling of bank account display (0b0d9fa) – Quentin SAILLARD
* fix: handle potential null value for newKeywords in deletedKeywords calculation (c179a54) – Quentin SAILLARD
* feat: add initial README with installation instructions and project structure (7e27aa5) – Quentin SAILLARD
* feat: implement keyword collision detection and validation in category matches (3b4a102) – Quentin SAILLARD
* feat: initialize accountsId and categoriesId and bind to select components (c4cd1ca) – Quentin SAILLARD
* :bug: fix routine calcul of the percent (9adeb60) – Quentin SAILLARD
* Merge pull request #23 from QuentinSAIL/feat/transaction-dashboard (4716e81) – Quentin SAILLARD
* REFACTO: create atom select (6c2e9de) – Quentin SAILLARD
* WIP: chart; can be edited and created (f9369e9) – Quentin SAILLARD
* WIP: transaction dashboard chart displayed (f9201e5) – Quentin SAILLARD
* WIP: transaction dashboard chart displayed (d5f6f93) – Quentin SAILLARD
* feat: close registration (1079940) – Quentin SAILLARD
* ci: update tests workflow for improved Postgres service configuration and clarity (1add10c) – Quentin SAILLARD
* feat: setup test (5877fa1) – Quentin SAILLARD
* WIP: dashboard transaction (9122572) – Quentin SAILLARD
* UI: improve routine show, with congrat at the end and resume at the start (fba7b19) – Quentin SAILLARD
* Refactor app logo and update branding (6333c74) – Quentin SAILLARD
* UI: Refactor UI components and improve styling across various views (e081c04) – Quentin SAILLARD
* Merge pull request #7 from QuentinSAIL/feat/money (1dd0802) – Quentin SAILLARD
* feat: add registration error handling; display error message when registration is closed (b1c51ac) – Quentin SAILLARD
* feat: implement module management; add UserModule middleware, create Module model and migration, and update user permissions for module access (f37a761) – Quentin SAILLARD
* Merge pull request #6 from QuentinSAIL/feat/money (c620fc6) – Quentin SAILLARD
* feat: enhance category management; update category matching logic, improve UI for category forms, and add functionality to apply matches to existing transactions (6ef05e8) – Quentin SAILLARD
* feat: add category table crud, and categories matches crud (8d1e709) – Quentin SAILLARD
* feat: add logo support to bank accounts; update models, services, and views to handle logo data (1cdd055) – Quentin SAILLARD
* Merge pull request #5 from QuentinSAIL/feat/money (fb6b83b) – Quentin SAILLARD
* feat: implement middleware to ensure valid GoCardless API keys for user access; add error handling and redirect to API key settings (9cd72c1) – Quentin SAILLARD
* Merge pull request #4 from QuentinSAIL/feat/money (5b0b7ba) – Quentin SAILLARD
* feat: enhance GoCardless API key management; add validation for credentials, implement deletion of API keys, and improve UI for better user experience (4cc3558) – Quentin SAILLARD
* Merge pull request #3 from QuentinSAIL/feat/money (98f1a32) – Quentin SAILLARD
* feat: add inpt form to set api key for extarnal services (aa40ab5) – Quentin SAILLARD
* feat: add new bank account functionality; update GoCardless service methods, enhance bank account migration, and improve UI text for better user experience (8e65239) – Quentin SAILLARD
* feat: enhance bank account management; suppress requisition on gocardless; add new fields to the bank accounts table, improve user agreement handling, and update UI components for better user experience (efe3788) – Quentin SAILLARD
* WIP: setup a bank account with goCardLess (d47bdaa) – Quentin SAILLARD
* feat: enhance bank transaction management; add category matching and account selection features; update UI for better user experience (60a0b9d) – Quentin SAILLARD
* feat: implement bank account management with update, and delete functionalities; refactor transaction fetching and category matching (146f8b9) – Quentin SAILLARD
* feat: can now add categories to transactions in bulk (571049e) – Quentin SAILLARD
* feat: add table for the transactions (c049cdd) – Quentin SAILLARD
* feat: mvp expenses gestion; get data with gocardless; display on money/transac; categ bdd ready (c303e08) – Quentin SAILLARD
* refactor: remove unused properties and methods; streamline note saving process (899e691) – Quentin SAILLARD
* fix: unused text (934fa2d) – Quentin SAILLARD
* ui: improve UI UX for task routine; adding toast on startstop actions; addmvp live saving fo rthe note (7515725) – Quentin SAILLARD
* feat: add task completion handling and reset form functionality; enhance UI for routine timer display (abf72be) – Quentin SAILLARD
* ui: improve UI UX for task routine. (e883ece) – Quentin SAILLARD
* feat: and improve form handling in Livewire components (4d67b28) – Quentin SAILLARD
* language: add new translation (af6a218) – Quentin SAILLARD
* Merge pull request #2 from QuentinSAIL/feat/routine (c41bd5d) – Quentin SAILLARD
* feat: enhance routine task management with delete and duplicate functionalities, and update UI for task forms (b2ebdc1) – Quentin SAILLARD
* feat: implement task ordering functionality with drag-and-drop support (4b6cf15) – Quentin SAILLARD
* fix: height index routine, order routinetask seeder, hour in timer (ab76724) – Quentin SAILLARD
* feat: add play/pause and stop functionality with notifications for routine tasks (54777ff) – Quentin SAILLARD
* fix: adding wire:ignore and keys to keep the livewire.routine.form displaying, and get the good index in the routines arr (319d9c5) – Quentin SAILLARD
* feat: implement task timer functionality and update UI for routine tasks (a1fce8c) – Quentin SAILLARD
* UI: display selected routine's tasks (320570c) – Quentin SAILLARD
* feat: refacto routine creation form; add routine edition feature (a254475) – Quentin SAILLARD
* WIP: on change debounce save notes (e35945e) – Quentin SAILLARD
* feat: mvp language switcher (bd8f8ea) – Quentin SAILLARD
* UI: Update navigation links and route definitions; add elix yellow logo (333958f) – Quentin SAILLARD
* Merge pull request #1 from QuentinSAIL/feat/notes (7731b1f) – Quentin SAILLARD
* feat: enhance note selection and deletion logic; improve UI feedback for actions (ed8a5c2) – Quentin SAILLARD
* feat: notes: support markdown, handle creation and update in front, added better UI UX (cab43ef) – Quentin SAILLARD
* feat: implement Note management with creation, deletion, and UI integration; add migration and model (1b460b1) – Quentin SAILLARD
* Update web.php (1145731) – Quentin SAILLARD
* feat: initialize Alpine.js and integrate livewire-toaster for enhanced UI interactions (e998fff) – Quentin SAILLARD
* feat: enhance Routine and Frequency management with new modal for creating routines, improved validation, and updated UI components (98d728d) – Quentin SAILLARD
* chore: comment out branch triggers for push events in workflow configuration (99556a9) – Quentin SAILLARD
* feat: implement Routine and Frequency management with creation, deletion, and modal integration; add toast notifications (892b71d) – Quentin SAILLARD
* feat: enhance Routine management with delete functionality and mvp modal for creating routines (69bb5b4) – Quentin SAILLARD
* feat: create livewire index view, add relationships to user and routine (7fc8cd0) – Quentin SAILLARD
* feat: implement Routine management with models, factories, migrations, and Livewire component (3528bab) – Quentin SAILLARD
* feat: add models and migrations for Frequency, Routine, and RoutineTask; update User model to use UUIDs (6a9ba8c) – Quentin SAILLARD
* :hammer: update deployment script path in GitHub Actions workflow (c8dbf55) – Quentin SAILLARD
* :rocket: add Dockerfile for PHP 8.3 environment setup (344356e) – Quentin SAILLARD
