# Schema — ABL Sistem SDM

## Entity Relationship Overview

```
users ──┬── units
         ├── positions
         ├── duty_trips (employee)
         ├── duty_trips (manager)
         ├── attendances
         ├── employee_kpis
         ├── performance_reviews
         ├── merit_results
         ├── career_goals
         ├── employee_competencies
         ├── training_requests
         ├── mentorings
         ├── activity_logs
         └── push_subscriptions

units    ──┬── positions
            └── users

duty_trips     ──┬── duty_locations
                  └── attendances

approval_chains (standalone config)

activity_logs  (polymorphic: subject_type + subject_id)
```

## Tables

### Organisasi

#### `units`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| name | string | |
| code | string | UNIQUE |
| created_at | timestamp | |
| updated_at | timestamp | |

#### `positions`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| unit_id | bigint | FK → units, CASCADE |
| name | string | |
| level | unsignedSmallInt | default 1 |
| created_at | timestamp | |
| updated_at | timestamp | |
| UNIQUE | (unit_id, name) | |

#### `users`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| name | string | |
| email | string | UNIQUE |
| password | string | |
| avatar_url | string | nullable |
| role | string | indexed; UserRole enum |
| unit_id | bigint | FK → units, NULL ON DELETE |
| position_id | bigint | FK → positions, NULL ON DELETE |
| manager_id | bigint | FK → users (self-ref), NULL ON DELETE |
| delegate_id | bigint | FK → users (self-ref), NULL ON DELETE |
| employee_number | string | UNIQUE, nullable |
| phone | string | nullable |
| is_active | boolean | indexed |
| notification_preferences | json | nullable |
| email_verified_at | timestamp | nullable |
| remember_token | string | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

### Perintah Dinas & Absensi

#### `duty_locations`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| name | string | |
| address | text | |
| latitude | decimal(10,7) | |
| longitude | decimal(10,7) | |
| radius_meters | unsignedInt | default 100 |
| is_active | boolean | indexed |
| created_at | timestamp | |
| updated_at | timestamp | |

#### `duty_trips`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| employee_id | bigint | FK → users, CASCADE |
| manager_id | bigint | FK → users, RESTRICT |
| duty_location_id | bigint | FK → duty_locations, NULL ON DELETE |
| destination | string | |
| purpose | text | |
| starts_at | datetime | |
| ends_at | datetime | |
| location_name | string | |
| address | text | |
| latitude | decimal(10,7) | |
| longitude | decimal(10,7) | |
| radius_meters | unsignedInt | |
| supporting_document_path | string | nullable |
| status | string | indexed; DutyTripStatus enum |
| rejection_reason | text | nullable |
| approved_at | timestamp | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |
| INDEX | (employee_id, starts_at) | |
| INDEX | (manager_id, status) | |

#### `attendances`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| client_uuid | uuid | UNIQUE |
| duty_trip_id | bigint | FK → duty_trips, CASCADE |
| employee_id | bigint | FK → users, CASCADE |
| attendance_date | date | |
| captured_at | datetime | |
| latitude | decimal(10,7) | |
| longitude | decimal(10,7) | |
| accuracy_meters | unsignedInt | nullable |
| distance_meters | unsignedInt | |
| photo_path | string | |
| face_descriptor | text | nullable |
| status | string | indexed; AttendanceStatus enum |
| review_reason | text | nullable |
| mock_location_suspected | boolean | default false |
| synced_at | timestamp | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |
| UNIQUE | (duty_trip_id, employee_id, attendance_date) | |

### KPI & Merit

#### `review_periods`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| name | string | |
| starts_at | date | |
| ends_at | date | |
| kpi_weight | unsignedTinyInt | default 40 |
| discipline_weight | unsignedTinyInt | default 20 |
| manager_weight | unsignedTinyInt | default 20 |
| review_360_weight | unsignedTinyInt | default 20 |
| base_bonus | decimal(15,2) | default 0 |
| is_active | boolean | indexed |
| created_at | timestamp | |
| updated_at | timestamp | |

#### `kpi_indicators`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| review_period_id | bigint | FK → review_periods, CASCADE |
| name | string | |
| description | text | nullable |
| unit | string | nullable |
| weight | unsignedTinyInt | |
| created_at | timestamp | |
| updated_at | timestamp | |
| UNIQUE | (review_period_id, name) | |

#### `employee_kpis`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| review_period_id | bigint | FK → review_periods, CASCADE |
| kpi_indicator_id | bigint | FK → kpi_indicators, CASCADE |
| employee_id | bigint | FK → users, CASCADE |
| manager_id | bigint | FK → users, RESTRICT |
| target | decimal(15,2) | |
| achievement | decimal(15,2) | default 0 |
| notes | text | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |
| UNIQUE | (kpi_indicator_id, employee_id) | |
| INDEX | (manager_id, review_period_id) | |

