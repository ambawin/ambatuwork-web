````md
# MVP Plan — Scrum-Based Collaboration App

## 1. Product Goal

Build a lightweight collaboration app that helps small teams practice Scrum principles in a simple, guided way.

The app is not meant to be a generic task board. The main purpose is to help users apply the ideas from *Scrum: The Art of Doing Twice the Work in Half the Time* through product behavior:

- Work is organized around short Sprint cycles.
- Every project has a clear goal.
- Every Sprint has a Sprint Goal.
- Work is prioritized by value.
- Team members inspect progress regularly.
- Blockers are made visible.
- Work is only marked done when it meets agreed quality criteria.
- Teams review completed work.
- Teams run retrospectives and peer reviews to improve collaboration.

---

## 2. Current Technical Context

The application is built with:

- Laravel
- Laravel Sanctum
- Google Authentication
- API-based backend
- Authenticated users using Sanctum tokens

Authentication is already handled through Google login. The MVP should build on top of the existing `users` table and Sanctum authentication system.

---

## 3. MVP Scope

The MVP has the following batasan:

1. A user can create a project.
2. A project works like an independent team/workspace.
3. Projects are independent from one another.
4. A project creator can invite other users.
5. Invited users can join the project.
6. Users inside a project can do work.
7. A project can have supervisors.
8. Supervisors can only observe and comment.
9. Supervisors cannot create, move, assign, or complete work.
10. Each project can run Sprints.
11. Each Sprint has backlog items, daily check-ins, review, retrospective, and peer review.
12. Peer review happens inside the team after a Sprint.

---

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


## 4. Core Roles

### Owner

The user who creates the project.

For the MVP, the owner acts as:

- Project creator
- Product Owner
- Lightweight Scrum facilitator

Permissions:

- Manage project settings
- Invite users
- Add/remove members
- Create and prioritize backlog items
- Create/start/close Sprints
- Do work like a normal member
- Comment
- View peer review summaries

---

### Member

A normal project contributor.

Permissions:

- View project
- Create backlog items
- Update assigned work
- Move work through the Sprint board
- Submit daily check-ins
- Report blockers
- Comment
- Submit peer reviews
- Participate in retrospective

---

### Supervisor

An observer/stakeholder.

Permissions:

- View project
- View backlog
- View Sprint board
- View Sprint review
- View retrospective summary
- Comment

Restrictions:

- Cannot create work
- Cannot edit work
- Cannot assign work
- Cannot move work
- Cannot mark work as done
- Cannot submit peer reviews
- Cannot change Sprint status
- Cannot manage members

---

## 5. Main App Flow

## 5.1 Login

Users authenticate using Google login.

After login, the user lands on the project dashboard.

---

## 5.2 Create Project

A user can create a new project.

Required fields:

- Project name
- Project goal
- Default Sprint length
- Definition of Done

Example:

