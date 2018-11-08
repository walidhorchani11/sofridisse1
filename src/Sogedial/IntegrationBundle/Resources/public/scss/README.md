# Sass architecture

This project is using the [7-1 architecture pattern](http://sass-guidelin.es/#architecture) and sticking to [Sass Guidelines](http://sass-guidelin.es) writing conventions.

Each folder of this local directory has its own `README.md` file to explain the purpose and add extra information. Be sure to browse the repository to see how it works.

Some useful articles to better understand the choice of this architecture as well as some other features:

- [Sass basics](http://sass-lang.com/guide)
- [An opinionated styleguide for writing sane, maintainable and scalable Sass](https://sass-guidelin.es)
- [Architecture for a Sass project](https://www.sitepoint.com/architecture-sass-project)
- [Aesthetic Sass 1: Architecture and Style Organization](https://scotch.io/tutorials/aesthetic-sass-1-architecture-and-style-organization)
- [Aesthetic Sass 2: Colors and Palettes](https://scotch.io/tutorials/aesthetic-sass-2-colors)
- [Aesthetic Sass 3: Typography and Vertical Rhythm](https://scotch.io/tutorials/aesthetic-sass-3-typography-and-vertical-rhythm)

## Main file

The main file (labelled `styles.scss`) shall be the only Sass file in the `public/scss` directory not to begin with an underscore (the rest of the files must all be Sass partials). This file should not contain anything but `@import` and comments.

*Note: when using [Eyeglass](https://github.com/sass-eyeglass/eyeglass) for distribution, it might be a fine idea to name this file `index.scss` rather than `styles.scss` in order to stick to [Eyeglass modules specifications](https://github.com/sass-eyeglass/eyeglass#writing-an-eyeglass-module-with-sass-files). See [#21](https://github.com/HugoGiraudel/sass-boilerplate/issues/21) for reference.*

Reference: [Sass Guidelines](http://sass-guidelin.es/) > [Architecture](http://sass-guidelin.es/#architecture) > [Main file](http://sass-guidelin.es/#main-file)
