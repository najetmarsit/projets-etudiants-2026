# Medical API Project - TODO List

## Phase 1: Project Setup
- [x] Create Laravel project
- [x] Install JWT Auth package
- [ ] Configure PostgreSQL database in .env
- [ ] Configure JWT secret
- [ ] Publish JWT config

## Phase 2: Models and Migrations
- [ ] Create User model with roles (Doctor/Patient/Admin)
- [ ] Create Patient model
- [ ] Create Operation model
- [ ] Create HealthIndicator model
- [ ] Create Alert model
- [ ] Create Message model
- [ ] Create Report model
- [ ] Run migrations

## Phase 3: Authentication
- [ ] Configure JWT Auth
- [ ] Create AuthController with login/logout/me
- [ ] Add authentication routes
- [ ] Test authentication endpoints

## Phase 4: API Controllers
- [ ] Create UserController (Admin CRUD)
- [ ] Create PatientController (CRUD)
- [ ] Create OperationController (CRUD)
- [ ] Create HealthIndicatorController
- [ ] Create AlertController
- [ ] Create MessageController
- [ ] Create ReportController

## Phase 5: Services and Business Logic
- [ ] Implement alert generation logic (thresholds)
- [ ] Add validation rules
- [ ] Implement role-based middleware

## Phase 6: Testing and Security
- [ ] Test all APIs with Postman
- [ ] Ensure JSON responses
- [ ] Verify JWT security
- [ ] Add request validation
- [ ] Final security review