```json
{
  "name": "Marketing Website Revamp",
  "product_goal": "Launch a clear and high-converting website.",
  "default_sprint_length_days": 14,
  "definition_of_done": [
    "Acceptance criteria are completed",
    "Reviewed by another team member",
    "No known critical bugs",
    "Demoable in Sprint Review"
  ]
}
````

After project creation:

* The creator becomes `owner`.
* A default Definition of Done is created.
* The project starts with an empty backlog.
* The user is guided to create the first backlog item.

---

## 5.3 Invite Users

The project owner can invite users by email.

Invite roles:

* `member`
* `supervisor`

Invitation flow:

1. Owner enters email address.
2. Owner selects role.
3. System creates invitation token.
4. Invitee receives invitation link.
5. Invitee logs in with Google.
6. Invitee accepts invitation.
7. System creates project membership.

---

## 5.4 Create Product Backlog

The project backlog contains all work that may be done.

Backlog items should encourage Scrum behavior by requiring:

* Title
* Description
* Business value
* Estimate points
* Acceptance criteria
* Priority order

Example backlog item:

```json
{
  "title": "Invite users by email",
  "description": "As a project owner, I want to invite users so they can collaborate in my project.",
  "type": "story",
  "business_value": 9,
  "estimate_points": 5,
  "acceptance_criteria": [
    "Owner can invite a user by email",
    "Invitee receives an invitation link",
    "Invitee can accept the invitation after Google login"
  ]
}
```

The backlog should be ordered by priority.

The app should encourage users to work on the most valuable items first.

---

## 5.5 Sprint Planning

Before work starts, the team creates a Sprint.

Required Sprint fields:

* Sprint name
* Sprint Goal
* Start date
* End date
* Selected backlog items

Sprint rules:

* A Sprint must have a Sprint Goal.
* A Sprint must have at least one backlog item.
* A Sprint should be short, usually 1–2 weeks for MVP.
* Only one Sprint can be active per project at a time.

Example:

```json
{
  "name": "Sprint 1",
  "sprint_goal": "Allow users to create projects and invite collaborators.",
  "start_date": "2026-05-01",
  "end_date": "2026-05-14",
  "backlog_item_ids": [
    "uuid-1",
    "uuid-2"
  ]
}
```

---

## 5.6 Sprint Board

The Sprint board shows work in progress.

MVP board columns:

* Selected
* In Progress
* In Review
* Done

The Sprint board should always show:

* Sprint Goal
* Sprint date range
* Current Sprint progress
* Open blockers
* Missing daily check-ins
* Work items grouped by status

Supervisors can view and comment but cannot move cards.

---

## 5.7 Daily Check-In

Each active member can submit one daily check-in per Sprint day.

Check-in fields:

* What did I complete?
* What will I work on next?
* What is blocking me?
* Confidence score

Example:

```json
{
  "yesterday": "Finished project creation form.",
  "today": "Work on invitation accept flow.",
  "blockers": "Need SMTP provider configuration.",
  "confidence_score": 3
}
```

If a user submits a blocker, the system should create or suggest creating an impediment.

---

## 5.8 Impediments / Blockers

Blockers should be visible to the whole team.

A blocker can be connected to:

* Project
* Sprint
* Backlog item

Blocker statuses:

* Open
* In Progress
* Resolved
* Ignored

The goal is to make problems visible early instead of hiding them until the end of the Sprint.

---

## 5.9 Mark Work as Done

A backlog item should not be marked as `done` casually.

Before marking an item as done, the user must confirm:

* Acceptance criteria are completed.
* Definition of Done is satisfied.
* Item was reviewed by another member, if required.

Example:

```json
{
  "acceptance_criteria_checked": true,
  "definition_of_done_checked": true,
  "notes": "Reviewed by another member and ready for demo."
}
```

This reinforces the Scrum idea that “Done” means truly done, not just worked on.

---

## 5.10 Sprint Review

At the end of a Sprint, the team reviews completed work.

Sprint Review includes:

* Completed backlog items
* Incomplete backlog items
* Demo notes
* Accepted items
* Rejected items
* Carry-over items
* Supervisor comments

Supervisor comments are allowed here because supervisors act like stakeholders.

---

## 5.11 Retrospective

After Sprint Review, the team runs a retrospective.

Retro prompts:

* What went well?
* What did not go well?
* What slowed us down?
* What should we improve next Sprint?
* What action item should we commit to?

The retrospective must produce at least one improvement action.

Example:

```json
{
  "went_well": [
    "Daily check-ins helped expose blockers early."
  ],
  "needs_improvement": [
    "Some backlog items were too large."
  ],
  "action_items": [
    {
      "title": "Break backlog items into smaller slices before Sprint Planning",
      "assigned_to_user_id": "uuid"
    }
  ]
}
```

---

## 5.12 Peer Review

After a Sprint, members review each other.

Peer review should focus on collaboration and team improvement, not punishment.

Review fields:

* Collaboration score
* Delivery score
* Communication score
* Continue feedback
* Improve feedback

Example:

```json
{
  "reviewee_user_id": "uuid",
  "collaboration_score": 5,
  "delivery_score": 4,
  "communication_score": 4,
  "continue_feedback": "Communicated blockers early.",
  "improve_feedback": "Update task status more consistently.",
  "is_anonymous_to_reviewee": true
}
```

Rules:

* Members can review other members.
* Owners can participate because they can also do work.
* Supervisors cannot submit peer reviews.
* A user cannot review themselves.
* One reviewer can submit one review per reviewee per Sprint.

---

# 6. Database Tables

## 6.1 `users`

Laravel already has this table.

Additional useful fields if needed:

```php
$table->string('google_id')->nullable()->unique();
$table->string('avatar_url')->nullable();
$table->string('timezone')->default('Asia/Jakarta');
```

---

## 6.2 `projects`

Stores independent project/team workspaces.

Columns:

```php
$table->uuid('id')->primary();
$table->foreignUuid('owner_user_id')->constrained('users')->cascadeOnDelete();
$table->string('name');
$table->text('description')->nullable();
$table->text('product_goal');
$table->integer('default_sprint_length_days')->default(14);
$table->integer('wip_limit_per_member')->nullable();
$table->string('status')->default('active'); // active, archived
$table->timestamps();
```

---

## 6.3 `project_memberships`

Stores users inside projects.

Columns:

```php
$table->uuid('id')->primary();
$table->foreignUuid('project_id')->constrained()->cascadeOnDelete();
$table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
$table->string('role'); // owner, member, supervisor
$table->string('status')->default('active'); // active, invited, removed
$table->foreignUuid('invited_by_user_id')->nullable()->constrained('users')->nullOnDelete();
$table->timestamp('joined_at')->nullable();
$table->timestamps();

