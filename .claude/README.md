# Claude Code Skills & Documentation

This directory contains specialized skills and comprehensive documentation to help Claude Code work efficiently with this Training Management System.

## 📁 Directory Structure

```
.claude/
├── skills/              # Specialized skills for common tasks
│   ├── module-generator.md
│   ├── permission-manager.md
│   ├── testing-guide.md
│   └── laravel-12-expert.md
├── docs/               # Detailed documentation
│   ├── architecture.md
│   ├── database-conventions.md
│   └── frontend-guide.md
└── README.md          # This file
```

## 🎯 Skills

Skills are specialized guides that Claude Code references when handling specific tasks. They provide step-by-step workflows and best practices.

### Available Skills

| Skill | Purpose | When to Reference |
|-------|---------|------------------|
| **module-generator.md** | Creating new modules following the custom modular architecture | User wants to add a new feature/module, create CRUD resources, or scaffold new functionality |
| **permission-manager.md** | Managing RBAC permissions and roles using Spatie Laravel Permission | User wants to add/modify permissions, restrict access, or debug permission issues |
| **testing-guide.md** | Creating and running PHPUnit tests for features and units | User requests tests, wants to improve test coverage, or is debugging test failures |
| **laravel-12-expert.md** | Laravel 12 specific patterns and breaking changes | Working with middleware, scheduling, service providers, or encountering Laravel 12 specific issues |

### How to Use Skills

When Claude Code encounters a task that matches a skill's purpose, it should:

1. **Reference the skill** to understand the established patterns
2. **Follow the conventions** outlined in the skill
3. **Use the provided examples** as templates
4. **Complete the checklist** at the end of each skill

**Example:**
- User: "Add a new Course module with CRUD operations"
- Claude: References `module-generator.md` → Follows the module structure → Creates controller, model, views, routes → Adds permissions via `permission-manager.md` → Creates tests via `testing-guide.md`

## 📚 Documentation

Comprehensive reference documentation covering architecture, conventions, and best practices.

### Available Documentation

| Document | Purpose | Key Topics |
|----------|---------|------------|
| **architecture.md** | System architecture and design patterns | Module structure, data flow, permission system, service providers, deployment |
| **database-conventions.md** | Database design patterns and Eloquent conventions | Migrations, relationships, indexing, seeding, factories, query optimization |
| **frontend-guide.md** | Frontend integration and UI patterns | Tailwind CSS v4, Alpine.js, Blade components, Vite, forms, layouts |

### How to Use Documentation

Documentation should be referenced for:

- **Understanding system design** before implementing features
- **Looking up conventions** when unsure about naming or structure
- **Finding examples** of common patterns
- **Troubleshooting** issues

**Example:**
- User: "How should I structure a many-to-many relationship?"
- Claude: References `database-conventions.md` → Checks BelongsToMany pattern → Implements with pivot table

## 🚀 Quick Reference Guide

### For New Module Creation

1. **Read:** `module-generator.md` - Complete workflow
2. **Reference:** `architecture.md` - Module structure
3. **Reference:** `permission-manager.md` - Add permissions
4. **Reference:** `testing-guide.md` - Create tests
5. **Reference:** `database-conventions.md` - Create migrations/models

### For Permission Management

1. **Read:** `permission-manager.md` - Complete guide
2. **Reference:** `architecture.md` - Permission system architecture
3. **Apply:** Middleware patterns in routes
4. **Test:** Permission checks in tests

### For Testing

1. **Read:** `testing-guide.md` - Testing patterns
2. **Reference:** `database-conventions.md` - Factory patterns
3. **Write:** Feature tests for CRUD
4. **Verify:** Run `composer test`

### For Laravel 12 Issues

1. **Read:** `laravel-12-expert.md` - Breaking changes
2. **Check:** `bootstrap/app.php` - Middleware/schedule registration
3. **Verify:** No Kernel files exist
4. **Update:** Follow migration checklist if needed

### For Frontend Development

1. **Read:** `frontend-guide.md` - Complete frontend stack
2. **Important:** Use `@import "tailwindcss"` (not `@tailwind`)
3. **Reference:** Blade component patterns
4. **Run:** `npm run dev` for development

## 📋 Common Workflows

### Creating a New Feature Module

```
1. Generate module structure
   → php artisan module:scaffold {ModuleName}
   → Reference: module-generator.md

2. Create database structure
   → Create migration
   → Define model with relationships
   → Reference: database-conventions.md

3. Add permissions
   → Update SyncPermissionsAndRoles.php
   → Run php artisan permissions:sync
   → Reference: permission-manager.md

4. Create views and routes
   → Follow Blade patterns
   → Apply middleware to routes
   → Reference: frontend-guide.md

5. Write tests
   → Create feature tests
   → Test CRUD operations
   → Test permissions
   → Reference: testing-guide.md

6. Verify
   → Run composer test
   → Run vendor/bin/pint --dirty
   → Test manually in browser
```

### Debugging Permission Issues

```
1. Check permission exists
   → Review SyncPermissionsAndRoles.php
   → Reference: permission-manager.md

2. Verify user has permission
   → Check model_has_permissions table
   → Or check via role_has_permissions

3. Clear permission cache
   → php artisan cache:forget spatie.permission.cache

4. Re-sync permissions
   → php artisan permissions:sync --reset
   → Reference: permission-manager.md
```

### Optimizing Database Queries

