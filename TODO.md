# TODO.md

Version: 1.0

Status: Planning

Project:
Giờ chuẩn giảng viên

---

# PROJECT ROADMAP

Epic-01
Project Analysis

Epic-02
Database

Epic-03
Backend

Epic-04
Frontend

Epic-05
Calculation Engine

Epic-06
Reports

Epic-07
Testing

Epic-08
Deployment

---

# STATUS

TODO

IN PROGRESS

REVIEW

DONE

BLOCKED

---

# PRIORITY

P0

Critical

P1

High

P2

Medium

P3

Low

---

# =======================================================
# EPIC-01
# PROJECT ANALYSIS
# =======================================================

TASK-001

Status

TODO

Priority

P0

Tên

Đọc AI_CONTEXT.md

Done

AI hiểu toàn bộ project.

---

TASK-002

Đọc PROJECT_STRUCTURE.md

---

TASK-003

Đọc BUSINESS_RULES.md

---

TASK-004

Đọc FEATURE_SPEC.md

---

TASK-005

Review Route hiện có.

---

TASK-006

Review Database.

---

TASK-007

Review Module Instructor.

---

TASK-008

Review TrainingSchedule.

---

TASK-009

Review TeachingAssignment.

---

TASK-010

Review Permission.

---

# =======================================================
# EPIC-02
# DATABASE
# =======================================================

TASK-011

Thiết kế ERD.

---

TASK-012

Review Database hiện tại.

---

TASK-013

Migration

standard_object_types

---

TASK-014

Migration

standard_positions

---

TASK-015

Migration

standard_hour_norms

---

TASK-016

Migration

research_hour_norms

---

TASK-017

Migration

conversion_categories

---

TASK-018

Migration

research_categories

---

TASK-019

Migration

conversion_records

---

TASK-020

Migration

research_records

---

TASK-021

Migration

yearly_standard_results

---

TASK-022

Foreign Keys

---

TASK-023

Index

---

TASK-024

Seeder

Object Types

---

TASK-025

Seeder

Positions

---

TASK-026

Seeder

Research Categories

---

TASK-027

Seeder

Conversion Categories

---

TASK-028

Permission Seeder

---

TASK-029

Sync Permission

---

TASK-030

Migration Test

---

# =======================================================
# EPIC-03
# MODELS
# =======================================================

TASK-031

ObjectType Model

---

TASK-032

Position Model

---

TASK-033

HourNorm Model

---

TASK-034

ResearchNorm Model

---

TASK-035

ConversionCategory Model

---

TASK-036

ConversionRecord Model

---

TASK-037

ResearchCategory Model

---

TASK-038

ResearchRecord Model

---

TASK-039

YearlyResult Model

---

TASK-040

Relationship

---

TASK-041

Scopes

---

TASK-042

Accessor

---

TASK-043

Mutator

---

# =======================================================
# EPIC-04
# REQUESTS
# =======================================================

TASK-044

StoreObjectTypeRequest

---

TASK-045

UpdateObjectTypeRequest

---

TASK-046

StorePositionRequest

---

TASK-047

UpdatePositionRequest

---

TASK-048

StoreHourNormRequest

---

TASK-049

UpdateHourNormRequest

---

TASK-050

StoreConversionRequest

---

TASK-051

UpdateConversionRequest

---

TASK-052

StoreResearchRequest

---

TASK-053

UpdateResearchRequest

---

# =======================================================
# EPIC-05
# SERVICES
# =======================================================

TASK-054

ObjectTypeService

---

TASK-055

PositionService

---

TASK-056

HourNormService

---

TASK-057

ResearchNormService

---

TASK-058

ConversionCategoryService

---

TASK-059

ConversionService

---

TASK-060

ResearchCategoryService

---

TASK-061

ResearchService

---

TASK-062

SynchronizationService

---

TASK-063

CalculationService

---

TASK-064

ReportService

---

# =======================================================
# EPIC-06
# CONTROLLERS
# =======================================================

TASK-065

ObjectTypeController

---

TASK-066

PositionController

---

TASK-067

HourNormController

---

TASK-068

ResearchNormController

---

TASK-069

ConversionCategoryController

---

TASK-070

ConversionController

---

TASK-071

ResearchCategoryController

---

TASK-072

ResearchController

---

TASK-073

CalculationController

---

TASK-074

ReportController

---

# =======================================================
# EPIC-07
# ROUTES
# =======================================================

TASK-075

Route Group

---

TASK-076

CRUD Route

Object Types

---

TASK-077

CRUD Route

Positions

---

TASK-078

CRUD Route

Hour Norms

---

TASK-079

CRUD Route

Research

---

TASK-080

Report Route

---

# =======================================================
# EPIC-08
# VIEWS
# =======================================================

TASK-081

Dashboard

---

TASK-082

Object Types

---

TASK-083

Positions

---

TASK-084

Hour Norms

---

TASK-085

Research

---

TASK-086

Conversion

---

TASK-087

Calculation

---

TASK-088

Reports

---

# =======================================================
# EPIC-09
# JAVASCRIPT
# =======================================================

TASK-089

Ajax CRUD

---

TASK-090

Modal

---

TASK-091

Datatable

---

TASK-092

Validation

---

TASK-093

Select2

---

TASK-094

SweetAlert

---

TASK-095

Toast

---

# =======================================================
# EPIC-10
# CALCULATION ENGINE
# =======================================================

TASK-096

Teaching Hours

---

TASK-097

Conversion Hours

---

TASK-098

Research Hours

---

TASK-099

Business Rules

---

TASK-100

Calculation Engine

---

TASK-101

Result Engine

---

TASK-102

Approval

---

TASK-103

Lock Year

---

# =======================================================
# EPIC-11
# REPORT
# =======================================================

TASK-104

Summary Report

---

TASK-105

Teacher Report

---

TASK-106

Faculty Report

---

TASK-107

Dashboard

---

TASK-108

Excel

---

TASK-109

PDF

---

TASK-110

Print

---

# =======================================================
# EPIC-12
# TESTING
# =======================================================

TASK-111

CRUD Test

---

TASK-112

Permission Test

---

TASK-113

Calculation Test

---

TASK-114

Performance Test

---

TASK-115

Security Test

---

TASK-116

Regression Test

---

TASK-117

Bug Fix

---

# =======================================================
# EPIC-13
# DEPLOYMENT
# =======================================================

TASK-118

Migration

---

TASK-119

Permission Sync

---

TASK-120

Smoke Test

---

TASK-121

Release

---

TASK-122

Production Verification

---

# =======================================================
# AI WORKFLOW
# =======================================================

Mỗi khi AI nhận một yêu cầu mới phải thực hiện theo thứ tự:

1. Đọc:
   - AI_CONTEXT.md
   - PROJECT_STRUCTURE.md
   - BUSINESS_RULES.md
   - FEATURE_SPEC.md
   - TODO.md

2. Xác định Epic.

3. Xác định Task.

4. Kiểm tra Dependency.

5. Liệt kê file sẽ sửa.

6. Đánh giá ảnh hưởng.

7. Chờ người dùng xác nhận.

8. Thực hiện code.

9. Self Review.

10. Đánh dấu trạng thái Task.

---

# TASK STATUS UPDATE

Ví dụ:

TASK-063

Status:

DONE

Completed At:

2026-07-05

Files:

Services/CalculationService.php

Controllers/CalculationController.php

Tests/CalculationServiceTest.php

Notes:

Hoàn thành Calculation Engine theo BUSINESS_RULES.md