$table->unique(['project_id', 'user_id']);
```

---

## 6.4 `project_invitations`

Stores pending project invitations.

Columns:

```php
$table->uuid('id')->primary();
$table->foreignUuid('project_id')->constrained()->cascadeOnDelete();
$table->string('email');
$table->string('role'); // member, supervisor
$table->string('token_hash');
$table->string('status')->default('pending'); // pending, accepted, expired, revoked
$table->foreignUuid('invited_by_user_id')->constrained('users')->cascadeOnDelete();
$table->foreignUuid('accepted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
$table->timestamp('expires_at');
$table->timestamp('accepted_at')->nullable();
$table->timestamps();
```

---

## 6.5 `definitions_of_done`

Stores project-level Definition of Done.

Columns:

```php
$table->uuid('id')->primary();
$table->foreignUuid('project_id')->constrained()->cascadeOnDelete();
$table->string('title')->default('Default Definition of Done');
$table->json('checklist');
$table->boolean('is_active')->default(true);
$table->foreignUuid('created_by_user_id')->constrained('users')->cascadeOnDelete();
$table->timestamps();
```

Example checklist:

```json
[
  "Acceptance criteria are completed",
  "Reviewed by another member",
  "No known critical bugs",
  "Demoable in Sprint Review"
]
```

---

## 6.6 `backlog_items`

Stores product backlog and Sprint work items.

Columns:

```php
$table->uuid('id')->primary();
$table->foreignUuid('project_id')->constrained()->cascadeOnDelete();
$table->string('title');
$table->text('description')->nullable();
$table->string('type')->default('story'); // story, task, bug, improvement
$table->string('status')->default('backlog'); 
// backlog, ready, selected, in_progress, in_review, done, archived
$table->decimal('priority_rank', 20, 10)->nullable();
$table->integer('business_value')->nullable();
$table->integer('estimate_points')->nullable();
$table->json('acceptance_criteria')->nullable();
$table->foreignUuid('created_by_user_id')->constrained('users')->cascadeOnDelete();
$table->foreignUuid('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
$table->timestamp('done_at')->nullable();
$table->timestamps();
```

---

## 6.7 `sprints`

Stores project Sprints.

Columns:

```php
$table->uuid('id')->primary();
$table->foreignUuid('project_id')->constrained()->cascadeOnDelete();
$table->string('name');
$table->text('sprint_goal');
$table->string('status')->default('planned'); 
// planned, active, review, retro, closed, cancelled
$table->date('start_date');
$table->date('end_date');
$table->foreignUuid('created_by_user_id')->constrained('users')->cascadeOnDelete();
$table->foreignUuid('closed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
$table->timestamp('closed_at')->nullable();
$table->timestamps();
```

Important rule:

Only one active Sprint is allowed per project.

This can be enforced in application logic.

---

## 6.8 `sprint_items`

Join table between Sprints and backlog items.

Columns:

```php
$table->uuid('id')->primary();
$table->foreignUuid('sprint_id')->constrained()->cascadeOnDelete();
$table->foreignUuid('backlog_item_id')->constrained()->cascadeOnDelete();
$table->integer('committed_points')->nullable();
$table->foreignUuid('added_by_user_id')->constrained('users')->cascadeOnDelete();
$table->timestamp('added_at')->useCurrent();

$table->unique(['sprint_id', 'backlog_item_id']);
```

---

## 6.9 `daily_checkins`

Stores daily Scrum-style updates.

Columns:

```php
$table->uuid('id')->primary();
$table->foreignUuid('project_id')->constrained()->cascadeOnDelete();
$table->foreignUuid('sprint_id')->constrained()->cascadeOnDelete();
$table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
$table->date('checkin_date');
$table->text('yesterday')->nullable();
$table->text('today')->nullable();
$table->text('blockers')->nullable();
$table->unsignedTinyInteger('confidence_score')->nullable(); // 1-5
$table->timestamps();

$table->unique(['sprint_id', 'user_id', 'checkin_date']);
```

---

## 6.10 `impediments`

Stores blockers.

Columns:

```php
$table->uuid('id')->primary();
$table->foreignUuid('project_id')->constrained()->cascadeOnDelete();
$table->foreignUuid('sprint_id')->nullable()->constrained()->nullOnDelete();
$table->foreignUuid('backlog_item_id')->nullable()->constrained()->nullOnDelete();
$table->foreignUuid('reported_by_user_id')->constrained('users')->cascadeOnDelete();
$table->foreignUuid('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
$table->string('title');
$table->text('description')->nullable();
$table->string('status')->default('open'); // open, in_progress, resolved, ignored
$table->timestamp('resolved_at')->nullable();
$table->timestamps();
```

---

## 6.11 `comments`

Generic comments table.

Columns:

```php
$table->uuid('id')->primary();
$table->foreignUuid('project_id')->constrained()->cascadeOnDelete();
$table->string('entity_type'); 
// backlog_item, sprint, sprint_review, retrospective, peer_review_summary, impediment
$table->uuid('entity_id');
$table->foreignUuid('author_user_id')->constrained('users')->cascadeOnDelete();
$table->text('body');
$table->timestamps();

$table->index(['entity_type', 'entity_id']);
```

---

## 6.12 `sprint_reviews`

Stores Sprint Review summary.

Columns:

```php
$table->uuid('id')->primary();
$table->foreignUuid('project_id')->constrained()->cascadeOnDelete();
$table->foreignUuid('sprint_id')->constrained()->cascadeOnDelete();
$table->text('summary')->nullable();
$table->string('demo_url')->nullable();
$table->foreignUuid('created_by_user_id')->constrained('users')->cascadeOnDelete();
$table->timestamps();

$table->unique('sprint_id');
```

---

## 6.13 `sprint_review_items`

Stores review decision for each Sprint item.

Columns:

```php
$table->uuid('id')->primary();
$table->foreignUuid('sprint_review_id')->constrained()->cascadeOnDelete();
$table->foreignUuid('backlog_item_id')->constrained()->cascadeOnDelete();
$table->string('decision'); // accepted, rejected, carry_over
$table->text('notes')->nullable();
$table->foreignUuid('decided_by_user_id')->constrained('users')->cascadeOnDelete();
$table->timestamps();

$table->unique(['sprint_review_id', 'backlog_item_id']);
```

---

## 6.14 `retrospectives`

Stores Sprint retrospective.

Columns:

```php
$table->uuid('id')->primary();
$table->foreignUuid('project_id')->constrained()->cascadeOnDelete();
$table->foreignUuid('sprint_id')->constrained()->cascadeOnDelete();
$table->unsignedTinyInteger('team_happiness_score')->nullable(); // 1-5
$table->timestamps();

$table->unique('sprint_id');
```

---

## 6.15 `retro_items`

Stores retrospective notes and action items.

Columns:

```php
$table->uuid('id')->primary();
$table->foreignUuid('retrospective_id')->constrained()->cascadeOnDelete();
$table->foreignUuid('author_user_id')->constrained('users')->cascadeOnDelete();
$table->string('type'); // went_well, problem, idea, action
$table->text('body');
$table->foreignUuid('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
$table->foreignUuid('due_sprint_id')->nullable()->constrained('sprints')->nullOnDelete();
$table->boolean('is_completed')->default(false);
$table->timestamps();
```

---

## 6.16 `peer_review_cycles`

Stores one peer review cycle per Sprint.

Columns:

```php
$table->uuid('id')->primary();
$table->foreignUuid('project_id')->constrained()->cascadeOnDelete();
$table->foreignUuid('sprint_id')->constrained()->cascadeOnDelete();
$table->string('status')->default('open'); // open, closed
$table->timestamp('opens_at')->nullable();
$table->timestamp('closes_at')->nullable();
$table->timestamps();

$table->unique('sprint_id');
```

---

## 6.17 `peer_reviews`

Stores member-to-member reviews.

Columns:

```php
$table->uuid('id')->primary();
$table->foreignUuid('cycle_id')->constrained('peer_review_cycles')->cascadeOnDelete();
$table->foreignUuid('reviewer_user_id')->constrained('users')->cascadeOnDelete();
$table->foreignUuid('reviewee_user_id')->constrained('users')->cascadeOnDelete();
$table->unsignedTinyInteger('collaboration_score'); // 1-5
$table->unsignedTinyInteger('delivery_score'); // 1-5
$table->unsignedTinyInteger('communication_score'); // 1-5
$table->text('continue_feedback')->nullable();
$table->text('improve_feedback')->nullable();
$table->boolean('is_anonymous_to_reviewee')->default(true);
$table->timestamp('submitted_at')->nullable();
$table->timestamps();

$table->unique(['cycle_id', 'reviewer_user_id', 'reviewee_user_id']);
```

Application rule:

```php
reviewer_user_id !== reviewee_user_id
```

---

## 6.18 `activity_events`

Stores project timeline and audit trail.

Columns:

```php
$table->uuid('id')->primary();
$table->foreignUuid('project_id')->constrained()->cascadeOnDelete();
$table->foreignUuid('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
$table->string('event_type');
// project.created, member.invited, sprint.started, item.done, blocker.created, etc.
$table->string('entity_type')->nullable();
$table->uuid('entity_id')->nullable();
$table->json('metadata')->nullable();
$table->timestamps();
```

---

# 7. Laravel Models

Recommended models:

```txt
User
Project
ProjectMembership
ProjectInvitation
DefinitionOfDone
BacklogItem
Sprint
SprintItem
DailyCheckin
Impediment
Comment
SprintReview
SprintReviewItem
Retrospective
RetroItem
PeerReviewCycle
PeerReview
ActivityEvent
```

---

# 8. Main Relationships

## User

```php
public function projects()
{
    return $this->belongsToMany(Project::class, 'project_memberships')
        ->withPivot(['role', 'status'])
        ->withTimestamps();
}

public function ownedProjects()
{
    return $this->hasMany(Project::class, 'owner_user_id');
}
```

## Project

```php
public function owner()
{
    return $this->belongsTo(User::class, 'owner_user_id');
}

public function memberships()
{
    return $this->hasMany(ProjectMembership::class);
}

public function members()
{
    return $this->belongsToMany(User::class, 'project_memberships')
        ->withPivot(['role', 'status'])
        ->withTimestamps();
}

public function backlogItems()
{
    return $this->hasMany(BacklogItem::class);
}

public function sprints()
{
    return $this->hasMany(Sprint::class);
}

public function activeSprint()
{
    return $this->hasOne(Sprint::class)->where('status', 'active');
}
```

## Sprint

```php
public function project()
{
    return $this->belongsTo(Project::class);
}

public function items()
{
    return $this->belongsToMany(BacklogItem::class, 'sprint_items')
        ->withPivot(['committed_points', 'added_by_user_id', 'added_at']);
}

public function checkins()
{
    return $this->hasMany(DailyCheckin::class);
}

public function review()
{
    return $this->hasOne(SprintReview::class);
}

public function retrospective()
{
    return $this->hasOne(Retrospective::class);
}

public function peerReviewCycle()
{
    return $this->hasOne(PeerReviewCycle::class);
}
```

---

# 9. API Routes

Use Sanctum middleware for protected API routes.

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [MeController::class, 'show']);

    Route::apiResource('projects', ProjectController::class);

    Route::get('/projects/{project}/members', [ProjectMemberController::class, 'index']);
    Route::patch('/projects/{project}/members/{user}', [ProjectMemberController::class, 'update']);
    Route::delete('/projects/{project}/members/{user}', [ProjectMemberController::class, 'destroy']);

    Route::post('/projects/{project}/invitations', [ProjectInvitationController::class, 'store']);
    Route::post('/invitations/{token}/accept', [ProjectInvitationController::class, 'accept']);

    Route::get('/projects/{project}/backlog-items', [BacklogItemController::class, 'index']);
    Route::post('/projects/{project}/backlog-items', [BacklogItemController::class, 'store']);
    Route::get('/backlog-items/{backlogItem}', [BacklogItemController::class, 'show']);
    Route::patch('/backlog-items/{backlogItem}', [BacklogItemController::class, 'update']);
    Route::delete('/backlog-items/{backlogItem}', [BacklogItemController::class, 'destroy']);
    Route::post('/projects/{project}/backlog-items/reorder', [BacklogItemController::class, 'reorder']);

    Route::get('/projects/{project}/sprints', [SprintController::class, 'index']);
    Route::post('/projects/{project}/sprints', [SprintController::class, 'store']);
    Route::get('/sprints/{sprint}', [SprintController::class, 'show']);
    Route::post('/sprints/{sprint}/items', [SprintItemController::class, 'store']);
    Route::delete('/sprints/{sprint}/items/{backlogItem}', [SprintItemController::class, 'destroy']);
    Route::post('/sprints/{sprint}/start', [SprintLifecycleController::class, 'start']);
    Route::post('/sprints/{sprint}/close', [SprintLifecycleController::class, 'close']);
    Route::get('/sprints/{sprint}/board', [SprintBoardController::class, 'show']);

    Route::patch('/backlog-items/{backlogItem}/status', [BacklogItemStatusController::class, 'update']);
    Route::post('/backlog-items/{backlogItem}/mark-done', [BacklogItemStatusController::class, 'markDone']);

    Route::get('/sprints/{sprint}/checkins', [DailyCheckinController::class, 'index']);
    Route::post('/sprints/{sprint}/checkins', [DailyCheckinController::class, 'store']);

    Route::post('/projects/{project}/impediments', [ImpedimentController::class, 'store']);
    Route::patch('/impediments/{impediment}', [ImpedimentController::class, 'update']);
    Route::post('/impediments/{impediment}/resolve', [ImpedimentController::class, 'resolve']);

    Route::get('/comments', [CommentController::class, 'index']);
    Route::post('/comments', [CommentController::class, 'store']);
    Route::patch('/comments/{comment}', [CommentController::class, 'update']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);

    Route::get('/sprints/{sprint}/review', [SprintReviewController::class, 'show']);
    Route::post('/sprints/{sprint}/review', [SprintReviewController::class, 'store']);

    Route::get('/sprints/{sprint}/retrospective', [RetrospectiveController::class, 'show']);
    Route::post('/sprints/{sprint}/retrospective', [RetrospectiveController::class, 'store']);
    Route::post('/retrospectives/{retrospective}/items', [RetroItemController::class, 'store']);

    Route::get('/sprints/{sprint}/peer-review-cycle', [PeerReviewCycleController::class, 'show']);
    Route::post('/sprints/{sprint}/peer-review-cycle', [PeerReviewCycleController::class, 'store']);
    Route::post('/peer-review-cycles/{cycle}/reviews', [PeerReviewController::class, 'store']);
    Route::get('/peer-review-cycles/{cycle}/summary', [PeerReviewSummaryController::class, 'show']);
    Route::post('/peer-review-cycles/{cycle}/close', [PeerReviewCycleController::class, 'close']);
});
```

---

# 10. API Response Models

## Project

```json
{
  "id": "uuid",
  "name": "Marketing Website Revamp",
  "description": "Build and launch the new website.",
  "product_goal": "Launch a clear and high-converting website.",
  "owner_user_id": "uuid",
  "default_sprint_length_days": 14,
  "wip_limit_per_member": 2,
  "status": "active",
  "my_role": "owner",
  "created_at": "2026-04-29T10:00:00Z"
}
```

---

## Backlog Item

```json
{
  "id": "uuid",
  "project_id": "uuid",
  "title": "Invite users by email",
  "description": "As a project owner, I want to invite users so they can collaborate.",
  "type": "story",
  "status": "in_progress",
  "priority_rank": 1,
  "business_value": 9,
  "estimate_points": 5,
  "acceptance_criteria": [
    "Owner can invite by email",
    "Invitee receives invite link",
    "Invitee can accept invite"
  ],
  "assigned_to_user": {
    "id": "uuid",
    "name": "Daffa"
  }
}
```

---

## Sprint

```json
{
  "id": "uuid",
  "project_id": "uuid",
  "name": "Sprint 1",
  "sprint_goal": "Enable project creation and user invitations.",
  "status": "active",
  "start_date": "2026-05-01",
  "end_date": "2026-05-14",
  "committed_points": 18,
  "done_points": 8,
  "open_impediments": 2
}
```

---

## Sprint Board

```json
{
  "sprint": {
    "id": "uuid",
    "name": "Sprint 1",
    "sprint_goal": "Enable project creation and user invitations.",
    "status": "active",
    "start_date": "2026-05-01",
    "end_date": "2026-05-14"
  },
  "columns": {
    "selected": [],
    "in_progress": [],
    "in_review": [],
    "done": []
  },
  "impediments": [],
  "today_checkins": []
}
```

---

# 11. Authorization Rules

Use Laravel Policies for the main models.

Recommended policies:

```txt
ProjectPolicy
BacklogItemPolicy
SprintPolicy
DailyCheckinPolicy
ImpedimentPolicy
CommentPolicy
SprintReviewPolicy
RetrospectivePolicy
PeerReviewPolicy
```

## Permission Matrix

| Action                   | Owner |        Member |            Supervisor |
| ------------------------ | ----: | ------------: | --------------------: |
| View project             |   Yes |           Yes |                   Yes |
| Update project           |   Yes |            No |                    No |
| Invite users             |   Yes |            No |                    No |
| Remove members           |   Yes |            No |                    No |
| Create backlog item      |   Yes |           Yes |                    No |
| Update backlog item      |   Yes |           Yes |                    No |
| Prioritize backlog       |   Yes |            No |                    No |
| Create Sprint            |   Yes | Yes, optional |                    No |
| Start Sprint             |   Yes |            No |                    No |
| Close Sprint             |   Yes |            No |                    No |
| Move work status         |   Yes |           Yes |                    No |
| Mark work done           |   Yes |           Yes |                    No |
| Submit daily check-in    |   Yes |           Yes |                    No |
| Report blocker           |   Yes |           Yes |                    No |
| Resolve blocker          |   Yes |           Yes |                    No |
| Comment                  |   Yes |           Yes |                   Yes |
| Submit peer review       |   Yes |           Yes |                    No |
| View peer review summary |   Yes |       Limited | No or aggregated only |

For MVP, keep Sprint start and close owner-only.

---

# 12. Core Product Rules

These rules should be enforced by validation or service logic.

1. A project must have a product goal.
2. A project must have an active Definition of Done.
3. A project can only have one active Sprint.
4. A Sprint must have a Sprint Goal before it can start.
5. A Sprint must have at least one backlog item before it can start.
6. A Sprint cannot start if another Sprint in the same project is active.
7. A backlog item cannot be marked done unless the Definition of Done is confirmed.
8. A member can only submit one check-in per Sprint per day.
9. A supervisor can comment but cannot mutate project work.
10. A peer review cycle belongs to one Sprint.
11. A user cannot peer review themselves.
12. A retrospective should produce at least one action item.
13. A closed Sprint should not allow normal work updates.
14. Carry-over items should return to backlog or move to the next Sprint.

---

# 13. Recommended Service Classes

To avoid bloated controllers, use service classes.

```txt
ProjectService
ProjectInvitationService
BacklogService
SprintPlanningService
SprintLifecycleService
SprintBoardService
DailyCheckinService
ImpedimentService
SprintReviewService
RetrospectiveService
PeerReviewService
ActivityEventService
```

Example responsibilities:

## `SprintLifecycleService`

* Start Sprint
* Validate Sprint Goal
* Validate selected backlog items
* Prevent multiple active Sprints
* Close Sprint
* Move incomplete work to carry-over
* Open review/retro flow

## `BacklogService`

* Create backlog item
* Reorder backlog
* Assign item
* Change status
* Mark item as done
* Validate Definition of Done

## `PeerReviewService`

* Open review cycle
* Submit review
* Prevent self-review
* Prevent duplicate reviews
* Generate summary

---

# 14. MVP Screens

## 14.1 Project List

Shows all projects the user belongs to.

Each card shows:

* Project name
* User role
* Active Sprint, if any
* Open blockers
* Last activity

---

## 14.2 Project Dashboard

Shows:

* Product Goal
* Current Sprint
* Sprint Goal
* Sprint progress
* Open blockers
* Missing daily check-ins
* Recent comments
* Next recommended action

Possible next actions:

* Create backlog item
* Plan Sprint
* Submit daily check-in
* Review Sprint
* Run retrospective
* Submit peer review

---

## 14.3 Backlog

Shows:

* Prioritized backlog items
* Business value
* Estimate points
* Status
* Assignee
* Acceptance criteria

Actions:

* Create item
* Edit item
* Reorder item
* Add item to Sprint
* Archive item

---

## 14.4 Sprint Planning

Shows:

* Sprint Goal form
* Start and end date
* Available backlog items
* Selected Sprint items
* Total committed points

---

## 14.5 Sprint Board

Shows columns:

* Selected
* In Progress
* In Review
* Done

Also shows:

* Sprint Goal
* Blockers
* Daily check-ins
* Work progress

---

## 14.6 Daily Check-In

Shows one form per day.

Fields:

* Yesterday
* Today
* Blockers
* Confidence score

---

## 14.7 Sprint Review

Shows:

* Completed items
* Incomplete items
* Demo notes
* Item acceptance decision
* Supervisor comments

---

## 14.8 Retrospective

Shows:

* Went well
* Problems
* Ideas
* Action items
* Team happiness score

---

## 14.9 Peer Review

Shows:

* Review form for each member
* Score fields
* Continue feedback
* Improve feedback

---

# 15. MVP Metrics

The MVP should track simple team metrics.

Recommended metrics:

| Metric                      | Purpose                              |
| --------------------------- | ------------------------------------ |
| Committed points            | How much work the team planned       |
| Done points                 | How much work was actually completed |
| Carry-over points           | How much planned work was unfinished |
| Open blockers               | Current impediments                  |
| Average blocker age         | How quickly blockers are resolved    |
| Check-in completion rate    | Daily collaboration health           |
| Team happiness score        | Sustainability                       |
| Peer review completion rate | Feedback loop health                 |

Avoid ranking individuals by output. The product should encourage team improvement, not surveillance.

---

# 16. Implementation Priority

## Phase 1 — Foundation

* Google Auth with Sanctum
* Users
* Projects
* Project memberships
* Project invitations
* Role-based authorization

## Phase 2 — Scrum Core

* Definition of Done
* Backlog items
* Sprint creation
* Sprint planning
* Sprint board
* Work status updates
* Comments

## Phase 3 — Inspection and Adaptation

* Daily check-ins
* Impediments/blockers
* Sprint Review
* Retrospective
* Peer review

## Phase 4 — Metrics and Guidance

* Sprint progress
* Done vs committed points
* Carry-over tracking
* Blocker tracking
* Check-in rate
* Peer review summary
* Team improvement history

---

# 17. Product Positioning

This app should be positioned as:

> A lightweight Scrum execution app for small teams that turns Scrum principles into guided daily behavior.

The main differentiator is not the task board.

The differentiator is the workflow:

* The app guides teams to set goals.
* The app keeps work transparent.
* The app makes blockers visible.
* The app prevents fake “done”.
* The app creates regular feedback loops.
* The app allows supervisors to observe without micromanaging.
* The app helps teams improve after every Sprint.

The MVP should stay simple, but every feature should reinforce better Scrum behavior.

```
```
