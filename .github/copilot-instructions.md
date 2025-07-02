# Copilot Instructions for Userli Project

## Twig Template Guidelines

When working with Twig templates in this project, please follow these guidelines:

### HTML Structure

- **Use semantic HTML**: Always use appropriate HTML elements that convey meaning (e.g., `<header>`, `<main>`, `<section>`, `<article>`, `<nav>`, `<aside>`, `<footer>`)
- **Ensure accessibility**: Include proper ARIA attributes, alt text for images, and semantic markup for screen readers
- **Responsive layout**: All layouts must be responsive and work across different device sizes

### CSS Styling

- **Use Tailwind Utility Classes**: Style components using Tailwind CSS utility classes instead of custom CSS
- Prefer utility-first approach for consistent design system
- Use responsive utilities (e.g., `sm:`, `md:`, `lg:`, `xl:`) for responsive behavior

### Content and Translations

- **Use Symfony Translations**: Always use translation functions (`{{ 'key'|trans }}` or `{% trans %}`) for all user-facing text
- **No hardcoded strings**: Never include custom strings directly in templates - all text must be translatable
- Ensure translation keys follow the project's naming conventions

### Language and Tone

- **Use inclusive language**: Choose words and phrases that are welcoming and accessible to all users
- **Use informal tone**: Write in a friendly, approachable manner rather than formal or technical language
- Consider the project's target audience when crafting content

### Example Template Structure

```twig
{% extends 'base.html.twig' %}

{% block title %}{{ 'page.title'|trans }}{% endblock %}

{% block body %}
<main class="container mx-auto px-4 py-8">
    <header class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">
            {{ 'welcome.heading'|trans }}
        </h1>
    </header>

    <section class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <article class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">
                {{ 'section.title'|trans }}
            </h2>
            <p class="text-gray-600">
                {{ 'section.description'|trans }}
            </p>
        </article>
    </section>
</main>
{% endblock %}
```

### Additional Notes

- Test templates across different screen sizes
- Validate HTML for semantic correctness
- Ensure proper heading hierarchy (h1 → h2 → h3, etc.)
- Include focus management for interactive elements
- Use descriptive link text and button labels
