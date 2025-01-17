# SM - Promo Options Manager for WooCommerce

[![Licence](https://img.shields.io/badge/LICENSE-GPL2.0+-blue)](./LICENSE)

- **Developed by:** Martin Nestorov 
    - Founder and Lead Developer at [Smarty Studio](https://smartystudio.net) | Explore more at [nestorov.dev](https://github.com/mnestorov)
- **Plugin URI:** https://github.com/mnestorov/smarty-promo-options-manager

## Overview

The **Promo Options Manager for WooCommerce** plugin empowers online store owners to create and customize promotional labels for their WooCommerce products. It seamlessly integrates with WordPress and WooCommerce to help boost conversions by highlighting discounts and promotional offers effectively.

### Key Highlights:
- Fully customizable promotional labels with colors, text, and styles.
- Add default and additional discount percentages to products and variations.
- Responsive design for optimal display across devices.
- Translation-ready for global eCommerce.

## Features

- **Dynamic Discount Calculations**: Combines existing sale discounts with an additional customizable percentage.
- **Customizable Design**: Adjust the label colors, fonts, and sizes to match your store branding.
- **Multi-Variation Support**: Works seamlessly with WooCommerce product variations.
- **Shortcode Functionality**: Easily display promotional labels with `[smarty_po_label]` shortcode.
- **Free Delivery Indicators**: Highlight free delivery options above a certain price threshold.
- **Translation-Ready**: Add translations directly to the `languages` directory.

## Installation

1. **Download the Plugin**:
   - Clone the repository or [download the latest release](https://github.com/mnestorov/smarty-promo-options-manager/releases).
   
2. **Install via WordPress Admin**:
   - Go to `Plugins > Add New`.
   - Click `Upload Plugin` and select the ZIP file of this plugin.
   - Click `Install Now` and activate the plugin.

3. **Install via FTP**:
   - Extract the plugin ZIP file.
   - Upload the extracted folder to your `/wp-content/plugins/` directory.
   - Activate the plugin from the WordPress admin.

## Usage

### How to Use the Shortcode

To display the promotional label, use the shortcode:

```php
[smarty_po_label]
```

The shortcode automatically adapts to product variations on WooCommerce pages.

### Using the Plugin in Templates

The plugin provides utility functions like `smarty_po_get_variation_label()` to generate promotional labels for specific product variations.

Example usage in a template:

```php
echo smarty_po_get_variation_label($variation_id, 15);
```

## Configuration

Navigate to WooCommerce > Promo Options Manager in the WordPress admin dashboard.

Customize the following settings:

- Label Text: Specify the promotional message to display (e.g., "Use promo code BLACK15").
- Additional Discount: Set the default additional discount percentage.
- Colors: Adjust the background and text colors for the labels.
- Font Sizes: Configure font sizes for desktop and mobile views.

Save your settings and view your labels on product and shop pages.

## Requirements

- WordPress 4.7+ or higher.
- WooCommerce 5.1.0 or higher.
- PHP 7.2+

## Translation

This plugin is translation-ready, and translations can be added to the `languages` directory.

## Changelog

For a detailed list of changes and updates made to this project, please refer to our [Changelog](./CHANGELOG.md).

## Contributing

Contributions are welcome. Please follow the WordPress coding standards and submit pull requests for any enhancements.

## Support The Project

If you find this script helpful and would like to support its development and maintenance, please consider the following options:

- **_Star the repository_**: If you're using this script from a GitHub repository, please give the project a star on GitHub. This helps others discover the project and shows your appreciation for the work done.

- **_Share your feedback_**: Your feedback, suggestions, and feature requests are invaluable to the project's growth. Please open issues on the GitHub repository or contact the author directly to provide your input.

- **_Contribute_**: You can contribute to the project by submitting pull requests with bug fixes, improvements, or new features. Make sure to follow the project's coding style and guidelines when making changes.

- **_Spread the word_**: Share the project with your friends, colleagues, and social media networks to help others benefit from the script as well.

- **_Donate_**: Show your appreciation with a small donation. Your support will help me maintain and enhance the script. Every little bit helps, and your donation will make a big difference in my ability to keep this project alive and thriving.

Your support is greatly appreciated and will help ensure all of the projects continued development and improvement. Thank you for being a part of the community!
You can send me money on Revolut by following this link: **https://revolut.me/mnestorovv**

---

## License

This project is released under the [GPL-2.0+ License](http://www.gnu.org/licenses/gpl-2.0.txt).
