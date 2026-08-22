# Frontend Integration Guide

## Overview

The Training Management System uses a modern frontend stack with server-side rendering (Blade) enhanced by Tailwind CSS v4 and Alpine.js for interactivity.

## Tech Stack

- **Blade Templates** - Laravel's server-side templating engine
- **Tailwind CSS v4** - Utility-first CSS framework
- **Alpine.js** - Lightweight JavaScript framework for interactivity
- **Vite** - Next-generation frontend tooling with HMR (Hot Module Replacement)

## Project Structure

```
resources/
├── css/
│   └── app.css              # Main CSS (Tailwind imports)
├── js/
│   └── app.js               # Main JavaScript entry
└── views/
    ├── layouts/
    │   ├── admin.blade.php  # Admin panel layout
    │   └── app.blade.php    # Public site layout
    ├── components/          # Reusable Blade components
    └── ...                  # Module views

public/
└── build/                   # Compiled assets (Vite output)
    ├── manifest.json
    ├── assets/
    │   ├── app-[hash].js
    │   └── app-[hash].css
```

## Vite Configuration

### vite.config.js

```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js'
            ],
            refresh: true,  // Auto-refresh on file changes
        }),
    ],
});
```

### Development vs Production

**Development (Hot Module Replacement):**
```bash
npm run dev
# Vite dev server runs on http://localhost:5173
# Assets served directly from Vite with HMR
```

**Production (Build):**
```bash
npm run build
# Compiles assets to public/build/
# Minified, optimized, fingerprinted
```

### In Blade Templates

```blade
<!DOCTYPE html>
<html>
<head>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <!-- Your content -->
</body>
</html>
```

## Tailwind CSS v4

### Important: v4 Syntax Changes

**Tailwind CSS v4 uses `@import` instead of `@tailwind` directives:**

