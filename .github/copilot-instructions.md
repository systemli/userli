# Copilot Instructions for Userli Project

## Table of Contents

1. [Security Guidelines](#security-guidelines)
2. [Template Architecture](#template-architecture)
3. [Twig Development Guidelines](#twig-development-guidelines)
4. [JavaScript Guidelines](#javascript-guidelines)
5. [Code Quality Standards](#code-quality-standards)
6. [Best Practices](#best-practices)

## Security Guidelines

### HTML Sanitization and XSS Prevention

This project uses DOMPurify for client-side HTML sanitization to prevent XSS attacks:

#### In Twig Templates

- **NEVER use `|raw` filter** for user-generated or dynamic content
- **USE `|safe_html` filter** for dynamic/user content that needs HTML sanitization
- **USE `|raw` filter ONLY** for trusted static translation strings that contain intentional HTML formatting

```twig
{# ❌ DANGEROUS - Never do this for user content #}
{{ user_content|raw }}

{# ✅ SAFE - Use this for user/dynamic content #}
{{ user_content|safe_html }}

{# ✅ SAFE - Use this for trusted translation strings with HTML #}
{{ "static.translation.with.html"|trans|raw }}
{{ "user.provided.content"|trans({'%user_input%': user_data})|safe_html }}
```

**When to use `|raw` vs `|safe_html`:**

- **`|raw`**: Only for static translation strings from `messages.*.yml` that contain trusted HTML (like `<strong>`, `<a href>`, `<br>`)
- **`|safe_html`**: For all dynamic content, user inputs, or when unsure about HTML source safety

The `|safe_html` filter provides:

- Server-side basic sanitization
- Client-side DOMPurify sanitization
- Allows safe HTML tags: `b`, `i`, `em`, `strong`, `u`, `br`, `p`, `span`, `div`, `a`
- Removes dangerous attributes and JavaScript URLs

#### In JavaScript

- **Always sanitize HTML content** before using `innerHTML`:

```javascript
// ❌ DANGEROUS
element.innerHTML = userContent;

// ✅ SAFE
element.innerHTML = sanitizeHTML(userContent);
```

- Use the global `sanitizeHTML()` function for dynamic content
- Content marked with `data-safe-html` is automatically sanitized on page load

### Form Security

- Always use `{{ form_errors(form) }}` and `{{ form_errors(form.field) }}` to display validation errors
- Use CSRF protection for all forms (enabled by default in Symfony)
- Validate all user input on both client and server side

## Template Architecture

### Base Template Hierarchy

The project uses a clean template hierarchy:

- **`base.html.twig`**: Root template with basic HTML structure and global assets
- **`base_page.html.twig`**: For full-page layouts with header, subtitle, and content areas
- **`base_step.html.twig`**: For step-by-step processes (registration, recovery, etc.)

### Template Block Structure

#### Page Templates (extending `base_page.html.twig`)

```twig
{% extends 'base_page.html.twig' %}

{% block title %}{{ domain }} - {{ "page.title"|trans }}{% endblock %}
{% block page_title %}{{ "welcome.heading"|trans }}{% endblock %}
{% block page_subtitle %}{{ "welcome.description"|trans }}{% endblock %}

{% block page_content %}
    {# Your page content here #}
{% endblock %}
```

#### Step Templates (extending `base_step.html.twig`)

```twig
{% extends 'base_step.html.twig' %}

{% block title %}{{ domain }} - {{ "step.title"|trans }}{% endblock %}
{% block step_title %}{{ "registration.heading"|trans }}{% endblock %}
{% block step_description %}{{ "registration.description"|trans }}{% endblock %}

{% block step_content %}
    {# Your step content here #}
{% endblock %}
```

### Title and Subtitle Handling

- **`{% block title %}`**: Browser tab titles (format: `{{ domain }} - {{ "page.title"|trans }}`)
- **`{% block page_title %}`**: Main page headings (H1)
- **`{% block page_subtitle %}`**: Page descriptions (automatically styled by base template)
- **`{% block step_title %}`**: Step process headings
- **`{% block step_description %}`**: Step process descriptions

> **Note**: Subtitle styling is automatically applied - just provide the text content without HTML wrapper.

## Twig Development Guidelines

### HTML Structure

- **Use semantic HTML**: Always use appropriate HTML elements (`<header>`, `<main>`, `<section>`, `<article>`, `<nav>`, `<aside>`, `<footer>`)
- **Ensure accessibility**: Include proper ARIA attributes, alt text for images, and semantic markup
- **Responsive layout**: All layouts must be responsive and work across different device sizes
- **Proper heading hierarchy**: Follow h1 → h2 → h3 structure

### CSS Styling

- **Use Tailwind Utility Classes**: Style components using Tailwind CSS utility classes instead of custom CSS
- **Utility-first approach**: Prefer utility classes for consistent design system
- **Responsive utilities**: Use responsive prefixes (`sm:`, `md:`, `lg:`, `xl:`)
- **Consistent spacing**: Use Tailwind spacing utilities for consistent layouts

### Content and Translations

- **Use Symfony Translations**: Always use `{{ 'key'|trans }}` or `{% trans %}` for all user-facing text
- **No hardcoded strings**: Never include custom strings directly in templates
- **Translation parameters**: Use proper parameter syntax `{{ 'key'|trans({'%param%': value}) }}`
- **Follow naming conventions**: Use consistent translation key patterns

### Error Handling in Templates

Always include proper error display in forms:

```twig
{% form_theme form 'Form/fields.html.twig' %}

{{ form_start(form) }}
    {{ form_errors(form) }}

    {{ form_row(form.field) }}
    {{ form_errors(form.field) }}
{{ form_end(form) }}
```

### Modern Layout Patterns

- **Avoid nested card layouts**: Use single-level cards for cleaner design
- **Use step-like layouts**: For multi-step processes
- **Implement responsive grids**: With Tailwind's grid system
- **Single responsibility**: Each template block should have one clear purpose

## JavaScript Guidelines

### Security

- **Always sanitize dynamic HTML**: Use `sanitizeHTML()` before setting `innerHTML`
- **Validate user input**: Both client and server side
- **Avoid inline event handlers**: Use proper event delegation

### Code Organization

- **Use modern JavaScript**: ES6+ features are supported
- **Event delegation**: Prefer event delegation for dynamic content
- **Modular functions**: Keep functions small and focused
- **Clear naming**: Use descriptive function and variable names

### DOMPurify Integration

```javascript
// For dynamic content
element.innerHTML = sanitizeHTML(userContent);

// Automatic processing for marked elements
<div data-safe-html>{{ content }}</div>;
```

## Code Quality Standards

### General Principles

- **DRY (Don't Repeat Yourself)**: Avoid code duplication
- **KISS (Keep It Simple, Stupid)**: Prefer simple, clear solutions
- **Separation of concerns**: Keep template logic, styling, and behavior separate
- **Consistency**: Follow established patterns throughout the project

### Template Quality

- **Remove redundant code**: Eliminate unused blocks and duplicate content
- **Use semantic elements**: Choose HTML elements based on meaning, not appearance
- **Consistent naming**: Use clear, descriptive names for blocks and variables
- **Optimize for accessibility**: Screen readers, keyboard navigation, focus management

### Performance Considerations

- **Minimize HTTP requests**: Use Webpack Encore for asset bundling
- **Optimize images**: Use appropriate formats and sizes
- **Lazy loading**: For non-critical content
- **Efficient selectors**: Use specific CSS selectors to avoid unnecessary styling

## Best Practices

### Language and Tone

- **Use inclusive language**: Welcoming and accessible to all users
- **Informal tone**: Friendly, approachable manner rather than formal
- **Clear communication**: Simple, understandable language
- **User-focused**: Consider the target audience in all content

### Development Workflow

1. **Security first**: Always consider security implications
2. **Accessibility by design**: Include accessibility from the start
3. **Mobile-first**: Design for mobile, enhance for desktop
4. **Test thoroughly**: Across different browsers and device sizes
5. **Document changes**: Update relevant documentation

### Testing Guidelines

- **Cross-browser testing**: Ensure compatibility across major browsers
- **Responsive testing**: Test on various screen sizes
- **Accessibility testing**: Use screen readers and keyboard navigation
- **Performance testing**: Monitor loading times and resource usage
- **Security testing**: Validate input sanitization and XSS prevention

### Example Complete Template

```twig
{% extends 'base_page.html.twig' %}

{% block title %}{{ domain }} - {{ "welcome.title"|trans }}{% endblock %}

{% block page_title %}{{ "welcome.heading"|trans({'%domain%': domain}) }}{% endblock %}

{% block page_subtitle %}{{ "welcome.description"|trans }}{% endblock %}

{% block page_content %}
    <div class="max-w-4xl mx-auto">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <section class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h2 class="text-xl font-semibold mb-4">
                    {{ "section.title"|trans }}
                </h2>
                <p class="text-gray-600 mb-4">
                    {{ "section.description"|trans }}
                </p>
                <a href="{{ path('route_name') }}"
                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    {{ "action.button"|trans }}
                </a>
            </section>
        </div>
    </div>
{% endblock %}
```
