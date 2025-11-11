# WP Pronotron Art Direction Images WordPress Plugin

## Motivation
Although the <picture> tag was introduced back in 2014, its utilization on the web remains rare due to the complexity of implementation. Its benefits are manifold, ranging from being a crucial SEO quality signal to enhancing UI/UX.

## Archive Notes
This repository represents my first experiment in building an aspect-ratio aware image pipeline. It later evolved into a SaaS solution powered by client-side WebAssembly and cloud storage. I’ve archived this version publicly to serve as a reference and inspiration for others. It also demonstrates best practices in WordPress plugin development.
Using <picture> allows you to serve an image format that perfectly fits the client’s device. Traditionally, achieving this would require cropping or hiding parts of the image, often with additional styling or layout adjustments.

<div align="right">
	<sub>Created by <a href="https://www.linkedin.com/in/yunusbayraktaroglu/">Yunus Bayraktaroglu</a> with ❤️</sub>
</div>

#### WordPress Plugin - WP Pronotron Art Direction Images
- Crop images with user defined image aspect ratios via admin UI.
- Auto-generates `<picture>...sources</picture>` media items with applied media orientation
- Supports webp

![Art direction images](https://github.com/yunusbayraktaroglu/wp-pronotron-art-direction-images/blob/main/manual/art-direction-images.jpg)