**❌ Old Tailwind v3 syntax (DON'T USE):**
```css
@tailwind base;
@tailwind components;
@tailwind utilities;
```

**✅ Tailwind v4 syntax (USE THIS):**
```css
/* resources/css/app.css */
@import "tailwindcss";

/* Your custom styles */
@layer components {
  .btn-primary {
    @apply bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded;
  }
}

@layer utilities {
  .custom-scrollbar::-webkit-scrollbar {
    width: 8px;
  }
}
```

### Tailwind Configuration

**tailwind.config.js:**
```javascript
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./modules/**/*.blade.php",  // Include module views
  ],
  theme: {
    extend: {
      colors: {
        primary: '#3490dc',
        secondary: '#ffed4e',
      },
    },
  },
  plugins: [],
}
```

### Common Utility Classes

```blade
{{-- Layout --}}
<div class="container mx-auto px-4">
<div class="flex justify-between items-center">
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">

{{-- Typography --}}
<h1 class="text-3xl font-bold text-gray-900">
<p class="text-sm text-gray-600">

{{-- Spacing --}}
<div class="p-4 m-2">         {{-- padding, margin --}}
<div class="mt-4 mb-2">       {{-- margin-top, margin-bottom --}}

{{-- Colors --}}
<button class="bg-blue-500 text-white hover:bg-blue-600">
<div class="border border-gray-300">

{{-- Responsive --}}
<div class="w-full md:w-1/2 lg:w-1/3">
<div class="hidden md:block">  {{-- Hidden on mobile, visible on md+ --}}
```

### Custom Components with Tailwind

```css
/* resources/css/app.css */
@import "tailwindcss";

@layer components {
  .btn {
    @apply inline-flex items-center px-4 py-2 border rounded-md font-semibold text-sm transition;
  }

  .btn-primary {
    @apply btn bg-blue-500 text-white hover:bg-blue-600 border-blue-500;
  }

  .btn-danger {
    @apply btn bg-red-500 text-white hover:bg-red-600 border-red-500;
  }

  .card {
    @apply bg-white rounded-lg shadow-md p-6;
  }

  .input-field {
    @apply w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500;
  }
}
```

**Usage in Blade:**
```blade
<button class="btn-primary">Save</button>
<div class="card">
  <input type="text" class="input-field" />
</div>
```

## Alpine.js Integration

### Setup

**resources/js/app.js:**
```javascript
import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();
```

### Basic Alpine.js Patterns

**Toggle/Show/Hide:**
```blade
<div x-data="{ open: false }">
    <button @click="open = !open">Toggle</button>

    <div x-show="open">
        Content to toggle
    </div>
</div>
```

**Dropdown Menu:**
```blade
<div x-data="{ open: false }" @click.away="open = false">
    <button @click="open = !open">
        Menu
    </button>

    <div x-show="open"
         x-transition
         class="absolute bg-white shadow-lg rounded">
        <a href="#">Item 1</a>
        <a href="#">Item 2</a>
    </div>
</div>
```

**Modal:**
```blade
<div x-data="{ showModal: false }">
    <button @click="showModal = true">Open Modal</button>

    <div x-show="showModal"
         x-cloak
         class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center">
        <div @click.away="showModal = false"
             class="bg-white rounded-lg p-6">
            <h3>Modal Title</h3>
            <p>Modal content</p>
            <button @click="showModal = false">Close</button>
        </div>
    </div>
</div>

<style>
[x-cloak] { display: none !important; }
</style>
```

**Form Validation:**
```blade
<div x-data="{
    email: '',
    isValid: false
}" x-init="$watch('email', value => isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value))">
    <input type="email"
           x-model="email"
           class="input-field"
           :class="{ 'border-red-500': email && !isValid }">

    <p x-show="email && !isValid"
       class="text-red-500 text-sm mt-1">
        Invalid email format
    </p>
</div>
```

**Tabs:**
```blade
<div x-data="{ activeTab: 'tab1' }">
    <div class="flex border-b">
        <button @click="activeTab = 'tab1'"
                :class="{ 'border-b-2 border-blue-500': activeTab === 'tab1' }"
                class="px-4 py-2">
            Tab 1
        </button>
        <button @click="activeTab = 'tab2'"
                :class="{ 'border-b-2 border-blue-500': activeTab === 'tab2' }"
                class="px-4 py-2">
            Tab 2
        </button>
    </div>

    <div x-show="activeTab === 'tab1'">Content 1</div>
    <div x-show="activeTab === 'tab2'">Content 2</div>
</div>
```

## Blade Components

### Creating Components

```bash
php artisan make:component Button
# Creates: app/View/Components/Button.php
# Creates: resources/views/components/button.blade.php
```

**Or anonymous component (no class):**
```bash
# Just create the view file
resources/views/components/alert.blade.php
```

### Component Example

**Class-based Component:**

```php
// app/View/Components/Button.php
namespace App\View\Components;

use Illuminate\View\Component;

class Button extends Component
{
    public function __construct(
        public string $type = 'button',
        public string $variant = 'primary',
    ) {}

    public function render()
    {
        return view('components.button');
    }
}
```

```blade
{{-- resources/views/components/button.blade.php --}}
<button type="{{ $type }}"
        {{ $attributes->merge([
            'class' => $variant === 'primary'
                ? 'btn-primary'
                : 'btn-secondary'
        ]) }}>
    {{ $slot }}
</button>
```

**Usage:**
```blade
<x-button type="submit" variant="primary">
    Save Changes
</x-button>

<x-button variant="secondary" class="ml-2">
    Cancel
</x-button>
```

**Anonymous Component:**

```blade
{{-- resources/views/components/alert.blade.php --}}
@props(['type' => 'info'])

<div {{ $attributes->merge([
    'class' => 'p-4 rounded-lg ' . match($type) {
        'success' => 'bg-green-100 text-green-800',
        'error' => 'bg-red-100 text-red-800',
        'warning' => 'bg-yellow-100 text-yellow-800',
        default => 'bg-blue-100 text-blue-800',
    }
]) }}>
    {{ $slot }}
</div>
```

**Usage:**
```blade
<x-alert type="success">
    Student created successfully!
</x-alert>

<x-alert type="error">
    Failed to save student.
</x-alert>
```

## Layouts

### Admin Layout

```blade
{{-- resources/views/layouts/admin.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Panel')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-gray-100">
    <div class="flex">
        {{-- Sidebar --}}
        <aside class="w-64 bg-gray-800 text-white min-h-screen">
            @include('layouts.partials.sidebar')
        </aside>

        {{-- Main Content --}}
        <main class="flex-1">
            {{-- Header --}}
            <header class="bg-white shadow">
                @include('layouts.partials.header')
            </header>

            {{-- Flash Messages --}}
            @if(session('success'))
                <x-alert type="success">{{ session('success') }}</x-alert>
            @endif

            @if(session('error'))
                <x-alert type="error">{{ session('error') }}</x-alert>
            @endif

            {{-- Page Content --}}
            <div class="container mx-auto p-6">
                @yield('content')
            </div>
        </main>
    </div>

    @stack('scripts')
</body>
</html>
```

### Using Layout in Module View

```blade
{{-- modules/Student/Views/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'Students')

@push('styles')
    <style>
        /* Custom styles for this page */
    </style>
@endpush

@section('content')
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Students</h1>

        @can('create.student')
            <a href="{{ route('students.create') }}" class="btn-primary">
                Add Student
            </a>
        @endcan
    </div>

    <div class="card">
        <table class="w-full">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Class</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $student)
                    <tr>
                        <td>{{ $student->name }}</td>
                        <td>{{ $student->email }}</td>
                        <td>{{ $student->class?->name }}</td>
                        <td>
                            @can('edit.student')
                                <a href="{{ route('students.edit', $student) }}">
                                    Edit
                                </a>
                            @endcan

                            @can('delete.student')
                                <form method="POST"
                                      action="{{ route('students.destroy', $student) }}"
                                      onsubmit="return confirm('Are you sure?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600">
                                        Delete
                                    </button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-gray-500">
                            No students found
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $students->links() }}  {{-- Pagination --}}
    </div>
@endsection

@push('scripts')
    <script>
        // Custom scripts for this page
    </script>
@endpush
```

## Forms

### Standard Form Pattern

```blade
<form method="POST" action="{{ route('students.store') }}" class="space-y-4">
    @csrf

    {{-- Name Field --}}
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700">
            Name
        </label>
        <input type="text"
               id="name"
               name="name"
               value="{{ old('name') }}"
               class="input-field @error('name') border-red-500 @enderror"
               required>
        @error('name')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Email Field --}}
    <div>
        <label for="email" class="block text-sm font-medium text-gray-700">
            Email
        </label>
        <input type="email"
               id="email"
               name="email"
               value="{{ old('email') }}"
               class="input-field @error('email') border-red-500 @enderror"
               required>
        @error('email')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Select Dropdown --}}
    <div>
        <label for="class_id" class="block text-sm font-medium text-gray-700">
            Class
        </label>
        <select id="class_id"
                name="class_id"
                class="input-field @error('class_id') border-red-500 @enderror">
            <option value="">Select a class</option>
            @foreach($classes as $class)
                <option value="{{ $class->id }}"
                        {{ old('class_id') == $class->id ? 'selected' : '' }}>
                    {{ $class->name }}
                </option>
            @endforeach
        </select>
        @error('class_id')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Checkbox --}}
    <div class="flex items-center">
        <input type="checkbox"
               id="is_active"
               name="is_active"
               value="1"
               {{ old('is_active') ? 'checked' : '' }}
               class="rounded border-gray-300">
        <label for="is_active" class="ml-2 text-sm text-gray-700">
            Active
        </label>
    </div>

    {{-- Submit Buttons --}}
    <div class="flex justify-end space-x-2">
        <a href="{{ route('students.index') }}" class="btn-secondary">
            Cancel
        </a>
        <button type="submit" class="btn-primary">
            Create Student
        </button>
    </div>
</form>
```

## Common UI Patterns

### Data Tables

```blade
<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    Name
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    Email
                </th>
                <th class="px-6 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @foreach($students as $student)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">{{ $student->name }}</td>
                    <td class="px-6 py-4">{{ $student->email }}</td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('students.edit', $student) }}"
                           class="text-blue-600 hover:text-blue-900">
                            Edit
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{ $students->links() }}
```

### Pagination

```blade
{{-- Default pagination --}}
{{ $students->links() }}

{{-- Custom Tailwind pagination (already styled) --}}
{{-- Just use ->links() and it will use Tailwind by default in Laravel 12 --}}
```

### Loading States (with Alpine.js)

```blade
<div x-data="{ loading: false }">
    <button @click="loading = true; $el.closest('form').submit()"
            :disabled="loading"
            class="btn-primary">
        <span x-show="!loading">Submit</span>
        <span x-show="loading">Loading...</span>
    </button>
</div>
```

## Asset Management

### Including Images

```blade
{{-- Public images --}}
<img src="{{ asset('images/logo.png') }}" alt="Logo">

{{-- From storage --}}
<img src="{{ Storage::url('avatars/user.jpg') }}" alt="Avatar">
```

### Custom JavaScript/CSS for Module

```javascript
// resources/js/modules/student.js
export function initStudentForm() {
    // Student-specific JavaScript
}
```

```javascript
// resources/js/app.js
import { initStudentForm } from './modules/student';

if (document.querySelector('.student-form')) {
    initStudentForm();
}
```

## Troubleshooting

### Vite Manifest Error

**Error:** "Vite manifest not found"

**Solution:**
```bash
npm run build  # Production
# OR
npm run dev    # Development
```

### Styles Not Updating

1. Check Vite is running: `npm run dev`
2. Clear browser cache
3. Check Tailwind config includes your files
4. Rebuild: `npm run build`

### Alpine.js Not Working

1. Ensure Alpine is imported in `app.js`
2. Check `@vite` directive in layout
3. Look for JavaScript errors in browser console
4. Ensure `Alpine.start()` is called

## Best Practices

1. **Always use Blade components** for reusable UI elements
2. **Use Tailwind utilities** instead of custom CSS when possible
3. **Use Alpine.js** for simple interactivity (avoid jQuery)
4. **Run `npm run dev`** during development for HMR
5. **Keep JavaScript minimal** - leverage server-side rendering
6. **Use `@vite` directive** in layouts, not individual views
7. **Version assets** in production (Vite does this automatically)

## References

- Tailwind CSS v4 Docs: https://tailwindcss.com/docs
- Alpine.js Docs: https://alpinejs.dev/
- Vite Docs: https://vitejs.dev/
- Laravel Blade: https://laravel.com/docs/blade
- Project assets: `resources/css/` and `resources/js/`