```
1. Identify N+1 queries
   → Use Laravel Debugbar or logs
   → Reference: database-conventions.md

2. Add eager loading
   → Model::with('relation')->get()
   → Reference examples in database-conventions.md

3. Add appropriate indexes
   → Create migration
   → Add index to frequently queried columns
   → Reference: database-conventions.md

4. Test performance
   → Measure query count before/after
   → Use chunking for large datasets
```

## 🎨 Code Style Standards

All code must follow these standards:

### Laravel Conventions

- PSR-12 coding standard
- Type hints for all parameters and return types
- Explicit visibility (public/protected/private)
- Docblocks for complex methods only

### Module Conventions

- PascalCase for module names
- kebab-case for routes
- snake_case for database tables/columns
- Explicit relationship return types

### Formatting

**Before committing, always run:**
```bash
vendor/bin/pint --dirty  # Format modified files only
# OR
vendor/bin/pint          # Format all files
```

## ⚠️ Common Pitfalls to Avoid

### 1. Laravel 12 Specific

- ❌ Don't create `app/Http/Kernel.php` or `app/Console/Kernel.php`
- ✅ Register middleware in `bootstrap/app.php`
- ❌ Don't use `env()` in code (only in config files)
- ✅ Use `config()` helper

### 2. Tailwind CSS v4

- ❌ Don't use `@tailwind` directives
- ✅ Use `@import "tailwindcss"`

### 3. Migrations

- ❌ Don't modify columns without ALL attributes
- ✅ Include all attributes when using `->change()`

### 4. Permissions

- ❌ Don't manually insert permissions in database
- ✅ Use `php artisan permissions:sync`

### 5. Testing

- ❌ Don't create data manually in tests
- ✅ Use factories for test data

### 6. Module Structure

- ❌ Don't put everything in `app/`
- ✅ Follow modular structure in `modules/`

## 📊 Decision Matrix

When unsure which approach to take:

| Task | Use | Not |
|------|-----|-----|
| Create CRUD feature | Module generator pattern | Generic controller in app/ |
| Add frontend interactivity | Alpine.js | jQuery, vanilla JS for complex logic |
| Style components | Tailwind utilities | Custom CSS file |
| Validate forms | Form Request classes | Inline validation in controller |
| Test features | Feature tests | Manual browser testing only |
| Protect routes | Middleware + permissions | Manual checks in every method |
| Query relationships | Eager loading | N+1 queries |
| Large datasets | Pagination/chunking | Get all records |

## 🔧 Troubleshooting Quick Links

| Issue | Solution | Reference |
|-------|----------|-----------|
| "Vite manifest not found" | Run `npm run build` or `npm run dev` | frontend-guide.md |
| "Permission denied" | Run `php artisan permissions:sync` | permission-manager.md |
| "Route not found" | Check ServiceProvider registered | laravel-12-expert.md |
| Tests failing | Check factories and RefreshDatabase | testing-guide.md |
| N+1 queries | Add eager loading with `->with()` | database-conventions.md |
| Middleware not working | Check `bootstrap/app.php` registration | laravel-12-expert.md |

## 📝 Maintenance

### Updating Skills/Docs

When updating these files:

1. **Maintain consistency** across all documents
2. **Update cross-references** if structure changes
3. **Add examples** for new patterns
4. **Keep it practical** - focus on real use cases
5. **Test examples** before documenting

### When to Create New Skills

Create a new skill when:

- A task is frequently repeated (3+ times)
- A task has specific, complex workflow
- Multiple steps must be followed in order
- Common mistakes happen without guidance

### When to Update Documentation

Update docs when:

- New patterns emerge in codebase
- Best practices change
- New tools/packages are added
- Common questions arise repeatedly

## 🎓 Learning Path for New Contributors

For someone new to this codebase:

1. **Start:** Read `architecture.md` to understand the system
2. **Learn:** Review `module-generator.md` to understand module pattern
3. **Practice:** Create a simple CRUD module following the guides
4. **Deep Dive:** Study `database-conventions.md` and `permission-manager.md`
5. **Frontend:** Review `frontend-guide.md` for UI development
6. **Advanced:** Read `laravel-12-expert.md` for framework specifics
7. **Quality:** Follow `testing-guide.md` for test-driven development

## 🤝 Contributing

When working with this codebase:

1. **Always reference relevant skills/docs** before starting
2. **Follow established patterns** even if you prefer alternatives
3. **Update skills/docs** if you find gaps or errors
4. **Ask questions** if conventions are unclear
5. **Format code** with Laravel Pint before committing
6. **Write tests** for new features
7. **Run full test suite** before submitting changes

## 📖 External Resources

- **Laravel 12 Docs:** https://laravel.com/docs
- **Spatie Permission:** https://spatie.be/docs/laravel-permission
- **Tailwind CSS v4:** https://tailwindcss.com/docs
- **Alpine.js:** https://alpinejs.dev
- **PHPUnit:** https://phpunit.de/documentation.html

## 🎯 Goals of This Documentation

1. **Reduce cognitive load** - Clear patterns to follow
2. **Ensure consistency** - All code looks like one person wrote it
3. **Speed up development** - No need to figure out structure every time
4. **Improve quality** - Best practices baked in
5. **Onboard faster** - New contributors can be productive quickly
6. **Maintain standards** - Documentation as single source of truth

---

**Remember:** These skills and docs are living documents. As the project evolves, keep them updated to reflect current best practices.
