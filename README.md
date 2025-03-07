# FAL AI Image Generator

A WordPress plugin that allows you to generate AI images using the FAL AI API directly from your WordPress admin dashboard.

## Description

FAL AI Image Generator integrates with the FAL AI API to bring powerful AI image generation capabilities to your WordPress site. This plugin allows administrators to generate images by providing text prompts, manage the generated images, and monitor the generation process—all from within the WordPress admin interface.

## Features

- **Easy API Integration**: Simply enter your FAL AI API key in the settings page to get started.
- **Custom Image Generation**: Create AI-generated images by providing text prompts.
- **Multiple Model Support**: Choose from different AI models including fast-sdxl and flux/dev.
- **Batch Processing**: Generate multiple images in a single request.
- **Image Management**: View, organize, and manage all your AI-generated images.
- **Real-time Status Updates**: Monitor the status of your image generation requests in real-time.
- **Responsive Design**: View your generated images across different devices with a responsive image gallery.

## Installation

1. Download the `fal-ai-image-generator.php` file.
2. Go to your WordPress admin dashboard → Plugins → Add New → Upload Plugin.
3. Browse for the `fal-ai-image-generator.php` file and click "Install Now".
4. Activate the plugin through the 'Plugins' menu in WordPress.
5. Go to "FAL AI Images" → "Settings" and enter your FAL AI API key.

## Usage

### Configuring the Plugin

1. Navigate to "FAL AI Images" → "Settings" in your WordPress admin menu.
2. Enter your FAL AI API key and save the settings.

### Generating Images

1. Go to "FAL AI Images" → "Generate New" in your WordPress admin menu.
2. Enter your text prompt in the text area.
3. Select the number of images you want to generate (1-10).
4. Choose the AI model from the dropdown menu.
5. Click "Generate Images" to submit your request.

### Viewing Generated Images

1. Navigate to "FAL AI Images" in your WordPress admin menu.
2. You'll see a table of all your generation requests, including their status.
3. Click "View Images" on any completed request to see the generated images.
4. From the image details view, you can:
   - See image metadata (dimensions, content type, seed)
   - Check if any images were flagged as NSFW
   - Open the full-size image in a new tab

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- A valid FAL AI API key (obtain from [FAL AI website](https://fal.ai))

## Technical Details

- The plugin creates two custom database tables to store request and image data:
  - `wp_fal_ai_requests`: Stores information about generation requests
  - `wp_fal_ai_images`: Stores information about individual generated images
- Automatic status checking for pending requests every 10 seconds on the images page
- Handles API requests and responses using WordPress HTTP API
- Uses AJAX for real-time updates without page refreshes

## Frequently Asked Questions

**Q: Do I need an account with FAL AI to use this plugin?**  
A: Yes, you need to register with FAL AI and obtain an API key.

**Q: How long does it take to generate images?**  
A: Generation time varies based on the AI model selected, prompt complexity, and FAL AI's current queue status. Typically it ranges from a few seconds to a minute.

**Q: Is there a limit to how many images I can generate?**  
A: The plugin allows generating up to 10 images per request. Your overall usage may be limited by your FAL AI account tier.

**Q: Are the generated images stored on my WordPress site?**  
A: No, the plugin stores links to the images hosted on FAL AI's servers, not the actual image files.

**Q: What happens if an image generation fails?**  
A: Failed generations will be marked with a "FAILED" status in the requests table.

## Credits

This plugin was developed to provide easy access to FAL AI's image generation capabilities within WordPress.

## License

This plugin is licensed under the GPL v2 or later.

## Changelog

### 1.0.0
- Initial release

## Support

For support, please create an issue in the plugin's repository or contact the author.