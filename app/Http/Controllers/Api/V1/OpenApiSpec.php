<?php

namespace App\Http\Controllers\Api\V1;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "Ambatuwork API v1",
    description: "API documentation for Ambatuwork, a Scrum-based collaboration application used by Web and Android clients. Authenticate your requests using a Sanctum Bearer token."
)]
#[OA\Server(
    url: "/api/v1",
    description: "API v1 Endpoint Base"
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    name: "Authorization",
    in: "header",
    scheme: "bearer",
    bearerFormat: "JWT",
    description: "Enter your Sanctum token. Example: Bearer 1|abcdef..."
)]
class OpenApiSpec
{
    // --- SCHEMA DEFINITIONS ---

    #[OA\Schema(
        schema: "User",
        type: "object",
        properties: [
            new OA\Property(property: "id", type: "integer", example: 1),
            new OA\Property(property: "name", type: "string", example: "Jane Doe"),
            new OA\Property(property: "email", type: "string", format: "email", example: "jane@example.com"),
            new OA\Property(property: "avatar_url", type: "string", format: "uri", nullable: true, example: "https://lh3.googleusercontent.com/..."),
            new OA\Property(property: "last_login_at", type: "string", format: "date-time", nullable: true, example: "2026-06-04T12:00:00.000000Z"),
            new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2026-06-01T10:00:00.000000Z"),
            new OA\Property(property: "updated_at", type: "string", format: "date-time", example: "2026-06-04T12:00:00.000000Z"),
        ]
    )]
    public $user;

    #[OA\Schema(
        schema: "DefinitionOfDone",
        type: "object",
        properties: [
            new OA\Property(property: "id", type: "integer", example: 1),
            new OA\Property(property: "project_id", type: "integer", example: 1),
            new OA\Property(property: "title", type: "string", example: "Default Definition of Done"),
            new OA\Property(property: "checklist", type: "array", items: new OA\Items(type: "string"), example: ["Tests pass", "Code reviewed", "Documentation updated"]),
            new OA\Property(property: "is_active", type: "boolean", example: true),
            new OA\Property(property: "created_by_user_id", type: "integer", example: 1),
            new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2026-06-01T10:00:00.000000Z"),
            new OA\Property(property: "updated_at", type: "string", format: "date-time", example: "2026-06-04T12:00:00.000000Z"),
        ]
    )]
    public $definitionOfDone;

    #[OA\Schema(
        schema: "Project",
        type: "object",
        properties: [
            new OA\Property(property: "id", type: "integer", example: 1),
            new OA\Property(property: "name", type: "string", example: "Website Redesign"),
            new OA\Property(property: "description", type: "string", nullable: true, example: "Refresh the marketing site"),
            new OA\Property(property: "product_goal", type: "string", example: "Increase demo requests by 20%"),
            new OA\Property(property: "owner_user_id", type: "integer", example: 1),
            new OA\Property(property: "default_sprint_length_days", type: "integer", example: 14),
            new OA\Property(property: "wip_limit_per_member", type: "integer", nullable: true, example: 3),
            new OA\Property(property: "status", type: "string", example: "active"),
            new OA\Property(property: "definition_of_done", ref: "#/components/schemas/DefinitionOfDone"),
            new OA\Property(property: "my_role", type: "string", example: "owner"),
            new OA\Property(property: "member_count", type: "integer", example: 1),
            new OA\Property(property: "backlog_item_count", type: "integer", example: 4),
            new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2026-06-01T10:00:00.000000Z"),
            new OA\Property(property: "updated_at", type: "string", format: "date-time", example: "2026-06-04T12:00:00.000000Z"),
        ]
    )]
    public $project;

    #[OA\Schema(
        schema: "BacklogItem",
        type: "object",
        properties: [
            new OA\Property(property: "id", type: "integer", example: 10),
            new OA\Property(property: "project_id", type: "integer", example: 1),
            new OA\Property(property: "title", type: "string", example: "Add dark mode"),
            new OA\Property(property: "description", type: "string", nullable: true, example: "Support theme switching in the settings screen"),
            new OA\Property(property: "type", type: "string", example: "story"),
            new OA\Property(property: "status", type: "string", example: "in_progress"),
            new OA\Property(property: "priority", type: "string", enum: ["highest", "high", "medium", "low", "lowest"], default: "medium", example: "medium"),
            new OA\Property(property: "estimate_points", type: "integer", nullable: true, example: 5),
            new OA\Property(property: "acceptance_criteria", type: "array", items: new OA\Items(type: "string"), nullable: true, example: ["User can toggle dark mode", "Preference persists after refresh"]),
            new OA\Property(property: "created_by_user_id", type: "integer", example: 1),
            new OA\Property(property: "assigned_to_user_id", type: "integer", nullable: true, example: 2),
            new OA\Property(property: "assigned_to_user", ref: "#/components/schemas/User", nullable: true),
            new OA\Property(property: "done_at", type: "string", format: "date-time", nullable: true, example: null),
            new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2026-06-01T10:00:00.000000Z"),
            new OA\Property(property: "updated_at", type: "string", format: "date-time", example: "2026-06-04T12:00:00.000000Z"),
        ]
    )]
    public $backlogItem;

    #[OA\Schema(
        schema: "Sprint",
        type: "object",
        properties: [
            new OA\Property(property: "id", type: "integer", example: 5),
            new OA\Property(property: "project_id", type: "integer", example: 1),
            new OA\Property(property: "name", type: "string", example: "Sprint 12"),
            new OA\Property(property: "sprint_goal", type: "string", example: "Ship the invitation flow"),
            new OA\Property(property: "status", type: "string", example: "active"),
            new OA\Property(property: "start_date", type: "string", format: "date", example: "2026-06-01"),
            new OA\Property(property: "end_date", type: "string", format: "date", example: "2026-06-14"),
            new OA\Property(property: "created_by_user_id", type: "integer", example: 1),
            new OA\Property(property: "closed_by_user_id", type: "integer", nullable: true, example: null),
            new OA\Property(property: "closed_at", type: "string", format: "date-time", nullable: true, example: null),
            new OA\Property(property: "item_count", type: "integer", example: 3),
            new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2026-06-01T10:00:00.000000Z"),
            new OA\Property(property: "updated_at", type: "string", format: "date-time", example: "2026-06-04T12:00:00.000000Z"),
        ]
    )]
    public $sprint;

    #[OA\Schema(
        schema: "DailyCheckin",
        type: "object",
        properties: [
            new OA\Property(property: "id", type: "integer", example: 1),
            new OA\Property(property: "project_id", type: "integer", example: 1),
            new OA\Property(property: "sprint_id", type: "integer", example: 5),
            new OA\Property(property: "user_id", type: "integer", example: 2),
            new OA\Property(property: "yesterday", type: "string", nullable: true, example: "Implemented tests"),
            new OA\Property(property: "today", type: "string", nullable: true, example: "Debugging routes"),
            new OA\Property(property: "blockers", type: "string", nullable: true, example: "Database connection timeout"),
            new OA\Property(property: "confidence_score", type: "integer", example: 4),
            new OA\Property(property: "checkin_date", type: "string", format: "date", example: "2026-06-04"),
            new OA\Property(property: "user", ref: "#/components/schemas/User"),
            new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2026-06-04T12:00:00.000000Z"),
            new OA\Property(property: "updated_at", type: "string", format: "date-time", example: "2026-06-04T12:00:00.000000Z"),
        ]
    )]
    public $dailyCheckin;

    #[OA\Schema(
        schema: "Impediment",
        type: "object",
        properties: [
            new OA\Property(property: "id", type: "integer", example: 1),
            new OA\Property(property: "project_id", type: "integer", example: 1),
            new OA\Property(property: "sprint_id", type: "integer", nullable: true, example: 5),
            new OA\Property(property: "reported_by_user_id", type: "integer", example: 2),
            new OA\Property(property: "resolved_by_user_id", type: "integer", nullable: true, example: 1),
            new OA\Property(property: "title", type: "string", example: "Database connection timeout"),
            new OA\Property(property: "description", type: "string", nullable: true, example: "Sail mysql is throwing timeout exception"),
            new OA\Property(property: "status", type: "string", example: "resolved"),
            new OA\Property(property: "resolved_at", type: "string", format: "date-time", nullable: true, example: "2026-06-04T12:30:00.000000Z"),
            new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2026-06-04T12:00:00.000000Z"),
            new OA\Property(property: "updated_at", type: "string", format: "date-time", example: "2026-06-04T12:30:00.000000Z"),
        ]
    )]
    public $impediment;

    #[OA\Schema(
        schema: "RetroItem",
        type: "object",
        properties: [
            new OA\Property(property: "id", type: "integer", example: 1),
            new OA\Property(property: "type", type: "string", example: "went_well"),
            new OA\Property(property: "body", type: "string", example: "Collaboration was awesome!"),
            new OA\Property(property: "author_user_id", type: "integer", example: 2),
            new OA\Property(property: "author", ref: "#/components/schemas/User"),
            new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2026-06-04T12:00:00.000000Z"),
            new OA\Property(property: "updated_at", type: "string", format: "date-time", example: "2026-06-04T12:00:00.000000Z"),
        ]
    )]
    public $retroItem;

    #[OA\Schema(
        schema: "Retrospective",
        type: "object",
        properties: [
            new OA\Property(property: "id", type: "integer", example: 1),
            new OA\Property(property: "sprint_id", type: "integer", example: 5),
            new OA\Property(property: "team_happiness_score", type: "integer", example: 5),
            new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2026-06-04T12:00:00.000000Z"),
            new OA\Property(property: "updated_at", type: "string", format: "date-time", example: "2026-06-04T12:00:00.000000Z"),
            new OA\Property(property: "items", type: "array", items: new OA\Items(ref: "#/components/schemas/RetroItem")),
        ]
    )]
    public $retrospective;

    #[OA\Schema(
        schema: "SprintReviewItem",
        type: "object",
        properties: [
            new OA\Property(property: "id", type: "integer", example: 1),
            new OA\Property(property: "backlog_item_id", type: "integer", example: 10),
            new OA\Property(property: "decision", type: "string", example: "accepted"),
            new OA\Property(property: "notes", type: "string", nullable: true, example: "Looking good"),
            new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2026-06-04T12:00:00.000000Z"),
            new OA\Property(property: "updated_at", type: "string", format: "date-time", example: "2026-06-04T12:00:00.000000Z"),
        ]
    )]
    public $sprintReviewItem;

    #[OA\Schema(
        schema: "SprintReview",
        type: "object",
        properties: [
            new OA\Property(property: "id", type: "integer", example: 1),
            new OA\Property(property: "sprint_id", type: "integer", example: 5),
            new OA\Property(property: "summary", type: "string", example: "Finished core features."),
            new OA\Property(property: "demo_url", type: "string", format: "uri", nullable: true, example: "https://example.com/demo"),
            new OA\Property(property: "created_by_user_id", type: "integer", example: 1),
            new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2026-06-04T12:00:00.000000Z"),
            new OA\Property(property: "updated_at", type: "string", format: "date-time", example: "2026-06-04T12:00:00.000000Z"),
            new OA\Property(property: "items", type: "array", items: new OA\Items(ref: "#/components/schemas/SprintReviewItem")),
        ]
    )]
    public $sprintReview;

    #[OA\Schema(
        schema: "PeerReviewCycle",
        type: "object",
        properties: [
            new OA\Property(property: "id", type: "integer", example: 1),
            new OA\Property(property: "project_id", type: "integer", example: 1),
            new OA\Property(property: "sprint_id", type: "integer", example: 5),
            new OA\Property(property: "status", type: "string", example: "open"),
            new OA\Property(property: "created_by_user_id", type: "integer", example: 1),
            new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2026-06-04T12:00:00.000000Z"),
            new OA\Property(property: "updated_at", type: "string", format: "date-time", example: "2026-06-04T12:00:00.000000Z"),
        ]
    )]
    public $peerReviewCycle;

    #[OA\Schema(
        schema: "PeerReview",
        type: "object",
        properties: [
            new OA\Property(property: "id", type: "integer", example: 1),
            new OA\Property(property: "peer_review_cycle_id", type: "integer", example: 1),
            new OA\Property(property: "reviewer_user_id", type: "integer", example: 2),
            new OA\Property(property: "reviewee_user_id", type: "integer", example: 1),
            new OA\Property(property: "collaboration_score", type: "integer", example: 5),
            new OA\Property(property: "delivery_score", type: "integer", example: 4),
            new OA\Property(property: "communication_score", type: "integer", example: 5),
            new OA\Property(property: "continue_feedback", type: "string", nullable: true, example: "Good planning."),
            new OA\Property(property: "improve_feedback", type: "string", nullable: true, example: "None."),
            new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2026-06-04T12:00:00.000000Z"),
            new OA\Property(property: "updated_at", type: "string", format: "date-time", example: "2026-06-04T12:00:00.000000Z"),
        ]
    )]
    public $peerReview;

    #[OA\Schema(
        schema: "ProjectInvitation",
        type: "object",
        properties: [
            new OA\Property(property: "id", type: "integer", example: 1),
            new OA\Property(property: "project_id", type: "integer", example: 1),
            new OA\Property(property: "email", type: "string", example: "sam@example.com"),
            new OA\Property(property: "role", type: "string", example: "member"),
            new OA\Property(property: "token", type: "string", example: "abc123token"),
            new OA\Property(property: "created_by_user_id", type: "integer", example: 1),
            new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2026-06-04T12:00:00.000000Z"),
            new OA\Property(property: "updated_at", type: "string", format: "date-time", example: "2026-06-04T12:00:00.000000Z"),
            new OA\Property(property: "project", ref: "#/components/schemas/Project"),
        ]
    )]
    public $projectInvitation;

    #[OA\Schema(
        schema: "UserStats",
        type: "object",
        properties: [
            new OA\Property(
                property: "projects",
                type: "object",
                properties: [
                    new OA\Property(property: "total_active", type: "integer", example: 3),
                ]
            ),
            new OA\Property(
                property: "backlog_items",
                type: "object",
                properties: [
                    new OA\Property(property: "assigned_total", type: "integer", example: 12),
                    new OA\Property(
                        property: "assigned_by_status",
                        type: "object",
                        properties: [
                            new OA\Property(property: "backlog", type: "integer", example: 2),
                            new OA\Property(property: "ready", type: "integer", example: 1),
                            new OA\Property(property: "selected", type: "integer", example: 1),
                            new OA\Property(property: "in_progress", type: "integer", example: 3),
                            new OA\Property(property: "in_review", type: "integer", example: 1),
                            new OA\Property(property: "done", type: "integer", example: 4),
                        ]
                    ),
                    new OA\Property(property: "completed_points", type: "integer", example: 15),
                ]
            ),
            new OA\Property(
                property: "daily_checkins",
                type: "object",
                properties: [
                    new OA\Property(property: "total_submitted", type: "integer", example: 8),
                    new OA\Property(property: "average_confidence", type: "number", format: "float", example: 4.25),
                ]
            ),
            new OA\Property(
                property: "impediments",
                type: "object",
                properties: [
                    new OA\Property(property: "reported_total", type: "integer", example: 3),
                    new OA\Property(property: "reported_resolved", type: "integer", example: 2),
                    new OA\Property(
                        property: "reported_by_status",
                        type: "object",
                        properties: [
                            new OA\Property(property: "open", type: "integer", example: 1),
                            new OA\Property(property: "in_progress", type: "integer", example: 0),
                            new OA\Property(property: "resolved", type: "integer", example: 2),
                            new OA\Property(property: "ignored", type: "integer", example: 0),
                        ]
                    ),
                ]
            ),
            new OA\Property(
                property: "peer_reviews",
                type: "object",
                properties: [
                    new OA\Property(property: "submitted_total", type: "integer", example: 5),
                    new OA\Property(property: "received_total", type: "integer", example: 4),
                    new OA\Property(
                        property: "received_average_scores",
                        type: "object",
                        properties: [
                            new OA\Property(property: "collaboration", type: "number", format: "float", example: 4.5),
                            new OA\Property(property: "delivery", type: "number", format: "float", example: 4.0),
                            new OA\Property(property: "communication", type: "number", format: "float", example: 4.75),
                        ]
                    ),
                ]
            ),
        ]
    )]
    public $userStats;
}