#### `performance_reviews`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| review_period_id | bigint | FK → review_periods, CASCADE |
| reviewer_id | bigint | FK → users, CASCADE |
| reviewee_id | bigint | FK → users, CASCADE |
| type | string | ReviewType enum |
| score | unsignedTinyInt | 1-5 |
| comments | text | nullable |
| submitted_at | timestamp | |
| created_at | timestamp | |
| updated_at | timestamp | |
| UNIQUE | (review_period_id, reviewer_id, reviewee_id, type) | |

#### `merit_results`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| review_period_id | bigint | FK → review_periods, CASCADE |
| employee_id | bigint | FK → users, CASCADE |
| kpi_score | decimal(6,2) | |
| discipline_score | decimal(6,2) | |
| manager_score | decimal(6,2) | |
| review_360_score | decimal(6,2) | |
| total_score | decimal(6,2) | |
| estimated_bonus | decimal(15,2) | |
| calculated_at | timestamp | nullable |
| manager_verified_by | bigint | FK → users, NULL ON DELETE |
| manager_verified_at | timestamp | nullable |
| hr_verified_by | bigint | FK → users, NULL ON DELETE |
| hr_verified_at | timestamp | nullable |
| published_at | timestamp | nullable, indexed |
| created_at | timestamp | |
| updated_at | timestamp | |
| UNIQUE | (review_period_id, employee_id) | |

### Pengembangan Karir

#### `competencies`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| name | string | UNIQUE |
| description | text | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

#### `position_competency`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| position_id | bigint | FK → positions, CASCADE |
| competency_id | bigint | FK → competencies, CASCADE |
| required_level | unsignedTinyInt | 1-5 |
| created_at | timestamp | |
| updated_at | timestamp | |
| UNIQUE | (position_id, competency_id) | |

#### `employee_competencies`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| user_id | bigint | FK → users, CASCADE |
| competency_id | bigint | FK → competencies, CASCADE |
| level | unsignedTinyInt | 1-5 |
| assessed_at | date | |
| notes | text | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |
| UNIQUE | (user_id, competency_id) | |

#### `career_goals`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| user_id | bigint | FK → users, CASCADE, UNIQUE |
| target_position_id | bigint | FK → positions, CASCADE |
| created_at | timestamp | |
| updated_at | timestamp | |

### Pelatihan & Mentoring

#### `trainings`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| competency_id | bigint | FK → competencies, NULL ON DELETE |
| name | string | |
| provider | string | nullable |
| type | string | |
| description | text | nullable |
| starts_at | datetime | nullable |
| ends_at | datetime | nullable |
| is_active | boolean | indexed |
| created_at | timestamp | |
| updated_at | timestamp | |

#### `training_requests`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| user_id | bigint | FK → users, CASCADE |
| training_id | bigint | FK → trainings, CASCADE |
| manager_id | bigint | FK → users, RESTRICT |
| status | string | indexed; TrainingRequestStatus enum |
| reason | text | nullable |
| manager_notes | text | nullable |
| hr_result | text | nullable |
| requested_at | timestamp | |
| manager_decided_at | timestamp | nullable |
| hr_verified_by | bigint | FK → users, NULL ON DELETE |
| hr_verified_at | timestamp | nullable |
| completed_at | timestamp | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |
| UNIQUE | (user_id, training_id) | |

#### `mentorings`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| employee_id | bigint | FK → users, CASCADE |
| manager_id | bigint | FK → users, RESTRICT |
| status | string | indexed; MentoringStatus enum |
| topic | string | |
| target | text | |
| requested_at | datetime | |
| scheduled_at | datetime | nullable |
| manager_notes | text | nullable |
| result | text | nullable |
| follow_up | text | nullable |
| completed_at | timestamp | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

### Audit & Konfigurasi

#### `activity_logs`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| user_id | bigint | FK → users, NULL ON DELETE |
| action | string | indexed |
| subject_type | string | nullable (morphs) |
| subject_id | bigint | nullable (morphs) |
| data | json | nullable |
| created_at | timestamp | useCurrent |

#### `approval_chains`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| module | string(50) | UNIQUE |
| name | string | |
| steps | json | |
| is_active | boolean | default true |
| created_at | timestamp | |
| updated_at | timestamp | |

#### `push_subscriptions`
Standard Web Push table: endpoint, keys (auth, p256dh), user_id FK.

### Notifications (Laravel default)
`notifications` table: id (uuid), type, notifiable_type, notifiable_id, data (json), read_at.

### Enums & Domain Values

| Enum | Values | Used By |
|------|--------|---------|
| UserRole | Employee, Manager, Hr | users.role |
| DutyTripStatus | Pending, Approved, Completed, Cancelled, Rejected | duty_trips.status |
| AttendanceStatus | Valid, OutsideRadius, Late, NeedsReview | attendances.status |
| ReviewType | ManagerToEmployee, EmployeeToManager, Peer | performance_reviews.type |
| MentoringStatus | Pending, Approved, Rejected, Completed | mentorings.status |
| TrainingRequestStatus | PendingManager, PendingHr, Approved, Rejected, Completed | training_requests.status |
