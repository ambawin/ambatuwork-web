Add this section to `PLAN.md`, preferably after **Current Technical Context** or after **MVP Scope**.

````md
## 3. Platform Scope

The app will support two platforms:

1. Web app
2. Mobile app

Both platforms will use the same Laravel backend API.

The backend should be treated as the single source of truth for:

- Authentication
- Users
- Projects
- Memberships
- Invitations
- Backlog items
- Sprints
- Daily check-ins
- Impediments/blockers
- Comments
- Sprint reviews
- Retrospectives
- Peer reviews
- Metrics

The web and mobile apps should not have separate business logic. They should consume the same API and follow the same authorization rules.

---

## 3.1 Web Platform

The web app is the main workspace for heavier project and Scrum management.

The web app should be optimized for:

- Project creation
- Project settings
- Inviting members and supervisors
- Backlog management
- Sprint planning
- Sprint board management
- Sprint Review
- Retrospective
- Peer review summary
- Supervisor observation
- Project metrics

The web platform is better suited for work that needs more screen space, such as backlog prioritization, Sprint planning, and reviewing team progress.

Recommended web screens:

- Login with Google
- Project list
- Project dashboard
- Project settings
- Member management
- Backlog
- Sprint planning
- Sprint board
- Daily check-ins
- Impediments
- Sprint Review
- Retrospective
- Peer Review
- Metrics dashboard

---

## 3.2 Mobile Platform

The mobile app is the lightweight companion for daily Scrum activity.

The mobile app should be optimized for quick updates and team awareness.

The mobile app should support:

- Login with Google
- Viewing projects
- Viewing current Sprint
- Viewing Sprint Goal
- Viewing assigned work
- Updating work status
- Submitting daily check-ins
- Reporting blockers
- Commenting
- Viewing supervisor comments
- Submitting peer reviews
- Viewing basic Sprint progress

The mobile platform should make it easy for users to participate every day without opening the full web app.

Recommended mobile screens:

- Login with Google
- Project list
- Current Sprint dashboard
- My work
- Sprint board
- Daily check-in form
- Blocker report form
- Comments
- Peer review form
- Notifications

---

## 3.3 Platform Responsibility Split

| Feature | Web | Mobile |
|---|---:|---:|
| Google login | Yes | Yes |
| View projects | Yes | Yes |
| Create project | Yes | Optional for MVP |
| Edit project settings | Yes | No |
| Invite users | Yes | Optional |
| Manage members | Yes | No |
| View backlog | Yes | Yes |
| Create backlog item | Yes | Optional |
| Prioritize backlog | Yes | No |
| Create Sprint | Yes | No |
| Start/close Sprint | Yes | No |
| View Sprint board | Yes | Yes |
| Move work status | Yes | Yes |
| Submit daily check-in | Yes | Yes |
| Report blocker | Yes | Yes |
| Comment | Yes | Yes |
| Sprint Review | Yes | View only or limited |
| Retrospective | Yes | Yes |
| Peer review | Yes | Yes |
| Metrics dashboard | Yes | Limited |
| Supervisor observation | Yes | Yes |

For MVP, the web app should handle the complete workflow.  
The mobile app should focus on daily execution, updates, comments, blockers, and peer review.

---

## 3.4 API-First Architecture

Because the app has both web and mobile platforms, the backend should be designed API-first.

Laravel should expose REST API endpoints consumed by both clients.

Recommended structure:

```txt
Laravel Backend API
    ├── Web Frontend
    └── Mobile App
````

The API should handle:

* Authentication state
* Authorization
* Validation
* Scrum workflow rules
* Role permissions
* Project data
* Sprint data
* Review and retrospective data

The frontend clients should only handle presentation and user interaction.

Business rules should stay in Laravel services and policies.

---

## 3.5 Authentication Across Platforms

The app currently uses Laravel Sanctum with Google Authentication.

For the MVP:

### Web App

The web app can use Sanctum SPA authentication.

Recommended behavior:

* User clicks "Continue with Google"
* Laravel redirects to Google OAuth
* Google returns authenticated user
* Laravel creates or updates the user
* Laravel creates authenticated session/token
* User accesses protected API routes

### Mobile App

The mobile app should also authenticate with Google, but the flow may be different from the web flow.

Recommended behavior:

* User signs in with Google on mobile
* Mobile app receives Google identity token
* Mobile app sends token to Laravel API
* Laravel verifies the Google token
* Laravel creates or updates the user
* Laravel returns a Sanctum API token
* Mobile app stores the token securely
* Mobile app uses the token for API requests

Mobile token storage should use secure storage, not normal local storage.

Examples:

* iOS Keychain
* Android Keystore
* Expo SecureStore
* React Native Keychain

---

## 3.6 Shared Authorization Rules

Both platforms must follow the same permission rules.

Authorization should not be duplicated separately in the web app and mobile app.

Laravel Policies should enforce:

* Who can view a project
* Who can invite users
* Who can create backlog items
* Who can update work
* Who can start or close a Sprint
* Who can submit peer reviews
* What supervisors are allowed to do

The frontend can hide unavailable buttons for better UX, but the backend must still enforce every rule.

Example:

* The mobile app may hide the "Move to Done" button for supervisors.
* The Laravel API must still reject the request if a supervisor tries to call the endpoint directly.

---

## 3.7 Notifications

Because the app has mobile and web platforms, notifications should be considered early.

MVP notification types:

* Project invitation received
* Sprint started
* Daily check-in reminder
* User was mentioned in a comment
* Blocker was created
* Blocker was resolved
* Sprint Review is ready
* Retrospective is ready
* Peer review is open
* Peer review deadline is near

For the first MVP version, notifications can be implemented as in-app notifications.

Later, notifications can expand to:

* Email notifications
* Mobile push notifications
* Browser push notifications

Recommended table:

```txt
notifications
```

Suggested columns:

```php
$table->uuid('id')->primary();
$table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
$table->foreignUuid('project_id')->nullable()->constrained()->cascadeOnDelete();
$table->string('type');
$table->string('title');
$table->text('body')->nullable();
$table->string('entity_type')->nullable();
$table->uuid('entity_id')->nullable();
$table->timestamp('read_at')->nullable();
$table->json('metadata')->nullable();
$table->timestamps();
```

---

## 3.8 Mobile MVP Priority

The mobile MVP should not try to replicate the full web app.

Mobile should focus on frequent, lightweight Scrum actions:

1. View active Sprint
2. View Sprint Goal
3. View assigned work
4. Move work status
5. Submit daily check-in
6. Report blocker
7. Comment
8. Submit peer review
9. Receive notifications

This keeps the mobile app simple and useful.

---

## 3.9 Web MVP Priority

The web MVP should provide the complete project management workflow:

1. Create project
2. Manage members
3. Invite supervisors
4. Create and prioritize backlog
5. Plan Sprint
6. Start Sprint
7. Manage Sprint board
8. Run Sprint Review
9. Run Retrospective
10. View peer review summary
11. View metrics

The web app is the primary control center for the project.
The mobile app is the daily collaboration companion.

````

Also update the existing **Current Technical Context** section to this:

```md
## 2. Current Technical Context

The application is built with:

- Laravel backend
- Laravel Sanctum
- Google Authentication
- API-based architecture
- Web frontend
- Mobile app
- Shared backend API for both platforms

Authentication is already handled through Google login.

The MVP should build on top of the existing `users` table and Sanctum authentication system.

Because the product will support both web and mobile platforms, the Laravel backend should be designed API-first. All core business logic, authorization, validation, and Scrum workflow rules should live in the backend, not separately inside each frontend client.
````
